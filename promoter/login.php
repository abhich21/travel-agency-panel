<?php
session_start();
require_once '../config/config.php';
require_once '../config/helper_function.php';

// If already logged in, redirect to scanner page
if (isset($_SESSION['promoterLoggedIn']) && $_SESSION['promoterLoggedIn'] === TRUE) {
    header('Location: scanner.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password']; // Default password '123456' as per requirement

    // Prepare and execute the query to find the promoter
    $stmt = $conn->prepare("SELECT user_id, organization_id, username, password FROM promoters WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $promoter = $result->fetch_assoc();

    if ($promoter) {
        // Since the password is a fixed value, we can check it directly
        if ($password === $promoter['password']) {
            $_SESSION['promoterLoggedIn'] = TRUE;
            $_SESSION['promoter_user_id'] = $promoter['user_id'];
            $_SESSION['organization_id'] = $promoter['organization_id'];
            $_SESSION['promoter_username'] = $promoter['username'];

            // Redirect to the scanner page
            header('Location: index.php');
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promoter Login</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
        }
    </style>
</head>
<body class="d-flex flex-column h-100 bg-light">
    <div class="container d-flex flex-column justify-content-center h-100">
        <div class="card p-4 shadow-sm login-container mx-auto">
            <h2 class="card-title text-center mb-4">Promoter Login</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
