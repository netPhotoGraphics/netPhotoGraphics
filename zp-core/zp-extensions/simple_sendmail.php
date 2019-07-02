<?php

/**
 * PHP sendmail mailing handler
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/simple_sendmail
 * @pluginCategory mail
 */
$plugin_is_filter = defaultExtension(5 | CLASS_PLUGIN);
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext("Outgoing mail handler based on the PHP <em>mail</em> facility.");
	$plugin_disable = (npgFilters::has_filter('sendmail') && !extensionEnabled('simple_sendmail')) ? sprintf(gettext('Only one Email handler plugin may be enabled. <a href="#%1$s"><code>%1$s</code></a> is already enabled.'), stripSuffix(npgFilters::script('sendmail'))) : '';
}

if (OFFSET_PATH == 2) {
	if ($priority = extensionEnabled('zenphoto_sendmail')) {
		enableExtension('simple_sendmail', $priority);
		enableExtension('zenphoto_sendmail', 0);
	}
}

npgFilters::register('sendmail', 'simple_sendmail');

function simple_sendmail($msg, $email_list, $subject, $message, $from_mail, $from_name, $cc_addresses, $bcc_addresses, $replyTo, $html = false) {
	$headers = sprintf('From: %1$s <%2$s>', $from_name, $from_mail) . "\n";
	if (count($cc_addresses) > 0) {
		$cclist = '';
		foreach ($cc_addresses as $cc_name => $cc_mail) {
			$cclist .= ',' . $cc_mail;
		}
		$headers .= 'Cc: ' . substr($cclist, 1) . "\n";
	}
	if ($replyTo) {
		$headers .= 'Reply-To: ' . array_shift($replyTo) . "\n";
	}
	$sendList = array_merge($email_list, $bcc_addresses);

	$result = true;
	foreach ($sendList as $to_mail) {
		$result = $result && utf8::send_mail($to_mail, $subject, $message, $headers, '', $html);
	}
	if (!$result) {
		if (!empty($msg))
			$msg .= '<br />';
		$msg .= sprintf(gettext('<code>simple_sendmail</code> failed to send <em>%s</em> to one or more recipients.'), $subject);
	}
	return $msg;
}

?>