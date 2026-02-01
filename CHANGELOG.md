# Changelog - Sistema Natalia

## [1.1.0] - 2026-02-01

### Added
- **6 nuevas imágenes de amenidades de Salado**
  - Piscinas principales (4 vistas diferentes)
  - Fachada frontal calle Punta Cana
  - Vista combinada piscina y fachada
  - Optimizadas: ~350KB cada una, 1920px ancho
  - URLs: `http://194.41.119.21:9001/salado-amenidad-[1-6].jpg`

- **Detección automática de amenidades**
  - Keywords nuevas: `amenidad|piscina|pool|fachada|facade|instalaciones|facilities`
  - Priorización de amenidades sobre exteriores cuando se solicitan
  - Integrado en `/root/natalia-whatsapp-bridge/server.js` líneas 90-104

### Changed
- Actualizado server.js con lógica específica para amenidades
- Las consultas sobre piscinas/amenidades ahora devuelven imágenes relevantes

### Technical Details
**Servidor de imágenes:**
- Ubicación: `194.41.119.21:/root/salado-images/`
- Servicio: Python SimpleHTTPServer puerto 9001
- Total de imágenes: 20 (14 exteriores + 6 amenidades)

**Procesamiento:**
- Origen: Google Drive folder (11TlvSBb1RdN14NPgdxj7Sa-A2Xv6Fi0H)
- Descargadas con gdown en entorno virtual
- Optimización: ImageMagick convert -resize 1920x -quality 85
- Reducción de tamaño: ~10MB → ~370KB (95%)

**Código modificado:**
```javascript
// server.js líneas 90-104
const amenidadesKeywords = /amenidad|piscina|pool|fachada|facade|instalaciones|facilities/i;
if (amenidadesKeywords.test(userMessage) && asksForPhotos) {
  const amenidadesUrls = [
    'http://194.41.119.21:9001/salado-amenidad-1.jpg',
    'http://194.41.119.21:9001/salado-amenidad-3.jpg',
    'http://194.41.119.21:9001/salado-amenidad-4.jpg',
    'http://194.41.119.21:9001/salado-amenidad-5.jpg',
    'http://194.41.119.21:9001/salado-amenidad-6.jpg',
    'http://194.41.119.21:9001/salado-amenidad-2.jpg'
  ];
  imageUrls = amenidadesUrls.concat(imageUrls.filter(url => !url.includes('amenidad')));
}
```

---

## [1.0.0] - 2026-02-01

### Added
- Sistema completo de envío automático de imágenes
- Integración WhatsApp Business API
- Bot de Telegram funcional
- Documentación completa (README, IMPLEMENTACION-TECNICA, TROUBLESHOOTING)
- Soporte para hasta 3 imágenes por respuesta
- Detección automática de solicitudes de fotos

### Features
- RAG (Retrieval-Augmented Generation) con Qdrant
- Búsqueda semántica de imágenes
- Contexto conversacional
- Respuestas con DeepSeek
- Timeout handling (35s cliente, 45s RAG)

### Infrastructure
- Natalia Bridge: 194.41.119.117:18790
- RAG Service: 194.41.119.21:9000
- Image Server: 194.41.119.21:9001
- WhatsApp Backend: panel.redservicio.net
