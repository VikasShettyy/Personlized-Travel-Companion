<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Ensure the full list of 18 Karnataka destinations and their activities are populated.
 */
function ensure_extended_destinations_seeded(PDO $pdo): void
{
    static $seeded = false;
    if ($seeded) {
        return;
    }

    try {
        $count = (int) $pdo->query('SELECT COUNT(*) FROM destinations')->fetchColumn();
        if ($count >= 15) {
            $seeded = true;
            return;
        }

        $extraDestinations = [
            [
                'name' => 'Badami',
                'district' => 'Bagalkote',
                'category' => 'Heritage',
                'desc' => 'Famous for spectacular 6th-century rock-cut cave temples carved into red sandstone cliffs overlooking Lake Agastya.',
                'image' => 'https://images.unsplash.com/photo-1600100397608-f404fa3a36f9?auto=format&fit=crop&w=800&q=80',
                'cost' => 2400.00,
                'activities' => [
                    ['Cave Temples Exploration', 'Explore the 4 rock-cut cave temples featuring Chalukya architecture.', 250.00],
                    ['Bhutanatha Temple Walk', 'Historic temple complex on the edge of serene Agastya Lake.', 150.00],
                    ['Agastya Lake Sunset View', 'Enjoy panoramic sunset reflections over red sandstone cliffs.', 100.00]
                ]
            ],
            [
                'name' => 'Dandeli',
                'district' => 'Uttara Kannada',
                'category' => 'Adventure',
                'desc' => 'Premier adventure destination renowned for white-water rafting on the Kali River, wildlife sanctuaries, and jungle treks.',
                'image' => 'https://images.unsplash.com/photo-1544551763-46a013bb70d5?auto=format&fit=crop&w=800&q=80',
                'cost' => 3800.00,
                'activities' => [
                    ['White Water Rafting', 'Exhilarating rafting through Grade 2 and 3 rapids on Kali River.', 1200.00],
                    ['Jungle Wildlife Safari', 'Spot hornbills, deer, and black panthers in Dandeli Sanctuary.', 650.00],
                    ['Kayaking & Natural Jacuzzi', 'Glide along scenic river channels and relax in forest rapids.', 450.00]
                ]
            ],
            [
                'name' => 'Udupi',
                'district' => 'Udupi',
                'category' => 'Food & Culture',
                'desc' => 'Coastal cultural gem celebrated for the sacred Krishna Matha, pristine Malpe beach, and legendary South Indian cuisine.',
                'image' => 'https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=800&q=80',
                'cost' => 2600.00,
                'activities' => [
                    ['Sri Krishna Matha Darshan', 'Visit the centuries-old temple with the iconic silver Kanakana Kindi.', 100.00],
                    ['Malpe & St. Marys Island', 'Ferry ride to unique volcanic columnar basalt rock formations.', 400.00],
                    ['Traditional Coastal Food Trail', 'Taste authentic masala dosa, goli baje, and coastal dishes.', 350.00]
                ]
            ],
            [
                'name' => 'Belur & Halebidu',
                'district' => 'Hassan',
                'category' => 'Heritage',
                'desc' => 'Twin ancient capitals showcasing the pinnacle of 12th-century Hoysala architecture with intricately carved star-shaped temples.',
                'image' => 'https://images.unsplash.com/photo-1621252179027-94459d278660?auto=format&fit=crop&w=800&q=80',
                'cost' => 2500.00,
                'activities' => [
                    ['Chennakeshava Temple Tour', 'Admire 10,000+ detailed stone carvings depicting epics and dance.', 200.00],
                    ['Hoysaleswara Sculpture Walk', 'Guided exploration of stone friezes and monumental Nandi statues.', 150.00],
                    ['Halebidu Archaeological Museum', 'View preserved 12th-century artifacts and Hoysala relics.', 100.00]
                ]
            ],
            [
                'name' => 'Jog Falls',
                'district' => 'Shivamogga',
                'category' => 'Nature',
                'desc' => 'One of Indias most magnificent plunge waterfalls, dropping 253 meters in four distinct cascades amidst dense Western Ghats.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
                'cost' => 2900.00,
                'activities' => [
                    ['Waterfall Viewpoint Trek', 'Descend the 1,400 steps for a close-up mist view of Raja & Rani falls.', 300.00],
                    ['Sharavathi Valley Nature Walk', 'Hike through lush rain-forest paths filled with indigenous birds.', 250.00],
                    ['Linganamakki Dam Overlook', 'Scenic viewpoint overlooking Karnataka’s largest hydro reservoir.', 100.00]
                ]
            ],
            [
                'name' => 'Bandipur National Park',
                'district' => 'Chamarajanagar',
                'category' => 'Adventure',
                'desc' => 'Famous Project Tiger reserve within the Nilgiri Biosphere, home to elephants, tigers, leopards, and dense teak forests.',
                'image' => 'https://images.unsplash.com/photo-1534177616072-ef7dc120449d?auto=format&fit=crop&w=800&q=80',
                'cost' => 4200.00,
                'activities' => [
                    ['Tiger Reserve Jeep Safari', 'Early morning forest drive guided by certified forest trackers.', 850.00],
                    ['Elephant Sanctuary Visit', 'Observe Asian elephant herds in their natural forest corridors.', 300.00],
                    ['Gopalswamy Betta Viewpoint', 'Drive to the highest peak in Bandipur with misty panoramic vistas.', 200.00]
                ]
            ],
            [
                'name' => 'Kabini',
                'district' => 'Mysuru',
                'category' => 'Nature',
                'desc' => 'Enchanting backwaters surrounded by Nagarhole forest, famed for high density of big cats and serene boat safaris.',
                'image' => 'https://images.unsplash.com/photo-1516426122078-c23e76319801?auto=format&fit=crop&w=800&q=80',
                'cost' => 4500.00,
                'activities' => [
                    ['Kabini Riverboat Safari', 'Cruise the backwaters spotting marsh crocodiles and elephants drinking.', 950.00],
                    ['Nagarhole Forest Trail', 'Guided wildlife trail along the edge of the teak jungle.', 750.00],
                    ['Birdwatching & Stargazing', 'Spot 250+ avian species by dusk and clear night skies.', 300.00]
                ]
            ],
            [
                'name' => 'Murudeshwar',
                'district' => 'Uttara Kannada',
                'category' => 'Beaches',
                'desc' => 'Iconic coastal pilgrimage town featuring the worlds 2nd tallest Lord Shiva statue perched against the azure Arabian Sea.',
                'image' => 'https://images.unsplash.com/photo-1582510003544-4d00b7f74220?auto=format&fit=crop&w=800&q=80',
                'cost' => 2800.00,
                'activities' => [
                    ['Shiva Statue & Raja Gopura Lift', 'Take the 18-storey elevator to top of the tower for sea views.', 150.00],
                    ['Netrani Scuba Diving', 'Speedboat ride to Netrani Island for coral reef scuba & snorkeling.', 1500.00],
                    ['Sunset Beach Walk', 'Relax along the crescent beach overlooking temple hill.', 50.00]
                ]
            ],
            [
                'name' => 'Pattadakal',
                'district' => 'Bagalkote',
                'category' => 'Heritage',
                'desc' => 'UNESCO World Heritage complex showcasing a harmonious blend of Northern Nagara and Southern Dravidian temple architecture.',
                'image' => 'https://images.unsplash.com/photo-1590050752117-238cb0fb12b1?auto=format&fit=crop&w=800&q=80',
                'cost' => 2300.00,
                'activities' => [
                    ['UNESCO Monument Guided Walk', 'Visit 8th-century royal coronation temples along the river.', 200.00],
                    ['Virupaksha Temple Architecture', 'Inspect intricate stone pillars detailing Ramayana & Mahabharata.', 150.00],
                    ['Malaprabha River Heritage Walk', 'Riverside sunset walk near ancient ceremonial bathing ghats.', 100.00]
                ]
            ],
            [
                'name' => 'Vijayapura',
                'district' => 'Vijayapura',
                'category' => 'Heritage',
                'desc' => 'Historic capital of the Adil Shahi dynasty, famous for Gol Gumbaz with its miraculous acoustic whispering gallery dome.',
                'image' => 'https://images.unsplash.com/photo-1599661046827-dacff0c0f09a?auto=format&fit=crop&w=800&q=80',
                'cost' => 2200.00,
                'activities' => [
                    ['Gol Gumbaz Whispering Gallery', 'Experience sounds echoing 11 times beneath the freestanding dome.', 200.00],
                    ['Ibrahim Rauza Complex', 'Marvel at the Taj Mahal of the Deccan with delicate stone tracery.', 150.00],
                    ['Malik-e-Maidan Historic Cannon', 'See the medieval 55-tonne bronze cannon on the city ramparts.', 100.00]
                ]
            ],
            [
                'name' => 'Sakleshpur',
                'district' => 'Hassan',
                'category' => 'Nature',
                'desc' => 'Misty Western Ghats hill station enveloped in coffee plantations, emerald mountain ridges, and the iconic star-shaped fort.',
                'image' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80',
                'cost' => 3100.00,
                'activities' => [
                    ['Manjarabad Star Fort Trek', 'Climb to the unique 8-pointed star fort built in 1792 by Tipu Sultan.', 200.00],
                    ['Bisle Ghat Viewpoint Hike', 'Breathtaking lookout point across three Western Ghats mountain ranges.', 250.00],
                    ['Coffee & Spice Plantation Walk', 'Walk beneath shade trees tasting fresh coffee, cardamom, and pepper.', 300.00]
                ]
            ],
            [
                'name' => 'Nandi Hills',
                'district' => 'Chikkaballapur',
                'category' => 'Relaxation',
                'desc' => 'Iconic ancient hilltop fortress near Bengaluru renowned for breathtaking sea-of-clouds sunrises and refreshing climate.',
                'image' => 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80',
                'cost' => 1800.00,
                'activities' => [
                    ['Sunrise Cloud-Bed Experience', 'Watch the sun rise over an ethereal blanket of morning valley clouds.', 100.00],
                    ['Tipus Drop & Fort Walk', 'Historic cliff edge and summer residence walk surrounded by trees.', 150.00],
                    ['Bhoga Nandeeshwara Temple', 'Visit the 1,000-year-old Dravidian temple at the base of the hill.', 100.00]
                ]
            ],
        ];

        // Categories mapping
        $catStmt = $pdo->query('SELECT id, name FROM categories');
        $catMap = [];
        while ($row = $catStmt->fetch()) {
            $catMap[$row['name']] = (int) $row['id'];
        }

        foreach ($extraDestinations as $item) {
            $check = $pdo->prepare('SELECT id FROM destinations WHERE name = ?');
            $check->execute([$item['name']]);
            $existingId = $check->fetchColumn();

            $destId = 0;
            if (!$existingId) {
                $catId = $catMap[$item['category']] ?? null;
                $ins = $pdo->prepare(
                    'INSERT INTO destinations (name, district, category_id, description, image_url, estimated_cost)
                     VALUES (?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([
                    $item['name'],
                    $item['district'],
                    $catId,
                    $item['desc'],
                    $item['image'],
                    $item['cost'],
                ]);
                $destId = (int) $pdo->lastInsertId();
            } else {
                $destId = (int) $existingId;
            }

            if ($destId > 0 && !empty($item['activities'])) {
                $actCheck = $pdo->prepare('SELECT COUNT(*) FROM activities WHERE destination_id = ?');
                $actCheck->execute([$destId]);
                if ((int) $actCheck->fetchColumn() === 0) {
                    $insAct = $pdo->prepare(
                        'INSERT INTO activities (destination_id, name, description, estimated_cost) VALUES (?, ?, ?, ?)'
                    );
                    foreach ($item['activities'] as $act) {
                        $insAct->execute([$destId, $act[0], $act[1], $act[2]]);
                    }
                }
            }
        }

        $seeded = true;
    } catch (Throwable $e) {
        // Table or database permission fallback
    }
}

/**
 * Retrieve destinations with optional search and filter criteria.
 */
function get_all_destinations(PDO $pdo, string $q = '', int $categoryId = 0, $budget = null): array
{
    ensure_extended_destinations_seeded($pdo);

    $sql = "SELECT d.*, c.name AS category_name
            FROM destinations d
            LEFT JOIN categories c ON c.id = d.category_id
            WHERE 1=1";
    $params = [];

    $q = trim($q);
    if ($q !== '') {
        $sql .= " AND (
            d.name LIKE ?
            OR d.district LIKE ?
            OR d.description LIKE ?
        )";
        $like = "%$q%";
        array_push($params, $like, $like, $like);
    }

    if ($categoryId > 0) {
        $sql .= " AND d.category_id = ?";
        $params[] = $categoryId;
    }

    if ($budget !== null && $budget !== '' && is_numeric($budget)) {
        $sql .= " AND d.estimated_cost <= ?";
        $params[] = (float) $budget;
    }

    $sql .= " ORDER BY d.name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Retrieve all categories ordered by name.
 */
function get_destination_categories(PDO $pdo): array
{
    ensure_extended_destinations_seeded($pdo);
    return $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

/**
 * Retrieve single destination with its category details.
 */
function get_destination_by_id(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        'SELECT d.*, c.name AS category_name
         FROM destinations d
         LEFT JOIN categories c ON c.id = d.category_id
         WHERE d.id = ?'
    );
    $stmt->execute([$id]);
    $res = $stmt->fetch();
    return $res ?: null;
}

/**
 * Retrieve activities for a destination.
 */
function get_destination_activities(PDO $pdo, int $destinationId): array
{
    $stmt = $pdo->prepare(
        'SELECT * FROM activities WHERE destination_id = ? ORDER BY name'
    );
    $stmt->execute([$destinationId]);
    return $stmt->fetchAll();
}
