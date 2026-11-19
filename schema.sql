-- DELTA DRUG STORE ERP - fresh installation schema
-- Create the database in cPanel first, then import this file into that database.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS audit_logs, stock_movements, sale_items, sales, purchase_items, purchases, expenses, products, categories, suppliers, customers, users, branches;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE branches (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, city VARCHAR(120) DEFAULT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(60) NOT NULL UNIQUE, full_name VARCHAR(150) NOT NULL,
 password_hash VARCHAR(255) NOT NULL, role ENUM('admin','pharmacist','cashier','storekeeper') NOT NULL DEFAULT 'cashier',
 branch_id INT NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_users_branch FOREIGN KEY(branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE categories (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL UNIQUE, description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;
CREATE TABLE suppliers (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, phone VARCHAR(40), email VARCHAR(150),
 address VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE customers (
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, phone VARCHAR(40), address VARCHAR(255),
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE products (
 id INT AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, category_id INT NULL, sku VARCHAR(60) NOT NULL,
 name VARCHAR(180) NOT NULL, unit VARCHAR(40) NOT NULL DEFAULT 'pcs', cost_price DECIMAL(12,2) NOT NULL DEFAULT 0,
 selling_price DECIMAL(12,2) NOT NULL DEFAULT 0, qty DECIMAL(12,3) NOT NULL DEFAULT 0,
 reorder_level DECIMAL(12,3) NOT NULL DEFAULT 5, barcode VARCHAR(100) DEFAULT NULL,
 batch_no VARCHAR(100) DEFAULT NULL, expiry_date DATE DEFAULT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_product_branch_sku(branch_id,sku),
 INDEX idx_products_name(name), INDEX idx_products_expiry(expiry_date),
 CONSTRAINT fk_products_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
 CONSTRAINT fk_products_category FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;
CREATE TABLE purchases (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, supplier_id INT NULL, invoice_no VARCHAR(80) NOT NULL,
 subtotal DECIMAL(12,2) NOT NULL DEFAULT 0, discount DECIMAL(12,2) NOT NULL DEFAULT 0,
 total DECIMAL(12,2) NOT NULL DEFAULT 0, status ENUM('received','draft','cancelled') NOT NULL DEFAULT 'received',
 notes TEXT, user_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_purchase_invoice(branch_id,invoice_no),
 FOREIGN KEY(branch_id) REFERENCES branches(id), FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
 FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE purchase_items (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, purchase_id BIGINT NOT NULL, product_id INT NOT NULL,
 qty DECIMAL(12,3) NOT NULL, unit_cost DECIMAL(12,2) NOT NULL, line_total DECIMAL(12,2) NOT NULL,
 FOREIGN KEY(purchase_id) REFERENCES purchases(id) ON DELETE CASCADE, FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;
CREATE TABLE sales (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, customer_id INT NULL, invoice_no VARCHAR(80) NOT NULL,
 subtotal DECIMAL(12,2) NOT NULL DEFAULT 0, discount DECIMAL(12,2) NOT NULL DEFAULT 0,
 total DECIMAL(12,2) NOT NULL DEFAULT 0, cost_total DECIMAL(12,2) NOT NULL DEFAULT 0,
 payment_method ENUM('cash','bank','mobile','credit') NOT NULL DEFAULT 'cash',
 user_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_sales_invoice(branch_id,invoice_no),
 FOREIGN KEY(branch_id) REFERENCES branches(id), FOREIGN KEY(customer_id) REFERENCES customers(id) ON DELETE SET NULL,
 FOREIGN KEY(user_id) REFERENCES users(id), INDEX idx_sales_date(created_at)
) ENGINE=InnoDB;
CREATE TABLE sale_items (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, sale_id BIGINT NOT NULL, product_id INT NOT NULL,
 qty DECIMAL(12,3) NOT NULL, unit_price DECIMAL(12,2) NOT NULL, unit_cost DECIMAL(12,2) NOT NULL,
 line_total DECIMAL(12,2) NOT NULL, FOREIGN KEY(sale_id) REFERENCES sales(id) ON DELETE CASCADE,
 FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB;
CREATE TABLE stock_movements (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, product_id INT NOT NULL,
 movement_type ENUM('opening','purchase','sale','adjustment','return') NOT NULL,
 reference_type VARCHAR(40), reference_id BIGINT NULL, qty_in DECIMAL(12,3) NOT NULL DEFAULT 0,
 qty_out DECIMAL(12,3) NOT NULL DEFAULT 0, balance_after DECIMAL(12,3) NOT NULL,
 note VARCHAR(255), user_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(branch_id) REFERENCES branches(id), FOREIGN KEY(product_id) REFERENCES products(id),
 FOREIGN KEY(user_id) REFERENCES users(id), INDEX idx_movement_product(product_id,created_at)
) ENGINE=InnoDB;
CREATE TABLE expenses (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, branch_id INT NOT NULL, category VARCHAR(100) NOT NULL,
 description VARCHAR(255), amount DECIMAL(12,2) NOT NULL, expense_date DATE NOT NULL,
 user_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(branch_id) REFERENCES branches(id), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE audit_logs (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, branch_id INT NULL, action VARCHAR(100) NOT NULL,
 details TEXT, ip_address VARCHAR(64), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 FOREIGN KEY(branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB;
INSERT INTO branches(name,city) VALUES('Main Branch','Jigjiga');
