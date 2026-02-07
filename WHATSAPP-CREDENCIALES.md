# 🔐 Credenciales WhatsApp Business API

**Fecha:** 3 de febrero de 2026
**Propietario:** Soltia Consulting Group

---

## 📱 Información General

**Número WhatsApp:** +34 685 80 59 24
**Estado:** VERIFIED
**Proveedor:** Meta (Facebook) WhatsApp Business API

---

## 🔑 Credenciales

### Tokens de Acceso

```bash
WHATSAPP_ACCESS_TOKEN=EAAegimfjNj0BQgpuWrRpGNt4rjlzqB7hRmCFlueJENds8BniSYqHFYCpdRHG7ARda7W9jdyC0s2ZB8yDmEJZAZB7RdpQnA6RJbPmjjzJHZCGAY9hZBQMBpW6JoKwS8z1Vf3YEpGtWJ8n4ZAIYe6YcsWOF4vmA26gL4fzu4F833DK9832HvDAZA7RNw2nmA7j1lojgZDZD
```

### IDs

```bash
WHATSAPP_PHONE_NUMBER_ID=1031061770070997
WHATSAPP_BUSINESS_ACCOUNT_ID=1664702307862246
```

### Token de Verificación

```bash
WHATSAPP_VERIFY_TOKEN=soltia_redservicio_webhook_2026
```

---

## 🌐 Webhook Configurado

### Actual (panel.redservicio.net)

```
URL: https://panel.redservicio.net/webhook/whatsapp
Servidor: 184.174.36.104 (nodo0 - externo)
Estado: ✅ Funcional (fix aplicado el 2026-02-03)
```

### Recomendado (code.juliobrasa.com)

```
URL: https://webhook.soporteclientes.net/webhook/whatsapp
Servidor: 194.41.119.101 (infraestructura propia)
Estado: 📋 Pendiente implementar
```

**Ver:** `/root/NATALIA-DOCS/WHATSAPP-WEBHOOK-MIGRACION-2026-02-03.md`

---

## 📊 Endpoints de API

### WhatsApp Business API

```
Base URL: https://graph.facebook.com/v18.0
Phone Endpoint: /1031061770070997/messages
Account Endpoint: /1664702307862246
```

### Natalia

```
Endpoint: http://194.41.119.117:18790/api/chat
Método: POST
Headers: Content-Type: application/json
Payload: {
  "messages": [...],
  "max_tokens": 500,
  "user_phone": "34XXXXXXXXX"  ← IMPORTANTE
}
```

---

## 🔧 Gestión del Token

### Renovar Token (si expira)

1. Ir a https://business.facebook.com/
2. Seleccionar cuenta de WhatsApp Business
3. Ir a **Configuración del sistema → Token de acceso**
4. Generar nuevo token
5. Actualizar en:
   - `/home/panel.redservicio.net/public_html/.env` (actual)
   - `/opt/whatsapp-webhook/.env` (después de migrar)

### Verificar validez del token

```bash
curl -X GET "https://graph.facebook.com/v18.0/1031061770070997" \
  -H "Authorization: Bearer WHATSAPP_ACCESS_TOKEN"
```

---

## 🔐 Seguridad

⚠️ **IMPORTANTE:**
- Este archivo contiene credenciales sensibles
- NO compartir públicamente
- NO subir a repositorios públicos
- Mantener permisos restrictivos: `chmod 600`

---

## 📝 Ubicaciones de las Credenciales

### Actual

```
Servidor: 184.174.36.104 (panel.redservicio.net)
Archivo: /home/panel.redservicio.net/public_html/.env
Usuario: panel3833
```

### Backup Local

```
Servidor: 194.41.119.101 (code.juliobrasa.com)
Archivo: /root/NATALIA-DOCS/WHATSAPP-CREDENCIALES.md
Permisos: chmod 600
```

---

## 🧪 Testing

### Test de Conexión

```bash
TOKEN="EAAegimfjNj0BQgpuWrRpGNt4rjlzqB7hRmCFlueJENds8BniSYqHFYCpdRHG7ARda7W9jdyC0s2ZB8yDmEJZAZB7RdpQnA6RJbPmjjzJHZCGAY9hZBQMBpW6JoKwS8z1Vf3YEpGtWJ8n4ZAIYe6YcsWOF4vmA26gL4fzu4F833DK9832HvDAZA7RNw2nmA7j1lojgZDZD"

curl -X GET "https://graph.facebook.com/v18.0/1031061770070997" \
  -H "Authorization: Bearer $TOKEN" | jq .
```

### Test de Envío

```bash
curl -X POST "https://graph.facebook.com/v18.0/1031061770070997/messages" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "messaging_product": "whatsapp",
    "to": "34698189848",
    "type": "text",
    "text": {
      "body": "Test desde API"
    }
  }'
```

---

## 📚 Documentación Relacionada

- **Migración de Webhook:** `/root/NATALIA-DOCS/WHATSAPP-WEBHOOK-MIGRACION-2026-02-03.md`
- **Fix de Sesiones:** `/root/NATALIA-DOCS/NATALIA-PROBLEMA-SESIONES-SIN-TELEFONO-2026-02-03.md`
- **Solución WhatsApp:** `/root/NATALIA-DOCS/SOLUCION-WHATSAPP-AGREGAR-USER-PHONE-2026-02-03.md`

---

**Última actualización:** 3 de febrero de 2026 15:35 UTC
**Mantenedor:** Julio Brasa / Claude Code
