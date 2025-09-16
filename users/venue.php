<?php
// venue.php - Displays event venue information

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

// --- 3. Start Output Buffering ---
ob_start();
?>

<style>
    .venue-header {
        padding: 4rem 1.5rem;
        text-align: center;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    .venue-container {
        margin: 3rem auto;
        padding: 2rem;
    }
    .map-container {
        position: relative;
        overflow: hidden;
        width: 100%;
        padding-top: 50%; /* 4:3 Aspect Ratio */
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
    }
    .map-container iframe {
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        width: 100%;
        height: 100%;
    }
    .venue-details h3 {
        color: <?php echo $nav_bg_color; ?>;
        font-weight: 700;
        border-bottom: 2px solid <?php echo $nav_bg_color . '40'; ?>;
        padding-bottom: 0.5rem;
    }
</style>

<main>
    <div class="venue-header">
        <h1>Event Venue</h1>
        <p class="lead">Find your way to <?php echo $org_title; ?>!</p>
    </div>

    <div class="container venue-container">
        <div classs="row g-5">
            <div class="col-lg-7">
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3506.222770289133!2d77.42995297510769!3d28.502930475734316!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x390ce817ff9b039f%3A0x62735741f2374de4!2sIndia%20Exposition%20Mart%20Ltd!5e0!3m2!1sen!2sin!4v1726482181163!5m2!1sen!2sin" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="venue-details">
                    <h3>The Grand Conference Center</h3>
                    <p class="lead">
                        123 Event Horizon Plaza,<br>
                        Knowledge Park II,<br>
                        Greater Noida, Uttar Pradesh 201310
                    </p>
                    <hr class="my-4">
                    
                    <h5>About the Venue</h5>
                    <p>The Grand Conference Center is a state-of-the-art facility designed to host world-class events. With over 200,000 sq ft of flexible event space, it's the perfect location for our gathering.</p>

                    <h5 class="mt-4">Parking</h5>
                    <p>Ample on-site parking is available in Garage B and Garage C. A flat rate of ₹200 applies for all-day event parking.</p>

                    <h5 class="mt-4">Public Transport</h5>
                    <p>The venue is a 5-minute walk from the "Expo Mart" Aqua Line metro station. Multiple bus lines also service the direct area.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php
// --- 4. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 5. Define Page Variables ---
$page_title = 'Event Venue';

// --- 6. Include the Master Layout ---
include 'layout.php';
?>