require("dotenv").config();
const express = require('express');
const axios = require('axios');
const { exec } = require('child_process');
const fs = require('fs');
const util = require('util');
const execPromise = util.promisify(exec);

const app = express();
app.use(express.json());

// Rate limiting
const rateLimit = require("express-rate-limit");
const chatLimiter = rateLimit({
  windowMs: 60 * 1000,
  max: 20,
  message: { error: "Too many requests, please try again later" },
  standardHeaders: true,
  legacyHeaders: false
});
app.use("/api/chat", chatLimiter);

// Configuración
const MOLTBOT_GATEWAY = process.env.MOLTBOT_GATEWAY || 'http://localhost:3100';
const MOLTBOT_TOKEN = process.env.MOLTBOT_TOKEN || 'natalia-coordinator-token-2026';
const PORT = process.env.PORT || 18790;
const RAG_SERVICE = process.env.RAG_SERVICE || 'http://localhost:9000';
const DEEPSEEK_API = process.env.DEEPSEEK_API_URL || 'https://api.deepseek.com/chat/completions';
const DEEPSEEK_KEY = process.env.DEEPSEEK_API_KEY || '';
const SESSIONS_FILE = process.env.SESSIONS_FILE || '/var/lib/natalia-whatsapp/sessions.json';
// ==================== SISTEMA DE SESIONES (SQLite) ====================
const Database = require('better-sqlite3');
const SESSIONS_DB = SESSIONS_FILE.replace('.json', '.db');
const db = new Database(SESSIONS_DB);

// Crear tabla si no existe
db.exec("CREATE TABLE IF NOT EXISTS sessions (phone TEXT PRIMARY KEY, messages TEXT NOT NULL DEFAULT '[]', last_activity INTEGER NOT NULL, first_interaction INTEGER NOT NULL)");

// Migrar datos desde JSON si existe
(function migrateFromJson() {
  try {
    if (fs.existsSync(SESSIONS_FILE)) {
      const data = JSON.parse(fs.readFileSync(SESSIONS_FILE, 'utf8'));
      const insert = db.prepare('INSERT OR IGNORE INTO sessions (phone, messages, last_activity, first_interaction) VALUES (?, ?, ?, ?)');
      const tx = db.transaction((sessions) => {
        for (const [phone, session] of sessions) {
          insert.run(phone, JSON.stringify(session.messages || []), session.lastActivity || Date.now(), session.firstInteraction || Date.now());
        }
      });
      tx(data);
      const count = db.prepare('SELECT COUNT(*) as c FROM sessions').get().c;
      console.log('[Session SQLite] Migradas ' + count + ' sesiones desde JSON');
      fs.renameSync(SESSIONS_FILE, SESSIONS_FILE + '.migrated');
    }
  } catch (e) {
    console.log('[Session SQLite] No JSON to migrate:', e.message);
  }
})();

// Prepared statements
const stmtGet = db.prepare('SELECT * FROM sessions WHERE phone = ?');
const stmtUpsert = db.prepare('INSERT INTO sessions (phone, messages, last_activity, first_interaction) VALUES (?, ?, ?, ?) ON CONFLICT(phone) DO UPDATE SET messages = excluded.messages, last_activity = excluded.last_activity');
const stmtCleanup = db.prepare('DELETE FROM sessions WHERE last_activity < ?');

function getSession(phoneNumber) {
  if (!phoneNumber) return { messages: [], lastActivity: Date.now() };

  const row = stmtGet.get(phoneNumber);
  if (row) {
    return {
      messages: JSON.parse(row.messages),
      lastActivity: row.last_activity,
      firstInteraction: row.first_interaction
    };
  }

  const session = { messages: [], lastActivity: Date.now(), firstInteraction: Date.now() };
  stmtUpsert.run(phoneNumber, '[]', session.lastActivity, session.firstInteraction);
  console.log('[Session] Nueva sesion: ' + phoneNumber);
  return session;
}

