<?php

/**
 * Hide the output of user rights and other info if a user does <b>NOT</b> have <var>ADMIN_RIGHTS</var>.
 *
 * To change what is hidden, comment lines you do want to display.
 *
 * @author Stephen Billard (sbillard), Fred Sondaar (fretzl)
 *
 * @package plugins/showNoUserRights
 * @pluginCategory example
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext("Hide the output of user rights and other info if user does NOT have ADMIN_RIGHTS.");

npgFilters::register('admin_head', 'showNoUserRights::customDisplayRights');
npgFilters::register('plugin_tabs', 'showNoUserRights::tab');

class showNoUserRights {

	static function customDisplayRights() {
		global $_admin_tab, $_admin_subtab;
		if (!npg_loggedin(ADMIN_RIGHTS)) {
			if ($_admin_tab == 'admin' && ($_admin_subtab == 'users') || is_null($_admin_subtab)) {
				?>
				<script type="text/javascript">
					// <!-- <![CDATA[
					$(document).ready(function () {
						$('select[name="showgroup"]').parent("th").remove(); 	// the "Show" dropdownn menu
						$('.box-rights').remove(); 								// Rights. (the part with all the checkboxes).
						$('.box-albums-unpadded').remove(); 			// Albums, Pages, and Categories.
						$('td .notebox').parent().parent().remove();	//	notes and warnings he can't do anything about
						$('.notebox').remove();
					});
					// ]]> -->
				</script>

				<?php

			}
		}
	}

	static function tab($xlate) {
		$xlate['demo'] = gettext('demo');
		return $xlate;
	}

}
?>