<?php
// notifications.php - Displays user notifications

// --- 1. SETUP AND SESSION ---
session_start();
require_once '../config/config.php';

// --- 2. FETCH THEME DATA ---
$nav_bg_color = "#1a1a1a";
$nav_text_color = "#ffffff";
$logo_url = '';
$org_title = 'Event Portal';

if (isset($_GET['view']) && !empty($_GET['view']) && isset($conn)) {
    // Fetch theme data
    $organization_title = $_GET['view'];
    $stmt_org = $conn->prepare("SELECT id, title, logo_url, bg_color, text_color FROM organizations WHERE title = ? LIMIT 1");
    $stmt_org->bind_param("s", $organization_title);
    $stmt_org->execute();
    $result_org = $stmt_org->get_result();
    
    if ($org_details = $result_org->fetch_assoc()) {
        $org_title = htmlspecialchars($org_details['title']);
        $logo_url = htmlspecialchars($org_details['logo_url']);
        $nav_bg_color = htmlspecialchars($org_details['bg_color']);
        $nav_text_color = htmlspecialchars($org_details['text_color']);
    }
    $stmt_org->close();
}

// --- 3. DUMMY DATA FOR NOTIFICATIONS ---
// This is static data. You can replace this with a database query later.
$notifications_data = [
    [
        'type' => 'info', 
        'title' => 'Session Update', 
        'time' => '1 hour ago', 
        'message' => 'The keynote speech "Future of Tech" in Hall A has been moved from 10:00 AM to 10:30 AM.'
    ],
    [
        'type' => 'warning', 
        'title' => 'Registration Closing Soon', 
        'time' => '4 hours ago', 
        'message' => 'Registration for the "AI & Machine Learning" workshop closes at 5:00 PM today. Please sign up if you wish to attend.'
    ],
    [
        'type' => 'success', 
        'title' => 'Welcome to ' . htmlspecialchars($org_title) . '!', 
        'time' => '1 day ago', 
        'message' => 'Thank you for registering. We are excited to have you. Don\'t forget to check out the event Agenda and Fun Zone!'
    ],
];


// --- 4. Start Output Buffering ---
ob_start();
?>

<style>
    
    .hero-background-video {
    position: absolute;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    z-index: 1; /* Sits behind content */
    overflow: hidden; /* Hides any part of the video spilling out */
    background-attachment: fixed !important; /* This keeps the image still (parallax) */
}

.hero-background-video video {
    /* This combo acts like 'background-size: cover' for a video */
    min-width: 100%;
    min-height: 100%;
    width: auto;
    height: auto;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    
    /* This keeps your scroll-blur effect */
    transition: filter 0.2s ease-out;
}

.hero-overlay {
    /* This is the transparent black gradient */
    position: absolute;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6));
    z-index: 2; /* Sits on top of the video */
}


  .hero-section {
    /* This is now just a container */
    position: relative;
    overflow: hidden; /* This contains the blurred edges of the image */
}
   .hero-content {
    position: relative;
    z-index: 3; /* This puts your text on top of the overlay */
    color: white;
    text-align: center;
    padding: 5rem 1.5rem; /* This gives the hero section its size */

    /* You can add your fixed height rules here if you want */
    height: 30vh; 
    display: flex;
    flex-direction: column;
    justify-content: center;
     overflow: hidden;
}
    .notifications-container {
        max-width: 900px;
        margin: 3rem auto;
        padding: 2rem;
    }
    .notification-alert {
        border-left-width: 5px;
        box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.05);
    }
    .notification-alert .alert-heading {
        font-weight: 500;
    }
    .notification-alert .notification-time {
        font-size: 0.85rem;
        font-weight: 500;
        color: #6c757d;
    }
</style>

<main>
     <div class="hero-section">

       <div class="hero-background-video">
        <div class="hero-overlay"></div>
        <video autoplay muted loop playsinline>
            <source src="../assets/notification1.mp4">
        </video>
     </div>

     <div class="hero-content">
        <h1>Event Notifications</h1>
        <p class="lead">Do not miss important updates.</p>
    </div>

</div>

    <div class="container notifications-container">
        <div class="notification-list">
            
            <?php if (empty($notifications_data)): ?>
                <div class="alert alert-light text-center">
                    <i class="fas fa-bell-slash fa-2x mb-3"></i>
                    <h4 class="alert-heading">No New Notifications</h4>
                    <p>You're all caught up!</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications_data as $notification): ?>
                    <div class="alert notification-alert alert-<?php echo htmlspecialchars($notification['type']); ?>" role="alert">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="alert-heading mb-0"><?php echo htmlspecialchars($notification['title']); ?></h5>
                            <span class="notification-time"><?php echo htmlspecialchars($notification['time']); ?></span>
                        </div>
                        <hr>
                        <p class="mb-0"><?php echo htmlspecialchars($notification['message']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</main>
<script>
    const bgVideo = document.querySelector('.hero-background-video video');
    
    if (bgVideo) {
        window.addEventListener('scroll', () => {
            const scrollPos = window.scrollY;

            // --- 1. Calculate Parallax ---
            // This moves the video vertically at 50% of the scroll speed.
            // This creates the "parallax" effect.
            const parallaxOffset = scrollPos * 0.9;

            // --- 2. Calculate Blur ---
            let blurAmount = (scrollPos / 300) * 8; 
            if (blurAmount > 8) {
                blurAmount = 8;
            }
            
            // --- 3. Apply Both Styles ---
            // We combine the original centering transform with our new parallax 'translateY'
            // and apply the blur filter.
            bgVideo.style.transform = `translate(-50%, -50%) translateY(${parallaxOffset}px)`;
            bgVideo.style.filter = `blur(${blurAmount}px)`;
        });
    }
</script>

<?php
// --- 5. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 6. Define Page Variables ---
$page_title = 'Notifications';

// --- 7. Include the Master Layout ---
include 'layout.php';
?>