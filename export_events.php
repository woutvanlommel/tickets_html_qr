<?php
// Standalone CSV export for events (no HTML output).

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'includes/conn.php';

// Check admin role
$currentUserId = $_SESSION['user_id'] ?? 0;
$roleResult = mysqli_query($conn, "SELECT role FROM users WHERE id = $currentUserId LIMIT 1");
$currentRole = $roleResult ? mysqli_fetch_assoc($roleResult)['role'] ?? 'client' : 'client';
if ($currentRole !== 'admin') {
    http_response_code(403);
    exit('Forbidden');
}

// Fetch events
$events = [];
$result = mysqli_query($conn, "SELECT id, name, venue, event_date, ppl FROM events ORDER BY event_date ASC");
if ($result) {
    $events = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=events.csv');

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Name', 'Venue', 'Date', 'Capacity'], ',', '"', '\\');

foreach ($events as $e) {
    fputcsv($out, [$e['id'], $e['name'], $e['venue'], $e['event_date'], $e['ppl']], ',', '"', '\\');
}

fclose($out);
exit;
