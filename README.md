# Natalia - Universo Salado

**Version:** 2.0.0  
**Fecha:** 2026-02-08  
**Estado:** Produccion

## Descripcion

Natalia es la coordinadora inteligente de **Universo Salado** - un complejo de tres urbanizaciones exclusivas en White Sands, Bavaro, Punta Cana:

- **Salado 1** - Primera fase, en venta. 3 bloques (A, B, C), 5 tipologias, desde EUR165,000
- **Salado 2** - Segunda fase, en diseno. Parcela contigua a Salado 1
- **Salado 3** - Tercera fase, en desarrollo. 22 renders preliminares disponibles

Natalia gestiona WhatsApp, Telegram, web chat, y un panel de contenido para redes sociales.

## Componentes

### 1. WhatsApp Bridge (`server.js`)
- **Puerto:** 18790
- **Numero:** +34 685 80 59 24
- **Motor IA:** DeepSeek API
- **RAG:** Qdrant Vector DB (localhost:9000)
- Rate limiting, sesiones persistentes (SQLite), envio de imagenes automatico

### 2. Panel de Contenido (`salado-panel/`)
- **URL:** https://panel.saladoresort.com/admin
- **Stack:** Laravel 11 + Filament PHP 3 + MariaDB
- **Funcionalidades:**
  - CRUD de Posts, Campanas, Proyectos
  - Dashboard con widgets de estadisticas
  - Generador de contenido con IA (DeepSeek)
  - Soporte multi-proyecto (Salado 1, 2, 3)
  - 30 posts pre-generados para calendario de contenido
  - Landing page en panel.saladoresort.com

### 3. Clips de Video (`clips-voz/`)
- **URL:** https://panel.saladoresort.com/clips.html
- 5 clips con locucion (es-DO, Ramona) + musica tropical
- Formatos: Reels (9:16), Stories (9:16), Posts (1:1), Facebook

### 4. Telegram Bot
- **Bot:** @Natalia_jefa_bot
- **Servicio:** telegram-natalia.service

### 5. MoltBot Gateway
- **Puerto:** 3100
- **Web Chat:** https://natalia.soporteclientes.net/chat

## Servicios en natalia (194.41.119.117)

| Servicio | Puerto | systemd |
|----------|--------|---------|
| Natalia WhatsApp Bridge | 18790 | natalia-whatsapp.service |
| MoltBot Gateway | 3100 | moltbot.service |
| Telegram Bot | - | telegram-natalia.service |
| WhatsApp Webhook | - | whatsapp-webhook.service |
| RAG Service (Qdrant) | 9000 | rag-service |
| PHP-FPM 8.2 | sock | php8.2-fpm.service |
| MariaDB | 3306 | mariadb.service |
| Nginx | 80/443 | nginx.service |

## Media disponible

### Salado 1
- 3 renders (fachada dia/noche, vista frontal Bavaro)
- 30 posts con imagenes para calendario de contenido
- Video inicio de obras (5.3MB)
- Video promocional (71MB, 1080p)

### Salado 3
- 22 renders preliminares (Escenas 3D, 673MB total)

### Universo Salado (compartido)
- 3 videos playa White Sands (5.4MB + 9.8MB + 458MB 4K)
- 5 clips con locucion para redes sociales
- Plano urbanistico Salado 1 + Salado 2
- Vista aerea Google Earth

### URLs de media
- Imagenes: https://natalia.soporteclientes.net/images/
- Salado 3: https://natalia.soporteclientes.net/images/salado3/
- Clips sin audio: https://natalia.soporteclientes.net/images/clips/
- Clips con musica: https://natalia.soporteclientes.net/images/clips-music/
- Clips con voz: https://natalia.soporteclientes.net/images/clips-voz/

## Estructura del repositorio

```
natalia-docs/
├── server.js                    # WhatsApp Bridge (produccion)
├── package.json                 # Dependencias Node.js
├── sessions-admin.sh            # Herramienta admin de sesiones
├── README.md                    # Este archivo
├── salado-panel/                # Panel Laravel (archivos clave)
│   ├── app/
│   │   ├── Filament/
│   │   │   ├── Resources/       # PostResource, CampaignResource, ProjectResource
│   │   │   ├── Pages/           # AiGenerator
│   │   │   └── Widgets/         # Stats, Chart, LatestPosts
│   │   └── Models/              # Post, PostImage, Campaign, Project, Setting
│   ├── database/
│   │   ├── migrations/          # Tablas: posts, post_images, campaigns, settings, projects
│   │   └── seeders/             # ContentCalendarSeeder, ProjectSeeder
│   ├── resources/views/
│   │   ├── welcome.blade.php    # Landing page
│   │   └── filament/pages/      # Vista AI Generator
│   ├── clips.html               # Preview de video clips
│   └── nginx-panel.saladoresort.com.conf
└── docs/                        # Documentacion historica
    ├── AMENIDADES.md
    ├── CATALOGO-IMAGENES-SALADO.md
    ├── IMAGENES-COMERCIALES.md
    └── SIMULACION-VACACIONAL.md
```

## Reiniciar servicios

```bash
# WhatsApp Bridge
systemctl restart natalia-whatsapp

# Telegram
systemctl restart telegram-natalia

# MoltBot
systemctl restart moltbot

# Panel Laravel
cd /var/www/salado-panel && php artisan optimize:clear && php artisan optimize

# Nginx
systemctl reload nginx
```

## Test rapido

```bash
# Test WhatsApp Bridge
curl -X POST http://localhost:18790/api/chat \
  -H "Content-Type: application/json" \
  -d {messages:[{role:user,content:Que es Universo Salado?}]}

# Test RAG
curl -X POST http://localhost:9000/query \
  -H "Content-Type: application/json" \
  -d {text:apartamentos salado,top_k:3}

# Test Panel
curl -s https://panel.saladoresort.com/admin | head -5
```

---
**Ultima actualizacion:** 2026-02-08
