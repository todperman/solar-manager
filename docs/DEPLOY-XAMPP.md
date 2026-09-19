# คู่มือติดตั้ง G2K Solar Manager บน XAMPP

คู่มือนี้ใช้ติดตั้งระบบบนเครื่องใหม่ (Windows / macOS / Linux) ด้วย XAMPP โดย **ไม่ต้องใช้ Docker**

---

## 1. สิ่งที่ต้องมี

| รายการ | เวอร์ชัน | หมายเหตุ |
|---|---|---|
| XAMPP | 8.0 ขึ้นไป (แนะนำ 8.2) | ดาวน์โหลดที่ https://www.apachefriends.org |
| PHP | 8.0+ | มากับ XAMPP |
| MySQL / MariaDB | MariaDB 10.4+ หรือ MySQL 5.7+ | มากับ XAMPP |
| Git | ตัวไหนก็ได้ | ใช้ดึงโค้ด (หรือดาวน์โหลดเป็น ZIP ก็ได้) |

PHP extension ที่ใช้ ได้แก่ `pdo_mysql` และ `mbstring` ซึ่ง XAMPP เปิดไว้ให้แล้ว ส่วน `mod_rewrite` ของ Apache ก็เปิดไว้แล้วเช่นกัน

---

## 2. ติดตั้งแบบเร็ว (โฟลเดอร์ใน htdocs), ประมาณ 5 นาที

เหมาะกับการใช้ในเครื่องหรือในวง LAN ของออฟฟิศ

### 2.1 เปิด XAMPP Control Panel แล้วกด **Start** ที่ Apache และ MySQL

### 2.2 วางโค้ดไว้ใน htdocs

```bat
cd C:\xampp\htdocs
git clone <URL-ของ-repository> solar-manager
```

หรือดาวน์โหลด ZIP แล้วแตกไฟล์ให้ได้ `C:\xampp\htdocs\solar-manager\`

### 2.3 รันตัวติดตั้ง

- **Windows**: ดับเบิลคลิก `install.bat` ในโฟลเดอร์ `solar-manager`
- **macOS / Linux**: `cd /Applications/XAMPP/htdocs/solar-manager && ./install.sh`

ตัวติดตั้งจะถามค่าต่าง ๆ ถ้าไม่แน่ใจให้กด Enter เพื่อใช้ค่าเริ่มต้นได้เลย:

```
MySQL host [127.0.0.1]:
MySQL port [3306]:
MySQL user [root]:
MySQL password:                 ← XAMPP ค่าเริ่มต้นคือว่าง ให้กด Enter
Database name [solar_manager]:
Admin email [admin@g2k.co.th]:
Admin password (min 8 chars, blank = random):
```

ตัวติดตั้งจะทำสิ่งต่อไปนี้:
1. ตรวจเวอร์ชัน PHP และ extension
2. สร้างฐานข้อมูล (utf8mb4) และนำเข้าตารางพร้อมข้อมูลตัวอย่าง
3. ตั้งบัญชีผู้ดูแลระบบตามอีเมลและรหัสผ่านที่กรอก
4. สร้างไฟล์ `config/app.php`
5. ตรวจว่าโฟลเดอร์อัปโหลดรูปเขียนได้

> รันซ้ำได้อย่างปลอดภัย ถ้าฐานข้อมูลมีข้อมูลอยู่แล้ว ตัวติดตั้งจะไม่ลบและไม่ import ทับ

### 2.4 เปิดใช้งาน

- หน้าร้าน: http://localhost/solar-manager/
- หลังบ้าน: http://localhost/solar-manager/login.php

เครื่องอื่นในวง LAN เข้าผ่าน `http://<IP-เครื่องนี้>/solar-manager/` ได้ (ต้องอนุญาต Apache ใน Windows Firewall ก่อน)

---

## 3. ติดตั้งแบบ Production (VirtualHost), แนะนำสำหรับเครื่องที่ใช้งานจริง

แบบนี้ให้ Apache ชี้ตรงไปที่โฟลเดอร์ `public/` ไฟล์ระบบ (config, sql, scripts) จึงอยู่นอก web root ทั้งหมด

### 3.1 ทำข้อ 2.1–2.3 ให้เสร็จก่อน

### 3.2 เพิ่ม VirtualHost

แก้ไฟล์ `C:\xampp\apache\conf\extra\httpd-vhosts.conf` แล้วเพิ่มท้ายไฟล์ (ตัวอย่างใช้พอร์ต 8088):

```apache
Listen 8088

<VirtualHost *:8088>
    ServerName g2k.local
    DocumentRoot "C:/xampp/htdocs/solar-manager/public"

    <Directory "C:/xampp/htdocs/solar-manager/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog "logs/g2k-error.log"
    CustomLog "logs/g2k-access.log" common
</VirtualHost>
```

> ถ้าต้องการใช้พอร์ต 80 กับโดเมนจริง ให้ลบบรรทัด `Listen 8088` ออก, เปลี่ยน `*:8088` เป็น `*:80`, และตั้ง `ServerName` เป็นโดเมนของคุณ

### 3.3 ตรวจว่า httpd.conf โหลด vhosts แล้ว

ใน `C:\xampp\apache\conf\httpd.conf` บรรทัดนี้ต้องไม่มี `#` นำหน้า:

```apache
Include conf/extra/httpd-vhosts.conf
```

### 3.4 Restart Apache จาก XAMPP Control Panel แล้วเปิด http://localhost:8088/

ระบบตรวจ path อัตโนมัติ ไม่ต้องแก้ config เพิ่ม (`base_url` = `auto`)

---

## 4. ไฟล์ตั้งค่า `config/app.php`

