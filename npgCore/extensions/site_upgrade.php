<?php
/**
 * Provides a means to close access to your site for upgrading and testing.
 *
 * A button is placed in the <i>Utility functions</i> section of the Admin overview page to allow you
 * to manage the state of your site. This button changes function depending on the state of the site. You may
 * <i>close</i> a site, move a closed site to <i>test mode</i>, and then <i>open</i> the site.
 *
 * <i>Closing</i> the site will cause links to the site <i>front end</i> to be redirected to a script in
 * the folder <var>plugins/site_upgrade</var>. Access to the admin pages remains available.
 * You should close the site while
 * you are uploading a new release so that users will not catch the site in an unstable state.
 *
 * After you have uploaded the new release and run Setup you place the site in <i>test mode</i>. In this mode
 * only logged in <i>Administrators</i> can access the <i>front end</i>. You can then, as the administrator, view the
 * site to be sure that all your changes are as you wish them to be.
 *
 * Once your testing is completed you <i>open</i> your site to all visitors.
 *
 * Change the files in <var>plugins/site_upgrade</var> to meet your needs. (<b>Note</b> these files will
 * be copied to that folder during setup the first time you do an install. Setup will not overrite any existing
 * versions of these files, so if a change is made to the distributed versions of the files you will have to update
 * your copies either by removing them before running setup or by manually applying the distributed file changes to your
 * files.)
 *
 *
 * The plugin works best if <var>mod_rewrite</var> is active and the <var>.htaccess</var> file exists. If this is not the case
 * the plugin will still work in most cases. However if the release you are upgrading to has significant changes involving
 * plugin loading of the front-end site there may be PHP failures due if the site is accessed while the files
 * being uploaded are in a mixed release state.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/site_upgrade
 * @pluginCategory admin
 */
$plugin_is_filter = defaultExtension(900 | FEATURE_PLUGIN);
$plugin_description = gettext('Utility to divert access to the gallery to a screen saying the site is upgrading.');
$plugin_notice = (MOD_REWRITE) ? false : gettext('<em>mod_rewrite</em> is not enabled. This plugin may not work without rewrite redirection if the upgrade is significantly different than the running release.');

define('SITE_UPGRADE_FILELIST', array(
		'closed.htm' => '+', // copy and update define
		'closed.php' => '*' // copy and update
				)
);

class site_upgrade {

	static function notice() {
		?>
		<div style="width: 100%; position: fixed; top: 0px; left: 0px; z-index: 1000;" >
			<p style="text-align: center;">
				<strong style="background-color: #FFEFB7; color:black; padding: 5px;">
					<?php echo gettext('Site is available for testing only.'); ?>
				</strong>
			</p>
		</div>
		<?php
	}

	static function note($where) {
		global $_conf_vars;
		switch (isset($_conf_vars['site_upgrade_state']) ? $_conf_vars['site_upgrade_state'] : NULL) {
			case 'closed':
				if ($where == 'Overview') {
					?>
					<form class="dirtylistening" name="site_upgrade_form" id="site_upgrade_form">
					</form>
					<script type="text/javascript">
						window.addEventListener('load', function () {
							$('#site_upgrade_form').dirtyForms('setDirty');
							$.DirtyForms.message = '<?php echo gettext('The site is closed!'); ?>';
						}, false);
					</script>
					<?php
				}
				?>
				<p class="errorbox">
					<strong><?php echo gettext('The site is closed!'); ?></strong>
				</p>
				<?php
				break;
			case 'closed_for_test';
				?>
				<p class="notebox">
					<strong><?php echo gettext('Site is available for testing only.'); ?></strong>
				</p>
				<?php
				break;
		}
	}

	static function status() {
		global $_conf_vars;
		switch (isset($_conf_vars['site_upgrade_state']) ? $_conf_vars['site_upgrade_state'] : NULL) {
			case 'closed':
				?>
				<li>
					<?php echo gettext('Site status:'); ?> <span style="color:RED"><strong><?php echo gettext('The site is closed!'); ?></strong></span>
				</li>
				<?php
				break;
			case 'closed_for_test';
				?>
				<li>
					<?php echo gettext('Site status:'); ?> <span style="color:RED"><strong><?php echo gettext('The site is in test mode!'); ?></strong></span>
				</li>
				<?php
				break;
			default:
				?>
				<li>
					<?php echo gettext('Site status:'); ?> <strong><?php echo gettext('The site is opened'); ?></strong>
				</li>
				<?php
				break;
		}
	}

