<?php
if (extensionEnabled('register_user')) {
    include('includes/header.php');
    ?>
    <header class="page-header"><h1 class="page-title"><?php echo gettext('User Registration'); ?></h1></header>
    <div class="form-card"><?php printRegistrationForm(); ?></div>
    <?php
    include('includes/footer.php');
} else {
    include(SERVERPATH . '/' . CORE_FOLDER . '/404.php');
}
