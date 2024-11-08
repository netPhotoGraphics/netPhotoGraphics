<?php

/**
 * <i>WYSIWYG</i> editor TinyMCE.
 *
 * You can place your own additional custom configuration files within
 * <var>%USER_PLUGIN_FOLDER%/tinymce_v7/config</var> or <var>%THEMEFOLDER%/theme_name/tinymce_v7/config</var> folder.
 * The naming convention for these files is to prefix the file name with the intended
 * use, e.g.
 * <ol>
 * 	<li>photo-&lt;name&gt;.php</li>
 * 	<li>CMS-&lt;name&gt;.php</li>
 * 	<li>comment-&lt;name&gt;.php</li>
 * </ol>
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/tinymce
 * @pluginCategory admin
 */
$plugin_is_filter = defaultExtension(5 | ADMIN_PLUGIN);
$plugin_description = gettext("TinyMCE WYSIWYG editor");
$option_interface = 'tinymce';

if (!defined('EDITOR_SANITIZE_LEVEL')) {
	define('EDITOR_SANITIZE_LEVEL', 4);
}
if (!defined('TINYMCE')) {
	define('TINYMCE', PLUGIN_SERVERPATH . 'tinymce');
}

npgFilters::register('texteditor_config', 'tinymce::configJS');

/**
 * Plugin option handling class
 *
 */
class tinymce {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('tinymce_photo', 'photo-ribbon.php');
			setOptionDefault('tinymce_CMS', 'CMS-ribbon.php');
			setOptionDefault('tinymce_forms', 'forms-ribbon.php');
			setOptionDefault('tiny_mce_entity_encoding', 'raw');
		}
	}

	function getOptionsSupported() {
		global $_RTL_css;
		if ($_RTL_css) {
			setOption('tiny_mce_rtl_override', 1, false);
		}
		$configs_CMS = self::getConfigFiles('CMS');
		$configs_photo = self::getConfigFiles('photo');
		$configs_forms = self::getConfigFiles('forms');
		$config_comment = self::getConfigFiles('comment');
		$options = array(
				gettext('Text editor configuration - gallery') => array('key' => 'tinymce_photo', 'type' => OPTION_TYPE_SELECTOR,
						'selections' => $configs_photo,
						'null_selection' => gettext('Disabled'),
						'desc' => gettext('Applies to <em>admin</em> editable text other than for Zenpage pages and news articles.')),
				gettext('Text editor configuration - zenpage') => array('key' => 'tinymce_CMS', 'type' => OPTION_TYPE_SELECTOR,
						'selections' => $configs_CMS,
						'null_selection' => gettext('Disabled'),
						'desc' => gettext('Applies to editing on the Zenpage <em>pages</em> and <em>news</em> tabs.')),
				gettext('Text editor configuration - forms') => array('key' => 'tinymce_forms', 'type' => OPTION_TYPE_SELECTOR,
						'selections' => $configs_forms,
						'null_selection' => gettext('Disabled'),
						'desc' => gettext('Applies to editing on <em>forms option</em> tab.')),
				gettext('Text editor configuration (Theme comments)') => array('key' => 'tinymce_comments', 'type' => OPTION_TYPE_SELECTOR,
						'selections' => $config_comment,
						'null_selection' => gettext('Disabled'),
						'desc' => gettext('Configuration file for TinyMCE when used for comments. Set to <code>Disabled</code> to disable visual editing.')),
				gettext('Text editor configuration (Admin comments)') => array('key' => 'tinymce_admin_comments', 'type' => OPTION_TYPE_SELECTOR,
						'selections' => $config_comment,
						'null_selection' => gettext('Disabled'),
						'desc' => gettext('Configuration file for TinyMCE when used for the <em>edit comments</em> tab.')),
				gettext('Entity encoding') => array('key' => 'tiny_mce_entity_encoding', 'type' => OPTION_TYPE_SELECTOR,
						'selections' => array(gettext('named') => 'named', gettext('numeric') => 'numeric', gettext('raw') => 'raw'),
						'desc' => gettext('Select the TinyMCE <em>entity_encoding</em> strategy.')),
				gettext('RTL text direction') => array('key' => 'tiny_mce_rtl_override', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext('This option should be checked if your language writing direction is right-to-left'))
		);

		return $options;
	}

	function handleOption($option, $currentValue) {

	}

	static function configJS($mode) {
		global $_editorconfig, $MCEskin, $MCEdirection, $MCEcss, $MCEspecial, $MCEexternal, $MCEimage_advtab, $MCEtoolbars, $MCElocale;
		if (empty($_editorconfig)) { // only if we get here first!
			$MCEskin = $MCEdirection = $MCEcss = $MCEimage_advtab = $MCEtoolbars = $MCEexternal = NULL;
			$_editorconfig = getOption('tinymce_' . $mode);
			if (!empty($_editorconfig)) {
				$_editorconfigfile = getPlugin(basename(TINYMCE) . '/config/' . $_editorconfig, true);
				if (empty($_editorconfigfile)) {
					debuglog(sprintf(gettext('Could not find the tinymce %1$s config file <em>%2$s</em>'), $mode, $_editorconfig));
				} else {
					require_once($_editorconfigfile);
				}
			}
		}
		return $mode;
	}

	static function getConfigFiles($mode) {
		// get only those that work!
		$files = getPluginFiles($mode . '-*.php', basename(TINYMCE) . '/config/');
		$array = array();
		foreach ($files as $file) {
			$filename = strrchr($file, '/');
			$filename = substr($filename, 1);
			$option = preg_replace('/^' . $mode . '-/', '', $filename);
			$option = ucfirst(preg_replace('/.php$/', '', $option));
			$array[$option] = $filename;
		}
		return $array;
	}

}

?>