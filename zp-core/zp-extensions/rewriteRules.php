<?php

/*
 *
 * This plugin creates an development page that lists the netPhotoGraphics rewrite rules as "active". That is the rules will
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
 * etc. and the runtime value will be what the definition resolves to.
 * There are already definitions for many useful rewrite tokens. You can view these
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
 *
 * 	#### Rewrite rule cause "rules-list" to redirect to the rewriteRules admin page
 * 	<br />
 * 	RewriteRule ^%REWRITE_RULES%/*$										&percnt;ZENFOLDER&percnt;/&percnt;PLUGIN_FOLDER&percnt;/rewriteRules/admin_tab.php [L,QSA]
 *
 * 	### Rewite rule to cause "back-end" to redirect to the admin overview page
 * 	<br />
 * 	RewriteRule ^back-end/*$													&percnt;ZENFOLDER&percnt;/admin.php [L,QSA]
 * </code>
 *
 * The first Define associates the token <code>%REWRITE_RULES%</code> with the string <code>rules-list</code>
 * The second associates <code>&percnt;PLUGIN_FOLDER&percnt;</code> with the netPhotoGraphics define <code>PLUGIN_FOLDER</code>
 * which is currently defined as <code>%PLUGIN_FOLDER%</code>. The token <code>&percnt;ZENFOLDER&percnt;</code> used in the rules
 * has previously been defined in the standard rewrite rules as <code>%ZENFOLDER%</code>.
 *
 * netPhotoGraphics rules processing follows a simplified version of the
 * {@link https://httpd.apache.org/docs/2.2/mod/mod_rewrite.html#RewriteRule Apache RewriteRule Directive}.
 * At this time the netPhotoGraphics rewrite rules only handle full URL
 * rewrite.  Thus the rewrite token must be proceded by a caret(<code>^</code>) and consume the full
 * URL (the <code>/*$</code> termination.) The "L" <em>flag</em> is implied (i.e. processing
 * stops when a match is made.) The redirect ("R") and query string append ("QSA")
 * <em>flags</em> are honored. All other <em>flags</em> are ignored. If the <em>substitution path</em>
 * is not to "index.php" or the "R" <em>flag</em> is present, rules processing will force a page
 * reload to the <em>substitution path<em>.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/rewriteRules
 * @pluginCategory admin
 *
 * @Copyright 2019 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics and derivatives}
 */

$plugin_is_filter = 5 | FEATURE_PLUGIN;
$plugin_description = gettext("Site rewrite rules.");

zp_register_filter('admin_tabs', 'rewriteRules::tabs', 100);

class rewriteRules {

	static function tabs($tabs) {
		if (zp_loggedin(ADMIN_RIGHTS)) {
			if (!isset($tabs['development'])) {
				$tabs['development'] = array('text' => gettext("development"),
						'link' => WEBPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/rewriteRules/admin_tab.php?page=development&tab=rewrite',
						'default' => "rewrite",
						'subtabs' => NULL);
			}
			$tabs['development']['subtabs'][gettext("rewrite")] = PLUGIN_FOLDER . '/rewriteRules/admin_tab.php?page=development&tab=rewrite';
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

if ($ruleFile = getPlugin('/rewriteRules/rules.txt')) {
	rewriteRules::processRules(file_get_contents($ruleFile));
}
?>