function addMessageToSession(phoneNumber, role, content) {
  if (!phoneNumber) return [];

  const session = getSession(phoneNumber);
  session.messages.push({ role, content, timestamp: Date.now() });

  if (session.messages.length > 250) {
    session.messages = session.messages.slice(-250);
  }

  stmtUpsert.run(phoneNumber, JSON.stringify(session.messages), Date.now(), session.firstInteraction);
  console.log('[Session] ' + phoneNumber + ': ' + session.messages.length + ' mensajes');
  return session.messages;
}

// Cleanup expired sessions (1 year)
setInterval(() => {
  const cutoff = Date.now() - 365 * 24 * 60 * 60 * 1000;
  const result = stmtCleanup.run(cutoff);
  if (result.changes > 0) console.log('[Session] Limpiadas ' + result.changes + ' sesiones');
}, 60 * 60 * 1000);

const sessionCount = db.prepare('SELECT COUNT(*) as c FROM sessions').get().c;
console.log('[Session SQLite] Inicializado con ' + sessionCount + ' sesiones');
console.log('[Session SQLite] DB: ' + SESSIONS_DB);

// Graceful shutdown
process.on('SIGTERM', () => { db.close(); process.exit(0); });
process.on('SIGINT', () => { db.close(); process.exit(0); });



// Almacenar el número de teléfono del usuario actual
let currentUserPhone = null;

// Endpoint de metricas
app.get('/status', (req, res) => {
  const os = require('os');
  const uptime = process.uptime();
  const memUsage = process.memoryUsage();
  const totalSessions = db.prepare('SELECT COUNT(*) as count FROM sessions').get();
  const activeSessions = db.prepare("SELECT COUNT(*) as count FROM sessions WHERE last_activity > (strftime('%s','now') - 3600)").get();
  res.json({
    status: 'running',
    uptime_seconds: Math.floor(uptime),
    uptime_human: Math.floor(uptime/3600) + 'h ' + Math.floor((uptime%3600)/60) + 'm',
    memory: {
      rss_mb: Math.round(memUsage.rss / 1048576),
      heap_mb: Math.round(memUsage.heapUsed / 1048576)
    },
    system: {
      load: os.loadavg(),
      free_mem_mb: Math.round(os.freemem() / 1048576),
      total_mem_mb: Math.round(os.totalmem() / 1048576)
    },
    sessions: {
      total: totalSessions.count,
      active_1h: activeSessions.count
    },
    services: {
      bridge: 'natalia-whatsapp-bridge:18790',
      moltbot: 'moltbot-gateway:3100',
      webhook: 'whatsapp-webhook:3002',
      model: 'deepseek-chat'
    }
  });
});

// Endpoint de salud
app.get('/health', (req, res) => {
  res.json({ status: 'healthy', agent: 'natalia-whatsapp-bridge', rag_enabled: true });
});

// Función para detectar contexto inmobiliario en la conversación
function detectRealEstateContext(messages) {
  // Keywords que indican que estamos en contexto inmobiliario
  const realEstateKeywords = ['salado', 'resort', 'apartamento', 'punta cana', 'golf', 
    'playa', 'inmobiliaria', 'propiedad', 'desarrollo', 'inversión', 'plano', 'planos', 'bloque', 'tipología', 'tipologia', 'tipo a', 'tipo b', 'tipo c', 'tipo d', 'tipo e', 'masterplan', 'arquitect'];
  
  // Analizar los últimos 4 mensajes (2 intercambios)
  const recentMessages = messages.slice(-4);
  
  for (const msg of recentMessages) {
    const content = (msg.content || '').toLowerCase();
    if (realEstateKeywords.some(kw => content.includes(kw))) {
      return true;
    }
  }
  
  return false;
}

