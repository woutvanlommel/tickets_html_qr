<?php
require 'includes/conn.php';

// Detect if caller expects JSON (AJAX) or regular HTML
$isAjax = false;
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $isAjax = true;
} elseif (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    $isAjax = true;
}

function respond($data, $isAjax)
{
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // Render a small Bootstrap page for normal form submits
    include 'includes/header.php';
    $title = $data['success'] ? 'Event saved' : 'Error saving event';
?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="card-title mb-3"><?php echo htmlspecialchars($title); ?></h3>
                        <?php if (!empty($data['success'])): ?>
                            <p class="text-success">Het evenement is succesvol opgeslagen.</p>
                            <p><strong><?php echo htmlspecialchars($data['name'] ?? ''); ?></strong></p>
                            <div class="d-flex justify-content-center gap-2">
                                <a class="btn btn-primary" href="events.php">Bekijk events</a>
                                <a class="btn btn-outline-secondary" href="add-event.php">Voeg nog een event toe</a>
                            </div>
                        <?php else: ?>
                            <p class="text-danger"><?php echo htmlspecialchars($data['error'] ?? 'Onbekende fout'); ?></p>
                            <a class="btn btn-secondary" href="add-event.php">Terug</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$venue = isset($_POST['venue']) ? trim($_POST['venue']) : null;
$event_date = isset($_POST['event_date']) && $_POST['event_date'] !== '' ? $_POST['event_date'] : null;
$capacity = isset($_POST['ppl']) && $_POST['ppl'] !== '' ? intval($_POST['ppl']) : null;
$price = isset($_POST['price']) && $_POST['price'] !== '' ? intval($_POST['price']) : null;

if ($name === '') {
    respond(['success' => false, 'error' => 'Event name is required'], $isAjax);
}

// Insert event (without image first)
$stmt = $conn->prepare("INSERT INTO events (name, venue, event_date, ppl, price, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
if (!$stmt) {
    respond(['success' => false, 'error' => 'DB prepare failed: ' . $conn->error], $isAjax);
}
$stmt->bind_param('sssii', $name, $venue, $event_date, $capacity, $price);
if (!$stmt->execute()) {
    respond(['success' => false, 'error' => 'DB execute failed: ' . $stmt->error], $isAjax);
}

$event_id = $conn->insert_id;
$stmt->close();

$imagePath = null;
$savedImages = [];

// Prepare upload directory
$uploadDir = 'uploads/events/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        respond(['success' => false, 'error' => 'Failed to create upload directory'], $isAjax);
    }
}

// Support multiple files sent as files[] (Dropzone with uploadMultiple) or a single file field
if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
    // multiple files
    for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
        if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $originalName = basename($_FILES['files']['name'][$i]);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $targetName = 'event_' . $event_id . '_' . ($i + 1) . '.' . $ext;
        $targetPath = $uploadDir . $targetName;
        if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $targetPath)) {
            $savedImages[] = $targetPath;
        }
    }
} elseif (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    // single file fallback
    $originalName = basename($_FILES['file']['name']);
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    $targetName = 'event_' . $event_id . '.' . $ext;
    $targetPath = $uploadDir . $targetName;
    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
        $savedImages[] = $targetPath;
    }
}

// If we saved at least one image, ensure 'image' column exists and store the first one
if (count($savedImages) > 0) {
    $imagePath = $savedImages[0];
    $colCheck = $conn->query("SHOW COLUMNS FROM events LIKE 'image'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE events ADD COLUMN image VARCHAR(255) DEFAULT NULL");
    }
    $up = $conn->prepare("UPDATE events SET image = ? WHERE id = ?");
    if ($up) {
        $up->bind_param('si', $imagePath, $event_id);
        $up->execute();
        $up->close();
    }
}

// Successful response
respond(['success' => true, 'event_id' => $event_id, 'images' => $savedImages, 'name' => $name], $isAjax);
