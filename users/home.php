<?php
// home.php - Main Landing Page

// --- 1. ESTABLISH DATABASE CONNECTION & THEME ---
require_once '../config/config.php';

$nav_bg_color = "#1a1a1a";
$nav_text_color = "#ffffff";
$logo_url = '';
$org_title = 'Event Portal'; // Default title

if (isset($_GET['view']) && !empty($_GET['view']) && isset($conn)) {
    $organization_title = $_GET['view'];
    $stmt = $conn->prepare("SELECT id, title, logo_url, bg_color, text_color FROM organizations WHERE title = ? LIMIT 1");
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

// --- FETCH EVENT DETAILS ---
$event_date_display = "TBA"; // Default text if no date is set
$event_date_countdown = date('M d, Y'); // Default countdown to today
// ADD THESE TWO LINES
$home_about_title = "About The Event"; // Default title
$home_about_content = "<p>Event details are coming soon. Please check back later.</p>"; // Default content

// Check if we successfully found an organization from the previous step
if (isset($org_details['id'])) {
    $stmt_event = $conn->prepare("SELECT event_date, home_about_title, home_about_content FROM event_details WHERE organization_id = ? LIMIT 1");
    $stmt_event->bind_param("i", $org_details['id']);
    $stmt_event->execute();
    $result_event = $stmt_event->get_result();

   if ($event = $result_event->fetch_assoc()) {
    // Make sure the date is not empty in the database
    if (!empty($event['event_date'])) {
        $date = new DateTime($event['event_date']);
        $event_date_display = $date->format('F j, Y');
        $event_date_countdown = $date->format('M d, Y');
    }

    // ADD THIS NEW CODE BLOCK
    if (!empty($event['home_about_title'])) {
        $home_about_title = htmlspecialchars($event['home_about_title']);
    }
    if (!empty($event['home_about_content'])) {
        // We do NOT use htmlspecialchars here so HTML from the editor will render correctly.
        $home_about_content = $event['home_about_content'];
    }
}
    $stmt_event->close();
}

// --- 2. Start Output Buffering ---
ob_start();
?>

<!-- Page-specific styles and HTML for the Home Page -->
<style>
    /* --- HERO SECTION STYLES (UNCHANGED) --- */
    .hero-background-video {
        position: absolute;
        top: 0; left: 0;
        width: 100%;
        height: 100%;
        z-index: 1;
        overflow: hidden;
        background-attachment: fixed !important;
    }

    .hero-background-video video {
        min-width: 100%;
        min-height: 100%;
        width: auto;
        height: auto;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        transition: filter 0.2s ease-out;
    }

    .hero-overlay {
        position: absolute;
        top: 0; left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6));
        z-index: 2;
    }

    .hero-section {
        position: relative;
        overflow: hidden;
    }

    .hero-content {
        position: relative;
        z-index: 3;
        color: white;
        text-align: center;
        padding: 5rem 1.5rem;
        height: 30vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        overflow: hidden;
    }

    .hero-section .lead {
        font-size: 1rem;
        margin-bottom: 1rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    #countdown {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 1rem;
        margin-top: 1rem;
    }

    .countdown-item {
        background: rgba(197, 194, 194, 0.4);
        padding: 1rem;
        border-radius: 0.5rem;
        min-width: 100px;
    }

    .countdown-item span {
        display: block;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .countdown-item p {
        margin: 0;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* --- CONTENT SECTIONS STYLES (CORRECTED) --- */
    .about-section, .event-details-section {
        background-color: #f8f9fa; /* Consistent light grey background */
        padding: 3rem 0; /* Consistent vertical spacing */
    }

    .event-bullet-points {
        list-style: none;
        padding-left: 0;
    }

    .event-bullet-points .fa-check-circle {
        color: <?php echo $nav_bg_color; ?>;
    }

    .feature-card {
    background-color: #ffffff;
    border-radius: 0.5rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.07);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    padding: 2rem 1.5rem; /* Added more top padding */
    display: flex;
    flex-direction: column;
    align-items: center; /* Horizontally centers content */
    /* REMOVED justify-content: center; */
    text-align: center;
    gap: 0.5rem;
}

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.12);
    }

    .icon-circle {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background-color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        border: 1px solid #ddd;
    }

    .icon-circle i {
        color: <?php echo $nav_bg_color; ?>; /* Use the theme color */
    }

    .feature-card strong {
        font-size: 1rem;
        font-weight: 600;
        color: #343a40;
    }

    .feature-card p, .feature-card .btn-contact {
        font-size: 1.1rem;
        color: #495057;
        margin: 0;
    }

    .feature-card i {
        font-style: normal;
    }
    
    .btn-contact {
        background-color: transparent;
        border: none;
        font-weight: bold;
        padding: 0;
    }

    .btn-contact:hover {
        text-decoration: underline;
    }
</style>

