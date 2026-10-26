CREATE DATABASE IF NOT EXISTS travel_companion
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE travel_companion;

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    profile_picture VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. CATEGORIES TABLE
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE
);

-- 3. DESTINATIONS TABLE
CREATE TABLE IF NOT EXISTS destinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    district VARCHAR(100) NOT NULL,
    category_id INT NULL,
    description TEXT,
    image_url VARCHAR(500) DEFAULT '',
    estimated_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE SET NULL
);

-- 4. ACTIVITIES TABLE
CREATE TABLE IF NOT EXISTS activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    destination_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT,
    estimated_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (destination_id)
        REFERENCES destinations(id)
        ON DELETE CASCADE
);

-- 5. TRIPS TABLE
CREATE TABLE IF NOT EXISTS trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    destination_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    duration_days INT NOT NULL DEFAULT 1,
    group_size INT NOT NULL DEFAULT 1,
    budget DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM(
        'draft',
        'upcoming',
        'active',
        'past'
    ) NOT NULL DEFAULT 'draft',
    travel_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    accommodation_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    food_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    other_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    FOREIGN KEY (destination_id)
        REFERENCES destinations(id)
        ON DELETE RESTRICT
);

-- 6. TRIP ACTIVITIES TABLE
CREATE TABLE IF NOT EXISTS trip_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    activity_id INT NOT NULL,
    day_number INT NOT NULL DEFAULT 1,
    visit_time VARCHAR(20) DEFAULT '',
    FOREIGN KEY (trip_id)
        REFERENCES trips(id)
        ON DELETE CASCADE,
    FOREIGN KEY (activity_id)
        REFERENCES activities(id)
        ON DELETE CASCADE
);

-- 7. EXPENSES TABLE
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(80) NOT NULL,
    estimated_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    actual_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id)
        REFERENCES trips(id)
        ON DELETE CASCADE
);

-- INSERT CATEGORIES
INSERT IGNORE INTO categories (name)
VALUES
    ('Nature'),
    ('Adventure'),
    ('Heritage'),
    ('Beaches'),
    ('Relaxation'),
    ('Food & Culture');

-- INSERT DESTINATIONS WITH RELATIVE IMAGE PATHS

INSERT INTO destinations
    (name, district, category_id, description,
     image_url, estimated_cost)
SELECT
    'Coorg',
    'Kodagu',
    id,
    'Known for coffee plantations, waterfalls, hills and outdoor activities.',
    'images/destinations/coorg.jpg',
    3500
FROM categories
WHERE name = 'Nature'
AND NOT EXISTS (
    SELECT 1 FROM destinations WHERE name = 'Coorg'
);

INSERT INTO destinations
    (name, district, category_id, description,
     image_url, estimated_cost)
SELECT
    'Mysuru',
    'Mysuru',
    id,
    'Explore the palace, heritage architecture, markets and local food.',
    'images/destinations/mysuru.jpg',
    2500
FROM categories
WHERE name = 'Heritage'
AND NOT EXISTS (
    SELECT 1 FROM destinations WHERE name = 'Mysuru'
);

INSERT INTO destinations
    (name, district, category_id, description,
     image_url, estimated_cost)
SELECT
    'Gokarna',
    'Uttara Kannada',
    id,
    'A coastal destination known for beaches and relaxed stays.',
    'images/destinations/gokarna.jpg',
    3000
FROM categories
WHERE name = 'Beaches'
AND NOT EXISTS (
    SELECT 1 FROM destinations WHERE name = 'Gokarna'
);

INSERT INTO destinations
    (name, district, category_id, description,
     image_url, estimated_cost)
SELECT
    'Chikkamagaluru',
    'Chikkamagaluru',
    id,
    'Hill views, coffee estates and nature-oriented outings.',
    'images/destinations/chikmagaluru.jpg',
    3200
FROM categories
WHERE name = 'Nature'
AND NOT EXISTS (
    SELECT 1 FROM destinations WHERE name = 'Chikkamagaluru'
);

INSERT INTO destinations
    (name, district, category_id, description,
     image_url, estimated_cost)
SELECT
    'Hampi',
    'Vijayanagara',
    id,
    'Historic monuments, ruins and remarkable stone architecture.',
    'images/destinations/hampi.jpg',
    2800
FROM categories
WHERE name = 'Heritage'
AND NOT EXISTS (
    SELECT 1 FROM destinations WHERE name = 'Hampi'
);

