# ✅ Solución: Agregar user_phone al servicio WhatsApp

**Fecha:** 3 de febrero de 2026 03:35 UTC
**Estado:** 📋 DOCUMENTADO - PENDIENTE IMPLEMENTACIÓN

---

## 🔍 Servicios Encontrados

### ✅ Telegram (FUNCIONA CORRECTAMENTE)

**Ubicación:** `/opt/carlos-api/natalia-telegram-simple.js` (VM 101 - code.juliobrasa.com)

**Código actual:**
```javascript
// Llamar a Natalia con el mensaje
const nataliaResponse = await axios.post(NATALIA_URL, {
  messages: [
    { role: 'user', content: message }
  ],
  max_tokens: 500,
  user_phone: userId  // ✅ SÍ ENVÍA user_phone
}, {
  timeout: 35000,
  headers: {
    'Content-Type': 'application/json'
  }
});
```

**Estado:** ✅ Las sesiones de Telegram funcionan correctamente porque envía `user_phone`

---

### ❌ WhatsApp (PROBLEMA)

**User-Agent detectado:** `GuzzleHttp/7` (cliente PHP)

**Request body actual:**
```json
{
  "messages": [...],
  "max_tokens": 500
}
```

**Problema:** ❌ NO envía `user_phone`, `user`, `metadata`, ni header `x-user-phone`

---

## 📂 Archivos de WhatsApp Encontrados

### VM 100 (soltia / panel.redservicio.net)

1. **/home/panel.redservicio.net/public_html/app/Http/Controllers/WhatsAppWebhookController.php**
   - Recibe webhooks de WhatsApp Business API
   - Procesa mensajes entrantes
   - Llama a `AIAssistantService::generateResponse()`

2. **/home/panel.redservicio.net/public_html/app/Services/AIAssistantService.php**
   - ✅ Llama directamente a DeepSeek API (NO a Natalia)
   - Método: `callDeepSeek()`
   - Usa historial de base de datos (tabla `whatsapp_messages`)

3. **/home/panel.redservicio.net/public_html/app/Services/WhatsAppService.php**
   - Servicio para ENVIAR mensajes (no recibir)
   - Llama a WhatsApp Business API de Facebook

**Conclusión:** El sistema en VM 100 NO llama a Natalia, llama directamente a DeepSeek.

---

## ⚠️ Servicio WhatsApp NO Encontrado

El servicio PHP que hace llamadas con GuzzleHttp/7 a Natalia NO fue encontrado en:
- ✗ VM 100 (soltia)
- ✗ VM 101 (code.juliobrasa.com)
- ✗ VM 102-120 (otros servidores)
- ✗ VM 150-155 (agentes)

**Posibilidades:**
1. Está en un servidor externo (proveedor de WhatsApp Business API)
2. Está en un contenedor Docker no revisado
3. Es un webhook configurado en la plataforma de WhatsApp Business que apunta a Natalia directamente

---

## ✅ SOLUCIÓN 1: Modificar Servicio WhatsApp (CUANDO SE ENCUENTRE)

### Código a Implementar

**ANTES (problema):**
```php
<?php
use GuzzleHttp\Client;

$client = new Client();
$response = $client->post('http://194.41.119.117:18790/api/chat', [
    'json' => [
        'messages' => $messages,
        'max_tokens' => 500,
        // ❌ Falta user_phone
    ]
]);
```

**DESPUÉS (solución):**
```php
<?php
use GuzzleHttp\Client;

$client = new Client();
$response = $client->post('http://194.41.119.117:18790/api/chat', [
    'json' => [
        'messages' => $messages,
        'max_tokens' => 500,
        'user_phone' => $phoneNumber,  // ✅ AGREGAR ESTO
    ]
]);
```

### O via Header:

```php
<?php
$response = $client->post('http://194.41.119.117:18790/api/chat', [
    'headers' => [
        'X-User-Phone' => $phoneNumber,  // ✅ AGREGAR ESTO
        'Content-Type' => 'application/json',
    ],
    'json' => [
        'messages' => $messages,
        'max_tokens' => 500,
    ]
]);
```

