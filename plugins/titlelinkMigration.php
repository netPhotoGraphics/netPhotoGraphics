<?php

/*
 * Migrates old titlelinks to append the <em>mod_rewrite_suffix</em> so they are consistent
 * with newly created titlelinks.
 *
 * This migration will not add the <em>mod_rewrite_suffix</em> if it is already present.
 * Otherwise the new titlelink will be <var>old_titlelink</var>%RW_SUFFIX%.
 *
 * <b>NOTE</b>: migration may result in duplicated titlelinks. If that would be the case,
 * the titlelink will not be changed. This occurrence will be logged in the debug log.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/utf8mb4Migration
 * @pluginCategory development
 *
 * @Copyright 2018 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */

$plugin_is_filter = defaultExtension(5 | ADMIN_PLUGIN);
$plugin_description = gettext("Migrate titlelinks to include the <em>mod_rewrite_suffix</em>.");
$plugin_disable = npgFunctions::pluginDisable(array(array(!RW_SUFFIX, gettext('No <em>mod_rewrite_suffix</em> has been set.'))));

npgFilters::register('admin_utilities_buttons', 'titlelinkMigration::buttons');
$option_interface = 'titlelinkMigration';

class titlelinkMigration {

	function getOptionsSupported() {
		$old = getOption('titlelinkMigrate_old');
		purgeOption('titlelinkMigrate_old');
		$options = array(gettext('Migrate old suffix') => array('key' => 'titlelinkMigrate_old', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 1,
						'desc' => sprintf(gettext('Enter the old <code><em>mod_rewrite_suffix</em></code> you wish to migrate to "%1$s"'), RW_SUFFIX))
		);
		if ($old) {
			$count = getOption('titlelinkMigrate_count');
			purgeOption('titlelinkMigrate_count');
			$options['note'] = array(
					'key' => 'titlelinkMigratae_note',
					'type' => OPTION_TYPE_NOTE,
					'order' => 0,
					'desc' => '<div class="messagebox fade-message">' . ngettext(sprintf(gettext('Migrated %1$s titlelink suffix from "%2$s" to "%3$s".'), $count, $old, RW_SUFFIX), sprintf(gettext('Migrated %1$s titlelink suffixes from "%2$s" to "%3$s".'), $count, $old, RW_SUFFIX), $count) . '</div>'
			);
		}

		return $options;
	}

	function handleOptionSave($themename, $themealbum) {
		if (!empty($old = getOption('titlelinkMigrate_old'))) {
			$count = migrateTitleLinks($old, RW_SUFFIX);
			setOption('titlelinkMigrate_count', $count);
		}
	}

	static function buttons($buttons) {

		$buttons[] = array(
				'category' => gettext('Database'),
				'enable' => true,
				'button_text' => gettext('Migrate titlelinks'),
				'formname' => 'titlelink',
				'action' => getAdminLink(USER_PLUGIN_FOLDER . '/titlelinkMigration/migrate.php'),
				'icon' => BADGE_BLUE,
				'title' => gettext('A utility to append the mod_rewrite_suffix to CMS titlelinks.'),
				'alt' => '',
				'hidden' => '',
				'rights' => ADMIN_RIGHTS,
				'XSRFTag' => 'titlelinkMigration'
		);

		return $buttons;
	}

}