// Endpoint principal para recibir mensajes de WhatsApp
app.post('/api/chat', async (req, res) => {
  try {
    // Log completo de la request para debugging
    console.log('[Natalia WhatsApp] Request headers:', JSON.stringify(req.headers, null, 2));
    console.log('[Natalia WhatsApp] Request body keys:', Object.keys(req.body));

        const { messages, max_tokens = 500, user_phone, user, metadata } = req.body;

    // ==================== MANEJO DE SESIONES ====================
    // Intentar extraer el número de teléfono
    const phoneNumber = user_phone || user || metadata?.phone || metadata?.from || req.headers['x-user-phone'];

    if (!phoneNumber) {
      console.warn('[Natalia WhatsApp] ⚠️  No phone number - contexto no se guardará');
    } else {
      console.log('[Natalia WhatsApp] 📱 Phone:', phoneNumber);
    }

    // Obtener mensaje del usuario actual y manejar sesión
    let userMessage = '';
    let messagesArray = [];

    if (messages && Array.isArray(messages) && messages.length > 0) {
      userMessage = messages[messages.length - 1]?.content || '';

      // Si solo viene 1 mensaje, recuperar historial de sesión
      if (messages.length === 1 && phoneNumber) {
        // Agregar mensaje actual a la sesión
        addMessageToSession(phoneNumber, 'user', userMessage);
        
        // Usar historial completo de la sesión
        const session = getSession(phoneNumber);
        messagesArray = session.messages;
        console.log('[Session] 🔄 Recuperado: ' + messagesArray.length + ' mensajes');
      } else {
        // Si viene historial completo, usarlo y actualizar sesión
        messagesArray = messages;
        
        if (phoneNumber) {
          // Sincronizar sesión
          const session = getSession(phoneNumber);
          session.messages = messages;
          stmtUpsert.run(phoneNumber, JSON.stringify(session.messages), Date.now(), session.firstInteraction || Date.now());
          console.log('[Session] 🔄 Sincronizado: ' + messages.length + ' mensajes');
        }
      }
    } else {
      return res.status(400).json({ error: 'Invalid request: messages array required' });
    }

    // Continuar con detección de primera interacción
    const userMessages = messagesArray.filter(msg => msg.role === 'user');
    const assistantMessages = messagesArray.filter(msg => msg.role === 'assistant');
    const isFirstInteraction = userMessages.length === 1 && assistantMessages.length === 0;

    console.log('[Natalia WhatsApp] 💬 Mensaje:', userMessage.substring(0, 50));
    console.log('[Natalia WhatsApp] 📱 Phone:', phoneNumber || 'desconocido');
    console.log('[Natalia WhatsApp] 📊 Longitud:', messagesArray.length);
    console.log('[Natalia WhatsApp] 👋 Primera interacción:', isFirstInteraction);
    // ============================================================

    // Buscar contexto en RAG
    let ragContext = '';
    let imageUrls = [];
    
    // Keywords principales de inmobiliaria
    const primaryKeywords = ['salado', 'resort', 'apartamento', 'punta cana', 'golf', 'playa', 
      'inmobiliaria', 'propiedad', 'desarrollo', 'inversión', 'plano', 'planos', 'bloque', 'tipología', 'tipologia', 'tipo a', 'tipo b', 'tipo c', 'tipo d', 'tipo e', 'masterplan', 'arquitect'];
    
    // Keywords de seguimiento (indican preguntas de follow-up)
    const followUpKeywords = ['barato', 'económico', 'precio', 'costo', 'cuál', 'cuánto', 
      'más', 'mejor', 'disponible', 'tiene', 'hay', 'opciones', 'unidades', 'habitaciones',
      'metros', 'm²', 'tamaño', 'superficie', 'pago', 'financiamiento', 'entrega', 'amenidades', 'servicios', 'facilidades', 'ubicación', 'ubicacion'];
    
    // Keywords de imágenes
    const imageKeywords = ['foto', 'imagen', 'picture', 'exterior', 'interior', 'muestra', 'ver', 'envia', 'envíame', 'muéstrame', 'enseña', 'fotos', 'imágenes', 'galeria', 'visual', 'plano', 'planos', 'distribución', 'distribucion', 'layout'];
    
    // Combinar todas las keywords
    const allKeywords = [...primaryKeywords, ...imageKeywords];
    
    // Detectar si el mensaje actual tiene keywords
    const hasPrimaryKeyword = primaryKeywords.some(kw => userMessage.toLowerCase().includes(kw));
    const hasFollowUpKeyword = followUpKeywords.some(kw => userMessage.toLowerCase().includes(kw));
    const hasImageKeyword = imageKeywords.some(kw => userMessage.toLowerCase().includes(kw));
    
    // Detectar contexto inmobiliario en mensajes previos
    const hasRealEstateContext = detectRealEstateContext(messages);
    
    // Decidir si buscar en RAG
    const shouldSearchRAG = hasPrimaryKeyword || 
                            (hasRealEstateContext && hasFollowUpKeyword) ||
                            (hasRealEstateContext && messages.length <= 10); // Mantener contexto en conversaciones cortas
    
    const asksForPhotos = hasImageKeyword;
    
    console.log('[Natalia WhatsApp] Primary keyword:', hasPrimaryKeyword);
    console.log('[Natalia WhatsApp] Follow-up keyword:', hasFollowUpKeyword);
    console.log('[Natalia WhatsApp] Real estate context:', hasRealEstateContext);
    console.log('[Natalia WhatsApp] Should search RAG:', shouldSearchRAG);

    if (shouldSearchRAG) {
      try {
        console.log('[Natalia WhatsApp] Buscando en RAG...');
        
        // Si es un follow-up sin primary keyword, agregar contexto a la query
        let ragQuery = userMessage;
        if (!hasPrimaryKeyword && hasRealEstateContext) {
          ragQuery = 'Salado apartamentos ' + userMessage;
          console.log('[Natalia WhatsApp] Query expandida con contexto:', ragQuery);
        }
        
        const ragQueryResponse = await axios.post(`${RAG_SERVICE}/query`, {
          query: ragQuery,
          collection: 'marketing-inmobiliaria',
          top_k: 15
        }, {
          timeout: 45000
        });

        if (ragQueryResponse.data && ragQueryResponse.data.context_used) {
          ragContext = ragQueryResponse.data.context_used;
          const sources = ragQueryResponse.data.sources || [];
          imageUrls = sources
            .map(s => s.payload?.image_url)
            .filter(url => url && url.startsWith('http'));

          console.log('[Natalia WhatsApp] Contexto RAG obtenido');
          if (imageUrls.length > 0) {
            console.log('[Natalia WhatsApp] Imágenes encontradas:', imageUrls.length);
          }
        }
      } catch (ragError) {
        console.warn('[Natalia WhatsApp] RAG query failed:', ragError.message);
      }
    }



    // Si pide fotos en contexto inmobiliario pero no especificó categoría, enviar fotos por defecto
    if (asksForPhotos && imageUrls.length === 0) {
      const defaultUrls = [
        'https://natalia.soporteclientes.net/images/salado-amenidades-1.jpg',  // Piscina principal
        'https://natalia.soporteclientes.net/images/salado-playa-1.jpg',       // Acceso a playa
        'https://natalia.soporteclientes.net/images/salado-golf-1.jpg'         // Campo de golf
      ];
      imageUrls = defaultUrls;
      console.log('[Natalia WhatsApp] Fotos por defecto del resort agregadas (solicitud genérica)');
    }

    // ============= SISTEMA INTELIGENTE DE SELECCIÓN DE IMÁGENES =============
    
    const contextos = {
      piscina: /\b(piscina|pool|alberca|nadar|swim|jacuzzi)\b/i,
      fachada: /\b(fachada|facade|edificio|building|exterior|arquitectura)\b/i,
      playa: /\b(playa|beach|mar|sea|arena|sand|costa|shore|kayak|pier)\b/i,
      golf: /\b(golf|campo|course|green|hoyo|hole)\b/i,
      ubicacion: /\b(ubicación|location|donde|where|mapa|map|direccion|address|cerca|near)\b/i,
      interiores: /\b(interior|interiores|habitacion|habitaciones|room|rooms|lobby|restaurante|cocina|sala)\b/i
    };
    
    const imagenesPorCategoria = {
      piscina: [
        'https://natalia.soporteclientes.net/images/salado-piscina-8.jpg',
        'https://natalia.soporteclientes.net/images/salado-amenidad-1.jpg',
        'https://natalia.soporteclientes.net/images/salado-amenidad-6.jpg',
        'https://natalia.soporteclientes.net/images/salado-amenidad-3.jpg',
        'https://natalia.soporteclientes.net/images/salado-amenidad-4.jpg',
        'https://natalia.soporteclientes.net/images/salado-amenidad-5.jpg',
        'https://natalia.soporteclientes.net/images/salado-piscina-7.jpg'
      ],
      fachada: [
        'https://natalia.soporteclientes.net/images/salado-amenidad-2.jpg',
        'https://natalia.soporteclientes.net/images/salado-edificio-1.jpg'
      ],
      playa: [
        'https://natalia.soporteclientes.net/images/salado-playa-2.jpg',
        'https://natalia.soporteclientes.net/images/salado-playa-1.jpg',
        'https://natalia.soporteclientes.net/images/salado-playa-4.jpg',
        'https://natalia.soporteclientes.net/images/salado-playa-3.jpg',
        'https://natalia.soporteclientes.net/images/salado-playa-5.jpg',
        'https://natalia.soporteclientes.net/images/salado-playa-6.jpg'
      ],
      golf: [
        'https://natalia.soporteclientes.net/images/salado-golf-1.jpg'
      ],
      ubicacion: [
        'https://natalia.soporteclientes.net/images/salado-ubicacion-2.jpg',
        'https://natalia.soporteclientes.net/images/salado-ubicacion-1.jpg'
      ]
    };
    
    if (hasImageKeyword) {
      let contextosDetectados = [];
      
      if (contextos.piscina.test(userMessage)) contextosDetectados.push('piscina');
      if (contextos.fachada.test(userMessage)) contextosDetectados.push('fachada');
      if (contextos.playa.test(userMessage)) contextosDetectados.push('playa');
      if (contextos.golf.test(userMessage)) contextosDetectados.push('golf');
      if (contextos.ubicacion.test(userMessage)) contextosDetectados.push('ubicacion');
      if (contextos.interiores.test(userMessage)) contextosDetectados.push('interiores');
      
      console.log('[Image Selection] Contextos detectados:', contextosDetectados.join(', ') || 'general');
      
      if (contextosDetectados.length > 0) {
        imageUrls = [];
        
        if (contextosDetectados.includes('piscina') && contextosDetectados.includes('fachada')) {
          imageUrls = [
            'https://natalia.soporteclientes.net/images/salado-amenidad-1.jpg',
            'https://natalia.soporteclientes.net/images/salado-amenidad-5.jpg',
            'https://natalia.soporteclientes.net/images/salado-amenidad-6.jpg'
          ];
          console.log('[Image Selection] Mostrando: piscinas CON fachada visible');
        }
        else if (contextosDetectados.includes('fachada') && !contextosDetectados.includes('piscina')) {
          imageUrls = imagenesPorCategoria.fachada;
          console.log('[Image Selection] Mostrando: solo fachadas/edificios');
        }
        else if (contextosDetectados.includes('piscina')) {
          imageUrls = imagenesPorCategoria.piscina;
          console.log('[Image Selection] Mostrando: piscinas prioritarias');
        }
        else if (contextosDetectados.includes('interiores')) {
          imageUrls = [];
          console.log('[Image Selection] ⚠️  No hay fotos de interiores disponibles');
        }
        else {
          contextosDetectados.forEach(contexto => {
            if (imagenesPorCategoria[contexto]) {
              imageUrls = imageUrls.concat(imagenesPorCategoria[contexto]);
            }
          });
          console.log('[Image Selection] Mostrando: ' + contextosDetectados.join(', '));
        }
      } else if (imageUrls.length === 0) {
        imageUrls = [
          'https://natalia.soporteclientes.net/images/salado-piscina-8.jpg',
          'https://natalia.soporteclientes.net/images/salado-playa-2.jpg',
          'https://natalia.soporteclientes.net/images/salado-golf-1.jpg'
        ];
        console.log('[Image Selection] Mostrando: imágenes generales');
      }
    }
    
    // ============= FIN SISTEMA INTELIGENTE =============

    // Preparar system prompt
    let systemPrompt = getNataliaSystemPrompt(isFirstInteraction);
    if (ragContext) {
      systemPrompt += `\n\nCONTEXTO ADICIONAL:\n${ragContext}`;
    }
    if (imageUrls.length > 0 && asksForPhotos) {
      systemPrompt += `\n\nIMPORTANTE: El usuario pidió ver fotos/imágenes.
Tienes ${imageUrls.length} imágenes disponibles que se enviarán AUTOMÁTICAMENTE como archivos adjuntos.
NO menciones que envías fotos, NO digas "te envío", simplemente describe qué fotos son.
Responde BREVEMENTE, por ejemplo:
"Aquí tienes vistas del resort en Punta Cana 🏖️"`;
    } else if (imageUrls.length === 0 && hasImageKeyword) {
      systemPrompt += `\n\nIMPORTANTE: El usuario pidió ver fotos/imágenes pero NO tienes fotos disponibles de lo que solicita.
Responde HONESTAMENTE que no tienes esas fotos disponibles.
Ofrece mostrar las fotos que SÍ tienes: piscinas, fachadas, playa o campo de golf.
Ejemplo: "Lo siento, actualmente no tengo fotos de interiores disponibles. ¿Te gustaría ver la piscina, las fachadas del edificio, la playa o el campo de golf?"`;
    }

    // Llamar a DeepSeek
    const deepseekResponse = await axios.post(DEEPSEEK_API, {
      model: 'deepseek-chat',
      messages: [
        { role: 'system', content: systemPrompt },
        ...messagesArray.map(m => ({ role: m.role, content: m.content }))
      ],
      max_tokens,
      temperature: 0.7
    }, {
      headers: {
        'Authorization': `Bearer ${DEEPSEEK_KEY}`,
        'Content-Type': 'application/json'
      },
      timeout: 30000
    });

    const response = deepseekResponse.data;
    let assistantMessage = response.choices?.[0]?.message?.content;

    if (!assistantMessage) {
      throw new Error('No response from DeepSeek');
    }

    console.log('[Natalia WhatsApp] Response:', assistantMessage.substring(0, 100));

    // Guardar respuesta del asistente en la sesión
    if (phoneNumber && assistantMessage) {
      addMessageToSession(phoneNumber, 'assistant', assistantMessage);
      console.log('[Session] ✅ Respuesta guardada');
    }

    // Si hay imágenes, preparar para envío
    const imagesToSend = imageUrls.length > 0 ? imageUrls.slice(0, 3) : [];

    // Responder al usuario
    const responseData = {
      id: response.id || 'natalia-' + Date.now(),
      object: 'chat.completion',
      model: 'natalia-rag-deepseek',
      choices: [{
        message: {
          role: 'assistant',
          content: assistantMessage,
          mediaUrls: imagesToSend  // URLs de imágenes dentro del mensaje
        },
        finish_reason: response.choices[0].finish_reason || 'stop'
      }],
      usage: response.usage || {},
      rag_used: !!ragContext,
      images_found: imageUrls.length
    };

    if (imagesToSend.length > 0) {
      console.log('[Natalia WhatsApp] Agregadas', imagesToSend.length, 'URLs en mediaUrls:', imagesToSend);
    }

    res.json(responseData);

  } catch (error) {
    console.error('[Natalia WhatsApp] Error:', error.message);
    res.status(500).json({
      error: 'Internal server error',
      details: error.message
    });
  }
});

