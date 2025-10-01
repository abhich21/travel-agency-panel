<?php
// faq.php - Displays Frequently Asked Questions

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
// --- FETCH FAQ DETAILS ---
$faqs = [];
if (isset($org_details['id'])) {
    $stmt_faq = $conn->prepare("SELECT * FROM faqs WHERE organization_id = ? ORDER BY sort_order ASC");
    $stmt_faq->bind_param("i", $org_details['id']);
    $stmt_faq->execute();
    $result_faq = $stmt_faq->get_result();
    while ($item = $result_faq->fetch_assoc()) {
        $faqs[] = $item;
    }
    $stmt_faq->close();
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

    /* --- FAQ STYLES (UNCHANGED) --- */
    .faq-container {
        max-width: 900px;
        margin: 3rem auto;
        padding: 0 1rem; /* Adjusted padding for smaller screens */
    }
    .faq-main-title {
        font-weight: 700;
    }
    .accordion-button {
        font-weight: 500;
        font-size: 1.1rem;
    }
    .accordion-button:not(.collapsed) {
        color: <?php echo $nav_text_color; ?>;
        background-color: <?php echo $nav_bg_color; ?>;
    }
    .accordion-button:focus {
        box-shadow: 0 0 0 0.25rem <?php echo $nav_bg_color . '40'; ?>;
    }
    .accordion-button:not(.collapsed)::after {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23<?php echo substr($nav_text_color, 1); ?>'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    }
</style>

<main>
    <div class="hero-section">
       <div class="hero-background-video">
        <div class="hero-overlay"></div>
        <video autoplay muted loop playsinline>
            <source src="../assets/faq1.mp4">
        </video>
     </div>
     <div class="hero-content">
        <h1>Frequently Asked Questions</h1>
        <p class="lead">Your questions, answered.</p>
    </div>
    </div>

    <div class="container faq-container">
        <h2 class="faq-main-title text-center mb-5">Unleash the Power in New Delhi – a new era of Shell Helix FAQs</h2>
        
        <div class="accordion accordion-flush" id="faqAccordion">
            
            <!-- CONTENT FROM FAQ2.PHP IS NOW HERE -->
            <div class="accordion accordion-flush" id="faqAccordion">

    <?php if (empty($faqs)): ?>
        <p class="text-center mt-4">Frequently asked questions are coming soon.</p>
    <?php else: ?>
        <?php foreach ($faqs as $index => $faq): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-<?php echo $faq['id']; ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $faq['id']; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $faq['id']; ?>">
                        <?php echo ($index + 1) . '. ' . htmlspecialchars($faq['question']); ?>
                    </button>
                </h2>
                <div id="collapse-<?php echo $faq['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $faq['id']; ?>" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        <?php echo $faq['answer']; // Echo directly to render HTML from TinyMCE ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

        </div>
    </div>
</main>
<script>
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
$page_title = 'FAQs';

// --- 6. Include the Master Layout ---
include 'layout.php';
?>
