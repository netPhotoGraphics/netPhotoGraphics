<?php
/**
 * A plugin to require that visitors view and acknowledge a site policy page upon the first
 * visit to your site.
 *
 * The plugin requires a <i>zenpage page</i> object or a custom URL which states the site usage policy.
 * This URL will also become the <i>Policy URL</i> used for <i>Policy Submit</i> buttons. But of course that will
 * be moot since the visitor will have had to acknowledge your policy before
 * any content pages are shown.
 *
 * Usage:
 *
 * <ul>
 * 	<li>
 * 		Create a <em>zenpage page</em> or a custom theme script that states your site usage policy. For guidelines visit
 * 		{@link https://www.itgovernance.co.uk/blog/how-to-write-a-gdpr-privacy-notice-with-documentation-template-example/* How to write a GDPR privacy notice}.
 * </li>
 * 	<li>
 * 		Place the following <i>macro</i> in your page content or in one of the <i>codeblocks</i> for the page:
 * 		<code>[POLICYBUTTON]</code>
 * 		(This <i>macro</i> will place the policy button on the page.)
 *
 * 		<strong>Note</strong>: If you are using a custom page you will need to place a function call to
 * 		<code>GDPR_required::button();</code> to place a policy button somewhere appropriate in	your script.
 * 	</li>
 * 	<li>
 * 		Enable the <i>Usage policy</i> option on the general options page.
 * 	</li>
 *
 * </ul>
 *
 * Now when a visitor visits your site for the first time the site will redirect him to
 * your policy page. When he checks the acknowledgement box a button will appear to
 * direct him to your site index.
 *
 * @author Stephen Billard (sbillard)
 * @Copyright 2016 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/GDPR_required
 * @pluginCategory theme
 */
$plugin_is_filter = 980 | FEATURE_PLUGIN;
$plugin_description = gettext('Inject a site policy acknowledgement page.');

$option_interface = 'GDPR_required';

class GDPR_required {

	function __construct() {
		setOptionDefault('GDPR_Bots_Allowed', 'Baiduspider,bingbot,Googlebot,Google page speed insights,W3C-checklink,W3C_Validator,Yahoo! Slurp');
	}

	function getOptionsSupported() {
		global $_CMS;

		$possibilities = array('*' . gettext('Custom url') . '*' => '');
		if ($_CMS) {
			foreach ($_CMS->getPages(false) as $page) {
				$possibilities[get_language_string($page['title'])] = $page['titlelink'];
			}
		}
		$options = array(
				gettext('Policy page') => array('key' => 'GDPR_page', 'type' => OPTION_TYPE_SELECTOR,
						'selections' => $possibilities,
						'order' => 1,
						'desc' => gettext('The <em>script page</em> or zenpage <em>page</em> object to use as the policy page.')),
				gettext('Policy page URL') => array('key' => 'GDPR_URL', 'type' => OPTION_TYPE_CUSTOM,
						'order' => 2,
						'desc' => gettext('The URL to the site policy page. This will be the link to the <em>Policy page</em> object if a zenpage <em>page</em> is selected.')),
				gettext('disable list') => array('key' => 'GDPR_Bots_Allowed', 'type' => OPTION_TYPE_TEXTAREA,
						'order' => 4,
						'multilingual' => false,
						'desc' => gettext('Provide a comma separated list of user agents (web crawlers) that may bypass the acknowledgement. This is useful to allow indexing robots to browse your site.'))
		);
		$notice = array();
		if (!extensionEnabled('zenpage')) {
			$notice [] = gettext('The zenpage plugin is not enabled.');
			if (!file_exists(SERVERPATH . '/' . THEMEFOLDER . '/' . internalToFilesystem(getCurrentTheme()) . '/pages.php')) {
				$notice[] = gettext('The active theme has no <em>pages.php</em> script.');
			}
			$options['note'] = array('key' => 'GDPR_note', 'type' => OPTION_TYPE_NOTE,
					'order' => 3,
					'desc' => '<p class="warningbox">' . implode('<br />', $notice) . '</p>');
		}

		return $options;
	}

