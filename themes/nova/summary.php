<?php include('includes/header.php'); ?>

<header class="page-header"><h1 class="page-title"><?php echo gettext('Daily summary'); ?></h1></header>
<div class="search-form-wrap"><?php nova_printSearchForm(); ?></div>
<?php include getPlugin('/daily-summary/daily-summary_content.php'); ?>

<?php include('includes/footer.php'); ?>
