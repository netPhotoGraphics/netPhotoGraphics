<?php
$me = basename(dirname(__FILE__));

// Theme definition file
$theme_description['name'] = 'zpBootstrap';
$theme_description['author'] = 'Vincent3569';
$theme_description['version'] = '2.1';
$theme_description['date'] = '21/09/2017';
$theme_description['desc'] =
gettext('A responsive theme ready for desktop to mobile device, using <a href="http://getbootstrap.com/" title="Boostrap" target="_blank">Boostrap 3.3.7</a> framework.', $me) . '<br />' .
gettext('Please read', $me) . ' <a href="' . FULLWEBPATH . '/' . THEMEFOLDER . '/' . $me . '/readme.htm' . '" title="Readme File" target="_blank">Readme</a>, <a href="' . FULLWEBPATH . '/' . THEMEFOLDER . '/' . $me . '/license.htm' . '" title="License File" target="_blank">License</a> ' . gettext('and', $me) . ' <a href="' . FULLWEBPATH . '/' . THEMEFOLDER . '/' . $me . '/changelog.txt' . '" title="ChangeLog File" target="_blank">ChangeLog</a><br />' .
gettext('To design your content to be responsive using Boostrap framework, you can have a look on', $me). ' <a href="' . FULLWEBPATH . '/' . THEMEFOLDER . '/' . $me . '/bootstrap_grid_system.htm' . '" title="Bootstrap Grid System" target="_blank">' . gettext('Bootstrap Grid System', $me) . '</a>';
?>