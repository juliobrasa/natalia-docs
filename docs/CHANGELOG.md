# Changelog - Sistema Natalia

## [1.2.1] - 2026-02-01

### Added
- **Documentación financiera completa de rentabilidad vacacional**
  - Nuevo archivo: `SIMULACION-VACACIONAL.md`
  - Análisis detallado de 61 apartamentos
  - Proyecciones mensuales y anuales de ingresos
  - ROI estimado por tipología (8.42% - 17.73%)
  - Margen de explotación total: $1,661,110.62/año

- **Información financiera estructurada**
  - 4 tipologías de apartamentos con métricas detalladas
  - Análisis estacional (alta, media, baja temporada)
  - Estructura de costes completa (operación, fees, garantías)
  - Escenarios de rendimiento (optimista, base, conservador)
  - Comparativas y recomendaciones de inversión

### Changed
- README.md actualizado con enlace a simulación vacacional
- Natalia ahora puede responder consultas sobre rentabilidad e inversión

### Technical Details
**Fuente de datos:**
- Google Sheets: Simulación Vacacional Salado Resort
- Sheet ID: 11-g_lRxoadl0ootxioa0IOFxHpHsxAhM
- Formato: Excel (.xlsx) → Markdown documentado

**Métricas clave documentadas:**
- ADR (Average Daily Rate): $125.83 promedio
- Ocupación promedio: 67.25%
- RevPAR por mes y temporada
- Room Nights (RN) mensuales
- Costes operativos: 18% de ingresos brutos

**Uso en producción:**
Natalia puede ahora responder preguntas sobre:
- "¿Cuál es la rentabilidad de Salado Resort?"
- "¿Qué ROI tienen los apartamentos?"
- "¿Cuánto genera un apartamento de 1 habitación?"
- "¿Cuál es la mejor inversión en Salado?"

---

## [1.2.0] - 2026-02-01

### Added
- **12 nuevas imágenes de presentación comercial Salado**
  - 6 imágenes de playa (pier, vistas aéreas, lifestyle)
  - 2 mapas de ubicación (aéreas cercana y amplia)
  - 1 imagen de campo de golf
  - 2 imágenes de piscina adicionales (agregadas a amenidades)
  - 1 render de edificio/fachada moderna
  - Total optimizado: ~7.6MB (12 imágenes de alta calidad)
  - URLs: `http://194.41.119.21:9001/salado-{categoria}-{N}.jpg`

- **5 categorías de detección automática**
  - **Playa:** playa|beach|mar|sea|arena|sand|costa|shore
  - **Ubicación:** ubicacion|location|mapa|map|donde|where|aerial|aereo
  - **Golf:** golf|campo|course|green|hoyo|hole
  - **Edificio:** edificio|building|apartamento|apartment|unidad|unit
  - **Amenidades:** expandida con 2 piscinas comerciales adicionales

- **Documentación completa**
  - Nuevo archivo: `IMAGENES-COMERCIALES.md` con catálogo detallado
  - 12 imágenes documentadas con descripciones y keywords
  - Proceso completo de extracción desde presentación PDF
  - Guías de mantenimiento y testing

### Changed
- Actualizado `server.js` con lógica multi-categoría (líneas 90-156)
- Array `amenidadesUrls` expandido de 6 a 8 imágenes
- Total de imágenes disponibles: 20 → 32 (incremento 60%)
- README.md actualizado con nuevas categorías y keywords
- Versión del sistema: 1.1.0 → 1.2.0

### Technical Details
**Fuente de imágenes:**
- Presentación comercial Google Slides (42 páginas)
- Extraídas 195 imágenes PNG, seleccionadas 12 mejores
- Criterio: tamaño >1MB, contenido relevante, diversidad

**Procesamiento:**
- Herramienta: pdfimages para extracción, ImageMagick para optimización
- Optimización: -resize 1920x -quality 85
- Reducción promedio: ~85% de tamaño original
- Formatos: PNG → JPG

**Código modificado:**
```javascript
// server.js - Nuevas categorías agregadas
// Playa (líneas 107-118)
// Ubicación (líneas 120-128)
// Golf (líneas 130-138)
// Edificio (líneas 140-148)
// Amenidades actualizada con piscinas 7 y 8 (líneas 97-98)
```

**Deployment:**
```bash
# Imágenes subidas a 194.41.119.21:/root/salado-images/
# Servicio reiniciado: systemctl restart natalia-whatsapp
# Status: ✅ Active (running) desde 2026-02-01 21:13:43 UTC
```

---

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
