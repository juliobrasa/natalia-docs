# Troubleshooting - Sistema de Imágenes Natalia

## Índice de Problemas

1. [WhatsApp no envía imágenes](#whatsapp-no-envía-imágenes)
2. [Telegram no envía imágenes](#telegram-no-envía-imágenes)
3. [Natalia no devuelve mediaUrls](#natalia-no-devuelve-mediaurls)
4. [RAG no encuentra imágenes](#rag-no-encuentra-imágenes)
5. [Imágenes no son accesibles](#imágenes-no-son-accesibles)
6. [Errores de timeout](#errores-de-timeout)
7. [Bot de Telegram caído](#bot-de-telegram-caído)
8. [Servicio Natalia caído](#servicio-natalia-caído)

---

## WhatsApp no envía imágenes

### Síntomas
- Usuario recibe solo texto, sin imágenes
- Logs muestran "media_count: 0"

### Diagnóstico

**Paso 1: Verificar logs**
```bash
ssh root@panel.redservicio.net
tail -100 /home/panel.redservicio.net/public_html/storage/logs/laravel.log | grep "AI response\|media"
```

**Buscar:**
```
[Natalia Integration] Response received with media
media_count: 0  ← PROBLEMA
```

**Paso 2: Probar API directamente**
```bash
curl -X POST http://194.41.119.117:18790/api/chat \
  -H "Content-Type: application/json" \
  -d '{"messages":[{"role":"user","content":"Fotos de Salado"}]}' \
  | jq '.choices[0].message.mediaUrls'
```

**Resultado esperado:**
```json
[
  "http://194.41.119.21:9001/salado-exterior-8.png",
  "http://194.41.119.21:9001/salado-exterior-10.jpg"
]
```

### Soluciones

#### Solución 1: mediaUrls no viene en response
**Causa:** Natalia Bridge no está devolviendo mediaUrls

**Fix:**
```bash
ssh root@194.41.119.117
cd /root/natalia-whatsapp-bridge/
# Restaurar desde backup si es necesario
cp server.js.backup server.js
# O verificar que mediaUrls esté en message
grep -A 5 "mediaUrls" server.js
# Debería mostrar:
#   message: {
#     role: 'assistant',
#     content: assistantMessage,
#     mediaUrls: imagesToSend  ← Debe estar aquí
#   }

systemctl restart natalia-whatsapp
```

#### Solución 2: PHP no está procesando mediaUrls
**Causa:** Código PHP usando método viejo

**Fix:**
```bash
ssh root@panel.redservicio.net
cd /home/panel.redservicio.net/public_html/app/Http/Controllers/

# Verificar que usa generateResponseWithMedia()
grep "generateResponseWithMedia" WhatsAppWebhookController.php

# Si no aparece, restaurar desde backup
# (Contactar con admin para restaurar)
```

#### Solución 3: Mensaje no contiene palabras clave
**Causa:** Usuario no pide fotos explícitamente

**Keywords requeridas:**
```
foto, imagen, picture, muestra, ver, envia, pasa, envía, manda, dame
```

**Test:**
```
✅ "Fotos de Salado"
✅ "Envía imágenes"
✅ "Muestra el resort"
❌ "Información del resort"  (sin keyword)
❌ "Cuéntame sobre Salado"    (sin keyword)
```

---

## Telegram no envía imágenes

### Síntomas
- Bot responde con texto
- No llegan imágenes
- Logs: "Imágenes: 0"

### Diagnóstico

**Paso 1: Verificar bot activo**
```bash
ssh root@194.41.119.117
ps aux | grep telegram-natalia-bot
```

**Paso 2: Ver logs del bot**
```bash
tail -50 /tmp/telegram-bot.log
```

**Buscar:**
```
[Natalia] Imágenes: 0  ← PROBLEMA
```

**Paso 3: Probar API localmente**
```bash
curl -s -X POST http://localhost:18790/api/chat \
  -H "Content-Type: application/json" \
  -d '{"messages":[{"role":"user","content":"Fotos de Salado"}]}' \
  | jq '.choices[0].message'
```

### Soluciones

#### Solución 1: Bot no está corriendo
```bash
ssh root@194.41.119.117
pkill -f telegram-natalia-bot
nohup node /root/telegram-natalia-bot.js > /tmp/telegram-bot.log 2>&1 &

# Verificar
tail -f /tmp/telegram-bot.log
# Debería mostrar: "✓ Bot de Telegram iniciado"
```

#### Solución 2: Bot usa código viejo
```bash
ssh root@194.41.119.117

# Verificar que el código accede a mediaUrls correctamente
grep "mediaUrls" /root/telegram-natalia-bot.js

# Debería tener:
# const mediaUrls = message?.mediaUrls || [];

# Si no, restaurar código actualizado desde IMPLEMENTACION-TECNICA.md
```

#### Solución 3: Error al descargar imágenes
**Logs muestran:**
```
Error enviando imagen: ETELEGRAM: 400 Bad Request
```

**Fix:** Ya implementado - bot descarga como buffer

**Verificar:**
```bash
grep "arraybuffer" /root/telegram-natalia-bot.js
# Debe existir responseType: 'arraybuffer'
```

#### Solución 4: Múltiples instancias del bot
```bash
# Detener todas
pkill -9 -f telegram-natalia-bot

# Esperar
sleep 3

# Iniciar una sola instancia
nohup node /root/telegram-natalia-bot.js > /tmp/telegram-bot.log 2>&1 &

# Verificar que hay solo una
ps aux | grep telegram-natalia-bot | grep -v grep
# Debería mostrar UN solo proceso
```

---

## Natalia no devuelve mediaUrls

### Síntomas
- API de Natalia responde
- Campo mediaUrls ausente o vacío
- RAG funciona pero no hay imágenes

### Diagnóstico

**Test directo:**
```bash
curl -X POST http://194.41.119.117:18790/api/chat \
  -H "Content-Type: application/json" \
  -d '{"messages":[{"role":"user","content":"Fotos de Salado"}],"max_tokens":500}' \
  | jq '.'
```

**Verificar estructura:**
```json
{
  "choices": [{
    "message": {
      "role": "assistant",
      "content": "...",
      "mediaUrls": []  ← PROBLEMA: array vacío
    }
  }],
  "rag_used": true,
  "images_found": 0  ← PROBLEMA
}
```

### Soluciones

#### Solución 1: mediaUrls en lugar incorrecto
**Verificar ubicación en código:**
```bash
ssh root@194.41.119.117
cd /root/natalia-whatsapp-bridge/

# Ver estructura del response
sed -n '133,150p' server.js
```

**Debe ser:**
```javascript
choices: [{
  message: {
    role: 'assistant',
    content: assistantMessage,
    mediaUrls: imagesToSend  ← AQUÍ, dentro de message
  }
}]
```

**NO debe ser:**
```javascript
choices: [{
  message: {
    role: 'assistant',
    content: assistantMessage
  }
}],
mediaUrls: imagesToSend  ← MAL, fuera de message
```

**Fix:**
```bash
# Restaurar desde backup
cp server.js.backup server.js

# O editar manualmente según IMPLEMENTACION-TECNICA.md

systemctl restart natalia-whatsapp
```

#### Solución 2: RAG no devuelve imágenes
Ver siguiente sección: [RAG no encuentra imágenes](#rag-no-encuentra-imágenes)

---

## RAG no encuentra imágenes

### Síntomas
- Natalia responde con texto
- `images_found: 0` en response
- Logs: "Imágenes encontradas: 0"

### Diagnóstico

**Paso 1: Verificar RAG Service**
```bash
curl http://194.41.119.21:9000/health
# Esperado: {"status": "ok"}
```

**Paso 2: Probar consulta directa**
```bash
curl -X POST http://194.41.119.21:9000/query \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Salado resort fotos",
    "collection": "marketing-inmobiliaria",
    "top_k": 5
  }' | jq '.sources[].payload.image_url'
```

**Resultado esperado:**
```json
"http://194.41.119.21:9001/salado-exterior-8.png"
"http://194.41.119.21:9001/salado-exterior-10.jpg"
...
```

### Soluciones

#### Solución 1: RAG Service caído
```bash
ssh root@194.41.119.21
systemctl status rag-service

# Si está inactivo
systemctl start rag-service
systemctl status rag-service
```

#### Solución 2: Colección no tiene imágenes
**Verificar metadata en Qdrant:**
```bash
curl -X POST http://194.41.119.21:6333/collections/marketing-inmobiliaria/points/search \
  -H "Content-Type: application/json" \
  -d '{
    "vector": [0.1, 0.2, ...],  # Vector de prueba
    "limit": 5,
    "with_payload": true
  }' | jq '.result[].payload.image_url'
```

**Si no hay image_url en payload:**
- Reindexar documentos con metadata de imágenes
- Contactar admin de RAG

#### Solución 3: Keywords no coinciden
**Natalia solo busca imágenes si detecta:**
```javascript
const keywords = ['salado', 'resort', 'apartamento', 'punta cana',
                  'golf', 'playa', 'inmobiliaria', 'foto', 'imagen',
                  'picture', 'exterior', 'interior', 'muestra', 'ver', 'envia'];
```

**Test:**
```bash
# Esto debería funcionar
curl ... -d '{"messages":[{"role":"user","content":"Fotos de Salado"}]}'

# Esto NO activará imágenes
curl ... -d '{"messages":[{"role":"user","content":"Información"}]}'
```

---

## Imágenes no son accesibles

### Síntomas
- mediaUrls tiene URLs
- Pero las imágenes no cargan
- Error 404 o timeout

### Diagnóstico

**Probar URL directamente:**
```bash
curl -I http://194.41.119.21:9001/salado-exterior-8.png
```

**Resultado esperado:**
```
HTTP/1.0 200 OK
Content-type: image/png
Content-Length: 1176832
```

### Soluciones

#### Solución 1: Image Server caído
```bash
ssh root@194.41.119.21

# Ver proceso
ps aux | grep SimpleHTTP

# Si no está corriendo, iniciar
cd /var/www/images/
python3 -m http.server 9001 &
```

#### Solución 2: Imagen no existe
```bash
ssh root@194.41.119.21
ls -la /var/www/images/ | grep salado
```

**Si falta imagen:**
- Subir imagen al servidor
- O actualizar metadata en RAG

#### Solución 3: Firewall bloquea puerto 9001
```bash
# Verificar firewall
ufw status | grep 9001

# Abrir puerto si está cerrado
ufw allow 9001/tcp
```

---

## Errores de Timeout

### Síntomas
- "Request timeout"
- "Connection timeout"
- Respuestas muy lentas

### Diagnóstico

**Ver timeouts configurados:**

**WhatsApp (PHP):**
```php
// AIAssistantService.php
Http::timeout(35)->post(...)  // 35 segundos
```

**Telegram (Node.js):**
```javascript
axios.post(NATALIA_URL, {}, { timeout: 35000 })  // 35 segundos
```

**Natalia Bridge:**
```javascript
const ragQueryResponse = await axios.post(`${RAG_SERVICE}/query`, {}, {
  timeout: 45000  // 45 segundos
});
```

### Soluciones

#### Solución 1: RAG muy lento
```bash
# Ver logs de RAG
ssh root@194.41.119.21
journalctl -u rag-service -n 100 | grep "query time"
```

**Si query time > 30s:**
- Optimizar embeddings
- Reducir top_k
- Agregar índices a Qdrant

#### Solución 2: Aumentar timeouts
**Si es necesario aumentar (no recomendado):**

```javascript
// Natalia Bridge - server.js
const ragQueryResponse = await axios.post(`${RAG_SERVICE}/query`, {
  // ...
}, {
  timeout: 60000  // Aumentar a 60s
});
```

Luego reiniciar:
```bash
systemctl restart natalia-whatsapp
```

---

## Bot de Telegram caído

### Síntomas
- Bot no responde
- Mensajes no llegan
- No hay logs nuevos

### Diagnóstico

```bash
ssh root@194.41.119.117
ps aux | grep telegram-natalia-bot
```

**Si no muestra proceso:** Bot está caído

### Soluciones

#### Solución 1: Reiniciar bot
```bash
# Detener cualquier instancia vieja
pkill -f telegram-natalia-bot

# Iniciar nueva
nohup node /root/telegram-natalia-bot.js > /tmp/telegram-bot.log 2>&1 &

# Verificar
tail -f /tmp/telegram-bot.log
```

#### Solución 2: Error en el código
**Ver últimos logs:**
```bash
tail -50 /tmp/telegram-bot.log
```

**Errores comunes:**
```
SyntaxError: Invalid or unexpected token
→ Fix: Restaurar código desde IMPLEMENTACION-TECNICA.md

Error: Cannot find module 'axios'
→ Fix: npm install axios

ETELEGRAM: 409 Conflict: terminated by other getUpdates
→ Fix: Matar todas las instancias y arrancar solo una
```

#### Solución 3: Crear servicio systemd
**Para evitar caídas:**
```bash
# Ver sección de servicio systemd en IMPLEMENTACION-TECNICA.md
cp telegram-bot.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable telegram-bot
systemctl start telegram-bot
```

---

## Servicio Natalia caído

### Síntomas
- Tanto WhatsApp como Telegram fallan
- Error: "Connection refused" en puerto 18790
- Logs vacíos

### Diagnóstico

```bash
ssh root@194.41.119.117
systemctl status natalia-whatsapp
```

**Si muestra "inactive" o "failed":** Servicio caído

### Soluciones

#### Solución 1: Reiniciar servicio
```bash
systemctl restart natalia-whatsapp
systemctl status natalia-whatsapp

# Ver logs
journalctl -u natalia-whatsapp -n 50
```

#### Solución 2: Error en el código
**Ver logs de error:**
```bash
journalctl -u natalia-whatsapp -n 100 | grep -i error
```

**Errores comunes:**
```
SyntaxError
→ Fix: cp server.js.backup server.js; systemctl restart natalia-whatsapp

Cannot find module
→ Fix: cd /root/natalia-whatsapp-bridge; npm install

Port already in use
→ Fix: pkill node; systemctl restart natalia-whatsapp
```

#### Solución 3: Verificar dependencias
```bash
cd /root/natalia-whatsapp-bridge/
npm install
systemctl restart natalia-whatsapp
```

---

## Comandos Útiles de Diagnóstico

### Ver estado de todos los servicios
```bash
# Natalia Bridge
ssh root@194.41.119.117 "systemctl status natalia-whatsapp --no-pager"

# Telegram Bot
ssh root@194.41.119.117 "ps aux | grep telegram-natalia-bot | grep -v grep"

# RAG Service
ssh root@194.41.119.21 "systemctl status rag-service --no-pager"

# Image Server
ssh root@194.41.119.21 "ps aux | grep 'python.*9001' | grep -v grep"
```

### Test completo end-to-end
```bash
# 1. Test RAG directo
curl -X POST http://194.41.119.21:9000/query \
  -H "Content-Type: application/json" \
  -d '{"query":"Salado fotos","collection":"marketing-inmobiliaria","top_k":5}' \
  | jq '.sources[].payload.image_url'

# 2. Test Natalia Bridge
curl -X POST http://194.41.119.117:18790/api/chat \
  -H "Content-Type: application/json" \
  -d '{"messages":[{"role":"user","content":"Fotos de Salado"}]}' \
  | jq '.choices[0].message'

# 3. Test imagen accesible
curl -I http://194.41.119.21:9001/salado-exterior-8.png

# 4. Enviar mensaje a Telegram (requiere chat_id real)
# Hacerlo manualmente desde la app

# 5. Ver logs en tiempo real
ssh root@194.41.119.117 "tail -f /tmp/telegram-bot.log"
```

---

## Contacto para Soporte

Si los problemas persisten:

1. **Revisar documentación completa:** `/root/NATALIA-DOCS/`
2. **Ver logs detallados** de todos los componentes
3. **Restaurar desde backups** si es necesario
4. **Documentar el error** con logs completos

---

**Última actualización:** 2026-02-01
**Versión:** 1.0.0
