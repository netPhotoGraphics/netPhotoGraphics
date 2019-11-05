<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * @deprecated since version 1.9.3
 */
function getGeoCoord($image) {
	deprecated_functions::notify(gettext('Use GoogleMap::getGeoCoord.'));
	return GoogleMap::getGeoCoord($image);
}

/**
 * @deprecated since version 1.9.3
 */
function addGeoCoord($map, $coord) {
	deprecated_functions::notify(gettext('Use GoogleMap::addGeoCoord.'));
	return GoogleMap::addGeoCoord($map, $coord);
}
