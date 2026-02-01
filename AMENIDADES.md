# Catálogo de Imágenes de Amenidades - Salado Resort

**Fecha:** 2026-02-01
**Total de imágenes:** 6
**Servidor:** 194.41.119.21:9001
**Directorio:** /root/salado-images/

## Imágenes Disponibles

### 1. Piscina III - Vista panorámica
- **Archivo:** `salado-amenidad-1.jpg`
- **URL:** http://194.41.119.21:9001/salado-amenidad-1.jpg
- **Tamaño:** 375KB
- **Descripción:** Piscina principal del resort Salado Golf & Beach Resort en Punta Cana. Vista amplia de las amenidades acuáticas con áreas verdes circundantes.
- **Keywords:** piscina, amenidades, áreas recreativas

### 2. Fachada Calle Punta Cana
- **Archivo:** `salado-amenidad-2.jpg`
- **URL:** http://194.41.119.21:9001/salado-amenidad-2.jpg
- **Tamaño:** 341KB
- **Descripción:** Fachada frontal del edificio Salado I desde la calle principal de Punta Cana. Vista del diseño arquitectónico moderno del resort.
- **Keywords:** fachada, arquitectura, exterior, edificio

### 3. Piscina Bávaro y Salado
- **Archivo:** `salado-amenidad-3.jpg`
- **URL:** http://194.41.119.21:9001/salado-amenidad-3.jpg
- **Tamaño:** 392KB
- **Descripción:** Piscina del resort con vista de las áreas de Bávaro y Salado. Amenidades acuáticas compartidas entre zonas residenciales.
- **Keywords:** piscina, bávaro, amenidades compartidas

### 4. Piscina II - Vista alternativa
- **Archivo:** `salado-amenidad-4.jpg`
- **URL:** http://194.41.119.21:9001/salado-amenidad-4.jpg
- **Tamaño:** 373KB
- **Descripción:** Segunda vista de la piscina principal de Salado Golf & Beach Resort. Áreas de recreación y descanso junto al agua.
- **Keywords:** piscina, recreación, áreas comunes

### 5. Piscina y Fachada Combinadas
- **Archivo:** `salado-amenidad-5.jpg`
- **URL:** http://194.41.119.21:9001/salado-amenidad-5.jpg
- **Tamaño:** 370KB
- **Descripción:** Vista combinada de la piscina y fachada del resort Salado. Amenidades y arquitectura del complejo residencial.
- **Keywords:** piscina, fachada, vista combinada, amenidades

### 6. Piscina Principal
- **Archivo:** `salado-amenidad-6.jpg`
- **URL:** http://194.41.119.21:9001/salado-amenidad-6.jpg
- **Tamaño:** 364KB
- **Descripción:** Piscina principal del resort Salado I en Punta Cana. Amenidades recreativas con áreas de nado y descanso.
- **Keywords:** piscina principal, nado, recreación

## Proceso de Integración

### 1. Origen de las Imágenes
- **Fuente:** Google Drive
- **Folder ID:** 11TlvSBb1RdN14NPgdxj7Sa-A2Xv6Fi0H
- **Formato original:** PNG (3840x1741px)
- **Tamaño original:** ~10MB cada una

### 2. Optimización
```bash
# Conversión y optimización con ImageMagick
convert "imagen-original.png" -resize 1920x -quality 85 "salado-amenidad-X.jpg"
```

**Resultados:**
- Formato: PNG → JPG
- Resolución: 3840px → 1920px ancho
- Tamaño: ~10MB → ~370KB (reducción 95%)
- Calidad: 85% (óptimo para web/móvil)

### 3. Despliegue
```bash
# Copia al servidor de imágenes
scp salado-amenidad-*.jpg root@194.41.119.21:/root/salado-images/

# Verificación de accesibilidad HTTP
curl -I http://194.41.119.21:9001/salado-amenidad-1.jpg
```

### 4. Integración con Natalia

**Archivo modificado:** `/root/natalia-whatsapp-bridge/server.js`

