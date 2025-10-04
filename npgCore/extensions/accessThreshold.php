<?php
/**
 * This plugin monitors front-end access and shuts down responses when a particular
 * source tries to flood the gallery with requests.
 *
 * A mask is used to control the scope of the data collection. For a IPv4 addresses
 * 	255.255.255.255 (<em>Selectivity</em> 4) will resolve to the Host.
 *  255.255.255.0 (<em>Selectivity</em> 3) will resolve to the Sub-net (data for all hosts in the Sub-net are grouped.)
 *  255.255.0.0 (<em>Selectivity</em> 2) will resolve to the Network (data for the Network is grouped.)
 *
 * Access data is not acted upon until there are more access attempts than the <em>sensitivity</em> setting. This insures
 * that flooding is not prematurely indicated.
 *
 * Logged-in users are not monitored nor restricted in their access rate.
 *
 * @author Stephen Billard (sbillard)
 * @Copyright 2016, 2023 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/accessThreshold
 * @pluginCategory security
 */
$plugin_is_filter = 990 | FEATURE_PLUGIN;
$plugin_description = gettext("Tools to block denial of service attacks.");

$option_interface = 'accessThreshold';
define('accessThreshold_min_SIGNIFICANT', 2);

class accessThreshold {

	function __construct() {
		if (OFFSET_PATH == 2) {
			setOptionDefault('accessThreshold_IP_RETENTION', 500);
			if (getOption('accessThreshold_SIGNIFICANT') > (int) (MySQL_CONNECTIONS * 0.75) || getOption('accessThreshold_SIGNIFICANT') < accessThreshold_min_SIGNIFICANT) {
				purgeOption('accessThreshold_SIGNIFICANT');
			}
			setOptionDefault('accessThreshold_SIGNIFICANT', min((int) (MySQL_CONNECTIONS * 0.75), 20));
			setOptionDefault('accessThreshold_THRESHOLD', 20);
			setOptionDefault('accessThreshold_IP_ACCESS_WINDOW', 3600);
			if (!is_numeric(getOption('accessThreshold_SENSITIVITY'))) {
				purgeOption('accessThreshold_SENSITIVITY');
			}
			if (str_contains(getUserIP(), ':')) {
				setOptionDefault('accessThreshold_SENSITIVITY', 7);
			} else {
				setOptionDefault('accessThreshold_SENSITIVITY', 3);
			}
			purgeOption('accessThreshold_LocaleCount');

			setOptionDefault('accessThreshold_LIMIT', 100);
			setOptionDefault('accessThreshold_Monitor', TRUE);
			setOptionDefault('accessThreshold_Log', TRUE);
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
		if (str_contains(getOption('accessThreshold_Owner'), ':')) {
			$max = 8;
		} else {
			$max = 4;
		}
		$options = array(
				gettext('Memory') => array('key' => 'accessThreshold_IP_RETENTION', 'type' => OPTION_TYPE_NUMBER,
						'order' => 5,
						'desc' => gettext('The number unique (by masked IP address segments) access attempts to keep.')),
				gettext('Sensitivity') => array('key' => 'accessThreshold_SIGNIFICANT', 'type' => OPTION_TYPE_SLIDER,
						'min' => accessThreshold_min_SIGNIFICANT,
						'max' => min((int) (MySQL_CONNECTIONS * 0.75), 25),
						'order' => 2.5,
						'desc' => gettext('The minimum number of accesses for the Threshold to be valid.')),
				gettext('Threshold') => array('key' => 'accessThreshold_THRESHOLD', 'type' => OPTION_TYPE_NUMBER,
						'order' => 2,
						'desc' => gettext('Attempts will be blocked if the average access interval is less than this number of seconds.')),
				gettext('Window') => array('key' => 'accessThreshold_IP_ACCESS_WINDOW', 'type' => OPTION_TYPE_NUMBER,
						'order' => 1,
						'desc' => gettext('The access interval is reset if the last access is was more than this many seconds ago.')),
				gettext('Selectivity') => array('key' => 'accessThreshold_SENSITIVITY', 'type' => OPTION_TYPE_SLIDER,
						'min' => 1,
						'max' => $max,
						'order' => 4,
						'desc' => gettext('The number of IP address segments of the address that are considered significant.') . ' ' . sprintf(gettext('For instance %1$s would consolidate all hosts on a subnet. %2$s would consolidate the subnet and its hosts.'), $max - 1, $max - 2)),
				gettext('Display') => array('key' => 'accessThreshold_LIMIT', 'type' => OPTION_TYPE_NUMBER,
						'order' => 6,
						'desc' => sprintf(gettext('Show %d accesses per page.'), getOption('accessThreshold_LIMIT'))),
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
			file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg', serialize(array()));
		}
		purgeOption('accessThreshold_CLEAR');
	}

	static function log_tabs($tabs) {
		global $_current_admin_obj;
		if ((npg_loggedin(ADMIN_RIGHTS) && $_current_admin_obj->getID())) {
			$tabs['logs']['subtabs'][gettext("access")] = PLUGIN_FOLDER . '/accessThreshold/log_tab.php?page=logs&tab=access';
			if (!$tabs['logs']['default']) {
				$tabs['logs']['default'] = gettext("access");
				$tabs['logs']['link'] = getAdminLink(PLUGIN_FOLDER . '/accessThreshold/log_tab.php') . '?page=logs&tab=access';
			}
		}
		return $tabs;
	}

	/*
	 * Installation information tab content
	 */

	static function accessCount() {
		$days = [];
		$ips = [];
		if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg')) {
			$recentIP = getSerializedArray(file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg'));
			foreach ($recentIP as $data) {
				if (isset($data['accessed'])) {
					foreach ($data['accessed'] as $access) {
						$date = gmdate('yy-m-d H', $access['time']);
						if (isset($days[$date])) {
							$days[$date]++;
						} else {
							$days[$date] = 1;
						}
						$ip = $access['ip'];
						if (isset($ips[$ip])) {
							$ips[$ip]++;
						} else {
							$ips[$ip] = 1;
						}
					}
				}
			}
			krsort($days);
			uksort($ips, function ($a, $b) {
				$retval = 0;
				$_a = explode('.', str_replace(':', '.', $a));
				$_b = explode('.', str_replace(':', '.', $b));
				foreach ($_a as $key => $va) {
					if ($retval == 0 && isset($_b[$key])) {
						$retval = strnatcmp($va, $_b[$key]);
					} else {
						break;
					}
				}
				return $retval;
			});
			arsort($ips);
		}
		$info = gettext("Statistics gathered from the accessThreshold plugin's &quot;memory&quot; data.");
		?>
		<div class="box overview-section overview-install-info">
			<div class="overview-list-h3">
				<h3>
					<?php echo gettext('Site Visits by hour (GMT)'); ?>
					<span style="float:right!important" title="<?php echo $info; ?>">
						<?php echo INFORMATION_BLUE; ?>
					</span>
				</h3>
			</div>
			<div class="overview_list">
				<ul class="plugins">
					<?php
					foreach ($days as $date => $count) {
						?>
						<li>
							<?php
							printf(ngettext('%1$s: %2$s visit', '%1$s: %2$s visits', $count), $date, $count);
							?>
						</li>
						<?php
					}
					?>
				</ul>
			</div><!-- accessThreshold visits by date -->
		</div>
		<div class="box overview-section overview-install-info">
			<div class="overview-list-h3">
				<h3>
					<?php echo gettext('Site Visits by IP'); ?>
					<span style="float:right!important" title="<?php echo $info; ?>">
						<?php echo INFORMATION_BLUE; ?>
					</span>
				</h3>
			</div>
			<div class="overview_list">
				<ul class="plugins">
					<?php
					foreach ($ips as $ip => $count) {
						?>
						<li>
							<?php
							printf(ngettext('%1$s: %2$s visit', '%1$s: %2$s visits', $count), $ip, $count);
							?>
						</li>
						<?php
					}
					?>
				</ul>
			</div><!-- accessThreshold visits by IP -->
		</div>
		<?php
	}

	static function maskIP($full_ip) {
		if (str_contains(getOption('accessThreshold_Owner'), ':')) {
			$drop = 8;
		} else {
			$drop = 4;
		}
		$drop = $drop - getOption('accessThreshold_SENSITIVITY');

		$sHex = str_contains($full_ip, ':');
		if ($sHex) {
			$items = 7 - substr_count(trim($full_ip, ':'), ':');
			$fill = str_pad('', $items * 2, '0:');
			$full_ip = trim(str_replace('::', ':' . $fill, $full_ip), ':');
			$target = explode(':', $full_ip);
			$base = 8;
		} else {
			$target = explode('.', $full_ip);
			$base = 4;
		}

		While ($drop > 0) {
			$target[$base - $drop] = '···';
			$drop--;
		}

		if ($sHex) {
			return implode(':', $target);
		} else {
			return implode('.', $target);
		}
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
	npgFilters::register('admin_tabs', 'accessThreshold::log_tabs', -100);
	npgFilters::register('installation_information', 'accessThreshold::accessCount');
	if (OFFSET_PATH == 2) {
		setOption('accessThreshold_Owner', getUserIP());
	}
}

if (extensionEnabled('accessThreshold')) {
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
		$window = getOption('accessThreshold_IP_ACCESS_WINDOW');
		if (isset($recentIP[$ip]['lastAccessed']) && $__time - $recentIP[$ip]['lastAccessed'] > $window) {
			$recentIP[$ip] = array(
					'accessed' => array(),
					'blocked' => 0,
					'timesBlocked' => isset($recentIP[$ip]['timesBlocked']) ? $recentIP[$ip]['timesBlocked'] : 0,
					'interval' => 0
			);
		}
		$recentIP[$ip]['lastAccessed'] = $__time;
		if (isset($recentIP[$ip]['blocked']) && $recentIP[$ip]['blocked']) {
			if (!$monitor) {
				file_put_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg', serialize($recentIP));
				$mu->unlock();
				if (is_object($_siteMutex)) {
					$_siteMutex->unlock();
				}
				db_close();
				header("HTTP/1.0 429 Too Many Requests");
				header("Status: 429 Too Many Requests");
				header("Retry-After: $window");
				exit(); //	terminate the script with no output
			}
		} else {
			$recentIP[$ip]['accessed'][] = array('time' => $__time, 'ip' => $full_ip);
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
				$recentIP[$ip]['blocked'] = 2;
				if (isset($recentIP[$ip]['timesBlocked'])) {
					$recentIP[$ip]['timesBlocked']++;
				} else {
					$recentIP[$ip]['timesBlocked'] = 1;
				}
			}
		}
		if (count($recentIP) - 1 > getOption('accessThreshold_IP_RETENTION')) {
			$recentIP = sortMultiArray($recentIP, array('lastAccessed' => true), true, false, true);
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
		unset($mu);
	}
	unset($me);
}
