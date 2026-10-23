-- Desi Food Management System Database
-- Database: desi_food_db

CREATE DATABASE IF NOT EXISTS desi_food_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE desi_food_db;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS daily_cash;
DROP TABLE IF EXISTS salary_payments;
DROP TABLE IF EXISTS employee_advances;
DROP TABLE IF EXISTS employees;
DROP TABLE IF EXISTS expenses;
DROP TABLE IF EXISTS expense_categories;
DROP TABLE IF EXISTS supplier_payments;
DROP TABLE IF EXISTS purchase_items;
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS inventory_items;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Users
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role (role),
    INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- Settings
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Menu Categories
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Menu Items
CREATE TABLE menu_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    image VARCHAR(255) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_menu_status (status)
) ENGINE=InnoDB;

-- Orders
CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    order_date DATE NOT NULL,
    order_time TIME NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'other') NOT NULL DEFAULT 'cash',
    status ENUM('completed', 'cancelled') NOT NULL DEFAULT 'completed',
    notes TEXT,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_orders_date (order_date),
    INDEX idx_orders_status (status),
    INDEX idx_orders_payment (payment_method)
) ENGINE=InnoDB;

-- Order Items
CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    menu_item_id INT UNSIGNED NULL,
    item_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL,
    INDEX idx_order_items_order (order_id)
) ENGINE=InnoDB;

-- Inventory Items
CREATE TABLE inventory_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'General',
    current_quantity DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    unit VARCHAR(30) NOT NULL DEFAULT 'kg',
    minimum_stock DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    average_purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    last_purchase_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_inventory_category (category),
    INDEX idx_inventory_status (status)
) ENGINE=InnoDB;

-- Stock Movements
CREATE TABLE stock_movements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inventory_item_id INT UNSIGNED NOT NULL,
    movement_type ENUM('purchase', 'usage', 'wastage', 'adjustment', 'correction') NOT NULL,
    quantity DECIMAL(10,3) NOT NULL,
    unit_price DECIMAL(10,2) NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT UNSIGNED NULL,
    notes TEXT,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_stock_item (inventory_item_id),
    INDEX idx_stock_type (movement_type),
    INDEX idx_stock_date (created_at)
) ENGINE=InnoDB;

-- Suppliers
CREATE TABLE suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    opening_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_suppliers_status (status)
) ENGINE=InnoDB;

-- Purchases
CREATE TABLE purchases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_number VARCHAR(20) NOT NULL UNIQUE,
    purchase_date DATE NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    remaining DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('paid', 'partial', 'unpaid') NOT NULL DEFAULT 'unpaid',
    notes TEXT,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_purchases_date (purchase_date),
    INDEX idx_purchases_supplier (supplier_id),
    INDEX idx_purchases_status (payment_status)
) ENGINE=InnoDB;

-- Purchase Items
CREATE TABLE purchase_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT UNSIGNED NOT NULL,
    inventory_item_id INT UNSIGNED NOT NULL,
    item_name VARCHAR(150) NOT NULL,
    quantity DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    unit VARCHAR(30) NOT NULL DEFAULT 'kg',
    rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_item_id) REFERENCES inventory_items(id) ON DELETE RESTRICT,
    INDEX idx_purchase_items_purchase (purchase_id)
) ENGINE=InnoDB;

-- Supplier Payments
CREATE TABLE supplier_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT UNSIGNED NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'other') NOT NULL DEFAULT 'cash',
    reference VARCHAR(100),
    notes TEXT,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_supplier_payments_date (payment_date),
    INDEX idx_supplier_payments_supplier (supplier_id)
) ENGINE=InnoDB;

-- Expense Categories
CREATE TABLE expense_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Expenses
CREATE TABLE expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    expense_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    description TEXT,
    payment_method ENUM('cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'other') NOT NULL DEFAULT 'cash',
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES expense_categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_expenses_date (expense_date),
    INDEX idx_expenses_category (category_id)
) ENGINE=InnoDB;

-- Employees
CREATE TABLE employees (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    cnic VARCHAR(20) NULL,
    designation VARCHAR(100),
    joining_date DATE,
    monthly_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employees_status (status)
) ENGINE=InnoDB;

-- Employee Advances
CREATE TABLE employee_advances (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    advance_date DATE NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_advances_employee (employee_id),
    INDEX idx_advances_date (advance_date)
) ENGINE=InnoDB;

-- Salary Payments
CREATE TABLE salary_payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id INT UNSIGNED NOT NULL,
    salary_month VARCHAR(7) NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    advance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    bonus DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    deduction DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    net_payable DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    remaining DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_date DATE NULL,
    payment_method ENUM('cash', 'bank_transfer', 'easypaisa', 'jazzcash', 'other') NULL,
    status ENUM('paid', 'partial', 'unpaid') NOT NULL DEFAULT 'unpaid',
    notes TEXT,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uk_employee_month (employee_id, salary_month),
    INDEX idx_salary_month (salary_month),
    INDEX idx_salary_status (status)
) ENGINE=InnoDB;

