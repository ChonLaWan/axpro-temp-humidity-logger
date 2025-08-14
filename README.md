**สรุปวิธีการทั้งหมด** สำหรับสร้างระบบดึงข้อมูลอุณหภูมิและความชื้นจากอุปกรณ์ **Hikvision AX PRO** และแสดงผลผ่าน **เว็บกราฟ (Graph) แบบ Real-time + ย้อนหลัง** โดยเก็บข้อมูลใน MariaDB และทำงานอัตโนมัติด้วย systemd timer บน Linux (AlmaLinux 8/9):

---

## 🔧 1. เตรียมระบบ Linux และ Python Environment

### ติดตั้ง Python + Virtualenv

```bash
dnf install -y python3 python3-pip git
python3 -m venv /root/venv
source /root/venv/bin/activate
```

### ติดตั้งไลบรารี `hikaxpro` และ `mysql-connector-python`

```bash
pip install hikaxpro mysql-connector-python
```

---

## 📦 2. โครงสร้างไฟล์ทั้งหมด

```
/root/
├── venv/                      # Python virtual environment
├── log_temp.py               # Python script ดึงและบันทึกข้อมูล
├── get_temp.py               # ทดสอบ get ข้อมูลจาก AX PRO
├── axpro-logger.service      # systemd service
├── axpro-logger.timer        # systemd timer
/var/www/html/axpro_web/
├── index.php                 # หน้าหลัก
├── get_data.php              # API ดึงข้อมูลจาก DB
├── style.css                 # CSS (ถ้ามี)
```

---

## 🐍 3. Python Script `/root/log_temp.py`

```python
from hikaxpro import HikAxPro
import mysql.connector

axpro = HikAxPro(host="192.168.5.55", username="admin", password="password")
zones = axpro.zone_status()

db = mysql.connector.connect(
    host="localhost",
    user="db_user",
    password="password",
    database="axpro_data"
)
cursor = db.cursor()

for zone in zones:
    if 'temperature' in zone and 'humidity' in zone:
        cursor.execute("""
            INSERT INTO sensor_log (device_name, zone_id, zone_name, temperature, humidity)
            VALUES (%s, %s, %s, %s, %s)
        """, (
            'AXPRO-1', zone['zoneId'], zone['zoneName'], zone['temperature'], zone['humidity']
        ))

db.commit()
cursor.close()
db.close()
```

---

## 🗃️ 4. ฐานข้อมูล MariaDB

### คำสั่งสร้างฐานข้อมูลและตาราง:

```sql
CREATE DATABASE axpro_data CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE axpro_data.sensor_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(100),
    zone_id INT NOT NULL,
    zone_name VARCHAR(100),
    temperature DECIMAL(5,2),
    humidity DECIMAL(5,2),
    recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## 🖥️ 5. Web Frontend (PHP + Bootstrap + Chart.js)

### `/var/www/html/axpro_web/index.php`

* ใช้ Bootstrap + Chart.js
* เลือกช่วงเวลา: 10/30/60 นาที หรือ 1–6 ชม. (ย้อนหลัง)
* มี datepicker + refresh อัตโนมัติทุก 15 วิ (Real-time)

### `/var/www/html/axpro_web/get_data.php`

* รับ `start_time`, `end_time` เป็นพารามิเตอร์
* Query จาก MariaDB แล้วส่ง JSON ให้ Chart.js

---

## ⏱️ 6. Systemd Timer ตั้งให้ทำงานทุก 5 วิ

### `/etc/systemd/system/axpro-logger.service`

```ini
[Unit]
Description=Log AX PRO temperature and humidity

[Service]
Type=oneshot
ExecStart=/root/venv/bin/python3 /root/log_temp.py
```

### `/etc/systemd/system/axpro-logger.timer`

```ini
[Unit]
Description=Run AX PRO logger every 5 seconds

[Timer]
OnBootSec=10sec
OnUnitActiveSec=5sec
Unit=axpro-logger.service

[Install]
WantedBy=timers.target
```

### คำสั่งเปิดใช้งาน

```bash
systemctl daemon-reexec
systemctl daemon-reload
systemctl enable --now axpro-logger.timer
```

ตรวจสอบว่า timer ทำงานได้จริง:

```bash
systemctl list-timers --all
journalctl -u axpro-logger.service -n 30 --no-pager
```

---

## 🗜️ 7. รวมระบบเป็น ZIP

รวมไฟล์ทั้งหมด:

```bash
zip -r axpro_monitoring_system.zip /root/log_temp.py \
    /etc/systemd/system/axpro-logger.service \
    /etc/systemd/system/axpro-logger.timer \
    /var/www/html/axpro_web/
```

---

## ✅ สรุปจุดเด่น

| ระบบ           | รายละเอียด                            |
| -------------- | ------------------------------------- |
| อุปกรณ์        | Hikvision AX PRO (ผ่าน HTTP API)      |
| ภาษา           | Python + PHP                          |
| ฐานข้อมูล      | MariaDB                               |
| Web Graph      | Chart.js + Bootstrap + jQuery         |
| เวลารีเฟรช     | ทุก 5 วิ (real-time) + ย้อนหลัง       |
| ความแม่นยำเวลา | ใช้ `recorded_at` TIMESTAMP บน server |

---

หากต้องการฉบับ markdown (`README.md`) สำหรับ GitHub ก็สามารถสร้างต่อได้ทันทีเช่นกัน บอกได้เลย.
