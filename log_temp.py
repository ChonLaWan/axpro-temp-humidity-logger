# log_temp.py
from hikaxpro import HikAxPro
import mysql.connector
from datetime import datetime

# อ่านจาก AX PRO
axpro = HikAxPro(host="192.168.5.55", username="admin", password="password")
zones = axpro.zone_status()

# เชื่อมต่อ MariaDB
conn = mysql.connector.connect(
    host="localhost",
    user="db_user",
    password="password",
    database="axpro_data"
)
cursor = conn.cursor()

# บันทึกข้อมูลลง sensor_log
for z in zones['ZoneList']:
    zone = z['Zone']
    cursor.execute("""
        INSERT INTO sensor_log (device_name, zone_id, zone_name, temperature, humidity, recorded_at)
        VALUES (%s, %s, %s, %s, %s, %s)
    """, (
        "AXPRO01", zone['id'], zone['name'],
        zone.get('temperature', None),
        zone.get('humidity', None),
        datetime.now()
    ))

conn.commit()
cursor.close()
conn.close()
