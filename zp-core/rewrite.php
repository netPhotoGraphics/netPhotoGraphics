<?php

/**
 * "Rewrite" handling
 *
 * The basic rules are found in the rewrite.txt file. Additional rules can be provided by plugins. But
 * for the plugin to load in time for the rules to be seen it must be either a CLASS_PLUGIN or a FEATURE_PLUGIN.
 * Plugins add rules by inserting them into the $_conf_vars['special_pages'] array. Each "rule" is an array
 * of three elements: <var>define</var>, <var>rewrite</var>, and (optionally) <var>rule</rule>.
 *
 * Elemments which have a <var>define</var> and no <var>rule</rule> are processed by rewrite rules in the
 * rewrite.txt file and the <var>define</var> is used internally to netPhotoGraphics to reference
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
$rules['gallery'] = array(
		'define' => '_GALLERY_PAGE_', //	The netPhotoGraphics "define" for the link token
		'option' => 'galleryToken_link', //	the name of the option for storing the link token
		'default' => '_PAGE_/gallery', //	The default (initial value) of the link token
		'rewrite' => getOption('galleryToken_link') //	this will be "evaled" to yield the current link token for "gallery"
);

//	add the rewrite definition of the rewrite target
$rules[] = array(
		'definition' => '%GALLERY_PAGE%', //	the "reference" for the target in rewrite rules
		'rewrite' => '_GALLERY_PAGE_' //	the value that will be substituted for the above reference
);
$rules[] = array('comment' => "\t#### Rules from rewrite.php");
//	the next two entries are rewrite rules for the "gallery" page
//	If the option for 'galleryToken_link' is "albumindex" then these will produce the rules
//		rewriterule ^albumindex/([0-9]+)/*$  index.php?p=gallery&page=$1 [NC,L,QSA]
//		rewriterule ^albumindex/*$  index.php?p=gallery [NC,L,QSA]
$rules[] = array(
		'rewrite' => '%GALLERY_PAGE%/([0-9]+)',
		'rule' => '^%REWRITE%/*$		index.php?p=gallery&page=$1' . ' [NC,L,QSA]'
);
$rules[] = array(
		'rewrite' => '%GALLERY_PAGE%',
		'rule' => '^%REWRITE%/*$		index.php?p=gallery [NC,L,QSA]'
);
$rules[] = array('comment' => "\t#### Rules from \"plugins\"");
$primary = array_slice($_conf_vars['special_pages'], 0, 4, true);
$secondary = array_slice($_conf_vars['special_pages'], 4, NULL, true);

$_conf_vars['special_pages'] = array_merge($primary, $rules, $secondary);
unset($rules);
unset($primary);
unset($secondary);

/**
 * applies the rewrite rules
 * @global type $_conf_vars
 * @global type $_rewritten
 */