	function handleOption($option, $currentvalue) {
		?>
		<input type="text" name="GDPR_URL" size="35" value="<?php echo getOption('GDPR_URL'); ?>" />
		<?php
	}

	static function handleOptionSave($themename, $themealbum) {
		$page = getOption('GDPR_page');
		if ($page) {
			$pageobj = newPage($page);
			$link = $pageobj->getLink();
		} else {
			$link = sanitize($_POST['GDPR_URL']);
		}
		setOption('GDPR_URL', $link);
		return false;
	}

	/*
	 * Tests if the site policy has been acknowledged and if not, redirects to the
	 * policy page.
	 */

	static function page($themeScript, $requested_object) {
		global $_current_admin_obj, $_GDPR_acknowledge_loaded;
		if (!checkAccess($hint, $show)) { // password form will be shown!
			return $themeScript;
		}

		if (!($_current_admin_obj && $_current_admin_obj->getPolicyAck()) && getNPGCookie('policyACK') != getOption('GDPR_cookie')) {
			if ($link = getOption('GDPR_URL')) {
				$parts = explode('?', getRequestURI());
				if ($link == $parts[0]) {
					$_GDPR_acknowledge_loaded = true;
				} else {
					$goodBots = explode(',', strtolower(getOption('GDPR_Bots_Allowed')));
					$agent = strtolower(@$_SERVER['HTTP_USER_AGENT']);
					$require = true;
					foreach ($goodBots as $bot) {
						if (strpos($agent, $bot) !== false) {
							$require = false;
							break;
						}
					}
					if ($require) {
						$from = '?from=' . urlencode(getRequestURI());
						//	redirect to the policy page
						header("HTTP/1.0 307 Found");
						header("Status: 307 Found");
						header('Location: ' . $link . $from);
						exit();
					}
				}
			}
		}
		return $themeScript;
	}

	/**
	 * Displays the policySubmitButton on the policy page.
	 *
	 * Note: the button will NOT be present if the visitor has already acknowledged
	 * the policy, e.g. he visited the page from a normal link after his acknowledgement
	 * was recorded.
	 *
	 * @param string $target where the button should redirect
	 *
	 * @global type $_GDPR_acknowledge_loaded
	 */
	static function button($target = NULL) {
		global $_GDPR_acknowledge_loaded;
		if ($_GDPR_acknowledge_loaded) {
			setOption('GDPR_text', gettext('Check to acknowledge the site usage policy.'), false);
			setOption('GDPR_acknowledge', 1, false);
			if (is_null($target)) {
				if (isset($_GET['from'])) {
					$target = sanitizeRedirect(urldecode($_GET['from']));
				}
				if (empty($target)) {
					$target = getGalleryIndexURL();
				}
			}
			?>
			<form action="<?php echo $target; ?>" method = "post">
				<?php policySubmitButton(gettext('Continue to site')); ?>
			</form>
			<?php
		} else {
			?>
			<span style="color: green;"><?php echo gettext('Site usage policy has been acknowledged.'); ?></span>
			<?php
		}
	}

	static function macro($macros) {
		$my_macros = array(
				'POLICYBUTTON' => array('class' => 'procedure',
						'params' => array('string*'),
						'value' => 'GDPR_required::button',
						'owner' => 'GDPR_required',
						'desc' => gettext('Places a policy submit button on a page. Provide the target link (optionally) as %1.'))
		);
		return array_merge($macros, $my_macros);
	}

	static function isMe($allow, $page) {
		if ($link = getOption('GDPR_URL')) {
			$parts = explode('?', getRequestURI());
			if ($link == $parts[0]) {
				return true;
			}
		}
		return $allow;
	}

}

npgFilters::register('load_theme_script', 'GDPR_required::page');
npgFilters::register('content_macro', 'GDPR_required::macro');
npgFilters::register('isUnprotectedPage', 'GDPR_required::isMe');
