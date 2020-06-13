<?php
// force UTF-8 Ø
if (!defined('WEBPATH'))
	die();
?>
<!DOCTYPE html>
<html<?php i18n::htmlLanguageCode(); ?>>
	<head>
		<?php npgFilters::apply('theme_head'); ?>



		<meta name="viewport" content="width=device-width, initial-scale=1">

		<?php
		scriptLoader($_themeroot . '/style.css');
		?>
		<style>
			#loginform {
				text-align:left;
				padding:10px;
				width:365px;
				margin:25px auto;
				margin-top:15%;
				font-size:medium;
			}
			#loginform fieldset {
				padding:10px;
				text-align:left;
			}
			#loginform .show_checkbox {
				float:right;
				position:relative;
				top:0.2em;
				left:-55px;
			}
			#loginform input.textfield {
				margin:0px;
				width:270px;
				font-size:medium;
				padding:4px;
			}
			#loginform .logon_form_text {
				padding:4px;
				text-align:left;
				margin-left:10px;
				margin-left:5px;
			}
			#loginform .logon_link {
				text-align:center;
			}
			#loginform .button {
				padding:5px 10px;
				font-size:medium;
			}
			#loginform button[type] {
				text-decoration:none;
				padding:5px 10px 5px 7px;
				line-height:20px;
			}
		</style>
		<script>
			$(document).bind('mobileinit', function () {
				$.mobile.keepNative = "legend,fieldset,button,input";
				//$.mobile.page.prototype.options.keepNative = "select,input";
			});
		</script>
		<?php
		loadJqueryMobile();
		?>
	</head>

	<body>
		<?php npgFilters::apply('theme_body_open'); ?>

		<div data-role="page" id="mainpage">

			<?php jqm_printMainHeaderNav(); ?>

			<div class="ui-content" role="main">
				<div class="content-primary">
					<?php if (isset($hint)) {
						?>
						<h2><a href="<?php echo getGalleryIndexURL(); ?>">Index</a>
							<?php if (isset($hint)) {
								?>» <strong><strong><?php echo gettext("A password is required for the page you requested"); ?></strong></strong>
								<?php
							}
							?></h2>
						<?php
					}
					?>

					<div id="content-error">
						<div class="errorbox">
							<?php printPasswordForm(isset($hint) ? $hint : NULL, isset($show) ? $show : TRUE, false, isset($hint) ? WEBPATH : NULL); ?>
						</div>
						<?php
						if (!npg_loggedin() && function_exists('printRegisterURL') && $_gallery->isUnprotectedPage('register')) {
							printRegisterURL(gettext('Register for this site'), '<br />');
							echo '<br />';
						}
						?>
					</div>

				</div>

			</div><!-- /content -->
			<?php jqm_printBacktoTopLink(); ?>
			<?php jqm_printFooterNav(); ?>
		</div><!-- /page -->
	</body>
	<?php npgFilters::apply('theme_body_close'); ?>
</html>
