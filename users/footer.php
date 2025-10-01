<?php
// footer.php
// This component uses theme variables ($nav_bg_color, $nav_text_color, $org_title) 
// that are defined in layout.php.
?>

<footer class="text-center p-3 mt-auto" style="background-color: <?php echo $nav_bg_color; ?>; color: <?php echo $nav_text_color; ?>;">
    &copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($org_title); ?>. All Rights Reserved.
</footer>