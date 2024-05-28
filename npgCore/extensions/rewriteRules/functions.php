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
			if ($rule[0] == '#') {
				if (trim(ltrim($rule, '#')) == 'Quick links') {
					foreach ($pluginDefinitions as $def => $v) {
						if (is_numeric($def)) {
							if (end($list)[0] == 'comment') {
								array_pop($list);
							}
							$list[] = array('comment', $v, '');
							unset($definitions[$def]);
						} else {
							$list[] = array('Define ', $def, $v);
							$defined = true;
						}
					}
					if (end($list)[0] == 'comment') {
						array_pop($list);
					}
					$cleanup = function (&$item) {
						if ($item[0] == 'comment') {
							$item[0] = '';
							$item[1] = str_replace('####', '#---', $item[1]);
						}
					};
					array_walk($list, $cleanup);
				}
				if ($break && strpos($rule, '####') === 0) {
					$list[] = array('&nbsp;', '', '&nbsp;');
				}
				$list[] = array($rule, '', '&nbsp;');
				$break = true;
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
						try {
							eval('$definitions[$matches[1]] = ' . $matches[2] . ';');
						} catch (Throwable $t) {
							debugLog(sprintf(gettext('rulesList:Error evaluating define: %1$s = %2$s;'), $matches[1], $matches[2]));
							$definitions[$matches[1]] = $matches[2];
						}
						$list[] = array('Define', $matches[1], $definitions[$matches[1]]);
					}
				}
			}
		}
	}
	return $list;
}

?>