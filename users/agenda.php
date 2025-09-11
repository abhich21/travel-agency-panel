<?php
// agenda.php - Event Agenda Page

// --- 1. ESTABLISH DATABASE CONNECTION & THEME ---
require_once '../config/config.php';

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

// --- 2. Start Output Buffering ---
ob_start();
?>
<!-- All the HTML and page-specific styles from agenda_content.php are now here -->
<style>
    /* Page-specific styles are co-located with their content */
    .agenda-header {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://placehold.co/1920x400/cccccc/000000?text=Event+Venue');
        background-size: cover;
        background-position: center;
        padding: 4rem 1.5rem; /* Added padding for smaller screens */
        color: white;
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .agenda-header h1 {
        font-weight: 700;
        font-size: 3rem;
    }

   .timeline {
    position: relative;
    /* Increased left padding from 1.5rem to 150px to create a gutter */
    padding: 2rem 1.5rem 2rem 150px; 
}

    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        left: 170px; /* Adjusted position */
        height: 100%;
        width: 4px;
        background: #e9ecef;
        border-radius: 2px;
    }
    .timeline-item { margin-bottom: 2rem; position: relative; }
    .timeline-item::after { content: ''; display: block; clear: both; }
    .timeline-icon { position: absolute; top: 0; left:2px; width: 40px; height: 40px; border-radius: 50%; background: <?php echo isset($nav_bg_color) ? $nav_bg_color : '#343a40'; ?>; color: <?php echo isset($nav_text_color) ? $nav_text_color : '#ffffff'; ?>; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border: 3px solid #fff; box-shadow: 0 0 0 3px <?php echo isset($nav_bg_color) ? $nav_bg_color : '#343a40'; ?>; }
   .timeline-icon i {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}
    .timeline-content { margin-left: 75px; background: #fff; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
   .timeline-time {
    /* Position the time block on the left */
    position: absolute;
    left: -190px;
    top: 8px; /* Fine-tune vertical alignment */
    width: 200px; /* Give it a fixed width */
    text-align: right; /* Align text to the right */
    font-weight: 700;
    color: #333;
    padding-right: 20px; /* Space between time and the line */
    font-size: 0.85rem;
}
    .timeline-title { font-weight: 600; font-size: 1.25rem; margin-bottom: 0.5rem; }
    .timeline-description { color: #6c757d; }
    .day-header { font-size: 1.8rem; font-weight: 700; margin-bottom: 2rem; padding-left: 75px; }
</style>

<main>
    <header class="agenda-header">
        <h1>Event Agenda</h1>
        <p class="lead">Here's what you can look forward to.</p>
    </header>

    <div class="container-fluid">
        <!-- DAY 1 -->
        <h2 class="day-header">Day 1: Welcome & Innovation</h2>
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-time">09:00 AM - 11:00 AM</div>
                <div class="timeline-icon"><i class="fas fa-plane-arrival"></i></div>
                <div class="timeline-content">
                    <h3 class="timeline-title">Arrival & Registration</h3>
                    <p class="timeline-description">Welcome! Check in at the main lobby, collect your welcome kit, and enjoy some refreshments.</p>
                </div>
            </div>
             <div class="timeline-item">
                 <div class="timeline-time">11:30 AM - 01:00 PM</div>
                <div class="timeline-icon"><i class="fas  fa-chalkboard-user"></i></div>
                <div class="timeline-content">
                    <h3 class="timeline-title">Opening Keynote</h3>
                    <p class="timeline-description">Join our CEO for an inspiring opening talk on the future of our industry. <br><small class="text-muted"><i class="fas fa-map-marker-alt me-2"></i>Grand Ballroom</small></p>
                </div>
            </div>
            <!-- ... more items ... -->
        </div>

         <!-- DAY 2 -->
        <h2 class="day-header mt-5">Day 2: Workshops & Celebration</h2>
        <div class="timeline">
            <div class="timeline-item">
               <div class="timeline-time">07:00 PM - 10:00 PM</div>
                <div class="timeline-icon"><i class="fas fa-glass-cheers"></i></div>
                <div class="timeline-content">
                    <h3 class="timeline-title">Gala Dinner & Closing Party</h3>
                    <p class="timeline-description">Let's celebrate a successful event with dinner, music, and entertainment!</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// --- 2. Store the captured HTML ---
// Stop capturing and put everything that was held into the $page_content_html variable.
$page_content_html = ob_get_clean();

// --- 3. Define Page Variables ---
$page_title = 'Event Agenda';

// --- 4. Include the Master Layout ---
// The layout file will now use the $page_content_html variable to render the content.
include 'layout.php';
?>

