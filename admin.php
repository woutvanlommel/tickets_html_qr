<?php
// Admin page: simple CSV import/export for users.
// This page renders HTML and handles the CSV import form.

// Start the HTML layout + session (guarded in header.php)
include 'includes/header.php';

// Connect to the MySQL database using mysqli
require 'includes/conn.php';

// Get the current user role from the database.
$currentUserId = $_SESSION['user_id'];

// Query the role for the current user
$roleResult = mysqli_query($conn, "SELECT role FROM users WHERE id = $currentUserId");

// If the query succeeds, read the role; otherwise default to 'client'
$currentRole = $roleResult ? mysqli_fetch_assoc($roleResult)['role'] ?? 'client' : 'client';

// Stop non-admins from accessing the admin tools.
if ($currentRole !== 'admin') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Access denied. Admins only.</div></div>';
    exit;
}

// Export is handled by export_users.php to avoid HTML output.
// This keeps CSV output clean and free of any HTML markup.

// --- IMPORT: handle CSV upload ---
// Message shown to the user after an import attempt
$importMessage = '';
if (isset($_POST['import_users'])) {
    // Validate the upload
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $importMessage = 'Please upload a valid CSV file.';
    } else {
        // Use the temporary file path for reading
        $tmpPath = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($tmpPath, 'r');

        if ($handle === false) {
            $importMessage = 'Unable to read the uploaded file.';
        } else {
            // Read header row (expected columns: ID, Name, Email, Role)
            // We support flexible headers (case-insensitive).
            $header = fgetcsv($handle, 0, ',', '"', '\\');
            $map = [];
            if ($header) {
                foreach ($header as $index => $columnName) {
                    $key = strtolower(trim($columnName));
                    $map[$key] = $index;
                }
            }

            // Prepare insert statement (simple + safe)
            // We only insert name/email/role + a default password.
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");

            $inserted = 0;
            $skipped = 0;

            // Read each CSV row
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                // Read fields by header name (fallback to fixed positions).
                $name = $map['name'] ?? 1;
                $email = $map['email'] ?? 2;
                $role = $map['role'] ?? 3;

                $nameVal = trim($row[$name] ?? '');
                $emailVal = trim($row[$email] ?? '');
                $roleVal = trim($row[$role] ?? '');

                // Skip rows without required fields
                if ($nameVal === '' || $emailVal === '') {
                    $skipped++;
                    continue;
                }

                // Keep it simple: if role is missing/invalid, default to client.
                if ($roleVal !== 'admin' && $roleVal !== 'client') {
                    $roleVal = 'client';
                }

                // Simple default password for imported users (hashed).
                // User should change it after first login.
                $defaultPassword = password_hash('changeme', PASSWORD_DEFAULT);

                // Skip if email already exists.
                $emailEsc = mysqli_real_escape_string($conn, $emailVal);
                $exists = mysqli_query($conn, "SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1");
                if ($exists && mysqli_num_rows($exists) > 0) {
                    $skipped++;
                    continue;
                }

                // Insert the user row
                if ($stmt && $stmt->bind_param('ssss', $nameVal, $emailVal, $defaultPassword, $roleVal) && $stmt->execute()) {
                    $inserted++;
                } else {
                    $skipped++;
                }
            }

            if ($stmt) {
                $stmt->close();
            }

            // Close the CSV file handle
            fclose($handle);
            $importMessage = "Import finished. Inserted: $inserted. Skipped: $skipped.";
        }
    }
}

$orderQuery = 'SELECT * FROM tickets';
$orderResult = mysqli_query($conn, $orderQuery);
?>

<div class="container mt-4">
    <h1>Admin - Users CSV</h1>

    <?php if ($importMessage): ?>
        <div class="alert alert-info"><?php echo $importMessage; ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Export users</h5>
            <p class="card-text">Download a simple CSV with ID, Name, Email, Role.</p>
            <a class="btn btn-primary" href="export_users.php">Download CSV</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Import users</h5>
            <p class="card-text">Upload a CSV with header: ID, Name, Email, Role. New users get password <strong>changeme</strong>.</p>
            <form action="" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <input class="form-control" type="file" name="csv_file" accept=".csv" required>
                </div>
                <button class="btn btn-success" type="submit" name="import_users">Import CSV</button>
            </form>
        </div>
    </div>

    <div>
        <h2>Orders</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Email</th>
                    <th>Amount</th>
                    <th>User ID</th>
                    <th>PP Ticket</th>
                    <th>Order Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orderResult) {
                    foreach ($orderResult as $row) {
                        echo "<tr>
                            <td>$row['id']</td>
                            <td>$row['status']</td>
                            <td>$row['email']</td>
                            <td>$row['amount']</td>
                            <td>$row['user_id']</td>
                            <td>$row['ppticket']</td>
                            <td>$row['order_date']</td>
                        </tr>";
                    }
                }
    </div>
</div>

</body>

</html>