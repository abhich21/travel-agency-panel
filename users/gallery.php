<?php
// gallery.php - Displays the event photo gallery

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

// --- 3. DUMMY DATA FOR THE GALLERY ---
// Using picsum.photos for placeholders. You can replace these with your real URLs.
$gallery_images = [
    ['thumb' => 'https://picsum.photos/id/1018/400/300', 'full' => 'https://picsum.photos/id/1018/1200/900', 'alt' => 'Networking Event'],
    ['thumb' => 'https://picsum.photos/id/1025/400/300', 'full' => 'https://picsum.photos/id/1025/1200/900', 'alt' => 'Keynote Speaker'],
    ['thumb' => 'https://picsum.photos/id/103/400/300', 'full' => 'https://picsum.photos/id/103/1200/900', 'alt' => 'Breakout Session'],
    ['thumb' => 'https://picsum.photos/id/1043/400/300', 'full' => 'https://picsum.photos/id/1043/1200/900', 'alt' => 'Audience'],
    ['thumb' => 'https://picsum.photos/id/1047/400/300', 'full' => 'https://picsum.photos/id/1047/1200/900', 'alt' => 'Venue Hall'],
    ['thumb' => 'https://picsum.photos/id/1050/400/300', 'full' => 'https://picsum.photos/id/1050/1200/900', 'alt' => 'Candid Shot'],
];


// --- 4. Start Output Buffering ---
ob_start();
?>

<style>
    .gallery-header {
        padding: 4rem 1.5rem;
        text-align: center;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }
    .gallery-container {
        max-width: 1100px;
        margin: 3rem auto;
        padding: 2rem;
    }
    .gallery-thumbnail {
        border: 1px solid #ddd;
        border-radius: 0.375rem;
        padding: 0.25rem;
        background-color: #fff;
        transition: box-shadow 0.2s;
        cursor: pointer;
    }
    .gallery-thumbnail:hover {
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
    }
    .modal-lg {
        max-width: 90%;
    }
    .modal-body img {
        width: 100%;
        height: auto;
    }
    .btn-download {
        background-color: <?php echo $nav_bg_color; ?>;
        color: <?php echo $nav_text_color; ?>;
        border: none;
    }
    .btn-download:hover {
        color: <?php echo $nav_text_color; ?>;
        opacity: 0.9;
    }
</style>

<main>
    <div class="gallery-header">
        <h1>Event Gallery</h1>
        <p class="lead">See the highlights from <?php echo $org_title; ?>.</p>
    </div>

    <div class="container gallery-container">
        <div class="row g-4">
            
            <?php foreach ($gallery_images as $image): ?>
            <div class="col-lg-4 col-md-6">
                <a href="#" class="gallery-thumbnail-link" 
                   data-bs-toggle="modal" 
                   data-bs-target="#galleryModal" 
                   data-bs-img-src="<?php echo htmlspecialchars($image['full']); ?>"
                   data-bs-img-alt="<?php echo htmlspecialchars($image['alt']); ?>">
                    
                    <img src="<?php echo htmlspecialchars($image['thumb']); ?>" 
                         alt="<?php echo htmlspecialchars($image['alt']); ?>" 
                         class="img-fluid gallery-thumbnail">
                </a>
            </div>
            <?php endforeach; ?>

        </div>
    </div>
</main>

<div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="galleryModalLabel">Gallery View</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
    <div class="modal-body text-center">
        <img src="" id="modalImage" class="img-fluid" alt="">
      </div>
      
      <div class="modal-footer">
        <a href="" id="modalDownloadButton" class="btn btn-download" download="event-image.jpg">
            <i class="fas fa-download me-2"></i>Download
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
      </div>
  </div>
</div> 

<script>
document.addEventListener('DOMContentLoaded', function() {
    var galleryModal = document.getElementById('galleryModal');
    galleryModal.addEventListener('show.bs.modal', function (event) {
        // Button that triggered the modal
        var button = event.relatedTarget;
        
        // Extract info from data-bs-* attributes
        var imageUrl = button.getAttribute('data-bs-img-src');
        var imageAlt = button.getAttribute('data-bs-img-alt');
        
        // Update the modal's content
        var modalImage = galleryModal.querySelector('#modalImage');
        var modalTitle = galleryModal.querySelector('#galleryModalLabel');
        var downloadButton = galleryModal.querySelector('#modalDownloadButton'); // +++ ADD THIS LINE
        
        modalImage.src = imageUrl;
        modalImage.alt = imageAlt;
        modalTitle.textContent = imageAlt;
        downloadButton.href = imageUrl; // +++ ADD THIS LINE
    });
});
</script>


<?php
// --- 5. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 6. Define Page Variables ---
$page_title = 'Event Gallery';

// --- 7. Include the Master Layout ---
include 'layout.php';
?>