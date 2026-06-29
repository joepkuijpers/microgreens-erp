#!/usr/bin/env python3

import sqlite3
from datetime import datetime

temperature = 22.6
humidity = 58.0
pressure = 1014.0
light = 8450.0

db = sqlite3.connect("/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite")

db.execute("""
INSERT INTO sensor_log
(timestamp, temperature, humidity, pressure, light)
VALUES (?, ?, ?, ?, ?)
""", (
    datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
    temperature,
    humidity,
    pressure,
    light
))

db.commit()
db.close()

print("✅ Sensorwaarden opgeslagen")
