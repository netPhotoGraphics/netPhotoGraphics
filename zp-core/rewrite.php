<?php

/**
 * "Rewrite" handling for zenphoto
 *
 * The basic rules are found in the zenphoto-rewrite.txt file. Additional rules can be provided by plugins. But
 * for the plugin to load in time for the rules to be seen it must be either a CLASS_PLUGIN or a FEATURE_PLUGIN.
 * Plugins add rules by inserting them into the $_zp_conf_vars['special_pages'] array. Each "rule" is an array
 * of three elements: <var>define</var>, <var>rewrite</var>, and (optionally) <var>rule</rule>.
 *
 * Elemments which have a <var>define</var> and no <var>rule</rule> are processed by rewrite rules in the
 * zenphoto-rewrite.txt file and the <var>define</var> is used internally to netPhotoGraphics to reference
 * the rewrite text when building links.
 *
 * Elements with a <var>rule</rule> defined are processed after Search, Pages, and News rewrite rules and before
 * Image and album rewrite rules. The tag %REWRITE% in the rule is replaced with the <var>rewrite</var> text
 * before processing the rule. Thus <var>rewrite</var> is the token that should appear in the actual URL.
 *
 * It makes no sense to have an element without either a <var>define</var> or a <var>rule</rule> as nothing will happen.
 *
 * At present all rules are presumed to to stop processing the rule set. Historically that is what all our rules have done.
 *
 * If the target of the rewrite is index.php, no redirection will occur unless the "R" flag is set. Index.php is executed
 * in the normal loading sequence. The "R" flag may be used to cause a redirection with a status <var>header</var> even if
 * index.php is the target of the redirect.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// add "standard" (non-plugin dependent) rewrite rules here
//	setup definitions for the "gallery" page link
$_zp_conf_vars['special_pages']['gallery'] = array(
		'define' => '_GALLERY_PAGE_', //	The netPhotoGraphics "define" for the link token
		'option' => 'galleryToken_link', //	the name of the option for storing the link token
		'default' => '_PAGE_/gallery', //	The default (initial value) of the link token
		'rewrite' => getOption('galleryToken_link') //	this will be "evaled" to yield the current link token for "gallery"
);
//	add the rewrite definition of the rewrite target
$_zp_conf_vars['special_pages'][] = array(
		'definition' => '%GALLERY_PAGE%', //	the "reference" for the target in rewrite rules
		'rewrite' => '_GALLERY_PAGE_' //	the value that will be substituted for the above reference
);
//	the next two entries are rewrite rules for the "gallery" page
//	If the option for 'galleryToken_link' is "albumindex" then these will produce the rules
//		rewriterule ^albumindex/([0-9]+)/*$  index.php?p=gallery&page=$1 [L,QSA]
//		rewriterule ^albumindex/*$  index.php?p=gallery [L,QSA]
$_zp_conf_vars['special_pages'][] = array(
		'rewrite' => '%GALLERY_PAGE%/([0-9]+)',
		'rule' => '^%REWRITE%/*$		index.php?p=gallery&page=$1' . ' [L,QSA]'
);
$_zp_conf_vars['special_pages'][] = array(
		'rewrite' => '%GALLERY_PAGE%',
		'rule' => '^%REWRITE%/*$		index.php?p=gallery [L,QSA]'
);

/**
 * applies the rewrite rules
 * @global type $_zp_conf_vars
 * @global type $_zp_rewritten
 */
