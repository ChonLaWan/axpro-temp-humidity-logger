from hikaxpro import HikAxPro

# แก้ IP, username, password ให้ตรงกับของคุณ
axpro = HikAxPro(host="192.168.5.55", username="admin", password="password")

zones = axpro.get_zones()

for zone in zones:
    z = zone["Zone"]
    print(f"Zone {z['id']}: {z['name']}")

    # ลองแสดงค่าทั้งหมดใน zone
    for k, v in z.items():
        print(f"  {k}: {v}")

    print()
