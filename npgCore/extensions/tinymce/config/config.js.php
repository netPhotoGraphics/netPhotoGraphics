<?php
/**
 * The configuration parameters for TinyMCE.
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
global $_RTL_css, $_current_locale;

npgFilters::apply('tinymce_config', NULL);

if (empty($MCElocale)) {
	$MCElocale = 'en';
	$loc = $_current_locale;
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
}

if ($MCEcss) {
	$css = getPlugin(basename(TINYMCE) . '/config/' . $MCEcss, true, true);
	if ($css) {
		$MCEcss = $css;
	} else {
		$MCEcss = '';
	}
}

$MCEspecial['browser_spellcheck'] = "true";
if (OFFSET_PATH && npg_loggedin(UPLOAD_RIGHTS)) {
	$MCEspecial['images_upload_url'] = '"' . str_replace(SERVERPATH, WEBPATH, TINYMCE) . '/postAcceptor.php?XSRFToken=' . getXSRFToken('postAcceptor') . '"';
}

if ($MCEdirection == NULL) {
	if ($_RTL_css) {
		$MCEdirection = 'rtl';
	} else {
		if (getOption('tiny_mce_rtl_override')) {
			$MCEdirection = 'rtl';
		}
	}
}

scriptLoader(TINYMCE . '/tinymce.7.4.1.min.js');
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
<script>

<?php
if ($pasteObjEnabled) {
	?>
		var pasteObjConfig = {//	pasteObject window
			title: 'netPhotoGraphics:obj',
			url: '<?php echo getAdminLink(PLUGIN_FOLDER . '/' . basename(TINYMCE) . '/pasteobj/pasteobj.php', FULLWEBPATH, false); ?>',
			height: 600,
			width: 800
		};
	<?php
}
?>
	tinymce.init({<?php echo '/* ' . stripSuffix(basename($_editorconfig)) . " */\n"; ?>
	license_key: "gpl",
					promotion: false,
					entity_encoding : "<?php echo getOption('tiny_mce_entity_encoding'); ?>",
					selector: "<?php echo $MCEselector; ?>",
					language: "<?php echo $MCElocale; ?>",
					relative_urls: false,
					flash_video_player_url: false,
					inline_styles : true,
					remove_script_host : false,
					browser_spellcheck: true,
					contextmenu: false,
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
	plugins: [<?php
$line = '';
$count = 0;
foreach ($MCEplugins as $plugin) {
	$line .= '"' . $plugin . '",';
	$count++;
	if ($count % 10 == 0) {
		$line .= "\n\t\t\t";
	}
}
echo rtrim($line, ',');
;
?>],
<?php
if ($MCEexternal) {
	?>
		external_plugins: {
	<?php
	foreach ($MCEexternal as $plugin => $url) {
		echo "		  '" . $plugin . "': '" . $url . "'\n";
	}
	?>
		}
		,
	<?php
}
if (in_array('pagebreak', $MCEplugins)) {
	?>
		pagebreak_split_block: true,
	<?php
}

foreach ($MCEspecial as $element => $value) {
	echo $element . ': ' . $value . ",\n";
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
		$menu = array('file', 'edit', 'insert', 'view', 'format', 'table', 'tools', 'help');
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
				$MCEmenubar .= " charmap hr | pagebreak nonbreaking anchor | insertdatetime'},\n";
				break;
			case 'view':
				$MCEmenubar .= "      view: {title: 'View', items: 'visualaid'},\n";
				break;
			case 'format':
				$MCEmenubar .= "      format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | formats | removeformat'},\n";
				break;
			case 'table':
				$MCEmenubar .= "      table: {title: 'Table', items: 'inserttable | cell row column | advtablesort | tableprops deletetable'},\n";
				break;
			case 'tools':
				$MCEmenubar .= "      tools: {title: 'Tools', items: 'code wordcount'},\n";
				break;
			case 'help':
				$MCEmenubar .= "      help: {title: 'Help', items: 'help'},\n";
				break;
		}
	}
	$MCEmenubar = trim($MCEmenubar, ",\n") . "\n      },\n";
} else {
	$MCEmenubar = "menubar: false,\n";
}
echo $MCEmenubar;
?>
	setup: function (editor) {
	editor.on('blur', function (ed, e) {
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
		editor.on('postRender', function (e) {
		//	clear the form from any tinyMCE dirtying once it has loaded
		form = $(editor.getContainer()).closest('form');
						$(form).trigger("reset");
		});
	<?php
}
?>
	}
	}
	);
</script>
