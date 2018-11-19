<?php include ("inc-header.php"); ?>

<div id="breadcrumbs">
	<h2><a href="<?php echo html_encode(getGalleryIndexURL()); ?>" title="<?php echo gettext('Home'); ?>"><?php echo gettext('Home'); ?></a> &raquo; <a href="<?php echo getCustomPageURL('gallery'); ?>" title="<?php echo gettext('Gallery Index'); ?>"><?php echo gettext('Gallery Index'); ?></a> &raquo; <?php echo gettext("Daily summary"); ?></h2>
</div>
</div> <!-- close #header -->
<div id="content">
	<div id="main"<?php if ($zpmin_switch) echo ' class="switch"'; ?>>
		<div id="gallery-archive" class="archive">
			<?php
			include getPlugin('/daily-summary/daily-summary_content.php');
			?>
		</div>
	</div>
	<div id="sidebar"<?php if ($zpmin_switch) echo ' class="switch"'; ?>>
		<div class="sidebar-divide">
			<?php printGalleryDesc(true); ?>
		</div>
		<?php include ("inc-sidemenu.php"); ?>
		<div id="tag_cloud">
			<h4><?php echo gettext('Popular Tags'); ?></h4>
			<?php printAllTagsAs('cloud', 'tags'); ?>
		</div>
	</div>
</div>

<?php include ("inc-footer.php"); ?>