-- Daily Cash
CREATE TABLE daily_cash (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cash_date DATE NOT NULL UNIQUE,
    opening_cash DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    cash_sales DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    other_income DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    supplier_payments DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    employee_payments DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    expenses_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    expected_closing DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    actual_closing DECIMAL(10,2) NULL,
    difference DECIMAL(10,2) NULL,
    notes TEXT,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_daily_cash_date (cash_date)
) ENGINE=InnoDB;

-- Activity Logs
CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_logs_user (user_id),
    INDEX idx_logs_module (module),
    INDEX idx_logs_date (created_at)
) ENGINE=InnoDB;

-- Seed Data

INSERT INTO users (username, password, full_name, role, status) VALUES
('admin', '$2y$10$bFqxivDv5L5/RRELwjZl3OYvSBCS.gRIf5ezh3bOtFINsrQ0fQ1y6', 'System Administrator', 'admin', 'active');

INSERT INTO settings (setting_key, setting_value) VALUES
('shop_name', 'Desi Food Shop'),
('shop_address', 'Main Bazar, Lahore, Pakistan'),
('shop_phone', '+92-300-1234567'),
('currency_symbol', 'Rs.'),
('receipt_footer', 'Thank you for visiting Desi Food Shop!'),
('order_prefix', 'ORD'),
('purchase_prefix', 'PUR'),
('last_order_number', '0'),
('last_purchase_number', '0');

INSERT INTO categories (name, description, status) VALUES
('Main Dishes', 'Popular desi food items', 'active'),
('Beverages', 'Drinks and refreshments', 'active');

INSERT INTO menu_items (category_id, name, price, description, status) VALUES
(1, 'Kashmiri Chola', 250.00, 'Traditional Kashmiri style chola', 'active'),
(1, 'Haleem', 300.00, 'Rich and flavorful haleem', 'active'),
(1, 'Chicken Biryani', 350.00, 'Aromatic chicken biryani', 'active'),
(1, 'Naan', 50.00, 'Fresh tandoori naan', 'active'),
(2, 'Lassi', 100.00, 'Sweet or salty lassi', 'active'),
(2, 'Chai', 50.00, 'Hot Pakistani chai', 'active');

INSERT INTO expense_categories (name, status) VALUES
('Gas', 'active'),
('Electricity', 'active'),
('Rent', 'active'),
('Salary', 'active'),
('Transport', 'active'),
('Maintenance', 'active'),
('Cleaning', 'active'),
('Packaging', 'active'),
('Marketing', 'active'),
('Miscellaneous', 'active');

INSERT INTO inventory_items (name, category, current_quantity, unit, minimum_stock, average_purchase_price, last_purchase_price, status) VALUES
('Chana', 'Pulses', 50.000, 'kg', 10.000, 180.00, 180.00, 'active'),
('Daal', 'Pulses', 30.000, 'kg', 10.000, 220.00, 220.00, 'active'),
('Meat', 'Protein', 20.000, 'kg', 5.000, 1200.00, 1200.00, 'active'),
('Cooking Oil', 'Oil', 25.000, 'liter', 5.000, 450.00, 450.00, 'active'),
('Ghee', 'Oil', 10.000, 'kg', 2.000, 800.00, 800.00, 'active'),
('Flour', 'Grains', 100.000, 'kg', 20.000, 90.00, 90.00, 'active'),
('Rice', 'Grains', 80.000, 'kg', 15.000, 150.00, 150.00, 'active'),
('Masala', 'Spices', 15.000, 'kg', 3.000, 500.00, 500.00, 'active'),
('Onion', 'Vegetables', 40.000, 'kg', 10.000, 80.00, 80.00, 'active'),
('Tomato', 'Vegetables', 25.000, 'kg', 5.000, 100.00, 100.00, 'active'),
('Green Chili', 'Vegetables', 5.000, 'kg', 1.000, 120.00, 120.00, 'active'),
('Gas Cylinder', 'Utilities', 3.000, 'piece', 1.000, 3500.00, 3500.00, 'active'),
('Packaging', 'Supplies', 500.000, 'piece', 100.000, 5.00, 5.00, 'active'),
('Disposable Plates', 'Supplies', 200.000, 'piece', 50.000, 8.00, 8.00, 'active'),
('Tissue', 'Supplies', 50.000, 'pack', 10.000, 150.00, 150.00, 'active');

INSERT INTO suppliers (name, contact_person, phone, address, opening_balance, status) VALUES
('General Store Supplier', 'Ahmed Khan', '0300-1111111', 'Wholesale Market, Lahore', 0.00, 'active'),
('Meat Supplier', 'Bilal Ahmed', '0300-2222222', 'Meat Market, Lahore', 0.00, 'active'),
('Gas Supplier', 'Tariq Hussain', '0300-3333333', 'Industrial Area, Lahore', 0.00, 'active');

INSERT INTO employees (name, phone, cnic, designation, joining_date, monthly_salary, status, address, notes) VALUES
('Muhammad Ali', '0300-4444444', '35202-1234567-1', 'Chef', '2024-01-15', 45000.00, 'active', 'Lahore', 'Head chef'),
('Hassan Raza', '0300-5555555', '35202-7654321-1', 'Helper', '2024-03-01', 25000.00, 'active', 'Lahore', 'Kitchen helper');
