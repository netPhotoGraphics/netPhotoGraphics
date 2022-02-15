<?php

/**
 * PHP sendmail mailing handler
 *
 * @author Stephen Billard (sbillard)
 *
 * @Copyright 2019 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/simple_sendmail
 * @pluginCategory mail
 */
$plugin_is_filter = defaultExtension(5 | CLASS_PLUGIN);
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext("Outgoing mail handler based on the PHP <em>mail</em> facility.");
	$plugin_disable = npgFunctions::pluginDisable(array(
							array(!npgFunctions::isValidEmail(getOption('site_email')), gettext('The general option "Email"—used as the "From" address for all mails sent by the gallery—must be set.')),
							array(npgFilters::has_filter('sendmail') && !extensionEnabled('simple_sendmail'), sprintf(gettext('Only one Email handler plugin may be enabled. <a href="#%1$s"><code>%1$s</code></a> is already enabled.'), stripSuffix(npgFilters::script('sendmail'))))
	));
}

if (OFFSET_PATH == 2) {
	if ($priority = extensionEnabled('zenphoto_sendmail')) {
		enableExtension('simple_sendmail', $priority);
		enableExtension('zenphoto_sendmail', 0);
	}
}

npgFilters::register('sendmail', 'simple_sendmail');

function simple_sendmail($result, $email_list, $subject, $message, $from_mail, $from_name, $cc_addresses, $bcc_addresses, $replyTo) {
	$headers['from'] = sprintf('From: %1$s <%2$s>', $from_name, $from_mail);
	if (count($cc_addresses) > 0) {
		$list = '';
		foreach ($cc_addresses as $name => $mail) {
			if (is_numeric($name)) {
				$list .= ',' . $mail;
			} else {
				$list .= ',' . $name . ' <' . $mail . '>';
			}
		}
		$headers['cc'] = 'Cc: ' . substr($list, 1);
	}
	if ($replyTo) {
		$headers ['reply'] = 'Reply-To: ' . reset($replyTo);
	}
	$headers ['mime'] = "Mime-Version: 1.0";
	$headers ['type'] = "Content-Type: text/html; charset=" . LOCAL_CHARSET;
	$headers['content'] = "Content-Transfer-Encoding: quoted-printable";
	$message = quoted_printable_encode($message);

	$sendList = array_merge($email_list, $bcc_addresses);

	$success = true;
	$pause = false;
	foreach ($sendList as $name => $mail) {
		if (is_numeric($name)) {
			$to_mail = $mail;
		} else {
			$to_mail = $name . ' <' . $mail . '>';
		}
		$success = $success && mail($to_mail, $subject, $message, implode("\n", $headers));
		unset($headers['cc']); //	only  cc on one of the mails
		if ($pause) { //	do not flood the server
			sleep(10);
		} else {
			$pause = true;
		}
	}
	if (!$success) {
		if (!empty($result))
			$result .= '<br />';
		$result .= sprintf(gettext('<code>simple_sendmail</code> failed to send <em>%s</em> to one or more recipients.'), $subject);
	}
	return $result;
}

?>