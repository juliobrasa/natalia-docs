# 🔄 Migración de Webhook WhatsApp a code.juliobrasa.com

**Fecha:** 3 de febrero de 2026
**Objetivo:** Mover el webhook de WhatsApp desde panel.redservicio.net (externo) a code.juliobrasa.com (infraestructura propia)

---

## 📋 Credenciales de WhatsApp Business API

```bash
WHATSAPP_VERIFY_TOKEN=soltia_redservicio_webhook_2026
WHATSAPP_ACCESS_TOKEN=EAAegimfjNj0BQgpuWrRpGNt4rjlzqB7hRmCFlueJENds8BniSYqHFYCpdRHG7ARda7W9jdyC0s2ZB8yDmEJZAZB7RdpQnA6RJbPmjjzJHZCGAY9hZBQMBpW6JoKwS8z1Vf3YEpGtWJ8n4ZAIYe6YcsWOF4vmA26gL4fzu4F833DK9832HvDAZA7RNw2nmA7j1lojgZDZD
WHATSAPP_PHONE_NUMBER_ID=1031061770070997
WHATSAPP_BUSINESS_ACCOUNT_ID=1664702307862246
```

**Número WhatsApp:** +34 685 80 59 24
**Estado:** VERIFIED

---

## 🎯 Estado Actual vs Objetivo

### ❌ Estado Actual (PROBLEMA RESUELTO EN PANEL.REDSERVICIO.NET)

```
┌─────────────────────────────────────────────┐
│     Meta WhatsApp Business API              │
└───────────────┬─────────────────────────────┘
                │ webhook
                ▼
┌─────────────────────────────────────────────┐
│  panel.redservicio.net (184.174.36.104)     │
│  Servidor externo (nodo0)                   │
│                                              │
│  ✅ FIX APLICADO:                            │
│  - AIAssistantService envía user_phone      │
│  - Sesiones funcionan correctamente         │
└───────────────┬─────────────────────────────┘
                │ HTTP POST con user_phone
                ▼
┌─────────────────────────────────────────────┐
│  Natalia (194.41.119.117:18790)             │
│  ✅ Sesiones OK desde el fix                │
└─────────────────────────────────────────────┘
```

### ✅ Estado Objetivo (TODO EN INFRAESTRUCTURA PROPIA)

```
┌─────────────────────────────────────────────┐
│     Meta WhatsApp Business API              │
└───────────────┬─────────────────────────────┘
                │ webhook
                ▼
┌─────────────────────────────────────────────┐
│  code.juliobrasa.com (194.41.119.101)       │
│  Webhook Node.js simple                      │
│                                              │
│  - Recibe mensajes de WhatsApp              │
│  - Extrae número de teléfono                │
│  - Llama a Natalia con user_phone           │
│  - Devuelve respuesta a WhatsApp            │
└───────────────┬─────────────────────────────┘
                │ HTTP POST con user_phone
                ▼
┌─────────────────────────────────────────────┐
│  Natalia (194.41.119.117:18790)             │
│  ✅ Sesiones funcionan perfectamente         │
└─────────────────────────────────────────────┘
```

---

## 📝 Implementación del Webhook en code.juliobrasa.com

### 1. Crear directorio y archivo del webhook

```bash
ssh root@194.41.119.101

mkdir -p /opt/whatsapp-webhook
cd /opt/whatsapp-webhook
```

### 2. Crear el archivo `webhook.js`

