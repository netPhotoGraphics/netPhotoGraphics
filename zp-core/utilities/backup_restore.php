<?php
/**
 * Backup and restore of the database tables
 *
 * This plugin provides a means to make backups of your  database and
 * at a later time restore the database to the contents of one of these backups.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
if (!defined('OFFSET_PATH'))
	define('OFFSET_PATH', 3);
define('HEADER', '__HEADER__');
define('RECORD_SEPARATOR', ':****:');
define('TABLE_SEPARATOR', '::');
define('RESPOND_COUNTER', 1000);

require_once(dirname(dirname(__FILE__)) . '/admin-globals.php');
require_once(dirname(dirname(__FILE__)) . '/template-functions.php');
$signaure = getOption('netphotographics_install');
if (!$_current_admin_obj || $_current_admin_obj->getID()) {
	$rights = NULL;
} else {
	$rights = USER_RIGHTS;
}
admin_securityChecks($rights, currentRelativeURL());

if (isset($_REQUEST['action'])) {
	$action = $_REQUEST['action'];
} else {
	$action = NULL;
}

global $handle, $buffer, $counter, $file_version, $compression_handler; // so this script can run from a function
$buffer = '';

function extendExecution() {
	@set_time_limit(30);
	echo ' ';
}

function fillbuffer($handle) {
	global $buffer;
	$record = fread($handle, 8192);
	if ($record === false || empty($record)) {
		return false;
	}
	$buffer .= $record;
	return true;
}

function getrow($handle) {
	global $buffer, $counter, $file_version;
	if ($file_version == 0 || substr($buffer, 0, strlen(HEADER)) == HEADER) {
		$end = strpos($buffer, RECORD_SEPARATOR);
		while ($end === false) {
			if ($end = fillbuffer($handle)) {
				$end = strpos($buffer, RECORD_SEPARATOR);
			} else {
				return false;
			}
		}
		$result = substr($buffer, 0, $end);
		$buffer = substr($buffer, $end + strlen(RECORD_SEPARATOR));
	} else {
		$i = strpos($buffer, ':');
		if ($i === false) {
			fillbuffer($handle);
			$i = strpos($buffer, ':');
		}
		$end = substr($buffer, 0, $i) + $i + 1;
		while ($end >= strlen($buffer)) {
			if (!fillbuffer($handle))
				return false;
		}
		$result = substr($buffer, $i + 1, $end - $i - 1);
		$buffer = substr($buffer, $end);
	}
	return $result;
}

function decompressField($str) {
	global $compression_handler;
	switch ($compression_handler) {
		default:
			return $str;
		case 'bzip2':
			return bzdecompress($str);
		case 'gzip':
			return gzuncompress($str);
	}
}

function compressRow($str, $lvl) {
	global $compression_handler;
	switch ($compression_handler) {
		default:
			return $str;
		case 'bzip2_row':
			return bzcompress($str, $lvl);
		case 'gzip_row':
			return gzcompress($str, $lvl);
	}
}

function decompressRow($str) {
	global $compression_handler;
	switch ($compression_handler) {
		default:
			return $str;
		case 'bzip2_row':
			return bzdecompress($str);
		case 'gzip_row':
			return gzuncompress($str);
	}
}

function writeHeader($type, $value) {
	global $handle;
	return fwrite($handle, HEADER . $type . '=' . $value . RECORD_SEPARATOR);
}

if ($_current_admin_obj->reset) {
	printAdminHeader('restore');
} else {
	printAdminHeader('admin', 'backup');
}

echo '</head>';

$messages = '';

$prefix = trim(prefix(), '`');
$prefixLen = strlen($prefix);

$tables = array();
$result = db_show('tables');
if ($result) {
	while ($row = db_fetch_assoc($result)) {
		$tables[] = $row;
	}
	db_free_result($result);
}

if ($action == 'backup') {
	XSRFdefender('backup');
	$compression_level = sanitize($_REQUEST['compress'], 3);
	setOption('backup_compression', $compression_level);
	if ($compression_level > 0) {
		if (function_exists('bzcompress')) {
			$compression_handler = 'bzip2_row';
		} else {
			$compression_handler = 'gzip_row';
		}
	} else {
		$compression_handler = 'no';
	}

	if (!empty($tables)) {
		$folder = SERVERPATH . "/" . BACKUPFOLDER;
		$filename = $folder . '/backup-' . date('Y_m_d-H_i_s') . '.zdb';
		if (!is_dir($folder)) {
			mkdir($folder, FOLDER_MOD);
		}
		@chmod($folder, FOLDER_MOD);
		$writeresult = $handle = @fopen($filename, 'w');
		if ($handle === false) {
			$msg = sprintf(gettext('Failed to open %s for writing.'), $filename);
			echo $msg;
		} else {
			$writeresult = writeheader('file_version', 1);
			$writeresult = $writeresult && writeHeader('compression_handler', $compression_handler);
			if ($writeresult === false) {
				$msg = gettext('failed writing to backup!');
			}

			$counter = 0;
			$writeresult = true;
			foreach ($tables as $row) {
				$table = array_shift($row);
				$unprefixed_table = substr($table, strlen($prefix));
				$sql = 'SELECT * from `' . $table . '`';
				$result = query($sql);
				if ($result) {
					while ($tablerow = db_fetch_assoc($result)) {
						extendExecution();
						$storestring = serialize($tablerow);
						$storestring = compressRow($storestring, $compression_level);
						$storestring = $unprefixed_table . TABLE_SEPARATOR . $storestring;
						$storestring = strlen($storestring) . ':' . $storestring;
						$writeresult = fwrite($handle, $storestring);
						if ($writeresult === false) {
							$msg = gettext('failed writing to backup!');
							break;
						}
						$counter++;
						if ($counter >= RESPOND_COUNTER) {
							echo ' ';
							$counter = 0;
						}
					}
					db_free_result($result);
				}
				if ($writeresult === false)
					break;
			}
			fclose($handle);
			@chmod($filename, 0660 & CHMOD_VALUE);
		}
	} else {
		$msg = gettext('SHOW TABLES failed!');
		$writeresult = false;
	}
	if ($writeresult) {
		setOption('last_backup_run', time());
		$messages = '
		<div class="messagebox fade-message">
		<h2>
		';
		if ($compression_level > 0) {
			$messages .= sprintf(gettext('backup completed using <em>%1$s(%2$s)</em> compression'), $compression_handler, $compression_level);
		} else {
			$messages .= gettext('backup completed');
		}
		$messages .= '
		</h2>
		</div>
		<?php
		';
	} else {
		if (isset($_REQUEST['autobackup'])) {
			debugLog(sprintf('Autobackup failed: %s', $msg));
		}
		$messages = '
		<div class="errorbox fade-message">
		<h2>' . gettext("backup failed") . '</h2>
		<p>' . $msg . '</p>
		</div>
		';
	}
} else if ($action == 'restore') {
	XSRFdefender('restore');
	$oldlibauth = npg_Authority::getVersion();
	$errors = array(gettext('No backup set found.'));

	if (isset($_REQUEST['backupfile'])) {
		$file_version = 0;
		$compression_handler = 'gzip';
		$folder = SERVERPATH . "/" . BACKUPFOLDER . '/';
		$filename = $folder . internalToFilesystem(sanitize($_REQUEST['backupfile'], 3)) . '.zdb';
		if (file_exists($filename)) {
			$handle = fopen($filename, 'r');
			if ($handle !== false) {
				$resource = db_show('tables');
				if ($resource) {
					$result = array();
					while ($row = db_fetch_assoc($resource)) {
						$result[] = $row;
					}
					db_free_result($resource);
				} else {
					$result = false;
				}

				$unique = $tables = array();
				$table_cleared = array();
				if (is_array($result)) {
					foreach ($result as $row) {
						extendExecution();
						$table = array_shift($row);
						$tables[$table] = array();
						$table_cleared[$table] = false;
						$result2 = db_list_fields(substr($table, $prefixLen));
						if (is_array($result2)) {
							foreach ($result2 as $row) {
								$tables[$table][] = $row['Field'];
							}
						}
						$result2 = db_show('index', $table);
						if (is_array($result2)) {
							foreach ($result2 as $row) {
								if (is_array($row)) {
									if (array_key_exists('Non_unique', $row) && !$row['Non_unique']) {
										$unique[$table][] = $row['Column_name'];
									}
								}
							}
						}
					}
				}

				$errors = array();
				$string = getrow($handle);
				while (substr($string, 0, strlen(HEADER)) == HEADER) {
					$string = substr($string, strlen(HEADER));
					$i = strpos($string, '=');
					$type = substr($string, 0, $i);
					$what = substr($string, $i + 1);
					switch ($type) {
						case 'compression_handler':
							$compression_handler = $what;
							break;
						case 'file_version':
							$file_version = $what;
					}
					$string = getrow($handle);
				}
				$counter = 0;
				$missing_table = array();
				$missing_element = array();
				while (!empty($string) && count($errors) < 100) {
					extendExecution();
					$sep = strpos($string, TABLE_SEPARATOR);
					$table = substr($string, 0, $sep);
					if (isset($_REQUEST['restore_' . $table])) {
						if (array_key_exists($prefix . $table, $tables)) {
							if (!$table_cleared[$prefix . $table]) {
								if (!db_truncate_table($table)) {
									$errors[] = gettext('Truncate table<br />') . db_error();
								}
								$table_cleared[$prefix . $table] = true;
							}
							$row = substr($string, $sep + strlen(TABLE_SEPARATOR));
							$row = decompressRow($row);
							$row = unserialize($row);

							foreach ($row as $key => $element) {
								if ($compression_handler == 'bzip2' || $compression_handler == 'gzip') {
									if (!empty($element)) {
										$element = decompressField($element);
									}
								}
								if (array_search($key, $tables[$prefix . $table]) === false) {
//	Flag it if data will be lost
									$missing_element[] = $table . '->' . $key;
									unset($row[$key]);
								} else {
									if (is_null($element)) {
										$row[$key] = 'NULL';
									} else {
										$row[$key] = db_quote($element);
									}
								}
							}
							if (!empty($row)) {
								if ($table == 'options') {
									if ($row['name'] == 'netphotographics_install') {
										break;
									}
									if ($row['theme'] == 'NULL') {
										$row['theme'] = db_quote('');
									}
								}
								$sql = 'INSERT INTO ' . prefix($table) . ' (`' . implode('`,`', array_keys($row)) . '`) VALUES (' . implode(',', $row) . ')';
								foreach ($unique[$prefix . $table] as $exclude) {
									unset($row[$exclude]);
								}
								if (count($row) > 0) {
									$sqlu = ' ON DUPLICATE KEY UPDATE ';
									foreach ($row as $key => $value) {
										$sqlu .= '`' . $key . '`=' . $value . ',';
									}
									$sqlu = substr($sqlu, 0, -1);
								} else {
									$sqlu = '';
								}
								if (!query($sql . $sqlu, false)) {
									$errors[] = $sql . $sqlu . '<br />' . db_error();
								}
							}
						} else {
							$missing_table[] = $table;
						}
					}

					$counter++;
					if ($counter >= RESPOND_COUNTER) {
						echo ' ';
						$counter = 0;
					}
					$string = getrow($handle);
				}
			}
			fclose($handle);
		}
	}

	if (!empty($missing_table) || !empty($missing_element)) {
		$messages = '
		<div class="warningbox">
			<h2>' . gettext("Restore encountered exceptions") . '</h2>';
		if (!empty($missing_table)) {
			$messages .= '
				<p>' . gettext('The following tables were not restored because the table no longer exists:') . '
					<ul>
					';
			foreach (array_unique($missing_table) as $item) {
				$messages .= '<li><em>' . $item . '</em></li>';
			}
			$messages .= '
					</ul>
				</p>
				';
		}
		if (!empty($missing_element)) {
			$messages .= '
				<p>' . gettext('The following fields were not restored because the field no longer exists:') . '
					<ul>
					';

			foreach (array_unique($missing_element) as $item) {
				$messages .= '<li><em>' . $item . '</em></li>';
			}
			$messages .= '
					</ul>
				</p>
				';
		}
		$messages .= '
		</div>
		';
	} else if (count($errors) > 0) {
		$messages = '
		<div class="errorbox">
			<h2>';
		if (count($errors) >= 100) {
			$messages .= gettext('The maximum error count was exceeded and the restore aborted.');
			unset($_GET['compression']);
		} else {
			$messages .= gettext("Restore encountered the following errors:");
		}
		$messages .= '</h2>
			';
		foreach ($errors as $msg) {
			$messages .= '<p>' . html_encode($msg) . '</p>';
		}
		$messages .= '
		</div>
		';
	} else {
		$messages = '
			<script type="text/javascript">
				window.addEventListener(\'load\',  function() {
					window.location = "' . getAdminLink(UTILITIES_FOLDER . '/backup_restore.php') . '?tab=backup&compression=' . $compression_handler . '";
				}, false);
			</script>
		';
	}
	primeOptions(); //invalidate any options from before the restore
	if (getOption('netphotographics_install') !== $signaure) {
		$l1 = '<a href="' . getAdminLink('setup.php') . '">';
		$messages .= '<div class="notebox">
			<h2>' . sprintf(gettext('You have restored your database from a different instance of the software. You should run %1$ssetup%2$s to insure proper migration.'), $l1, '</a>') . '</h2>
			</div>';
	}

	setOption('license_accepted', NETPHOTOGRAPHICS_VERSION);
	if ($oldlibauth != npg_Authority::getVersion()) {
		if (!$_authority->migrateAuth($oldlibauth)) {
			$messages .= '
			<div class="errorbox fade-message">
			<h2>' . gettext('Rights migration failed!') . '</h2>
			</div>
			';
		}
	}
}

if (isset($_GET['compression'])) {
	$compression_handler = sanitize($_GET['compression']);
	$messages = '
	<div class="messagebox fade-message">
		<h2>
			';
	if ($compression_handler == 'no') {
		$messages .= (gettext('Restore completed'));
	} else {
		$messages .= sprintf(gettext('Restore completed using %s compression'), html_encode($compression_handler));
	}
	$messages .= '
		</h2>
	</div>
	';
}
?>


<body>
	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<?php npgFilters::apply('admin_note', 'backkup', ''); ?>
			<h1>
				<?php
				if ($_current_admin_obj->reset) {
					echo (gettext('Restore your Database'));
				} else {
					echo (gettext('Backup and Restore your Database'));
				}
				?>
			</h1>
			<div class="tabbox">
				<?php
				echo $messages;
				$compression_level = getOption('backup_compression');
				?>
				<p>
					<?php printf(gettext("Database software <strong>%s</strong>"), DATABASE_SOFTWARE); ?><br />
					<?php printf(gettext("Database name <strong>%s</strong>"), db_name()); ?><br />
					<?php printf(gettext("Tables prefix <strong>%s</strong>"), trim(prefix(), '`')); ?>
				</p>
				<br />
				<div>
					<?php
					if (!$_current_admin_obj->reset) {
						?>
						<form name="backup_gallery" method="post" action="?tab=backup&action=backup">
							<?php XSRFToken('backup'); ?>
							<input type="hidden" name="tab" value="backup" />
							<input type="hidden" name="backup" value="true" />

							<h1>
								<?php echo gettext('Database backup'); ?>
							</h1>

							<?php echo gettext('Compression level'); ?> <select name="compress">
								<?php
								for ($v = 0; $v <= 9; $v++) {
									?>
									<option value="<?php echo $v; ?>"<?php if ($compression_level == $v) echo ' selected="selected"'; ?>><?php echo $v; ?></option>
									<?php
								}
								?>
							</select>
							<br class="clearall">
							<br />
							<div class="buttons pad_button" id="dbbackup">
								<button class="fixedwidth tooltip" type="submit" title="<?php echo gettext("Backup the tables in your database."); ?>">
									<?php echo BURST_BLUE; ?>
									<?php echo gettext("Backup the Database"); ?>
								</button>
							</div>
							<br class="clearall">
							<br />
							<p>
								<?php
								printf(gettext('The backup facility creates database snapshots in the <code>%1$s</code> folder of your installation. These backups are named in according to the date and time the backup was taken. ' .
																'The compression level goes from 0 (no compression) to 9 (maximum compression). Higher compression requires more processing and may not result in much space savings.'), BACKUPFOLDER);
								?>
							</p>
						</form>
						<br class="clearall">
						<br />
					</div>
					<div>
						<h1>
							<?php echo gettext('Database restoration'); ?>
						</h1>
						<?php
					}
					$filelist = safe_glob(SERVERPATH . "/" . BACKUPFOLDER . '/*.zdb');
					if (count($filelist) <= 0) {
						echo gettext('You have not yet created a backup set.');
					} else {
						$curdir = getcwd();
						chdir(SERVERPATH . "/" . BACKUPFOLDER);
						$filelist = safe_glob('*.zdb');
						$list = array('' => NULL);
						foreach ($filelist as $file) {
							$file = str_replace('.zdb', '', $file);
							$list[] = filesystemToInternal($file);
						}
						chdir($curdir);
						?>
						<form name="restore_gallery" method="post" action="?tab=backup&action=restore">
							<input type="hidden" name="tab" value="backup" />
							<?php XSRFToken('restore'); ?>
							<?php echo gettext('Select the database restore file:'); ?>
							<br />
							<select id="backupfile" name="backupfile" onchange="$('#restore_button').prop('disabled', false)">
								<?php generateListFromArray(array(''), $list, true, false);
								?>
							</select>
							<input type="hidden" name="restore" value="true" />
							<br />
							<br />
							<span class="nowrap">
								<?php
								echo gettext('Select the tables to restore.');
								?>
								<label>
									<input type="checkbox" name="all" id="checkAllAuto" value="1" checked="checked" onclick="$('.checkAuto').prop('checked', $('#checkAllAuto').prop('checked'));" /><?php echo gettext('all'); ?>
								</label>
							</span>
							<br />
							<div style="max-width: 750px;">
								<p>
									<?php
									foreach (unserialize(file_get_contents(CORE_SERVERPATH . 'databaseTemplate')) as $table => $row) {
										?>
										<span class="nowrap">
											<label>
												<input type="checkbox" class="checkAuto" name="restore_<?php echo $table; ?>" value="1" checked="checked" /><?php echo $table; ?>
											</label>
										</span>
										<?php
									}
									?>
								</p>
							</div>

							<div class="buttons pad_button" id="dbrestore">
								<button id="restore_button" class="fixedwidth tooltip" type="submit" title="<?php echo gettext("Restore the tables in your database from a previous backup."); ?>" disabled="disabled">
									<?php echo CURVED_UPWARDS_AND_RIGHTWARDS_ARROW_BLUE; ?>
									<?php echo gettext("Restore the Database"); ?>
								</button>
							</div>
							<br class="clearall">
							<br />
							<p class="notebox">
								<?php
								echo gettext('<strong>Note:</strong> Each database table is emptied before the restore is attempted. After a successful restore the database will be in the same state as when the backup was created.');
								?>
							</p>
							<p>
								<?php
								echo gettext('Ideally a restore should be done only on the same version on which the backup was created. If you are intending to upgrade, first do the restore on the version you were running, then install the new version. If this is not possible the restore can still be done, but if the database fields have changed between versions, data from changed fields will not be restored.');
								?>
							</p>
							<br class="clearall">
						</form>
						<?php
					}
					?>
				</div>
			</div><!--content -->
			<?php printAdminFooter();
			?>
		</div><!-- main -->
</body>
<?php echo "</html>"; ?>