// System prompt de Natalia
function getNataliaSystemPrompt(isFirstInteraction) {
  const basePrompt = `Eres Natalia, la mejor agente inmobiliaria del mundo, especializada en Punta Cana.

TU IDENTIDAD:
- Agente exclusiva de atencion al cliente de UNIVERSO SALADO (Salado 1, Salado 2 y Salado 3)
- Experta en bienes raices de lujo en el Caribe. Universo Salado es un macro-proyecto de 3 fases integradas en White Sands, Bavaro, Punta Cana
- Conocedora profunda de cada aspecto del desarrollo Salado Golf & Beach
- Profesional de élite con pasión por ayudar a encontrar la propiedad perfecta

TU PERSONALIDAD:
- Entusiasta y apasionada por Punta Cana y Salado Golf & Beach
- Cálida, cercana y profesional al mismo tiempo
- Consultora experta que escucha primero y luego asesora
- Resolutiva: conviertes dudas en claridad, preguntas en soluciones
- Detallista: cada cliente merece información completa y precisa

TU MISIÓN:
- Resolver todas las dudas sobre Salado Golf & Beach Resort
- Proporcionar información detallada, precisa y útil
- Ayudar a los clientes a tomar decisiones informadas sobre su inversión
- Ser la guía confiable en el proceso de compra o inversión
- Destacar las ventajas únicas de vivir en Punta Cana y White Sands

CONOCIMIENTO ESPECIAL:
- Tienes acceso a información sobre Salado Golf & Beach Resort en Punta Cana
- Salado Golf & Beach es una urbanización dentro del complejo White Sands en Punta Cana
- Salado Golf & Beach Resort fue desarrollado por Arena Gorda, constructora con más de 30 años de experiencia y 250+ proyectos completados en República Dominicana
- El resort cuenta con un campo de golf de 9 hoyos
- SOLO tiene APARTAMENTOS, NO villas ni casas. 5 tipologías:
  * Tipo A: 103.75 m² total, 2 habitaciones, 2 baños (el más grande)
  * Tipo B: 59-62 m² total, 1 habitación, 1 baño (compacto, ideal inversión)
  * Tipo C: 69-71 m² total, 1 habitación, 1 baño (más amplio de 1 hab)
  * Tipo D: 62.68 m² total, 1 habitación, 1 baño
  * Tipo E: 99.63 m² total, 2 habitaciones, 2 baños
- 3 bloques residenciales (A, B, C), cada uno con 3 niveles + azotea con jacuzzi
- Todos los apartamentos incluyen: sala, comedor, cocina, terraza, área de lavado
- Puedes proporcionar detalles sobre desarrollos, ubicaciones, amenidades
- Tienes acceso a imágenes profesionales de Salado Resort
- Ubicación exacta en Google Maps: https://maps.app.goo.gl/RTNXTnHwnH29ju1L9
n- UNIVERSO SALADO: 3 fases integradas en parcelas contiguas dentro de White Sands
  * Salado 1: Primera fase, EN VENTA. 3 bloques, 5 tipologias, 15 apartamentos disponibles, desde EUR165,000
  * Salado 2: Segunda fase, EN FASE DE DISENO. Parcela contigua a Salado 1
  * Salado 3: Tercera fase, EN DESARROLLO. Parcela contigua a Salado 2
- Las 3 fases comparten amenidades y se integran como un unico complejo residencial de lujo
- Cuando pregunten por Salado sin especificar fase, asume Salado 1 (el que esta en venta actualmente)
- Si pregunten por Salado 2, indica que esta en fase de diseno y que pronto habra mas informacion
- Si preguntan por Salado 3, consulta el RAG que tiene informacion disponible
- Cuando pregunten por ubicación, dirección o cómo llegar, comparte este link de Google Maps

AMENIDADES DE WHITE SANDS (complejo):
- Deportivas: piscina semi-olímpica, 2 pistas de tenis, 4 pistas de pádel, campo de prácticas de golf 24/7, pista de running, gimnasio
- Servicios: helipuerto, minimarket, cafetería, club social, coworking
- Recreativas: parque infantil, parque de mascotas

MANEJO DE IMÁGENES:
- Cuando el usuario pida fotos, las imágenes se enviarán AUTOMÁTICAMENTE
- NO menciones URLs ni links
- Confirma brevemente que envías las fotos
- Usa emojis: 📸 🏖️ 🏌️ 🏠 ⛳

ESTILO:
- Mensajes concisos pero completos
- Emojis ocasionales para calidez
- Formato claro y fácil de leer

NO HAGAS:
- NUNCA digas que eres un bot o IA
- NUNCA inventes precios sin base
- NUNCA pidas contraseñas
- NUNCA olvides el contexto
- NUNCA menciones villas, casas o townhouses. Salado Golf & Beach SOLO tiene APARTAMENTOS (5 tipologías: A, B, C, D, E). NO existen villas ni casas en este proyecto.`;

  if (isFirstInteraction) {
    return basePrompt + `\n\nPRIMERA INTERACCIÓN:
Preséntate con entusiasmo: "¡Hola! Soy Natalia, tu asesora inmobiliaria de Salado Golf & Beach Resort. Estoy aquí para ayudarte con toda la información que necesites sobre este paraíso en Punta Cana. ¿Qué te gustaría saber? 🏖️⛳"`;
  } else {
    return basePrompt + `\n\nCONVERSACIÓN EN CURSO:
Ya te presentaste. NO vuelvas a saludar.
Continúa naturalmente, manteniendo el contexto.`;
  }
}