```javascript
const express = require('express');
const axios = require('axios');

const app = express();
app.use(express.json());

const PORT = 3002;
const NATALIA_URL = 'http://194.41.119.117:18790/api/chat';
const WHATSAPP_TOKEN = 'EAAegimfjNj0BQgpuWrRpGNt4rjlzqB7hRmCFlueJENds8BniSYqHFYCpdRHG7ARda7W9jdyC0s2ZB8yDmEJZAZB7RdpQnA6RJbPmjjzJHZCGAY9hZBQMBpW6JoKwS8z1Vf3YEpGtWJ8n4ZAIYe6YcsWOF4vmA26gL4fzu4F833DK9832HvDAZA7RNw2nmA7j1lojgZDZD';
const PHONE_NUMBER_ID = '1031061770070997';
const VERIFY_TOKEN = 'soltia_redservicio_webhook_2026';

// Verificación del webhook (GET) - Meta envía esto al configurar
app.get('/webhook/whatsapp', (req, res) => {
  const mode = req.query['hub.mode'];
  const token = req.query['hub.verify_token'];
  const challenge = req.query['hub.challenge'];

  console.log('[WhatsApp Webhook] Verificación recibida');

  if (mode === 'subscribe' && token === VERIFY_TOKEN) {
    console.log('[WhatsApp Webhook] ✅ Verificación exitosa');
    res.status(200).send(challenge);
  } else {
    console.log('[WhatsApp Webhook] ❌ Verificación fallida');
    res.sendStatus(403);
  }
});

// Recibir mensajes de WhatsApp (POST)
app.post('/webhook/whatsapp', async (req, res) => {
  try {
    console.log('[WhatsApp Webhook] POST recibido');

    const entry = req.body.entry || [];

    // Responder inmediatamente a WhatsApp (200 OK)
    res.sendStatus(200);

    // Procesar cada entrada
    for (const item of entry) {
      const changes = item.changes || [];

      for (const change of changes) {
        if (change.field !== 'messages') continue;

        const value = change.value;
        const messages = value.messages || [];

        // Procesar mensajes entrantes
        for (const message of messages) {
          const from = message.from; // Número de teléfono
          const messageId = message.id;
          const type = message.type;

          // Extraer texto del mensaje
          let text = '';
          if (type === 'text') {
            text = message.text.body;
          } else if (type === 'image') {
            text = message.image.caption || '[Imagen]';
          } else if (type === 'document') {
            text = message.document.filename || '[Documento]';
          } else if (type === 'audio') {
            text = '[Audio]';
          } else if (type === 'video') {
            text = message.video.caption || '[Video]';
          } else {
            text = `[${type}]`;
          }

          console.log(`[WhatsApp] Mensaje de ${from}: "${text}"`);

          // Llamar a Natalia con user_phone
          try {
            const nataliaResponse = await axios.post(NATALIA_URL, {
              messages: [
                { role: 'user', content: text }
              ],
              max_tokens: 500,
              user_phone: from  // ✅ ENVIAR user_phone
            }, {
              timeout: 35000,
              headers: {
                'Content-Type': 'application/json'
              }
            });

            const responseData = nataliaResponse.data;
            const responseText = responseData.choices?.[0]?.message?.content || 'Error al generar respuesta';
            const mediaUrls = responseData.choices?.[0]?.message?.mediaUrls || [];

            console.log(`[Natalia] Respuesta: "${responseText.substring(0, 50)}..."`);

            // Enviar respuesta a WhatsApp
            await axios.post(
              `https://graph.facebook.com/v18.0/${PHONE_NUMBER_ID}/messages`,
              {
                messaging_product: 'whatsapp',
                to: from,
                type: 'text',
                text: { body: responseText }
              },
              {
                headers: {
                  'Authorization': `Bearer ${WHATSAPP_TOKEN}`,
                  'Content-Type': 'application/json'
                }
              }
            );

            console.log(`[WhatsApp] ✅ Respuesta enviada a ${from}`);

            // Enviar imágenes si hay
            for (const imageUrl of mediaUrls) {
              try {
                await axios.post(
                  `https://graph.facebook.com/v18.0/${PHONE_NUMBER_ID}/messages`,
                  {
                    messaging_product: 'whatsapp',
                    to: from,
                    type: 'image',
                    image: { link: imageUrl }
                  },
                  {
                    headers: {
                      'Authorization': `Bearer ${WHATSAPP_TOKEN}`,
                      'Content-Type': 'application/json'
                    }
                  }
                );
                console.log(`[WhatsApp] 🖼️  Imagen enviada: ${imageUrl}`);
              } catch (imgErr) {
                console.error(`[WhatsApp] ❌ Error enviando imagen:`, imgErr.message);
              }
            }

            // Marcar mensaje como leído
            try {
              await axios.post(
                `https://graph.facebook.com/v18.0/${PHONE_NUMBER_ID}/messages`,
                {
                  messaging_product: 'whatsapp',
                  status: 'read',
                  message_id: messageId
                },
                {
                  headers: {
                    'Authorization': `Bearer ${WHATSAPP_TOKEN}`,
                    'Content-Type': 'application/json'
                  }
                }
              );
            } catch (readErr) {
              // No crítico si falla
            }

          } catch (nataliaErr) {
            console.error('[Natalia] ❌ Error:', nataliaErr.message);

            // Enviar mensaje de error al usuario
            try {
              await axios.post(
                `https://graph.facebook.com/v18.0/${PHONE_NUMBER_ID}/messages`,
                {
                  messaging_product: 'whatsapp',
                  to: from,
                  type: 'text',
                  text: {
                    body: 'Lo siento, hubo un error al procesar tu mensaje. Por favor intenta de nuevo más tarde.'
                  }
                },
                {
                  headers: {
                    'Authorization': `Bearer ${WHATSAPP_TOKEN}`,
                    'Content-Type': 'application/json'
                  }
                }
              );
            } catch (errSendErr) {
              console.error('[WhatsApp] ❌ Error enviando mensaje de error:', errSendErr.message);
            }
          }
        }
      }
    }

  } catch (error) {
    console.error('[WhatsApp Webhook] ❌ Error general:', error.message);
  }
});

