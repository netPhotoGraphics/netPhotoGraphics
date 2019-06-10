<?php
/**
 *
 * This plugin creates two development pages. One that list the <b>netPhotoGraphics</b> <em>rewrite rules</em> and one that lists
 * <em>rewrite token</em> definitions. It provides an option interface to change the definition of
 * <em>rewrite tokens</em>. It also allows you to define custom rewrite rules via a text file.
 *
 * The <b>netPhotoGraphics</b> <em>rewrite rules</em> are shown as "active". That is the rules will
 * have had all definitions replaced with the definition value so that the rule
 * is shown in the state in which it is applied.
 *
 * The plugin will read the <em>%USER_PLUGIN_FOLDER%/rewriteRules/rules.txt</em> file if present
 * and create <b>netPhotoGraphics</b> <em>rewrite rules</em> from the text.
 * The rules.txt file consists of the following kinds of lines:
 *
 * <dl>
 * <dt>comments</dt><dd>any line beginning in a crosshatch (#)</dd>
 * <dt>definitions</dt><dd>define <code>token</code> => <code>definition</code></dd>
 * <dt>rule</dt><dd>rewriterule <em>pattern</em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<em>substitution</em>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[<em>flags</em>]</dd>
 * </dl>
 *
 * The <code>definition</code> will be evaluated by PHP before processing the rules.
 * Thus you may use <b>netPhotoGraphics</b> defines, expressions such as <code>getOption('<em>option name<em>')</code>,
 * etc. and the runtime value will be what the definition resolves to. (<b>Note:</b> the set of functions
 * available at the time rewrite rules are processed is limited. In particular, template functions and functions
 * from plugins that are not <code>CLASS</code> or <code>FEATURE</code> plugins will not be available for use.)
 *
 *
 * There are already definitions for many useful rewrite tokens. You can view these and see the standard rewrite rules
 * on the {@link %FULLWEBPATH%/%CORE_PATH%/%PLUGIN_PATH%/rewriteRules/admin_tab.php DEVELOPMENT/REWRITE} admin page.
 *
 * <em>pattern</em> is a perl compatible regular expression. The incoming path is matched against the <en>pattern</em> and if
 * there is a match, the
 * rule is executed--that is, match in the path is replaced by the <em>substitution</em> after replacing parameters with
 * the appropriate capturing group content. [<em>flags</em>] (if present) is an array of rewrite flags. E.g. <code>[L,QSA]</code>
 * provides the <b>L</b>ast and the <b>Q</b>uery<b>S</b>tring<b>A</b>ppend flags. <em>flags</em> modify the behavior of the
 * rewrite processing.
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
 * 	Define &percnt;PLUGIN_PATH&percnt;						=>	PLUGIN_PATH
 * 	<br />
 * 	define %BREAKING_NEWS%						=>	str_replace(WEBPATH.'/', '', <span class="nowrap">newCategory("Breaking-news")->getLink(1)</span>);
 *
 * </code>
 * The first Define associates the token <code>%REWRITE_RULES%</code> with the string <code>rules-list</code>
 * The second associates <code>&percnt;PLUGIN_FOLDER&percnt;</code> with the <b>netPhotoGraphics</b>
 * define <code>PLUGIN_FOLDER</code>
 * which is currently defined as <code>%PLUGIN_FOLDER%</code>. The token <code>&percnt;CORE_FOLDER&percnt;</code> used in the rules
 * has previously been defined in the standard rewrite rules as <code>%CORE_FOLDER%</code>. The third Define is an example
 * of a complex expression. In this case computing the link to the theme category page with the titlelink "Breaking-news".
 * The code strips off the WEB path since the rewrite rule redirection prepends that to the
 * link before redirecting.
 *
 * <code>
 *
 * 	#### Rewrite rule cause "rules-list" to redirect to the rewriteRules admin page
 * 	<br />
 * 	RewriteRule ^%REWRITE_RULES%/*$										&percnt;CORE_PATH&percnt;/&percnt;PLUGIN_PATH&percnt;/rewriteRules/admin_tab.php [NC,L,QSA]
 *
 * 	### Rewite rule to cause "back-end" to redirect to the admin overview page
 * 	<br />
 * 	RewriteRule ^back-end/*$													&percnt;CORE_PATH&percnt;/admin.php [NC,L,QSA]
 * 	<br /><br />
 * 	### Rewite rule to cause "contact-us" to redirect to the theme "contact" script
 * 	<br />
 * 	RewriteRule ^contact-us/*$												index.php?p=contact [NC,L,QSA]
 * 	<br /><br />
 * 	### Rewite rule to cause "breaking-news" to redirect to the theme "Breaking-news" category page.
 * 	<br />
 * 	RewriteRule ^breaking-news/*$											%BREAKING_NEWS% [NC]	#Note: the pattern match is case insensitive
 * 	<br /><br />
 * 	### Rewite rule to cause redirect the "register-me" link as permanantly moved (R=301) to the theme registration page
 * 	<br />
 * 	RewriteRule ^register-me/*$												%REGISTER_USER% [R=301]
 * 	<br /><br />
 * 	### Rewite rules to cause any link in the "iambad" tree to be rejected as forbidden
 * 	<br />
 *  ### the dash("-") <em>substitution</em> indicates that no substitution should be performed
 * 	<br />
 * 	### (the existing path is passed through untouched)
 * 	<br />
 * 	RewriteRule ^iambad/(.*)/*$												- [F]
 * 	<br />
 * 	RewriteRule ^iambad/*$														- [F]
 * </code>
 *
 *
 * <b>netPhotoGraphics</b> rules processing follows a simplified version of the
 * {@link https://httpd.apache.org/docs/2.2/mod/mod_rewrite.html#RewriteRule Apache RewriteRule Directive}.
 * The chain ("C"), forbidden (F), gone (G), last ("L"), no case (NC),
 * query string append ("QSA"), query string discard (QSD), redirect ("R"), and skip("S")
 * <em>flags</em> are honored. All other <em>flags</em> are ignored. If the <em>substitution path</em>
 * is not to "index.php" or the "R" <em>flag</em> is present, rules processing will force a page
 * reload to the <em>substitution path</em>. Redirection away from the <b>netPhotoGraphics</b> site is not supported.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/rewriteRules
 * @pluginCategory admin
 *
 * @Copyright 2019 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */
