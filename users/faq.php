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

// --- 3. Start Output Buffering ---
ob_start();
?>

<style>
    .faq-header {
        padding: 4rem 1.5rem;
        text-align: center;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    .faq-container {
        max-width: 900px;
        margin: 3rem auto;
        padding: 2rem;
    }
    /* Style the accordion to match the theme */
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
    <div class="faq-header">
        <h1>Frequently Asked Questions</h1>
        <p class="lead">Have questions? We've got answers.</p>
    </div>

    <div class="container faq-container">
        <div class="accordion" id="faqAccordion">
            
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        What is the dress code for the event?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        The dress code for the event is <strong>Business Casual</strong>. We recommend smart trousers or skirts, button-down shirts or blouses, and comfortable shoes as there will be a significant amount of networking and walking.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        Is parking available at the venue?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes, ample on-site parking is available in Garage B and Garage C. A flat rate of ₹200 applies for all-day event parking. Please see the <strong>Venue</strong> page for a map and more details.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        What does my ticket include?
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Your event ticket includes full access to all keynote sessions, breakout tracks, the exhibition hall, and the networking lunch on both days. It also includes complimentary tea, coffee, and refreshments during official breaks.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        Can I get a refund if I am unable to attend?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Unfortunately, we are unable to offer refunds as per the policy stated during registration. However, you may transfer your ticket to a colleague or friend. Please contact our Helpdesk through the link in the navigation bar at least 48 hours before the event to request a name change.
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<?php
// --- 4. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 5. Define Page Variables ---
$page_title = 'FAQs';

// --- 6. Include the Master Layout ---
include 'layout.php';
?>