function rewriteHandler() {
	global $_conf_vars, $_rewritten;
	$_rewritten = false;
	$definitions = array();
	//	query parameters should already be loaded into the $_GET and $_REQUEST arrays, so we discard them here
	$request = explode('?', getRequestURI());
	//rewrite base
	$requesturi = ltrim(substr($request[0], strlen(WEBPATH)), '/');
	list($definitions, $rules) = getRules();
	$skip = 0;
	//process the rules
	foreach ($rules as $rule) {
		$rule = trim($rule);
		if (!empty($rule) && $rule{0} != '#') {
			if (preg_match('~^rewriterule~i', $rule)) {
				if (array_key_exists(1, $matches)) {
					// it is a rewrite rule, see if it is applicable
					if ($skip) {
						$skip--;
						continue;
					}
					$rule = strtr($rule, $definitions);
					preg_match('~^rewriterule\s+(.*?)\s+(.*?)\s*(\[.*?\])\s.*$~i', $rule . ' [Z] ', $matches);
					if (array_key_exists(1, $matches)) {
						//	parse rewrite rule flags
						$flags = array();
						if (isset($matches[3])) {
							$banner = explode(',', trim($matches[3], '[]'));
							foreach ($banner as $flag) {
								$f = explode('=', trim($flag));
								$flags[strtoupper(trim($f[0]))] = isset($f[1]) ? trim($f[1]) : NULL;
							}
						}
						if (array_key_exists('NC', $flags)) { //	nonor the NC flag
							$i = 'i';
						} else {
							$i = '';
						}
						if (preg_match('~' . $matches[1] . '~' . $i, $requesturi, $subs)) {
							//	it is a match
							if (array_key_exists('S', $flags)) {
								$skip = $flags['S'];
							}
							if ($matches[2] == '-') {
								$substitution = $subs[0];
							} else {
								$substitution = preg_replace('~' . $matches[1] . '~' . $i, $matches[2], $requesturi);
							}
							preg_match('~(.*?)\?(.*)~', $substitution, $action);
							if (empty($action)) {
								$action[1] = $substitution;
							}
							if (array_key_exists(2, $action)) {
								//	process the rules replacements
								parse_str($action[2], $gets);
							} else {
								$gets = array();
							}
							//	handle query string(s)
							if (array_key_exists('QSD', $flags) || (!empty($gets) && !array_key_exists('QSA', $flags))) {
								//	clear the query parameters.
								$_REQUEST = array_diff($_REQUEST, $_GET);
								$_GET = array();
							}
							$_GET = array_merge($_GET, $gets);
							$_REQUEST = array_merge($_REQUEST, $_GET);
							if (array_key_exists('G', $flags)) {
								$flags['R'] = 410;
							}
							if (array_key_exists('F', $flags)) {
								$flags['R'] = 403;
							}
							if (array_key_exists('R', $flags) || array_key_exists('L', $flags)) {
								//	we will execute the index.php script in due course. But if the rule
								//	action takes us elsewhere we will have to re-direct to that script.
								if (array_key_exists('R', $flags) || isset($action[1]) && $action[1] != 'index.php') {
									if (isset($flags['R']) && $flags['R'] >= 400) {
										//	redirect to the npg error page because the http response code gets lost in
										//	the .htaccess redirection process
										$_GET = array(
												'code' => $flags['R'],
												'z' => '',
												'p' => 'page_error'
										);
									} else {
										$qs = http_build_query($_GET);
										if ($qs) {
											$qs = '?' . $qs;
										}
										if (isset($flags['R'])) {
											header('Status: ' . $flags['R']);
										}
										header('Location: ' . WEBPATH . '/' . $action[1] . $qs);
										exit();
									}
								}
								$_rewritten = true;
								//	fall through to index.php
								break;
							}
							$requesturi = $action[1];
						} else {
							$skip = (int) array_key_exists('C', $flags);
						}
					} else {
						trigger_error(sprintf(gettext('Error processing rewrite rule: “%s”'), $rule), E_USER_WARNING);
					}
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

/**
 * loads the rewrite rules
 * @global type $_conf_vars
 * @return type
 */
function getRules() {
	global $_conf_vars;
	//	load rewrite rules
	$rules = trim(file_get_contents(CORE_SERVERPATH . 'rewrite.txt'));
	$definitions = $specialPageRules = array();
	foreach ($_conf_vars['special_pages'] as $key => $special) {
		if (array_key_exists('definition', $special)) {
			try {
				eval('$v = ' . $special['rewrite'] . ';');
			} catch (Throwable $t) {
				$v = '*undefined*';
			}
			$definitions[$special['definition']] = $v;
		}
		if (array_key_exists('rule', $special)) {
			$specialPageRules[$key] = "\tRewriteRule " . str_replace('%REWRITE%', $special['rewrite'], $special['rule']);
		}
		if (array_key_exists('comment', $special)) {
			$specialPageRules[$key] = $special['comment'];
		}
	}
	$rules = explode("_SPECIAL_", trim($rules));
	$rules = array_merge(explode("\n", $rules[0]), $specialPageRules, explode("\n", $rules[1]), array("\t#### Catch-all", "\t" . 'RewriteRule ^(.*?)/*$	index.php?album=$1 [NC,L,QSA]'));
	return array($definitions, $rules);
}

$_definitions = array();
if (isset($_conf_vars['special_pages'])) {
	foreach ($_conf_vars['special_pages'] as $definition) {
		if (isset($definition['define']) && $definition['define']) {
			define($definition['define'], strtr($definition['rewrite'], $_definitions));
			eval('$_definitions[$definition[\'define\']]=' . $definition['define'] . ';');
		}
	}
}
unset($definition);
unset($_definitions);
?>