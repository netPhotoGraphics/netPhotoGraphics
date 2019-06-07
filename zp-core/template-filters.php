<?php

/*
 * isolated so that the back end knows....
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */

npgFilters::register('theme_head', 'printThemeHeadItems', 9999);
npgFilters::register('theme_body_close', 'printThemeCloseItems');
npgFilters::register('theme_body_close', 'adminToolbox');
if (TEST_RELEASE)
	npgFilters::register('software_information', 'exposeSoftwareInformation');
?>