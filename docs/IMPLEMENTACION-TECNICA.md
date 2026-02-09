# Implementación Técnica - Sistema de Imágenes Natalia

## Tabla de Contenidos

1. [Arquitectura General](#arquitectura-general)
2. [Componente WhatsApp](#componente-whatsapp)
3. [Componente Telegram](#componente-telegram)
4. [Natalia Bridge](#natalia-bridge)
5. [Servicio RAG](#servicio-rag)
6. [Formato de Respuesta](#formato-de-respuesta)
7. [Flujo de Datos](#flujo-de-datos)

---

## Arquitectura General

```
┌─────────────────────────────────────────────────────────────┐
│                         USUARIOS                            │
│  WhatsApp (+34 685...)  │  Telegram (@Natalia_jefa_bot)    │
└────────────┬────────────┴──────────────┬─────────────────────┘
             │                           │
             ▼                           ▼
┌────────────────────────┐  ┌───────────────────────────────┐
│   WhatsApp Webhook     │  │   Telegram Bot (Node.js)      │
│   Laravel/PHP          │  │   telegram-natalia-bot.js     │
│ panel.redservicio.net  │  │   natalia:~/                  │
└────────────┬───────────┘  └───────────┬───────────────────┘
             │                          │
             └──────────┬───────────────┘
                        ▼
             ┌──────────────────────────┐
             │   Natalia Bridge API     │
             │   localhost:18790        │
             │   /api/chat              │
             └──────────┬───────────────┘
                        │
                        ▼
             ┌──────────────────────────┐
             │   RAG Service            │
             │   194.41.119.21:9000     │
             │   /query                 │
             └──────────┬───────────────┘
                        │
         ┌──────────────┴──────────────┐
         ▼                             ▼
┌─────────────────┐          ┌──────────────────┐
│  Qdrant VectorDB│          │  Image Server    │
│  Embeddings     │          │  :9001           │
│  + Metadata     │          │  SimpleHTTPServer│
└─────────────────┘          └──────────────────┘
```

---

## Componente WhatsApp

### Ubicación
```
Host: panel.redservicio.net
Path: /home/panel.redservicio.net/public_html/
Framework: Laravel 10.x
PHP: 8.2
```

### Archivos Modificados

#### 1. `app/Services/WhatsAppService.php`

**Método Agregado:** `sendImageMessage()`

```php
/**
 * Enviar mensaje con imagen
 *
 * @param string $to Número de WhatsApp destino
 * @param string $imageUrl URL pública de la imagen
 * @param string|null $caption Caption opcional para la imagen
 * @param int|null $ticketId ID del ticket asociado
 * @return WhatsAppMessage|null
 */
public function sendImageMessage(
    string $to,
    string $imageUrl,
    ?string $caption = null,
    ?int $ticketId = null
): ?WhatsAppMessage
{
    try {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => [
                'link' => $imageUrl,
            ],
        ];

        if ($caption) {
            $payload['image']['caption'] = $caption;
        }

        $response = $this->client->post(
            "{$this->apiVersion}/{$this->phoneNumberId}/messages",
            [
                'headers' => [
                    'Authorization' => "Bearer {$this->accessToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]
        );

        $data = json_decode($response->getBody(), true);
        $messageId = $data['messages'][0]['id'] ?? null;

        if ($messageId) {
            return WhatsAppMessage::create([
                'message_id' => $messageId,
                'wa_id' => $to,
                'phone_number' => $to,
                'type' => 'image',
                'body' => $caption ?? '[Imagen]',
                'metadata' => ['image_url' => $imageUrl],
                'message_timestamp' => now(),
                'direction' => 'outbound',
                'status' => 'sent',
                'ticket_id' => $ticketId,
                'is_processed' => true,
            ]);
        }

        return null;

    } catch (\Exception $e) {
        Log::error('WhatsApp send image error', [
            'to' => $to,
            'image_url' => $imageUrl,
            'error' => $e->getMessage(),
        ]);
        return null;
    }
}
```

**Ubicación:** Línea ~230 (antes del cierre de clase)
**Backup:** `WhatsAppService.php.backup`

---

#### 2. `app/Services/AIAssistantService.php`

**Método Agregado:** `generateResponseWithMedia()`

```php
/**
 * Generar respuesta de IA con soporte para imágenes
 * Retorna un array con 'text' y 'mediaUrls'
 *
 * @param string $userMessage Mensaje del usuario
 * @param Ticket|null $ticket Ticket asociado
 * @param array $context Contexto adicional
 * @return array|null ['text' => string, 'mediaUrls' => array]
 */
public function generateResponseWithMedia(
    string $userMessage,
    ?Ticket $ticket = null,
    array $context = []
): ?array
{
    // Verificar límite de mensajes automáticos
    if ($ticket && !$this->canSendAutoMessage($ticket)) {
        return null;
    }

    try {
        $messages = $this->buildConversationHistory(
            $userMessage,
            $ticket,
            $context
        );

        $response = match($this->provider) {
            'natalia' => $this->callNataliaWithMedia($messages),
            default => [
                'text' => $this->callNatalia($messages),
                'mediaUrls' => []
            ],
        };

        if ($response && $response['text']) {
            $this->incrementAutoMessageCount($ticket);
        }

        return $response;

    } catch (\Exception $e) {
        Log::error('AI Assistant error', [
            'error' => $e->getMessage(),
            'provider' => $this->provider,
        ]);
        return [
            'text' => $this->getFallbackResponse($ticket),
            'mediaUrls' => []
        ];
    }
}
```

**Método Agregado:** `callNataliaWithMedia()`

```php
/**
 * Llamada a Natalia Bridge con soporte para imágenes
 *
 * @param array $messages Historial de mensajes
 * @return array ['text' => string, 'mediaUrls' => array]
 */
protected function callNataliaWithMedia(array $messages): array
{
    try {
        Log::info('[Natalia Integration] Calling Natalia bridge with media support', [
            'endpoint' => $this->nataliaEndpoint,
            'message_count' => count($messages)
        ]);

        $response = Http::timeout(35)->post($this->nataliaEndpoint, [
            'messages' => $messages,
            'max_tokens' => 500
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $assistantMessage = $data['choices'][0]['message']['content'] ?? null;
            $mediaUrls = $data['choices'][0]['message']['mediaUrls'] ?? [];

            if ($assistantMessage) {
                Log::info('[Natalia Integration] Response received with media', [
                    'text_length' => strlen($assistantMessage),
                    'media_count' => count($mediaUrls)
                ]);
                return [
                    'text' => $assistantMessage,
                    'mediaUrls' => $mediaUrls
                ];
            }
        }

        Log::error('[Natalia Integration] API error', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        // Fallback a DeepSeek directo si Natalia falla
        return [
            'text' => $this->callDeepSeek($messages),
            'mediaUrls' => []
        ];

    } catch (\Exception $e) {
        Log::error('[Natalia Integration] Exception', [
            'error' => $e->getMessage()
        ]);

        // Fallback a DeepSeek directo
        return [
            'text' => $this->callDeepSeek($messages),
            'mediaUrls' => []
        ];
    }
}
```

**Ubicación:** Líneas 32-95 (después de `generateResponse()`)
**Backup:** `AIAssistantService.php.backup`

---

#### 3. `app/Http/Controllers/WhatsAppWebhookController.php`

**Método Modificado:** `sendAIResponse()`

```php
/**
 * Enviar respuesta automática con IA
 * Ahora soporta envío de imágenes
 */
private function sendAIResponse(
    string $to,
    ?string $userMessage,
    ?Ticket $ticket
): void
{
    // Verificar si la IA está habilitada
    if (!config('services.whatsapp.ai_enabled', false)) {
        return;
    }

    try {
        $aiService = new AIAssistantService();
        $whatsappService = new WhatsAppService();

        // Generar respuesta con IA (con soporte para imágenes)
        $response = $aiService->generateResponseWithMedia(
            $userMessage ?? '',
            $ticket
        );

        if ($response && !empty($response['text'])) {
            // Enviar respuesta de texto por WhatsApp
            $sentMessage = $whatsappService->sendTextMessage(
                $to,
                $response['text'],
                $ticket?->id
            );

            if ($sentMessage) {
                Log::info('AI response sent via WhatsApp', [
                    'to' => $to,
                    'ticket_id' => $ticket?->id,
                    'message_id' => $sentMessage->message_id,
                    'media_count' => count($response['mediaUrls'] ?? []),
                ]);
            }

            // Enviar imágenes si hay en la respuesta
            if (!empty($response['mediaUrls'])) {
                foreach ($response['mediaUrls'] as $index => $imageUrl) {
                    try {
                        $caption = null; // Sin caption por ahora
                        $sentImage = $whatsappService->sendImageMessage(
                            $to,
                            $imageUrl,
                            $caption,
                            $ticket?->id
                        );

                        if ($sentImage) {
                            Log::info('AI image sent via WhatsApp', [
                                'to' => $to,
                                'ticket_id' => $ticket?->id,
                                'image_url' => $imageUrl,
                                'message_id' => $sentImage->message_id,
                            ]);
                        }

                        // Pausa entre imágenes para evitar rate limiting
                        if ($index < count($response['mediaUrls']) - 1) {
                            usleep(500000); // 0.5 segundos
                        }
                    } catch (\Exception $imageError) {
                        Log::error('Failed to send AI image', [
                            'to' => $to,
                            'image_url' => $imageUrl,
                            'error' => $imageError->getMessage(),
                        ]);
                    }
                }
            }
        }
    } catch (\Exception $e) {
        Log::error('Failed to send AI response', [
            'to' => $to,
            'error' => $e->getMessage(),
        ]);
    }
}
```

**Cambios:**
- Usa `generateResponseWithMedia()` en lugar de `generateResponse()`
- Procesa array de respuesta con 'text' y 'mediaUrls'
- Envía cada imagen secuencialmente con pausa de 500ms
- Manejo de errores individual por imagen

**Ubicación:** Línea ~272
**Backup:** (incluido en backups de repositorio)

---

## Componente Telegram

### Ubicación
```
Host: natalia (194.41.119.117)
Path: /root/telegram-natalia-bot.js
Runtime: Node.js v22.22.0
```

### Implementación Completa

```javascript
const TelegramBot = require('node-telegram-bot-api');
const axios = require('axios');

const TELEGRAM_TOKEN = '8597765277:AAEtW05DLFoaQ0XpDGnwpcDoD08j9XCrUo8';
const NATALIA_URL = 'http://localhost:18790/api/chat';

const bot = new TelegramBot(TELEGRAM_TOKEN, { polling: true });

console.log('✓ Bot de Telegram iniciado: @Natalia_jefa_bot');

bot.on('message', async (msg) => {
  const chatId = msg.chat.id;
  const text = msg.text;

  if (!text) return;

  console.log(`[Telegram] Mensaje: ${text}`);

  try {
    // Llamar a Natalia
    const response = await axios.post(NATALIA_URL, {
      messages: [{ role: 'user', content: text }],
      max_tokens: 500
    }, { timeout: 35000 });

    const message = response.data.choices?.[0]?.message;
    const responseText = message?.content || 'Error';
    const mediaUrls = message?.mediaUrls || [];

    console.log(`[Natalia] Texto: ${responseText.substring(0, 80)}...`);
    console.log(`[Natalia] Imágenes: ${mediaUrls.length}`);

    // Enviar texto
    await bot.sendMessage(chatId, responseText);

    // Enviar imágenes descargándolas primero
    for (const url of mediaUrls) {
      try {
        console.log(`[Telegram] Descargando: ${url}`);

        // Descargar imagen como buffer
        const imgResp = await axios.get(url, {
          responseType: 'arraybuffer',
          timeout: 10000
        });

        const buffer = Buffer.from(imgResp.data, 'binary');

        // Enviar como foto
        await bot.sendPhoto(chatId, buffer);
        console.log(`[Telegram] ✓ Imagen enviada`);
      } catch (err) {
        console.error(`[Error] Imagen: ${err.message}`);
      }
    }

  } catch (error) {
    console.error('[Error]:', error.message);
    await bot.sendMessage(chatId, 'Error. Intenta de nuevo.');
  }
});

bot.on('polling_error', (err) => {
  console.error('[Polling Error]:', err.message);
});
```

### Dependencias

```json
{
  "dependencies": {
    "node-telegram-bot-api": "^0.64.0",
    "axios": "^1.6.0"
  }
}
```

### Instalación

```bash
cd /root
npm install node-telegram-bot-api axios
```

### Servicio Systemd (Opcional)

```ini
[Unit]
Description=Natalia Telegram Bot
After=network.target

[Service]
Type=simple
User=root
WorkingDirectory=/root
ExecStart=/usr/bin/node /root/telegram-natalia-bot.js
Restart=always
RestartSec=10
StandardOutput=append:/tmp/telegram-bot.log
StandardError=append:/tmp/telegram-bot.log

[Install]
WantedBy=multi-user.target
```

**Instalación:**
```bash
cp telegram-bot.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable telegram-bot
systemctl start telegram-bot
```

### Workaround: Descarga de Imágenes

**Problema:** Telegram rechaza URLs directas con error "wrong type of the web page content"

**Solución:** Descargar imágenes como arraybuffer y enviar como Buffer

```javascript
// En lugar de:
await bot.sendPhoto(chatId, imageUrl);  // ❌ Falla

// Hacemos:
const response = await axios.get(imageUrl, {
  responseType: 'arraybuffer'
});
const buffer = Buffer.from(response.data, 'binary');
await bot.sendPhoto(chatId, buffer);  // ✅ Funciona
```

---

## Natalia Bridge

### Ubicación
```
Host: natalia (194.41.119.117)
Path: /root/natalia-whatsapp-bridge/server.js
Puerto: 18790
Servicio: natalia-whatsapp.service
```

### Cambio Crítico: Estructura de Response

**Problema Original:**
El campo `mediaUrls` estaba en el nivel raíz del response, no dentro de `message`.

**ANTES (Incorrecto):**
```javascript
const responseData = {
  id: response.id,
  object: 'chat.completion',
  model: 'natalia-rag-deepseek',
  choices: [{
    message: {
      role: 'assistant',
      content: assistantMessage
    },
    finish_reason: 'stop'
  }],
  mediaUrls: imagesToSend  // ❌ Nivel raíz
};
```

**DESPUÉS (Correcto):**
```javascript
const responseData = {
  id: response.id,
  object: 'chat.completion',
  model: 'natalia-rag-deepseek',
  choices: [{
    message: {
      role: 'assistant',
      content: assistantMessage,
      mediaUrls: imagesToSend  // ✅ Dentro de message
    },
    finish_reason: 'stop'
  }],
  usage: response.usage || {},
  rag_used: !!ragContext,
  images_found: imageUrls.length
};
```

### Código Completo del Cambio

**Archivo:** `/root/natalia-whatsapp-bridge/server.js`
**Líneas:** 133-150

```javascript
// Si hay imágenes, preparar para envío
const imagesToSend = (imageUrls.length > 0 && asksForPhotos)
  ? imageUrls.slice(0, 3)
  : [];

// Responder al usuario
const responseData = {
  id: response.id || 'natalia-' + Date.now(),
  object: 'chat.completion',
  model: 'natalia-rag-deepseek',
  choices: [{
    message: {
      role: 'assistant',
      content: assistantMessage,
      mediaUrls: imagesToSend  // ← CAMBIO AQUÍ
    },
    finish_reason: response.choices[0].finish_reason || 'stop'
  }],
  usage: response.usage || {},
  rag_used: !!ragContext,
  images_found: imageUrls.length
};

if (imagesToSend.length > 0) {
  console.log('[Natalia WhatsApp] Agregadas',
              imagesToSend.length,
              'URLs en mediaUrls:',
              imagesToSend);
}

res.json(responseData);
```

### Detección de Solicitud de Imágenes

```javascript
// Keywords que activan búsqueda en RAG
const keywords = [
  'salado', 'resort', 'apartamento', 'punta cana',
  'golf', 'playa', 'inmobiliaria', 'foto', 'imagen',
  'picture', 'exterior', 'interior', 'muestra', 'ver', 'envia'
];

const hasKeyword = keywords.some(kw =>
  userMessage.toLowerCase().includes(kw)
);

// Detección específica de solicitud de fotos
const asksForPhotos = /foto|imagen|picture|muestra|ver|envia/i.test(userMessage);

// Solo buscar imágenes si pide fotos
const imagesToSend = (imageUrls.length > 0 && asksForPhotos)
  ? imageUrls.slice(0, 3)
  : [];
```

### Reinicio del Servicio

```bash
systemctl restart natalia-whatsapp
systemctl status natalia-whatsapp
journalctl -u natalia-whatsapp -f
```

---

## Servicio RAG

### Ubicación
```
Host: 194.41.119.21
Puerto: 9000 (RAG Service)
Puerto: 9001 (Image Server)
```

### Endpoint de Consulta

**URL:** `http://194.41.119.21:9000/query`

**Request:**
```json
{
  "query": "Fotos de Salado",
  "collection": "marketing-inmobiliaria",
  "top_k": 5
}
```

**Response:**
```json
{
  "answer": "Salado Golf & Beach Resort es un desarrollo...",
  "sources": [
    {
      "id": "doc123",
      "score": 0.95,
      "payload": {
        "text": "Vista del resort...",
        "image_url": "http://194.41.119.21:9001/salado-exterior-8.png",
        "metadata": { ... }
      }
    },
    {
      "id": "doc124",
      "score": 0.92,
      "payload": {
        "text": "Áreas comunes...",
        "image_url": "http://194.41.119.21:9001/salado-exterior-10.jpg",
        "metadata": { ... }
      }
    }
  ]
}
```

### Extracción de Image URLs

```javascript
const sources = ragQueryResponse.data.sources || [];
const imageUrls = sources
  .map(s => s.payload?.image_url)
  .filter(url => url && url.startsWith('http'));

console.log('[Natalia WhatsApp] Imágenes encontradas:', imageUrls.length);
```

### Image Server

**Servidor:** SimpleHTTPServer Python 3
**Puerto:** 9001
**Directorio:** `/var/www/images/`

**Ejemplo de URL:**
```
http://194.41.119.21:9001/salado-exterior-8.png
http://194.41.119.21:9001/salado-exterior-10.jpg
http://194.41.119.21:9001/salado-interior-1.jpg
```

---

## Formato de Respuesta

### Formato OpenAI Compatible

```json
{
  "id": "natalia-1738440123456",
  "object": "chat.completion",
  "model": "natalia-rag-deepseek",
  "choices": [
    {
      "message": {
        "role": "assistant",
        "content": "Aquí tienes imágenes del Salado Golf & Beach Resort...",
        "mediaUrls": [
          "http://194.41.119.21:9001/salado-exterior-8.png",
          "http://194.41.119.21:9001/salado-exterior-10.jpg"
        ]
      },
      "finish_reason": "stop",
      "index": 0
    }
  ],
  "usage": {
    "prompt_tokens": 120,
    "completion_tokens": 85,
    "total_tokens": 205
  },
  "rag_used": true,
  "images_found": 2
}
```

### Campos Adicionales

- **`rag_used`**: `boolean` - Indica si se usó RAG en la respuesta
- **`images_found`**: `number` - Total de imágenes encontradas en RAG
- **`mediaUrls`**: `string[]` - Array de URLs de imágenes (máximo 3)

---

## Flujo de Datos Completo

### Caso: Usuario solicita "Fotos de Salado"

```
1. Usuario → WhatsApp/Telegram
   Mensaje: "Fotos de Salado"

2. Cliente recibe mensaje
   - WhatsApp: Webhook recibe POST de Meta
   - Telegram: Bot recibe update via polling

3. Cliente llama a Natalia Bridge
   POST http://localhost:18790/api/chat
   Body: {
     "messages": [
       {"role": "user", "content": "Fotos de Salado"}
     ],
     "max_tokens": 500
   }

4. Natalia Bridge detecta keywords
   - hasKeyword: true (contiene "salado" y "fotos")
   - asksForPhotos: true (match regex /foto/)

5. Natalia Bridge consulta RAG
   POST http://194.41.119.21:9000/query
   Body: {
     "query": "Fotos de Salado",
     "collection": "marketing-inmobiliaria",
     "top_k": 5
   }

6. RAG Service busca en Qdrant
   - Búsqueda semántica por embeddings
   - Retorna top 5 documentos más relevantes
   - Cada documento incluye metadata con image_url

7. RAG Service genera respuesta
   Response: {
     "answer": "Texto descriptivo generado...",
     "sources": [
       {payload: {image_url: "http://...png"}},
       {payload: {image_url: "http://...jpg"}},
       ...
     ]
   }

8. Natalia Bridge procesa respuesta
   - Extrae texto de "answer"
   - Extrae image_urls de sources
   - Limita a 3 imágenes máximo
   - Construye response OpenAI-compatible

9. Natalia Bridge retorna a cliente
   Response: {
     "choices": [{
       "message": {
         "role": "assistant",
         "content": "Texto descriptivo...",
         "mediaUrls": ["url1", "url2", "url3"]
       }
     }]
   }

10. Cliente procesa respuesta
    - Extrae text de choices[0].message.content
    - Extrae mediaUrls de choices[0].message.mediaUrls

11. Cliente envía texto
    - WhatsApp: sendTextMessage() via Business API
    - Telegram: bot.sendMessage()

12. Cliente envía imágenes (loop)
    Para cada URL en mediaUrls:

    WhatsApp:
    - sendImageMessage(url) → Business API
    - Pausa 500ms entre imágenes

    Telegram:
    - axios.get(url, {responseType: 'arraybuffer'})
    - Buffer.from(data)
    - bot.sendPhoto(buffer)

13. Usuario recibe respuesta completa
    - Mensaje de texto
    - 2-3 imágenes como archivos adjuntos
```

---

## Seguridad y Rate Limiting

### WhatsApp Business API
- Rate limit: 80 mensajes/segundo por número
- Pausa implementada: 500ms entre imágenes
- Timeout: 30s por request

### Telegram Bot API
- Rate limit: 30 mensajes/segundo
- Rate limit: 20 mensajes/minuto por chat
- Timeout: 10s por descarga de imagen
- Polling timeout: 35s

### Natalia Bridge
- Timeout: 35s para llamada a RAG
- Timeout: 45s para consulta RAG completa
- Sin rate limiting interno

---

## Backups y Rollback

### Archivos con Backup

```bash
# WhatsApp
/home/panel.redservicio.net/public_html/app/Services/WhatsAppService.php.backup
/home/panel.redservicio.net/public_html/app/Services/AIAssistantService.php.backup

# Natalia Bridge
/root/natalia-whatsapp-bridge/server.js.backup
```

### Comando de Rollback

```bash
# WhatsApp
cd /home/panel.redservicio.net/public_html/app/Services/
cp WhatsAppService.php.backup WhatsAppService.php
cp AIAssistantService.php.backup AIAssistantService.php

# Natalia Bridge
cd /root/natalia-whatsapp-bridge/
cp server.js.backup server.js
systemctl restart natalia-whatsapp
```

---

**Documento generado:** 2026-02-01 20:30 UTC
**Versión:** 1.0.0
