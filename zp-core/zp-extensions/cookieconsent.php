<?php
/**
 * A plugin to add a cookie notify dialog to comply with the EU cookie law and Google's
 * requirement for Google Ads and more. See
 * {@link https://www.cookiechoices.org Helping publishers and advertisers with consent }
 *
 * Adapted of
 * {@link https://cookieconsent.insites.com COOKIE CONSENT by Insites }
 *
 * @author Malte Müller (acrylian), Fred Sondaar (fretzl), Vincent Bourganel (vincent3569)
 * @license GPL v3 or later
 *
 * @author Malte Müller (acrylian), Fred Sondaar (fretzl), Vincent Bourganel (vincent3569), Stephen Billard (netPhotoGraphics migration)

 * @package plugin/cookieconsent
 * @pluginCategory theme
 */
$plugin_is_filter = 5 | THEME_PLUGIN;
$plugin_description = gettext("A plugin to add a cookie notify dialog");
$option_interface = 'cookieConsent';

if (!isset($_COOKIE['cookieconsent_status'])) {
	npgFilters::register('theme_body_close', 'cookieConsent::getCSS');
	npgFilters::register('theme_body_close', 'cookieConsent::getJS');
}

class cookieConsent {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('zpcookieconsent_domain', $_SERVER['HTTP_HOST']);
			setOptionDefault('zpcookieconsent_expirydays', 365);
			setOptionDefault('zpcookieconsent_theme', 'block');
			setOptionDefault('zpcookieconsent_position', 'bottom');
			setOptionDefault('zpcookieconsent_colorpopup', '#000');
			setOptionDefault('zpcookieconsent_colorbutton', '#f1d600');
			setOptionDefault('zpcookieconsent_buttonlearnmorelink', getOption('GDPR_URL'));
			setOptionDefault('zpcookieconsent_buttonagree', getAllTranslations('Agree'));
			setOptionDefault('zpcookieconsent_buttonlearnmore', getAllTranslations('More info'));
			setOptionDefault('zpcookieconsent_message', getAllTranslations('This website uses cookies. By continuing to browse the site, you agree to our use of cookies.'));
		}
	}

	function getOptionsSupported() {
		$options = array(
				gettext('Button: Agree') => array(
						'key' => 'zpcookieconsent_buttonagree',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 1,
						'multilingual' => 1,
						'desc' => gettext('Text used for the dismiss button. Leave empty to use the default text.')),
				gettext('Button: Learn more') => array(
						'key' => 'zpcookieconsent_buttonlearnmore',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2,
						'multilingual' => 1,
						'desc' => gettext('Text used for the learn more info button. Leave empty to use the default text.')),
				gettext('Button: Learn more - URL') => array(
						'key' => 'zpcookieconsent_buttonlearnmorelink',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 3,
						'desc' => gettext('URL to your cookie policy / privacy info page.')),
				gettext('Message') => array(
						'key' => 'zpcookieconsent_message',
						'type' => OPTION_TYPE_TEXTAREA,
						'order' => 4,
						'multilingual' => 1,
						'desc' => gettext('The message shown by the plugin. Leave empty to use the default text.')),
				gettext('Domain') => array(
						'key' => 'zpcookieconsent_domain',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 5,
						'desc' => gettext('The domain for the consent cookie that Cookie Consent uses, to remember that users have consented to cookies. Useful if your website uses multiple subdomains, e.g. if your script is hosted at <code>www.example.com</code> you might override this to <code>example.com</code>, thereby allowing the same consent cookie to be read by subdomains like <code>foo.example.com</code>.')),
				gettext('Expire') => array(
						'key' => 'zpcookieconsent_expirydays',
						'type' => OPTION_TYPE_TEXTBOX,
						'order' => 6,
						'desc' => gettext('The number of days Cookie Consent should store the user’s consent information for. Use -1 for no expiry.')),
				gettext('Theme') => array(
						'key' => 'zpcookieconsent_theme',
						'type' => OPTION_TYPE_SELECTOR,
						'order' => 7,
						'selections' => array(
								'block' => 'block',
								'edgeless' => 'edgeless',
								'classic' => 'classic',
								gettext('custom') => 'custom'
						),
						'desc' => gettext('These are the included default themes. The chosen theme is added to the popup container as a CSS class in the form of .cc-style-THEME_NAME. Users can create their own themes.')),
				gettext('Position') => array(
						'key' => 'zpcookieconsent_position',
						'type' => OPTION_TYPE_SELECTOR,
						'order' => 7,
						'selections' => array(
								gettext('Top') => 'top',
								gettext('Top left') => 'top-left',
								gettext('Top right') => 'top-right',
								gettext('Bottom') => 'bottom',
								gettext('Bottom left') => 'bottom-left',
								gettext('Bottom right') => 'bottom-right',
						),
						'desc' => gettext('Choose the position of the popup. Top and Bottom = banner, Top left/right, Bottom left/right = floating')),
				gettext('Dismiss on Scroll') => array(
						'key' => 'zpcookieconsent_dismissonscroll',
						'type' => OPTION_TYPE_CHECKBOX,
						'order' => 9,
						'desc' => gettext('Check to dismiss when users scroll a page [other than <em>Learn more</em> page].')),
				gettext('Color - Popup') => array(
						'key' => 'zpcookieconsent_colorpopup',
						'type' => OPTION_TYPE_COLOR_PICKER,
						'order' => 10,
						'desc' => gettext('Choose the color of the popup background.')),
				gettext('Color - Button') => array(
						'key' => 'zpcookieconsent_colorbutton',
						'type' => OPTION_TYPE_COLOR_PICKER,
						'order' => 11,
						'desc' => gettext('Choose the color of the button.'))
		);
		return $options;
	}

	static function getCSS() {
		scriptLoader(getPlugin('cookieconsent/cookieconsent.min.css'));
	}

	static function getJS() {

		$message = get_language_string(getOption('zpcookieconsent_message'));
		$dismiss = get_language_string(getOption('zpcookieconsent_buttonagree'));
		$learnmore = get_language_string(getOption('zpcookieconsent_buttonlearnmore'));
		$link = getOption('zpcookieconsent_buttonlearnmorelink');
		$theme = getOption('zpcookieconsent_theme');
		$domain = getOption('zpcookieconsent_domain');
		$position = getOption('zpcookieconsent_position');
		$cookie_expiry = getOption('zpcookieconsent_expirydays');
		$dismiss_on_scroll = "false";
		if (getOption('zpcookieconsent_dismissonscroll') && strpos(sanitize($_SERVER['REQUEST_URI']), $link) === false) { // false in Cookie Policy Page
			$dismiss_on_scroll = 100;
		}
		$color_popup = getOption('zpcookieconsent_colorpopup');
		$color_button = getOption('zpcookieconsent_colorbutton');
		scriptLoader(CORE_SERVERPATH . PLUGIN_FOLDER . '/cookieconsent/cookieconsent.min.js');
		?>
		<script>
			window.addEventListener("load", function () {
				window.cookieconsent.initialise({
					"palette": {
						"popup": {
							"background": "<?php echo $color_popup; ?>"
						},
						"button": {
							"background": "<?php echo $color_button; ?>"
						}
					},
					"position": "<?php echo js_encode($position); ?>",
					"theme": "<?php echo js_encode($theme); ?>",
					"dismissOnScroll": <?php echo js_encode($dismiss_on_scroll); ?>,
					"cookie": {
						"expiryDays": <?php echo js_encode($cookie_expiry); ?>,
						"domain": "<?php echo js_encode($domain); ?>"
					},
					"content": {
						"message": "<?php echo js_encode($message); ?>",
						"dismiss": "<?php echo js_encode($dismiss); ?>",
						"link": "<?php echo js_encode($learnmore); ?>",
						"href": "<?php echo html_encode($link); ?>"
					},
					onStatusChange: function (status) {
						this.element.parentNode.removeChild(this.element);
						$.ajax({
							type: 'POST',
							cache: false,
							data: 'ajaxRequest=cookieconsent&status=' + status,
							url: '<?php echo getAdminLink(PLUGIN_FOLDER . '/cookieconsent/ajaxHandler.php'); ?>'
						});
					}
				})
			});
		</script>
		<?php
	}

}
