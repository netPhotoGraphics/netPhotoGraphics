<?php
/**
 * Default captcha handler
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/captcha
 * @pluginCategory admin
 */
// force UTF-8 Ø

global $_captcha;

$plugin_is_filter = defaultExtension(5 | CLASS_PLUGIN);
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext("netPhotoGraphics captcha handler.");
	$plugin_disable = ($_captcha->name && $_captcha->name != 'captcha') ? sprintf(gettext('Only one Captcha handler plugin may be enabled. <a href="#%1$s"><code>%1$s</code></a> is already enabled.'), $_captcha->name) : '';
}

$option_interface = 'captcha';

class captcha {

	var $name = 'captcha';

	/**
	 * Class instantiator
	 *
	 * @return captcha
	 */
	function __construct() {
		global $plugin_is_filter;
		if (OFFSET_PATH == 2) {
			if ($priority = extensionEnabled('zpCaptcha')) {
				enableExtension('captcha', $priority);
				enableExtension('zpCaptcha', 0);
			}
			if (getOption('zenphoto_captcha_key')) {
				setOption('npg_captcha_font', getOption('zenphoto_captcha_font'));
				setOption('npg_captcha_length', getOption('zenphoto_captcha_length'));
				setOption('npg_captcha_font_size', getOption('zenphoto_captcha_font_size'));
				setOption('npg_captcha_key', getOption('zenphoto_captcha_key'));
				setOption('npg_captcha_string', getOption('zenphoto_captcha_string'));

				purgeOption('zenphoto_captcha_font');
				purgeOption('zenphoto_captcha_length');
				purgeOption('zenphoto_captcha_font_size');
				purgeOption('zenphoto_captcha_key');
				purgeOption('zenphoto_captcha_string');
			}

			setOptionDefault('npg_captcha_font', '');
			setOptionDefault('npg_captcha_length', 5);
			setOptionDefault('npg_captcha_font_size', 18);
			setOptionDefault('npg_captcha_key', sha1($_SERVER['HTTP_HOST'] . 'a9606420399a77387af2a4b541414ee5' . getUserIP()));
			setOptionDefault('npg_captcha_string', 'abcdefghijkmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWXYZ');
		}
	}

	/**
	 * Returns array of supported options for the admin-options handler
	 *
	 * @return unknown
	 */
	function getOptionsSupported() {
		$fontlist = gl_getFonts();
		$options = array(
				gettext('Hash key') => array('key' => 'npg_captcha_key', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 2,
						'desc' => gettext('The key used in hashing the CAPTCHA string. Note: this key will change with each successful CAPTCHA verification.')),
				gettext('Allowed characters') => array('key' => 'npg_captcha_string', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 1,
						'desc' => gettext('The characters which may appear in the CAPTCHA string.')),
				gettext('CAPTCHA length') => array('key' => 'npg_captcha_length', 'type' => OPTION_TYPE_RADIO,
						'order' => 0,
						'buttons' => array(gettext('3') => 3, gettext('4') => 4, gettext('5') => 5, gettext('6') => 6),
						'desc' => gettext('The number of characters in the CAPTCHA.')),
				gettext('CAPTCHA font') => array('key' => 'npg_captcha_font', 'type' => OPTION_TYPE_SELECTOR,
						'order' => 3,
						'selections' => array_merge(array('*' . gettext('random') . '*' => '*'), $fontlist),
						'desc' => gettext('The font to use for CAPTCHA characters.')),
				gettext('CAPTCHA font size') => array('key' => 'npg_captcha_font_size', 'type' => OPTION_TYPE_NUMBER,
						'order' => 3.5,
						'disabled' => getSuffix(getOption('npg_captcha_font')) != 'ttf',
						'desc' => gettext('The size to use if the font is scalable (<em>TTF</em> and <em>Imagick</em> fonts.)')),
				'' => array('key' => 'npg_captcha_image', 'type' => OPTION_TYPE_CUSTOM,
						'order' => 4,
						'desc' => gettext('Sample CAPTCHA image'))
		);
		return $options;
	}

