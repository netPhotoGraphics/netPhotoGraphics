<?php
/**
 * This is the "accessThreshold" upload tab
 *
 * @author Stephen Billard (sbillard)
 *
 * @Copyright 2016 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 * @package plugins/accessThreshold
 */
require_once(dirname(dirname(__DIR__)) . '/admin-globals.php');
admin_securityChecks(DEBUG_RIGHTS, $return = currentRelativeURL());
if (!file_exists(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg')) {
	accessThreshold::handleOptionSave(NULL, NULL);
}
if (file_exists(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg')) {
	$recentIP = getSerializedArray(file_get_contents(SERVERPATH . '/' . DATA_FOLDER . '/recentIP.cfg'));
} else {
	$recentIP = array();
}

switch (isset($_REQUEST['data_sortby']) ? $_REQUEST['data_sortby'] : '') {
	default:
		$_REQUEST['data_sortby'] = 'date';
	case 'date':
		$sort = 'accessTime';
		$recentIP = sortMultiArray($recentIP, array('lastAccessed' => true), true, false, true);
		break;
	case 'ip':
		$sort = 'ip';
		uksort($recentIP, function ($a, $b) {
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
		break;
	case 'blocked':
		$sort = 'blocked';
		foreach ($recentIP as $key => $value) {
			if (!isset($value['blocked']) || $value['blocked'] == 0) {
				unset($recentIP[$key]);
			}
		}
		$recentIP = sortMultiArray($recentIP, array('blocked' => true, 'lastAccessed' => true), true, false, true);
		break;
	case 'timesBlocked';
		$sort = 'timesBlocked';
		foreach ($recentIP as $key => $value) {
			if (!isset($value['timesBlocked']) || $value['timesBlocked'] == 0) {
				unset($recentIP[$key]);
			}
		}
		$recentIP = sortMultiArray($recentIP, array('timesBlocked' => true), true, false, true);
		break;
	case 'interval':
		$sort = 'interval';
		uasort($recentIP, function ($a, $b) {
			if (is_array($a)) {
				$a_i = $a['interval'];
			} else {
				return -1;
			}
			if (is_array($b)) {
				$b_i = $b['interval'];
			} else {
				return 1;
			}
			if ($a_i === $b_i) {
				return 0;
			} else if ($a_i === 0) {
				return 1;
			} else if ($b_i === 0) {
				return -1;
			} else if ($a_i < $b_i) {
				return -1;
			}
			return 1;
		});
		break;
}
define('SENSITIVITY', getOption('accessThreshold_SIGNIFICANT'));
$rows = ceil(getOption('accessThreshold_LIMIT') / 3);
$slice = $rows * 3;
$pages = ceil(count($recentIP) / $slice);

if (isset($_GET['subpage'])) {
	$start = sanitize_numeric($_GET['subpage']) - 1;
} else {
	$start = 0;
}

$recentIP = array_slice($recentIP, $start * $slice, $slice);

$output = array();
$__time = time();
$ct = 0;
$legendExpired = $legendBlocked = $legendClick = $legendInvalid = false;

foreach ($recentIP as $ip => $data) {
	if (is_array($data)) {
		if (str_contains($ip, ':')) {
			$sep = ':';
		} else {
			$sep = '.';
		}
		if (str_contains(getOption('accessThreshold_Owner'), ':')) {
			$drop = 8 - getOption('accessThreshold_SENSITIVITY');
		} else {
			$drop = 4 - getOption('accessThreshold_SENSITIVITY');
		}

		$ipDisp = $ip;
		if (str_contains($ip, ':')) {
			$ipDisp = preg_replace('`(0\:)+`', '::', $ipDisp, 1);
			$ipDisp = str_replace(':::', '::', $ipDisp);
		}

		$invalid = '';

		if (isset($data['interval']) && $data['interval']) {
			$interval = sprintf('%.1f', $data['interval']);
		} else {
			if (isset($data['blocked']) && $data['blocked']) {
				$interval = '0.0';
			} else {
				$interval = '&hellip;';
			}
		}
		if (isset($data['lastAccessed']) && $data['lastAccessed'] < $__time - getOption('accessThreshold_IP_ACCESS_WINDOW')) {
			$old = 'color:DarkGray;';
			$legendExpired = '<p>' . gettext('Timestamps that are <span style="color:DarkGray;">grayed out</span> have expired.') . '</p>';
			;
		} else {
			$old = '';
		}
		if (count($data['accessed']) < SENSITIVITY) {
			$invalid = 'color:DarkGray;';
			$legendInvalid = '<p>' . gettext('Intervals that are <span style="color:DarkGray;">grayed out</span> have insufficient data to be valid.') . '</p>';
		}
		if (isset($data['blocked']) && $data['blocked']) {
			$invalid = 'color:red;';
			$legendBlocked = gettext('Address with intervals that are <span style="color:Red;">red</span> have been blocked. ');
			$legendClick = '<br />&nbsp;&nbsp;&nbsp;' . gettext('Click on the address for a list of IPs.');
			$ipDisp = '<a onclick="$.colorbox({
										close: \'' . gettext("close") . '\',
										maxHeight: \'80%\',
										maxWidth: \'80%\',
										innerWidth: \'560px\',
										href:\'' . getAdminLink(PLUGIN_FOLDER . '/accessThreshold/ip_list.php') . '?selected_ip=' . $ip . '\'});">' . $ipDisp . '</a>';
		}

		$row = $ct % $rows;
		$out = '<span style="width:33%;float:left;';
		if ($even = floor($ct / $rows) % 2) {
			$out .= 'background-color:WhiteSmoke;';
		}

		$out .= '">' . "\n";
		$out .= '  <span style="width:42%;float:left;"><span style="float:right;">' . $ipDisp . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></span>' . "\n";
		if ($sort == 'timesBlocked') {
			$out .= '  <span style="width:48%;float:left;' . '">' .
							sprintf(ngettext('blocked %s time', 'blocked %s times', $data['timesBlocked']), $data['timesBlocked']) . '</span>' .
							"\n";
		} else {
			$out .= '  <span style="width:48%;float:left;' . $old . '">' . gmdate('Y-m-d H:i:s', $data['lastAccessed']) . ' GMT</span>' . "\n";
			$out .= '  <span style="width:9%;float:left;"><span style="float:right;">' . '<span style="' . $invalid . '">' . $interval . '</span></span></span>' . "\n";
		}
		$out .= "</span>\n";

		if (isset($output[$row])) {
			$output[$row] .= $out;
		} else {
			$output[$row] = $out;
		}
		$ct++;
	}
}
if (empty($output)) {
	$output[] = gettext("No entries to display");
}

printAdminHeader('logs', 'access');
echo "\n</head>";
?>

<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php npgFilters::apply('admin_note', 'access', ''); ?>
			<h1>
				<?php
				echo gettext('Access threshold');
				?>
			</h1>
			<div id="container">
				<?php
				if (getOption('accessThreshold_Monitor')) {
					echo gettext('accessThreshold is in monitor mode. No addresses have been blocked.');
				}
				?>
				<div class="tabbox">
					<form name="data_sort" style="float: right;" method="post" action="<?php echo getAdminLink(PLUGIN_FOLDER . '/accessThreshold/log_tab.php'); ?>?action=data_sortorder&tab=accessThreshold" >
						<input type="hidden" name="data_sortyb" value="<?php echo $_REQUEST['data_sortby']; ?>" >
						<span class="nowrap">
							<?php echo gettext('Sort by:'); ?>
							<select id="sortselect" name="data_sortby" onchange="this.form.submit();">
								<option value="<?php echo 'interval'; ?>" <?php if ($sort == 'interval') echo 'selected="selected"'; ?>><?php echo gettext('interval'); ?></option>
								<option value="<?php echo 'date'; ?>" <?php if ($sort == 'accessTime') echo 'selected="selected"'; ?>><?php echo gettext('date'); ?></option>
								<option value="<?php echo 'ip'; ?>" <?php if ($sort == 'ip') echo 'selected="selected"'; ?>><?php echo gettext('IP'); ?></option>
								<option value="<?php echo 'blocked'; ?>" <?php if ($sort == 'blocked') echo 'selected="selected"'; ?>><?php echo gettext('blocked'); ?></option>
								<option value="<?php echo 'timesBlocked'; ?>" <?php if ($sort == 'timesBlocked') echo 'selected="selected"'; ?>><?php echo gettext('times blocked'); ?></option>
							</select>
						</span>
					</form>
					<br style="clearall">
					<span class="centered"><?php adminPageNav($start + 1, $pages, 'admin-tabs/edit.php', '?page=logs&tab=access&data_sortby=' . $_REQUEST['data_sortby'], ''); ?></span>
					<br />
					<?php
					foreach ($output as $row) {
						echo $row . '<br style="clearall">' . "\n";
					}
					?>
					<br style="clearall">
					<span class="centered"><?php adminPageNav($start + 1, $pages, 'admin-tabs/edit.php', '?page=logs&tab=access&data_sortby=' . $_REQUEST['data_sortby'], ''); ?></span>
					<?php
					if ($sort != 'timesBlocked') {
						echo $legendExpired;
						echo $legendInvalid;
						if ($legendBlocked) {
							echo '<p>';
							echo $legendBlocked;
							if ($legendBlocked) {
								echo '<br />';
							}
							echo $legendClick;
							echo '</p>';
						}
					}
					?>
				</div>
			</div>
		</div>
		<?php printAdminFooter();
		?>
	</div>
</body>
</html>
