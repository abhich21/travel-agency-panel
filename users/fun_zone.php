<?php
// fun_zone.php - Displays games and engagement modules

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
    .funzone-container {
        max-width: 1100px;
        margin: 3rem auto;
        padding: 2rem;
    }
    .fun-card {
        border: none;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        text-align: center;
        padding: 2rem;
        height: 100%;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .fun-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.15);
    }
    .fun-card i {
        font-size: 3rem;
        color: <?php echo $nav_bg_color; ?>;
        margin-bottom: 1.5rem;
    }
    .fun-card .btn-custom {
        background-color: <?php echo $nav_bg_color; ?>;
        color: <?php echo $nav_text_color; ?>;
        border: none;
    }
    .fun-card .btn-custom:hover,
.fun-card .btn-custom:active,
.fun-card .btn-custom:focus {
    background-color: <?php echo $nav_bg_color; ?>; /* Re-assert the background color */
    color: <?php echo $nav_text_color; ?>; /* Re-assert the text color */
    opacity: 0.9;
    box-shadow: none; /* Remove Bootstrap's default focus shadow */
}
</style>

<main>
    <div class="hero-section">

       <div class="hero-background-video">
        <div class="hero-overlay"></div>
        <video autoplay muted loop playsinline>
            <source src="../assets/funzone5.mp4">
        </video>
     </div>

     <div class="hero-content">
        <h1>Fun Zone</h1>
      
    </div>

</div>

    <div class="container funzone-container">
        <div class="row g-4">
            
            <div class="col-lg-4 col-md-6">
                <div class="fun-card">
                    <i class="fas fa-question-circle"></i>
                    <h4 class="card-title">Event Trivia</h4>
                    <p class="card-text">How much do you know about our speakers and <?php echo $org_title; ?>? Test your knowledge and climb the leaderboard!</p>
                    <a href="#" class="btn btn-custom">Play Now</a>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="fun-card">
                    <i class="fas fa-camera-retro"></i>
                    <h4 class="card-title">Virtual Photo Booth</h4>
                    <p class="card-text">Share your event selfie! Use our custom event frames and share your photo on social media with our event hashtag.</p>
                    <a href="#" class="btn btn-custom">Open Booth</a>
                </div>
            </div>

            <div class="col-lg-4 col-md-6">
                <div class="fun-card">
                    <i class="fas fa-chart-bar"></i>
                    <h4 class="card-title">Live Event Poll</h4>
                    <p class="card-text">What was your favorite session from Day 1? Cast your vote now and see the results in real-time.</p>
                    <a href="#" class_name="btn btn-custom">Vote Now</a>
                </div>
            </div>

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
// --- 4. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 5. Define Page Variables ---
$page_title = 'Fun Zone';

// --- 6. Include the Master Layout ---
include 'layout.php';
?>