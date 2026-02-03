# 🐛 Problema: Sesiones no se guardan (sin número de teléfono)

**Fecha:** 3 de febrero de 2026 03:26 UTC
**Estado:** 🔴 PROBLEMA IDENTIFICADO - SOLUCIÓN EN PROCESO

---

## 🎯 Problema Reportado

Usuario (Julio) experimenta que Natalia resetea la conversación en cada mensaje:

```
[Usuario] Tienes más fotos?
[Natalia] ¡Claro que sí! Te envío más imágenes...

[Usuario] Envía más fotos
[Natalia] ¡Claro! Te envío más imágenes...

[Usuario] Cualquiera
[Natalia] ¡Hola! Soy Natalia... (SALUDO INICIAL - RESETEO)
```

---

## 🔍 Causa Raíz

### Análisis de Logs:

```
[Natalia WhatsApp] Request body keys: ['messages', 'max_tokens']
[Natalia WhatsApp] ⚠️  No phone number - contexto no se guardará
[Natalia WhatsApp] 📱 Phone: desconocido
[Natalia WhatsApp] 📊 Longitud: 1
[Natalia WhatsApp] 👋 Primera interacción: true
```

**Problema:**
- El servicio que llama a Natalia (GuzzleHttp/7) NO envía `user_phone`
- Sin identificador de usuario, NO se pueden guardar sesiones
- Cada mensaje se trata como nueva conversación
- Historial siempre tiene longitud 1

### Request Headers:
```json
{
  "host": "194.41.119.117:18790",
  "user-agent": "GuzzleHttp/7",
  "content-type": "application/json",
  "content-length": "96"
}
```

**Campos esperados pero NO recibidos:**
- ❌ `user_phone` (body)
- ❌ `user` (body)
- ❌ `metadata.phone` (body)
- ❌ `metadata.from` (body)
- ❌ `x-user-phone` (header)

---

## ✅ Solución

### Opción 1: Usar IP como Identificador de Sesión (RECOMENDADO)

Cuando no hay número de teléfono, usar la IP del cliente como identificador de sesión.

**Código a agregar en `/root/natalia-whatsapp-bridge/server.js`:**

```javascript
// Después de la línea:
const phoneNumber = user_phone || user || metadata?.phone || metadata?.from || req.headers['x-user-phone'];

// AGREGAR:
// Si no hay phoneNumber, usar IP del cliente como identificador de sesión
let sessionId = phoneNumber;
if (!sessionId) {
  const clientIp = (req.headers["x-forwarded-for"] || "").split(",")[0].trim()
    || req.connection?.remoteAddress
    || req.socket?.remoteAddress
    || "unknown";
  sessionId = `ip-${clientIp}`;
  console.log("[Session] 🔑 Usando IP como identificador:", sessionId);
}
```

**Luego reemplazar todas las llamadas a funciones de sesión:**

```javascript
// ANTES:
addMessageToSession(phoneNumber, 'user', userMessage);
const session = getSession(phoneNumber);
conversationSessions.set(phoneNumber, session);

// DESPUÉS:
addMessageToSession(sessionId, 'user', userMessage);
const session = getSession(sessionId);
conversationSessions.set(sessionId, session);
```

**Actualizar condicionales:**

```javascript
// ANTES:
if (messages.length === 1 && phoneNumber) {
if (phoneNumber) {
if (phoneNumber && assistantMessage) {

// DESPUÉS:
if (messages.length === 1 && sessionId) {
if (sessionId) {
if (sessionId && assistantMessage) {
```

**Actualizar mensaje de warning:**

```javascript
// ANTES:
if (!phoneNumber) {
  console.warn('[Natalia WhatsApp] ⚠️  No phone number - contexto no se guardará');
}

// DESPUÉS:
if (!phoneNumber) {
  console.warn('[Natalia WhatsApp] ⚠️  No phone number - usando IP como sesión:', sessionId);
}
```

---

### Opción 2: Modificar Servicio que Llama a Natalia

**Identificar el servicio:**
- User-Agent: `GuzzleHttp/7` (PHP HTTP client)
- Probablemente un webhook de WhatsApp Business API

**Modificar para enviar número de teléfono:**

```php
// PHP código del webhook
$client = new \GuzzleHttp\Client();
$response = $client->post('http://194.41.119.117:18790/api/chat', [
    'json' => [
        'messages' => $messages,
        'max_tokens' => 500,
        'user_phone' => $phoneNumber,  // ← AGREGAR ESTO
    ]
]);
```

O via header:

```php
$response = $client->post('http://194.41.119.117:18790/api/chat', [
    'headers' => [
        'X-User-Phone' => $phoneNumber,  // ← AGREGAR ESTO
    ],
    'json' => [
        'messages' => $messages,
        'max_tokens' => 500,
    ]
]);
```

---

## 📝 Archivo de Script para Aplicar Fix

**Ubicación:** `/root/natalia-whatsapp-bridge/apply-session-fix.js`

```javascript
const fs = require("fs");
const file = "/root/natalia-whatsapp-bridge/server.js";

// Crear backup
const backupFile = `${file}.backup-session-fix-${Date.now()}`;
fs.copyFileSync(file, backupFile);
console.log(`📋 Backup creado: ${backupFile}`);

let content = fs.readFileSync(file, "utf8");

// 1. Insertar código de sessionId
if (!content.includes("let sessionId = phoneNumber")) {
  const insertion = `
    // Si no hay phoneNumber, usar IP del cliente como identificador de sesión
    let sessionId = phoneNumber;
    if (!sessionId) {
      const clientIp = (req.headers["x-forwarded-for"] || "").split(",")[0].trim() || req.connection?.remoteAddress || req.socket?.remoteAddress || "unknown";
      sessionId = \`ip-\${clientIp}\`;
      console.log("[Session] 🔑 Usando IP como identificador:", sessionId);
    }
`;

  content = content.replace(
    /(const phoneNumber = user_phone[^\n]+\n)/,
    '$1' + insertion
  );
  console.log("✅ Código de sessionId insertado");
}

