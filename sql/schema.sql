-- G2K Solar Manager — database schema + seed data
-- Compatible with MySQL 5.7+/8.x and MariaDB 10.4+ (XAMPP).
--
-- Normally imported by the installer (install.bat / install.sh).
-- Manual import via phpMyAdmin: create database "solar_manager" with collation
-- utf8mb4_unicode_ci, select it, then Import this file.

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- Users (Admin/Staff)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin','staff') DEFAULT 'staff',
    status ENUM('active','inactive') DEFAULT 'active',
    avatar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    sort_order INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    sku VARCHAR(50) UNIQUE NOT NULL,
    category_id INT,
    description TEXT,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    cost DECIMAL(10,2) DEFAULT 0,
    stock_qty INT DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'ชิ้น',
    image VARCHAR(255),
    specs JSON,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock History
CREATE TABLE stock_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type ENUM('in','out') NOT NULL,
    qty INT NOT NULL,
    reference VARCHAR(100),
    note TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Packages (Installation packages)
CREATE TABLE packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2),
    features JSON,
    popular TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Promotions
CREATE TABLE promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    code VARCHAR(50) UNIQUE,
    type ENUM('percent','fixed') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    min_amount DECIMAL(10,2) DEFAULT 0,
    max_discount DECIMAL(10,2),
    start_date DATE,
    end_date DATE,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    province VARCHAR(50),
    line_id VARCHAR(100),
    notes TEXT,
    source VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_no VARCHAR(20) UNIQUE NOT NULL,
    customer_id INT,
    user_id INT,
    type ENUM('pos','quotation','store') NOT NULL,
    status ENUM('pending','paid','completed','cancelled') DEFAULT 'pending',
    subtotal DECIMAL(10,2) DEFAULT 0,
    discount DECIMAL(10,2) DEFAULT 0,
    tax_rate DECIMAL(5,2) DEFAULT 7,
    tax DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) DEFAULT 0,
    payment_method VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    package_id INT,
    name VARCHAR(200) NOT NULL,
    qty INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: password)
INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@solarmgr.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert default categories
INSERT INTO categories (name, description, icon, sort_order) VALUES
('Solar Panel', 'แผงโซล่าเซลล์', '☀️', 1),
('Inverter', 'อินเวอร์เตอร์', '⚡', 2),
('Battery', 'แบตเตอรี่', '🔋', 3),
('Cable & Accessories', 'สายไฟและอุปกรณ์เสริม', '🔌', 4),
('Mounting System', 'โครงสร้างยึดแผง', '🔩', 5),
('Monitoring', 'ระบบมอนิเตอร์', '📊', 6);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('company_name', 'G2K'),
('company_address', '123 ถนนสุขุมวิท กรุงเทพฯ 10110'),
('company_phone', '02-123-4567'),
('company_email', 'info@solarcell.co.th'),
('tax_rate', '7'),
('currency', '฿');

-- Insert sample products
INSERT INTO products (name, sku, category_id, description, price, cost, stock_qty, specs) VALUES
('LONGi Hi-MO 6 450W', 'LP-450W-001', 1, 'แผงโซล่าเซลล์ LONGi 450W ประสิทธิภาพสูง', 6500.00, 4200.00, 100, '{"efficiency":"21.3%","size":"2094x1038mm","weight":"21.8kg","warranty":"25 years"}'),
('Huawei SUN2000 5KTL', 'INV-5KTL-001', 2, 'อินเวอร์เตอร์ Huawei 5kW ระบบ Hybrid', 32000.00, 22000.00, 50, '{"capacity":"5kW","type":"Hybrid","efficiency":"98.6%","warranty":"10 years"}'),
('BYD Battery-Box 5.1kWh', 'BAT-51-001', 3, 'แบตเตอรี่ BYD 5.1kWh ระบบ LFP', 45000.00, 32000.00, 30, '{"capacity":"5.1kWh","type":"LFP","cycles":"6000+","warranty":"10 years"}'),
('Cable 6mm² 30m', 'CBL-6MM-001', 4, 'สายไฟฟ้า 6mm² ความยาว 30 เมตร', 1200.00, 800.00, 200, '{"type":"DC Solar","length":"30m","rating":"1000V"}'),
('Aluminum Mounting Rail', 'MNT-ALU-001', 5, 'โครงสร้างยึดอลูมิเนียม สำหรับหลังคา', 3500.00, 2200.00, 80, '{"material":"Aluminum","length":"4.2m","suitable":"Metal/Roof"}');

-- Insert sample packages
INSERT INTO packages (name, description, price, original_price, features, popular) VALUES
('ระบบ 3kW ครอบครัวเล็ก', 'เหมาะสำหรับบ้านขนาดเล็ก ประหยัดค่าไฟได้ 1,200-1,800 บาท/เดือน', 89000.00, 110000.00, '["แผง 3kW (7 แผง)","อินเวอร์เตอร์ 3kW","อุปกรณ์ติดตั้งครบชุด","รับประกัน 25 ปี","ติดตั้งฟรี"]', 0),
('ระบบ 5kW ครอบครัวกลาง', 'เหมาะสำหรับบ้านขนาดกลาง ประหยัดค่าไฟได้ 2,000-3,000 บาท/เดือน', 139000.00, 175000.00, '["แผง 5kW (11 แผง)","อินเวอร์เตอร์ 5kW","อุปกรณ์ติดตั้งครบชุด","รับประกัน 25 ปี","ติดตั้งฟรี","มอนิเตอร์ระบบ"]', 1),
('ระบบ 10kW บ้านใหญ่', 'เหมาะสำหรับบ้านขนาดใหญ่ ประหยัดค่าไฟได้ 4,000-6,000 บาท/เดือน', 259000.00, 320000.00, '["แผง 10kW (22 แผง)","อินเวอร์เตอร์ Hybrid 10kW","Battery 10kWh","อุปกรณ์ติดตั้งครบชุด","รับประกัน 25 ปี","ติดตั้งฟรี","มอนิเตอร์ระบบ"]', 0);

-- Insert sample promotion
INSERT INTO promotions (name, code, type, value, min_amount, start_date, end_date) VALUES
('ส่วนลดพิเศษ 10%', 'SOLAR10', 'percent', 10.00, 50000.00, '2026-01-01', '2026-12-31'),
('ส่วนลด 5,000 บาท', 'SAVE5000', 'fixed', 5000.00, 100000.00, '2026-01-01', '2026-12-31');

SET foreign_key_checks = 1;
