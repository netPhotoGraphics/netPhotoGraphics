<?php
if (extensionEnabled('contact_form')) {
    include('includes/header.php');
    ?>
    <header class="page-header"><h1 class="page-title"><?php echo gettext('Contact'); ?></h1></header>
    <div class="form-card"><?php printContactForm(); ?></div>
    <?php
    include('includes/footer.php');
} else {
    include(CORE_SERVERPATH . '404.php');
}
