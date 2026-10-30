<?php

/**
 * Data quality report for the administrator.
 *
 * Adds <i>Data quality</i> to the <em>overview</em> tab. The report only READS the database, nothing is changed.
 * It lists photos and albums that probably need attention:
 * <ul>
 * <li>GPS: only one coordinate filled in, coordinates 0,0, out of range, or far away from the other photos of the same album</li>
 * <li>photos without a date or tags, hidden photos</li>
 * <li>albums without a description, mostly untitled albums, albums without any GPS photo, empty albums, hidden albums</li>
 * </ul>
 * Every row has a link to edit the item.
 *
 * @author Martin Kroul with Claude (AI assistant by Anthropic)
 * @license GPL 2 or later, like netPhotoGraphics
 *
 * @package plugins/quality_check
 * @pluginCategory admin
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext('Read-only data quality report: incomplete GPS data, missing titles, dates and tags, empty albums.');

npgFilters::register('admin_tabs', 'quality_check::tabs');

class quality_check {

	static function tabs($tabs) {
		if (npg_loggedin(ADMIN_RIGHTS) && !empty($tabs['overview'])) {
			$tabs['overview']['subtabs'][gettext('Data quality')] = PLUGIN_FOLDER . '/quality_check/report.php?page=overview&tab=quality';
		}
		return $tabs;
	}

}
