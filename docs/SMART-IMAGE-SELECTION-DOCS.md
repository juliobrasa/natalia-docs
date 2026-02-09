# 🎯 SISTEMA INTELIGENTE DE SELECCIÓN DE IMÁGENES

**Versión:** 2.0  
**Fecha:** 2026-02-04  
**Servidor:** Natalia WhatsApp Bridge

---

## 🆕 MEJORAS IMPLEMENTADAS

### Antes (Sistema Antiguo):
- ❌ Keywords demasiado amplias (piscina y fachada juntas)
- ❌ No distinguía contextos específicos
- ❌ Orden de prioridad no optimizado
- ❌ Enviaba imágenes incorrectas para el contexto

### Ahora (Sistema Nuevo):
- ✅ Detección precisa de contextos con regex específicas
- ✅ Catálogo de imágenes organizado por categoría
- ✅ Priorización basada en análisis visual real
- ✅ Manejo inteligente de contextos combinados
- ✅ Logging detallado de decisiones
- ✅ Integración con RAG mantenida

---

## 🔍 CONTEXTOS DETECTADOS

```javascript
const contextos = {
  piscina: /\b(piscina|pool|alberca|nadar|swim|jacuzzi)\b/i,
  fachada: /\b(fachada|facade|edificio|building|exterior|arquitectura)\b/i,
  playa: /\b(playa|beach|mar|sea|arena|sand|costa|shore|kayak|pier)\b/i,
  golf: /\b(golf|campo|course|green|hoyo|hole)\b/i,
  ubicacion: /\b(ubicación|location|donde|where|mapa|map|direccion|address|cerca|near)\b/i,
  amenidades: /\b(amenidad|amenities|facilities|instalaciones|servicios)\b/i
};
```

---

## 📸 CATÁLOGO DE IMÁGENES POR CATEGORÍA

### 🏊‍♂️ PISCINAS (7 imágenes)
**Orden de prioridad:**
1. ⭐ salado-piscina-8.jpg - Render realista moderno con cascadas
2. ⭐ salado-amenidad-1.jpg - Piscina laguna + fachada visible
3. salado-amenidad-6.jpg - Vista completa con gazebo
4. salado-amenidad-3.jpg - Piscina con gazebo central
5. salado-amenidad-4.jpg - Piscina tipo río orgánica
6. salado-amenidad-5.jpg - Piscina frontal + edificio
7. salado-piscina-7.jpg - Foto real (referencia otro resort)

### 🏢 FACHADAS/EDIFICIOS (2 imágenes)
**Orden de prioridad:**
1. ⭐ salado-amenidad-2.jpg - Fachada principal desde calle (SIN piscina)
2. ⭐ salado-edificio-1.jpg - Vista lateral con balcones amplios

### 🏖️ PLAYA (6 imágenes)
**Orden de prioridad:**
1. ⭐ salado-playa-2.jpg - Vista aérea espectacular
2. ⭐ salado-playa-1.jpg - Pier con kayak
3. ⭐ salado-playa-4.jpg - Palmera icónica
4. ⭐ salado-playa-3.jpg - Lifestyle (mujer saltando)
5. salado-playa-5.jpg - Playa activa con gente
6. salado-playa-6.jpg - Beach club relajado

### ⛳ GOLF (1 imagen)
1. ⭐ salado-golf-1.jpg - Campo de golf 9 hoyos (foto real)

### 🗺️ UBICACIÓN (2 imágenes)
1. ⭐ salado-ubicacion-2.jpg - Vista amplia con referencias
2. salado-ubicacion-1.jpg - Vista cercana

### 🎯 GENERAL (3 imágenes - cuando no hay contexto específico)
1. salado-piscina-8.jpg - Piscina moderna
2. salado-playa-2.jpg - Playa aérea
3. salado-golf-1.jpg - Campo golf

---

## 🧠 LÓGICA DE SELECCIÓN

### Caso 1: SOLO Piscina
**Input:** "Quiero ver la piscina"  
**Detectado:** piscina  
**Output:** 7 imágenes de piscinas (en orden de prioridad)

### Caso 2: SOLO Fachada
**Input:** "Muéstrame la fachada del edificio"  
**Detectado:** fachada  
**Output:** 2 imágenes de fachadas (SIN piscinas)

### Caso 3: Piscina + Fachada
**Input:** "Quiero ver la piscina y la fachada"  
**Detectado:** piscina, fachada  
**Output:** Solo piscinas que INCLUYEN fachada visible:
- salado-amenidad-1.jpg
- salado-amenidad-5.jpg
- salado-amenidad-6.jpg

### Caso 4: Playa
**Input:** "Tienes fotos de la playa?"  
**Detectado:** playa  
**Output:** 6 imágenes de playa (mejores primero)

### Caso 5: Golf
**Input:** "Muéstrame el campo de golf"  
**Detectado:** golf  
**Output:** 1 imagen del campo de golf

