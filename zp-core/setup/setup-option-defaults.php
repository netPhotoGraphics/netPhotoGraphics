<?php
// force UTF-8 Ø

/**
 * stores all the default values for options
 *
 * @author Stephen Billard (sbillard)
 *
 * @package setup
 */
setupLog(gettext('Set default options'), true);


$deprecatedPlugins = array('filterDoc', 'zenphoto_package');
$setOptions = getOptionList();

if (isset($_GET['debug'])) {
	$debug = '&debug';
} else {
	$debug = '';
}
if (defined('TEST_RELEASE') && TEST_RELEASE || strpos(getOption('markRelease_state'), '-DEBUG') !== false) {
	$fullLog = '&fullLog';
} else {
	$fullLog = false;
}

require(SERVERPATH . '/' . DATA_FOLDER . '/' . CONFIGFILE);

$testFile = SERVERPATH . '/' . DATA_FOLDER . '/' . internalToFilesystem('charset_tést');
if (!file_exists($testFile)) {
	file_put_contents($testFile, '');
}

foreach ($deprecatedPlugins as $remove) {
	npgFunctions::removeDir(SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/' . $remove);
	@unlink(SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/' . $remove . '.php');
}

$salt = 'abcdefghijklmnopqursuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789~!@#$%^&*()_+-={}[]|;,.<>?/';
$list = range(0, strlen($salt) - 1);
if (!isset($setOptions['extra_auth_hash_text'])) {
// setup a hash seed
	$auth_extratext = "";
	shuffle($list);
	for ($i = 0; $i < 30; $i++) {
		$auth_extratext = $auth_extratext . $salt{$list[$i]};
	}
	setOptionDefault('extra_auth_hash_text', $auth_extratext);
}
if (!isset($setOptions['secret_key_text'])) {
	$auth_extratext = "";
	shuffle($list);
	for ($i = 0; $i < 30; $i++) {
		$auth_extratext = $auth_extratext . $salt{$list[$i]};
	}
	setOptionDefault('secret_key_text', $auth_extratext);
}
if (!isset($setOptions['secret_init_vector'])) {
	$auth_extratext = "";
	shuffle($list);
	for ($i = 0; $i < 30; $i++) {
		$auth_extratext = $auth_extratext . $salt{$list[$i]};
	}
	setOptionDefault('secret_init_vector', $auth_extratext);
}
purgeOption('adminTagsTab', 0);

//	if your are installing, you must be OK
if ($_current_admin_obj) {
	$_current_admin_obj->setPolicyAck(1);
	$_current_admin_obj->save();
}

/* fix for NULL theme name */
Query('UPDATE ' . prefix('options') . ' SET `theme`="" WHERE `theme` IS NULL');

/* fix the admin_to_object table. type=news should have been type=news_categories */
$sql = 'UPDATE ' . prefix('admin_to_object') . ' SET `type`="news_categories" WHERE `type`="news"';
query($sql);

$sql = 'SELECT * FROM ' . prefix('options') . ' WHERE `theme`="" AND `creator` LIKE "themes/%";';
$result = query_full_array($sql);
foreach ($result as $row) {
	$elements = explode('/', $row['creator']);
	$theme = $elements[1];
	$sql = 'UPDATE ' . prefix('options') . ' SET `theme`=' . db_quote($theme) . ' WHERE `id`=' . $row['id'] . ';';
	if (!query($sql, false)) {
		$rslt = query('DELETE FROM ' . prefix('options') . ' WHERE `id`=' . $row['id'] . ';');
	}
}

//migrate plugin enables removing "zp" from name
$sql = 'SELECT * FROM ' . prefix('options') . ' WHERE `name` LIKE "zp\_plugin\_%"';
$result = query($sql);
while ($row = db_fetch_assoc($result)) {
	$sql = 'UPDATE ' . prefix('options') . ' SET `name`=' . db_quote(substr($row['name'], 2)) . ' WHERE `id`=' . $row['id'];
	if (!query($sql, false)) {
// the plugin has executed defaultExtension() which has set the _plugin_ option already
		$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `id`=' . $row['id'];
		query($sql);
	}
}
//clean up plugin creator field
$sql = 'UPDATE ' . prefix('options') . ' SET `creator`=' . db_quote(CORE_FOLDER . '/setup/setup-option-defaults.php[' . __LINE__ . ']') . ' WHERE `name` LIKE "\_plugin\_%" AND `creator` IS NULL;';
query($sql);

//clean up tag list quoted strings
$sql = 'SELECT * FROM ' . prefix('tags') . ' WHERE `name` LIKE \'"%\' OR `name` LIKE "\'%"';
$result = query($sql);
if ($result) {
	while ($row = db_fetch_assoc($result)) {
		$sql = 'UPDATE ' . prefix('tags') . ' SET `name`=' . db_quote(trim($row['name'], '"\'')) . ' WHERE `id`=' . $row['id'];
		if (!query($sql, false)) {
			$oldtag = $row['id'];
			$sql = 'DELETE FROM ' . prefix('tags') . ' WHERE `id`=' . $oldtag;
			query($sql);
			$sql = 'SELECT * FROM ' . prefix('tags') . ' WHERE `name`=' . db_quote(trim($row['name'], '"\''));
			$row = query_single_row($sql);
			if (!empty($row)) {
				$sql = 'UPDATE ' . prefix('obj_to_tag') . ' SET `tagid`=' . $row['id'] . ' WHERE `tagid`=' . $oldtag;
			}
		}
	}
}

//migrate favorites data
$all = query_full_array('SELECT * FROM ' . prefix('plugin_storage') . ' WHERE `type`="favoritesHandler" AND `subtype` IS NULL');
foreach ($all as $aux) {
	$instance = getSerializedArray($aux['aux']);
	if (isset($instance[1])) {
		query('UPDATE ' . prefix('plugin_storage') . ' SET `subtype`="named" WHERE `id`=' . $aux['id']);
	}
}

//migrate "publish" dates
foreach (array('albums', 'images', 'news', 'pages') as $table) {
	$sql = 'UPDATE ' . prefix($table) . ' SET `publishdate`=NULL WHERE `publishdate` ="0000-00-00 00:00:00"';
	query($sql);
	$sql = 'UPDATE ' . prefix($table) . ' SET `expiredate`=NULL WHERE `expiredate` ="0000-00-00 00:00:00"';
	query($sql);
	$sql = 'UPDATE ' . prefix($table) . ' SET `publishdate`=`date` WHERE `publishdate` IS NULL AND `show`="1"';
	query($sql);
}
foreach (array('news', 'pages', 'images', 'albums') as $table) {
	$sql = 'UPDATE ' . prefix($table) . ' SET `lastchange`=`date` WHERE `lastchange` IS NULL';
	query($sql);
	$sql = 'UPDATE ' . prefix($table) . ' SET `lastchangeuser`=`owner` WHERE `lastchangeuser` IS NULL';
	query($sql);
}
// published albums where both the `publishdate` and the `date` were NULL
$sql = 'SELECT `mtime`,`id` FROM ' . prefix('albums') . ' WHERE `publishdate` IS NULL AND `show`="1"';
$result = query($sql);
if ($result) {
	while ($row = db_fetch_assoc($result)) {
		$sql = 'UPDATE ' . prefix('albums') . ' SET `publishdate`=' . db_quote(date('Y-m-d H:i:s', $row['mtime'])) . ' WHERE `id`=' . $row['id'];
		query($sql);
	}
}
//	fix empty sort_order
foreach (array('news_categories', 'pages', 'images', 'albums', 'menu') as $table) {
	$sql = 'UPDATE ' . prefix($table) . ' SET `sort_order`="000" WHERE (`sort_order` IS NULL OR `sort_order`="")';
	query($sql);
}

//migrate rotation and GPS data
$result = db_list_fields('images');

$where = '';
if (isset($result['EXIFOrientation'])) {
	$where = ' OR (`rotation` IS NULL AND `EXIFOrientation`!="")';
}
if (isset($result['EXIFGPSLatitude'])) {
	$where .= ' OR (`GPSLatitude` IS NULL AND NOT `EXIFGPSLatitude` IS NULL)';
} else if (isset($result['EXIFGPSLongitude'])) {
	$where .= ' OR (`GPSLongitude` IS NULL AND NOT `EXIFGPSLongitude` IS NULL)';
} else if (isset($result['EXIFGPSAltitude'])) {
	$where .= ' OR (`GPSAltitude` IS NULL AND NOT `EXIFGPSAltitude` IS NULL)';
}
$where = ltrim($where, ' OR ');

if (!empty($where)) {
	$sql = 'SELECT `id` FROM ' . prefix('images') . ' WHERE ' . $where;
	$result = query($sql);
	while ($row = db_fetch_assoc($result)) {
		$img = getItemByID('images', $row['id']);
		if ($img) {
			foreach (array('EXIFGPSLatitude', 'EXIFGPSLongitude') as $source) {
				$data = $img->get($source);
				if (!empty($data)) {
					if (in_array(strtoupper($img->get($source . 'Ref')), array('S', 'W'))) {
						$data = -$data;
					}
					$img->set(substr($source, 4), $data);
				}
			}
			$alt = $img->get('EXIFGPSAltitude');
			if (!empty($alt)) {
				if ($img->get('EXIFGPSAltitudeRef') == '-') {
					$alt = -$alt;
				}
				$img->set('GPSAltitude', $alt);
			}
			$img->set('rotation', substr(trim($img->get('EXIFOrientation'), '!'), 0, 1));
			$img->save();
		}
	}
	db_free_result($result);
}

//	cleanup option mutexes
$list = safe_glob(SERVERPATH . '/' . DATA_FOLDER . '/' . MUTEX_FOLDER . '/oP*');
foreach ($list as $file) {
	unlink($file);
}

setOptionDefault('galleryToken_link', '_PAGE_/gallery');
setOptionDefault('gallery_data', NULL);
setOptionDefault('strong_hash', 9);

$old = @unserialize(getOption('netphotographics_install'));
$from = preg_replace('/\[.*\]/', '', @$old['NETPHOTOGRAPHICS']);
purgeOption('netphotographics_install');
setOption('netphotographics_install', serialize(installSignature()));

$questions[] = getSerializedArray(getAllTranslations("What is your father’s middle name?"));
$questions[] = getSerializedArray(getAllTranslations("What street did your Grandmother live on?"));
$questions[] = getSerializedArray(getAllTranslations("Who was your favorite singer?"));
$questions[] = getSerializedArray(getAllTranslations("When did you first get a computer?"));
$questions[] = getSerializedArray(getAllTranslations("How much wood could a woodchuck chuck if a woodchuck could chuck wood?"));
$questions[] = getSerializedArray(getAllTranslations("What is the date of the Ides of March?"));
setOptionDefault('challenge_foils', serialize($questions));

$admins = $_authority->getAdministrators('all');
if (empty($admins)) { //	empty administrators table
	$groupsdefined = NULL;
	if (isset($_SESSION['clone'][$cloneid])) { //replicate the user who cloned the install
		$clone = $_SESSION['clone'][$cloneid];
		setOption('UTF8_image_URI', $clone['UTF8_image_URI']);
		setOption('strong_hash', $clone['strong_hash']);
		setOption('extra_auth_hash_text', $clone['hash']);
		setOption('deprecated_functions_signature', $clone['deprecated_functions_signature']);
		setOption('zenphotoCompatibilityPack_signature', $clone['zenphotoCompatibilityPack_signature']);
		if ($clone['mod_rewrite']) {
			$_GET['mod_rewrite'] = true;
			setOption('mod_rewrite', 1);
		}
//	replicate plugins state
		foreach ($clone['plugins'] as $pluginOption => $priority) {
			setOption($pluginOption, $priority);
		}
		$admin_obj = unserialize($_SESSION['admin'][$cloneid]);
		$admindata = $admin_obj->getData();
		$myadmin = new npg_Administrator($admindata['user'], 1);
		unset($admindata['id']);
		unset($admindata['user']);
		foreach ($admindata as $key => $value) {
			$myadmin->set($key, $value);
		}
		$myadmin->save();
		npg_Authority::logUser($myadmin);
		$_loggedin = ALL_RIGHTS;
		setOption('license_accepted', NETPHOTOGRAPHICS_VERSION);
		unset($_SESSION['clone'][$cloneid]);
		unset($_SESSION['admin'][$cloneid]);
	} else {
		if (npg_Authority::$preferred_version > ($oldv = getOption('libauth_version'))) {
			if (empty($oldv)) {
//	The password hash of these old versions did not have the extra text.
//	Note: if the administrators table is empty we will re-do this option with the good stuff.
				purgeOption('extra_auth_hash_text');
				setOptionDefault('extra_auth_hash_text', '');
			} else {
				$msg = sprintf(gettext('Migrating lib-auth data version %1$s => version %2$s '), $oldv, npg_Authority::$preferred_version);
				if (!$_authority->migrateAuth(npg_Authority::$preferred_version)) {
					$msg .= ': ' . gettext('failed');
				}
				echo $msg;
				setupLog($msg, true);
			}
		} if (function_exists('hash')) {
			setOption('strong_hash', 3);
		} else {
			setOption('strong_hash', 1);
		}
		purgeOption('extra_auth_hash_text');
	}
} else {
	if (function_exists('hash') && getOption('strong_hash') == 2) {
		setOption('strong_hash', 3);
	}
	$groupsdefined = @unserialize(getOption('defined_groups'));
}
purgeOption('defined_groups');

// old configuration opitons. preserve them
$conf = $_conf_vars;

$showDefaultThumbs = array();
foreach (getOptionsLike('album_tab_default_thumbs_') as $option => $value) {
	if ($value) {
		$tab = str_replace('album_tab_default_thumbs_', '', $option);
		if (empty($tab))
			$tab = '*';
		$showDefaultThumbs[$tab] = $tab;
	}
	purgeOption($option);
}
setOptionDefault('album_tab_showDefaultThumbs', serialize($showDefaultThumbs));

$showDefaultThumbs = getSerializedArray(getOption('album_tab_showDefaultThumbs'));
foreach ($showDefaultThumbs as $key => $value) {
	if (!file_exists(getAlbumFolder() . $value)) {
		unset($showDefaultThumbs[$key]);
	}
}
setOption('album_tab_showDefaultThumbs', serialize($showDefaultThumbs));

setOptionDefault('time_zone', date('T'));
purgeOption('mod_rewrite');
$sfx = getOption('mod_rewrite_image_suffix');
if ($sfx) {
	purgeOption('mod_rewrite_image_suffix');
} else {
	$sfx = '.htm';
}
setOptionDefault('mod_rewrite_suffix', $sfx);
setOptionDefault('dirtyform_enable', 2);
?>
<script type="text/javascript">
	$(function () {
		$('img').on("error", function () {
			var link = $(this).attr('src');
			var title = $(this).attr('title');
			$(this).parent().html('<a href="' + link + '" target="_blank" title="' + title + '"><?php echo CROSS_MARK_RED; ?></a>');
			imageErr = true;
			$('#setupErrors').val(1);
		});
	});
</script>
<?php
purgeOption('mod_rewrite_detected');

//	Update the root index.php file so admin mod_rewrite works
//	Note: this must be done AFTER the mod_rewrite_suffix option is set and before we test if mod_rewrite works!
$rootupdate = updateRootIndexFile();


if (isset($_GET['mod_rewrite'])) {
	?>
	<p>
		<?php echo gettext('Mod_Rewrite check:'); ?>
		<br />
		<span>
			<img src="<?php echo FULLWEBPATH . '/' . CORE_PATH ?>/setup/setup_set-mod_rewrite" title="<?php echo gettext('Mod_rewrite'); ?>" alt="<?php echo gettext('Mod_rewrite'); ?>" height="16px" width="16px" />
		</span>
	</p>
	<?php
}
setOptionDefault('UTF8_image_URI_found', 'unknown');
if (isset($_POST['setUTF8URI'])) {
	setOption('UTF8_image_URI_found', sanitize($_POST['setUTF8URI']));
	if ($_POST['setUTF8URI'] == 'unknown') {
		setupLog(gettext('Setup could not find a configuration that allows image URIs containing diacritical marks.'), true);
		setOptionDefault('UTF8_image_URI', 1);
	} else {
		setOptionDefault('UTF8_image_URI', (int) ( $_POST['setUTF8URI'] == 'internal'));
	}
}
setOptionDefault('unique_image_prefix', NULL);

setOptionDefault('charset', "UTF-8");
setOptionDefault('image_quality', 85);
setOptionDefault('thumb_quality', 75);
setOptionDefault('last_garbage_collect', time());
setOptionDefault('cookie_persistence', 5184000);
setOptionDefault('cookie_path', WEBPATH);

setOptionDefault('search_password', '');
setOptionDefault('search_hint', NULL);

setOptionDefault('backup_compression', 0);
setOptionDefault('license_accepted', 0);

setOptionDefault('protected_image_cache', NULL);
setOptionDefault('secure_image_processor', NULL);

setoptionDefault('image_allow_upscale', NULL);
setoptionDefault('image_cache_suffix', NULL);
setoptionDefault('image_sharpen', NULL);
setoptionDefault('image_interlace', NULL);
setOptionDefault('thumb_sharpen', NULL);
setOptionDefault('use_embedded_thumb', NULL);


setOptionDefault('watermark_image', 'watermarks/watermark.png');
if (getOption('perform_watermark')) {
	$v = str_replace('.png', "", basename(getOption('watermark_image')));
} else {
	$v = NULL;
}
setoptionDefault('fullimage_watermark', $v);

setOptionDefault('pasteImageSize', NULL);
setOptionDefault('watermark_h_offset', 90);
setOptionDefault('watermark_w_offset', 90);
setOptionDefault('watermark_scale', 5);
setOptionDefault('watermark_allow_upscale', 1);
setOptionDefault('perform_video_watermark', 0);
setOptionDefault('ImbedIPTC', NULL);

if (getOption('perform_video_watermark')) {
	$v = str_replace('.png', "", basename(getOption('video_watermark_image')));
	setoptionDefault('Video_watermark', $v);
}

setOptionDefault('hotlink_protection', '1');

setOptionDefault('search_fields', 'title,desc,tags,file,location,city,state,country,content,author');

$style_tags = "abbr =>(class=>() id=>() title =>())\n" .
				"acronym =>(class=>() id=>() title =>())\n" .
				"b => (class=>() id=>() )\n" .
				"blockquote =>(class=>() id=>() cite =>())\n" .
				"br => (class=>() id=>())\n" .
				"code => (class=>() id=>())\n" .
				"em => (class=>() id=>())\n" .
				"i => (class=>() id=>()) \n" .
				"strike => (class=>() id=>())\n" .
				"strong => (class=>() id=>())\n" .
				"sup => (class=>() id=>())\n" .
				"sub => (class=>() id=>())\n"
;

$general_tags = "a => (href =>() title =>() target=>() class=>() id=>() rel=>())\n" .
				"ul => (class=>() id=>())\n" .
				"ol => (class=>() id=>())\n" .
				"li => (class=>() id=>())\n" .
				"p => (class=>() id=>() style=>())\n" .
				"h1=>(class=>() id=>() style=>())\n" .
				"h2=>(class=>() id=>() style=>())\n" .
				"h3=>(class=>() id=>() style=>())\n" .
				"h4=>(class=>() id=>() style=>())\n" .
				"h5=>(class=>() id=>() style=>())\n" .
				"h6=>(class=>() id=>() style=>())\n" .
				"pre=>(class=>() id=>() style=>())\n" .
				"address=>(class=>() id=>() style=>())\n" .
				"span=>(class=>() id=>() style=>())\n" .
				"div=>(class=>() id=>() style=>())\n" .
				"img=>(class=>() id=>() style=>() src=>() title=>() alt=>() width=>() height=>() size=>() srcset=>())\n" .
				"iframe=>(class=>() id=>() style=>() src=>() title=>() width=>() height=>())\n" .
				"figure=>(class=>() id=>() style=>())\n" .
				"figcaption=>(class=>() id=>() style=>())\n" .
				"article=>(class=>() id=>() style=>())\n" .
				"section => (class=>() id=>() style=>())\n" .
				"nav => (class=>() id=>() style=>())\n" .
				"video => (class=>() id=>() style=>() src=>() controls=>() autoplay=>() buffered=>() height=>() width=>() loop=>() muted=>() preload=>() poster=>())\n" .
				"audio => (class=>() id=>() style=>() src=>() controls=>() autoplay=>() buffered=>() height=>() width=>() loop=>() muted=>() preload=>() volume=>())\n" .
				"picture=>(class=>() id=>())\n" .
				"source=>(src=>() scrset=>() size=>() type=>() media=>())\n" .
				"track=>(src=>() kind=>() scrlang=>() label=>() default=>())\n" .
				"table => (class=>() id=>())\n" .
				"caption => (class=>() id=>())\n" .
				"th => (class=>() id=>())\n" .
				"tr => (class=>() id=>())\n" .
				"td => (class=>() id=>() colspan=>())\n" .
				"thead => (class=>() id=>())\n" .
				"tbody => (class=>() id=>())\n" .
				"tfoot => (class=>() id=>())\n" .
				"colgroup => (class=>() id=>())\n" .
				"col => (class=>() id=>())\n"
;
setOption('allowed_tags_default', $style_tags . $general_tags);
setOptionDefault('allowed_tags', $style_tags . $general_tags);
setOptionDefault('style_tags', $style_tags);

setOptionDefault('GDPR_text', getAllTranslations('Check to acknowledge the site <a href="%s">usage policy</a>.'));
setOptionDefault('GDPR_cookie', microtime());

setOptionDefault('full_image_quality', 75);
setOptionDefault('protect_full_image', 'Protected view');

setOptionDefault('locale', '');
setOptionDefault('date_format', '%x');

setOptionDefault('use_lock_image', 1);
setOptionDefault('search_user', '');
setOptionDefault('multi_lingual', 0);
setOptionDefault('tagsort', 0);
setOptionDefault('albumimagesort', 'ID');
setOptionDefault('albumimagedirection', 'DESC');
setOptionDefault('cache_full_image', 0);
setOptionDefault('exact_tag_match', 0);
setOptionDefault('image_max_size', 3000);
setOptionDefault('IPTC_encoding', 'ISO-8859-1');
setOptionDefault('sharpen_amount', 40);
setOptionDefault('sharpen_radius', 0.5);
setOptionDefault('sharpen_threshold', 3);

setOptionDefault('search_space_is_or', 0);
setOptionDefault('search_no_albums', 0);

// default groups
if (!is_array($groupsdefined)) {
	$groupsdefined = array();
}
if (!in_array('administrators', $groupsdefined)) {
	$groupobj = npg_Authority::newAdministrator('administrators', 0);
	$groupobj->setName('group');
	$groupobj->setRights(ALL_RIGHTS);
	$groupobj->set('other_credentials', gettext('Users with full privileges'));
	$groupobj->setValid(0);
	$groupobj->save();
	$groupsdefined[] = 'administrators';
}
if (!in_array('viewers', $groupsdefined)) {
	$groupobj = npg_Authority::newAdministrator('viewers', 0);
	$groupobj->setName('group');
	$groupobj->setRights(NO_RIGHTS | POST_COMMENT_RIGHTS | VIEW_ALL_RIGHTS);
	$groupobj->set('other_credentials', gettext('Users allowed only to view and comment'));
	$groupobj->setValid(0);
	$groupobj->save();
	$groupsdefined[] = 'viewers';
}
if (!in_array('blocked', $groupsdefined)) {
	$groupobj = npg_Authority::newAdministrator('blocked', 0);
	$groupobj->setName('group');
	$groupobj->setRights(0);
	$groupobj->set('other_credentials', gettext('Banned users'));
	$groupobj->setValid(0);
	$groupobj->save();
	$groupsdefined[] = 'blocked';
}
if (!in_array('album managers', $groupsdefined)) {
	$groupobj = npg_Authority::newAdministrator('album managers', 0);
	$groupobj->setName('template');
	$groupobj->setRights(NO_RIGHTS | OVERVIEW_RIGHTS | POST_COMMENT_RIGHTS | VIEW_ALL_RIGHTS | UPLOAD_RIGHTS | COMMENT_RIGHTS | ALBUM_RIGHTS | THEMES_RIGHTS);
	$groupobj->set('other_credentials', gettext('Managers of one or more albums'));
	$groupobj->setValid(0);
	$groupobj->save();
	$groupsdefined[] = 'album managers';
}
if (!in_array('default', $groupsdefined)) {
	$groupobj = npg_Authority::newAdministrator('default', 0);
	$groupobj->setName('template');
	$groupobj->setRights(DEFAULT_RIGHTS);
	$groupobj->set('other_credentials', gettext('Default user settings'));
	$groupobj->setValid(0);
	$groupobj->save();
	$groupsdefined[] = 'default';
}
if (!in_array('newuser', $groupsdefined)) {
	$groupobj = npg_Authority::newAdministrator('newuser', 0);
	$groupobj->setName('template');
	$groupobj->setRights(NO_RIGHTS);
	$groupobj->set('other_credentials', gettext('Newly registered and verified users'));
	$groupobj->setValid(0);
	$groupobj->save();
	$groupsdefined[] = 'newuser';
}
setOption('defined_groups', serialize($groupsdefined)); // record that these have been set once (and never again)

setOptionDefault('AlbumThumbSelect', 1);

setOptionDefault('site_email', "netPhotoGraphics" . $_SERVER['SERVER_NAME']);
setOptionDefault('site_email_name', 'netPhotoGraphics');

setOptionDefault('register_user_notify', 1);
setOptionDefault('zenpage_news_label', getAllTranslations('News'));

setOptionDefault('obfuscate_cache', 0);

//	obsolete plugin cleanup.
$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `name` LIKE "tinymce_tinyzenpage%";';
query($sql);
$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `name` LIKE "tinymce4%";';
query($sql);
$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `name` LIKE "zenpage_combinews%";';
query($sql);
$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `name` LIKE "cycle-slideshow_%_slideshow";';
query($sql);
purgeOption('tinyMCEPresent');
purgeOption('enable_ajaxfilemanager');
purgeOption('zenphoto_theme_list');
purgeOption('spam_filter');
purgeOption('site_upgrade_state');
purgeOption('last_update_check');

foreach (array('albums_per_page', 'albums_per_row', 'images_per_page', 'images_per_row', 'image_size', 'image_use_side', 'thumb_size', 'thumb_crop_width', 'thumb_crop_height', 'thumb_crop', 'thumb_transition') as $option) {
	$sql = 'DELETE FROM ' . prefix('options') . ' WHERE `name`=' . db_quote($option) . ' AND `theme`=""';
	query($sql);
}

foreach (getOptionsLike('logviewed_') as $option => $value) {
	$file = SERVERPATH . '/' . DATA_FOLDER . '/' . str_replace('logviewed_', '', $option) . '.log';
	if (!file_exists($file)) {
		purgeOption($option);
	}
}

//effervescence_plus migration
if (file_exists(SERVERPATH . '/' . THEMEFOLDER . '/effervescence_plus')) {
	if ($_gallery->getCurrentTheme() == 'effervescence_plus') {
		$_gallery->setCurrentTheme('effervescence+');
		$_gallery->save();
	}
	$options = query_full_array('SELECT LCASE(`name`) as name, `value` FROM ' . prefix('options') . ' WHERE `theme`="effervescence_plus"');
	foreach ($options as $option) {
		setThemeOption($option['name'], $option['value'], NULL, 'effervescence+', true);
	}
	npgFunctions::removeDir(SERVERPATH . '/' . THEMEFOLDER . '/effervescence_plus');
}
?>
<p>
	<?php
	setOption('known_themes', serialize(array())); //	reset known themes
	$deprecate = false;
	$themes = array_keys($_gallery->getThemes());
	natcasesort($themes);
	echo gettext('Theme setup:') . '<br />';

	foreach ($themes as $key => $theme) {
		$class = 0;
		if (protectedTheme($theme)) {
			unset($themes[$key]);
		} else {
			$deprecate = true;
			$class = 1;
		}
		?>
		<span>
			<img src="<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/setup/setup_themeOptions.php?theme=' . urlencode($theme) . $debug; ?>&class=<?php echo $class . $fullLog; ?>&from=<?php echo $from; ?>&unique=<?php echo time(); ?>" title="<?php echo $theme; ?>" alt="<?php echo $theme; ?>" height="16px" width="16px" />
		</span>
		<?php
	}
	?>
</p>

<?php
query('DELETE FROM ' . prefix('options') . ' WHERE  `name` ="search_space_is_OR"', false);

if (!file_exists(SERVERPATH . '/favicon.ico')) {
	@copy(CORE_SERVERPATH . 'images/favicon.ico', SERVERPATH . '/favicon.ico');
} else {
	$ico = md5_file(SERVERPATH . '/favicon.ico');
	$ico_L = '2a479b69ab8479876cb5a7e6384e7a85'; //	hash of legacy zenphoto favicon
	$ico_20 = '8eac492afff6cbb0d3f1e4b913baa8a3'; //	hash of zenphoto20 favicon
	if ($ico_L == $ico || $ico_20 == $ico) {
		unlink(SERVERPATH . '/favicon.ico');
		@copy(CORE_SERVERPATH . 'images/favicon.ico', SERVERPATH . '/favicon.ico');
	}
}

setOptionDefault('default_copyright', sprintf(gettext('Copyright %1$u  : %2$s '), date('Y'), $_SERVER["HTTP_HOST"]));
setOptionDefault('fullsizeimage_watermark', getOption('fullimage_watermark'));


$data = getOption('gallery_data');
if ($data) {
	$data = getSerializedArray($data);
	if (isset($data['Gallery_description'])) {
		$data['Gallery_description'] = getSerializedArray($data['Gallery_description']);
	}
	if (isset($data['gallery_title'])) {
		$data['gallery_title'] = getSerializedArray($data['gallery_title']);
	}
	if (isset($data['unprotected_pages'])) {
		$data['unprotected_pages'] = getSerializedArray($data['unprotected_pages']);
	}
} else {
	$data = array();
}

if (!isset($data['gallery_sortdirection'])) {
	$data['gallery_sortdirection'] = (int) getOption('gallery_sortdirection');
}
if (!isset($data['gallery_sorttype'])) {
	$data['gallery_sorttype'] = getOption('gallery_sorttype');
	if (empty($data['gallery_sorttype'])) {
		$data['gallery_sorttype'] = 'ID';
	}
}
if (!isset($data['gallery_title'])) {
	$data['gallery_title'] = getOption('gallery_title');
	if (is_null($data['gallery_title'])) {
		gettext($str = "Gallery");
		$data['gallery_title'] = gettext("Gallery");
	}
}
if (!isset($data['Gallery_description'])) {
	$data['Gallery_description'] = getOption('Gallery_description');
	if (is_null($data['Gallery_description'])) {
		$data['Gallery_description'] = gettext('You can insert your Gallery description on the Admin Options Gallery tab.');
	}
}
if (!isset($data['gallery_password']))
	$data['gallery_password'] = getOption('gallery_password');
if (!isset($data['gallery_user']))
	$data['gallery_user'] = getOption('gallery_user');
if (!isset($data['gallery_hint']))
	$data['gallery_hint'] = getOption('gallery_hint');
if (!isset($data['hitcounter'])) {
	$data['hitcounter'] = $result = getOption('Page-Hitcounter-index');
	purgeOption('Page-Hitcounter-index');
}
if (!isset($data['current_theme'])) {
	$data['current_theme'] = getOption('current_theme');
	if (is_null($data['current_theme'])) {
		$data['current_theme'] = 'default';
	}
}
if (!isset($data['website_title']))
	$data['website_title'] = getOption('website_title');
if (!isset($data['website_url']))
	$data['website_url'] = getOption('website_url');
if (!isset($data['gallery_security'])) {
	$data['gallery_security'] = getOption('gallery_security');
	if (is_null($data['gallery_security'])) {
		$data['gallery_security'] = 'public';
	}
}
if (!isset($data['login_user_field']))
	$data['login_user_field'] = getOption('login_user_field');
if (!isset($data['album_use_new_image_date']))
	$data['album_use_new_image_date'] = getOption('album_use_new_image_date');
if (!isset($data['thumb_select_images']))
	$data['thumb_select_images'] = getOption('thumb_select_images');
if (!isset($data['unprotected_pages']))
	$data['unprotected_pages'] = getOption('unprotected_pages');
if ($data['unprotected_pages']) {
	$unprotected = $data['unprotected_pages'];
} else {
	$unprotected = array('register', 'contact');
}

primeOptions(); // get a fresh start
$optionlist = getOptionsLike('gallery_page_unprotected_');
foreach ($optionlist as $key => $option) {
	if ($option) {
		$name = str_replace('gallery_page_unprotected_', '', $key);
		$unprotected[] = $name;
		purgeOption($key);
	}
}
$unprotected = array_unique($unprotected);

if (!isset($data['album_publish'])) {
	$set = getOption('album_default');
	if (is_null($set))
		$set = 1;
	$data['album_publish'] = $set;
}
if (!isset($data['image_publish'])) {
	$set = getOption('image_default');
	if (is_null($set))
		$set = 1;
	$data['image_publish'] = $set;
}
$data['unprotected_pages'] = $unprotected;
if (!isset($data['image_sorttype'])) {
	$set = getOption('image_sorttype');
	if (is_null($set))
		$set = 'Filename';
	$data['image_sorttype'] = $set;
}
if (!isset($data['image_sortdirection'])) {
	$set = getOption('image_sortdirection');
	if (is_null($set))
		$set = 0;
	$data['image_sorttype'] = $set;
}

setOption('gallery_data', serialize($data));
// purge the old versions of these
foreach ($data as $key => $value) {
	purgeOption($key);
}

$_gallery = new Gallery(); // insure we have the proper options instantiated

setOptionDefault('search_cache_duration', 30);
setOptionDefault('cache_random_search', 1);
setOptionDefault('search_within', 1);

setOptionDefault('plugins_per_page', 25);
setOptionDefault('users_per_page', 10);
setOptionDefault('groups_per_page', 10);
setOptionDefault('articles_per_page', 15);
setOptionDefault('debug_log_size', 5000000);
setOptionDefault('imageProcessorConcurrency', 15);
setOptionDefault('search_album_sort_type', 'title');
setOptionDefault('search_album_sort_direction', '');
setOptionDefault('search_image_sort_type', 'title');
setOptionDefault('search_image_sort_direction', '');
setOptionDefault('search_article_sort_type', 'date');
setOptionDefault('search_article_sort_direction', '');
setOptionDefault('search_page_sort_type', 'title');
setOptionDefault('search_page_sort_direction', '');


query('UPDATE ' . prefix('administrators') . ' SET `passhash`=' . ((int) getOption('strong_hash')) . ' WHERE `valid`>=1 AND `passhash` IS NULL');
query('UPDATE ' . prefix('administrators') . ' SET `passupdate`=' . db_quote(date('Y-m-d H:i:s')) . ' WHERE `valid`>=1 AND `passupdate` IS NULL');
setOptionDefault('image_processor_flooding_protection', 1);
setOptionDefault('codeblock_first_tab', 1);
setOptionDefault('GD_FreeType_Path', SERVERPATH . '/' . USER_PLUGIN_FOLDER . '/gd_fonts');

setOptionDefault('theme_head_listparents', 0);
setOptionDefault('theme_head_separator', ' | ');

setOptionDefault('tagsort', 'alpha');
setOptionDefault('languageTagSearch', 1);

$vers = explode('-', NETPHOTOGRAPHICS_VERSION);
$vers = explode('.', $vers[0]);
while (count($vers) < 3) {
	$vers[] = 0;
}
$npg_version = $vers[0] . '.' . $vers[1] . '.' . $vers[2];
$_languages = i18n::generateLanguageList('all');

$unsupported = $disallow = array();
$disallowd = getOptionsLike('disallow_');

foreach ($disallowd as $key => $option) {
	purgeOption($key);
	if ($option) {
		$lang = str_replace('disallow_', '', $key);
		$disallow[$lang] = $lang;
	}
}
setOptionDefault('locale_disallowed', serialize($disallow));

foreach ($_languages as $language => $dirname) {
	if (!empty($dirname) && $dirname != 'en_US') {
		if (!i18n::setLocale($dirname)) {
			$unsupported[$dirname] = $dirname;
		}
	}
}
setOption('locale_unsupported', serialize($unsupported));
i18n::setupCurrentLocale($_setupCurrentLocale_result);

//The following should be done LAST so it catches anything done above
//set plugin default options by instantiating the options interface
$plugins = getPluginFiles('*.php');
$plugins = array_keys($plugins);
?>
<p>
	<?php
//clean up plugins needed for themes and other plugins
	$dependentExtensions = array('cacheManager' => 'cacheManager', 'colorbox' => 'colorbox_js');

	foreach ($dependentExtensions as $class => $extension) {
		$key = array_search($extension, $plugins);
		if ($key !== false) {
			$_GET['from'] = $from;
			unset($plugins[$key]);
			list($usec, $sec) = explode(" ", microtime());
			$start = (float) $usec + (float) $sec;
			setupLog(sprintf(gettext('Plugin:%s setup started'), $extension), $fullLog);
			require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/' . $extension . '.php');
			$priority = $plugin_is_filter & PLUGIN_PRIORITY;
			if ($plugin_is_filter & CLASS_PLUGIN) {
				$priority .= ' | CLASS_PLUGIN';
			}
			if ($plugin_is_filter & ADMIN_PLUGIN) {
				$priority .= ' | ADMIN_PLUGIN';
			}
			if ($plugin_is_filter & FEATURE_PLUGIN) {
				$priority .= ' | FEATURE_PLUGIN';
			}
			if ($plugin_is_filter & THEME_PLUGIN) {
				$priority .= ' | THEME_PLUGIN';
			}
			if (extensionEnabled($extension)) {
				enableExtension($extension, $plugin_is_filter);
			}
			setupLog(sprintf(gettext('Plugin:%s enabled (%2$s)'), $extension, $priority), $fullLog);
			new $class;
			setupLog(sprintf(gettext('Plugin:%1$s option interface instantiated (%2$s)'), $extension, $option_interface), $fullLog);
			list($usec, $sec) = explode(" ", microtime());
			$last = (float) $usec + (float) $sec;
			setupLog(sprintf(gettext('Plugin:%1$s setup completed in %2$.4f seconds'), $extension, $last - $start), $fullLog);
		}
	}

	natcasesort($plugins);
	echo gettext('Plugin setup:') . '<br />';
	foreach ($plugins as $key => $extension) {
		$class = 0;
		$path = getPlugin($extension . '.php');
		if (strpos($path, SERVERPATH . '/' . USER_PLUGIN_FOLDER) === 0) {
			if (distributedPlugin($plugin)) {
				unset($plugins[$key]);
			} else {
				$deprecate = true;
				$class = 1;
			}
		} else {
			unset($plugins[$key]);
		}
		?>
		<span>
			<img src="<?php echo FULLWEBPATH . '/' . CORE_FOLDER . '/setup/setup_pluginOptions.php?plugin=' . $extension . $debug; ?>&class=<?php echo $class . $fullLog; ?>&from=<?php echo $from; ?>&unique=<?php echo time(); ?>" title="<?php echo $extension; ?>" alt="<?php echo $extension; ?>" height="16px" width="16px" />
		</span>
		<?php
	}
	?>
</p>

<?php
setOptionDefault('deprecated_functions_signature', NULL);
$compatibilityIs = array('themes' => $themes, 'plugins' => $plugins);

if ($deprecate) {
	require_once(CORE_SERVERPATH . PLUGIN_FOLDER . '/deprecated-functions.php');
	$deprecated = new deprecated_functions();
	$listed = sha1(serialize($deprecated->listed_functions));
	if ($listed != getOption('deprecated_functions_signature')) {
		setOption('deprecated_functions_signature', $listed);
		enableExtension('deprecated-functions', 900 | CLASS_PLUGIN);
		setupLog('<span class="logwarning">' . gettext('There has been a change in function deprecation. The deprecated-functions plugin has been enabled.') . '</span>', true);
	}
	$compatibilityWas = getSerializedArray(getOption('zenphotoCompatibilityPack_signature'));
	if ($compatibilityIs != $compatibilityWas) {
		setOption('zenphotoCompatibilityPack_signature', serialize($compatibilityIs));
		enableExtension('zenphotoCompatibilityPack', 1 | CLASS_PLUGIN);
		setupLog('<span class="logwarning">' . gettext('There has been a change of themes or plugins. The zenphotoCompatibilityPack plugin has been enabled.') . '</span>', true);
	}
}

$_gallery->garbageCollect();

setOption('zenphotoCompatibilityPack_signature', serialize($compatibilityIs));
?>
