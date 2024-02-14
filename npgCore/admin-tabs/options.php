<?php
/**
 * provides the Options tab of admin
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 */
// force UTF-8 Ø

define('OFFSET_PATH', 1);
require_once(dirname(__DIR__) . '/admin-globals.php');
require_once(CORE_SERVERPATH . 'lib-config.php');
require_once(PLUGIN_SERVERPATH . 'tag_suggest.php');

if (isset($_GET['tab'])) {
	$_admin_subtab = sanitize($_GET['tab'], 3);
} else {
	if (isset($_POST['saveoptions'])) {
		$_admin_subtab = sanitize($_POST['saveoptions'], 3);
	} else {
		$_admin_subtab = 'general';
	}
}
if (file_exists(CORE_SERVERPATH . 'admin_options/' . $_admin_subtab . '.php')) {
	require_once(CORE_SERVERPATH . 'admin_options/' . $_admin_subtab . '.php');

	admin_securityChecks($optionRights, currentRelativeURL());

	/* handle posts */
	if (isset($_GET['action'])) {
		$action = sanitize($_GET['action']);
		$themeswitch = false;
		if ($action == 'saveoptions') {
			XSRFdefender('saveoptions');

			list($returntab, $notify, $themealbum, $themename, $failed) = saveOptions();

			/*			 * * custom options ** */
			if (!$failed) { // was really a save.
				$returntab = processCustomOptionSave($returntab, $themename, $themealbum);
			}

			if (empty($notify)) {
				$notify = '?saved';
			}
			header("Location: " . $notify . $returntab);
			exit();
		}
	}
	printAdminHeader('options');
	?>
	<script src='<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/js/spectrum/spectrum.js'></script>
	<link rel='stylesheet' href='<?php echo WEBPATH . '/' . CORE_FOLDER; ?>/js/spectrum/spectrum.css' />
	<?php
	$table = NULL;

	npgFilters::apply('texteditor_config', 'forms');
	npg_Authority::printPasswordFormJS();
	?>
	<script>

		var table;

		function setColumns() {
			var columns = Math.floor($('#columns').width() / table);
			if (columns < 2) {
				$('.breakpoint').removeClass('columnbreak');
			} else {
				$('.breakpoint').addClass('columnbreak');
			}
			$('#columns').css('column-count', columns);
		}

		$(window).resize(function () {
			setColumns();
		});

		window.addEventListener('load', function () {
			table = Math.round($('#npgOptions').width());
			$('.colwidth').width(table);
			setColumns();
		}, false);

	</script>
	</head>
	<body>
		<?php printLogoAndLinks(); ?>
		<div id ="main">
			<?php printTabs(); ?>
			<div id="content">
				<?php
				/* Page code */
				$subtab = getCurrentTab();
				$name = getTabName('options', $subtab);
				switch ($subtab) {
					case 'plugin':
						if (isset($_GET['single'])) {
							$name = '<i>' . $_GET['single'] . '</i> ';
						}
						break;
					case 'theme':
						if (isset($_GET['optiontheme'])) {
							$name = '<i>' . $_GET['optiontheme'] . '</i> ';
						}
						break;
				}
				npgFilters::apply('admin_note', 'options', $subtab);
				?>
				<h1>
					<?php
					printf(gettext('%1$s options'), $name);
					?>
				</h1>
				<?php
				if (isset($_GET['tag_parse_error'])) {
					echo '<div class="errorbox fade-message">';
					echo "<h2>";
					if ($_GET['tag_parse_error'] === '0') {
						echo gettext("Forbidden tag.");
					} else {
						echo gettext("Your Allowed tags change did not parse successfully.");
					}
					echo "</h2>";
					echo '</div>';
				}
				if (isset($_GET['post_error'])) {
					echo '<div class="errorbox">';
					echo "<h2>" . gettext('Error') . "</h2>";
					echo gettext('The form submission is incomplete. Perhaps the form size exceeds configured server or browser limits.');
					echo '</div>';
				}
				if (isset($_GET['saved'])) {
					echo '<div class="messagebox fade-message">';
					echo "<h2>" . gettext("Applied") . "</h2>";
					echo '</div>';
				}
				if (isset($_GET['custom'])) {
					echo '<div class="errorbox">';
					echo '<h2>' . html_encode(sanitize($_GET['custom'])) . '</h2>';
					echo '</div>';
				}
				if (isset($_GET['missing'])) {
					echo '<div class="errorbox">';
					echo '<h2>' . gettext('Your browser did not post all the fields. Some options may not have been set.') . '</h2>';
					echo '</div>';
				}
				if (isset($_GET['maxsize'])) {
					echo '<div class="errorbox">';
					echo '<h2>' . gettext('Maximum image size must be greater than zero.') . '</h2>';
					echo '</div>';
				}

				if (isset($_GET['mismatch'])) {
					echo '<div class="errorbox fade-message">';
					switch ($_GET['mismatch']) {
						case 'user':
							echo "<h2>" . sprintf(gettext("You must supply a password for the <em>%s</em> guest user"), html_encode(ucfirst($subtab))) . "</h2>";
							break;
						default:
							echo "<h2>" . gettext('Your passwords did not match.') . "</h2>";
							break;
					}
					echo '</div>';
				}

				if (isset($_GET['cookiepath']) && getNPGCookie('cookie_path') != getOption('cookie_path')) {
					setOption('cookie_path', NULL);
					?>
					<div class="errorbox">
						<h2><?php echo gettext('The path you selected resulted in cookies not being retrievable. It has been reset.'); ?></h2>
					</div>
					<?php
				}

				getOptionContent();
				?>

			</div><!-- end of content -->
			<?php printAdminFooter(); ?>
		</div><!-- end of main -->
	</body>
	</html>

	<?php
}