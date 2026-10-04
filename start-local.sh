#!/usr/bin/env bash
# รันโปรเจกต์บนเครื่อง (PHP + Vite) โดยต่อกับ MySQL/phpMyAdmin ที่อยู่ใน docker
# ใช้: ./start-local.sh        แล้วกด Ctrl+C เพื่อหยุดทุกอย่าง
set -euo pipefail
cd "$(dirname "$0")"

APP_PORT="${APP_PORT:-8000}"

busy() { lsof -nP -iTCP:"$1" -sTCP:LISTEN >/dev/null 2>&1; }

if busy "$APP_PORT"; then
  echo "✗ port $APP_PORT มีอะไรรันอยู่แล้ว"
  lsof -nP -iTCP:"$APP_PORT" -sTCP:LISTEN | awk 'NR>1{print "  → "$1" pid="$2}'
  echo
  echo "  ถ้าเป็น artisan serve ตัวเก่า: เปิด http://localhost:$APP_PORT ใช้ได้เลย"
  echo "  จะหยุดตัวเก่า:  kill \$(lsof -t -iTCP:$APP_PORT -sTCP:LISTEN)"
  echo "  หรือใช้ port อื่น: APP_PORT=8001 ./start-local.sh"
  exit 1
fi
busy 5173 && { echo "✗ port 5173 (vite) ถูกใช้อยู่ — หยุด vite ตัวเก่าก่อน"; exit 1; } || true

echo "==> 1/3 เปิดฐานข้อมูลใน docker"
docker compose up -d

echo "==> 2/3 รอ MariaDB พร้อมใช้งาน"
for i in $(seq 1 60); do
  status=$(docker inspect -f '{{.State.Health.Status}}' twofamily_db 2>/dev/null || echo missing)
  [ "$status" = "healthy" ] && { echo "    ✓ ฐานข้อมูลพร้อม"; break; }
  [ "$i" = 60 ] && { echo "    ✗ ฐานข้อมูลไม่พร้อมใน 60 วิ — ลอง: docker compose logs db"; exit 1; }
  sleep 1
done

echo "==> 3/3 เริ่ม dev server"
echo "    แอป        http://localhost:$APP_PORT"
echo "    phpMyAdmin http://localhost:8080"
echo
exec npx concurrently -k -c "#93c5fd,#fdba74,#c4b5fd" \
  --names=app,vite,logs \
  "php artisan serve --port=$APP_PORT" \
  "npm run dev" \
  "php artisan pail --timeout=0"
