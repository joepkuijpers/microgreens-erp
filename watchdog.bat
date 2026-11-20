@echo off
cd /d "C:\Users\joepk\Downloads\microgreens-erp"
php app\check_climate_watchdog.php >> storage\watchdog_cron.log 2>&1