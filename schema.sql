CREATE DATABASE IF NOT EXISTS ecozin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecozin;
CREATE TABLE IF NOT EXISTS users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, full_name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL,
 role ENUM('farmer','business','admin') NOT NULL, profile_data JSON,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS waste_listings (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, farmer_id INT UNSIGNED NOT NULL,
 product_type ENUM('Pomegranate','Walnut','Olive Pomace') NOT NULL,
 quantity_tons DECIMAL(10,3) NOT NULL, price_per_ton DECIMAL(12,2) NOT NULL DEFAULT 0,
 location_lat DECIMAL(10,7) NOT NULL, location_lng DECIMAL(10,7) NOT NULL,
 description TEXT NOT NULL, photo_url VARCHAR(255), status ENUM('available','reserved','sold') DEFAULT 'available',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(farmer_id) REFERENCES users(id), INDEX(status,product_type), CHECK(quantity_tons>0)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS matches_and_ads (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id INT UNSIGNED NOT NULL, farmer_id INT UNSIGNED NOT NULL,
 matched_product_id INT UNSIGNED NOT NULL, ai_score DECIMAL(5,2), ad_message TEXT,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(business_id) REFERENCES users(id), FOREIGN KEY(farmer_id) REFERENCES users(id),
 FOREIGN KEY(matched_product_id) REFERENCES waste_listings(id) ON DELETE CASCADE,
 UNIQUE(business_id,matched_product_id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS messages (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, sender_id INT UNSIGNED NOT NULL, receiver_id INT UNSIGNED NOT NULL,
 message_text TEXT, media_url VARCHAR(255), message_type ENUM('text','image','voice','video','document') DEFAULT 'text',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(sender_id) REFERENCES users(id), FOREIGN KEY(receiver_id) REFERENCES users(id), INDEX(sender_id,receiver_id,id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS subscriptions (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, business_id INT UNSIGNED NOT NULL, farmer_id INT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE(business_id,farmer_id),
 FOREIGN KEY(business_id) REFERENCES users(id), FOREIGN KEY(farmer_id) REFERENCES users(id)
) ENGINE=InnoDB;
