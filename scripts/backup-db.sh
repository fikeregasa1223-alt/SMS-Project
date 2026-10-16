#!/usr/bin/env bash
# ============================================================
# SMS - Database Backup Script (Linux/macOS)
# ============================================================

set -euo pipefail

DB_NAME="sms-db"
DB_USER="root"
DB_PASS=""
DB_HOST="127.0.0.1"

BACKUP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/backups"
mkdir -p "$BACKUP_DIR"

STAMP="$(date +%Y-%m-%d_%H-%M-%S)"
OUTFILE="$BACKUP_DIR/${DB_NAME}_${STAMP}.sql"

echo "Backing up $DB_NAME to $OUTFILE..."

if [ -z "$DB_PASS" ]; then
    mysqldump -h "$DB_HOST" -u "$DB_USER" \
        --routines --triggers --single-transaction \
        --default-character-set=utf8mb4 "$DB_NAME" > "$OUTFILE"
else
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
        --routines --triggers --single-transaction \
        --default-character-set=utf8mb4 "$DB_NAME" > "$OUTFILE"
fi

echo "Backup complete: $OUTFILE"

# Delete backups older than 30 days
find "$BACKUP_DIR" -type f -name "*.sql" -mtime +30 -delete