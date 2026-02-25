<?php
// admin.php
include 'includes/header.php';
require 'includes/conn.php';

// 1. Toegangscontrole: Alleen voor admins
$currentUserId = $_SESSION['user_id'];
$roleResult = mysqli_query($conn, "SELECT role FROM users WHERE id = $currentUserId");
$currentRole = $roleResult ? mysqli_fetch_assoc($roleResult)['role'] ?? 'client' : 'client';

if ($currentRole !== 'admin') {
    echo '<div class="container mt-4"><div class="alert alert-danger">Toegang geweigerd. Alleen admins hebben toegang tot deze pagina.</div></div>';
    exit;
}

$message = '';

// 2. LOGICA: Gebruikers Importeren via CSV
if (isset($_POST['import_users'])) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $message = '<div class="alert alert-warning">Upload a valid CSV file.</div>';
    } else {
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ($handle !== false) {
            fgetcsv($handle); // Sla de header-rij over
            $count = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Verwachte kolommen: 0:Naam, 1:Email, 2:Rol
                $name = mysqli_real_escape_string($conn, $data[0]);
                $email = mysqli_real_escape_string($conn, $data[1]);
                $role = mysqli_real_escape_string($conn, $data[2]);
                $pass = password_hash('changeme', PASSWORD_DEFAULT);

                $sql = "INSERT INTO users (name, email, role, password) VALUES ('$name', '$email', '$role', '$pass') 
                        ON DUPLICATE KEY UPDATE role='$role'";
                if (mysqli_query($conn, $sql)) {
                    $count++;
                }
            }
            fclose($handle);
            $message = "<div class='alert alert-success'>Import voltooid! $count gebruikers verwerkt.</div>";
        }
    }
}

// 3. LOGICA: Event Verwijderen
if (isset($_POST['delete_event'])) {
    $id = (int)$_POST['event_id'];
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Event succesvol verwijderd.</div>';
    }
    $stmt->close();
}

// 4. LOGICA: Event Toevoegen
if (isset($_POST['add_event'])) {
    $name = trim($_POST['event_name']);
    $venue = trim($_POST['event_venue']);
    $date = $_POST['event_date'];
    $ppl = $_POST['event_ppl'] !== '' ? (int)$_POST['event_ppl'] : null;

    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO events (name, venue, event_date, ppl) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sssi', $name, $venue, $date, $ppl);
        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Nieuw event toegevoegd!</div>';
        }
        $stmt->close();
    }
}

// 5. DATA OPHALEN: Actuele lijst met events
$res = mysqli_query($conn, "SELECT * FROM events ORDER BY event_date ASC");
$eventsList = mysqli_fetch_all($res, MYSQLI_ASSOC);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Admin Dashboard</h1>
        <div>
            <a href="export_users.php" class="btn btn-outline-primary btn-sm">Export Users</a>
            <a href="export_events.php" class="btn btn-outline-secondary btn-sm">Export Events</a>
        </div>
    </div>

    <?php echo $message; ?>

    <div class="row g-4">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Events Beheren</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Event Info</th>
                                <th>Datum</th>
                                <th>Capaciteit</th>
                                <th class="text-end pe-3">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($eventsList)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Geen events gevonden.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($eventsList as $ev): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <strong><?php echo htmlspecialchars($ev['name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($ev['venue']); ?></small>
                                        </td>
                                        <td><?php echo date('d-m-Y', strtotime($ev['event_date'])); ?></td>
                                        <td><?php echo $ev['ppl'] ? number_format($ev['ppl'], 0, ',', '.') : '—'; ?></td>
                                        <td class="text-end pe-3">
                                            <form method="POST" onsubmit="return confirm('Weet je zeker dat je dit event wilt verwijderen?');" style="display:inline;">
                                                <input type="hidden" name="event_id" value="<?php echo $ev['id']; ?>">
                                                <button type="submit" name="delete_event" class="btn btn-sm btn-danger">Verwijder</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">Importeer Gebruikers (CSV)</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Kolomvolgorde: Naam, Email, Rol.</p>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" name="csv_file" class="form-control form-control-sm" accept=".csv" required>
                        </div>
                        <button type="submit" name="import_users" class="btn btn-success btn-sm w-100">Start Import</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Nieuw Event Toevoegen</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-2">
                            <label class="form-label small mb-1">Naam *</label>
                            <input type="text" name="event_name" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Locatie</label>
                            <input type="text" name="event_venue" class="form-control form-control-sm">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small mb-1">Datum</label>
                            <input type="date" name="event_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small mb-1">Capaciteit</label>
                            <input type="number" name="event_ppl" class="form-control form-control-sm" min="0">
                        </div>
                        <button type="submit" name="add_event" class="btn btn-primary btn-sm w-100">Opslaan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>

</html>