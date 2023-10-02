<?php
/*
 * popup to display IP list for an entry
 *
 * @author Stephen Billard (sbillard)
 *
 * @Copyright 2016 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/accessThreshold
 */

require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');
admin_securityChecks(DEBUG_RIGHTS, $return = currentRelativeURL());

$recentIP = $localeList = $ipList = array();
$ip = sanitize($_GET['selected_ip']);
if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg')) {
	$recentIP = getSerializedArray(file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg'));
}

if (isset($recentIP[$ip])) {
	foreach ($recentIP[$ip]['accessed'] as $instance) {
		$ipList[] = $instance['ip'];
	}
	$ipList = array_unique($ipList);
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" />
<head>
	<?php printStandardMeta(); ?>
	<title><? echo $ip; ?></title>
	<?php scriptLoader(CORE_SERVERPATH . 'admin.css'); ?>
	<style>
		ul, ol {
			list-style: none;
			padding: 0;
		}
		li {
			margin-left: 1.5em;
			padding-bottom: 0.5em;
		}
	</style>
</head>
<body>
	<div id="main">
		<?php
		echo $ip;
		?>
		<div id="content">
			<ol>
				<?php
				foreach ($ipList as $ip) {
					echo '<li>';
					echo $ip;
					$host = gethostbyaddr($ip);
					if ($host && $host != $ip) {
						echo' (' . $host . ')';
					}
					echo '</li>';
				}
				?>
			</ol>
		</div>
	</div>
</body>




