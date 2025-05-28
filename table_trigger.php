<?php/*
// database_schema_fixed.php

// Connect to MySQL
$connection = mysqli_connect('localhost', 'root', '');

if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$queries = [
    "CREATE DATABASE IF NOT EXISTS inventory_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "USE inventory_system",
    "SET FOREIGN_KEY_CHECKS = 1"
];

foreach ($queries as $query) {
    if (!mysqli_query($connection, $query)) {
        die("Error creating database: " . mysqli_error($connection));
    }
}

// Drop tables in proper order to avoid foreign key constraints
$tablesToDrop = [
    'sales_return_items', 'purchase_return_items',
    'sales_payment', 'purchase_payment',
    'sales_returns', 'purchase_returns',
    'sale_items', 'purchase_items',
    'stock', 'inventory_adjustments',
    'sales', 'purchase',
    'products', 'child_category',
    'sub_category', 'category',
    'brand', 'customers',
    'suppliers', 'security_logs',
    'system_logs', 'users'
];

foreach ($tablesToDrop as $table) {
    $query = "DROP TABLE IF EXISTS `$table`";
    if (!mysqli_query($connection, $query)) {
        die("Error dropping table $table: " . mysqli_error($connection));
    }
}

// Create tables in proper order
$createTables = [
    // Users table
    "CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        profile_pic VARCHAR(255) NULL,
        username VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        role ENUM('admin','manager','cashier','inventory') NOT NULL DEFAULT 'cashier',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_login DATETIME NULL,
        last_login_ip VARCHAR(45) NULL,
        login_attempts TINYINT NOT NULL DEFAULT 0,
        locked_until DATETIME NULL,
        reset_token VARCHAR(100) NULL,
        reset_token_expires DATETIME NULL,
        email_verified TINYINT(1) NOT NULL DEFAULT 0,
        verification_token VARCHAR(100) NULL,
        password_changed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at TIMESTAMP NULL,
        UNIQUE INDEX idx_username (username, is_deleted),
        UNIQUE INDEX idx_email (email, is_deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Category tables
    "CREATE TABLE category (
        id INT PRIMARY KEY AUTO_INCREMENT,
        category VARCHAR(100) NOT NULL,
        details TEXT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE INDEX idx_category_name (category, is_deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE sub_category (
        id INT PRIMARY KEY AUTO_INCREMENT,
        category_id INT NOT NULL,
        category_name VARCHAR(100) NOT NULL,
        details TEXT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE CASCADE,
        UNIQUE INDEX idx_subcat_name (category_id, category_name, is_deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE child_category (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sub_category_id INT NOT NULL,
        category_name VARCHAR(100) NOT NULL,
        details TEXT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (sub_category_id) REFERENCES sub_category(id) ON DELETE CASCADE,
        UNIQUE INDEX idx_childcat_name (sub_category_id, category_name, is_deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Brand table
    "CREATE TABLE brand (
        id INT PRIMARY KEY AUTO_INCREMENT,
        brand_name VARCHAR(100) NOT NULL,
        details TEXT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE INDEX idx_brand_name (brand_name, is_deleted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Customers table
    "CREATE TABLE customers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NULL,
        email VARCHAR(100) NULL,
        address TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Suppliers table
    "CREATE TABLE suppliers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NULL,
        email VARCHAR(100) NULL,
        address TEXT NULL,
        company_name VARCHAR(100) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_name (name)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Products table
    "CREATE TABLE products (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        barcode VARCHAR(50) NOT NULL,
        category_id INT NULL,
        sub_category_id INT NULL,
        child_category_id INT NULL,
        brand_id INT NULL,
        price DECIMAL(10,2) NOT NULL,
        sell_price DECIMAL(10,2) NOT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES category(id) ON DELETE SET NULL,
        FOREIGN KEY (sub_category_id) REFERENCES sub_category(id) ON DELETE SET NULL,
        FOREIGN KEY (child_category_id) REFERENCES child_category(id) ON DELETE SET NULL,
        FOREIGN KEY (brand_id) REFERENCES brand(id) ON DELETE SET NULL,
        UNIQUE INDEX idx_barcode (barcode, is_deleted),
        FULLTEXT INDEX ft_search (name, barcode)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Purchase table
    "CREATE TABLE purchase (
        id INT PRIMARY KEY AUTO_INCREMENT,
        supplier_id INT NULL,
        reference_no VARCHAR(50) NULL,
        payment_method VARCHAR(20) DEFAULT 'cash',
        subtotal DECIMAL(10,2) DEFAULT 0,
        discount DECIMAL(5,2) DEFAULT 0,
        vat DECIMAL(10,2) NOT NULL DEFAULT 0,
        total DECIMAL(10,2) NOT NULL,
        user_id INT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_reference (reference_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Sales table
    "CREATE TABLE sales (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NULL,
        customer_name VARCHAR(100) NULL,
        customer_email VARCHAR(100) NULL,
        invoice_no VARCHAR(50) NULL,
        subtotal DECIMAL(10,2) DEFAULT 0,
        discount DECIMAL(5,2) DEFAULT 0,
        vat DECIMAL(10,2) NOT NULL DEFAULT 0,
        total DECIMAL(10,2) NOT NULL,
        payment_status ENUM('paid', 'partial', 'pending') DEFAULT 'paid',
        user_id INT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        UNIQUE INDEX idx_invoice (invoice_no)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Purchase items table
    "CREATE TABLE purchase_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        purchase_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (purchase_id) REFERENCES purchase(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Sale items table
    "CREATE TABLE sale_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sale_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Stock table
    "CREATE TABLE stock (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        user_id INT NULL,
        change_type ENUM('purchase', 'sale', 'adjustment', 'purchase_return', 'sales_return') NOT NULL,
        qty INT DEFAULT 0,
        price DECIMAL(10,2) NOT NULL,
        purchase_id INT NULL,
        sale_id INT NULL,
        adjustment_id INT NULL,
        purchase_return_id INT NULL,
        sales_return_id INT NULL,
        note TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        FOREIGN KEY (purchase_id) REFERENCES purchase(id) ON DELETE SET NULL,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Sales returns table
    "CREATE TABLE sales_returns (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sale_id INT NOT NULL,
        return_reason ENUM('defective','wrong_item','customer_change_mind','other') NOT NULL,
        return_note TEXT NULL,
        refund_amount DECIMAL(10,2) NOT NULL,
        refund_method ENUM('cash','credit','exchange') NOT NULL,
        user_id INT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE RESTRICT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Purchase returns table
    "CREATE TABLE purchase_returns (
        id INT PRIMARY KEY AUTO_INCREMENT,
        purchase_id INT NOT NULL,
        return_reason ENUM('defective','wrong_item','supplier_error','other') NOT NULL,
        return_note TEXT NULL,
        refund_amount DECIMAL(10,2) NOT NULL,
        refund_method ENUM('cash','credit','exchange') NOT NULL,
        user_id INT NULL,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (purchase_id) REFERENCES purchase(id) ON DELETE RESTRICT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Sales return items table
    "CREATE TABLE sales_return_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sales_return_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sales_return_id) REFERENCES sales_returns(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Purchase return items table
    "CREATE TABLE purchase_return_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        purchase_return_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        total_price DECIMAL(10,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (purchase_return_id) REFERENCES purchase_returns(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Sales payment table
    "CREATE TABLE sales_payment (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NULL,
        sales_id INT NULL,
        sales_return_id INT NULL,
        type ENUM('payment', 'return') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('cash', 'credit', 'card', 'bank_transfer') NOT NULL DEFAULT 'cash',
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
        FOREIGN KEY (sales_id) REFERENCES sales(id) ON DELETE SET NULL,
        FOREIGN KEY (sales_return_id) REFERENCES sales_returns(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Purchase payment table
    "CREATE TABLE purchase_payment (
        id INT PRIMARY KEY AUTO_INCREMENT,
        supplier_id INT NULL,
        purchase_id INT NULL,
        purchase_return_id INT NULL,
        type ENUM('payment', 'return') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('cash', 'credit', 'card', 'bank_transfer') NOT NULL DEFAULT 'cash',
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
        FOREIGN KEY (purchase_id) REFERENCES purchase(id) ON DELETE SET NULL,
        FOREIGN KEY (purchase_return_id) REFERENCES purchase_returns(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Inventory adjustments table
    "CREATE TABLE inventory_adjustments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        adjustment_type ENUM('add','remove') NOT NULL,
        quantity INT NOT NULL,
        reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // System logs table
    "CREATE TABLE system_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT NULL,
        category ENUM('auth','product','sale','stock','user','security','system') NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    // Security logs table
    "CREATE TABLE security_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NULL,
        ip_address VARCHAR(45) NOT NULL,
        action VARCHAR(50) NOT NULL,
        details TEXT NULL,
        status ENUM('success','failure') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($createTables as $query) {
    if (!mysqli_query($connection, $query)) {
        die("Error creating table: " . mysqli_error($connection));
    }
}

// Create views
$views = [
    "CREATE OR REPLACE VIEW current_stock AS
    SELECT 
      p.id AS product_id,
      p.name AS product_name,
      p.barcode,
      COALESCE(SUM(CASE 
        WHEN s.change_type = 'purchase' THEN s.qty
        WHEN s.change_type = 'sales_return' THEN s.qty
        WHEN s.change_type = 'purchase_return' THEN -s.qty
        WHEN s.change_type = 'sale' THEN -s.qty
        WHEN s.change_type = 'adjustment' THEN 
          CASE 
            WHEN (SELECT adjustment_type FROM inventory_adjustments WHERE id = s.adjustment_id) = 'add' THEN s.qty
            ELSE -s.qty
          END
      END), 0) AS current_quantity,
      p.price,
      p.sell_price
    FROM products p
    LEFT JOIN stock s ON p.id = s.product_id
    WHERE p.is_deleted = 0
    GROUP BY p.id, p.name, p.barcode, p.price, p.sell_price",

    "CREATE OR REPLACE VIEW sales_summary AS
    SELECT 
      DATE(s.created_at) AS sale_date,
      COUNT(*) AS total_sales,
      SUM(s.total) AS total_amount,
      SUM(s.discount) AS total_discount,
      SUM(s.vat) AS total_vat,
      (SELECT COUNT(*) FROM sales_returns sr WHERE DATE(sr.created_at) = DATE(s.created_at)) AS total_returns,
      (SELECT COALESCE(SUM(sr.refund_amount), 0) FROM sales_returns sr WHERE DATE(sr.created_at) = DATE(s.created_at)) AS total_refunds
    FROM sales s
    WHERE s.is_deleted = 0
    GROUP BY DATE(s.created_at)"
];

foreach ($views as $query) {
    if (!mysqli_query($connection, $query)) {
        die("Error creating view: " . mysqli_error($connection));
    }
}

// Create triggers
$triggers = [
    // Stock Update Triggers
    "DROP TRIGGER IF EXISTS after_purchase_item_insert",
    "CREATE TRIGGER after_purchase_item_insert
    AFTER INSERT ON purchase_items
    FOR EACH ROW
    BEGIN
        INSERT INTO stock (product_id, user_id, change_type, qty, price, purchase_id, created_at)
        VALUES (NEW.product_id, (SELECT user_id FROM purchase WHERE id = NEW.purchase_id), 
               'purchase', NEW.quantity, NEW.unit_price, NEW.purchase_id, NOW());
        
        UPDATE products 
        SET price = NEW.unit_price, 
            updated_at = NOW()
        WHERE id = NEW.product_id AND (price <> NEW.unit_price OR price IS NULL);
    END",

    "DROP TRIGGER IF EXISTS after_sale_item_insert",
    "CREATE TRIGGER after_sale_item_insert
    AFTER INSERT ON sale_items
    FOR EACH ROW
    BEGIN
        INSERT INTO stock (product_id, user_id, change_type, qty, price, sale_id, created_at)
        VALUES (NEW.product_id, (SELECT user_id FROM sales WHERE id = NEW.sale_id), 
               'sale', NEW.quantity, NEW.unit_price, NEW.sale_id, NOW());
    END",

    // Automatic Total Calculation Triggers
    "DROP TRIGGER IF EXISTS after_purchase_item_change",
    "CREATE TRIGGER after_purchase_item_change
    AFTER INSERT ON purchase_items
    FOR EACH ROW
    BEGIN
        DECLARE v_subtotal DECIMAL(10,2);
        DECLARE v_total DECIMAL(10,2);
        
        SELECT SUM(total_price), SUM(total_price) + COALESCE(MAX(vat), 0)
        INTO v_subtotal, v_total
        FROM purchase_items
        WHERE purchase_id = NEW.purchase_id;
        
        UPDATE purchase
        SET subtotal = v_subtotal,
            total = v_total,
            updated_at = NOW()
        WHERE id = NEW.purchase_id;
    END",

    "DROP TRIGGER IF EXISTS after_sale_item_change",
    "CREATE TRIGGER after_sale_item_change
    AFTER INSERT ON sale_items
    FOR EACH ROW
    BEGIN
        DECLARE v_subtotal DECIMAL(10,2);
        DECLARE v_total DECIMAL(10,2);
        DECLARE v_discount DECIMAL(5,2);
        
        SELECT discount INTO v_discount FROM sales WHERE id = NEW.sale_id;
        
        SELECT SUM(total_price), 
               SUM(total_price) - (SUM(total_price) * COALESCE(v_discount, 0)/100) + COALESCE(MAX(vat), 0)
        INTO v_subtotal, v_total
        FROM sale_items
        WHERE sale_id = NEW.sale_id;
        
        UPDATE sales
        SET subtotal = v_subtotal,
            total = v_total,
            updated_at = NOW()
        WHERE id = NEW.sale_id;
    END",

    // Inventory Alert Trigger
    "DROP TRIGGER IF EXISTS after_stock_update",
    "CREATE TRIGGER after_stock_update
    AFTER INSERT ON stock
    FOR EACH ROW
    BEGIN
        DECLARE v_current_qty INT;
        DECLARE v_product_name VARCHAR(100);
        DECLARE v_threshold INT DEFAULT 5;
        
        SELECT current_quantity INTO v_current_qty
        FROM current_stock
        WHERE product_id = NEW.product_id;
        
        SELECT name INTO v_product_name FROM products WHERE id = NEW.product_id;
        
        IF v_current_qty <= v_threshold THEN
            INSERT INTO system_logs (user_id, ip_address, category, message)
            VALUES (NEW.user_id, NULL, 'stock', 
                    CONCAT('Low stock alert: ', v_product_name, ' (', v_current_qty, ' remaining)'));
        END IF;
    END",

    // Sales Return Trigger
    "DROP TRIGGER IF EXISTS after_sales_return_item_insert",
    "CREATE TRIGGER after_sales_return_item_insert
    AFTER INSERT ON sales_return_items
    FOR EACH ROW
    BEGIN
        INSERT INTO stock (product_id, user_id, change_type, qty, price, sales_return_id, created_at)
        VALUES (NEW.product_id, (SELECT user_id FROM sales_returns WHERE id = NEW.sales_return_id), 
               'sales_return', NEW.quantity, NEW.unit_price, NEW.sales_return_id, NOW());
    END",

    // Price Validation Trigger
    "DROP TRIGGER IF EXISTS before_product_price_update",
    "CREATE TRIGGER before_product_price_update
    BEFORE UPDATE ON products
    FOR EACH ROW
    BEGIN
        IF NEW.sell_price < NEW.price THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Sell price cannot be lower than purchase price';
        END IF;
    END",

    // User Security Trigger
    "DROP TRIGGER IF EXISTS after_user_login_attempt",
    "CREATE TRIGGER after_user_login_attempt
    AFTER UPDATE ON users
    FOR EACH ROW
    BEGIN
        IF OLD.login_attempts <> NEW.login_attempts AND NEW.login_attempts >= 5 THEN
            INSERT INTO security_logs (user_id, ip_address, action, details, status)
            VALUES (NEW.id, NEW.last_login_ip, 'account_lock', 
                    'Account locked due to too many failed attempts', 'failure');
        END IF;
    END"
];

foreach ($triggers as $query) {
    if (!mysqli_query($connection, $query)) {
        die("Error creating trigger: " . mysqli_error($connection));
    }
}

echo "Database schema created successfully with all tables and relationships!";
mysqli_close($connection);*/
?>