// 2. Reemplazar phoneNumber con sessionId en funciones de sesión
const replacements = [
  [/addMessageToSession\(phoneNumber,/g, "addMessageToSession(sessionId,"],
  [/const session = getSession\(phoneNumber\)/g, "const session = getSession(sessionId)"],
  [/conversationSessions\.set\(phoneNumber,/g, "conversationSessions.set(sessionId,"],
  [/if \(messages\.length === 1 && phoneNumber\) \{/g, "if (messages.length === 1 && sessionId) {"],
  [/if \(phoneNumber && assistantMessage\) \{/g, "if (sessionId && assistantMessage) {"],
];

replacements.forEach(([pattern, replacement], idx) => {
  const matches = content.match(pattern);
  if (matches) {
    content = content.replace(pattern, replacement);
    console.log(`✅ Reemplazo ${idx+1}: ${matches.length} ocurrencias`);
  }
});

// 3. Actualizar mensaje de warning
content = content.replace(
  /console\.warn\('\[Natalia WhatsApp\] ⚠️  No phone number - contexto no se guardará'\);/,
  'console.warn("[Natalia WhatsApp] ⚠️  No phone number - usando IP como sesión:", sessionId);'
);
console.log("✅ Mensaje de warning actualizado");

// 4. Arreglar bloque if-else de sincronización
content = content.replace(
  /if \(phoneNumber\) \{\s+\/\/ Sincronizar sesión/,
  'if (sessionId) {\n        // Sincronizar sesión'
);

// Guardar
fs.writeFileSync(file, content, "utf8");
console.log("✅ Archivo actualizado correctamente");
console.log("\n🔄 Ahora ejecuta: systemctl restart natalia-whatsapp");
```

**Para aplicar el fix:**

```bash
cd /root/natalia-whatsapp-bridge
node apply-session-fix.js
systemctl restart natalia-whatsapp
systemctl status natalia-whatsapp
```

---

## 🧪 Tests de Verificación

### Test 1: Primer Mensaje

```bash
curl -X POST http://194.41.119.117:18790/api/chat \
  -H 'Content-Type: application/json' \
  -d '{"messages":[{"role":"user","content":"Hola"}],"max_tokens":50}'
```

**Logs esperados:**
```
[Session] 🔑 Usando IP como identificador: ip-xxx.xxx.xxx.xxx
[Natalia WhatsApp] ⚠️  No phone number - usando IP como sesión: ip-xxx.xxx.xxx.xxx
[Session] 🆕 Nueva sesión: ip-xxx.xxx.xxx.xxx
[Natalia WhatsApp] 📊 Longitud: 1
[Natalia WhatsApp] 👋 Primera interacción: true
```

### Test 2: Segundo Mensaje (MISMO IP)

```bash
curl -X POST http://194.41.119.117:18790/api/chat \
  -H 'Content-Type: application/json' \
  -d '{"messages":[{"role":"user","content":"Cuéntame más"}],"max_tokens":50}'
```

**Logs esperados:**
```
[Session] 🔑 Usando IP como identificador: ip-xxx.xxx.xxx.xxx
[Session] 🔄 Recuperado: 3 mensajes
[Natalia WhatsApp] 📊 Longitud: 3
[Natalia WhatsApp] 👋 Primera interacción: false  ← NO SALUDA DE NUEVO
```

---

## 📊 Estado Actual

| Componente | Estado | Nota |
|------------|--------|------|
| Problema identificado | ✅ | Falta user_phone en requests |
| Causa raíz | ✅ | Servicio PHP no envía identificador |
| Solución diseñada | ✅ | Usar IP como sessionId |
| Fix implementado | 🟡 | En proceso |
| Tests realizados | ❌ | Pendiente |
| Documentación | ✅ | Este documento |

---

## 🔧 Solución Alternativa Temporal

Si el fix no se puede aplicar inmediatamente, el servicio que llama a Natalia puede enviar el historial completo en cada request:

```json
{
  "messages": [
    {"role": "user", "content": "Tienes más fotos?"},
    {"role": "assistant", "content": "¡Claro que sí! Te envío..."},
    {"role": "user", "content": "Envía más fotos"},
    {"role": "assistant", "content": "¡Claro! Te envío más..."},
    {"role": "user", "content": "Cualquiera"}
  ],
  "max_tokens": 500
}
```

De esta manera, aunque no se guarden sesiones en el bridge, el historial completo llega en cada request.

---

## 📞 Próximos Pasos

1. ✅ **Identificar causa:** Falta user_phone
2. 🟡 **Aplicar fix:** Usar IP como sessionId
3. ❌ **Probar:** Verificar que sesiones persistan
4. ❌ **Monitorear:** Logs en producción
5. ❌ **Documentar:** Actualizar este doc con resultados

---

**Documentado por:** Claude Code
**Prioridad:** 🔴 ALTA (afecta experiencia de usuario)
**Impacto:** Natalia resetea conversación en cada mensaje
**Solución:** Usar IP del cliente como identificador de sesión
