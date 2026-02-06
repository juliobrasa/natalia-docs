#!/bin/bash
# Herramientas de administración de sesiones

SESSIONS_FILE="/var/lib/natalia-whatsapp/sessions.json"

case "$1" in
  list)
    echo "📋 SESIONES ACTIVAS"
    echo "════════════════════════════════════════"
    jq -r '.[] | "📱 " + .[0] + ": " + (.[1].messages | length | tostring) + " mensajes"' "$SESSIONS_FILE" 2>/dev/null || echo "No hay sesiones guardadas"
    ;;
    
  show)
    if [ -z "$2" ]; then
      echo "❌ Uso: $0 show <número_teléfono>"
      exit 1
    fi
    echo "💬 CONVERSACIÓN: $2"
    echo "════════════════════════════════════════"
    jq -r ".[] | select(.[0] == \"$2\") | .[1].messages[] | \"[\(.role | ascii_upcase)]: \(.content | .[0:150])\"" "$SESSIONS_FILE" 2>/dev/null
    ;;
    
  stats)
    echo "📊 ESTADÍSTICAS DE SESIONES"
    echo "════════════════════════════════════════"
    echo "Total sesiones: $(jq '. | length' "$SESSIONS_FILE" 2>/dev/null || echo 0)"
    echo "Total mensajes: $(jq '[.[] | .[1].messages | length] | add' "$SESSIONS_FILE" 2>/dev/null || echo 0)"
    echo "Archivo: $SESSIONS_FILE"
    echo "Tamaño: $(ls -lh "$SESSIONS_FILE" 2>/dev/null | awk '{print $5}' || echo "N/A")"
    ;;
    
  backup)
    BACKUP="/var/lib/natalia-whatsapp/sessions-backup-$(date +%Y%m%d-%H%M%S).json"
    cp "$SESSIONS_FILE" "$BACKUP" 2>/dev/null && echo "✅ Backup creado: $BACKUP" || echo "❌ Error al crear backup"
    ;;
    
  *)
    echo "Uso: $0 {list|show <phone>|stats|backup}"
    echo ""
    echo "Comandos:"
    echo "  list          - Lista todas las sesiones activas"
    echo "  show <phone>  - Muestra conversación de un teléfono"
    echo "  stats         - Muestra estadísticas generales"
    echo "  backup        - Crea backup del archivo de sesiones"
    ;;
esac
