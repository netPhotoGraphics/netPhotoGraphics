<?php

/**
 * captcha handler to bypass captcha handling
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/noCaptcha
 * @pluginCategory development
 */
// force UTF-8 Ø

global $_captcha;

$plugin_is_filter = defaultExtension(5 | CLASS_PLUGIN);
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext("Bypass captcha handling.");
	$plugin_disable = ($_captcha->name && $_captcha->name != 'noCaptcha') ? sprintf(gettext('Only one Captcha handler plugin may be enabled. <a href="#%1$s"><code>%1$s</code></a> is already enabled.'), $_captcha->name) : '';
}

class noCaptcha {

	var $name = 'noCaptcha';

	/**
	 * Checks if a CAPTCHA string matches the CAPTCHA attached to the comment post
	 * Returns true if there is a match.
	 *
	 * @param string $code
	 * @param string $code_ok
	 * @return bool
	 */
	function checkCaptcha($code, $code_ok) {
		return TRUE;
	}

	/**
	 * generates a simple captcha
	 *
	 * @return array;
	 */
	function getCaptcha($prompt = NULL) {
		return array(NULL);
	}

}

$_captcha = new noCaptcha();
