# รันโปรเจกต์บนเครื่อง (local) + ฐานข้อมูลใน Docker

Laravel รันบนเครื่องปกติ (`php artisan serve` + Vite) ส่วน **MySQL กับ phpMyAdmin อยู่ใน Docker**

## เริ่มงาน — คำสั่งเดียวจบ

```bash
./start-local.sh
```

สคริปต์จะเปิด docker → รอ MariaDB พร้อม → แล้วเริ่ม `artisan serve` + `vite` + `pail` (log สด)
กด **Ctrl+C** ครั้งเดียวหยุดทั้งหมด (ฐานข้อมูลใน docker ยังรันต่อ)

| | URL |
|---|---|
| แอป | <http://localhost:8000> |
| phpMyAdmin | <http://localhost:8080> |
| Vite dev server | <http://127.0.0.1:5173> |

ถ้า port 8000 ไม่ว่าง สคริปต์จะบอกว่าใครใช้อยู่ และให้ทางเลือก:

```bash
APP_PORT=8001 ./start-local.sh      # ใช้ port อื่น
kill $(lsof -t -iTCP:8000 -sTCP:LISTEN)   # หรือหยุดตัวเก่า
```

## ถ้าอยากสั่งเองทีละอย่าง

```bash
docker compose up -d     # 1. ฐานข้อมูล
php artisan serve        # 2. แอป      → :8000
npm run dev              # 3. Vite     → :5173
```

## ตั้งเครื่องใหม่ (clone มาครั้งแรก)

```bash
composer install
npm install
cp .env.example .env && php artisan key:generate
php artisan storage:link
./start-local.sh
```

---

# รายละเอียดฝั่ง Docker

| บริการ | เข้าที่ | หมายเหตุ |
|---|---|---|
| MariaDB 10.11 | `127.0.0.1:3306` | user `root`, รหัสผ่านว่าง (ตรงกับ `.env`) |
| phpMyAdmin | <http://localhost:8080> | ล็อกอินอัตโนมัติ ไม่ต้องกรอกอะไร |

> ทั้งสอง port bind ไว้แค่ `127.0.0.1` เครื่องอื่นใน LAN ต่อเข้ามาไม่ได้

## คำสั่งที่ใช้บ่อย

```bash
docker compose up -d        # เปิด
docker compose ps           # ดูสถานะ
docker compose logs -f db   # ดู log
docker compose stop         # ปิด (ข้อมูลยังอยู่)
docker compose down         # ลบ container (ข้อมูลยังอยู่ใน volume)
```

## .env

ไม่ต้องแก้อะไร ค่าเดิมใช้ได้เลย:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=twofamily_en
DB_USERNAME=root
DB_PASSWORD=
```

ถ้ามี MySQL/XAMPP ตัวอื่นกิน port 3306 อยู่ ให้ปิดตัวนั้น หรือเปลี่ยนใน `docker-compose.yml`
เป็น `"127.0.0.1:3307:3306"` แล้วตั้ง `DB_PORT=3307` ใน `.env`

## การ import ข้อมูล

`twofamily_en.sql` ถูก mount เข้า `/docker-entrypoint-initdb.d/` → import **อัตโนมัติเฉพาะครั้งแรก**
ที่สร้าง volume เท่านั้น (ถ้ามีข้อมูลอยู่แล้ว MariaDB จะข้ามไป)

**ล้างทั้งหมดแล้ว import ใหม่จากไฟล์ sql:**

```bash
docker compose down -v && docker compose up -d
```

> `-v` ลบ volume `twofamily_en_db_data` ข้อมูลที่แก้ไว้ในฐานข้อมูลจะหายทั้งหมด — export ก่อนถ้ายังต้องใช้

**import ไฟล์ sql อื่นเข้าฐานที่รันอยู่:**

```bash
docker compose exec -T db mariadb -uroot twofamily_en < path/to/file.sql
```

**export (dump) ออกมา:**

```bash
docker compose exec -T db mariadb-dump -uroot twofamily_en > twofamily_en.sql
```

## ไฟล์ที่เกี่ยวข้อง

```
start-local.sh                          # สคริปต์เปิดทุกอย่างทีเดียว
docker-compose.yml
docker/mysql/conf.d/my.cnf              # utf8mb4, timezone +07:00, max_allowed_packet
docker/phpmyadmin/config.user.inc.php   # เปิด AllowNoPassword, ซ่อน system database
vite.config.js                          # bind 127.0.0.1:5173 (กัน vite ไปอยู่ IPv6 อย่างเดียว)
```
