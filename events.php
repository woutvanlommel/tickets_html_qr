<?php
// Start the HTML layout + session (guarded in header.php)
include 'includes/header.php';

// Connect to the MySQL database using mysqli
require 'includes/conn.php';

// Fetch events from database
$sql = "SELECT id, name, venue, event_date, ppl FROM events ORDER BY event_date ASC";
$result = $conn->query($sql);
?>

<div class="container">
    <div class="row">
        <div class="col">
            <h1>Events</h1>
            <button class="btn btn-primary" onclick="location.href='add-event.php';">Upload an Event</button>
        </div>
    </div>
    <div class="row">
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
                <?php while ($row = $result->fetch_assoc()):
                    $id = (int)$row['id'];
                    $name = htmlspecialchars($row['name']);
                    $venue = htmlspecialchars($row['venue']);
                    $datePretty = $row['event_date'] ? date('F j, Y', strtotime($row['event_date'])) : '';
                    $capacity = is_null($row['ppl']) ? '—' : intval($row['ppl']);
                ?>
                    <div class="col">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $name; ?></h5>
                                <?php if ($datePretty): ?><p class="card-text text-muted mb-1"><strong>Date:</strong> <?php echo htmlspecialchars($datePretty); ?></p><?php endif; ?>
                                <?php if (!empty($venue)): ?><p class="card-text mb-2"><strong>Venue:</strong> <?php echo $venue; ?></p><?php endif; ?>
                                <p class="card-text mt-auto"><small class="text-muted">Capacity: <?php echo $capacity; ?></small></p>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="order.php?event_id=<?php echo $id; ?>" class="btn btn-primary">Order tickets</a>
                                    <a href="add-event.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">Edit</a>
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