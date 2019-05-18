<?php
/**
 *
 * This plugin creates two development pages that list the netPhotoGraphic rewrite rules and the
 * rewrite token definitions. It also provides an option interface to change the definition of
 * the rewrite tokens.
 *
 *
 * The netPhotoGraphics rewrite rules are shown as "active". That is the rules will
 * have had all definitions replaced with the definition value so that the rule
 * is shown in the state in which it is applied.
 *
 * It will read the "rules.txt" plugin file if present
 * and create netPhotoGraphics rewrite rules from the text.
 *
 * The %USER_PLUGIN_FOLDER%/rewriteRules/rules.txt file consists of the
 * following kinds of lines:
 *
 * <dl>
 * <dt>comments</dt><dd>any line beginning in a crosshatch (#)</dd>
 * <dt>definitions</dt><dd>define <code>token</code> => <code>definition</code></dd>
 * <dt>rule</dt><dd>rewriterule <em>pattern</em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em>substitution</em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em>flags</em></dd>
 * </dl>
 *
 * The <code>definition</code> will be evaluated by PHP before processing the rules.
 * Thus you may use netPhotoGraphic defines, expressions such as <code>getOption('<em>option name<em>')</code>,
 * etc. and the runtime value will be what the definition resolves to. (<b>Note:</b> the set of functions
 * available at the time rewrite rules are processed is limited. In particular, template functions and functions
 * from plugins that are not <code>CLASS</code> or <code>FEATURE</code> plugins will not be available for use.)
 *
 *
 * There are already definitions for many useful rewrite tokens. You can view these and see the standard rewrite rules
 * on the {@link %FULLWEBPATH%/%ZENFOLDER%/%PLUGIN_FOLDER%/rewriteRules/admin_tab.php DEVELOPMENT/REWRITE} admin page.
 *
 * An example <em>rules.txt</em> file:
 *
 * <code>
 * ### Example rules file
 *
 * #### Definitions
 * 	<br />
 * 	Define %REWRITE_RULES%						=>	"rules-list"
 * 	<br />
 * 	Define &percnt;PLUGIN_FOLDER&percnt;						=>	PLUGIN_FOLDER
 * 	<br />
 * 	define %BREAKING_NEWS%						=>	str_replace(WEBPATH.'/','',newCategory("Breaking-news")->getLink(1));
 *
 *
 * 	#### Rewrite rule cause "rules-list" to redirect to the rewriteRules admin page
 * 	<br />
 * 	RewriteRule ^%REWRITE_RULES%/*$										&percnt;ZENFOLDER&percnt;/&percnt;PLUGIN_FOLDER&percnt;/rewriteRules/admin_tab.php [L,QSA]
 *
 * 	### Rewite rule to cause "back-end" to redirect to the admin overview page
 * 	<br />
 * 	RewriteRule ^back-end/*$													&percnt;ZENFOLDER&percnt;/admin.php [L,QSA]
 * 	<br />
 * 	### Rewite rule to cause "contact-us" to redirect to the theme "contact" script
 * 	<br />
 * 	RewriteRule ^contact-us/*$												index.php?p=contact [L,QSA]
 * 	<br />
 * 	### Rewite rule to cause "contact-us" to redirect to the theme "contact" script
 * 	<br />
 * 	RewriteRule ^contact-us/*$												index.php?p=contact [L,QSA]
 * 	### Rewite rule to cause "breaking-news" to redirect to the theme "Breaking-news" category page
 * 	<br />
 * 	RewriteRule ^breaking-news/*$											%BREAKING_NEWS%
 * </code>
 *
 * The first Define associates the token <code>%REWRITE_RULES%</code> with the string <code>rules-list</code>
 * The second associates <code>&percnt;PLUGIN_FOLDER&percnt;</code> with the netPhotoGraphics define <code>PLUGIN_FOLDER</code>
 * which is currently defined as <code>%PLUGIN_FOLDER%</code>. The token <code>&percnt;ZENFOLDER&percnt;</code> used in the rules
 * has previously been defined in the standard rewrite rules as <code>%ZENFOLDER%</code>. The third Define is an example
 * of a complex expression. In this case computing the link to the theme category page with the titlelink "Breaking-news".
 * The code strips off the WEB path since the rewrite rule redirection prepends that to the
 * link before redirecting.
 *
 *
 * netPhotoGraphics rules processing follows a simplified version of the
 * {@link https://httpd.apache.org/docs/2.2/mod/mod_rewrite.html#RewriteRule Apache RewriteRule Directive}.
 * At this time the netPhotoGraphics rewrite rules only handle full URL
 * rewrite.  Thus the rewrite token must be proceded by a caret(<code>^</code>) and consume the full
 * URL (the <code>/*$</code> termination.) The "L" <em>flag</em> is implied (i.e. processing
 * stops when a match is made.) The redirect ("R") and query string append ("QSA")
 * <em>flags</em> are honored. All other <em>flags</em> are ignored. If the <em>substitution path</em>
 * is not to "index.php" or the "R" <em>flag</em> is present, rules processing will force a page
 * reload to the <em>substitution path</em>. Redirection away from the netPhotoGraphics site is not supported.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/rewriteRules
 * @pluginCategory admin
 *
 * @Copyright 2019 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics and derivatives}
 */
