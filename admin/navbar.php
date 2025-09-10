<?php
// Note: This script assumes a session is already started and a database connection exists
// The parent PHP file (e.g., index.php, login.php) must provide these dependencies
$nav_bg_color = "#343a40"; // Default dark color
$nav_text_color = "#ffffff"; // Default white color
$logo_url = '';

// Only fetch colors and logo if an admin is logged in
if (isset($_SESSION['admin_user_id']) && isset($conn)) {
    $stmt = $conn->prepare("SELECT o.bg_color, o.text_color, o.logo_url FROM organizations o WHERE o.user_id = ?");
    $stmt->bind_param("i", $_SESSION['admin_user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $org_details = $result->fetch_assoc();
    if ($org_details) {
        $nav_bg_color = htmlspecialchars($org_details['bg_color']);
        $nav_text_color = htmlspecialchars($org_details['text_color']);
        $logo_url = htmlspecialchars($org_details['logo_url']);
    }
}

// Get the current page file name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: <?php echo $nav_bg_color; ?>; color: <?php echo $nav_text_color; ?>;">
    <div class="container-fluid">
        <?php if (!empty($logo_url)) : ?>
            <a class="navbar-brand" href="index.php">
                <img src="<?php echo $logo_url; ?>" alt="Logo" style="height: 50px; margin-left: 1rem;">
            </a>
        <?php else : ?>
            <a class="navbar-brand" href="index.php" style="color: <?php echo $nav_text_color; ?>;">Admin Panel</a>
        <?php endif; ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation" style="border-color: <?php echo $nav_text_color; ?>;">
            <span class="navbar-toggler-icon" style="background-image: url('data:image/svg+xml;charset=utf8,%3Csvg viewBox=\'0 0 30 30\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath stroke=\'<?php echo rawurlencode($nav_text_color); ?>\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-miterlimit=\'10\' d=\'M4 7h22M4 15h22M4 23h22\'/%3E%3C/svg%3E');"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active fw-bold' : ''; ?>" aria-current="page" href="index.php" style="color: <?php echo $nav_text_color; ?>;">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'users.php') ? 'active fw-bold' : ''; ?>" href="users.php" style="color: <?php echo $nav_text_color; ?>;">Users</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'organization.php') ? 'active fw-bold' : ''; ?>" href="organization.php" style="color: <?php echo $nav_text_color; ?>;">Organization</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'logout.php') ? 'active fw-bold' : ''; ?>" href="logout.php" style="color: <?php echo $nav_text_color; ?>;">Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