// Health check
app.get('/health', (req, res) => {
  res.json({
    ok: true,
    service: 'whatsapp-webhook',
    natalia: NATALIA_URL,
    phone: '+34 685 80 59 24'
  });
});

app.listen(PORT, () => {
  console.log(`✓ WhatsApp Webhook escuchando en puerto ${PORT}`);
  console.log(`✓ Natalia endpoint: ${NATALIA_URL}`);
  console.log(`✓ WhatsApp Phone: +34 685 80 59 24`);
});
```

### 3. Crear `package.json`

```bash
cat > /opt/whatsapp-webhook/package.json << 'EOF'
{
  "name": "whatsapp-webhook",
  "version": "1.0.0",
  "description": "WhatsApp Business API webhook for Natalia",
  "main": "webhook.js",
  "scripts": {
    "start": "node webhook.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "axios": "^1.6.2"
  }
}
EOF
```

### 4. Instalar dependencias

```bash
cd /opt/whatsapp-webhook
npm install
```

### 5. Crear servicio systemd

```bash
cat > /etc/systemd/system/whatsapp-webhook.service << 'EOF'
[Unit]
Description=WhatsApp Webhook for Natalia
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/opt/whatsapp-webhook
ExecStart=/usr/bin/node /opt/whatsapp-webhook/webhook.js
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
SyslogIdentifier=whatsapp-webhook

[Install]
WantedBy=multi-user.target
EOF
```

### 6. Activar y arrancar el servicio

```bash
systemctl daemon-reload
systemctl enable whatsapp-webhook
systemctl start whatsapp-webhook
systemctl status whatsapp-webhook
```

### 7. Ver logs

```bash
journalctl -u whatsapp-webhook -f
```

---

## 🌐 Configurar Nginx (Proxy Reverso)

### Crear configuración de Nginx

```bash
cat > /etc/nginx/sites-available/whatsapp-webhook << 'EOF'
server {
    listen 80;
    server_name webhook.soporteclientes.net;

    location /webhook/whatsapp {
        proxy_pass http://localhost:3002/webhook/whatsapp;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
    }

    location /health {
        proxy_pass http://localhost:3002/health;
        proxy_http_version 1.1;
    }
}
EOF
```

### Activar configuración

```bash
ln -s /etc/nginx/sites-available/whatsapp-webhook /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### Obtener certificado SSL

```bash
certbot certonly --nginx -d webhook.soporteclientes.net
```

