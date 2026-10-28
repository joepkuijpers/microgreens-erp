#!/usr/bin/env python3

from datetime import datetime
from bh1750 import read_lux
from logger import save_sensor_data

# Tijdelijke waardes totdat de BME280 is aangesloten
temperature = 22.6
humidity = 58.0
pressure = 1014.0

# BH1750 uitlezen
light = read_lux()

# Tijdstempel
timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

# Opslaan in database
save_sensor_data(
    timestamp,
    temperature,
    humidity,
    pressure,
    light
)

print("✅ Sensorwaarden opgeslagen")