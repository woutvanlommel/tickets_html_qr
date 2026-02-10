<?php
// Include the header which starts the session and provides HTML structure
include 'includes/header.php';
// Include the database connection file
require 'includes/conn.php';

// Initialize message variable
$message = "";

// Check if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input
    // We use mysqli_real_escape_string for email to prevent SQL injection
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // Raw password for verification

    // Bereid de SQL query voor (role is een enum in de users tabel)
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    // Check of er een gebruiker met dit emailadres bestaat
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Controleer het wachtwoord
        if (password_verify($password, $user['password'])) {
            // Wachtwoord is correct!

            // Sla gebruikersgegevens op in de SESSION
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role']; // De rol uit de enum kolom


            // Optional: Redirect to the home page or dashboard
            header("Location: index.php");
            exit(); // Stop script execution after redirect
        } else {
            // Password does not match
            $message = "Invalid password.";
        }
    } else {
        // No user found with that email
        $message = "User not found.";
    }
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h1>Login</h1>

            <?php if ($message): ?>
                <div class="alert alert-danger"><?php echo $message; ?></div>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" class="form-control" name="email" id="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" id="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
                <div class="mt-3">
                    Not registered? <a href="index.php">Create an account</a>.
                </div>
            </form>
        </div>
    </div>
</div>

</body>

</html>