$plugin_is_filter = 1 | FEATURE_PLUGIN;
$plugin_description = gettext("Site rewrite rules.");

$option_interface = 'rewriteTokens';

zp_register_filter('admin_tabs', 'rewriteRules::tabs', 100);

class rewriteRules {

	static function tabs($tabs) {
		if (zp_loggedin(ADMIN_RIGHTS)) {
			if (!isset($tabs['development'])) {
				$tabs['development'] = array('text' => gettext("development"),
						'link' => WEBPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/rewriteRules/rules_tab.php?page=development&tab=rewrite',
						'default' => "rewrite",
						'subtabs' => NULL);
			}
			$tabs['development']['subtabs'][gettext("rewrite")] = PLUGIN_FOLDER . '/rewriteRules/rules_tab.php?page=development&tab=rewrite';
			$tabs['development']['subtabs'][gettext("tokens")] = PLUGIN_FOLDER . '/rewriteRules/tokens_tab.php?page=development&tab=tokens';
		}
		return $tabs;
	}

	static function processRules($ruleFile) {
		global $_zp_conf_vars;
		$customRules = explode("\n", $ruleFile);

		$definitions = array();
		$rules[] = array('comment' => "\t#### Rules from rewriteRules/rules.txt");
		foreach ($customRules as $rule) {
			$rule = trim($rule);
			if (strlen($rule) > 0 && $rule{0} != '#') {
				if (preg_match('~define\s(.+?)\s*=>\s+(.+)~i', $rule, $matches)) {
					$definitions[] = array('definition' => $matches[1], 'rewrite' => $matches[2]);
				} else {
					if (preg_match('~rewriterule\s+\^(.+?)\/\*\$\s+(.+)~i', $rule, $matches)) {
						$rules[] = array('rewrite' => $matches[1], 'rule' => '^%REWRITE%/*$	' . $matches[2]);
					}
				}
			}
		}
		$_zp_conf_vars['special_pages'] = array_merge($_zp_conf_vars['special_pages'], $definitions, $rules);
	}

}

require_once(SERVERPATH . '/' . ZENFOLDER . '/functions-config.php');

class rewriteTokens {

	private $zp_cfg_a;
	private $zp_cfg_b;
	private $conf_vars = array();
	private $plugin_vars = array();

	function __construct() {
		global $_configMutex, $_zp_conf_vars;
		$_configMutex->lock();
		$zp_cfg = file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);
		$i = strpos($zp_cfg, "\$conf['special_pages']");
		$j = strpos($zp_cfg, '//', $i);
		if ($i === false || $j === false) {
			$this->conf_vars = $_zp_conf_vars['special_pages'];
			$i = strpos($zp_cfg, '/** Do not edit below this line. **/');
			if ($i === false) {
				trigger_error(gettext('The Configuration file is corrupt. You will need to restore it from a backup.'), E_USER_ERROR);
			}
			$this->zp_cfg_a = substr($zp_cfg, 0, $i);
			$this->zp_cfg_b = "//\n" . substr($zp_cfg, $i);
		} else {
			$this->zp_cfg_a = substr($zp_cfg, 0, $i);
			$this->zp_cfg_b = substr($zp_cfg, $j);
			eval(substr($zp_cfg, $i, $j - $i));
			$this->conf_vars = $conf['special_pages'];
			foreach ($_zp_conf_vars['special_pages'] as $page => $element) {
				if (isset($element['option'])) {
					$this->plugin_vars[$page] = $element;
				}
			}
		}

