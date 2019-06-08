<?php
// force UTF-8  Ø
require_once(dirname(__FILE__) . '/functions.php');

function http_response_text($code) {
	switch ($code) {

		case 400:
			$text = gettext('Bad Request');
			break;
		case 401:
			$text = gettext('Unauthorized');
			break;
		case 402:
			$text = gettext('Payment Required');
			break;
		case 403:
			$text = gettext('Forbidden');
			break;
		case 404:
			$text = gettext('Not Found');
			break;
		case 405:
			$text = gettext('Method Not Allowed');
			break;
		case 406:
			$text = gettext('Not Acceptable');
			break;
		case 407:
			$text = gettext('Proxy Authentication Required');
			break;
		case 408:
			$text = gettext('Request Time-out');
			break;
		case 409:
			$text = gettext('Conflict');
			break;
		case 410:
			$text = gettext('Gone');
			break;
		case 411:
			$text = gettext('Length Required');
			break;
		case 412:
			$text = gettext('Precondition Failed');
			break;
		case 413:
			$text = gettext('Request Entity Too Large');
			break;
		case 414:
			$text = gettext('Request-URI Too Large');
			break;
		case 415:
			$text = gettext('Unsupported Media Type');
			break;
		default:
			$text = $code;
			break;
	}
	return $text;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/2002/REC-xhtml1-20020801/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<style>
			body {
				padding: 50px;
			}
			div {
				margin: auto;
				text-align: center;
				width: 50%;
				background-color:rgb(255,255,215);
				border: 1px solid black;
			}
			p {
				text-align: center;
			}
			.large {
				font-size: x-large;
			}
		</style>
	</head>
	<body>
		<div>
			<p class="large">
				<?php echo gettext('Something went wrong'); ?>
			</p>
			<p >
				<img src="<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/images/err-broken-page.png" />
			</p>
			<p>
				<?php printf(gettext('URL: %1$s'), getRequestURI()); ?>
			</p>
			<p>
				<?php printf(gettext('HTTP status: %1$s'), http_response_text($_GET['code'])); ?>
			</p>
		</div>
	</body>
</html>
