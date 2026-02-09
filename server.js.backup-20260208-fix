const express = require('express');
const axios = require('axios');
const { exec } = require('child_process');
const fs = require('fs');
const util = require('util');
const execPromise = util.promisify(exec);

const app = express();
app.use(express.json());

// Configuración
const MOLTBOT_GATEWAY = 'http://localhost:3100';
const MOLTBOT_TOKEN = 'natalia-coordinator-token-2026';
const PORT = process.env.PORT || 18790;
const RAG_SERVICE = 'http://localhost:9000';
const DEEPSEEK_API = 'https://api.deepseek.com/chat/completions';
const DEEPSEEK_KEY = 'sk-d47fe7b31106439baaf4fa35fe18b4f2';
const SESSIONS_FILE = '/var/lib/natalia-whatsapp/sessions.json';
// ==================== SISTEMA DE SESIONES ====================
const conversationSessions = new Map();
const SESSION_TIMEOUT = 365 * 24 * 60 * 60 * 1000; // 1 año


// ==================== PERSISTENCIA DE SESIONES ====================

// Guardar sesiones a disco
function saveSessions() {
  try {
    const sessionsArray = Array.from(conversationSessions.entries());
    fs.writeFileSync(SESSIONS_FILE, JSON.stringify(sessionsArray, null, 2));
    console.log(`[Session] 💾 Guardadas ${sessionsArray.length} sesiones a disco`);
  } catch (error) {
    console.error('[Session] ❌ Error al guardar sesiones:', error.message);
  }
}

// Cargar sesiones desde disco
function loadSessions() {
  try {
    if (fs.existsSync(SESSIONS_FILE)) {
      const data = fs.readFileSync(SESSIONS_FILE, 'utf8');
      const sessionsArray = JSON.parse(data);
      
      for (const [phone, session] of sessionsArray) {
        conversationSessions.set(phone, session);
      }
      
      console.log(`[Session] 📂 Cargadas ${sessionsArray.length} sesiones desde disco`);
      
      // Mostrar resumen
      for (const [phone, session] of conversationSessions.entries()) {
        console.log(`[Session]    📱 ${phone}: ${session.messages.length} mensajes`);
      }
    } else {
      console.log('[Session] ℹ️  No hay sesiones previas guardadas');
    }
  } catch (error) {
    console.error('[Session] ❌ Error al cargar sesiones:', error.message);
  }
}

// ==================================================================

function getSession(phoneNumber) {
  if (!phoneNumber) {
    return { messages: [], lastActivity: Date.now() };
  }

  if (!conversationSessions.has(phoneNumber)) {
    conversationSessions.set(phoneNumber, {
      messages: [],
      lastActivity: Date.now(),
      firstInteraction: Date.now()
    });
    console.log(`[Session] 🆕 Nueva sesión: ${phoneNumber}`);
  }

  const session = conversationSessions.get(phoneNumber);
  session.lastActivity = Date.now();
  return session;
}

function addMessageToSession(phoneNumber, role, content) {
  if (!phoneNumber) return [];

  const session = getSession(phoneNumber);
  session.messages.push({ role, content, timestamp: Date.now() });

  if (session.messages.length > 250) {
    session.messages = session.messages.slice(-250);
  }

  conversationSessions.set(phoneNumber, session);
  console.log(`[Session] 💾 ${phoneNumber}: ${session.messages.length} mensajes`);
  return session.messages;
}

setInterval(() => {
  const now = Date.now();
  let cleaned = 0;
  for (const [phone, session] of conversationSessions.entries()) {
    if (now - session.lastActivity > SESSION_TIMEOUT) {
      conversationSessions.delete(phone);
      cleaned++;
    }
  }
  if (cleaned > 0) console.log(`[Session] 🧹 Limpiadas ${cleaned} sesiones`);
}, 60 * 60 * 1000);

// ============================================================
console.log('[Session Storage] ✅ Sistema inicializado (timeout 1 año)');

// Cargar sesiones al iniciar
loadSessions();

// Guardar sesiones automáticamente cada 5 minutos
setInterval(() => {
  saveSessions();
}, 5 * 60 * 1000);

// Guardar sesiones al cerrar el proceso
process.on('SIGTERM', () => {
  console.log('[Session] 💾 Guardando sesiones antes de cerrar...');
  saveSessions();
  process.exit(0);
});

process.on('SIGINT', () => {
  console.log('[Session] 💾 Guardando sesiones antes de cerrar...');
  saveSessions();
  process.exit(0);
});

console.log('[Session Storage] 💾 Persistencia a disco: ACTIVADA');
console.log('[Session Storage] 📂 Archivo: ' + SESSIONS_FILE);
console.log('[Session Storage] ⏰ Auto-guardado: cada 5 minutos');