		if (OFFSET_PATH == 2) {
			$old = array_keys($conf['special_pages']);
			$zp_cfg = file_get_contents(SERVERPATH . '/' . ZENFOLDER . '/zenphoto_cfg.txt');
			$i = strpos($zp_cfg, "\$conf['special_pages']");
			$j = strpos($zp_cfg, '//', $i);
			eval(substr($zp_cfg, $i, $j - $i));
			$new = array_keys($conf['special_pages']);
			if ($old != $new) {
				//Things have changed, need to reset to defaults;
				setOption('rewriteTokens_restore', 1);
				$this->handleOptionSave(NULL, NULL);
				setupLog(gettext('rewriteTokens restored to default'), true);
			}
		} else {
			enableExtension('rewriteTokens', 97 | ADMIN_PLUGIN); //	plugin must be enabled for saving options
		}
	}

	function __destruct() {
		global $_configMutex;
		$_configMutex->unlock();
	}

	protected static function anOption($page, $element, &$_definitions) {
		if ($define = $element['define']) {
			$_definitions[$element['define']] = strtr($element['rewrite'], $_definitions);
			$rewrite = strtr($element['rewrite'], $_definitions);
			if (!$rewrite) {
				$rewrite = '<strong>' . gettext('disabled') . '</strong>';
			}
			$desc = sprintf(gettext('The <code>%1$s</code> rule defines <strong>%2$s</strong> as <em>%3$s</em>.'), $page, $define, $rewrite);
		} else {
			$desc = sprintf(gettext('Link for <em>%s</em> script page.'), $page);
		}
		return array('key' => 'rewriteTokens_' . $page, 'type' => OPTION_TYPE_CUSTOM,
				'desc' => $desc);
	}

	function getOptionsSupported() {
		$_definitions = array();
		$options = array();
		if (!MOD_REWRITE) {
			$options['note'] = array(
					'key' => 'rewriteTokens_note',
					'type' => OPTION_TYPE_NOTE,
					'order' => 0,
					'desc' => gettext('<p class="notebox">Rewrite Tokens are not useful unless the <code>mod_rewrite</code> option is enabled.</p>')
			);
		}
		$options[gettext('Reset')] = array('key' => 'rewriteTokens_restore', 'type' => OPTION_TYPE_CHECKBOX,
				'order' => 99999,
				'desc' => gettext('Restore defaults.'));
		foreach ($this->conf_vars as $page => $element) {
			$options[$page] = self::anOption($page, $element, $_definitions);
		}
		foreach ($this->plugin_vars as $page => $element) {
			$options[$page] = self::anOption($page, $element, $_definitions);
		}
		ksort($options);
		$order = 0;
		foreach ($options as $key => $option) {
			$options[$key]['order'] = $order++;
		}
		return $options;
	}

	function handleOption($option, $currentValue) {
		$element = str_replace('rewriteTokens_', '', $option);
		if (array_key_exists($element, $this->plugin_vars)) {
			$element = $this->plugin_vars[$element]['rewrite'];
		} else {
			$element = $this->conf_vars[$element]['rewrite'];
		}
		?>
		<input type="textbox" name="<?php echo $option; ?>" value="<?php echo $element; ?>" >
		<?php
	}

	function handleOptionSave($theme, $album) {
		$notify = false;
		if (getOption('rewriteTokens_restore')) {
			$updated = false;
			purgeOption('rewriteTokens_restore');
			$template = file_get_contents(SERVERPATH . '/' . ZENFOLDER . '/zenphoto_cfg.txt');
			$i = strpos($template, "\$conf['special_pages']");
			$j = strpos($template, '//', $i);
			$newtext = substr($template, $i, $j - $i);
			eval($newtext);
			$this->conf_vars = $conf['special_pages'];
			foreach ($this->plugin_vars as $page => $element) {
				if (isset($element['option'])) {
					$this->plugin_vars[$page]['rewrite'] = $element['default'];
					setOption($element['option'], $element['default']);
				}
			}
		} else {
			foreach ($this->conf_vars as $page => $element) {
				$rewrite = sanitize($_POST['rewriteTokens_' . $page]);
				if (empty($rewrite)) {
					$notify = '&custom=' . gettext('Rewrite tokens may not be empty.');
				} else {
					$this->conf_vars[$page]['rewrite'] = $rewrite;
				}
				foreach ($this->plugin_vars as $page => $element) {
					if (isset($element['option'])) {
						$rewrite = sanitize($_POST['rewriteTokens_' . $page]);
//						if (empty($rewrite)) {
//							$notify = '&custom=' . gettext('Rewrite tokens may not be empty.');
//						} else {
						$this->plugin_vars[$page]['rewrite'] = $rewrite;
						setOption($element['option'], $rewrite);
//						}
					}
				}
			}
		}
		$newtext = "\$conf['special_pages'] = array(";
		foreach ($this->conf_vars as $page => $element) {
			if ($define = $element['define']) {
				$define = "'" . $define . "'";
				$desc = sprintf(gettext('Link for <em>%s</em> rule.'), $page);
			} else {
				$define = 'false';
				$desc = sprintf(gettext('Link for <em>%s</em> script page.'), $page);
			}
			if (array_key_exists('rule', $element)) {
				$rule = ",		'rule'=>'{$element['rule']}'";
			} else {
				$rule = '';
			}
			$newtext .= $token = "\n														'$page'=>			array('define'=>$define,						'rewrite'=>'{$element['rewrite']}'$rule),";
		}
		$newtext = substr($newtext, 0, -1) . "\n												);\n";
		$zp_cfg = $this->zp_cfg_a . $newtext . $this->zp_cfg_b;
		storeConfig($zp_cfg);
		return $notify;
	}

}

if ($ruleFile = getPlugin('/rewriteRules/rules.txt')) {
	rewriteRules::processRules(file_get_contents($ruleFile));
}
?>