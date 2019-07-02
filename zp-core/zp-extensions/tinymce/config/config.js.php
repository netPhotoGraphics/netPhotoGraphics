<?php
/**
 * The configuration parameters for TinyMCE 4.x.
 *
 * base configuration file, included by all TinyMCE configuration files
 *
 * Note:
 *
 * The following variables are presumed to have been set up by the specific configuration
 * file before including this script:
 *
 * <ul>
 * 	<li>$MCEselector: the class(es) upon which tinyMCE will activate</li>
 * 	<li>$MCEplugins: array of plugins to include in the configuration</li>
 * 	<li>$MCEtoolbars: toolbar(s) for the configuration</li>
 * 	<li>$MCEstatusbar: Status to true for a status bar, false for none</li>
 * 	<li>$MCEmenubar: array of menu items for the menu bar, false for none</li>
 * </ul>
 *
 * And the following variables are optional, if set they will be used, otherwise default
 * settings will be selected:
 *
 * <ul>
 * 	<li>$MCEdirection: set to "rtl" for right-to-left text flow</li>
 * 	<li>$MCEspecial: used to insert arbitrary initialization parameters such as styles </li>
 *  <li>$MCEexternal: array of external plugins</li>
 * 	<li>$MCEskin: set to the override the default tinyMCE skin</li>
 * 	<li>$MCEcss: css file to be used by tinyMce</li>
 * 	<li>$MCEimage_advtab: set to <var>false</var> to disable the advanced image tab on the image insert popup (<i>style</i>, <i>borders</i>, etc.)</li>
 * </ul>
 *
 * @author Stephen Billard (sbillard)
 *
 * @Copyright 2014 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */
npgFilters::apply('tinymce_config', NULL);

if ($MCEcss) {
	$MCEcss = getPlugin('tinymce/config/' . $MCEcss, true, true);
} else {
	$MCEcss = getPlugin('tinymce/config/content.css', true, true);
}
global $_RTL_css;
if ($MCEdirection == NULL) {
	if ($_RTL_css) {
		$MCEdirection = 'rtl';
	} else {
		if (getOption('tiny_mce_rtl_override')) {
			$MCEdirection = 'rtl';
		}
	}
}

scriptLoader(TINYMCE . '/tinymce.5.0.4.min.js');
scriptLoader(TINYMCE . '/jquery.tinymce.min.js');
if (OFFSET_PATH && getOption('dirtyform_enable') > 1) {
	scriptLoader(CORE_SERVERPATH . 'js/dirtyforms/jquery.dirtyforms.helpers.tinymce.min.js');
}

if ($MCEplugins && !is_array($MCEplugins)) {
	$MCEplugins = explode(' ', preg_replace('/\s\s+/', ' ', trim($MCEplugins)));
}
if ($pasteObjEnabled = array_search('pasteobj', $MCEplugins)) {
	scriptLoader(TINYMCE . '/pasteobj/plugin.js');
}
?>
<script type="text/javascript">
	// <!-- <![CDATA[
<?php
if ($pasteObjEnabled) {
	?>
		var pasteObjConfig = {	//	pasteObject window
		title: 'netPhotoGraphics:obj',
						url: '<?php echo WEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/tinymce/pasteobj/pasteobj.php'; ?>',
						height: 600,
						width: 800
		};
	<?php
}
?>
	tinymce.init({
	entity_encoding : "<?php echo getOption('tiny_mce_entity_encoding'); ?>",
					selector: "<?php echo $MCEselector; ?>",
					language: "<?php echo $MCElocale; ?>",
					relative_urls: false,
					flash_video_player_url: false,
<?php
if ($MCEimage_advtab == NULL || $MCEimage_advtab) {
	?>
		image_advtab: true,
	<?php
}
if ($MCEdirection) {
	?>
		directionality : '<?php echo $MCEdirection; ?>',
	<?php
}
if ($MCEcss) {
	?>
		content_css: "<?php echo $MCEcss; ?>",
	<?php
}
?>
	plugins: ["<?php echo implode(' ', $MCEplugins); ?>"],
<?php
if ($MCEexternal) {
	?>
		external_plugins: {
	<?php
	foreach ($MCEexternal as $plugin => $url) {
		echo "		  '" . $plugin . "': '" . $url . "'\n";
	}
	?>
		},
	<?php
}
if (in_array('pagebreak', $MCEplugins)) {
	?>
		pagebreak_split_block: true,
	<?php
}
if ($MCEspecial) {
	foreach ($MCEspecial as $element => $value) {
		echo $element . ': ' . $value . ",\n";
	}
}
if ($MCEskin) {
	?>
		skin: "<?php echo $MCEskin; ?>",
	<?php
}
if (empty($MCEtoolbars)) {
	?>
		toolbar: false,
	<?php
} else {
	foreach ($MCEtoolbars as $key => $toolbar) {
		?>
			toolbar<?php if (count($MCEtoolbars) > 1) echo $key; ?>: "<?php echo $toolbar; ?>",
		<?php
	}
}
?>
	statusbar: <?php echo ($MCEstatusbar) ? 'true' : 'false'; ?>,
<?php
if ($MCEmenubar) {
	if (is_array($MCEmenubar)) {
		$menu = $MCEmenubar;
	} else if (is_string($MCEmenubar)) {
		$menu = explode(' ', preg_replace('/\s\s+/', ' ', trim($MCEmenubar)));
	} else {
		$menu = array('file', 'edit', 'insert', 'view', 'format', 'table', 'tools');
	}

	$MCEmenubar = "    menu: {\n";
	foreach ($menu as $item) {
		switch ($item) {
			case 'file':
				$MCEmenubar .= "      file: {title: 'File', items: 'newdocument'},\n";
				break;
			case 'edit':
				$MCEmenubar .= "      edit: {title: 'Edit', items: 'undo redo | cut copy paste pastetext | selectall'},\n";
				break;
			case 'insert':
				$MCEmenubar .= "      insert: {title: 'Insert', items: 'image link media";
				if (in_array('pasteobj', $MCEplugins)) {
					$MCEmenubar .= " pasteobj";
				}
				$MCEmenubar .= " template | charmap hr | pagebreak nonbreaking anchor | insertdatetime'},\n";
				break;
			case 'view':
				$MCEmenubar .= "      view: {title: 'View', items: 'visualaid'},\n";
				break;
			case 'format':
				$MCEmenubar .= "      format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | formats | removeformat'},\n";
				break;
			case 'table':
				$MCEmenubar .= "      table: {title: 'Table', items: 'inserttable tableprops deletetable | row column | cell'},\n";
				break;
			case 'tools':
				$MCEmenubar .= "      tools: {title: 'Tools', items: 'spellchecker code'}\n";
				break;
		}
	}
	$MCEmenubar = trim($MCEmenubar, ",\n") . "\n      }";
} else {
	$MCEmenubar = "false";
}
echo $MCEmenubar;
?>,
					setup: function(editor) {
					editor.on('blur', function(ed, e) {
					form = $(editor.getContainer()).closest('form');
					if (editor.isDirty()) {
					$(form).addClass('tinyDirty');
					} else {
					$(form).removeClass('tinyDirty');
					}
					});
<?php
if (getOption('dirtyform_enable') > 1) {
	?>
						editor.on('postRender', function(e) {
						//	clear the form from any tinyMCE dirtying once it has loaded
						form = $(editor.getContainer()).closest('form');
						$(form).trigger("reset");
						});
	<?php
}
?>
					}


	});
	// ]]> -->
</script>
