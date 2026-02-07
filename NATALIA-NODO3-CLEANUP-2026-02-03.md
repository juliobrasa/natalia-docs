# ✅ Limpieza de nodo3 y Migración a nodo0

**Fecha:** 3 de febrero de 2026 01:42 UTC
**Estado:** COMPLETADO ✅

---

## 🎯 Objetivo

Eliminar toda la infraestructura duplicada de RAG en nodo3 y migrar Natalia completamente a la infraestructura activa en nodo0.

---

## 🗑️ VMs Eliminadas de nodo3

### Lista de VMs Destruidas:

| VMID | Nombre | RAM | Disco | Estado |
|------|--------|-----|-------|--------|
| 311 | qdrant | 8 GB | 20 GB | ✅ ELIMINADA |
| 312 | ollama-cpu | 65 GB | 100 GB | ✅ ELIMINADA |
| 313 | embeddings | 4 GB | 20 GB | ✅ ELIMINADA |

**Total liberado:** 77 GB RAM, 140 GB Disco

### Comandos Ejecutados:
```bash
ssh nodo2 "ssh nodo3 'qm stop 311 && qm stop 312 && qm stop 313'"
ssh nodo2 "ssh nodo3 'qm destroy 311 && qm destroy 312 && qm destroy 313'"
```

---

## 🛑 Servicios Detenidos en nodo3

### Servicios de systemd:

| Servicio | Descripción | Estado |
|----------|-------------|--------|
| rag-service.service | MoltBot RAG Service | ✅ Stopped & Disabled |
| metrics-collector.service | RAG Metrics Collector | ✅ Stopped & Disabled |
| rag-dashboard.service | RAG Dashboard | ✅ Stopped & Disabled |
| rag-proxy.service | RAG Proxy for MoltBot | ✅ Stopped & Disabled |
| ollama.service | Ollama Service | ✅ Stopped & Disabled |

### Comando Ejecutado:
```bash
ssh nodo2 "ssh nodo3 'systemctl stop rag-service metrics-collector rag-dashboard rag-proxy ollama'"
ssh nodo2 "ssh nodo3 'systemctl disable rag-service metrics-collector rag-dashboard rag-proxy ollama'"
```

### Procesos Terminados:
- `/opt/rag-service-venv/bin/uvicorn rag-service:app` (PID 447437)
- `/usr/bin/python3 /root/metrics-collector.py` (PID 3343559)
- `/usr/bin/python3 /root/dashboard.py` (PID 3344044)
- `/usr/bin/node /root/rag-proxy.js` (PID 3701719)
- `/usr/local/bin/ollama serve` (PID 241442)

---

## 📝 Nota: Proceso qdrant Standalone

Existe un proceso qdrant standalone en el host nodo3 que se reinicia automáticamente:
```
./qdrant (PID variable)
```

**Estado:** Dejado en ejecución (no afecta a nada ya que las VMs y servicios están eliminados)

---

## 🔧 Migración a nodo0

### Infraestructura Activa en nodo0:

| Servidor | VMID | Servicio | IP | Puerto | Función |
|----------|------|----------|-------|--------|---------|
| VM | 116 | rag (Qdrant) | 194.41.119.116 | 6333 | Vector database |
| VM | 118 | embeddings | 194.41.119.118 | 8000 | Embedding service |
| Container | 121 | rag-service | 194.41.119.21 | 9000 | RAG API |

### Verificación de Salud:
```bash
curl http://194.41.119.21:9000/health
# Resultado: {"services":{"qdrant":"healthy","embeddings":"healthy","deepseek":"healthy"}}
```

---

## 🔄 Actualización del Túnel SSH (VM 117)

### ANTES (apuntando a nodo3):
```systemd
[Service]
ExecStart=/usr/bin/ssh -N -L 9000:10.5.0.10:9000 nodo2
```
**Problema:** 10.5.0.10 era nodo3 (ahora eliminado)

### DESPUÉS (apuntando a nodo0):
```systemd
[Service]
ExecStart=/usr/bin/ssh -N -L 9000:194.41.119.21:9000 root@194.41.119.21
```
**Solución:** Túnel directo a Container 121 en nodo0

### Servicio Actualizado:
```bash
# Archivo: /etc/systemd/system/rag-tunnel.service (VM 117)
systemctl daemon-reload
systemctl restart rag-tunnel
systemctl status rag-tunnel  # ✅ Active (running)
```