	function handleOption($key, $cv) {
		$captcha = $this->getCaptcha(NULL);
		preg_match('/src=\"(.*?)\"/', $captcha['html'], $matches);
		?>
		<span id="npg_captcha_image_loc">
			<?php echo $captcha['html']; ?>
		</span>
		<script type="text/javascript">
			// <!-- <![CDATA[
			var path = '<?php echo $matches[1]; ?>';
			window.addEventListener('load', function () {
				$('#__npg_captcha_font').change(function () {
					newpath = path + '&amp;f=' + $('#__npg_captcha_font').val() + '&amp;p=' + $('#__npg_captcha_font_size').val();
					nbase = $('#npg_captcha_image_loc').html().replace(/src=".+?"/g, 'src="' + newpath + '"');
					$('#npg_captcha_image_loc').html(nbase);
					suffix = $('#__npg_captcha_font').val().split('.').pop().toLowerCase();
					if (suffix == 'ttf') {
						$('#__npg_captcha_font_size').prop('disabled', '');
					} else {
						$('#__npg_captcha_font_size').prop('disabled', 'disabled');
					}
				});
				$('#__npg_captcha_font_size').change(function () {
					newpath = path + '&amp;f=' + $('#__npg_captcha_font').val() + '&amp;p=' + $('#__npg_captcha_font_size').val();
					nbase = $('#npg_captcha_image_loc').html().replace(/src=".+?"/g, 'src="' + newpath + '"');
					$('#npg_captcha_image_loc').html(nbase);
				});
				$('#form_options').on('reset', function () {
					nbase = $('#npg_captcha_image_loc').html().replace(/src=".+?"/g, 'src="' + path + '"');
					$('#npg_captcha_image_loc').html(nbase);
				});
			}, false);
			// ]]> -->
		</script>
		<?php
	}

	/**
	 * gets (or creates) the CAPTCHA encryption key
	 *
	 * @return string
	 */
	function getCaptchaKey() {
		global $_authority;
		$key = getOption('npg_captcha_key');
		if (empty($key)) {
			$admin = $_authority->getMasterUser();
			if (is_object($admin)) {
				$key = $admin->getPass();
			} else {
				$key = 'No admin set';
			}
			$key = sha1('npg' . $key . 'captcha key');
			setOption('npg_captcha_key', $key);
		}
		return $key;
	}

	/**
	 * Checks if a CAPTCHA string matches the CAPTCHA attached to the comment post
	 * Returns true if there is a match.
	 *
	 * @param string $code
	 * @param string $code_ok
	 * @return bool
	 */
	function checkCaptcha($code, $code_ok) {
		$captcha_len = getOption('npg_captcha_length');
		$key = $this->getCaptchaKey();
		$code_cypher = sha1(bin2hex(rc4($key, trim($code))));
		$code_ok = trim($code_ok);
		if ($code_cypher != $code_ok || strlen($code) != $captcha_len) {
			return false;
		}
		query('DELETE FROM ' . prefix('captcha') . ' WHERE `ptime`<' . (time() - 3600)); // expired tickets
		$result = query('DELETE FROM ' . prefix('captcha') . ' WHERE `hash`="' . $code_cypher . '"');
		if ($result && db_affected_rows() == 1) {
			$len = rand(0, strlen($key) - 1);
			$key = sha1(substr($key, 0, $len) . $code . substr($key, $len));
			setOption('npg_captcha_key', $key);
			return true;
		}
		return false;
	}

	/**
	 * generates a simple captcha
	 *
	 * @return array;
	 */
	function getCaptcha($prompt = NULL) {
		global $_HTML_cache;
		$_HTML_cache->disable();
		$captcha_len = getOption('npg_captcha_length');
		$key = $this->getCaptchaKey();
		$lettre = getOption('npg_captcha_string');
		$numlettre = strlen($lettre) - 1;

		$string = '';
		for ($i = 0; $i < $captcha_len; $i++) {
			$string .= $lettre[rand(0, $numlettre)];
		}
		$cypher = bin2hex(rc4($key, $string));
		$code = sha1($cypher);
		query('DELETE FROM ' . prefix('captcha') . ' WHERE `ptime`<' . (time() - 3600), false); // expired tickets
		query("INSERT INTO " . prefix('captcha') . " (ptime, hash) VALUES (" . db_quote(time()) . "," . db_quote($code) . ")", false);
		$html = '<label for="code" class="captcha_label">' . $prompt . '</label><img id="captcha" src="' . WEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/captcha/c.php?i=' . $cypher . '" alt="Code" />';
		$input = '<input type="text" id="code" name="code" class="captchainputbox" />';
		$hidden = '<input type="hidden" name="code_h" value="' . $code . '" />';
		return array('input' => $input, 'html' => $html, 'hidden' => $hidden);
	}

}

$_captcha = new captcha();
