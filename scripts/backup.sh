#!/bin/bash
# EstrateGIA — Backup diario automático
BACKUP_DIR="/home/emilio/estrategia/backups"
DB_NAME="estrategia_v1"
DB_USER="emilio"
DB_PASS="s1gma"
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"
DATE=$(date +%Y%m%d_%H%M%S)
FILE="$BACKUP_DIR/estrategia_v1_$DATE.sql.gz"

mysqldump -u"$DB_USER" -p"$DB_PASS" --single-transaction --no-tablespaces "$DB_NAME" 2>/dev/null | gzip > "$FILE"

# Clean old backups
find "$BACKUP_DIR" -name "*.sql.gz" -mtime +$RETENTION_DAYS -delete

echo "Backup: $FILE ($(du -h "$FILE" | cut -f1))"