### O via metadata:

```php
<?php
$response = $client->post('http://194.41.119.117:18790/api/chat', [
    'json' => [
        'messages' => $messages,
        'max_tokens' => 500,
        'metadata' => [
            'phone' => $phoneNumber,  // ✅ AGREGAR ESTO
            'from' => $phoneNumber,
        ]
    ]
]);
```

---

## ✅ SOLUCIÓN 2: Modificar Natalia Bridge (YA IMPLEMENTABLE)

Si no se puede modificar el servicio WhatsApp, modificar Natalia para usar IP como sessionId.

**Ver documentación completa:** `/root/NATALIA-DOCS/NATALIA-PROBLEMA-SESIONES-SIN-TELEFONO-2026-02-03.md`

**Resumen:**
- Usar IP del cliente como identificador de sesión cuando falta `user_phone`
- Script de fix listo en: `/root/natalia-whatsapp-bridge/apply-session-fix.js` (por crear)
- Requiere modificación de `/root/natalia-whatsapp-bridge/server.js`

---

## ✅ SOLUCIÓN 3: Webhook de WhatsApp Business Direct

Si el webhook está configurado en la plataforma de WhatsApp Business (Meta), necesitamos:

1. **Acceder a Meta Business Manager:**
   - https://business.facebook.com/
   - Ir a Configuración → WhatsApp → Webhook

2. **Verificar webhook actual:**
   ```
   URL actual probablemente: http://194.41.119.117:18790/api/chat
   ```

3. **Opción A: Crear webhook intermediario**

   Crear nuevo archivo: `/opt/whatsapp-to-natalia-bridge.php`

   ```php
   <?php
   // Recibir webhook de WhatsApp Business API
   $input = file_get_contents('php://input');
   $data = json_decode($input, true);

   // Extraer información
   $entry = $data['entry'][0] ?? [];
   $changes = $entry['changes'][0] ?? [];
   $value = $changes['value'] ?? [];
   $messages = $value['messages'] ?? [];

   if (!empty($messages)) {
       $message = $messages[0];
       $from = $message['from']; // Número de teléfono
       $text = $message['text']['body'] ?? '';

       // Llamar a Natalia CON user_phone
       $ch = curl_init('http://194.41.119.117:18790/api/chat');
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
           'messages' => [
               ['role' => 'user', 'content' => $text]
           ],
           'max_tokens' => 500,
           'user_phone' => $from,  // ✅ AGREGAR user_phone
       ]));
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       $response = curl_exec($ch);
       curl_close($ch);

       // Enviar respuesta de vuelta a WhatsApp...
       // (código para enviar mensaje via WhatsApp Business API)
   }

   http_response_code(200);
   echo json_encode(['status' => 'ok']);
   ```

4. **Opción B: Actualizar webhook URL en Meta**
   - Cambiar URL del webhook para incluir el número en la URL o query string
   - Ejemplo: `http://194.41.119.117:18790/api/chat?phone={PHONE}`

---

## 🔎 Cómo Encontrar el Servicio WhatsApp

### Método 1: Logs de Natalia

```bash
# Ver las IPs que llaman a Natalia
ssh root@194.41.119.117 "journalctl -u natalia-whatsapp -n 500 --no-pager | grep 'Request headers' -A5 | grep host"
```

### Método 2: Netstat/ss

```bash
# Ver conexiones activas cuando llegue un mensaje de WhatsApp
ssh root@194.41.119.117 "ss -tnp | grep :18790"
```

### Método 3: tcpdump

```bash
# Capturar tráfico en el puerto 18790
ssh root@194.41.119.117 "tcpdump -i any port 18790 -n -A 2>&1"
# Enviar un mensaje de WhatsApp mientras corre tcpdump
```

### Método 4: Buscar en Meta Business Manager

1. Ir a https://business.facebook.com/
2. Seleccionar el negocio de WhatsApp
3. Ir a WhatsApp → Configuración → Configuración de API
4. Ver "URL del webhook" configurada

---

## 📋 Checklist de Implementación

