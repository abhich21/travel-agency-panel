<?php
// leaderboard.php - Displays the event leaderboard

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

// --- 3. DUMMY DATA FOR THE LEADERBOARD ---
// This is static data. You can replace this with a database query later.
$leaderboard_data = [
    ['rank' => 1, 'name' => 'Priya Sharma', 'points' => 2150],
    ['rank' => 2, 'name' => 'Alex Johnson', 'points' => 1980],
    ['rank' => 3, 'name' => 'Rohan Gupta', 'points' => 1800],
    ['rank' => 4, 'name' => 'Emily Chen', 'points' => 1640],
    ['rank' => 5, 'name' => 'David Lee', 'points' => 1520],
    ['rank' => 6, 'name' => 'Fatima Al-Sayed', 'points' => 1400],
    ['rank' => 7, 'name' => 'Michael Brown', 'points' => 1210],
    ['rank' => 8, 'name' => 'Aarav Patel', 'points' => 1100],
];


// --- 4. Start Output Buffering ---
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
    .leaderboard-container {
        max-width: 800px;
        margin: 3rem auto;
        padding: 2rem;
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
    }
    .table thead th {
        background-color: <?php echo $nav_bg_color; ?>;
        color: <?php echo $nav_text_color; ?>;
        border: none;
    }
    .table tbody tr td {
        vertical-align: middle;
        font-weight: 500;
    }
    .table tbody tr td .fa-trophy {
        font-size: 1.2rem;
    }
    /* Top 3 rank styling */
    .rank-gold { color: #FFD700; }
    .rank-silver { color: #C0C0C0; }
    .rank-bronze { color: #CD7F32; }
</style>

<main>
     <div class="hero-section">

       <div class="hero-background-video">
        <div class="hero-overlay"></div>
        <video autoplay muted loop playsinline>
            <source src="../assets/leaderboard1.mp4.">
        </video>
     </div>

     <div class="hero-content">
        <h1>Leaderboard</h1>
       
    </div>

</div>

    <div class="leaderboard-container">
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th scope="col" style="width: 15%;">Rank</th>
                        <th scope="col">Name</th>
                        <th scope="col" style="width: 20%;">Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaderboard_data as $row): ?>
                        <tr>
                            <td class="fs-5">
                                <?php 
                                $rank_class = '';
                                $icon = htmlspecialchars($row['rank']);
                                if ($row['rank'] == 1) {
                                    $rank_class = 'rank-gold';
                                    $icon = '<i class="fas fa-trophy rank-gold"></i> 1';
                                } elseif ($row['rank'] == 2) {
                                    $rank_class = 'rank-silver';
                                    $icon = '<i class="fas fa-trophy rank-silver"></i> 2';
                                } elseif ($row['rank'] == 3) {
                                    $rank_class = 'rank-bronze';
                                    $icon = '<i class="fas fa-trophy rank-bronze"></i> 3';
                                }
                                echo $icon;
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="fs-5"><?php echo htmlspecialchars($row['points']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
    }</script>
<?php
// --- 5. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 6. Define Page Variables ---
$page_title = 'Leaderboard';

// --- 7. Include the Master Layout ---
include 'layout.php';
?>