<?php
// navbar.php - HYBRID Responsive Navigation Bar (Top on Desktop, Bottom on Mobile)
// This script assumes a database connection '$conn' is available from a config file.

// --- 1. THEME AND LOGO CONFIGURATION ---
$nav_bg_color = "#1a1a1a"; // A fallback dark background
$nav_text_color = "#ffffff"; // A fallback white text color
$logo_url = ''; // Fallback for logo
$org_title = 'Event Portal'; // Fallback title

if (isset($_GET['view']) && !empty($_GET['view'])) {
    $organization_title = $_GET['view'];
    $stmt = $conn->prepare("SELECT title, logo_url, bg_color, text_color FROM organizations WHERE title = ? LIMIT 1");
    $stmt->bind_param("s", $organization_title);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($org_details = $result->fetch_assoc()) {
        $org_title = htmlspecialchars($org_details['title']);
        $logo_url = htmlspecialchars($org_details['logo_url']);
        $nav_bg_color = htmlspecialchars($org_details['bg_color']);
        $nav_text_color = htmlspecialchars($org_details['text_color']);
    }
    $stmt->close();
}

// --- 2. ACTIVE PAGE HIGHLIGHTING ---
$current_page = basename($_SERVER['PHP_SELF']);
// +++ START NEW CODE +++
// --- 3. CHECK LOGIN STATUS ---
// This file is included by pages that ALREADY started the session (like login.php, my_qr_code.php, etc)
// So we can safely check the session variable here.
$is_logged_in = (isset($_SESSION['attendee_logged_in']) && $_SESSION['attendee_logged_in'] === true);
// +++ END NEW CODE +++
?>

