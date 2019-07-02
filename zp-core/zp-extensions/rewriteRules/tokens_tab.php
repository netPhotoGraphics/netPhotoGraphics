<?php
/**
 * This is the "tokens" upload tab
 *
 * @package plugins/rewriteTokens
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/admin-globals.php');
admin_securityChecks(ADMIN_RIGHTS, $return = currentRelativeURL());
printAdminHeader('development', gettext('rewriteTokens'));

echo "\n</head>";

$_definitions = array();
?>

<body>

	<?php printLogoAndLinks(); ?>
	<div id="main">
		<?php printTabs(); ?>
		<div id="content">
			<div id="container">
				<?php
				npgFilters::apply('admin_note', 'development', '');
				?>
				<h1>
					<?php
					echo gettext('Rewrite Tokens');
					?>
				</h1>				<div class="tabbox">
					<dl class="code">
						<?php
						foreach ($_conf_vars['special_pages'] as $page => $element) {
							if (array_key_exists('define', $element) && $element['define']) {
								$_definitions[$element['define']] = strtr($element['rewrite'], $_definitions);
								?>
								<dt>
									<?php
									echo $element['define'];
									?>
								</dt>
								<dd>
									<?php
									echo strtr($element['rewrite'], $_definitions);
									?>
								</dd>
								<?php
							}
						}
						?>
					</dl>
				</div>
			</div>
		</div>
		<?php printAdminFooter(); ?>
	</div>
</body>
</html>
