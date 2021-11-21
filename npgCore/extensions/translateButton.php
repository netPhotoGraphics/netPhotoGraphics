<?php

/**
 * Provides a Google Translate button
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/translateButton
 * @pluginCategory theme
 */
$plugin_is_filter = 5 | FEATURE_PLUGIN;
$plugin_description = gettext("Provides a macro to produce a Google Translate button.");

npgFilters::register('content_macro', 'translateButton::macro');
npgFilters::register('theme_head', 'translateButton::head');

class translateButton {

	static function macro($macros) {
		$my_macros = array(
				'TRANSLATEBUTTON' => array('class' => 'procedure',
						'params' => array(),
						'value' => 'translateButton::button',
						'owner' => 'translateButton',
						'desc' => gettext('Places a Google Translate button on a page.'))
		);
		return array_merge($macros, $my_macros);
	}

	static function button() {
		?>
		<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
		<div id="google_translate_element"></div>
		<script type="text/javascript">
			function googleTranslateElementInit() {
				new google.translate.TranslateElement({pageLanguage: 'en', layout: google.translate.TranslateElement.InlineLayout.SIMPLE}, 'google_translate_element');
			}
		</script>
		<?php

	}

	static function head() {
		?>
		<style>
			#google_translate_element {
				text-decoration: none;
				float: right !important;
			}
		</style>
		<?php

	}

}
