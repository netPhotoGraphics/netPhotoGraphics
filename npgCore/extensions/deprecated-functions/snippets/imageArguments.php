<?php

/*
 * Snippet to handle deprecated getCustomImage() parameters
 */

$args = array();
foreach ($p as $k => $v) {
	$args[$a[$k]] = $v;
}
if (isset($args['image'])) {
	$image = $args['image'];
	unset($args['image']);
} else {
	$image = NULL;
}
if (isset($args['suffix'])) {
	$suffix = $args['suffix'];
	unset($args['suffix']);
} else {
	$suffix = NULL;
}
if (array_key_exists('class', $args)) {
	$class = $args['class'];
	unset($args['class']);
	if (is_null($class)) {
		$class = false;
	}
} else {
	$class = false;
}
if (array_key_exists('id', $args)) {
	$id = $args['id'];
	unset($args['id']);
} else {
	$id = NULL;
}
if (array_key_exists('title', $args)) {
	$title = $args['title'];
	unset($args['title']);
} else {
	$title = NULL;
}

require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');
deprecated_functions::notify_call($whom, gettext('The function should be called with an image arguments array.') . sprintf(gettext(' e.g. %1$s '), npgFunctions::array_arg_example($args)));