### Caso 6: Ubicación
**Input:** "Dónde está ubicado?"  
**Detectado:** ubicacion  
**Output:** 2 mapas satelitales

### Caso 7: General/Amenidades
**Input:** "Envíame fotos del resort"  
**Detectado:** general o amenidades  
**Output:** 3 imágenes representativas (piscina, playa, golf)

### Caso 8: Múltiples contextos
**Input:** "Quiero ver playa y golf"  
**Detectado:** playa, golf  
**Output:** Combina imágenes de playa + golf

---

## 📊 LOGGING Y MONITOREO

El sistema ahora muestra logs claros de sus decisiones:

```
[Image Selection] Contextos detectados: piscina
[Image Selection] Mostrando: piscinas prioritarias
```

```
[Image Selection] Contextos detectados: fachada
[Image Selection] Mostrando: solo fachadas/edificios
```

```
[Image Selection] Contextos detectados: piscina, fachada
[Image Selection] Mostrando: piscinas CON fachada visible
```

```
[Image Selection] Contextos detectados: general
[Image Selection] Mostrando: imágenes generales del resort
```

---

## 🔗 INTEGRACIÓN CON RAG

El sistema mantiene la integración con el servicio RAG:

```javascript
// Integrar con RAG si hay resultados
if (ragResponse.images && ragResponse.images.length > 0) {
  const ragImages = ragResponse.images.map(img => img.url || img);
  // Agregar imágenes del RAG que no estén ya en la lista
  ragImages.forEach(url => {
    if (!imageUrls.includes(url)) {
      imageUrls.push(url);
    }
  });
  console.log('[Image Selection] Integradas imágenes del RAG:', ragImages.length);
}
```

---

## ⚙️ CONFIGURACIÓN

### Límite de imágenes por mensaje:
```javascript
const imagesToSend = imageUrls.slice(0, 3);  // Máximo 3 imágenes
```

### Activación:
El sistema se activa cuando:
1. El mensaje contiene palabras de solicitud de fotos: `foto|photo|imagen|image|pic|mostrar|show|ver|see|enseñar|enviar`
2. O menciona "amenidades"

---

## 📝 EJEMPLOS DE USO

### Ejemplo 1: Cliente pregunta por piscina
```
Usuario: "Quiero ver fotos de la piscina"
Sistema detecta: piscina
Natalia envía: 3 mejores imágenes de piscinas
```

### Ejemplo 2: Cliente pregunta por fachada
```
Usuario: "Muéstrame la fachada"
Sistema detecta: fachada
Natalia envía: 2 imágenes de fachadas (sin piscinas)
```

### Ejemplo 3: Cliente pregunta por playa
```
Usuario: "Tienes fotos de la playa?"
Sistema detecta: playa
Natalia envía: 3 mejores fotos de playa
```

### Ejemplo 4: Cliente pregunta genérico
```
Usuario: "Envíame fotos del resort"
Sistema detecta: general
Natalia envía: Mix (piscina + playa + golf)
```

---

## 🎯 VENTAJAS DEL SISTEMA

| Característica | Beneficio |
|---------------|-----------|
| **Detección precisa** | No confunde fachada con piscina |
| **Priorización inteligente** | Las mejores imágenes primero |
| **Contexto combinado** | Maneja "piscina y fachada" correctamente |
| **Fallback robusto** | Siempre tiene respuesta apropiada |
| **Logging claro** | Fácil debugging |
| **Integración RAG** | Combina con búsqueda semántica |
| **Basado en análisis real** | Selección fundamentada visualmente |

---

## 📂 ARCHIVOS RELACIONADOS

1. `/root/natalia-whatsapp-bridge/server.js` - Código actualizado
2. `/root/natalia-whatsapp-bridge/server.js.backup-before-smart-images` - Backup del código anterior
3. `/root/natalia-whatsapp-bridge/CATALOGO-IMAGENES-SALADO.md` - Análisis visual de todas las imágenes
4. `/root/natalia-whatsapp-bridge/SMART-IMAGE-SELECTION-DOCS.md` - Este documento

---

## 🔄 MANTENIMIENTO

### Para agregar nuevas imágenes:
1. Subir imagen al servidor de imágenes (http://194.41.119.21:9001/)
2. Agregar URL al catálogo correspondiente en `imagenesPorCategoria`
3. Reiniciar servicio: `systemctl restart natalia-whatsapp`

### Para ajustar prioridades:
1. Editar el orden en el array de cada categoría
2. Las primeras posiciones tienen mayor prioridad
3. Reiniciar servicio

### Para agregar nuevos contextos:
1. Agregar regex en el objeto `contextos`
2. Crear array en `imagenesPorCategoria`
3. Agregar lógica de detección en el bloque de selección
4. Reiniciar servicio

---

**Documento generado:** 2026-02-04  
**Sistema:** Natalia WhatsApp Bridge v2.0  
**Autor:** Claude Code
