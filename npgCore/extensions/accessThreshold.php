<?php

/**
 * This plugin monitors front-end access and shuts down responses when a particular
 * source tries to flood the gallery with requests.
 *
 * A mask is used to control the scope of the data collection. For a IPv4 addresses
 * 	255.255.255.255 will resolve to the Host.
 *  255.255.255.0 will resolve to the Sub-net (data for all hosts in the Sub-net are grouped.)
 *  255.255.0.0 will resolve to the Network (data for the Network is grouped.)
 *
 * Access data is not acted upon until there is at least 10 access attempts. This insures
 * that flooding is not prematurely indicated.
 *
 * @author Stephen Billard (sbillard)
 * @Copyright 2016 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/accessThreshold
 * @pluginCategory security
 */
$plugin_is_filter = 990 | FEATURE_PLUGIN;
$plugin_description = gettext("Tools to block denial of service attacks.");

$option_interface = 'accessThreshold';

class accessThreshold {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOption('accessThreshold_Owner', getUserIP()); //	if he ran setup he is the owner.
			setOptionDefault('accessThreshold_IP_RETENTION', 500);
			setOptionDefault('accessThreshold_SIGNIFICANT', 10);
			setOptionDefault('accessThreshold_THRESHOLD', 5);
			setOptionDefault('accessThreshold_IP_ACCESS_WINDOW', 3600);
			setOptionDefault('accessThreshold_SENSITIVITY', '255.255.255.0');
			setOptionDefault('accessThreshold_LocaleCount', 5);
			setOptionDefault('accessThreshold_LIMIT', 100);
			setOptionDefault('accessThreshold_Monitor', TRUE);
			if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg')) {
				$recentIP = getSerializedArray(file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg'));
				if (isset($recentIP['config'])) {
					unset($recentIP['config']);
					file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg', serialize($recentIP));
				}
			}
		}
	}

	function getOptionsSupported() {
		$options = array(
				gettext('Memory') => array('key' => 'accessThreshold_IP_RETENTION', 'type' => OPTION_TYPE_NUMBER,
						'order' => 5,
						'desc' => gettext('The number unique access attempts to keep.')),
				gettext('Sensitivity') => array('key' => 'accessThreshold_SIGNIFICANT', 'type' => OPTION_TYPE_NUMBER,
						'order' => 2.5,
						'desc' => gettext('The minimum number of accesses for the Threshold to be valid.')),
				gettext('Threshold') => array('key' => 'accessThreshold_THRESHOLD', 'type' => OPTION_TYPE_NUMBER,
						'order' => 2,
						'desc' => gettext('Attempts will be blocked if the average access interval is less than this number of seconds.')),
				gettext('Window') => array('key' => 'accessThreshold_IP_ACCESS_WINDOW', 'type' => OPTION_TYPE_NUMBER,
						'order' => 1,
						'desc' => gettext('The access interval is reset if the last access is was more than this many seconds ago.')),
				gettext('Mask') => array('key' => 'accessThreshold_SENSITIVITY', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 4,
						'desc' => gettext('IP mask to determine the IP elements sensitivity')),
				gettext('Locale limit') => array('key' => 'accessThreshold_LocaleCount', 'type' => OPTION_TYPE_NUMBER,
						'order' => 3,
						'desc' => sprintf(gettext('Requests will be blocked if more than %d locales are requested.'), getOption('accessThreshold_LocaleCount'))),
				gettext('Limit') => array('key' => 'accessThreshold_LIMIT', 'type' => OPTION_TYPE_NUMBER,
						'order' => 6,
						'desc' => sprintf(gettext('The top %d accesses will be displayed.'), getOption('accessThreshold_LIMIT'))),
				gettext('Owner') => array('key' => 'accessThreshold_Owner', 'type' => OPTION_TYPE_TEXTBOX,
						'order' => 7,
						'desc' => sprintf(gettext('Requests from this IP will be ignored.') . ' <span class="logwarning">' . gettext('If your IP address is dynamically assigned you may need to update this on a regular basis.') . '</span>', getOption('accessThreshold_LIMIT'))),
				gettext('Mointor only') => array('key' => 'accessThreshold_Monitor', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 7,
						'desc' => sprintf(gettext('It this box is checked, data will be collected but visitors will not be blocked.'), getOption('accessThreshold_LIMIT'))),
				gettext('Clear list') => array('key' => 'accessThreshold_CLEAR', 'type' => OPTION_TYPE_CHECKBOX,
						'order' => 99,
						'value' => 0,
						'desc' => gettext('Clear the access list.'))
		);
		return $options;
	}

	static function handleOptionSave($themename, $themealbum) {
		if (getOption('accessThreshold_CLEAR')) {
			$recentIP = array();
			setOption('accessThreshold_Owner', getUserIP());
		} else {
			if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg')) {
				$recentIP = getSerializedArray(file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg'));
			} else {
				$recentIP = array();
			}
		}
		purgeOption('accessThreshold_CLEAR');
		file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg', serialize($recentIP));
	}

	static function admin_tabs($tabs) {
		global $_current_admin_obj;

		if ((npg_loggedin(ADMIN_RIGHTS) && $_current_admin_obj->getID())) {
			$tabs['logs']['subtabs'][gettext("access")] = PLUGIN_FOLDER . '/accessThreshold/admin_tab.php?page=logs&tab=access';
			if (!$tabs['logs']['default']) {
				$tabs['logs']['default'] = gettext("access");
				$tabs['logs']['link'] = getAdminLink(PLUGIN_FOLDER . '/accessThreshold/admin_tab.php') . '?page=logs&tab=access';
			}
		}
		return $tabs;
	}

	static function maskIP($full_ip) {
		$sHex = strpos($full_ip, '.') === false;
		$mask = getOption('accessThreshold_SENSITIVITY');
		$mHex = strpos($mask, '.') === false;
		$target = explode('.', str_replace(':', '.', ltrim($full_ip, ':')));
		$mask = explode('.', str_replace(':', '.', $mask . '.0.0.0.0.0.0.0.0'));
		foreach ($mask as $key => $m) {
			if ($mHex) {
				$m = (int) hexdec($m);
			} else {
				$m = (int) $m;
			}
			if (isset($target[$key])) {
				if ($sHex) {
					$target[$key] = dechex((int) hexdec($target[$key]) & $m);
				} else {
					$target[$key] = (int) $target[$key] & $m;
				}
			} else {
				break;
			}
		}
		$c = count($target) - 1;
		while ($c >= 0) {
			if ($target[$c]) {
				break;
			}
			unset($target[$c]);
			$c--;
		}
		return implode($sHex ? ':' : '.', $target);
	}

	static function walk(&$element, $key, $__time) {
		global $__previous, $__interval, $__count;
		if (isset($element['time'])) {
			$v = $element['time'];
		} else {
			$v = 0;
		}
		if ($__time - $v < 3600) { //only the within the last 10 minutes
			if ($__count > 0) {
				$__interval = $__interval + ($v - $__previous);
			}
			$__count++;
		} else {
			$element = NULL;
		}
		$__previous = $v;
	}

}