<main>
    <!-- HERO SECTION (UNCHANGED) -->
    <div class="hero-section">
        <div class="hero-background-video">
            <div class="hero-overlay"></div>
            <video autoplay muted loop playsinline>
                <source src="../assets/From KlickPin CF Pin by sasha on video engine wallpaper _ Light background images Overlays instagram Green screen video backgrounds.mp4" type="video/mp4">
            </video>
        </div> 
        <div class="hero-content">
            <p class="lead"><strong>Join us for an unforgettable event where power takes center stage and celebrations turn into memorable moments.</strong></p>
            <div id="countdown">
                <div class="countdown-item">
                    <span id="days">00</span>
                    <p>Days</p>
                </div>
                <div class="countdown-item">
                    <span id="hours">00</span>
                    <p>Hours</p>
                </div>
                <div class="countdown-item">
                    <span id="minutes">00</span>
                    <p>Minutes</p>
                </div>
                <div class="countdown-item">
                    <span id="seconds">00</span>
                    <p>Seconds</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ABOUT SECTION (NEW AND CORRECTED) -->
  <section class="about-section">
    <div class="container">
        <div class="row justify-content-center">
            
            <div class="col-lg-10 text-center">
                <h2><?php echo $home_about_title; ?></h2>
                <div class="lead text-start">
                    <?php echo $home_about_content; ?>
                </div>
            </div>

        </div>
    </div>
</section>

    <!-- EVENT DETAILS SECTION (NEW AND CORRECTED) -->
    <section class="event-details-section">
        
        <div class="container">
            <h3 class="text-center mb-4">Event Details</h3>
            <div class="row text-center align-items-stretch">
                <div class="col-md-3 mb-4">
                    <div class="feature-card h-100">
                        <div class="icon-circle">
                            <i class="fa-regular fa-calendar-days fa-2x"></i>
                        </div>
                        <strong>Date</strong>
                        <p class="detail-text mb-0"><i><?php echo $event_date_display; ?></i></p>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="feature-card h-100" onclick="location.href='agenda.php#agenda-dress-code'" style="cursor: pointer;">
                        <div class="icon-circle">
                            <i class="fas fa-tshirt fa-2x"></i>
                        </div>
                        <strong>Dress Code</strong>
                        <p class="detail-text mb-0">Business casual (refer to dress code for specific days)</p>
                    </div>
                </div>
               <div class="col-md-3 mb-4">
    <div class="feature-card h-100" 
         onclick="location.href='venue.php?view=<?php echo urlencode($org_title); ?>'" 
         style="cursor: pointer;">
        <div class="icon-circle">
            <i class="fas fa-bed fa-2x"></i>
        </div>
        <strong>Hotel</strong>
        <p class="detail-text mb-0"><i>Hyatt Regency</i></p>
    </div>
</div>
                <div class="col-md-3 mb-4">
                    <div class="feature-card h-100">
                        <div class="icon-circle">
                            <i class="far fa-envelope fa-2x"></i>
                        </div>
                        <button type="button" class="btn btn-contact" 
                                data-bs-toggle="popover" 
                                data-bs-title="Contact Us" 
                                data-bs-content="Reach out to your immediate Zonal Managers or ICAM">
                                Contact us</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- CORRECTED COUNTDOWN DATE ---
    const countDownDate = new Date("<?php echo $event_date_countdown; ?> 00:00:00 GMT+0530").getTime();
    const updateCountdown = () => {
        const now = new Date().getTime();
        const distance = countDownDate - now;
        const countdownElement = document.getElementById("countdown");
        if (!countdownElement) return;

        if (distance < 0) {
            clearInterval(interval);
            countdownElement.innerHTML = "<div class='text-center w-100'><h3>The Event has Started!</h3></div>";
            const videoElement = document.querySelector('.hero-background-video video');
            if (videoElement) {
                videoElement.src = '../assets/confetti2.mp4';
                videoElement.addEventListener('loadedmetadata', () => {
                    videoElement.play();
                    const currentScroll = window.scrollY;
                    const parallaxOffset = currentScroll * 0.9;
                    let blurAmount = (currentScroll / 300) * 8;
                    if (blurAmount > 8) { blurAmount = 8; }
                    videoElement.style.transform = `translate(-50%, -50%) translateY(${parallaxOffset}px)`;
                    videoElement.style.filter = `blur(${blurAmount}px)`;
                }, { once: true });
                videoElement.load();
            }
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        const daysEl = document.getElementById("days");
        const hoursEl = document.getElementById("hours");
        const minutesEl = document.getElementById("minutes");
        const secondsEl = document.getElementById("seconds");

        if (daysEl) daysEl.innerText = String(days).padStart(2, '0');
        if (hoursEl) hoursEl.innerText = String(hours).padStart(2, '0');
        if (minutesEl) minutesEl.innerText = String(minutes).padStart(2, '0');
        if (secondsEl) secondsEl.innerText = String(seconds).padStart(2, '0');
    };

    const interval = setInterval(updateCountdown, 1000);
    updateCountdown();

    // --- SCRIPT FOR PARALLAX & BLUR EFFECTS (UNCHANGED) ---
    const bgVideo = document.querySelector('.hero-background-video video');
    if (bgVideo) {
        window.addEventListener('scroll', () => {
            const scrollPos = window.scrollY;
            const parallaxOffset = scrollPos * 0.9;
            let blurAmount = (scrollPos / 300) * 8;
            if (blurAmount > 8) {
                blurAmount = 8;
            }
            bgVideo.style.transform = `translate(-50%, -50%) translateY(${parallaxOffset}px)`;
            bgVideo.style.filter = `blur(${blurAmount}px)`;
        });
    }

    // --- SCRIPT FOR POPOVER ---
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })
});
</script>

<?php
// --- 3. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 4. Define Page Variables ---
$page_title = 'Home';

// --- 5. Include the Master Layout ---
include 'layout.php';
?>