INSERT INTO destinations
    (name, district, category_id, description,
     image_url, estimated_cost)
SELECT
    'Mangaluru',
    'Dakshina Kannada',
    id,
    'Coastal city with beaches, temples and distinctive local cuisine.',
    'images/destinations/mangaluru.jpg',
    2200
FROM categories
WHERE name = 'Food & Culture'
AND NOT EXISTS (
    SELECT 1 FROM destinations WHERE name = 'Mangaluru'
);

-- INSERT ACTIVITIES

INSERT INTO activities
    (destination_id, name, description, estimated_cost)
SELECT
    d.id,
    'Waterfall visit',
    'Visit a nearby waterfall or scenic spot.',
    500
FROM destinations d
WHERE d.name = 'Coorg'
AND NOT EXISTS (
    SELECT 1 FROM activities a
    WHERE a.destination_id = d.id
    AND a.name = 'Waterfall visit'
);

INSERT INTO activities
    (destination_id, name, description, estimated_cost)
SELECT
    d.id,
    'Coffee estate walk',
    'Explore a coffee estate.',
    400
FROM destinations d
WHERE d.name = 'Coorg'
AND NOT EXISTS (
    SELECT 1 FROM activities a
    WHERE a.destination_id = d.id
    AND a.name = 'Coffee estate walk'
);

INSERT INTO activities
    (destination_id, name, description, estimated_cost)
SELECT
    d.id,
    'Palace visit',
    'Explore the historic palace.',
    200
FROM destinations d
WHERE d.name = 'Mysuru'
AND NOT EXISTS (
    SELECT 1 FROM activities a
    WHERE a.destination_id = d.id
    AND a.name = 'Palace visit'
);

INSERT INTO activities
    (destination_id, name, description, estimated_cost)
SELECT
    d.id,
    'Beach visit',
    'Spend time at the beach.',
    0
FROM destinations d
WHERE d.name = 'Gokarna'
AND NOT EXISTS (
    SELECT 1 FROM activities a
    WHERE a.destination_id = d.id
    AND a.name = 'Beach visit'
);

INSERT INTO activities
    (destination_id, name, description, estimated_cost)
SELECT
    d.id,
    'Hill viewpoint',
    'Visit a scenic viewpoint.',
    300
FROM destinations d
WHERE d.name = 'Chikkamagaluru'
AND NOT EXISTS (
    SELECT 1 FROM activities a
    WHERE a.destination_id = d.id
    AND a.name = 'Hill viewpoint'
);

INSERT INTO activities
    (destination_id, name, description, estimated_cost)
SELECT
    d.id,
    'Heritage walk',
    'Explore historic monuments.',
    250
FROM destinations d
WHERE d.name = 'Hampi'
AND NOT EXISTS (
    SELECT 1 FROM activities a
    WHERE a.destination_id = d.id
    AND a.name = 'Heritage walk'
);

SET SQL_SAFE_UPDATES = 0;
UPDATE destinations
SET image_url = CASE
    WHEN name = 'Chikkamagaluru'
        THEN 'images/Chikkamagaluru.avif'
    WHEN name = 'Coorg'
        THEN 'images/Coorg.jpg'
    WHEN name = 'Gokarna'
        THEN 'images/Gokarna.jpg'
    WHEN name = 'Hampi'
        THEN 'images/Hampi.jpg'
    WHEN name = 'Mangaluru'
        THEN 'images/Mangaluru.avif'
    WHEN name = 'Mysuru'
        THEN 'images/Mysore.jpeg'
    ELSE image_url
END
WHERE name IN (
    'Chikkamagaluru',
    'Coorg',
    'Gokarna',
    'Hampi',
    'Mangaluru',
    'Mysuru'
);
SET SQL_SAFE_UPDATES = 1;

ALTER TABLE trips
ADD COLUMN start_date DATE NULL,
ADD COLUMN end_date DATE NULL;

-- 8. EMAIL OTPS TABLE
CREATE TABLE IF NOT EXISTS email_otps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    otp VARCHAR(10) NOT NULL,
    purpose ENUM('register', 'forgot_password') NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(email),
    INDEX(expires_at)
);

-- 9. ENQUIRIES TABLE
CREATE TABLE IF NOT EXISTS enquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    destination_id INT NOT NULL,
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    admin_reply TEXT NULL,
    status ENUM('pending', 'replied') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    replied_at DATETIME NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
);