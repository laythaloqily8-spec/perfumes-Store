-- ==========================================================================
-- Database and table setup for Adenor Perfume Store
-- Run this script to initialize the database.
-- ==========================================================================

CREATE DATABASE IF NOT EXISTS perfume_store
    DEFAULT CHARACTER SET utf8
    DEFAULT COLLATE utf8_general_ci;

USE perfume_store;

-- ==========================================================================
-- Existing migration: add email column for databases created before v2
-- ==========================================================================
-- ALTER TABLE users ADD COLUMN email VARCHAR(255) NOT NULL UNIQUE AFTER login;

-- ==========================================================================
-- Table: users
-- Stores registered customer accounts.
-- ==========================================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    login VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    credit_balance DECIMAL(10, 2) NOT NULL DEFAULT 1000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ==========================================================================
-- Table: perfumes
-- Product catalogue of available perfumes.
-- ==========================================================================
CREATE TABLE IF NOT EXISTS perfumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    category VARCHAR(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- For existing databases that already have a perfumes table without the category column:
-- ALTER TABLE perfumes ADD COLUMN category VARCHAR(50) DEFAULT NULL AFTER image;

-- ==========================================================================
-- Table: cart
-- Shopping cart items linked to a user and a perfume.
-- ==========================================================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    perfume_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ==========================================================================
-- Seed data: perfumes (6 default fragrances matching the JS fallback data)
-- ==========================================================================
INSERT IGNORE INTO perfumes (id, name, description, price, image, category) VALUES
(1, 'Opulent Herenius',  'A bold, rich blend of dark oud, dry wood, and desert sand chords.', 75.00, 'images/brown.jpg', 'luxury'),
(2, 'Lost Cherry Luxe',  'A luscious, full-bodied journey into the vibrant, sweet-tart cherry notes.', 90.00, 'images/red.jpg',   'women'),
(3, 'Baiciel Parrane',   'An ethereal, soft powdery mist layered over delicate white floral petals.', 65.00, 'images/white.jpg',  'unisex'),
(4, 'Spectrum Forest',   'Crisp pine needles fused with deep mossy tones and a touch of green warmth.', 55.00, 'images/green.jpg',  'men'),
(5, 'Parfum Homme Marine','An ocean breeze splash combined with fresh aquatic minerals and clean citrus zest.', 48.00, 'images/blue.jpg',   'men'),
(6, 'Rosé Clouds Elixir','A dreamlike pink vanilla cream cloud surrounding a luxurious blooming rose heart.', 80.00, 'images/pink.jpg',   'women');

-- ==========================================================================
-- Table: orders
-- Completed orders placed by users.
-- ==========================================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ==========================================================================
-- Table: order_items
-- Individual items belonging to a completed order.
-- ==========================================================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    perfume_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (perfume_id) REFERENCES perfumes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
