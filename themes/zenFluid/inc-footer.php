<?php
// force UTF-8 Ø
		if (getOption('zenfluid_showfooter')) {
			?>
			<div class="footer border colour" <?php echo $titleStyle;?>>
				<?php echo gettext('zenFluid theme designed by '); ?> Jim Brown&nbsp;|&nbsp;
				<?php print_SW_Link(); echo "\n"; ?>
			</div>
			<?php 
		} 
		?>
	</div>
	<?php include("inc-sidebar.php"); ?>
</div>

<?php
npgFilters::apply('theme_body_close');
?>
