-- =============================================
-- VALI STORE - DATABASE SETUP
-- =============================================

CREATE DATABASE IF NOT EXISTS vali_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vali_store;

-- Bảng users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bảng products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(12,0) NOT NULL,
    image VARCHAR(255) DEFAULT 'default.jpg',
    category VARCHAR(100),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Bảng orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    fullname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    total DECIMAL(12,0) NOT NULL,
    status ENUM('pending', 'confirmed', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bảng order_details
CREATE TABLE IF NOT EXISTS order_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(12,0) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- ===== DỮ LIỆU MẪU - SẢN PHẨM =====
INSERT INTO products (id, name, description, price, image, category, stock) VALUES
(1, 'Vali Kéo Travel King 20 inch', 'Vali kéo cao cấp, chất liệu PC chống xước, khóa TSA, 4 bánh xoay 360 độ. Kích thước 20 inch phù hợp xách tay lên máy bay.', 1200000, 'assets/images/demo-reference-1.jpg', 'Vali kéo', 25),
(2, 'Vali Kéo Samsonite 24 inch', 'Vali kéo Samsonite chính hãng, chất liệu ABS siêu bền, khóa mật mã, tay kéo hợp kim nhôm. Kích thước 24 inch ký gửi.', 3500000, 'assets/images/demo-reference-2.jpg', 'Vali kéo', 15),
(3, 'Vali Kéo Rimowa 28 inch', 'Vali cao cấp Rimowa, chất liệu polycarbonate, hệ thống bánh xe Multiwheel, khóa TSA tích hợp. Kích thước 28 inch cho chuyến đi dài.', 8500000, 'assets/images/demo-reference-3.jpg', 'Vali kéo', 8),
(4, 'Vali Vải Oxford 20 inch', 'Vali vải Oxford chống thấm nước, nhẹ, có ngăn mở rộng. Phù hợp cho chuyến công tác ngắn ngày.', 650000, 'assets/images/demo-reference-4.png', 'Vali vải', 30),
(5, 'Vali Vải Heys 24 inch', 'Vali vải cao cấp Heys, thiết kế thời trang, nhiều ngăn tiện dụng, khóa mật mã.', 1800000, 'assets/images/demo-reference-5.jpg', 'Vali vải', 20),
(6, 'Balo Du Lịch 40L', 'Balo du lịch dung tích 40L, chất liệu nylon chống thấm, có ngăn laptop 15.6 inch, đai lưng hỗ trợ.', 450000, 'assets/images/demo-reference-6.jpg', 'Balo', 40),
(7, 'Balo Leo Núi 50L', 'Balo leo núi chuyên dụng 50L, khung nhôm hỗ trợ lưng, chống thấm, nhiều ngăn chứa đồ.', 890000, 'assets/images/demo-reference-7.jpg', 'Balo', 18),
(8, 'Túi Du Lịch Xách Tay', 'Túi du lịch xách tay thời trang, chất liệu canvas bền đẹp, dung tích 30L, có dây đeo chéo.', 320000, 'assets/images/demo-reference-8.jpg', 'Túi du lịch', 50)
ON DUPLICATE KEY UPDATE name=VALUES(name);
