<?php
// layout.php - Master Site Layout

// --- 1. ESTABLISH DATABASE CONNECTION & THEME ---
// This now runs first, making theme variables available to all pages.
require_once $_SERVER['DOCUMENT_ROOT'] . '/travel-agency-panel/config/config.php';

$nav_bg_color = "#1a1a1a";
$nav_text_color = "#ffffff";
$logo_url = '';
$org_title = 'Event Portal'; // Default title

if (isset($_GET['view']) && !empty($_GET['view']) && isset($conn)) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . $org_title : $org_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <style>
        body { background-color: #f4f7f6; }
        .site-wrapper {
            width: 85vw;
            max-width: 1400px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
        }
        @media (max-width: 991.98px) {
            .site-wrapper {
                width: 100%;
                padding-top: 1rem;
                padding-bottom: 80px;
            }
        }
    </style>
</head>
<body>

    <div class="site-wrapper">
        <?php
        // Include the navbar, which will use the theme variables defined above.
        include 'navbar.php'; 
        
        // Display the specific page content.
        if (isset($page_content_html)) {
            echo $page_content_html;
        }
        // +++ INCLUDE THE NEW FOOTER FILE HERE +++
        include 'footer.php';
        
        ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const orgTitle = '<?php echo urlencode($org_title); ?>';
        const isRegistered = localStorage.getItem('event_user_registered_' + orgTitle);
        if (isRegistered === 'true') {
            document.querySelectorAll('#nav-top-qr, #nav-top-ticket, #nav-bottom-qr, #nav-offcanvas-ticket').forEach(el => el.classList.remove('d-none'));
            document.querySelectorAll('#nav-top-registration, #nav-offcanvas-registration').forEach(el => el.classList.add('d-none'));
        }
    });
    </script>
</body>
</html>