ตัวติดตั้งสร้างให้อัตโนมัติ และไฟล์นี้ **ไม่ถูก commit ขึ้น git** ถ้าต้องการสร้างเอง ให้คัดลอกจาก `config/app.example.php`

| คีย์ | ค่าเริ่มต้น | ความหมาย |
|---|---|---|
| `db.host` / `db.port` | `127.0.0.1` / `3306` | ที่อยู่ MySQL |
| `db.name` | `solar_manager` | ชื่อฐานข้อมูล |
| `db.user` / `db.pass` | `root` / ว่าง | บัญชี MySQL |
| `app.base_url` | `auto` | path ของระบบใน URL ปกติให้ใช้ `auto` ถ้าตรวจผิดค่อยระบุเอง เช่น `'/solar-manager'` หรือ `''` |
| `app.timezone` | `Asia/Bangkok` | เขตเวลา (XAMPP ตั้งค่าเริ่มต้นเป็น Europe/Berlin ระบบจึงบังคับเป็นค่านี้) |
| `app.debug` | `false` | `true` = แสดง error บนหน้าจอ ใช้เฉพาะตอนแก้ปัญหา |

---

## 5. ความปลอดภัยสำหรับเครื่อง Production

- [ ] **ตั้งรหัสผ่าน MySQL root** (XAMPP ค่าเริ่มต้นไม่มีรหัส): ตั้งผ่าน phpMyAdmin → User accounts แล้วรัน `install.bat` ใหม่เพื่ออัปเดต `config/app.php`
- [ ] ดีกว่านั้นคือสร้าง MySQL user เฉพาะของระบบ:
  ```sql
  CREATE USER 'g2k'@'localhost' IDENTIFIED BY 'รหัสผ่านที่แข็งแรง';
  GRANT ALL PRIVILEGES ON solar_manager.* TO 'g2k'@'localhost';
  ```
- [ ] ปิดการเข้าถึง phpMyAdmin จากเครื่องอื่น (XAMPP ค่าเริ่มต้นอนุญาตเฉพาะ localhost ให้คงไว้แบบนั้น)
- [ ] ใช้ VirtualHost (ข้อ 3) แทนการวางไว้ใน htdocs
- [ ] ถ้าเปิดให้เข้าจากอินเทอร์เน็ต ต้องใช้ HTTPS (ผ่าน reverse proxy / Cloudflare หรือตั้ง SSL ใน Apache)
- [ ] `app.debug` ต้องเป็น `false`

---

## 6. สำรองข้อมูล (Backup)

สิ่งที่ต้องสำรองมี 2 อย่าง:

1. **ฐานข้อมูล**
   ```bat
   C:\xampp\mysql\bin\mysqldump.exe -u root -p --single-transaction --routines solar_manager > backup_solar_manager.sql
   ```
   หรือใช้ phpMyAdmin → เลือกฐานข้อมูล → Export
2. **รูปสินค้าที่อัปโหลด** ในโฟลเดอร์ `public/assets/img/uploads/`

การกู้คืน: สร้างฐานข้อมูลเปล่า แล้ว import ไฟล์ `.sql` ด้วย phpMyAdmin หรือ `mysql.exe -u root -p solar_manager < backup_solar_manager.sql` จากนั้นคัดลอกโฟลเดอร์ uploads กลับไปที่เดิม

---

## 7. อัปเดตเวอร์ชัน

```bat
cd C:\xampp\htdocs\solar-manager
git pull
install.bat
```

`install.bat` จะเก็บข้อมูลเดิมไว้ ไม่ลบทับ ส่วน `config/app.php` และรูปที่อัปโหลดไม่ได้อยู่ใน git จึงไม่ถูกแตะ

---

## 8. แก้ปัญหาที่พบบ่อย

| อาการ | สาเหตุ / วิธีแก้ |
|---|---|
| "ไม่สามารถเชื่อมต่อฐานข้อมูลได้" | MySQL ยังไม่ Start ใน XAMPP หรือรหัสผ่านใน `config/app.php` ไม่ตรง |
| 404 เมื่อเปิด `/solar-manager/` | ชื่อโฟลเดอร์ไม่ตรงกับ URL หรือ `mod_rewrite` ถูกปิด (ดูใน `httpd.conf`: `LoadModule rewrite_module` ต้องไม่มี `#`) |
| 403 Forbidden ทั้งเว็บ | `mod_rewrite` ถูกปิด (ไฟล์ `.htaccess` ที่ root จะบล็อกไว้เพื่อความปลอดภัย) |
| หน้าเว็บไม่มี CSS / ลิงก์ผิด path | ตั้ง `app.base_url` ใน `config/app.php` เองให้ตรงกับ URL เช่น `'/solar-manager'` |
| อัปโหลดรูปไม่ได้ | โฟลเดอร์ `public/assets/img/uploads` ต้องเขียนได้ (บน Linux: `chmod 775` และให้ Apache เป็นเจ้าของ) หรือไฟล์ใหญ่เกิน `upload_max_filesize` ใน `php.ini` |
| Apache Start ไม่ขึ้น (พอร์ต 80 ชน) | Skype / IIS ใช้พอร์ต 80 อยู่ ให้ปิดโปรแกรมนั้น หรือใช้ VirtualHost พอร์ต 8088 |
| `install.bat` แจ้งว่าหา php.exe ไม่เจอ | XAMPP ไม่ได้อยู่ที่ `C:\xampp` ให้รัน `set XAMPP_HOME=E:\xampp` ก่อน แล้วรัน `install.bat` |
| ลืมรหัสผ่าน admin | รัน `install.bat` แล้วตอบ `y` ที่คำถาม "Reset the administrator login?" |

ถ้าเจอ error อื่น ให้ดูที่ `C:\xampp\apache\logs\error.log` หรือเปิด `app.debug => true` ชั่วคราว