$plugin_is_filter = 1 | FEATURE_PLUGIN;
$plugin_description = gettext("Site rewrite rules.");
$plugin_notice = (($ruleFile = getPlugin('/rewriteRules/rules.txt')) && !extensionEnabled('rewriteRules')) ? gettext('The <em>rules.txt</em> file is not processed when the <em>rewriteRules</em> plugin is disabled.') : '';

$option_interface = 'rewriteRules';

if ($ruleFile) {
	rewriteRules::processRules(file_get_contents($ruleFile));
}

npgFilters::register('admin_tabs', 'rewriteRules::tabs', 100);


require_once(CORE_SERVERPATH . 'lib-config.php');

class rewriteRules {

	private $_config_contents_a;
	private $_config_contents_b;
	private $conf_vars = array();
	private $plugin_vars = array();

	function __construct() {
		global $_configMutex, $_conf_vars;

		if (OFFSET_PATH == 2 && getPlugin('/rewriteRules/rules.txt')) {
			enableExtension('rewriteRules', 1 | FEATURE_PLUGIN);
		}

		$_configMutex->lock();
		$_config_contents = file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);
		$i = strpos($_config_contents, "\$conf['special_pages']");
		$j = strpos($_config_contents, '//', $i);
		if ($i === false || $j === false) {
			$this->conf_vars = $_conf_vars['special_pages'];
			$i = strpos($_config_contents, '/** Do not edit below this line. **/');
			if ($i === false) {
				trigger_error(gettext('The Configuration file is corrupt. You will need to restore it from a backup.'), E_USER_ERROR);
			}
			$this->_cfg_a = substr($_config_contents, 0, $i);
			$this->_cfg_b = "//\n" . substr($_config_contents, $i);
		} else {
			$this->_cfg_a = substr($_config_contents, 0, $i);
			$this->_cfg_b = substr($_config_contents, $j);
			eval(substr($_config_contents, $i, $j - $i));
			$this->conf_vars = $conf['special_pages'];
			foreach ($_conf_vars['special_pages'] as $page => $element) {
				if (isset($element['option'])) {
					$this->plugin_vars[$page] = $element;
				}
			}
		}

		if (OFFSET_PATH == 2) {
			$old = array_keys($conf['special_pages']);
			$_config_contents = file_get_contents(CORE_SERVERPATH . 'netPhotoGraphics_cfg.txt');
			$i = strpos($_config_contents, "\$conf['special_pages']");
			$j = strpos($_config_contents, '//', $i);
			eval(substr($_config_contents, $i, $j - $i));
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
			$template = file_get_contents(CORE_SERVERPATH . 'netPhotoGraphics_cfg.txt');
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
						$this->plugin_vars[$page]['rewrite'] = $rewrite;
						setOption($element['option'], $rewrite);
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
		$_config_contents = $this->_cfg_a . $newtext . $this->_cfg_b;
		configFile::store($_config_contents);
		return $notify;
	}

	static function tabs($tabs) {
		if (npg_loggedin(ADMIN_RIGHTS)) {
			if (!isset($tabs['development'])) {
				$tabs['development'] = array('text' => gettext("development"),
						'link' => getAdminLink(PLUGIN_FOLDER . '/rewriteRules/rules_tab.php') . '?page=development&tab=rewrite',
						'default' => "rewrite",
						'subtabs' => NULL);
			}
			$tabs['development']['subtabs'][gettext("rewrite")] = PLUGIN_FOLDER . '/rewriteRules/rules_tab.php?page=development&tab=rewrite';
			$tabs['development']['subtabs'][gettext("tokens")] = PLUGIN_FOLDER . '/rewriteRules/tokens_tab.php?page=development&tab=tokens';
		}
		return $tabs;
	}

	static function processRules($ruleFile) {
		global $_conf_vars;
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
		$_conf_vars['special_pages'] = array_merge($_conf_vars['special_pages'], $definitions, $rules);
	}

}

function rewriteRules_enable($enabled) {
	if (!$enabled && getPlugin('/rewriteRules/rules.txt')) {
		requestSetup('rewriteRules', gettext('The <em>rules.txt</em> file is not processed when the <em>rewriteRules</em> plugin is disabled.'));
	}
}
?>