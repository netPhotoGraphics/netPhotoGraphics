<?php
/**
 *
 * Displays a "plugin usage" document based on the plugin's doc comment block.
 *
 * Supports the following PHPDoc markup tags:
 * <code>
 * { @link URL text } text may be empty in which case the link is used as the link text.
 * <i> emphasis
 * <b> strong
 * <var> mono-spaced text
 * <code> code blocks (Note: PHPDocs will create an ordered list of the enclosed text)
 * <hr> horizontal rule
 * <ul><li> bulleted list
 * <ol><li> lists
 * <super></super> superscript
 * <pre>
 * <br> line break
 * </code>
 *
 * NOTE: These apply ONLY to the plugin's document block. Normal string use (e.g. plugin_notices, etc.).
 * should use standard markup.
 *
 * The definitions for folder names and paths are represented by <var>%define%</var> (e.g. <var>%WEBPATH%</var>). The
 * document processor will substitute the actual value for these tags when it renders the document.
 * Image URIs are also processed. Use the appropriate definition tokens to cause the URI to point
 * to the actual image. E.g. <var><img src="%WEBPATH%/%CORE_FOLDER%/images/admin-logo.png" /></var>
 *
 * @author Stephen Billard (sbillard)
 *
 * @package admin
 * @pluginCategory development
 */
// force UTF-8 Ø

global $_CMS;

