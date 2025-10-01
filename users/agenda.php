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
// --- FETCH AGENDA DETAILS ---
$agenda_by_day = [];
if (isset($org_details['id'])) {
    $stmt_agenda = $conn->prepare("SELECT * FROM agenda_items WHERE organization_id = ? ORDER BY day_number ASC, sort_order ASC");
    $stmt_agenda->bind_param("i", $org_details['id']);
    $stmt_agenda->execute();
    $result_agenda = $stmt_agenda->get_result();
    while ($item = $result_agenda->fetch_assoc()) {
        // Group items into a nested array by their day number
        $agenda_by_day[$item['day_number']][] = $item;
    }
    $stmt_agenda->close();
}

// --- 2. Start Output Buffering ---
ob_start();
?>
<!-- All the HTML and page-specific styles are now here -->
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
    
    .hero-content h1 {
        font-weight: 700;
        font-size: 3rem;
    }

    /* --- TIMELINE STYLES (UPDATED FOR LEFT TIMESTAMPS) --- */
    .timeline-container {
        background-color: #f8f9fa;
        padding: 3rem 0;
    }

    .timeline {
        position: relative;
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem 0;
    }

    .timeline::before {
        content: '';
        position: absolute;
        top: 0;
        left: 148px; /* Positioned to align with icons */
        height: 100%;
        width: 4px;
        background: #e9ecef;
        border-radius: 2px;
    }

    .timeline-item { 
        margin-bottom: 2rem; 
        position: relative; 
        padding-left: 180px; /* Creates gutter for icon and content */
    }
    
    .timeline-icon { 
        position: absolute; 
        top: 5px; 
        left: 128px; /* Aligned with the timeline bar */
        width: 44px; 
        height: 44px; 
        border-radius: 50%; 
        background: <?php echo isset($nav_bg_color) ? $nav_bg_color : '#343a40'; ?>; 
        color: <?php echo isset($nav_text_color) ? $nav_text_color : '#ffffff'; ?>; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        font-size: 1.2rem; 
        border: 4px solid #f8f9fa; 
    }

    .timeline-content { 
        background: #fff; 
        padding: 1.5rem; 
        border-radius: 0.5rem; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
    }

    .timeline-time {
        position: absolute;
        left: 0;
        top: 8px; /* Vertically aligns with the icon */
        width: 120px; /* Width of the time column */
        text-align: right;
        font-weight: 700;
        font-size: 1.1rem;
        color: #343a40;
    }

    .timeline-title { 
        font-weight: 600; 
        font-size: 1.25rem; 
        margin-bottom: 0.5rem; 
    }

    .timeline-description { 
        color: #6c757d; 
    }

    .day-header { 
        font-size: 2.2rem; 
        font-weight: 700; 
        text-align: center; 
        margin-bottom: 2rem; 
    }
    
    /* Styles for Dress Code block inside timeline */
    .timeline-dress-code {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e9ecef;
    }

    .timeline-dress-code h5 {
        font-weight: 600;
    }

    .dress-code-images {
        display: flex;
        gap: 0.5rem;
        margin: 0.5rem 0;
        flex-wrap: wrap;
    }
    
    .dress-code-image {
        width: 80px;
        height: 80px;
        border-radius: 0.25rem;
        object-fit: cover;
    }
</style>

<main>
   <!-- HERO SECTION (UNCHANGED) -->
   <div class="hero-section">
       <div class="hero-background-video">
        <div class="hero-overlay"></div>
        <video autoplay muted loop playsinline>
            <source src="../assets/agenda2.mp4">
        </video>
     </div>
     <div class="hero-content">
        <h1>Event Agenda</h1>
        <p class="lead">Here's what you can look forward to.</p>
    </div>
   </div>

    <!-- RESTORED TIMELINE WITH NEW CONTENT -->
    <div class="timeline-container">
    <div class="container">
        <?php if (empty($agenda_by_day)): ?>
            <h2 class="day-header">Agenda Coming Soon</h2>
            <p class="text-center">Please check back later for the detailed event schedule.</p>
        <?php else: ?>
            <?php foreach ($agenda_by_day as $day_number => $items): ?>
                <h2 class="day-header mt-5">Day <?php echo $day_number; ?></h2>
                <div class="timeline">
                    <?php foreach ($items as $item): ?>
                        <div class="timeline-item">
                            <?php if (!empty($item['item_time'])): ?>
                                <div class="timeline-time"><?php echo htmlspecialchars($item['item_time']); ?></div>
                            <?php endif; ?>
                            <div class="timeline-icon"><i class="<?php echo htmlspecialchars($item['icon_class']); ?>"></i></div>
                            <div class="timeline-content">
                                <h3 class="timeline-title"><?php echo htmlspecialchars($item['title']); ?></h3>
                                <?php if (!empty($item['description'])): ?>
                                    <div class="timeline-description">
                                        <?php echo $item['description']; // Echo directly to render HTML from TinyMCE ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</main>
<script>
    // SCRIPT FOR PARALLAX & BLUR EFFECTS (UNCHANGED)
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
// --- 3. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 4. Define Page Variables ---
$page_title = 'Event Agenda';

// --- 5. Include the Master Layout ---
include 'layout.php';
?>

