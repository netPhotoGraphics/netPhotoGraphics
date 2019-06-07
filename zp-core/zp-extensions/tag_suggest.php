<?php
/**
 * Provide javaScript tag "suggestions"
 * Based on Remy Sharp's {@link http://remysharp.com/2007/12/28/jquery-tag-suggestion/ jQuery Tag Suggestion}
 * plugin as modified for netPhotoGraphics to enhance performance.
 *
 * This plugin provides suggestions for tag fields such as the search form. It is
 * automatically enabled for administration fields. The plugin must be enabled for
 * the suggestions to appear on theme pages.
 *
 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/tag_suggest
 * @pluginCategory theme
 */
$plugin_is_filter = defaultExtension(9 | THEME_PLUGIN);
$plugin_description = gettext("Enables jQuery tag suggestions on the search field.");

$option_interface = 'tag_suggest';

npgFilters::register('theme_body_close', 'tag_suggest::JS');
npgFilters::register('admin_head', 'tag_suggest::JS');

class tag_suggest {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('tag_suggest_threshold', 1);
		}
	}

	function getOptionsSupported() {
		$options = array(gettext('threshold') => array('key' => 'tag_suggest_threshold', 'type' => OPTION_TYPE_NUMBER,
						'order' => 1,
						'limits' => array('min' => 1),
						'desc' => gettext('Only tags with at least this number of uses will be suggested.'))
		);
		return $options;
	}

	static function JS() {
		// the scripts needed
		scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/tag_suggest/encoder.min.js');
		scriptLoader(CORE_SERVERPATH .  PLUGIN_FOLDER . '/tag_suggest/tag.min.js');
		scriptLoader(getPlugin('tag_suggest/tag.css', true));
		$taglist = getAllTagsUnique(OFFSET_PATH ? false : NULL, OFFSET_PATH ? 0 : getOption('tag_suggest_threshold'));
		$tags = array();
		foreach ($taglist as $tag) {
			$tags[] = addslashes($tag);
		}

		if (OFFSET_PATH || getOption('search_space_is') == 'OR') {
			$tagseparator = ' ';
		} else {
			$tagseparator = ',';
		}
		?>
		<script type="text/javascript">
			// <!-- <![CDATA[
			var _tagList = ["<?php echo implode($tags, '","'); ?>"];
			$(function () {
				$('#search_input, #edit-editable_4, .tagsuggest').tagSuggest({separator: '<?php echo $tagseparator; ?>', tags: _tagList, quoteSpecial: <?php echo OFFSET_PATH ? 'false' : 'true'; ?>})
			});
			// ]]> -->
		</script>
		<?php
	}

}
?>