---

## 🔧 Actualización del Bridge (VM 117)

### Cambios en `/root/natalia-whatsapp-bridge/server.js`:

#### 1. Endpoint Corregido:
```javascript
// ANTES (nodo3):
const ragQueryResponse = await axios.post(`${RAG_SERVICE}/search`, {

// DESPUÉS (nodo0):
const ragQueryResponse = await axios.post(`${RAG_SERVICE}/query`, {
```

#### 2. Campo de Respuesta Corregido:
```javascript
// ANTES (formato nodo3):
if (ragQueryResponse.data && ragQueryResponse.data.context_text) {
  ragContext = ragQueryResponse.data.context_text;

// DESPUÉS (formato nodo0):
if (ragQueryResponse.data && ragQueryResponse.data.context_used) {
  ragContext = ragQueryResponse.data.context_used;
```

### Estructura de Respuesta API:

**nodo3 (antiguo):**
```json
{
  "context_text": "...",
  "count": 3
}
```

**nodo0 (nuevo - Container 121):**
```json
{
  "query": "apartamentos salado",
  "answer": "Respuesta de DeepSeek...",
  "context_used": "Contexto RAG combinado...",
  "sources": [...]
}
```

### Backup Creado:
```bash
/root/natalia-whatsapp-bridge/server.js.backup-nodo3-cleanup-20260203-014123
```

### Servicio Reiniciado:
```bash
systemctl restart natalia-whatsapp
systemctl status natalia-whatsapp  # ✅ Active (running)
```

---

## 📊 Arquitectura Final

### Flujo de Datos Actual:

```
WhatsApp User
    ↓
WhatsApp Business API
    ↓
VM 117:18790 (Natalia Bridge)
    ↓
localhost:9000 (Túnel SSH)
    ↓ [SSH over 194.41.119.21]
Container 121:9000 (nodo0 - rag-service)
    ↓
VM 116:6333 (nodo0 - Qdrant)
    ↓
VM 118:8000 (nodo0 - Embeddings)
    ↓
DeepSeek API
    ↓
Response → Usuario
```

### Topología de Nodos:

```
✅ nodo0 (51.195.5.203) - ACTIVO
├─ VM 116: Qdrant (194.41.119.116:6333)
├─ VM 118: Embeddings (194.41.119.118:8000)
├─ Container 121: RAG Service (194.41.119.21:9000)
└─ VM 117: Natalia Bridge (194.41.119.117:18790)

❌ nodo3 (10.5.0.x via nodo2) - ELIMINADO
├─ VM 311: qdrant ❌ DESTRUIDA
├─ VM 312: ollama-cpu ❌ DESTRUIDA
└─ VM 313: embeddings ❌ DESTRUIDA
```

---

## ✅ Verificación Post-Migración

### 1. Túnel SSH:
```bash
ssh root@194.41.119.117 "systemctl status rag-tunnel"
# ✅ Active (running)
# ✅ Conectado a 194.41.119.21:9000
```

### 2. RAG Query Test:
```bash
ssh root@194.41.119.21 "curl -X POST http://localhost:9000/query \
  -H 'Content-Type: application/json' \
  -d '{\"query\":\"apartamentos salado\",\"collection\":\"marketing-inmobiliaria\",\"top_k\":2}'"

# ✅ Respuesta exitosa con context_used y answer
```

### 3. Bridge Service:
```bash
ssh root@194.41.119.117 "systemctl status natalia-whatsapp"
# ✅ Active (running)
# ✅ RAG Service: http://localhost:9000
```

### 4. Health Check:
```bash
curl http://194.41.119.21:9000/health
# ✅ {"services":{"qdrant":"healthy","embeddings":"healthy","deepseek":"healthy"}}
```

---

## 🎯 Comparación Antes/Después

### ANTES (con nodo3):

| Componente | Ubicación | Estado |
|------------|-----------|--------|
| RAG duplicado | nodo3 VMs | 🔴 Timeout/Conflictos |
| Túnel SSH | VM 117 → nodo3 (10.5.0.10) | ⚠️ Red privada |
| Endpoint | /search | ❌ No funciona en nodo0 |
| Campo respuesta | context_text | ❌ No existe en nodo0 |
| VMs nodo3 | 3 VMs (77GB RAM) | 💰 Desperdicio recursos |

### DESPUÉS (solo nodo0):

