<?php
include('inc_header.php');
?>
<!-- .container main -->
<!-- .page-header -->
<!-- .header -->
<h3><?php echo gettext('Daily summary'); ?></h3>
</div><!-- .header -->
</div><!-- /.page-header -->

<div class="page-header row">
	<div class="col-xs-offset-1 col-xs-10 col-sm-offset-2 col-sm-8 col-md-offset-3 col-md-6">
		<?php printSearchForm(); ?>
	</div>
</div>
<?php
include getPlugin('/daily-summary/daily-summary_content.php');
?>

</div><!-- /.container main -->

<?php include('inc_footer.php'); ?>