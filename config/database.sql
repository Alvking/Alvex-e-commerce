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
    image VARCHAR(2048) DEFAULT NULL,
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

    image VARCHAR(2048),

    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,

    stock INT DEFAULT 100,
    is_active TINYINT(1) NOT NULL DEFAULT 1,

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
-- 3A. PRODUCT IMAGE GALLERY
-- ==========================================

CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(2048) NOT NULL,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_images_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_product_image (product_id, image_path(191)),
    INDEX idx_product_images_order (product_id, sort_order)
) ENGINE=InnoDB;

-- Add the archive flag to databases created with an earlier version of this schema.
SET @has_product_active := (
    SELECT COUNT(*)
    FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'products'
      AND column_name = 'is_active'
);
SET @add_product_active_sql := IF(
    @has_product_active = 0,
    'ALTER TABLE products ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER stock',
    'SELECT 1'
);
PREPARE add_product_active_stmt FROM @add_product_active_sql;
EXECUTE add_product_active_stmt;
DEALLOCATE PREPARE add_product_active_stmt;


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


-- ==========================================
-- 9. SAMPLE PRODUCT CATALOG
-- Product images are public image URLs or paths relative to the website root.
-- ==========================================

INSERT INTO categories (name, description, image)
VALUES
    ('Laptops', 'Powerful laptops for work, study, and creative projects.', 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800'),
    ('Smartphones', 'Smartphones for communication, photography, and entertainment.', 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800'),
    ('Audio', 'Headphones and audio gear for music and calls.', 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800'),
    ('Wearables', 'Smart watches and wearable technology.', 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800'),
    ('Gaming', 'Gaming consoles, controllers, and accessories.', 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=800'),
    ('Smart Home', 'Connected devices for a smarter home.', 'https://images.unsplash.com/photo-1558002038-1055907df827?w=800')
ON DUPLICATE KEY UPDATE
    description = VALUES(description),
    image = VALUES(image);

INSERT INTO products (category_id, sku, name, description, image, price, stock)
VALUES
    (
        (SELECT id FROM categories WHERE name = 'Laptops'),
        'NOVABOOK-PRO-14',
        'NovaBook Pro 14-inch Laptop',
        'A lightweight 14-inch laptop built for everyday productivity. It features a sharp display, fast solid-state storage, and a long-lasting battery for work or study on the go.',
        'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800',
        116870.00,
        25
    ),
    (
        (SELECT id FROM categories WHERE name = 'Audio'),
        'PULSE-BUDS-PRO',
        'Pulse Buds Pro',
        'Wireless earbuds with clear sound, a pocket-sized charging case, and a comfortable fit for music, calls, and travel.',
        'https://images.unsplash.com/photo-1606220945770-b5b6c2c55bf1?w=800',
        16770.00,
        50
    ),
    (
        (SELECT id FROM categories WHERE name = 'Smartphones'),
        'VISTA-X-SMARTPHONE',
        'Vista X Smartphone',
        'A modern smartphone with a vivid screen, capable cameras, and all-day battery life for staying connected and capturing everyday moments.',
        'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800',
        90870.00,
        30
    ),
    (
        (SELECT id FROM categories WHERE name = 'Wearables'),
        'TRACKFIT-ELITE',
        'TrackFit Elite Smart Watch',
        'A versatile smart watch for activity tracking, notifications, and checking your daily progress at a glance.',
        'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800',
        25870.00,
        40
    ),
    (
        (SELECT id FROM categories WHERE name = 'Gaming'),
        'PLAYMAX-CONTROLLER',
        'PlayMax Wireless Controller',
        'A comfortable wireless controller with responsive buttons for gaming at home.',
        'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=800',
        8970.00,
        35
    ),
    (
        (SELECT id FROM categories WHERE name = 'Smart Home'),
        'HOMECAM-360',
        'HomeCam 360',
        'A compact smart-home camera designed to help you keep an eye on your space from one convenient device.',
        'https://images.unsplash.com/photo-1558002038-1055907df827?w=800',
        11570.00,
        20
    )
ON DUPLICATE KEY UPDATE
    category_id = VALUES(category_id),
    name = VALUES(name),
    description = VALUES(description),
    image = VALUES(image),
    price = VALUES(price),
    stock = VALUES(stock);

-- Keep existing single-image products visible in the gallery.
INSERT INTO product_images (product_id, image_path, sort_order)
SELECT p.id, p.image, 0
FROM products p
WHERE p.image IS NOT NULL
  AND p.image <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM product_images pi
      WHERE pi.product_id = p.id
        AND pi.image_path = p.image
  );