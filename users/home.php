<?php
// home.php - Main Landing Page

// --- 1. ESTABLISH DATABASE CONNECTION & THEME ---
// This logic is now at the top of the entry-point file.
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

<!-- Page-specific styles and HTML for the Home Page -->
<style>
    .hero-section {
        background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('');
        background-size: cover;
        background-position: center;
        color: white;
        text-align: center;
        padding: 6rem 1.5rem;
    }

    .hero-section h1 {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .hero-section .lead {
        font-size: 1.25rem;
        margin-bottom: 2rem;
        max-width: 600px;
        margin-left: auto;
        margin-right: auto;
    }

    #countdown {
        display: flex;
        flex-wrap: wrap; /* Allow wrapping on smaller screens */
        justify-content: center;
        gap: 1rem;
        margin-top: 2rem;
    }

    .countdown-item {
        background: rgba(0, 0, 0, 0.4);
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        min-width: 100px;
    }

    .countdown-item span {
        display: block;
        font-size: 2.5rem;
        font-weight: 700;
    }

    .countdown-item p {
        margin: 0;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .about-section {
        padding: 4rem 1.5rem;
    }
</style>

<main>
    <div class="hero-section">
        <!-- This now works because $org_title was defined before this HTML was processed -->
        <h1><?php echo $org_title; ?></h1>
        <p class="lead">Welcome to our exclusive event portal. Here you'll find everything you need to know about the upcoming event. Stay tuned for an unforgettable experience!</p>
        
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

    <div class="container about-section text-center">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2>About The Event</h2>
                <p class="lead">This is where a more detailed description of the event will go. You can add information about the speakers, the purpose of the event, key highlights, and what attendees can expect to gain from participating.</p>
            </div>
        </div>
    </div>
</main>

<script>
// --- JAVASCRIPT FOR COUNTDOWN TIMER ---
document.addEventListener('DOMContentLoaded', function() {
    // --- IMPORTANT: SET YOUR EVENT DATE HERE ---
    const countDownDate = new Date("Dec 25, 2025 10:00:00").getTime();

    const updateCountdown = () => {
        const now = new Date().getTime();
        const distance = countDownDate - now;

        const countdownElement = document.getElementById("countdown");
        if (!countdownElement) return;

        // If the countdown is over, display a message
        if (distance < 0) {
            countdownElement.innerHTML = "<div class='text-center w-100'><h3>The Event has Started!</h3></div>";
            clearInterval(interval);
            return;
        }

        // Time calculations for days, hours, minutes and seconds
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Get the elements to display the countdown
        const daysEl = document.getElementById("days");
        const hoursEl = document.getElementById("hours");
        const minutesEl = document.getElementById("minutes");
        const secondsEl = document.getElementById("seconds");

        // Update the elements with the new values, adding a leading zero if needed
        if (daysEl) daysEl.innerText = String(days).padStart(2, '0');
        if (hoursEl) hoursEl.innerText = String(hours).padStart(2, '0');
        if (minutesEl) minutesEl.innerText = String(minutes).padStart(2, '0');
        if (secondsEl) secondsEl.innerText = String(seconds).padStart(2, '0');
    };

    // Update the countdown every 1 second
    const interval = setInterval(updateCountdown, 1000);
    
    // Run the function once immediately to avoid a 1-second delay before the timer appears
    updateCountdown(); 
});
</script>

<?php
// --- 3. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 4. Define Page Variables ---
$page_title = 'Home';

// --- 5. Include the Master Layout ---
// The layout will now use the variables we created at the top of this file.
include 'layout.php';
?>