// Iniciar servidor

// ============= WEBHOOK DIRECTO WHATSAPP (Twilio) =============
// Endpoint preparado para recibir mensajes directos de Twilio
// Configurar en Twilio Console: POST https://natalia.soporteclientes.net/webhook/whatsapp
app.post("/webhook/whatsapp", async (req, res) => {
  try {
    const { Body, From, To, MessageSid } = req.body;
    if (!Body || !From) return res.status(400).send("<Response></Response>");
    const phone = From.replace("whatsapp:", "");
    console.log("[Webhook Direct] " + phone + ": " + Body.substring(0, 50));
    // Reutilizar la lógica del /api/chat
    const axios = require("axios");
    const chatResp = await axios.post("http://localhost:" + PORT + "/api/chat", {
      messages: [{ role: "user", content: Body }],
      user_phone: phone,
      max_tokens: 500
    }, { timeout: 40000 });
    const reply = chatResp.data.choices?.[0]?.message?.content || "Error";
    // Responder en TwiML
    let twiml = "<Response><Message>" + reply.replace(/&/g,"&amp;").replace(/</g,"&lt;") + "</Message></Response>";
    res.type("text/xml").send(twiml);
  } catch (err) {
    console.error("[Webhook Direct] Error:", err.message);
    res.type("text/xml").send("<Response><Message>Error. Intenta de nuevo.</Message></Response>");
  }
});
// ============= FIN WEBHOOK DIRECTO =============

app.listen(PORT, '0.0.0.0', () => {
  console.log(`[Natalia WhatsApp Bridge] Running on port ${PORT}`);
  console.log(`[Natalia WhatsApp Bridge] RAG Service: ${RAG_SERVICE}`);
  console.log(`[Natalia WhatsApp Bridge] Image Sending: ENABLED`);
  console.log(`[Natalia WhatsApp Bridge] Context Management: ENHANCED ✨`);
  console.log(`[Natalia WhatsApp Bridge] Follow-up detection: ENABLED`);
});
