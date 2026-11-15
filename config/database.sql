ALTER TABLE products
ADD COLUMN category_id INT NULL,
ADD CONSTRAINT fk_product_category
FOREIGN KEY (category_id)
REFERENCES categories(id)
ON DELETE SET NULL;

INSERT INTO categories (name, description, image)
VALUES
('Men', 'Explore our mens collection.', 'images/men.jpg'),
('Women', 'Discover our womens collection.', 'images/women.jpg'),
('Shoes', 'Find your perfect footwear.', 'images/shoes.jpg'),
('Accessories', 'Complete your look with accessories.', 'images/accessories.jpg');


////////////////////////////////////////////////////////////////////////////////////////////////
-- ==========================================
-- 1. CREATE DATABASE
-- ==========================================

CREATE DATABASE IF NOT EXISTS my_store_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE my_store_db;


-- ==========================================
-- 2. CATEGORIES TABLE
-- ==========================================

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    description TEXT,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ==========================================
-- 3. PRODUCTS TABLE
-- ==========================================

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,

    category_id INT NULL,

    sku VARCHAR(100) UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,

    image VARCHAR(255),

    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,

    stock INT DEFAULT 100,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_product_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_product_name (name),
    INDEX idx_product_category (category_id),
    INDEX idx_product_price (price)
) ENGINE=InnoDB;


-- ==========================================
-- 4. ORDERS TABLE
-- ==========================================

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,

    order_number VARCHAR(50) NOT NULL UNIQUE,

    customer_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,

    county VARCHAR(100) NOT NULL,
    delivery_address VARCHAR(500) NOT NULL,
    notes TEXT,

    payment_method ENUM(
        'mpesa',
        'cash_on_delivery'
    ) NOT NULL,

    subtotal DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 300,
    total_amount DECIMAL(10,2) NOT NULL,

    payment_status ENUM(
        'pending',
        'paid',
        'failed'
    ) DEFAULT 'pending',

    order_status ENUM(
        'pending',
        'processing',
        'shipped',
        'delivered',
        'cancelled'
    ) DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_order_number (order_number),
    INDEX idx_order_email (email),
    INDEX idx_order_phone (phone),
    INDEX idx_order_status (order_status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_order_date (created_at)

) ENGINE=InnoDB;


-- ==========================================
-- 5. ORDER ITEMS TABLE
-- ==========================================

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,
    product_id INT NOT NULL,

    product_name VARCHAR(255) NOT NULL,

    unit_price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    line_total DECIMAL(10,2) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE RESTRICT,

    INDEX idx_order_items_order (order_id),
    INDEX idx_order_items_product (product_id)

) ENGINE=InnoDB;


-- ==========================================
-- 6. ADMIN USERS TABLE
-- ==========================================

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,

    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    role ENUM(
        'super_admin',
        'admin',
        'manager'
    ) DEFAULT 'admin',

    status ENUM(
        'active',
        'inactive'
    ) DEFAULT 'active',

    last_login DATETIME DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB;


-- ==========================================
-- 7. CONTACT MESSAGES TABLE
-- ==========================================

CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20),

    subject VARCHAR(255),
    message TEXT NOT NULL,

    status ENUM(
        'unread',
        'read',
        'replied'
    ) DEFAULT 'unread',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_contact_status (status),
    INDEX idx_contact_date (created_at)

) ENGINE=InnoDB;


-- ==========================================
-- 8. M-PESA PAYMENT TRANSACTIONS TABLE
-- ==========================================

CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,

    merchant_request_id VARCHAR(150) DEFAULT NULL,
    checkout_request_id VARCHAR(150) DEFAULT NULL,

    phone VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,

    mpesa_receipt_number VARCHAR(50) DEFAULT NULL,

    result_code VARCHAR(20) DEFAULT NULL,
    result_description TEXT,

    status ENUM(
        'pending',
        'completed',
        'failed'
    ) DEFAULT 'pending',

    callback_data JSON DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_payment_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE,

    UNIQUE KEY uq_checkout_request (checkout_request_id),
    INDEX idx_merchant_request (merchant_request_id),
    INDEX idx_mpesa_receipt (mpesa_receipt_number),
    INDEX idx_transaction_status (status)

) ENGINE=InnoDB;