### Actualizar Nginx para HTTPS

```bash
cat > /etc/nginx/sites-available/whatsapp-webhook << 'EOF'
server {
    listen 80;
    server_name webhook.soporteclientes.net;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name webhook.soporteclientes.net;

    ssl_certificate /etc/letsencrypt/live/webhook.soporteclientes.net/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/webhook.soporteclientes.net/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    location /webhook/whatsapp {
        proxy_pass http://localhost:3002/webhook/whatsapp;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
    }

    location /health {
        proxy_pass http://localhost:3002/health;
        proxy_http_version 1.1;
    }
}
EOF

nginx -t
systemctl reload nginx
```

---

## 🔧 Configurar DNS

Agregar registro A en el DNS:

```
webhook.soporteclientes.net  →  194.41.119.101
```

---

## 📱 Actualizar Webhook en Meta Business Manager

### 1. Acceder a Meta Business Manager

https://business.facebook.com/

### 2. Navegar a WhatsApp → Configuración → Configuración de API

### 3. Actualizar URL del Webhook

**Webhook URL:** `https://webhook.soporteclientes.net/webhook/whatsapp`
**Verify Token:** `soltia_redservicio_webhook_2026`

### 4. Suscribirse a eventos

Asegurarse de que esté suscrito a:
- ✅ messages
- ✅ message_status (opcional)

### 5. Guardar cambios

Meta enviará una petición GET para verificar. Debería ver en los logs:

```
[WhatsApp Webhook] Verificación recibida
[WhatsApp Webhook] ✅ Verificación exitosa
```

---

## 🧪 Pruebas

### Test 1: Health Check

```bash
curl https://webhook.soporteclientes.net/health
```

**Esperado:**
```json
{
  "ok": true,
  "service": "whatsapp-webhook",
  "natalia": "http://194.41.119.117:18790/api/chat",
  "phone": "+34 685 80 59 24"
}
```

### Test 2: Verificación del Webhook

```bash
curl "https://webhook.soporteclientes.net/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=soltia_redservicio_webhook_2026&hub.challenge=TEST123"
```

**Esperado:**
```
TEST123
```

### Test 3: Enviar mensaje de WhatsApp

1. Enviar mensaje al **+34 685 80 59 24**
2. Ver logs: `journalctl -u whatsapp-webhook -f`
3. Verificar en Natalia: `ssh root@194.41.119.117 "journalctl -u natalia-whatsapp -f"`

**Logs esperados en whatsapp-webhook:**
```
[WhatsApp] Mensaje de 34698189848: "Hola"
[Natalia] Respuesta: "¡Hola! 👋 Soy Natalia de Soltia..."
[WhatsApp] ✅ Respuesta enviada a 34698189848
```

**Logs esperados en Natalia:**
```
[Natalia WhatsApp] Request body keys: [ 'messages', 'max_tokens', 'user_phone' ]
[Natalia WhatsApp] 📱 Phone: 34698189848
[Session] 🆕 Nueva sesión: 34698189848
```

### Test 4: Segundo mensaje (verificar sesión)

Enviar segundo mensaje. Debería ver:

```
[Session] 🔄 Recuperado: 3 mensajes
[Natalia WhatsApp] 📊 Longitud: 3
[Natalia WhatsApp] 👋 Primera interacción: false
```

✅ NO debe saludar de nuevo

---

## 🗑️ Eliminar Webhook Antiguo (OPCIONAL)

Una vez confirmado que el nuevo webhook funciona:

### En Meta Business Manager

Simplemente cambiar la URL del webhook (ya hecho en paso anterior).

### En panel.redservicio.net (184.174.36.104)

```bash
ssh root@184.174.36.104

# Opcional: Desactivar AI para WhatsApp en .env
sed -i 's/WHATSAPP_AI_ENABLED=true/WHATSAPP_AI_ENABLED=false/' \
  /home/panel.redservicio.net/public_html/.env

# Reiniciar PHP-FPM
systemctl restart php82-php-fpm
```

O simplemente dejar el sistema como está (no hará daño).