### Cuando se encuentre el servicio WhatsApp:

- [ ] **Identificar archivo exacto** (PHP, JS, Python, etc.)
- [ ] **Crear backup** del archivo
- [ ] **Agregar user_phone** en la llamada a Natalia
- [ ] **Probar** con un mensaje de prueba
- [ ] **Verificar logs** de Natalia: debe mostrar "📱 Phone: +1234567890" (no "desconocido")
- [ ] **Verificar sesiones:** segundo mensaje debe tener "Longitud: 3" (no "1")
- [ ] **Verificar respuesta:** Natalia NO debe saludar en cada mensaje
- [ ] **Commit** del cambio
- [ ] **Documentar** en este archivo la ubicación exacta

---

## 🧪 Test de Verificación

### Test 1: Enviar primer mensaje por WhatsApp

```
Usuario: Hola
```

**Logs esperados en Natalia:**
```
[Natalia WhatsApp] Request body keys: ['messages', 'max_tokens', 'user_phone']
[Natalia WhatsApp] 📱 Phone: +1234567890
[Session] 🆕 Nueva sesión: +1234567890
[Natalia WhatsApp] 📊 Longitud: 1
[Natalia WhatsApp] 👋 Primera interacción: true
```

**Respuesta esperada:**
```
¡Hola! 👋 Soy Natalia de Soltia Consulting Group...
```

### Test 2: Enviar segundo mensaje (mismo usuario)

```
Usuario: Cuéntame más
```

**Logs esperados:**
```
[Natalia WhatsApp] 📱 Phone: +1234567890
[Session] 🔄 Recuperado: 3 mensajes
[Natalia WhatsApp] 📊 Longitud: 3
[Natalia WhatsApp] 👋 Primera interacción: false  ← NO saluda
```

**Respuesta esperada:**
```
[Respuesta sin saludo, continúa la conversación]
```

---

## 📊 Comparación: Telegram vs WhatsApp

| Aspecto | Telegram | WhatsApp |
|---------|----------|----------|
| Ubicación | `/opt/carlos-api/natalia-telegram-simple.js` (VM 101) | ❓ No encontrado |
| Cliente HTTP | axios (Node.js) | GuzzleHttp/7 (PHP) |
| Envía user_phone | ✅ SÍ (`user_phone: userId`) | ❌ NO |
| Sesiones funcionan | ✅ SÍ | ❌ NO (resetea cada mensaje) |
| Código base | Natalia bridge | ❓ Desconocido |

---

## 🎯 Próximos Pasos

1. **URGENTE:** Encontrar el servicio PHP que llama a Natalia
   - Métodos: tcpdump, logs, Meta Business Manager

2. **Implementar fix:** Agregar `user_phone` en la llamada

3. **Alternativa:** Si no se encuentra, implementar Solución 2 (usar IP como sessionId)

4. **Documentar:** Ubicación exacta del servicio cuando se encuentre

5. **Commit y deploy:** Una vez implementado

---

## 📞 Información de Contacto del Problema

**Usuario afectado:** Julio (probablemente +34 XXX XXX XXX)

**Síntoma:**
```
[Julio] Tienes más fotos?
[Natalia] ¡Claro que sí! Te envío...

[Julio] Envía más fotos
[Natalia] ¡Claro! Te envío...

[Julio] Cualquiera
[Natalia] ¡Hola! Soy Natalia... (RESETEO COMPLETO)
```

**Logs:**
```
[Natalia WhatsApp] Request body keys: ['messages', 'max_tokens']  ← Falta user_phone
[Natalia WhatsApp] ⚠️  No phone number - contexto no se guardará
[Natalia WhatsApp] 📱 Phone: desconocido
[Natalia WhatsApp] 📊 Longitud: 1  ← Siempre 1
[Natalia WhatsApp] 👋 Primera interacción: true  ← Siempre true
```

---

**Documentado por:** Claude Code
**Prioridad:** 🔴 ALTA
**Estado:** Pendiente encontrar servicio WhatsApp
**Solución alternativa:** Modificar Natalia bridge (Solución 2)
