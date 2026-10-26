<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

/**
 * Submit a customer enquiry for a destination.
 */
function submit_enquiry(PDO $pdo, ?int $userId, int $destinationId, string $userName, string $userEmail, string $message): array
{
    $userName = trim($userName);
    $userEmail = strtolower(trim($userEmail));
    $message = trim($message);

    if ($destinationId <= 0) {
        return ['success' => false, 'error' => 'Please select a valid destination.'];
    }
    if ($userName === '') {
        return ['success' => false, 'error' => 'Please enter your name.'];
    }
    if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address.'];
    }
    if ($message === '') {
        return ['success' => false, 'error' => 'Please write your enquiry message.'];
    }

    // Fetch destination name
    $destStmt = $pdo->prepare('SELECT name FROM destinations WHERE id = ?');
    $destStmt->execute([$destinationId]);
    $destName = (string) ($destStmt->fetchColumn() ?: 'Karnataka Destination');

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO enquiries (user_id, destination_id, user_name, user_email, message, status)
             VALUES (?, ?, ?, ?, ?, "pending")'
        );
        $stmt->execute([
            $userId ?: null,
            $destinationId,
            $userName,
            $userEmail,
            $message,
        ]);
        $enquiryId = (int) $pdo->lastInsertId();

        // Send alert email to Admin
        $adminEmail = get_admin_email($pdo);
        $subject = "New Enquiry for {$destName} from {$userName}";
        $body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #23352b;'>
                <h2 style='color: #247653;'>New Destination Enquiry</h2>
                <p>You have received a new enquiry from a traveler on <strong>Personalized Travel Companion</strong>.</p>
                <div style='background: #f4f8f5; border-left: 4px solid #247653; padding: 15px; margin: 20px 0;'>
                    <p><strong>Destination:</strong> " . htmlspecialchars($destName) . "</p>
                    <p><strong>Traveler Name:</strong> " . htmlspecialchars($userName) . "</p>
                    <p><strong>Traveler Email:</strong> " . htmlspecialchars($userEmail) . "</p>
                    <p><strong>Message:</strong></p>
                    <p style='white-space: pre-wrap;'>" . htmlspecialchars($message) . "</p>
                </div>
                <p>Log in to your <a href='http://localhost/personalized_travel_companion/frontend/admin/'>Admin Dashboard</a> to reply to this enquiry.</p>
            </div>
        ";
        send_app_email($adminEmail, $subject, $body);

        return ['success' => true, 'error' => '', 'id' => $enquiryId];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Could not submit your enquiry. Please try again.'];
    }
}

/**
 * Retrieve enquiries for admin dashboard.
 */
function get_admin_enquiries(PDO $pdo, ?string $statusFilter = null): array
{
    $sql = 'SELECT e.*, d.name AS destination_name, d.district, d.image_url AS destination_image
            FROM enquiries e
            JOIN destinations d ON d.id = e.destination_id';
    $params = [];

    if ($statusFilter && in_array($statusFilter, ['pending', 'replied'], true)) {
        $sql .= ' WHERE e.status = ?';
        $params[] = $statusFilter;
    }

    $sql .= ' ORDER BY (e.status = "pending") DESC, e.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Count pending enquiries for badge notifications.
 */
function get_pending_enquiries_count(PDO $pdo): int
{
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM enquiries WHERE status = "pending"');
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Admin responds to an enquiry.
 */
function reply_to_enquiry(PDO $pdo, int $enquiryId, string $adminReply): array
{
    $adminReply = trim($adminReply);
    if ($adminReply === '') {
        return ['success' => false, 'error' => 'Response message cannot be empty.'];
    }

    $stmt = $pdo->prepare(
        'SELECT e.*, d.name AS destination_name FROM enquiries e JOIN destinations d ON d.id = e.destination_id WHERE e.id = ?'
    );
    $stmt->execute([$enquiryId]);
    $enquiry = $stmt->fetch();

    if (!$enquiry) {
        return ['success' => false, 'error' => 'Enquiry not found.'];
    }

    try {
        $update = $pdo->prepare(
            'UPDATE enquiries SET admin_reply = ?, status = "replied", replied_at = NOW() WHERE id = ?'
        );
        $update->execute([$adminReply, $enquiryId]);

        // Send email response to traveler
        $userEmail = (string) $enquiry['user_email'];
        $destName = (string) $enquiry['destination_name'];
        $userName = (string) $enquiry['user_name'];

        $subject = "Response regarding your enquiry about {$destName} | Travel Companion";
        $body = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #23352b;'>
                <h2 style='color: #247653;'>Hello " . htmlspecialchars($userName) . ",</h2>
                <p>Our travel specialist has replied to your enquiry regarding <strong>" . htmlspecialchars($destName) . "</strong>:</p>
                <div style='background: #eef7f2; border-left: 4px solid #247653; padding: 15px; margin: 20px 0;'>
                    <p style='white-space: pre-wrap; font-size: 15px; color: #173b2a; margin: 0;'>" . htmlspecialchars($adminReply) . "</p>
                </div>
                <div style='margin-top: 20px; padding: 12px; background: #fafcfb; border: 1px solid #e1e8e3; border-radius: 6px;'>
                    <p style='font-size: 12px; color: #64756b; margin: 0 0 5px;'><strong>Your original enquiry:</strong></p>
                    <p style='font-size: 13px; color: #64756b; margin: 0; white-space: pre-wrap;'>" . htmlspecialchars($enquiry['message']) . "</p>
                </div>
                <p style='margin-top: 25px;'>You can also review your enquiries and plans anytime in your <a href='http://localhost/personalized_travel_companion/frontend/my_trips.php'>Travel Dashboard</a>.</p>
                <p>Happy Journey,<br><strong>Personalized Travel Companion Team</strong></p>
            </div>
        ";
        send_app_email($userEmail, $subject, $body);

        return ['success' => true, 'error' => ''];
    } catch (Throwable $e) {
        return ['success' => false, 'error' => 'Failed to save reply. Please try again.'];
    }
}

/**
 * Retrieve enquiries submitted by a user for their dashboard.
 */
function get_user_enquiries(PDO $pdo, int $userId, string $userEmail): array
{
    $userEmail = strtolower(trim($userEmail));
    $stmt = $pdo->prepare(
        'SELECT e.*, d.name AS destination_name, d.district, d.image_url AS destination_image
         FROM enquiries e
         JOIN destinations d ON d.id = e.destination_id
         WHERE e.user_id = ? OR e.user_email = ?
         ORDER BY e.created_at DESC'
    );
    $stmt->execute([$userId, $userEmail]);
    return $stmt->fetchAll();
}