	static function button($buttons) {
		global $_conf_vars;
		$state = isset($_conf_vars['site_upgrade_state']) ? $_conf_vars['site_upgrade_state'] : NULL;

		$update = false;
		$hash = getSerializedArray(getOption('site_upgrade_hash'));

		foreach (npgFilters::apply('site_upgrade_xml', SITE_UPGRADE_FILELIST) as $name => $source) {
			if (file_exists(USER_PLUGIN_SERVERPATH . 'site_upgrade/' . $name)) {
				if (!isset($hash[$name]) || $hash[$name] != md5(file_get_contents(USER_PLUGIN_SERVERPATH . 'site_upgrade/' . $name))) {
					$update = true;
					break;
				}
			} else {
				$update = true;
				break;
			}
		}

		if ($update) {
			$buttons[] = array(
					'XSRFTag' => 'site_upgrade_refresh',
					'category' => gettext('Admin'),
					'enable' => true,
					'button_text' => gettext('Restore site_upgrade files'),
					'formname' => 'refreshHTML',
					'action' => getAdminLink('admin.php') . '?refreshHTML=1',
					'icon' => CLOCKWISE_OPEN_CIRCLE_ARROW_GREEN,
					'title' => gettext('Restores the files in the "plugins/site_upgrade" folder to their default state. Note: this will overwrite any custom edits you may have made.'),
					'alt' => '',
					'rights' => ADMIN_RIGHTS
			);
		}
		switch ($state) {
			case 'closed':
				$buttons[] = array(
						'XSRFTag' => 'site_upgrade',
						'category' => gettext('Admin'),
						'enable' => 3,
						'button_text' => gettext('Site » test mode'),
						'formname' => 'site_upgrade',
						'action' => getAdminLink(PLUGIN_FOLDER . '/site_upgrade/site_upgrade.php'),
						'icon' => LOCK_OPEN,
						'title' => gettext('Make the site available for viewing administrators only.'),
						'onclick' => "$('#site_upgrade_form').dirtyForms('setClean');this.form.submit();",
						'alt' => '',
						'hidden' => '<input type="hidden" name="siteState" value="closed_for_test" />',
						'rights' => ADMIN_RIGHTS
				);
				break;
			case 'closed_for_test':
				$buttons[] = array(
						'XSRFTag' => 'site_upgrade',
						'category' => gettext('Admin'),
						'enable' => 2,
						'button_text' => gettext('Site » open'),
						'formname' => 'site_upgrade',
						'action' => getAdminLink(PLUGIN_FOLDER . '/site_upgrade/site_upgrade.php'),
						'icon' => LOCK_OPEN,
						'title' => gettext('Make site available for viewing.'),
						'alt' => '',
						'hidden' => '<input type="hidden" name="siteState" value="open" />',
						'rights' => ADMIN_RIGHTS
				);
				list($diff, $needs) = checkSignature(0);
				if (npgFunctions::hasPrimaryScripts() && empty($needs)) {
					?>
					<script type="text/javascript">
						window.addEventListener('load', function () {
							$('#site_upgrade').submit(function () {
								return confirm('<?php echo gettext('Your setup scripts are not protected!'); ?>');
							})
						}, false);
					</script>
					<?php
				}
				break;
			default:
				$buttons[] = array(
						'XSRFTag' => 'site_upgrade',
						'category' => gettext('Admin'),
						'enable' => true,
						'button_text' => gettext('Site » close'),
						'formname' => 'site_upgrade.php',
						'action' => getAdminLink(PLUGIN_FOLDER . '/site_upgrade/site_upgrade.php'),
						'icon' => LOCK,
						'title' => gettext('Make site unavailable for viewing by redirecting to the "closed.html" page.'),
						'alt' => '',
						'hidden' => '<input type="hidden" name="siteState" value="closed" />',
						'rights' => ADMIN_RIGHTS
				);
				break;
		}

		return $buttons;
	}

