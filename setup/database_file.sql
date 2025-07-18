-- CREATE DATABASE online_shopping_db;

-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- -----------------------------------------------------


-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS subcategories (
    subcategory_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    subcategory_name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);
-- -----------------------------------------------------


-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS products(
	product_id INT AUTO_INCREMENT PRIMARY KEY,
    subcategory_id INT NOT NULL,
	product_name VARCHAR(200) NOT NULL UNIQUE,
    product_description TEXT NOT NULL,
	product_price DECIMAL(9,2) NOT NULL,
	product_qoh int(11) NOT NULL,
	product_img TEXT NOT NULL,
    has_variants BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(subcategory_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);
-- -----------------------------------------------------


-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS product_variants (
    variant_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_name VARCHAR(100) NOT NULL,     -- e.g., '1L', '2m'
    variant_price DECIMAL(9,2) NOT NULL,
    variant_qoh INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);
-- -----------------------------------------------------


-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
-- -----------------------------------------------------

-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT,
    user_name VARCHAR(100) NOT NULL,
    user_password VARCHAR(100) NOT NULL,
    user_email VARCHAR(100) UNIQUE,
    user_address VARCHAR(100) UNIQUE,
    user_telephone_no VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP, 
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);
-- -----------------------------------------------------

-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS customer_orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY ,
    customer_id INT,
    total_amount DECIMAL(9, 2) NOT NULL, 
    order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    order_status VARCHAR(100) NOT NULL,
    date_shipped DATE,  
    date_delivered DATE, 
    FOREIGN KEY (customer_id) REFERENCES users(user_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);
-- ----------------------------------------------------------------



-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_products (
    order_product_id INT AUTO_INCREMENT PRIMARY KEY, 
    order_id INT,
    product_id INT NOT NULL,           -- Always set, even for variants
    variant_id INT NULL,               -- NULL for non-variant items
    quantity INT NOT NULL,
    sub_total_amount DECIMAL(9, 2) NOT NULL, 
    FOREIGN KEY (order_id) REFERENCES customer_orders(order_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
);
-- ----------------------------------------------------------------