
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

-- 10. ADMIN OPERATIONS TABLE
CREATE TABLE IF NOT EXISTS admin_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    admin_name VARCHAR(100) NOT NULL DEFAULT 'Admin',
    operation_type VARCHAR(50) NOT NULL,
    target_type VARCHAR(50) NOT NULL DEFAULT '',
    target_id INT NULL DEFAULT NULL,
    description TEXT NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (admin_id),
    INDEX (operation_type),
    INDEX (created_at)
);

-- 11. EXTENDED 12 DESTINATIONS & ACTIVITIES (Total 18)
INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Badami', 'Bagalkote', id, 'Famous for spectacular 6th-century rock-cut cave temples carved into red sandstone cliffs overlooking Lake Agastya.', 'https://images.unsplash.com/photo-1600100397608-f404fa3a36f9?auto=format&fit=crop&w=800&q=80', 2400.00 FROM categories WHERE name = 'Heritage';

INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Dandeli', 'Uttara Kannada', id, 'Premier adventure destination renowned for white-water rafting on the Kali River, wildlife sanctuaries, and jungle treks.', 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80', 3800.00 FROM categories WHERE name = 'Adventure';

INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Udupi', 'Udupi', id, 'Coastal cultural gem celebrated for the sacred Krishna Matha, pristine Malpe beach, and legendary South Indian cuisine.', 'https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=800&q=80', 2600.00 FROM categories WHERE name = 'Food & Culture';

INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Belur & Halebidu', 'Hassan', id, 'Twin ancient capitals showcasing the pinnacle of 12th-century Hoysala architecture with intricately carved star-shaped temples.', 'https://images.unsplash.com/photo-1621252179027-94459d278660?auto=format&fit=crop&w=800&q=80', 2500.00 FROM categories WHERE name = 'Heritage';

INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Jog Falls', 'Shivamogga', id, 'One of Indias most magnificent plunge waterfalls, dropping 253 meters in four distinct cascades amidst dense Western Ghats.', 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80', 2900.00 FROM categories WHERE name = 'Nature';

INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Bandipur National Park', 'Chamarajanagar', id, 'Famous Project Tiger reserve within the Nilgiri Biosphere, home to elephants, tigers, leopards, and dense teak forests.', 'https://images.unsplash.com/photo-1534177616072-ef7dc120449d?auto=format&fit=crop&w=800&q=80', 4200.00 FROM categories WHERE name = 'Adventure';

INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Kabini', 'Mysuru', id, 'Enchanting backwaters surrounded by Nagarhole forest, famed for high density of big cats and serene boat safaris.', 'https://images.unsplash.com/photo-1516426122078-c23e76319801?auto=format&fit=crop&w=800&q=80', 4500.00 FROM categories WHERE name = 'Nature';

INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Murudeshwar', 'Uttara Kannada', id, 'Iconic coastal pilgrimage town featuring the worlds 2nd tallest Lord Shiva statue perched against the azure Arabian Sea.', 'https://images.unsplash.com/photo-1582510003544-4d00b7f74220?auto=format&fit=crop&w=800&q=80', 2800.00 FROM categories WHERE name = 'Beaches';

INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Pattadakal', 'Bagalkote', id, 'UNESCO World Heritage complex showcasing a harmonious blend of Northern Nagara and Southern Dravidian temple architecture.', 'https://images.unsplash.com/photo-1590050752117-238cb0fb12b1?auto=format&fit=crop&w=800&q=80', 2300.00 FROM categories WHERE name = 'Heritage';

INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Vijayapura', 'Vijayapura', id, 'Historic capital of the Adil Shahi dynasty, famous for Gol Gumbaz with its miraculous acoustic whispering gallery dome.', 'https://images.unsplash.com/photo-1599661046827-dacff0c0f09a?auto=format&fit=crop&w=800&q=80', 2200.00 FROM categories WHERE name = 'Heritage';

INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Sakleshpur', 'Hassan', id, 'Misty Western Ghats hill station enveloped in coffee plantations, emerald mountain ridges, and the iconic star-shaped fort.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80', 3100.00 FROM categories WHERE name = 'Nature';

INSERT IGNORE INTO destinations (name, district, category_id, description, image_url, estimated_cost)
SELECT 'Nandi Hills', 'Chikkaballapur', id, 'Iconic ancient hilltop fortress near Bengaluru renowned for breathtaking sea-of-clouds sunrises and refreshing climate.', 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80', 1800.00 FROM categories WHERE name = 'Relaxation';