if (OFFSET_PATH) {
	npgFilters::register('admin_tabs', 'accessThreshold::admin_tabs', -100);
}

if (!isset($_current_admin_obj) || (!$me = $_current_admin_obj && !$_current_admin_obj->transient)) {
	$me = getUserIP() == getOption('accessThreshold_Owner');
} else {
	if ($_current_admin_obj->master) {
		if (getOption('accessThreshold_Owner') != $ip = getUserIP()) {
			//	keep it mostly current if the user does not have a static IP
			setOption('accessThreshold_Owner', $ip);
		}
	}
}

if (!$me) {
	$monitor = getOption('accessThreshold_Monitor');
	$mu = new npgMutex('aT');
	$mu->lock();
	if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg')) {
		$recentIP = getSerializedArray(file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg'));
	} else {
		$recentIP = array();
	}

	$__time = time();
	$full_ip = getUserIP();
	$ip = accessThreshold::maskIP($full_ip);
	if (isset($recentIP[$ip]['lastAccessed']) && $__time - $recentIP[$ip]['lastAccessed'] > getOption('accessThreshold_IP_ACCESS_WINDOW')) {
		$recentIP[$ip] = array(
				'accessed' => array(),
				'locales' => array(),
				'blocked' => 0,
				'interval' => 0
		);
	}
	$recentIP[$ip]['lastAccessed'] = $__time;
	if (!$monitor && isset($recentIP[$ip]['blocked']) && $recentIP[$ip]['blocked']) {
		file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg', serialize($recentIP));
		$mu->unlock();
		sleep(10);
		header("HTTP/1.0 503 Service Unavailable");
		header("Status: 503 Service Unavailable");
		header("Retry-After: 300");
		exit(); //	terminate the script with no output
	} else {
		$recentIP[$ip]['accessed'][] = array('time' => $__time, 'ip' => $full_ip);
		$__locale = i18n::getUserLocale();
		if (isset($recentIP[$ip]['locales'][$__locale])) {
			$recentIP[$ip]['locales'][$__locale]['ip'][$full_ip] = $__time;
		} else {
			$recentIP[$ip]['locales'][$__locale] = array('time' => $__time, 'ip' => array($full_ip => $__time));
		}

		$__previous = $__interval = $__count = 0;
		array_walk($recentIP[$ip]['locales'], 'accessThreshold::walk', $__time);
		foreach ($recentIP[$ip]['locales'] as $key => $data) {
			if (is_null($data)) {
				unset($recentIP[$ip]['locales'][$key]);
			}
		}
		if ($__count > getOption('accessThreshold_LocaleCount')) {
			npgFilters::apply('access_control', 4, 'locales', 'accessThreshold', getRequestURI());
			$recentIP[$ip]['blocked'] = 1;
		}

		$__previous = $__interval = $__count = 0;
		array_walk($recentIP[$ip]['accessed'], 'accessThreshold::walk', $__time);
		foreach ($recentIP[$ip]['accessed'] as $key => $data) {
			if (is_null($data)) {
				unset($recentIP[$ip]['accessed'][$key]);
			}
		}
		if ($__count > 1) {
			$__interval = $__interval / $__count;
		} else {
			$__interval = 0;
		}
		$recentIP[$ip]['interval'] = $__interval;
		if ($__count > getOption('accessThreshold_SIGNIFICANT') && $__interval < getOption('accessThreshold_THRESHOLD')) {
			npgFilters::apply('access_control', 4, 'threshold', 'accessThreshold', getRequestURI());
			$recentIP[$ip]['blocked'] = 2;
		}
	}
	if (count($recentIP) - 1 > getOption('accessThreshold_IP_RETENTION')) {
		$recentIP = sortMultiArray($recentIP, array('lastAccessed'), true, true, false, true);
		$recentIP = array_slice($recentIP, 0, getOption('accessThreshold_IP_RETENTION'));
	}
	file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg', serialize($recentIP));
	$mu->unlock();

	unset($ip);
	unset($full_ip);
	unset($recentIP);
	unset($__time);
	unset($__interval);
	unset($__previous);
	unset($__count);
	unset($__locale);
}
