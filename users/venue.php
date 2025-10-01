<?php
// venue.php - Displays event venue and hotel information

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

// --- NEW: FETCH EVENT DETAILS ---
$venue_name = "Event Venue";
$venue_address = "Address to be announced.";
$venue_url = "#";

if (isset($org_details['id'])) {
    $stmt_event = $conn->prepare("SELECT venue_name, venue_address, venue_url FROM event_details WHERE organization_id = ? LIMIT 1");
    $stmt_event->bind_param("i", $org_details['id']);
    $stmt_event->execute();
    $result_event = $stmt_event->get_result();

    if ($event = $result_event->fetch_assoc()) {
        $venue_name = !empty($event['venue_name']) ? htmlspecialchars($event['venue_name']) : $venue_name;
        $venue_address = !empty($event['venue_address']) ? htmlspecialchars($event['venue_address']) : $venue_address;
        $venue_url = !empty($event['venue_url']) ? htmlspecialchars($event['venue_url']) : $venue_url;
         // ADD THIS NEW LINE
    $embed_map_url = "https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=URL_ENCODED_ADDRESS" . urlencode($venue_address);
    }
    $stmt_event->close();
}

// --- 3. Start Output Buffering ---
ob_start();
?>

<style>
    /* --- HERO STYLES (UNCHANGED) --- */
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

    /* --- VENUE STYLES (UNCHANGED) --- */
    .venue-container {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }
    .map-container {
        position: relative;
        overflow: hidden;
        width: 100%;
        padding-top: 56.25%; /* 16:9 Aspect Ratio */
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
    .details-header {
        color: <?php echo $nav_bg_color; ?>;
        font-weight: 700;
        border-bottom: 2px solid <?php echo $nav_bg_color . '40'; ?>;
        padding-bottom: 0.5rem;
        margin-bottom: 1.5rem;
    }

    /* --- NEW STYLES FOR HOTEL CONTENT --- */
    .hotel-section {
        background-color: #f8f9fa;
        padding: 3rem 0;
    }
    .hotel-gallery-img {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }
    .inclusions-list {
        list-style: none;
        padding-left: 0;
    }
    .inclusions-list li {
        margin-bottom: 0.75rem;
        font-size: 1.1rem;
    }
    .inclusions-list .fa-check-circle {
        color: #198754; /* Green for inclusions */
    }
    .inclusions-list .fa-circle-xmark {
        color: #dc3545; /* Red for exclusions */
    }
</style>

<main>
    <div class="hero-section">
       <div class="hero-background-video">
        <div class="hero-overlay"></div>
        <video autoplay muted loop playsinline>
            <source src="../assets/4933324_Indoor_Interior_3840x2160.mp4">
        </video>
     </div>
     <div class="hero-content">
        <h1>Venue & Hotel</h1>
        <p class="lead">Event location and accommodation details.</p>
    </div>
    </div>

   <div class="container venue-container">
        <!-- ORIGINAL VENUE SECTION (UNCHANGED) -->
        <div class="row g-5">
            <div class="col-lg-7">

              <div class="map-container mb-4">
        <iframe
            width="600"
            height="450"
            style="border:0;"
            loading="lazy"
            allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"
            src="<?php echo $embed_map_url; ?>">
        </iframe>
    </div>
                
            <div class="col-lg-5">
                <div class="venue-details">
                    
                   <h3 class="details-header"><?php echo $venue_name; ?>.</h3>
                    
                    <div class="d-flex mb-3">
                        <i class="fas fa-map-marker-alt fa-2x mt-2 me-3" style="color: <?php echo $nav_bg_color; ?>;"></i>
                        <div>
                            <h5 class="mb-0">Address</h5>
                            <p class="lead" style="font-size: 1.1rem;"><?php echo htmlspecialchars($venue_address); ?></p>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mb-4">
    <a href="<?php echo $venue_url; ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fab fa-google me-2"></i>Google Maps</a>
    <a href="https://maps.apple.com/?q=<?php echo urlencode($venue_address); ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fab fa-apple me-2"></i>Apple Maps</a>
    <a href="#" class="btn btn-sm btn-outline-secondary" id="copyLocationLink" data-link="<?php echo $venue_url; ?>"><i class="fas fa-copy me-2"></i>Copy Link</a>
</div>
                </div>
            </div>
        </div>
    </div>

    <!-- NEW HOTEL SECTION (CONTENT FROM HOTEL.PHP) -->
    <div class="hotel-section">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-7">
                    <h3 class="details-header">Accommodation: Hyatt Regency, Gurgaon</h3>
                    <p class="lead">We welcome you to to Hyatt Regency, Gurgaon. A premium 5-star business hotel with world-class event spaces, conveniently located off NH-48 with easy access to Gurgaon’s corporate hub and IMT Manesar.</p>
                    <p>Upon arrival, you will be greeted by the Shell Helix Team to check in. Your stay includes bed and breakfast for 1 night (August 19th). Your room will be assigned upon arrival.</p>
                    <p class="small text-muted"><span class="fw-bold">*Disclaimer:</span> The hotel room is assigned strictly against occupancy determined by the Shell Helix team. We do not permit additional guests.</p>
                    <a href="https://www.hyatt.com/hyatt-regency/en-US/delrg-hyatt-regency-gurgaon" target="_blank" class="btn btn-outline-dark">Visit Hotel Website <i class="fas fa-external-link-alt ms-2"></i></a>
                </div>
                <div class="col-lg-5">
                    <div class="row">
                        <div class="col-12">
                             <img src="../assets/hyatt-front-view.webp" class="img-fluid hotel-gallery-img" alt="Hotel Front View">
                        </div>
                         <div class="col-6">
                             <img src="../assets/hyatt-pool-view.webp" class="img-fluid hotel-gallery-img" alt="Hotel Pool View">
                        </div>
                         <div class="col-6">
                             <img src="../assets/Hyatt-Regency-Gurgaon-Lobby-Lounge.webp" class="img-fluid hotel-gallery-img" alt="Hotel Lobby">
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <div class="row g-5">
                <div class="col-md-6">
                    <h4 class="mb-3">Room Inclusions & Facilities</h4>
                    <ul class="inclusions-list">
                        <li><i class="fas fa-check-circle me-2"></i> All Meals</li>
                        <li><i class="fas fa-check-circle me-2"></i> Access to Pool and Gym</li>
                        <li><i class="fas fa-check-circle me-2"></i> Bottled Mineral Water</li>
                        <li><i class="fas fa-check-circle me-2"></i> Complimentary Wireless Internet Access</li>
                        <li><i class="fas fa-check-circle me-2"></i> Iron and Ironing Board</li>
                    </ul>
                     <p class="mt-4"><strong>Complimentary Breakfast:</strong><br>
                    <i class="fas fa-map-marker-alt me-2"></i> Location: Kitchen District<br>
                    <i class="fas fa-clock me-2"></i> Time: 7:30 am to 9:00 am</p>
                </div>
                <div class="col-md-6">
                     <h4 class="mb-3">Room Exclusions</h4>
                     <ul class="inclusions-list">
                        <li><i class="fas fa-circle-xmark me-2"></i> Room Service</li>
                        <li><i class="fas fa-circle-xmark me-2"></i> Mini Bar</li>
                        <li><i class="fas fa-circle-xmark me-2"></i> Laundry</li>
                    </ul>
                    <p class="mt-4 small text-muted"><span class="fw-bold">*Disclaimer:</span> Please note additional services which sit outside of the Shell Helix programme will be payable by the guest.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.getElementById('copyLocationLink');
    if (copyButton) {
        copyButton.addEventListener('click', function(event) {
            event.preventDefault(); 
            const linkToCopy = this.getAttribute('data-link');
            const originalText = this.innerHTML;
            navigator.clipboard.writeText(linkToCopy).then(() => {
                this.innerHTML = '<i class="fas fa-check me-2"></i>Copied!';
                this.classList.add('disabled');
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.classList.remove('disabled');
                }, 2500);
            }).catch(err => {
                console.error('Failed to copy link: ', err);
                alert('Failed to copy. Please right-click the link to copy.');
            });
        });
    }
});

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
</script>

<?php
// --- 4. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 5. Define Page Variables ---
$page_title = 'Venue & Hotel';

// --- 6. Include the Master Layout ---
include 'layout.php';
?>