function processDocBlock($docBlock) {
	global $plugin_author, $plugin_copyright, $plugin_repository, $plugincategory, $plugin_deprecated;
	$markup = array(
			'&amp;gt;' => '>',
			'&amp;lt;' => '<',
			'&amp;percnt;' => '%',
			'&lt;i&gt;' => '<em>',
			'&lt;/i&gt;' => '</em>',
			'&lt;em&gt;' => '<em>',
			'&lt;/em&gt;' => '</em>',
			'&lt;b&gt;' => '<strong>',
			'&lt;/b&gt;' => '</strong>',
			'&lt;strong&gt;' => '<strong>',
			'&lt;/strong&gt;' => '</strong>',
			'&lt;code&gt;' => '<span class="inlinecode">',
			'&lt;/code&gt;' => '</span>',
			'&lt;sup&gt;' => '<span class="superscript">',
			'&lt;/sup&gt;' => '</span>',
			'&lt;hr&gt;' => '<hr />',
			'&lt;ul&gt;' => '<ul>',
			'&lt;/ul&gt;' => '</ul>',
			'&lt;ol&gt;' => '<ol>',
			'&lt;/ol&gt;' => '</ol>',
			'&lt;li&gt;' => '<li>',
			'&lt;/li&gt;' => '</li>',
			'&lt;dl&gt;' => '<dl>',
			'&lt;/dl&gt;' => '</dl>',
			'&lt;dt&gt;' => '<dt><strong>',
			'&lt;/dt&gt;' => '</strong></dt>',
			'&lt;dd&gt;' => '<dd>',
			'&lt;/dd&gt;' => '</dd>',
			'&lt;pre&gt;' => '<pre>',
			'&lt;/pre&gt;' => '</pre>',
			'&lt;br&gt;' => '<br />',
			'&lt;br /&gt;' => '<br />',
			'&lt;var&gt;' => '<span class="inlinecode">',
			'&lt;/var&gt;' => '</span>',
			'&lt;flag&gt;' => '<span class="warningbox">',
			'&lt;/flag&gt;' => '</span>'
	);
	$const_tr = array(
			'%CORE_FOLDER%' => CORE_FOLDER,
			'%PLUGIN_FOLDER%' => PLUGIN_FOLDER,
			'%CORE_PATH%' => CORE_PATH,
			'%PLUGIN_PATH%' => PLUGIN_PATH,
			'%USER_PLUGIN_FOLDER%' => USER_PLUGIN_FOLDER,
			'%USER_PLUGIN_PATH%' => USER_PLUGIN_PATH,
			'%ALBUMFOLDER%' => ALBUMFOLDER,
			'%THEMEFOLDER%' => THEMEFOLDER,
			'%BACKUPFOLDER%' => BACKUPFOLDER,
			'%UTILITIES_FOLDER%' => UTILITIES_FOLDER,
			'%DATA_FOLDER%' => DATA_FOLDER,
			'%CACHEFOLDER%' => CACHEFOLDER,
			'%UPLOAD_FOLDER%' => UPLOAD_FOLDER,
			'%STATIC_CACHE_FOLDER%' => STATIC_CACHE_FOLDER,
			'%FULLWEBPATH%' => FULLWEBPATH,
			'%WEBPATH%' => WEBPATH,
			'%RW_SUFFIX%' => RW_SUFFIX,
			'%GITHUB_ORG%' => GITHUB_ORG,
			'%GITHUB%' => GITHUB,
			'%LOCALE%' => i18n::getUserLocale()
	);
	$body = $doc = '';
	$par = false;
	$empty = false;
	$lines = explode("\n", strtr($docBlock, $const_tr));
	foreach ($lines as $line) {
		$line = trim(preg_replace('~^\s*\*~', '', $line));
		if (empty($line)) {
			if (!$empty) {
				if ($par) {
					$doc .= '</p>';
				}
				$doc .= '<p>';
				$empty = $par = true;
			}
		} else {
			if (strpos($line, '@') === 0) {
				preg_match('/@(.*?)\s/', $line, $matches);
				if (!empty($matches)) {
					switch ($case = strtolower($matches[1])) {
						case 'author':
							$plugin_author = trim(substr($line, 8));
							break;
						case 'plugincategory':
							$plugincategory = trim(substr($line, 16));
							break;
						case 'repository':
						case 'copyright':
							$result = trim(substr($line, strlen($case) + 1));
							preg_match('~{@link(.*)}~', $result, $matches);
							if (!empty($matches)) {
								$line = trim($matches[1]);
								$l = strpos($line, ' ');
								if ($l === false) {
									$text = $line;
								} else {
									$text = substr($line, $l + 1);
									$line = substr($line, 0, $l);
								}
								$result = str_replace($matches[0], '<a href="' . $line . '">' . $text . '</a>', $result);
							}
							$case = 'plugin_' . $case;
							$$case = $result;
							break;
						case 'link':
							$line = trim(substr($line, 5));
							$l = strpos($line, ' ');
							if ($l === false) {
								$text = $line;
							} else {
								$text = substr($line, $l + 1);
								$line = substr($line, 0, $l);
							}
							$links[] = array('text' => $text, 'link' => $line);
							break;
						case 'deprecated':
							preg_match('~.*(deprecated\s+[since\s+[\d+\.]*]*)\s*[and]*(.*)~i', $line, $matches);

							$plugin_deprecated = ucfirst(trim($matches[1])) . '<br />' . ucfirst(trim($matches[2]));
							break;
					}
				}
			} else {
				$tags = array();
				preg_match_all('|<img src="(.*?)"\s*/>|', $line, $matches);
				if (!empty($matches[0])) {
					foreach ($matches[0] as $key => $match) {
						if (!empty($match)) {
							$line = str_replace($match, '%' . $key . '$i', $line);
							$tags['%' . $key . '$i'] = '<img src="' . pathurlencode($matches[1][$key]) . '" alt="" />';
						}
					}
				}
				preg_match_all('|\{@link (.*?)\}|', $line, $matches);
				if (!empty($matches[0])) {
					foreach ($matches[0] as $key => $match) {
						if (!empty($match)) {
							$line = str_replace($match, '%' . $key . '$l', $line);
							$l = strpos($matches[1][$key], ' ');
							if ($l === false) {
								$link = $text = $matches[1][$key];
							} else {
								$text = substr($matches[1][$key], $l + 1);
								$link = substr($matches[1][$key], 0, $l);
							}
							$tags['%' . $key . '$l'] = '<a href="' . html_encode($link) . '">' . strtr(html_encode($text), $markup) . '</a>';
						}
					}
				}
				$line = strtr(html_encode($line), array_merge($tags, $markup));
				$doc .= $line . " \n";
				$empty = false;
			}
		}
	}

	if ($par) {
		$doc .= '</p>';
		$body .= $doc;
		$doc = '';
	}
	return $body;
}