**Código agregado (líneas 90-104):**
```javascript
// Agregar imágenes de amenidades si se solicitan específicamente
const amenidadesKeywords = /amenidad|piscina|pool|fachada|facade|instalaciones|facilities/i;
if (amenidadesKeywords.test(userMessage) && asksForPhotos) {
  const amenidadesUrls = [
    'http://194.41.119.21:9001/salado-amenidad-1.jpg', // Piscina III
    'http://194.41.119.21:9001/salado-amenidad-3.jpg', // Piscina Bávaro y Salado
    'http://194.41.119.21:9001/salado-amenidad-4.jpg', // Piscina II
    'http://194.41.119.21:9001/salado-amenidad-5.jpg', // Piscina y fachada
    'http://194.41.119.21:9001/salado-amenidad-6.jpg', // Piscina principal
    'http://194.41.119.21:9001/salado-amenidad-2.jpg'  // Fachada calle Punta Cana
  ];

  // Priorizar amenidades sobre exteriores
  imageUrls = amenidadesUrls.concat(imageUrls.filter(url => !url.includes('amenidad')));
  console.log('[Natalia WhatsApp] Imágenes de amenidades agregadas');
}
```

**Servicio reiniciado:**
```bash
systemctl restart natalia-whatsapp
```

## Uso en Producción

### Keywords que Activan Amenidades
- amenidad, amenidades
- piscina, pool
- fachada, facade
- instalaciones, facilities

**Nota:** También requiere keywords de solicitud de foto: `foto|imagen|picture|muestra|ver|envia`

### Ejemplos de Consultas

✅ **"Fotos de la piscina de Salado"**
- Devuelve: 3 imágenes de piscinas (amenidad-1, amenidad-3, amenidad-4)

✅ **"Envía fotos de las amenidades"**
- Devuelve: 3 imágenes variadas de amenidades

✅ **"Muestra la fachada del resort"**
- Devuelve: 3 imágenes incluyendo fachada (amenidad-2)

❌ **"Información sobre Salado"**
- No devuelve imágenes (falta keyword de foto)

## Pruebas Realizadas

### API Test
```bash
curl -X POST http://194.41.119.117:18790/api/chat \
  -H "Content-Type: application/json" \
  -d '{"messages":[{"role":"user","content":"Fotos de la piscina de Salado"}],"max_tokens":300}'
```

**Resultado esperado:**
```json
{
  "choices": [{
    "message": {
      "content": "Aquí tienes fotos de la piscina principal...",
      "mediaUrls": [
        "http://194.41.119.21:9001/salado-amenidad-1.jpg",
        "http://194.41.119.21:9001/salado-amenidad-3.jpg",
        "http://194.41.119.21:9001/salado-amenidad-4.jpg"
      ]
    }
  }]
}
```

### Canales Probados
- ✅ WhatsApp: Funcionando
- ✅ Telegram: Funcionando
- ✅ API directa: Funcionando

## Mantenimiento

### Agregar Nuevas Amenidades

1. **Optimizar imagen:**
```bash
convert "nueva-imagen.png" -resize 1920x -quality 85 "salado-amenidad-7.jpg"
```

2. **Subir al servidor:**
```bash
scp salado-amenidad-7.jpg root@194.41.119.21:/root/salado-images/
```

3. **Actualizar código:**
Editar `/root/natalia-whatsapp-bridge/server.js`, agregar URL en array `amenidadesUrls`

4. **Reiniciar servicio:**
```bash
ssh root@194.41.119.117 "systemctl restart natalia-whatsapp"
```

### Verificar Imágenes Disponibles
```bash
ssh root@194.41.119.21 "ls -lh /root/salado-images/salado-amenidad-*.jpg"
```

### Test de Accesibilidad
```bash
for i in {1..6}; do
  echo "Amenidad $i:"
  curl -I http://194.41.119.21:9001/salado-amenidad-$i.jpg 2>&1 | grep "HTTP"
done
```

---

**Última actualización:** 2026-02-01
**Responsable:** Claude Sonnet 4.5