function rewriteHandler() {
	global $_zp_conf_vars, $_zp_rewritten;
	$_zp_rewritten = false;
	$definitions = array();

	//	query parameters should already be loaded into the $_GET and $_REQUEST arrays, so we discard them here
	$request = explode('?', getRequestURI());
	//rewrite base
	$requesturi = ltrim(substr($request[0], strlen(WEBPATH)), '/');
	list($definitions, $rules) = getRules();

	//process the rules
	foreach ($rules as $rule) {
		if ($rule = trim($rule)) {
			if ($rule{0} != '#') {
				if (preg_match('~^rewriterule~i', $rule)) {
					// it is a rewrite rule, see if it is applicable
					$rule = strtr($rule, $definitions);
					preg_match('~^rewriterule\s+(.*?)\s+(.*?)\s*\[(.*)\]$~i', $rule, $matches);
					if (array_key_exists(1, $matches)) {
						if (preg_match('~' . $matches[1] . '~', $requesturi, $subs)) {
							$params = array();
							//	setup the rule replacement values
							foreach ($subs as $key => $sub) {
								$params['$' . $key] = urlencode($sub); // parse_str is going to decode the string!
							}
							//	parse rewrite rule flags
							$flags = array();
							$banner = explode(',', strtoupper($matches[3]));
							foreach ($banner as $flag) {
								$flag = strtoupper(trim($flag));
								$f = explode('=', $flag);
								$flags[trim($f[0])] = isset($f[1]) ? trim($f[1]) : NULL;
							}

							if (!array_key_exists('QSA', $flags)) {
								//	QSA means merge the query parameters. Otherwise we clear them
								$_REQUEST = array_diff($_REQUEST, $_GET);
								$_GET = array();
							}
							preg_match('~(.*?)\?(.*)~', $matches[2], $action);
							if (empty($action)) {
								$action[1] = $matches[2];
							}
							if (array_key_exists(2, $action)) {
								//	process the rules replacements
								$query = strtr($action[2], $params);
								parse_str($query, $gets);
								$_GET = array_merge($_GET, $gets);
								$_REQUEST = array_merge($_REQUEST, $gets);
							}
							//	we will execute the index.php script in due course. But if the rule
							//	action takes us elsewhere we will have to re-direct to that script.
							if (array_key_exists('R', $flags) || isset($action[1]) && $action[1] != 'index.php') {
								$qs = http_build_query($_GET);
								if ($qs) {
									$qs = '?' . $qs;
								}
								if (array_key_exists('R', $flags)) {
									header('Status: ' . $flags['R']);
								}
								header('Location: ' . WEBPATH . '/' . $action[1] . $qs);
								exit();
							}
							$_zp_rewritten = true;
							break;
						}
					} else {
						trigger_error(sprintf(gettext('Error processing rewrite rule: “%s”'), trim(preg_replace('~^rewriterule~i', '', $rule))), E_USER_WARNING);
					}
				} else {
					if (preg_match('~define\s+(.*?)\s*\=\>\s*(.*)$~i', $rule, $matches)) {
						//	store definitions
						eval('$definitions[$matches[1]] = ' . $matches[2] . ';');
					}
				}
			}
		}
	}
}

/**
 * loads the rewrite rules
 * @global type $_zp_conf_vars
 * @return type
 */
function getRules() {
	global $_zp_conf_vars;
	//	load rewrite rules
	$rules = trim(file_get_contents(SERVERPATH . '/' . ZENFOLDER . '/zenphoto-rewrite.txt'));

	$definitions = $specialPageRules = array();

	foreach ($_zp_conf_vars['special_pages'] as $key => $special) {
		if (array_key_exists('definition', $special)) {
			eval('$v = ' . $special['rewrite'] . ';');
			if (empty($v)) {
				break;
			}
			$definitions[$special['definition']] = $v;
		}
		if (array_key_exists('rule', $special)) {
			$specialPageRules[$key] = "\tRewriteRule " . str_replace('%REWRITE%', $special['rewrite'], $special['rule']);
		}
	}

	$rules = explode("_SPECIAL_", trim($rules));
	$rules = array_merge(explode("\n", $rules[0]), $specialPageRules, explode("\n", $rules[1]), array("\t#### Catch-all", "\t" . 'RewriteRule ^(.*?)/*$	index.php?album=$1 [L,QSA]'));
	return array($definitions, $rules);
}

$_definitions = array();
if (isset($_zp_conf_vars['special_pages'])) {
	foreach ($_zp_conf_vars['special_pages'] as $definition) {
		if (isset($definition['define']) && $definition['define']) {
			define($definition['define'], strtr($definition['rewrite'], $_definitions));
			eval('$_definitions[$definition[\'define\']]=' . $definition['define'] . ';');
		}
	}
}
unset($definition);
unset($_definitions);
?>