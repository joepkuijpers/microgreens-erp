#!/bin/bash
# Sync script voor kDrive
# Zorg dat je de kDrive client hebt geïnstalleerd en ingelogd op de Pi

DB_SOURCE=\"/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite\"
KDRIVE_PATH=\"/home/joep/kDrive/MicrogreensBackup\" # Pas dit aan naar jouw kDrive pad
BACKUP_NAME=\"ERP_Backup_$(date +%Y%m%d_%H%M%S).sqlite\"

echo \"Start backup...\"
cp \"$DB_SOURCE\" \"$KDRIVE_PATH/$BACKUP_NAME\"

if [ $? -eq 0 ]; then
    echo \"✅ Backup succesvol naar kDrive: $BACKUP_NAME\"
    # Optioneel: verwijder backups ouder dan 30 dagen
    find \"$KDRIVE_PATH\" -name \"ERP_Backup_*.sqlite\" -mtime +30 -delete
else
    echo \"❌ Fout bij backup naar kDrive\"
fi
EOF
