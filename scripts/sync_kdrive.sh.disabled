#!/bin/bash
echo "🚀 Start georganiseerde backup naar kDrive..."

# 1. Bron en Basis Pad
DB_SOURCE="/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite"
BASE_PATH="/home/joep/kDrive/MicrogreensBackup"

# 2. Datum onderdelen voor de mapstructuur
YEAR=$(date +%Y)
MONTH_NUM=$(date +%m)
MONTH_NAME=$(date +%B)
DAY=$(date +%d)
TIMESTAMP=$(date +%H%M%S)

# 3. Bouw het volledige pad: .../2026/09_September/25/
TARGET_DIR="$BASE_PATH/$YEAR/$MONTH_NUM_$MONTH_NAME/$DAY"
BACKUP_FILE="ERP_Backup_${YEAR}${MONTH_NUM}${DAY}_${TIMESTAMP}.sqlite"

# 4. Controleer database
if [ ! -f "$DB_SOURCE" ]; then
    echo "❌ Fout: Database niet gevonden op $DB_SOURCE"
    exit 1
fi

# 5. Maak de geneste mappenstructuur aan
echo "📂 Controleer mapstructuur: $TARGET_DIR"
mkdir -p "$TARGET_DIR"
chown -R joep:joep "$BASE_PATH"

# 6. Kopieer de backup
echo "💾 Kopieer database..."
cp "$DB_SOURCE" "$TARGET_DIR/$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✅ Succes! Backup opgeslagen in: $TARGET_DIR/$BACKUP_FILE"
    
    # 7. OPRUIMEN UITGESCHAKELD
    # We bewaren alle data voor onbeperkte traceability.
    echo "💾 Alle backups worden bewaard (geen automatische verwijdering)."
    echo "📦 Totale aantal backups in archief: $(find "$BASE_PATH" -name "*.sqlite" | wc -l)"
else
    echo "❌ Fout bij het kopiëren!"
    exit 1
fi
