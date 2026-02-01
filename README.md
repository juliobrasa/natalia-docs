# Natalia - Sistema de Envío de Imágenes Automático

**Versión:** 1.0.0
**Fecha:** 2026-02-01
**Estado:** ✅ Producción

## Descripción

Natalia es una coordinadora inteligente con capacidades de RAG (Retrieval-Augmented Generation) que puede enviar automáticamente imágenes relevantes junto con respuestas de texto a través de WhatsApp y Telegram.

## Canales Disponibles

### WhatsApp
- **Número:** +34 685 80 59 24
- **Plataforma:** WhatsApp Business API
- **Backend:** Laravel/PHP en panel.redservicio.net

### Telegram
- **Bot:** @Natalia_jefa_bot
- **ID:** 8597765277
- **Backend:** Node.js en natalia (194.41.119.117)

### Web Chat
- **URL:** https://natalia.soporteclientes.net/chat
- **Backend:** MoltBot Gateway

## Características

✅ **Respuestas inteligentes con RAG**
- Búsqueda semántica en base de conocimientos
- Colección: marketing-inmobiliaria
- Backend: Qdrant Vector DB

✅ **Envío automático de imágenes**
- Detección automática de solicitudes de fotos
- Máximo 3 imágenes por respuesta
- Imágenes de alta calidad del servidor local

✅ **Contexto conversacional**
- Mantiene historial de conversación
- Tickets integrados en WhatsApp
- Respuestas contextuales

## Uso Rápido

**Ejemplo 1 - Solicitar fotos:**
```
Usuario: "Fotos de Salado"
Natalia: [Texto descriptivo del resort]
         [Imagen 1: Vista exterior]
         [Imagen 2: Áreas comunes]
```

**Ejemplo 2 - Información general:**
```
Usuario: "Información del resort"
Natalia: [Descripción detallada con datos del RAG]
```

**Ejemplo 3 - Consulta técnica:**
```
Usuario: "Cuántos apartamentos tiene?"
Natalia: [Respuesta precisa basada en documentos]
```

## Palabras Clave que Activan Imágenes

```javascript
['foto', 'imagen', 'picture', 'muestra', 'ver', 'envia',
 'pasa', 'envía', 'manda', 'dame']
```

## Arquitectura Simplificada

```
Usuario (WhatsApp/Telegram)
    ↓
Cliente (PHP/Node.js)
    ↓
Natalia Bridge (localhost:18790)
    ↓
RAG Service (194.41.119.21:9000)
    ↓
Qdrant Vector DB + Image Server
    ↓
Respuesta: texto + mediaUrls[]
```

## Servicios y Puertos

| Servicio | Host | Puerto | Descripción |
|----------|------|--------|-------------|
| Natalia Bridge | 194.41.119.117 | 18790 | API principal |
| RAG Service | 194.41.119.21 | 9000 | Búsqueda semántica |
| Image Server | 194.41.119.21 | 9001 | HTTP simple Python |
| MoltBot Gateway | 194.41.119.117 | 3100 | WebSocket control |

## Monitoreo y Logs

### Ver logs de WhatsApp
```bash
ssh root@panel.redservicio.net
tail -f /home/panel.redservicio.net/public_html/storage/logs/laravel.log | grep "AI response\|image"
```

### Ver logs de Telegram
```bash
ssh root@194.41.119.117
tail -f /tmp/telegram-bot.log
```

### Ver logs de Natalia Bridge
```bash
ssh root@194.41.119.117
journalctl -u natalia-whatsapp -f
```

### Ver logs del RAG
```bash
ssh root@194.41.119.21
journalctl -u rag-service -f
```

## Reiniciar Servicios

### WhatsApp (Laravel)
```bash
# No requiere reinicio, cambios son inmediatos
```

### Telegram
```bash
ssh root@194.41.119.117
pkill -f telegram-natalia-bot
nohup node /root/telegram-natalia-bot.js > /tmp/telegram-bot.log 2>&1 &
```

### Natalia Bridge
```bash
ssh root@194.41.119.117
systemctl restart natalia-whatsapp
```

### MoltBot
```bash
ssh root@194.41.119.117
systemctl restart moltbot
```

## Testing Rápido

### Test WhatsApp
```bash
# Enviar mensaje al número: +34 685 80 59 24
# Mensaje: "Fotos de Salado"
# Esperado: Texto + 2-3 imágenes
```

### Test Telegram
```bash
# Enviar mensaje a: @Natalia_jefa_bot
# Mensaje: "Fotos de Salado"
# Esperado: Texto + 2-3 imágenes
```

### Test API directo
```bash
curl -X POST http://194.41.119.117:18790/api/chat \
  -H "Content-Type: application/json" \
  -d '{"messages":[{"role":"user","content":"Fotos de Salado"}],"max_tokens":500}' \
  | jq '.choices[0].message'
```

## Documentación Adicional

- **Implementación técnica:** [IMPLEMENTACION-TECNICA.md](./IMPLEMENTACION-TECNICA.md)
- **Troubleshooting:** [TROUBLESHOOTING.md](./TROUBLESHOOTING.md)
- **Changelog:** [CHANGELOG.md](./CHANGELOG.md)
- **API Reference:** [API-REFERENCE.md](./API-REFERENCE.md)

## Contacto y Soporte

- **Documentación:** `/root/NATALIA-DOCS/`
- **Backups:** Ver sección de backups en cada documento técnico
- **Logs:** Ver sección de monitoreo arriba

---

**Última actualización:** 2026-02-01 20:30 UTC
