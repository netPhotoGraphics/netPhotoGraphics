<?php

/**
 *
 * Generates a list of albums containg the summary of all photos taken that day
 *
 * <b>Installation</b>
 * <ol>
 * 	<li>Create a summary script for your theme (default name: <var>summary.php</var>. A good starting point	would be to modify a simple page such as the archive.php script.</li>
 * 	<li>For the page content part of the your summary script include the contents of the <code>daily-summary_content.php</code><sup>†</sup></li>
 * 	<li>Set the plugin option <em>Theme script page</em> to the script you created.</li>
 * 	<li>Enable the <var>daily-summary</var> plugin.</li>
 * 	<li>Navigate to <var>%FULLWEBPATH%/page/daily-summary</var><sup>‡</sup> and you will now see a page summarising the recently uploaded images from your gallery.</li>
 * 	<li>Use <var>printDailySummaryLink()</var> to put a link to the summary on theme pages.</li>
 * </ol>
 *
 * <sup>†</sup>You will probably want to edit your theme <var>summary.php</var> script and/or your theme's <var>css</var> to
 * 		get the content, look, and feel you want for your theme. For distributed themes' summary scripts include the
 * 		<code>daily-summary_content.php</code> script.  You can customize them by placing your modified copy in the
 * 		<var>%USER_PLUGIN_FOLDER%/daily-cummary</var> folder.
 *
 *
 * <sup>‡</sup>Use <var>%FULLWEBPATH%/p=daily-summary</var> if mod_rewrite is not activated.
 *
 * @author Marcus Wong (wongm) with updates by Stephen Billard
 * @package plugins/daily-summary
 * @pluginCategory theme

 */
$plugin_is_filter = 5 | THEME_PLUGIN;
$plugin_description = gettext("Generates a list of albums containg the summary of all photos taken that day.");
$plugin_author = "Marcus Wong (wongm) and Stephen Billard";

$option_interface = 'DailySummaryOptions';

if (isset($_gallery_page) && $_gallery_page == getOption('DailySummaryScript')) {
	require_once(dirname(__FILE__) . '/daily-summary/class-dailysummary.php');
	require_once(dirname(__FILE__) . '/daily-summary/class-dailysummaryitem.php');
	require_once(dirname(__FILE__) . '/daily-summary/dailysummary-template-functions.php');
	$_current_DailySummary = new DailySummary();
	npgFilters::register('checkPageValidity', 'DailySummary::pageCount');
}

class DailySummaryOptions {

	function __construct() {
		setOptionDefault('DailySummaryDays', 10);
		setOptionDefault('DailySummaryItemsPage', 5);
		setOptionDefault('DailySummaryScript', 'summary.php');
	}

	function getOptionsSupported() {
		global $_gallery;
		$curdir = getcwd();
		$root = SERVERPATH . '/' . THEMEFOLDER . '/' . $_gallery->getCurrentTheme() . '/';
		chdir($root);
		$filelist = safe_glob('*.php');
		$list = array();
		foreach ($filelist as $file) {
			$file = filesystemToInternal($file);
			$list[$file] = stripSuffix($file);
		}
		chdir($curdir);
		$list = array_diff($list, standardScripts());
		$list = array_flip($list);

		$current = array(getOption('DailySummaryScript'));


		return array(gettext('Days of history') => array(
						'order' => 0,
						'key' => 'DailySummaryDays',
						'type' => OPTION_TYPE_NUMBER,
						'desc' => gettext("How many days back to show.")
				),
				gettext('Items per page') => array(
						'order' => 1,
						'key' => 'DailySummaryItemsPage',
						'type' => OPTION_TYPE_NUMBER,
						'desc' => gettext('Controls the number of items that will shown on each daily-summary page.')
				),
				gettext('Theme script page') => array(
						'order' => 2,
						'key' => 'DailySummaryScript',
						'type' => OPTION_TYPE_ORDERED_SELECTOR,
						'null_selection' => stripSuffix(getOption('DailySummaryScript')),
						'selections' => $list,
						'desc' => gettext('The theme script for showing the summary.')
				)
		);
	}

}

function printDailySummaryLink($linkText = NULL, $q = '', $prev = '', $next = '', $class = NULL) {
	if (is_null($linkText)) {
		$linkText = gettext('Daily Summary');
	}
	printCustomPageURL($linkText, stripSuffix(getOption('DailySummaryScript')), $q, $prev, $next, $class);
}

?>