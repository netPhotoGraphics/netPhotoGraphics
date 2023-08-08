<?php

/**
 * Adapted from DiscusDesk at {@link http://www.discussdesk.com/login-with-twitter-oauth-api-using-php.htm}
 * by Stephen Billard
 *
 * @author Stephen Billard (sbillard)
 * @Copyright 2017 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/twitterLogin
 */
if (!defined('OFFSET_PATH')) {
	define('OFFSET_PATH', 3);
}
require_once(file_get_contents(dirname(dirname($_SERVER['SCRIPT_FILENAME'])) . '/core-locator.npg') . "admin-globals.php");
npg_session_start();

define('CONSUMER_KEY', getOption('tweet_news_consumer')); // YOUR CONSUMER KEY
define('CONSUMER_SECRET', getOption('tweet_news_consumer_secret')); //YOUR CONSUMER SECRET KEY
define('OAUTH_CALLBACK', getAdminLink(PLUGIN_FOLDER . '/twitterLogin/twitter.php')); // Redirect URL


if (isset($_REQUEST['redirect'])) {
	$_SESSION['redirect'] = filter_var($_REQUEST['redirect'], FILTER_SANITIZE_URL);
} else {
	if (!isset($_SESSION['redirect'])) {
		$_SESSION['redirect'] = FULLWEBPATH;
	}
}


// Include twitter PHP Library
require_once(CORE_SERVERPATH . PLUGIN_FOLDER . "/common/oAuth/twitteroauth.php");
$error = '';

if (isset($_GET['request'])) {
	//Fresh authentication
	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET);

	//Any value other than 200 is failure, so continue only if http code is 200
	if ($connection->http_code == '200') {

		$request_token = $connection->getRequestToken(OAUTH_CALLBACK);

		//Received token info from twitter
		$_SESSION['token'] = $request_token['oauth_token'];
		$_SESSION['token_secret'] = $request_token['oauth_token_secret'];

		//redirect user to twitter
		$twitter_url = $connection->getAuthorizeURL($request_token['oauth_token']);
		header('Location: ' . $twitter_url);
	} else {
		$error = gettext("error connecting to twitter! try again later!");
	}
}

if (isset($_REQUEST['oauth_token']) && $_SESSION['token'] == $_REQUEST['oauth_token']) {

	$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, $_SESSION['token'], $_SESSION['token_secret']);
	$access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);

	if ($connection->http_code == '200') {

		$user_data = $connection->get('account/verify_credentials');

		twitterLogin::credentials($user_data['id'], isset(user_data['email']) ? $user_data['email'] : NULL, $user_data['name'], $_SESSION['redirect']);
	}
}
if (empty($error)) {
	$error = gettext('Twitter authorization failed.');
}

session_unset();
header('Location: ' . getAdminLink('admin.php') . '?_login_error=' . html_encode($error));
exit();
?>
