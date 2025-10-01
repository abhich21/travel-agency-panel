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
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                        1. How will I be able to book my flight / train for the event?
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Fill all details in the dedicated Registration Website for Unleash the Power in New Delhi - a new era of Shell Helix event. Shell team will enable return travel for the event back to base location.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                        2. Will I need to share KYC details for flight/train bookings?
                    </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes, kindly share your KYC details onto the dedicated event website to enable ticket bookings.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                        3. I don’t have direct flight to New Delhi from my base location.
                    </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        If you don’t have direct flight from your base location, you will need to reach the nearest airport to take the flight. Shell will provide flight tickets from the nearest airport to New Delhi and back to your base location.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFour">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                        4. Once I arrive at the New Delhi airport how do I travel to the hotel?
                    </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Shell team coordinators will be available at the airport arrival just outside the terminal holding the Shell Pecten, kindly follow them to the designated HSSE compliant coach for your travel to the Hotel.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFive">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                        5. What does your trip includes?
                    </button>
                </h2>
                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Airfare to and from New Delhi <br><br>
                        Round-trip airport transportation during the program dates of 19th Aug – 20th Aug <br><br>
                        Accommodation for 1 guest at the Hyatt Regency, Gurgaon <br><br>
                        Exclusive dining experiences and meals during the program dates <br><br>
                        Activities as shown on the agenda <br><br>
                        Not included: Transportation to/from your home city airport; transportation to/from the hotels on non-program location or dates; luggage fees; hotel incidentals such as telephone calls, room service, mini-bar purchases, internet charges, spa and laundry.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSix">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                        6. Can I request to cancel/transfer my participation to someone I nominate?
                    </button>
                </h2>
                <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes, you may nominate a business associate for attending the event on your behalf or to represent your business only at the time of registering on the dedicated brand event website. However, once the ticket booking is done. No changes will be entertained.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSeven">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                        7. Can I extend the date of return after the event is concluded?
                    </button>
                </h2>
                <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes, you may extend your date of return post the Unleash the Power in New Delhi – a New Era of Shell Helix event. However, all arrangements need to be done by the guest on their own cost. Shell will provide the return day ticket provided there is no substantial increase in the air fare.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingEight">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight" aria-expanded="false" aria-controls="collapseEight">
                        8.What happens if my request to extend my return to the base location results in a higher airfare?
                    </button>
                </h2>
                <div id="collapseEight" class="accordion-collapse collapse" aria-labelledby="headingEight" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        In case your return day ticket air fare is high. The guest will need to make their individual arrangement for returning to their respective base locations. Shell will be unable to support ticket date extension with higher costs.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingNine">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNine" aria-expanded="false" aria-controls="collapseNine">
                        9. How will I travel back to the airport on my day of departure?
                    </button>
                </h2>
                <div id="collapseNine" class="accordion-collapse collapse" aria-labelledby="headingNine" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Shell team will provide a HSSE compliant coach for drop to New Delhi IGI airport post the conclusion of Unleash the Power in New Delhi – a New Era of Shell Helix brand event. In case of an extended stay the guest must arrange for their own airport transfers.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTen" aria-expanded="false" aria-controls="collapseTen">
                        10. What are the dress codes for Unleash the Power in New Delhi – a New Era of Shell Helix?
                    </button>
                </h2>
                <div id="collapseTen" class="accordion-collapse collapse" aria-labelledby="headingTen" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Dress code for day 1 – Dress to impress in your personal modern/traditional style <br><br>
                        • Men: Formal black-tie attire (preferably black suit) <br><br>
                        • Women: Formal modern/traditional attire (preferably black) <br><br>
                        Dress code for day 2: <br><br>
                        Helix red T-shirt provided in the registration kit, along with red caps.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingEleven">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEleven" aria-expanded="false" aria-controls="collapseEleven">
                        11. Will I receive a name badge or lanyard?
                    </button>
                </h2>
                <div id="collapseEleven" class="accordion-collapse collapse" aria-labelledby="headingEleven" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes, you will receive a name badge and lanyard at check-in. Please always wear it during the event for access and identification.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwelve">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwelve" aria-expanded="false" aria-controls="collapseTwelve">
                        12. Do I need to have travel or medical insurance?
                    </button>
                </h2>
                <div id="collapseTwelve" class="accordion-collapse collapse" aria-labelledby="headingTwelve" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Yes, it is your responsibility to ensure you have valid medical and travel insurance to cover the duration of your trip, including any extended stay.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThirteen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThirteen" aria-expanded="false" aria-controls="collapseThirteen">
                        13. Are there any travel guidelines I should be aware of?
                    </button>
                </h2>
                <div id="collapseThirteen" class="accordion-collapse collapse" aria-labelledby="headingThirteen" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Arrive at your departure airport at least 2 hours prior to your flight. Keep your travel documents, medications, and electronic devices in your carry-on luggage. Make sure all devices are fully charged and avoid packing valuables in checked bags.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFourteen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFourteen" aria-expanded="false" aria-controls="collapseFourteen">
                        14. Can I bring a family member or child along?
                    </button>
                </h2>
                <div id="collapseFourteen" class="accordion-collapse collapse" aria-labelledby="headingFourteen" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Participation is strictly by invitation. Only guests officially registered for the event will be accommodated. No additional guests or children are permitted unless specified in your invite.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFifteen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFifteen" aria-expanded="false" aria-controls="collapseFifteen">
                        15. What should I know about my luggage and valuables?
                    </button>
                </h2>
                <div id="collapseFifteen" class="accordion-collapse collapse" aria-labelledby="headingFifteen" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Do not pack valuables or essential documents in your checked luggage. Use the in-room safe for storing personal items. If your luggage is delayed or lost, notify the airline staff and inform the Shell travel team upon arrival.
                    </div>
                </div>
            </div>

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSixteen">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSixteen" aria-expanded="false" aria-controls="collapseSixteen">
                        16. Who should I contact for travel-related assistance?
                    </button>
                </h2>
                <div id="collapseSixteen" class="accordion-collapse collapse" aria-labelledby="headingSixteen" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        Your travel details must be submitted via the registration website. For any specific assistance, please reach out to your designated Shell focal point for your market.
                    </div>
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
