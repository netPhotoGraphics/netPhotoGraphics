<?php

/*
 * CURL support
 *
 * NOTE: it is presumed that these routines are used for launching requests upon
 * the local site netPhotoGraphic scripts
 */

/**
 * Sends a simple cURL request to the $uri specified.
 *
 * @param string $uri The uri to send the request to. Sets `curl_setopt($ch, CURLOPT_URL, $uri);`
 * @param array $options An array of cURL options to set (uri is set via the separate parameter)
 * Default is if nothing is set:
 * 	array(
 * 		CURLOPT_RETURNTRANSFER => true,
 * 		CURLOPT_TIMEOUT => 2000
 * )
 * See http://php.net/manual/en/function.curl-setopt.php for more info
 * @return boolean
 */
function curlRequest($uri, $options = array()) {
	if (function_exists('curl_init')) {
		if (empty($options) || !is_array($options)) {
			$cookies = 'user_auth=' . getNPGCookie('user_auth');
			$options = array(
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_TIMEOUT => 2000,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_MAXREDIRS => 3,
					CURLOPT_COOKIE => $cookies
			);
		}
		$ch = curl_init($uri);
		curl_setopt_array($ch, $options);
		$curl_exec = curl_exec($ch);
		if (curl_errno($ch)) {
			throw new Exception(sprintf(gettext('Curl returned error #%1$s'), curl_errno($ch)));
		} else {
			$result = trim($curl_exec);
		}
		curl_close($ch);
		return $result;
	}
	throw new Exception(gettext('ERROR: Your server does not support cURL.'));
}

/**
 * Async cURL Requests
 *
 * Adapted from Programster's Blog (https://blog.programster.org/php-async-curl-requests)
 *
 * @Copyright Stephen L Billard
 * permission granted for use in conjunction with netPhotoGraphics. All other rights reserved
 *
 */
class ParallelCURL {

	var $res = array();

	function __construct($urls) {
		// Create get requests for each URL
		$mh = curl_multi_init();
		$options = array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_COOKIE => 'user_auth=' . getNPGCookie('user_auth')
		);

		foreach ($urls as $i => $url) {
			$ch[$i] = curl_init($url);
			curl_setopt_array($ch[$i], $options);
			curl_multi_add_handle($mh, $ch[$i]);
		}

		// Start performing the request
		do {
			$execReturnValue = curl_multi_exec($mh, $runningHandles);
		} while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);

		// Loop and continue processing the request
		while ($runningHandles && $execReturnValue == CURLM_OK) {
			if (curl_multi_select($mh) != -1) {
				usleep(100);
			}

			do {
				$execReturnValue = curl_multi_exec($mh, $runningHandles);
			} while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
		}

		// Check for any errors
		if ($execReturnValue != CURLM_OK) {
			trigger_error(sprinft(gettext("Curl multi read error %1$S"), $execReturnValue), E_USER_WARNING);
		}

		// Extract the content
		foreach ($urls as $i => $url) {
			// Check for errors
			$curlError = curl_error($ch[$i]);

			if ($curlError == "") {
				$responseContent = curl_multi_getcontent($ch[$i]);
				$this->res[$i] = $responseContent;
			} else {
				throw new Exception(sprintf(gettext("Curl error on handle $1$s: %2$s"), $ursl[$i], $curlError));
			}
			// Remove and close the handle
			curl_multi_remove_handle($mh, $ch[$i]);
			curl_close($ch[$i]);
		}

		// Clean up the curl_multi handle
		curl_multi_close($mh);
	}

	public function getResults() {
		return $this->res;
	}

}
