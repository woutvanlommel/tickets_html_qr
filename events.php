<?php
// Start the HTML layout + session (guarded in header.php)
include 'includes/header.php';

// Connect to the MySQL database using mysqli
require 'includes/conn.php';

// Determine if the current user is an admin (used to show add-event button)
$isAdmin = false;
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!empty($_SESSION['user_id'])) {
    $currentUserId = (int)$_SESSION['user_id'];
    $r = mysqli_query($conn, "SELECT role FROM users WHERE id = $currentUserId LIMIT 1");
    if ($r) {
        $row = mysqli_fetch_assoc($r);
        if ($row && isset($row['role']) && $row['role'] === 'admin') {
            $isAdmin = true;
        }
    }
}

// Fetch events from database
$sql = "SELECT id, name, venue, event_date, ppl FROM events ORDER BY event_date ASC";
$result = $conn->query($sql);
?>

<div class="container">
    <div class="row">
        <div class="col">
            <h1>Events</h1>
            <?php if ($isAdmin): ?>
                <button class="btn btn-primary" onclick="location.href='admin.php';">Upload an Event</button>
            <?php endif; ?>
        </div>
    </div>
    <div class="row">
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
                <?php while ($row = $result->fetch_assoc()):
                    $id = (int)$row['id'];
                    $name = htmlspecialchars($row['name']);
                    $venue = htmlspecialchars($row['venue']);
                    $datePretty = $row['event_date'] ? date('l, F j, Y', strtotime($row['event_date'])) : '';
                    $capacity = is_null($row['ppl']) ? '—' : intval($row['ppl']);

                    // Look for a local image file for the event (jpg/png/webp)
                    $imgWeb = '';
                    $imgFound = false;
                    $exts = ['jpg', 'jpeg', 'png', 'webp'];
                    foreach ($exts as $ext) {
                        $candidate = __DIR__ . "/uploads/events/{$id}.{$ext}";
                        if (file_exists($candidate)) {
                            $imgWeb = 'uploads/events/' . $id . '.' . $ext;
                            $imgFound = true;
                            break;
                        }
                    }
                ?>
                    <div class="col">
                        <div class="card h-100 shadow-sm">
                            <?php if ($imgFound): ?>
                                <img src="<?php echo $imgWeb; ?>" class="card-img-top" alt="<?php echo $name; ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:140px;">
                                    <div class="text-muted">No image</div>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?php echo $name; ?></h5>
                                    <?php if ($datePretty): ?><small class="text-muted"><?php echo htmlspecialchars($datePretty); ?></small><?php endif; ?>
                                </div>
                                <?php if (!empty($venue)): ?><p class="text-muted mb-2 small"><strong>Venue:</strong> <?php echo $venue; ?></p><?php endif; ?>
                                <div class="mt-auto d-flex flex-column justify-content-between align-items-start">
                                    <div class="small text-muted">Capacity: <?php echo number_format($capacity, 0, ',', '.'); ?></div>
                                    <div class="d-flex flex-row">
                                        <a href="order.php?event_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">Order</a>
                                        <?php if (!empty($isAdmin)): ?>
                                            <a href="add-event.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-secondary ms-2">Edit</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info mt-3">No events found. <a href="add-event.php">Add one</a>.</div>
            </div>
        <?php endif; ?>
    </div>
</div>