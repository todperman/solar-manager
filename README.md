# G2K Solar Manager

ระบบบริหารงานขายและติดตั้งโซล่าเซลล์ / ปั๊มน้ำ ประกอบด้วย **หน้าร้านออนไลน์** และ **หลังบ้าน (Back Office)**

- **หน้าร้าน**: แคตตาล็อกสินค้า, แพ็กเกจติดตั้ง, ตะกร้า, สั่งซื้อ
- **หลังบ้าน**: Dashboard, สินค้า/หมวดหมู่, Stock รับเข้า-เบิกออก, แพ็กเกจ, โปรโมชั่น, POS หน้าร้าน, คำสั่งซื้อ, ใบเสนอราคา (พิมพ์/PDF), ลูกค้า (CRM), รายงาน + Export CSV, ตั้งค่าระบบ

**Stack:** PHP 8 (ไม่ใช้ framework และไม่ต้องใช้ Composer) · MySQL/MariaDB · Apache (XAMPP)

---

## ติดตั้งบนเครื่องใหม่ (XAMPP)

```bat
cd C:\xampp\htdocs
git clone <URL-ของ-repository> solar-manager
cd solar-manager
install.bat
```

จากนั้นเปิด http://localhost/solar-manager/ (หลังบ้าน: `/login.php`)

macOS / Linux ใช้ `./install.sh` แทน

**คู่มือฉบับเต็ม** (VirtualHost สำหรับ production, ความปลอดภัย, backup, อัปเดต, แก้ปัญหา): [docs/DEPLOY-XAMPP.md](docs/DEPLOY-XAMPP.md)

---

## โครงสร้างโปรเจกต์

```
solar-manager/
├── public/                 ← Web root (VirtualHost ชี้มาที่นี่)
│   ├── admin/              หลังบ้าน
│   ├── store/              หน้าร้าน
│   ├── api/                AJAX endpoint (ตะกร้า)
│   ├── includes/           layout (header/footer/nav)
│   ├── assets/             CSS, รูป, uploads/ (รูปสินค้า, ไม่อยู่ใน git)
│   └── login.php, logout.php, index.php
├── includes/               config, auth, helper functions (อยู่นอก web root)
├── config/
│   ├── app.example.php     ตัวอย่างไฟล์ตั้งค่า
│   └── app.php             ไฟล์ตั้งค่าจริง (ตัวติดตั้งสร้างให้, ไม่อยู่ใน git)
├── sql/schema.sql          โครงสร้างฐานข้อมูลและข้อมูลตัวอย่าง
├── scripts/install.php     ตัวติดตั้ง (CLI)
├── install.bat / install.sh
├── .htaccess               ใช้กรณีวางใน htdocs: ส่ง request ไป public/ และบล็อกโฟลเดอร์ระบบ
└── docs/DEPLOY-XAMPP.md
```

## การตั้งค่า

ค่าทั้งหมดอยู่ใน `config/app.php` (ดูตัวอย่างที่ `config/app.example.php`) ถ้าไม่มีไฟล์นี้ ระบบจะอ่านจาก environment variable (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_DEBUG`, `APP_BASE_URL`) แล้วจึงใช้ค่าเริ่มต้นของ XAMPP

ระบบตรวจ path ของ URL ให้เอง ใช้ได้ทั้งแบบ `http://host/solar-manager/` และแบบ VirtualHost `http://host:8088/` โดยไม่ต้องแก้โค้ด ในโค้ดให้สร้างลิงก์ด้วย `url('/admin/orders.php')` และ redirect ด้วย `redirect('/admin/orders.php')` เสมอ ห้ามเขียน path แบบ `/admin/...` ตรง ๆ

## พัฒนาในเครื่อง (ทางเลือก)

- **XAMPP**: ทำตามขั้นตอนติดตั้งด้านบน
- **PHP built-in server**: `php -S localhost:8000 -t public` (ต้องมี MySQL และรัน `php scripts/install.php` ก่อน)
- **Docker** (dev เท่านั้น): `docker compose up -d` แล้วเปิด http://localhost:8088 (ใช้ MySQL บนเครื่อง host)

## ความต้องการของระบบ

- PHP 8.0+ พร้อม `pdo_mysql`, `mbstring`
- MySQL 5.7+ / 8.x หรือ MariaDB 10.4+
- Apache 2.4 ที่เปิด `mod_rewrite` และ `AllowOverride All` (XAMPP ตั้งไว้ให้แล้ว)
