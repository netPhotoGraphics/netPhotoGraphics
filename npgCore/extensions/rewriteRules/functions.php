<?php

/**
 * @package plugins/rewriteRules
 */
function rulesList() {
	list($pluginDefinitions, $rules) = getRules();
	$definitions = $pluginDefinitions;
	$list = array();
	$break = false;
	//process the rules
	foreach ($rules as $rule) {
		if ($rule = trim($rule)) {
			if ($rule{0} == '#') {
				if (trim(ltrim($rule, '#')) == 'Quick links') {
					foreach ($pluginDefinitions as $def => $v) {
						$list[] = array('Define ', $def, $v);
					}
				}
				if ($break) {
					$list[] = $break;
				} else {
					$break = array('&nbsp;', '', '&nbsp;');
				}
				$list[] = array($rule, '', '&nbsp;');
			} else {
				if (preg_match('~^rewriterule~i', $rule)) {
					// it is a rewrite rule, see if it is applicable
					$rule = strtr($rule, $definitions);
					preg_match('~^rewriterule\s+(.*?)\s+(.*?)\s*(\[.*?\])\s.*$~i', $rule . ' [Z] ', $matches);
					if (array_key_exists(1, $matches)) {
						if ($matches[3] == '[Z]') {
							$matches[3] = '';
						} else {
							$matches[3] = str_replace(' ', '', $matches[3]);
						}
						$list[] = array('rewriterule', $matches[1], $matches[2] . ' ' . $matches[3]);
					} else {
						$list[] = array(gettext('Error processing rewrite rule:'), '', $rule);
					}
				} else {
					if (preg_match('~define\s+(.*?)\s*\=\>\s*(.*)$~i', $rule, $matches)) {
						//	store definitions
						eval('$definitions[$matches[1]] = ' . $matches[2] . ';');
						$list[] = array('Define', $matches[1], $definitions[$matches[1]]);
					}
				}
			}
		}
	}
	return $list;
}

?>