<!DOCTYPE html>
<html>
<head>
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>

        /* .site-wrapper2 {
  width:80vw;
  max-width: 1400px;
  margin: 0 auto;
  background-color: white;
  box-shadow: 0 0 10px rgba(0,0,0,0.1);
} */

        /* --- 3. CUSTOM CSS FOR HYBRID NAVBAR --- */
        body {
            /* Add padding to the bottom of the body to prevent content from being hidden by the bottom navbar on mobile */
            padding-bottom: 80px;
        }

        /* --- DESKTOP: TOP NAVBAR --- */
        .navbar-top {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 0.8rem 1rem;
        }
        .navbar-top .navbar-brand img {
            height: 45px;
            max-width: 150px;
            object-fit: contain;
        }
        .navbar-top .brand-text {
            font-weight: bold;
            font-size: 1.5rem;
        }
        .navbar-top .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            border-radius: 0.375rem;
            transition: background-color 0.2s ease-in-out;
            margin: 0 0.25rem;
        }
        .navbar-top .nav-link.active,
        .navbar-top .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15); /* Subtle highlight */
        }
        
        /* --- MOBILE: BOTTOM NAVBAR --- */
        .navbar-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
        .navbar-bottom .navbar-nav {
            width: 100%;
            flex-direction: row;
            justify-content: space-around;
        }
        .navbar-bottom .nav-link {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            padding: 0.5rem 0.25rem;
            text-align: center;
        }
        .navbar-bottom .nav-link i {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        .navbar-bottom .nav-link.active {
            transform: scale(1.1);
            border-radius: 8px;
        }

        /* Offcanvas Menu for "More" */
        .offcanvas-body .list-group-item {
            background-color: transparent;
            border: none;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
        }
        .offcanvas-body .list-group-item i {
            width: 30px; /* Align icons */
        }
    </style>
</head>
<body>

<!-- --- 4. HTML STRUCTURE --- -->

<!-- DESKTOP: TOP NAVBAR - Visible on large screens and up (d-none d-lg-flex) -->
 
 <nav class="navbar navbar-top navbar-expand-lg  d-none d-lg-flex" style="background-color: <?php echo $nav_bg_color; ?>;">
    <div class="container-fluid">
        <a class="navbar-brand" href="home.php?view=<?php echo urlencode($org_title); ?>">
            <?php if (!empty($logo_url)) : ?>
                <img src="<?php echo $logo_url; ?>" alt="<?php echo $org_title; ?> Logo">
            <?php else : ?>
                <span class="brand-text" style="color: <?php echo $nav_text_color; ?>;"><?php echo $org_title; ?></span>
            <?php endif; ?>
        </a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <?php
                // Helper function to generate nav items
                function nav_item($page, $title, $icon, $current_page, $color, $org_title, $id = '', $is_hidden = false) {
                    $active_class = ($current_page == $page) ? 'active' : '';
                    $hidden_class = $is_hidden ? 'd-none' : ''; // Bootstrap class to hide element
                    echo "<li id='$id' class='nav-item $hidden_class'>";
                    echo "<a class='nav-link $active_class' style='color: $color;' href='$page?view=" . urlencode($org_title) . "'><i class='$icon me-2'></i>$title</a>";
                    echo "</li>";
                }

 // --- PUBLIC LINKS (Always Show) ---
                nav_item('home.php', 'Home', 'fas fa-home', $current_page, $nav_text_color, $org_title);
                nav_item('agenda.php', 'Agenda', 'fas fa-calendar-alt', $current_page, $nav_text_color, $org_title);
                nav_item('venue.php', 'Venue', 'fas fa-map-marker-alt', $current_page, $nav_text_color, $org_title);
                nav_item('faq.php', 'FAQs', 'fas fa-question-circle', $current_page, $nav_text_color, $org_title);

                // --- CONDITIONAL LINKS (Based on Login Status) ---
                if ($is_logged_in):
                    // --- USER IS LOGGED IN ---
                    nav_item('my_account.php', 'My Account', 'fas fa-user-circle', $current_page, $nav_text_color, $org_title);
                    nav_item('logout.php', 'Logout', 'fas fa-sign-out-alt', $current_page, $nav_text_color, $org_title);
                else:
                    // --- USER IS LOGGED OUT ---
                    nav_item('registration.php', 'Registration', 'fas fa-user-plus', $current_page, $nav_text_color, $org_title);
                    nav_item('login.php', 'Login', 'fas fa-sign-in-alt', $current_page, $nav_text_color, $org_title);
                endif;                // Dropdown for the rest
                ?>
                 <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="color: <?php echo $nav_text_color; ?>;">
                        More
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="fun_zone.php?view=<?php echo urlencode($org_title); ?>"><i class="fas fa-gamepad me-2"></i>Fun Zone</a></li>
                        <li><a class="dropdown-item" href="leaderboard.php?view=<?php echo urlencode($org_title); ?>"><i class="fas fa-trophy me-2"></i>Leaderboard</a></li>
                        <li><a class="dropdown-item" href="gallery.php?view=<?php echo urlencode($org_title); ?>"><i class="fas fa-images me-2"></i>Gallery</a></li>
                        <li><a class="dropdown-item" href="notifications.php?view=<?php echo urlencode($org_title); ?>"><i class="fas fa-bell me-2"></i>Notifications</a></li>
                        <li><a class="dropdown-item" href="helpdesk.php?view=<?php echo urlencode($org_title); ?>"><i class="fas fa-headset me-2"></i>Helpdesk</a></li>
                    </ul>
                </li>
            </ul>
        </div>
   
</nav>

<!-- MOBILE: BOTTOM NAVBAR - Visible on medium screens and down (d-lg-none) -->
<nav class="navbar navbar-bottom d-lg-none" style="background-color: <?php echo $nav_bg_color; ?>;">
    <ul class="navbar-nav">
        <?php
        function mobile_nav_item($page, $title, $icon, $current_page, $color, $org_title, $id = '', $is_hidden = false) {
            $active_class = ($current_page == $page) ? 'active' : '';
            $hidden_class = $is_hidden ? 'd-none' : '';
            echo "<li id='$id' class='nav-item $hidden_class'><a class='nav-link $active_class' style='color: $color;' href='$page?view=" . urlencode($org_title) . "'><i class='$icon'></i><span>$title</span></a></li>";
        }
        
        mobile_nav_item('home.php', 'Home', 'fas fa-home', $current_page, $nav_text_color, $org_title);
        mobile_nav_item('agenda.php', 'Agenda', 'fas fa-calendar-alt', $current_page, $nav_text_color, $org_title);
mobile_nav_item('my_account.php', 'Account', 'fas fa-user-circle', $current_page, $nav_text_color, $org_title);        mobile_nav_item('gallery.php', 'Gallery', 'fas fa-images', $current_page, $nav_text_color, $org_title);
        ?>
        <li class="nav-item">
            <a class="nav-link" style="color: <?php echo $nav_text_color; ?>;" href="#" data-bs-toggle="offcanvas" data-bs-target="#moreMenu">
                <i class="fas fa-bars"></i><span>More</span>
            </a>
        </li>
    </ul>
</nav>

<!-- MOBILE: "MORE" OFFCANVAS MENU -->
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="moreMenu" aria-labelledby="moreMenuLabel" style="background-color: <?php echo $nav_bg_color; ?>; color: <?php echo $nav_text_color; ?>; height: auto;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="moreMenuLabel">More Options</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close" style="background-color: <?php echo $nav_text_color; ?>;"></button>
    </div>
    <div class="offcanvas-body">
        <div class="list-group">
            <?php
            function offcanvas_item($page, $title, $icon, $color, $org_title, $id = '', $is_hidden = false) {
                $hidden_class = $is_hidden ? 'd-none' : '';
                echo "<a id='$id' href='$page?view=" . urlencode($org_title) . "' class='list-group-item list-group-item-action $hidden_class' style='color: $color;'><i class='$icon me-3'></i>$title</a>";
            }

           if ($is_logged_in):
    // Show "My Ticket" if logged IN
offcanvas_item('my_account.php', 'My Account', 'fas fa-user-circle', $nav_text_color, $org_title);else:
    // Show "Registration" and "Login" if LOGGED OUT
    offcanvas_item('registration.php', 'Registration', 'fas fa-user-plus', $nav_text_color, $org_title);
    offcanvas_item('login.php', 'Login', 'fas fa-sign-in-alt', $nav_text_color, $org_title);
endif;            offcanvas_item('venue.php', 'Venue Details', 'fas fa-map-marker-alt', $nav_text_color, $org_title);
            offcanvas_item('faq.php', 'FAQs', 'fas fa-question-circle', $nav_text_color, $org_title);
            offcanvas_item('fun_zone.php', 'Fun Zone', 'fas fa-gamepad', $nav_text_color, $org_title);
            offcanvas_item('leaderboard.php', 'Leaderboard', 'fas fa-trophy', $nav_text_color, $org_title);
            offcanvas_item('notifications.php', 'Notifications', 'fas fa-bell', $nav_text_color, $org_title);
            offcanvas_item('helpdesk.php', 'Helpdesk', 'fas fa-headset', $nav_text_color, $org_title);
            // +++ ADD THIS NEW LOGOUT BLOCK +++
        if ($is_logged_in): ?>
            <hr style="border-color: <?php echo $nav_text_color . '40'; ?>;">
            <?php offcanvas_item('logout.php', 'Logout', 'fas fa-sign-out-alt', $nav_text_color, $org_title); ?>
        <?php endif; ?>
            
        </div>
    </div>
</div>


<!-- --- 5. JAVASCRIPT FOR CONDITIONAL LINKS --- -->
<!-- <script>
document.addEventListener('DOMContentLoaded', function() {
    const isRegistered = localStorage.getItem('event_user_registered_<?php echo urlencode($org_title); ?>');

    if (isRegistered === 'true') {
        // Show all post-registration links
        document.querySelectorAll('#nav-top-qr, #nav-top-ticket, #nav-bottom-qr, #nav-offcanvas-ticket').forEach(el => el.classList.remove('d-none'));
        
        // Hide all registration links
        document.querySelectorAll('#nav-top-registration, #nav-offcanvas-registration').forEach(el => el.classList.add('d-none'));
    }
});
</script> -->

</body>
</html>