// Almacenar el número de teléfono del usuario actual
let currentUserPhone = null;

// Endpoint de salud
app.get('/health', (req, res) => {
  res.json({ status: 'healthy', agent: 'natalia-whatsapp-bridge', rag_enabled: true });
});

// Función para detectar contexto inmobiliario en la conversación
function detectRealEstateContext(messages) {
  // Keywords que indican que estamos en contexto inmobiliario
  const realEstateKeywords = ['salado', 'resort', 'apartamento', 'punta cana', 'golf', 
    'playa', 'inmobiliaria', 'propiedad', 'desarrollo', 'inversión'];
  
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
          conversationSessions.set(phoneNumber, session);
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
      'inmobiliaria', 'propiedad', 'desarrollo', 'inversión'];
    
    // Keywords de seguimiento (indican preguntas de follow-up)
    const followUpKeywords = ['barato', 'económico', 'precio', 'costo', 'cuál', 'cuánto', 
      'más', 'mejor', 'disponible', 'tiene', 'hay', 'opciones', 'unidades', 'habitaciones',
      'metros', 'm²', 'tamaño', 'superficie', 'pago', 'financiamiento', 'entrega', 'amenidades', 'servicios', 'facilidades', 'ubicación', 'ubicacion'];
    
    // Keywords de imágenes
    const imageKeywords = ['foto', 'imagen', 'picture', 'exterior', 'interior', 'muestra', 'ver', 'envia'];
    
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
          top_k: 5
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

    // Agregar imágenes de amenidades si se solicitan específicamente
    const amenidadesKeywords = /amenidad|piscina|pool|fachada|facade|instalaciones|facilities/i;
    if (amenidadesKeywords.test(userMessage) && asksForPhotos) {
      const amenidadesUrls = [
        'http://194.41.119.21:9001/salado-amenidad-1.jpg', // Piscina III
        'http://194.41.119.21:9001/salado-amenidad-3.jpg', // Piscina Bávaro y Salado
        'http://194.41.119.21:9001/salado-amenidad-4.jpg', // Piscina II
        'http://194.41.119.21:9001/salado-piscina-7.jpg',  // Piscina con camastros
        'http://194.41.119.21:9001/salado-piscina-8.jpg',  // Piscina moderna render
        'http://194.41.119.21:9001/salado-amenidad-5.jpg', // Piscina y fachada
        'http://194.41.119.21:9001/salado-amenidad-6.jpg', // Piscina principal
        'http://194.41.119.21:9001/salado-amenidad-2.jpg'  // Fachada calle Punta Cana
      ];

      // Priorizar amenidades sobre exteriores
      imageUrls = amenidadesUrls.concat(imageUrls.filter(url => !url.includes('amenidad') && !url.includes('piscina')));
      console.log('[Natalia WhatsApp] Imágenes de amenidades agregadas');
    }

    // Agregar imágenes de PLAYA si se solicitan específicamente
    const playaKeywords = /playa|beach|mar|sea|arena|sand|costa|shore/i;
    if (playaKeywords.test(userMessage) && asksForPhotos) {
      const playaUrls = [
        'http://194.41.119.21:9001/salado-playa-1.jpg', // Pier con kayak
        'http://194.41.119.21:9001/salado-playa-2.jpg', // Vista aérea playa resort
        'http://194.41.119.21:9001/salado-playa-4.jpg', // Palmera con camastros
        'http://194.41.119.21:9001/salado-playa-5.jpg', // Playa con gente
        'http://194.41.119.21:9001/salado-playa-6.jpg', // Playa palmeras
        'http://194.41.119.21:9001/salado-playa-3.jpg'  // Persona saltando
      ];
      imageUrls = playaUrls.concat(imageUrls.filter(url => !url.includes('playa')));
      console.log('[Natalia WhatsApp] Imágenes de playa agregadas');
    }

    // Agregar imágenes de UBICACIÓN si se solicitan específicamente
    const ubicacionKeywords = /ubicacion|location|mapa|map|donde|where|aerial|aereo/i;
    if (ubicacionKeywords.test(userMessage) && asksForPhotos) {
      const ubicacionUrls = [
        'http://194.41.119.21:9001/salado-ubicacion-1.jpg', // Mapa aéreo cercano
        'http://194.41.119.21:9001/salado-ubicacion-2.jpg'  // Mapa aéreo amplio
      ];
      imageUrls = ubicacionUrls.concat(imageUrls.filter(url => !url.includes('ubicacion')));
      console.log('[Natalia WhatsApp] Imágenes de ubicación agregadas');
    }

    // Agregar imágenes de GOLF si se solicitan específicamente
    const golfKeywords = /golf|campo|course|green|hoyo|hole/i;
    if (golfKeywords.test(userMessage) && asksForPhotos) {
      const golfUrls = [
        'http://194.41.119.21:9001/salado-golf-1.jpg'  // Campo de golf
      ];
      imageUrls = golfUrls.concat(imageUrls.filter(url => !url.includes('golf')));
      console.log('[Natalia WhatsApp] Imágenes de golf agregadas');
    }

    // Agregar imágenes de EDIFICIO/APARTAMENTO si se solicitan específicamente
    const edificioKeywords = /edificio|building|apartamento|apartment|unidad|unit/i;
    if (edificioKeywords.test(userMessage) && asksForPhotos && !amenidadesKeywords.test(userMessage)) {
      const edificioUrls = [
        'http://194.41.119.21:9001/salado-edificio-1.jpg'  // Fachada moderna
      ];
      imageUrls = edificioUrls.concat(imageUrls.filter(url => !url.includes('edificio')));
      console.log('[Natalia WhatsApp] Imágenes de edificio agregadas');
    }
    }


    // Si pide fotos en contexto inmobiliario pero no especificó categoría, enviar fotos por defecto
    if (asksForPhotos && imageUrls.length === 0) {
      const defaultUrls = [
        'http://194.41.119.21:9001/salado-amenidades-1.jpg',  // Piscina principal
        'http://194.41.119.21:9001/salado-playa-1.jpg',       // Acceso a playa
        'http://194.41.119.21:9001/salado-golf-1.jpg'         // Campo de golf
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
        'http://194.41.119.21:9001/salado-piscina-8.jpg',
        'http://194.41.119.21:9001/salado-amenidad-1.jpg',
        'http://194.41.119.21:9001/salado-amenidad-6.jpg',
        'http://194.41.119.21:9001/salado-amenidad-3.jpg',
        'http://194.41.119.21:9001/salado-amenidad-4.jpg',
        'http://194.41.119.21:9001/salado-amenidad-5.jpg',
        'http://194.41.119.21:9001/salado-piscina-7.jpg'
      ],
      fachada: [
        'http://194.41.119.21:9001/salado-amenidad-2.jpg',
        'http://194.41.119.21:9001/salado-edificio-1.jpg'
      ],
      playa: [
        'http://194.41.119.21:9001/salado-playa-2.jpg',
        'http://194.41.119.21:9001/salado-playa-1.jpg',
        'http://194.41.119.21:9001/salado-playa-4.jpg',
        'http://194.41.119.21:9001/salado-playa-3.jpg',
        'http://194.41.119.21:9001/salado-playa-5.jpg',
        'http://194.41.119.21:9001/salado-playa-6.jpg'
      ],
      golf: [
        'http://194.41.119.21:9001/salado-golf-1.jpg'
      ],
      ubicacion: [
        'http://194.41.119.21:9001/salado-ubicacion-2.jpg',
        'http://194.41.119.21:9001/salado-ubicacion-1.jpg'
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
            'http://194.41.119.21:9001/salado-amenidad-1.jpg',
            'http://194.41.119.21:9001/salado-amenidad-5.jpg',
            'http://194.41.119.21:9001/salado-amenidad-6.jpg'
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
          'http://194.41.119.21:9001/salado-piscina-8.jpg',
          'http://194.41.119.21:9001/salado-playa-2.jpg',
          'http://194.41.119.21:9001/salado-golf-1.jpg'
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
        ...messages
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
    const imagesToSend = (imageUrls.length > 0 && asksForPhotos) ? imageUrls.slice(0, 3) : [];

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
- Agente exclusiva de atención al cliente de Salado Golf & Beach Resort
- Experta en bienes raíces de lujo en el Caribe, específicamente Punta Cana
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
- Puedes proporcionar detalles sobre desarrollos, ubicaciones, amenidades
- Tienes acceso a imágenes profesionales de Salado Resort
- Ubicación exacta en Google Maps: https://maps.app.goo.gl/RTNXTnHwnH29ju1L9
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
- NUNCA olvides el contexto`;

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
app.listen(PORT, '0.0.0.0', () => {
  console.log(`[Natalia WhatsApp Bridge] Running on port ${PORT}`);
  console.log(`[Natalia WhatsApp Bridge] RAG Service: ${RAG_SERVICE}`);
  console.log(`[Natalia WhatsApp Bridge] Image Sending: ENABLED`);
  console.log(`[Natalia WhatsApp Bridge] Context Management: ENHANCED ✨`);
  console.log(`[Natalia WhatsApp Bridge] Follow-up detection: ENABLED`);
});