if (!defined('OFFSET_PATH')) {
	define('OFFSET_PATH', 2);
	define('SETUP_PLUGIN', TRUE); //	so the descriptions of class plugins are active
	require_once(__DIR__ . '/admin-globals.php');
	require_once(CORE_SERVERPATH . 'template-functions.php');

	$extension = sanitize($_GET['extension']);
	if (!in_array($extension, array_keys(getPluginFiles('*.php')))) {
		exit();
	}

	header('Content-Type: text/html; charset=' . LOCAL_CHARSET);

	$real_locale = i18n::getUserLocale();

	$pluginType = isset($_GET['type']) ? $_GET['type'] : NULL;
	if ($pluginType) {
		$pluginToBeDocPath = USER_PLUGIN_SERVERPATH . $extension . '.php';
		require_once(PLUGIN_SERVERPATH . 'deprecated-functions.php'); //	just incase
	} else {
		$pluginToBeDocPath = PLUGIN_SERVERPATH . '' . $extension . '.php';
	}
	$plugin_description = '';
	$plugin_notice = '';
	$plugin_disable = '';
	$plugin_author = '';
	$plugin_copyright = '';
	$plugin_repository = '';
	$plugin_version = '';
	$plugin_is_filter = '';
	$plugin_URL = '';
	$option_interface = '';
	$doclink = '';
	$plugin_deprecataed = '';

	require_once($pluginToBeDocPath);

	$macro_params = array($plugin_description, $plugin_notice, $plugin_disable, $plugin_author, $plugin_version, $plugin_is_filter, $plugin_URL, $option_interface, $doclink);

	$buttonlist = npgFilters::apply('admin_utilities_buttons', array());
	foreach ($buttonlist as $key => $button) {
		$buttonlist[$key]['enable'] = false;
	}
	$imagebuttons = preg_replace('/<a href=[^>]*/i', '<a', npgFilters::apply('edit_image_utilities', '', $_missing_image, 0, '', '', ''));
	if (!preg_match('~class\s*=.+button~', $imagebuttons)) {
		$imagebuttons = NULL;
	}
	$albumbuttons = preg_replace('/<a href=[^>]*/i', '<a', npgFilters::apply('edit_album_utilities', ' ', $_missing_album, ''));
	if (!preg_match('~class\s*=.+button~', $albumbuttons)) {
		$albumbuttons = NULL;
	}

	require_once(PLUGIN_SERVERPATH . 'macroList.php');
	list($plugin_description, $plugin_notice, $plugin_disable, $plugin_author, $plugin_version, $plugin_is_filter, $plugin_URL, $option_interface, $doclink) = $macro_params;
	$content_macros = getMacros();
	krsort($content_macros);
	foreach ($content_macros as $macro => $detail) {
		if (!isset($detail['owner']) || $detail['owner'] != $extension) {
			unset($content_macros[$macro]);
		}
	}

	preg_match('~/\*\*(.*?)\*/~s', file_get_contents($pluginToBeDocPath), $matches);
	if (isset($matches[1])) {
		$docBlock = $matches[1];

		$plugincategory = false;
		$body = processDocBlock($docBlock);
		switch ($pluginType) {
			case 'thirdparty':
				$whose = 'Third party plugin';
				$path = stripSuffix($pluginToBeDocPath) . '/logo.png';
				if (file_exists($path)) {
					$ico = '<img class="npg_logoicon" src="' . str_replace(SERVERPATH, WEBPATH, $path) . '" alt="logo" title="<?php echo $whose; ?>" /> ';
				} else {
					$ico = '';
				}
				break;
			case 'supplemental':
				$whose = 'Supplemental plugin';
				$ico = '<img class="npg_logoicon" src="' . WEBPATH . '/' . CORE_FOLDER . '/images/np_blue.png" alt="logo" title="<?php echo $whose; ?>" /> ';
				break;
			default:
				$whose = 'Official plugin';
				$ico = '<img class="npg_logoicon" src="' . WEBPATH . '/' . CORE_FOLDER . '/images/np_gold.png" alt="logo" title="<?php echo $whose; ?>" /> ';
				break;
		}

		$pluginusage = gettext('Plugin usage information');
		$pagetitle = sprintf(gettext('%1$s %2$s: %3$s'), html_encode($_gallery->getTitle()), gettext('admin'), html_encode($extension));
		i18n::setupCurrentLocale('en_US');
		?>
		<!DOCTYPE html>
		<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
			<head>
				<?php printStandardMeta(); ?>
				<title><?php echo $pagetitle; ?></title>
				<?php scriptLoader(CORE_SERVERPATH . 'admin.css'); ?>
				<style>
					#heading {
						height: 15px;
					}
					#plugin-content {
						background-color: #f1f1f1;
						border: 1px solid #CBCBCB;
						padding: 5px;
					}
					.doc_box_field {
						padding-left: 0px;
						padding-right: 5px;
						padding-top: 5px;
						padding-bottom: 5px;
						margin: 15px;
						border: 1px solid #cccccc;
						width: 700px;
					}
					.moc_button {
						display: block;
						float: left;
						width: 200px;
						margin: 0 7px 0 0;
						background-color: #f5f5f5;
						background-image:  linear-gradient(rgb(244,244,244), rgba(237,237,237));
						border: 1px solid #dedede;
						border-top: 1px solid #eee;
						border-left: 1px solid #eee;
						font-family: "Lucida Grande", Tahoma, Arial, Verdana, sans-serif;
						font-size: 100%;
						line-height: 130%;
						text-decoration: none;
						font-weight: bold;
						color: #565656;
						cursor: pointer;
						padding: 5px 10px 6px 7px; /* Links */
					}
					.tip {
						text-align: left;
					}
					dl {
						display: block;
						clear: both;
						width: 100%;
					}
					dt,dd {
						vertical-align: top;
						display: inline-block;
						width: 90%;
						margin: 0;
					}
					dt {
						font-weight: bold;
					}
					dd {
						width: 90%;
						padding-left: 3em;
					}
					ul {
						list-style: bullet;
						padding: 0;
					}
					ol {
						list-style: none;
						padding: 0;
					}
					li {
						margin-left: 1.5em;
						padding-bottom: 0.5em;
					}
					ul.options  {
						list-style: none;
						margin-left: 0;
						padding: 0;
					}
					ul.options li {
						list-style: none;
						margin-left: 1.5em;
						padding-bottom: 0.5em;
					}
					.superscript {
						vertical-align: super;
					}
					.nowrap {
						white-space: nowrap;
					}
				</style>
			</head>
			<body>
				<div id="main">
					<div id="heading">
						<?php
						echo $pluginusage;
						?>
						<div id="google_translate_element" class="floatright"></div>
						<script type="text/javascript">
							function googleTranslateElementInit() {
								new google.translate.TranslateElement({pageLanguage: 'en', layout: google.translate.TranslateElement.InlineLayout.SIMPLE}, 'google_translate_element');
							}
						</script>
						<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
					</div>
					<br class="clearall" />

					<div id="plugin-content">
						<h1><?php echo $ico; ?><?php
							echo html_encode($extension);
							if ($plugincategory) {
								?>
								<em><small>{<?php printf('%1$s plugins', $plugincategory); ?>}</small></em>
								<?php
							}
							?></h1>
						<?php
						if ($plugin_deprecated) {
							?>
							<h3 class="warningbox"><?php echo $plugin_deprecated; ?></h3>
							<?php
						}
						?>
						<div class="border">
							<?php echo $plugin_description; ?>
						</div>
						<?php
						if ($pluginType == 'thirdparty' && $plugin_version) {
							?>
							<h3><?php printf('Version: %s', $plugin_version); ?></h3>
							<?php
						}
						if ($plugin_author) {
							?>
							<h3><?php printf('Author: %s', html_encode($plugin_author)); ?></h3>
							<?php
						}
						if ($plugin_copyright) {
							?>
							<h3><?php echo '© ' . html_encodeTagged($plugin_copyright); ?></h3>
							<?php
						}
						if ($plugin_repository) {
							?>
							<h3><?php echo 'Repository: ' . html_encodeTagged($plugin_repository); ?></h3>
							<?php
						}
						?>
						<div>
							<?php
							if ($plugin_disable) {
								?>
								<div class="warningbox">
									<?php echo $plugin_disable; ?>
								</div>
								<?php
							}
							if ($plugin_notice) {
								?>
								<div class="notebox">
									<?php echo $plugin_notice; ?>
								</div>
								<?php
							}

							echo $body;

							if ($option_interface) {
								if (is_string($option_interface)) {
									$option_interface = new $option_interface;
								}
								$options = $supportedOptions = $option_interface->getOptionsSupported();
								$option = reset($options);
								if (array_key_exists('order', $option)) {
									$options = sortMultiArray($supportedOptions, 'order');
									$options = array_keys($options);
								} else {
									$options = array_keys($supportedOptions);
									sort($options, SORT_NATURAL | SORT_FLAG_CASE);
								}

								foreach ($options as $key => $option) {
									if (array_key_exists($option, $supportedOptions)) {
										$row = $supportedOptions[$option];
										if ($row['type'] == OPTION_TYPE_NOTE) {
											$n = getBare($row['desc']);
											if (!empty($n)) {
												$options[$key] = $n;
											}
										} else {
											if (false !== $i = stripos($option, chr(0))) {
												$option = substr($option, 0, $i);
											}
											if (!$option) {
												unset($options[$key]);
											}
											$options[$key] = '<code>' . $option . '</code>';
										}
									} else {
										unset($options[$key]);
									}
								}
								if (!empty($options)) {
									?>
									<hr />
									<p>
										<?php echo ngettext('Option:', 'Options:', count($options)); ?>
										<ol class="options">
											<?php
											foreach ($options as $option) {
												if (false !== $i = stripos($option, chr(0))) {
													$option = substr($option, 0, $i);
												}
												if ($option) {
													?>
													<li><?php echo $option; ?></li>
													<?php
												}
											}
											?>
										</ol>
									</p>
									<?php
								}
							}
							if (!empty($buttonlist) || !empty($albumbuttons) || !empty($imagebuttons)) {
								?>
								<hr />
								<?php
							}
							if (!empty($buttonlist)) {
								$buttonlist = sortMultiArray($buttonlist, array('category', 'button_text'), false);
								?>
								<div class="box" id="overview-section">
									<h2 class="h2_bordered">Utility functions</h2>
									<?php
									$category = '';
									foreach ($buttonlist as $button) {
										$button_category = isset($button['category']) ? $button['category'] : NULL;
										$button_icon = isset($button['icon']) ? $button['icon'] : NULL;
										if ($category != $button_category) {
											if ($category) {
												?>
												</fieldset>
												<?php
											}
											$category = $button_category;
											?>
											<fieldset class="doc_box_field"><legend><?php echo $category; ?></legend>
												<?php
											}
											?>
											<form class="overview_utility_buttons">
												<div class="moc_button tip" title="<?php if (isset($button['title'])) echo $button['title']; ?>" >
													<?php
													if (!empty($button_icon)) {
														if (strpos($button_icon, 'images/') === 0) {
															// old style icon image
															?>
															<img src="<?php echo $button_icon; ?>" alt="<?php echo html_encode($button['alt']); ?>" />
															<?php
														} else {
															echo $button_icon . ' ';
														}
													}
													if (isset($button['button_text'])) {
														echo html_encode($button['button_text']);
													}
													?>
												</div>
											</form>
											<?php
										}
										if ($category) {
											?>
										</fieldset>
										<?php
									}
									?>
								</div>
								<br class="clearall" />
								<?php
							}
							if ($albumbuttons) {
								$albumbuttons = preg_replace('|<hr(\s*)(/)>|', '', $albumbuttons);
								?>
								<h2 class="h2_bordered_edit">Album Utilities</h2>
								<div class="box-edit">
									<?php echo $albumbuttons; ?>
								</div>
								<br class="clearall" />
								<?php
							}
							if ($imagebuttons) {
								$imagebuttons = preg_replace('|<hr(\s*)(/)>|', '', $imagebuttons);
								?>
								<h2 class="h2_bordered_edit">Image Utilities</h2>
								<div class="box-edit">
									<?php echo $imagebuttons; ?>
								</div>
								<br class="clearall" />
								<?php
							}
							if (!empty($content_macros)) {
								echo ngettext('Macro defined:', 'Macros defined:', count($content_macros));
								foreach ($content_macros as $macro => $detail) {
									unset($detail['owner']);
									macroList_show($macro, $detail);
								}
								?>
								<br class="clearall" />
								<?php
							}
							?>
						</div>
					</div>
				</div>
				<br class="clearall" />
			</body>
			<?php
		}
	}

