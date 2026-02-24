<?php
// Start the HTML layout + session (guarded in header.php)
include 'includes/header.php';

// Connect to the MySQL database using mysqli
require 'includes/conn.php';

?>

<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Add / Edit Event</h1>

            <form id="eventForm" action="save-event.php" method="post" class="mb-4">
                <div class="mb-3">
                    <label class="form-label">Event name</label>
                    <input id="name" name="name" type="text" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Venue</label>
                    <input id="venue" name="venue" type="text" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input id="event_date" name="event_date" type="date" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Capacity</label>
                    <input id="ppl" name="ppl" type="number" class="form-control" min="1">
                </div>
                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input id="price" name="price" type="number" class="form-control" min="1">
                </div>

                <label class="form-label">Event image (optional)</label>
                <div id="eventDropzone" class="dropzone mb-3 form-control bord border-1 "></div>


                <div class="d-flex gap-2">
                    <button id="submitBtn" type="submit" class="btn btn-primary">Save Event</button>
                    <a href="events.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>

            <div id="formAlert"></div>
        </div>
    </div>
</div>

<!-- Dropzone CSS/JS (local from vendor/enyo/dropzone) -->
<link rel="stylesheet" href="vendor/enyo/dropzone/dist/dropzone.css" />
<script src="vendor/enyo/dropzone/dist/min/dropzone.min.js"></script>