---

## 📊 Comparación: Antes vs Después

| Aspecto | Antes (panel.redservicio.net) | Después (code.juliobrasa.com) |
|---------|-------------------------------|-------------------------------|
| Servidor | 184.174.36.104 (externo) | 194.41.119.101 (propio) |
| Tecnología | Laravel/PHP | Node.js/Express |
| Complejidad | Alta (framework completo) | Baja (webhook simple) |
| Dependencias | Base de datos, cache, etc. | Solo axios + express |
| Mantenimiento | Complejo | Simple |
| user_phone | ✅ (después del fix) | ✅ (nativo) |
| Control | Limitado | Total |
| Latencia | Mayor (servidor externo) | Menor (red local) |

---

## 🔐 Seguridad

### Variables de Entorno (Recomendado)

En lugar de hardcodear el token en `webhook.js`, usar variables de entorno:

```bash
# Crear archivo .env
cat > /opt/whatsapp-webhook/.env << 'EOF'
WHATSAPP_TOKEN=EAAegimfjNj0BQgpuWrRpGNt4rjlzqB7hRmCFlueJENds8BniSYqHFYCpdRHG7ARda7W9jdyC0s2ZB8yDmEJZAZB7RdpQnA6RJbPmjjzJHZCGAY9hZBQMBpW6JoKwS8z1Vf3YEpGtWJ8n4ZAIYe6YcsWOF4vmA26gL4fzu4F833DK9832HvDAZA7RNw2nmA7j1lojgZDZD
WHATSAPP_PHONE_NUMBER_ID=1031061770070997
WHATSAPP_VERIFY_TOKEN=soltia_redservicio_webhook_2026
NATALIA_URL=http://194.41.119.117:18790/api/chat
EOF

chmod 600 /opt/whatsapp-webhook/.env
```

```javascript
// En webhook.js, agregar al inicio:
require('dotenv').config();

const WHATSAPP_TOKEN = process.env.WHATSAPP_TOKEN;
const PHONE_NUMBER_ID = process.env.WHATSAPP_PHONE_NUMBER_ID;
const VERIFY_TOKEN = process.env.WHATSAPP_VERIFY_TOKEN;
const NATALIA_URL = process.env.NATALIA_URL;
```

```bash
# Instalar dotenv
cd /opt/whatsapp-webhook
npm install dotenv
systemctl restart whatsapp-webhook
```

---

## 📝 Archivos de Backup

Backups creados en panel.redservicio.net (184.174.36.104):

```
/home/panel.redservicio.net/public_html/app/Services/AIAssistantService.php.backup-20260203-*
/home/panel.redservicio.net/public_html/app/Http/Controllers/WhatsAppWebhookController.php.backup-20260203-*
```

---

## ✅ Checklist de Migración

- [ ] Crear directorio `/opt/whatsapp-webhook` en code.juliobrasa.com
- [ ] Crear archivo `webhook.js`
- [ ] Crear `package.json`
- [ ] Ejecutar `npm install`
- [ ] Crear servicio systemd `whatsapp-webhook.service`
- [ ] Iniciar servicio: `systemctl start whatsapp-webhook`
- [ ] Verificar logs: `journalctl -u whatsapp-webhook -f`
- [ ] Configurar DNS: `webhook.soporteclientes.net → 194.41.119.101`
- [ ] Configurar Nginx con proxy reverso
- [ ] Obtener certificado SSL con certbot
- [ ] Probar health check: `curl https://webhook.soporteclientes.net/health`
- [ ] Actualizar webhook en Meta Business Manager
- [ ] Enviar mensaje de prueba por WhatsApp
- [ ] Verificar que sesiones funcionan (segundo mensaje)
- [ ] Confirmar que NO resetea la conversación
- [ ] (Opcional) Desactivar webhook antiguo en panel.redservicio.net

---

**Documentado por:** Claude Sonnet 4.5
**Fecha:** 3 de febrero de 2026 15:30 UTC
**Estado:** ✅ LISTO PARA IMPLEMENTAR
