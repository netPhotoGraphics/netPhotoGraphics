<?php

/**
 * Back-end <i>WYSIWYG</i> editor TinyMCE.
 *
 * You can place your own additional custom configuration files within
 * <var>%USER_PLUGIN_FOLDER%/tinymce/config</var> or <var>%THEMEFOLDER%/theme_name/tinymce/config</var> folder.
 * The naming convention for these files is use prefix the file name with the intended
 * use, e.g.
 * <ol>
 * 	<li>photo-&lt;name&gt;.php</li>
 * 	<li>CMS-&lt;name&gt;.php</li>
 * 	<li>comment-&lt;name&gt;.php</li>
 * </ol>
 *
 * @author Stephen Billard (sbillard), Malte Müller (acrylian)
 *
 * @package plugins/tinymce
 * @pluginCategory admin
 */
$plugin_is_filter = defaultExtension(5 | ADMIN_PLUGIN);
$plugin_description = gettext("TinyMCE WYSIWYG editor");
$option_interface = 'tinymce';

if (!defined('EDITOR_SANITIZE_LEVEL'))
	define('EDITOR_SANITIZE_LEVEL', 4);
if (!defined('TINYMCE')) {
	define('TINYMCE', PLUGIN_SERVERPATH . 'tinymce_v7');
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
		$options = array(
				gettext('Text editor configuration - gallery') => array('key' => 'tinymce_photo', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 0,
						'selections' => $configs_photo,
						'null_selection' => gettext('Disabled'),
						'desc' => gettext('Applies to <em>admin</em> editable text other than for Zenpage pages and news articles.')),
				gettext('Text editor configuration - zenpage') => array('key' => 'tinymce_CMS', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 1,
						'selections' => $configs_CMS,
						'null_selection' => gettext('Disabled'),
						'desc' => gettext('Applies to editing on the Zenpage <em>pages</em> and <em>news</em> tabs.')),
				gettext('Text editor configuration - forms') => array('key' => 'tinymce_forms', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 1,
						'selections' => $configs_forms,
						'null_selection' => gettext('Disabled'),
						'desc' => gettext('Applies to editing on <em>forms option</em> tab.')),
				gettext('Entity encoding') => array('key' => 'tiny_mce_entity_encoding', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 2,
						'selections' => array(gettext('named') => 'named', gettext('numeric') => 'numeric', gettext('raw') => 'raw'),
						'desc' => gettext('Select the TinyMCE <em>entity_encoding</em> strategy.')),
				gettext('RTL text direction') => array('key' => 'tiny_mce_rtl_override', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 3,
						'desc' => gettext('This option should be checked if your language writing direction is right-to-left')));
		return $options;
	}

	function handleOption($option, $currentValue) {

	}

	static function configJS($mode) {
		global $_editorconfig, $MCEskin, $MCEdirection, $MCEcss, $MCEspecial, $MCEexternal, $MCEimage_advtab, $MCEtoolbars, $MCElocale;
		$MCEskin = $MCEdirection = $MCEcss = $MCEimage_advtab = $MCEtoolbars = $MCEexternal = NULL;
		$MCEspecial['browser_spellcheck'] = "true";
		if (OFFSET_PATH && npg_loggedin(UPLOAD_RIGHTS)) {
			$MCEspecial['images_upload_url'] = '"' . WEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/' . TINYMCE . '/postAcceptor.php?XSRFToken=' . getXSRFToken('postAcceptor') . '"';
		}
		if (empty($_editorconfig)) { // only if we get here first!
			$MCElocale = 'en';
			$loc = str_replace('_', '-', getOption('locale'));
			if ($loc) {
				if (file_exists(TINYMCE . '/langs/' . $loc . '.js')) {
					$MCElocale = $loc;
				} else {
					$loc = substr($loc, 0, 2);
					if (file_exists(TINYMCE . '/langs/' . $loc . '.js')) {
						$MCElocale = $loc;
					}
				}
			}

			$_editorconfig = getOption('tinymce_' . $mode);
			if (!empty($_editorconfig)) {
				$_editorconfig = getPlugin(basename(TINYMCE) . '/config/' . $_editorconfig, true);
				if (!empty($_editorconfig)) {
					require_once($_editorconfig);
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