	static function updateXML($files) {
		global $_gallery;
		mkdir_recursive(USER_PLUGIN_SERVERPATH . 'site_upgrade/', FOLDER_MOD);
		setOptionDefault('site_upgrade_hash', NULL);
		$hash = array();
		foreach ($files as $name => $source) {
			switch ($source) {
				case '*':
					$data = file_get_contents(PLUGIN_SERVERPATH . 'site_upgrade/' . $name);
					$defines = array(
							'SITEINDEX' => FULLWEBPATH . "/index.php",
							'CORE_FOLDER' => CORE_FOLDER, 'CORE_PATH' => CORE_PATH,
							'PLUGIN_PATH' => PLUGIN_PATH, 'PLUGIN_FOLDER' => PLUGIN_FOLDER,
							'USER_PLUGIN_PATH' => USER_PLUGIN_PATH, 'USER_PLUGIN_FOLDER' => USER_PLUGIN_FOLDER,
							'DATA_FOLDER' => DATA_FOLDER,
							'CONFIGFILE' => CONFIGFILE,
							'RW_SUFFIX' => preg_quote(getOption('mod_rewrite_suffix'))
					);
					$data = strtr($data, $defines);
					break;
				case '+':
					$data = file_get_contents(PLUGIN_SERVERPATH . 'site_upgrade/' . $name);
					$data = sprintf($data, sprintf(gettext('%s upgrade'), $_gallery->getTitle()), FULLWEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/site_upgrade/closed.png', sprintf(gettext('<strong><em>%s</em></strong> is undergoing an upgrade'), $_gallery->getTitle()), '<a href="' . FULLWEBPATH . '/index.php">' . gettext('Please return later') . '</a>', FULLWEBPATH . '/index.php');
					break;
				default:
					// Feed plugin
					$plugin = getPlugin(substr($name, 0, strpos($name, '-')) . '.php');
					require_once( $plugin);
					$items = array(
							array(
									'title' => sprintf(gettext('%s suspended'), $source),
									'link' => FULLWEBPATH . '/index.php',
									'enclosure' => '',
									'category' => 'suspend',
									'media_content' => '',
									'media_thumbnail' => '',
									'pubdate' => date("r", time()),
									'desc' => sprintf(gettext('The %s feed is currently not available.'), $source)
							)
					);
					$obj = new $source(array(strtolower($source) => 'null'));
					ob_start();
					$obj->printFeed($items);
					$data = ob_get_clean();
					break;
			}
			file_put_contents(USER_PLUGIN_SERVERPATH . 'site_upgrade/' . $name, $data);
			$hash[$name] = md5(file_get_contents(USER_PLUGIN_SERVERPATH . 'site_upgrade/' . $name));
		}
		setOption('site_upgrade_hash', serialize($hash));
	}

}

switch (OFFSET_PATH) {
	case 0:
		$state = isset($_conf_vars['site_upgrade_state']) ? $_conf_vars['site_upgrade_state'] : NULL;
		if ((!npg_loggedin(ADMIN_RIGHTS | DEBUG_RIGHTS) && $state == 'closed_for_test') || $state == 'closed') {
			header('location: ' . getAdminLink(USER_PLUGIN_FOLDER . '/site_upgrade/closed.php'));
			exit();
		} else if ($state == 'closed_for_test') {
			npgFilters::register('theme_body_open', 'site_upgrade::notice');
		}
		break;
	default:
		npgFilters::register('admin_utilities_buttons', 'site_upgrade::button');
		npgFilters::register('installation_information', 'site_upgrade::status');
		npgFilters::register('admin_note', 'site_upgrade::note');

		if (isset($_GET['refreshHTML'])) {
			XSRFdefender('site_upgrade_refresh');
			site_upgrade::updateXML(npgFilters::apply('site_upgrade_xml', SITE_UPGRADE_FILELIST));
			$_GET['report'] = gettext('site_upgrade files Restored to original.');
		}
		break;
	case 2:
		$_SITE_UPGRADE_FILELIST = SITE_UPGRADE_FILELIST;
		if (file_exists(USER_PLUGIN_SERVERPATH . 'site_upgrade/closed.htm')) {
			unset($_SITE_UPGRADE_FILELIST['closed.htm']);
		}
		site_upgrade::updateXML($_SITE_UPGRADE_FILELIST);
		unset($_SITE_UPGRADE_FILELIST);
		break;
}
