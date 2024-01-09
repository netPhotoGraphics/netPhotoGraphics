<?php

/*
 * Snippet to handle deprecated getHashAlgorithm() parameters
 */

$index = $userdata['passhash'];
if (!array_search($index, _Authority::$hashList)) { //	default
	$index = PASSWORD_FUNCTION_DEFAULT;
}
$name = array_search($index, _Authority::$hashList);
require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');
if (!empty($userdata['pass'])) {
	deprecated_functions::deprecationMessage(sprintf(gettext('The password for user %1$s is using the deprecated %2$s hashing method.'), $userdata['user'], $name));
}