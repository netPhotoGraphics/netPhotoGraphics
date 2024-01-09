<?php

/*
 * Snippet to handle deprecated sortMultiArray() parameters
 */

$args = '';
$field = array_flip($field);
if ($nat) {
	$direction = 'true, ';
} else {
	$direction = 'false, ';
}
foreach ($field as $key => $v) {
	$field[$key] = $nat;
	$args .= "'" . $key . "' => " . $direction;
}
$nat = $case;
$case = $preserveKeys;
$preserveKeys = $removeCriteria;
$removeCriteria = $dummy;

$args = rtrim(trim($args), ',');
$call = 'sortMultiArray($arrayToBeSorted, [' . $args . ']';
if (!$nat || $case) {
	$call .= ", false"; //	$nat
}
if ($case || !$preserveKeys) {
	$call .= ', true'; //	$case
}
if (!$preserveKeys || !empty($removeCriteria)) {
	$call .= ', true'; //	$preserveKeys
}
if (!empty($removeCriteria)) {
	$call .= ', [' . implode(',', $removeCriteria) . ']'; //	$removeCriteria
}
$call .= ');';
require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php');
deprecated_functions::notify_call('sortMultiArray', gettext('The function should be called with a $field array.') . sprintf(gettext(' e.g. %1$s '), $call));
