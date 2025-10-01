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
    /* --- PASTE THIS NEW CSS --- */

/* 1. Make the thumbnail container a positioning context */
.thumbnail-wrapper {
    position: relative;
    display: block; /* Ensures it behaves like a block element */
    border: 1px solid #ddd;
    border-radius: 0.375rem;
    padding: 0.25rem;
    background-color: #fff;
    transition: box-shadow 0.2s;
    overflow: hidden; /* Keeps the download button's rounded corners neat */
}

/* 2. Style the image inside the wrapper */
.gallery-thumbnail {
    display: block;
    width: 100%;
    transition: transform 0.3s ease;
}

/* 3. Style the download button */
.btn-thumb-download {
    position: absolute;
    bottom: 10px;
    right: 10px;
    z-index: 2; /* Ensures it sits on top of the image */
    
    /* Styling for the button itself */
    background-color: rgba(26, 26, 26, 0.7); /* Semi-transparent dark background */
    color: white;
    border: none;
    border-radius: 50%; /* Makes it a circle */
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    
    /* Hiding and transition for the hover effect */
    opacity: 0;
    transform: translateY(10px);
    transition: opacity 0.3s ease, transform 0.3s ease;
}

/* 4. Show the download button and zoom image on hover */
.thumbnail-wrapper:hover .btn-thumb-download {
    opacity: 1;
    transform: translateY(0);
}

.thumbnail-wrapper:hover .gallery-thumbnail {
    transform: scale(1.05); /* Slight zoom effect on the image */
}

/* --- END OF NEW CSS --- */
</style>

<main>
    <div class="hero-section">

       <div class="hero-background-video">
        <div class="hero-overlay"></div>
        <video autoplay muted loop playsinline>
            <source src="../assets/gallery1.mp4">
        </video>
     </div>

     <div class="hero-content">
     <h1>Gallery</h1>

    </div>

</div>

    <div class="container gallery-container">
        <div class="row g-4">
            
            <!-- REPLACE WITH THIS UPDATED HTML BLOCK -->
<?php foreach ($gallery_images as $index => $image): ?>
<div class="col-lg-4 col-md-6">
    <div class="thumbnail-wrapper">
        <!-- Link to open the modal -->
        <a href="#" 
           data-bs-toggle="modal" 
           data-bs-target="#galleryModal" 
           data-bs-img-src="<?php echo htmlspecialchars($image['full']); ?>"
           data-bs-img-alt="<?php echo htmlspecialchars($image['alt']); ?>">

            <img src="<?php echo htmlspecialchars($image['thumb']); ?>" 
                 alt="<?php echo htmlspecialchars($image['alt']); ?>" 
                 class="img-fluid gallery-thumbnail">
        </a>

        <?php
        // We build the proxy URL here
        $download_link = 'download.php?url=' . urlencode($image['full']) . '&name=event-photo-' . ($index + 1) . '.jpg';
        ?>
<a href="<?php echo $download_link; ?>" 
   class="btn-thumb-download" 
   title="Download image">
    <i class="fas fa-download"></i>
</a>
    </div>
</div>
<?php endforeach; ?>
<!-- END OF UPDATED BLOCK -->

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
        var imageName = 'event-photo-' + (button.closest('.col-lg-4').ariaRowIndex || 'modal') + '.jpg'; // Create a filename
downloadButton.href = `download.php?url=${encodeURIComponent(imageUrl)}&name=${imageName}`;// +++ ADD THIS LINE
    });
});

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
// --- 5. Store the captured HTML ---
$page_content_html = ob_get_clean();

// --- 6. Define Page Variables ---
$page_title = 'Event Gallery';

// --- 7. Include the Master Layout ---
include 'layout.php';
?>