<?php
include 'includes/header.php';
require 'includes/conn.php'; // 1. Deze ontbrak! Zonder dit werkt $conn niet.

// 2. Verbeterde beveiliging: als je geen admin bent (of niet bent ingelogd), word je weggestuurd.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}


$query = 'SELECT * FROM users WHERE role = "client"';
$result = mysqli_query($conn, $query);
?>

<div class="container mt-5">
    <div class="row border rounded p-4 mb-4">
        <div class="col">
            <h2>Import your CSV list</h2>
            <p>Upload a CSV with header: ID, Email, Name and Role. New users get the password: <span class="fw-bold">ChangeMe</span></p>
            <form action="admin.php" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="profilePic" class="form-label">CSV list</label>
                    <input type="file" class="form-control" name="csv_file" id="csv_file" accept=".csv">
                </div>
                <button type="submit" class="btn btn-succes">Import CSV</button>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <h1>Admin Dashboard</h1>
            <p>Welkom, <?php echo $_SESSION['name']; ?>. Je hebt admin-rechten.</p>

            <div class="alert alert-info">
                Hier kun je later bijvoorbeeld alle gebruikers of orders beheren.
            </div>
            <div>
                <div class="d-flex justify-content-between mb-3">
                    <h2>Users</h2>
                    <a href="csvGenerate.php" class="rounded btn btn-primary btn-sm w-auto d-flex align-items-center justify-content-center">Download CSV</a>
                </div>
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($result as $row) {
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['name']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                            echo '<td>' . htmlspecialchars($row['role']) . '</td>';
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>