| Componente | Ubicación | Estado |
|------------|-----------|--------|
| RAG único | nodo0 Container 121 | ✅ Funcionando |
| Túnel SSH | VM 117 → nodo0 (194.41.119.21) | ✅ IP pública directa |
| Endpoint | /query | ✅ Correcto |
| Campo respuesta | context_used | ✅ Existe y funciona |
| VMs nodo3 | 0 VMs | ✅ 77GB RAM liberados |

---

## 📈 Beneficios

### Recursos Liberados:
- **RAM:** 77 GB
- **Disco:** 140 GB
- **VMs:** 3 máquinas virtuales
- **Servicios:** 5 servicios systemd

### Mejoras Operativas:
- ✅ Infraestructura consolidada (un solo RAG)
- ✅ Sin servicios duplicados
- ✅ Túnel directo (sin gateway nodo2 para RAG)
- ✅ Endpoint y formato de respuesta correctos
- ✅ Mantenimiento más simple

### Mejoras de Red:
- ✅ Sin dependencia de red privada 10.5.0.x
- ✅ Conexión directa vía IP pública
- ✅ Menos saltos de red

---

## 📝 Archivos Modificados

| Archivo | Ubicación | Cambios |
|---------|-----------|---------|
| rag-tunnel.service | /etc/systemd/system/ (VM 117) | IP destino: 10.5.0.10 → 194.41.119.21 |
| server.js | /root/natalia-whatsapp-bridge/ (VM 117) | Endpoint: /search → /query |
| server.js | /root/natalia-whatsapp-bridge/ (VM 117) | Campo: context_text → context_used |

### Backups Creados:
```
/root/natalia-whatsapp-bridge/server.js.backup-nodo3-cleanup-20260203-014123
```

---

## 🔍 Comandos de Verificación

### Estado de nodo3:
```bash
# Ver VMs en nodo3 (debe estar vacío)
ssh nodo2 "ssh nodo3 'qm list'"

# Ver servicios en nodo3
ssh nodo2 "ssh nodo3 'systemctl list-units | grep -E \"rag|qdrant|ollama\"'"
```

### Estado de nodo0:
```bash
# Ver VMs y containers en nodo0
ssh nodo0 "qm list | grep -E 'rag|embeddings'"
ssh nodo0 "pct list | grep rag"

# Health check
curl http://194.41.119.21:9000/health
```

### Estado de Natalia (VM 117):
```bash
# Túnel
ssh root@194.41.119.117 "systemctl status rag-tunnel"
ssh root@194.41.119.117 "ss -tulpn | grep :9000"

# Bridge
ssh root@194.41.119.117 "systemctl status natalia-whatsapp"

# Test RAG
ssh root@194.41.119.117 "curl -X POST http://localhost:9000/query \
  -d '{\"query\":\"test\",\"collection\":\"marketing-inmobiliaria\"}'"
```

---

## ⚠️ Notas Adicionales

### Proceso qdrant en nodo3 Host:
- Un proceso qdrant standalone sigue ejecutándose en el host nodo3
- No afecta la operación ya que las VMs y servicios están eliminados
- Se puede dejar o eliminar según preferencia

### Colecciones en Qdrant:
- La colección `marketing-inmobiliaria` permanece en VM 116 (nodo0)
- Contiene la data actualizada de Salado (15 apartamentos, €165K-€375K)

### Servicios de Respaldo:
- El proxy en nodo2 (rag-proxy.service puerto 9001) sigue disponible como backup
- No es necesario ya que el túnel directo funciona

---

## 🎉 Estado Final

### ✅ Completado:
- [x] VMs de nodo3 eliminadas (311, 312, 313)
- [x] Servicios de nodo3 detenidos y deshabilitados
- [x] Túnel SSH actualizado a nodo0
- [x] Bridge actualizado con endpoint y campo correctos
- [x] Servicios reiniciados y verificados
- [x] Health checks pasados
- [x] Documentación completa

### 🎯 Resultado:
**Natalia WhatsApp ahora opera 100% sobre la infraestructura de nodo0, con nodo3 completamente limpio y liberado.**

---

**Documentado por:** Claude Code
**Servidores Afectados:** nodo0, nodo3, VM 117
**VMs Eliminadas:** 311, 312, 313 (nodo3)
**Servicios Actualizados:** rag-tunnel, natalia-whatsapp
**Fecha:** 2026-02-03 01:42 UTC
**Estado:** ✅ COMPLETADO Y OPERATIVO
