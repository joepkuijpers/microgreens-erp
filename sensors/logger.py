import sqlite3

DB = "/var/www/html/microgreens/PHP/database/MicrogreensERP_Live.sqlite"

def save_sensor_data(timestamp, temperature, humidity, pressure, light):
    conn = sqlite3.connect(DB)
    conn.execute("""
    INSERT INTO sensor_log
    (timestamp, temperature, humidity, pressure, light)
    VALUES (?, ?, ?, ?, ?)
    """, (timestamp, temperature, humidity, pressure, light))
    conn.commit()
    conn.close()