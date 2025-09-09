<?php
// Start the session to access session variables
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get the current page filename
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-black">
    <div class="container-fluid">
        <a class="navbar-brand text-white" href="#">Admin Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo ($currentPage == 'index.php') ? 'active fw-bold' : ''; ?>" href="index.php">
                        <i class="fas fa-home me-1"></i> Dashboard
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin')): ?>
                <li class="nav-item">
                    <a class="nav-link text-white <?php echo ($currentPage == 'create_user.php') ? 'active fw-bold' : ''; ?>" href="create_user.php">
                        <i class="fas fa-user-plus me-1"></i> Create User
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['adminLoggedIn']) && $_SESSION['adminLoggedIn'] === true): ?>
                <li class="nav-item">
                    <span class="nav-link text-white">
                        <i class="fas fa-user-circle me-1"></i> Logged in as: <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white" href="logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
