<?php
/**
 * Daily Summary theme page content template
 *
 * This script is intended as an example of what you might want in your theme
 * for a daily summary. Please modify and enhance as you see fit.
 *
 * @author Marcus Wong (wongm) with updates by Stephen Billard
 * @package plugins/daily-summary
 */
$d = getOption('DailySummaryDays');
if ($_current_DailySummary->getTotalItems()) {
	$count = $_current_DailySummary->getTotalImages();
	?>
	<p>
		<?php
		if ($count > 1) {
			printf(ngettext('There have been %2$d images uploaded in the last day.', 'There have been %2$d images uploaded in the last %1$s days.', $d), $d, $count);
		} else {
			printf(ngettext('There has been one image uploaded in the last day.', 'There has been one image uploaded in the last %1$s days.', $d), $d);
		}
		?>
	</p>
	<br />
	<?php
	while (next_DailySummaryItem()) {
		?>
		<b><a href="<?php echo getDailySummaryUrl(); ?>"><?php echo date("F j", strtotime(getDailySummaryDate())); ?></a></b>
		<p><img border="0" src="<?php echo getCustomDailySummaryThumb(getOption('thumb_size')); ?>" alt="<?php echo getDailySummaryTitle() ?>" /></p>
		<p><?php printf(gettext('Title: %s'), getDailySummaryTitle()); ?></p>
		<p><?php printf(gettext('Description: %s'), getDailySummaryDesc()); ?></p>
		<p><?php printf(gettext('Albums: %s'), getDailySummaryAlbumNameText()); ?></p>
		<p><?php printf(gettext('Uploaded date: %s'), date("F j", strtotime(getDailySummaryModifiedDate()))); ?></p>
		<p><?php printf(gettext('Image count: %s'), getDailySummaryNumImages()); ?></p>
		<p><?php
			echo gettext('Link: ');
			printDailySummaryUrl("See all photos", "Date: " . date("F j", strtotime(getDailySummaryDate())), "extra class", getDailySummaryDate());
			?></p>

		<p><?php echo gettext('Ordered album list with album links:'); ?></p>
		<?php
		printDailySummaryAlbumNameList(true, "ol");
		echo "<br />";
	}
	printDailySummaryPageListWithNav(gettext('next »'), gettext('« prev'), true, 'pagelist', true);
} else {
	printf(ngettext('There has been no activity within the last day.', 'There has been no activity within the last %d days.', $d), $d);
}
