<?php
// 1. Zorg dat er geen spaties voor de <?php staan
session_start();
require 'includes/conn.php';

// 2. Controleer toegang
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Toegang geweigerd.");
}

// 4. Headers instellen voor CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=users_export_' . time() . '.csv');

// 5. Open de output stream
$output = fopen('php://output', 'w');


// 6. Kolomtitels schrijven (bij gebruik van separator ook enclosure en escape meegeven)
fputcsv($output, array('ID', 'Naam', 'Email', 'Rol'), ';', '"', '\\');

// 7. Data ophalen
$query = "SELECT id, name, email, role FROM users WHERE role = 'client'";
$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, $row, ';', '"', '\\');
    }
}

fclose($output);
exit();
