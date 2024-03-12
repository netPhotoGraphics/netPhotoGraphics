<?php
/**
 * Support for the jPlayer jQuery/Flash 2.0.0 multimedia player (jplayer.org). It will play natively via HTML5 in capable browser
 * if the appropiate multimedia formats are provided.

 * Audio: <var>.mp3</var>, <var>.m4a</var>, <var>.fla</var> - Counterpart formats <var>.oga</var> and <var>.webma</var> supported (see note below!)<br>
 * Video: <var>.m4v</var>/<var>.mp4</var>, <var>.flv</var> - Counterpart formats <var>.ogv</var> and <var>.webmv</var> supported (see note below!)
 *
 * IMPORTANT NOTE ON OGG AND WEBM COUNTERPART FORMATS:
 *
 * The counterpart formats are not valid formats for netPhotoGraphics itself as that would confuse the management.
 * Therefore these formats can be uploaded via ftp only.
 * The files needed to have the same file name (beware the character case!). In single player usage the player
 * will check via file system if a counterpart file exists.
 * <b>NOTE:</b> Counterpart format does not work correctly on playlists yet. Detailed reason: Priority solution
 * setting must be "flash" as otherwise flv and fla will not work on some browsers like Safari.
 * This in return disables counterpart support for ogg and webm files for some reason on Firefox).
 * Since the flash fallback covers all essential formats this is not much of an issue for visitors though.
 *
 * Otherwise it will not work. It is all or none.
 * See {@link http://jplayer.org/latest/developer-guide/#reference-html5-media the developer guide} for info on that.
 *
 * If you have problems with any format being recognized, you might need to tell your server about the mime types first:
 * See examples on {@link http://jplayer.org/latest/developer-guide/#jPlayer-server-response the jplayer site}.
 *
 * NOTE on POPCORN Support (http://popcornjs.org):
 * jPlayer has support for this interactive libary and its plugin is included but currently not loaded or implemented. You need to customize the plugin or your theme to use it.
 * Please refer to http://jplayer.org/latest/developer-guide/ and http://popcornjs.org to learn about this extra functionality.
 *
 * NOTE ON PLAYER SKINS:<br>
 * The look of the player is determined by a pure HTML/CSS based skin (theme). There may occur display issues with themes.
 * Only the default skins <var>light</var> and <var>dark</var>
 * have been tested with the standard themes (and it does not work perfectly for all)).
 * Those two themes are also have a responsive width.
 * So you might need to adjust the skin yourself to work with your theme.
 *
 * The jplayer <var>Player size</var> options are dynamically determined from the skin css.
 * The plugin matches for style classes named <code>jp-video-<em>xxx</em>p</code> (where <em>xxx</em>
 * is the height.) The format of these classes must conform to the following forms:
 *
 * <code> .jp-video-270p { max-width: 480px; } </code> or <code>.jp-video-360p {	width: 640px; }</code>
 *
 * In the height is determined by the class name and the width is determined by the width element. The width
 * element must be the first element in the definition.
 *
 *
 * <b>NOTE:</b> A skin may have only one CSS file.
 *
 * You should place your custom skins within the root /plugins folder like:
 *
 * plugins/jPlayer/skin/<i>skin name1</i><br>
 * plugins/jPlayer/skin/<i>skin name2</i> ...
 *
 * You can select the skin then via the plugin options.
 *
 * USING PLAYLISTS:<br>
 * You can use <var>printjPlayerPlaylist()</var> on your theme's album.php directly to display a
 * video/audio playlist (default) or an audio only playlist.
 * Alternativly you can show a playlist of a specific album anywhere. In any case you need to modify your theme.
 * See the documentation for the parameter options.
 *
 * Alternativly you can show a playlist of a specific album anywhere. In any case you need to modify your theme.
 * See the documentation for the parameter options.
 *
 * CONTENT MACRO:<br>
 * jPlayer attaches to the content_macro MEDIAPLAYER you can use within normal text of Zenpage pages or articles for example.
 *
 * Usage:
 * [MEDIAPLAYER <albumname> <imagefilename> <number>]
 *
 * Example:
 * [MEDIAPLAYER album1 video.mp4]
 *
 * If you are using more than one player on a page you need to pass a 3rd parameter with for example an unique number:<br>
 * [MEDIAPLAYER album1 video1.mp4 <var>1</var>]<br>
 * [MEDIAPLAYER album2 video2.mp4 <var>2</var>]
 *
 * <b>NOTE:</b> This player does not support external albums!
 *
 * @author Malte Müller (acrylian)
 * @package plugins/jPlayer
 * @pluginCategory media
 */
$plugin_is_filter = defaultExtension(5 | CLASS_PLUGIN);
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext("This plugin handles <code>flv</code>, <code>fla</code>, <code>mp3</code>, <code>mp4</code>, <code>m4v</code>, and <code>m4a</code> multi-media files.");
	gettext("Please see <a href='http://jplayer.org'>jplayer.org</a> for more info about the player and its license.");
	$plugin_disable = npgFunctions::pluginDisable(array(array(!extensionEnabled('class-video'), gettext('This plugin requires the <em>class-video</em> plugin')), array(class_exists('Video') && Video::multimediaExtension() != 'jPlayer' && Video::multimediaExtension() != 'html5Player', sprintf(gettext('jPlayer not enabled, %s is already instantiated.'), class_exists('Video') ? Video::multimediaExtension() : false)), array(getOption('album_folder_class') === 'external', (gettext('This player does not support <em>External Albums</em>.')))));
}

$option_interface = 'jplayer';

require_once(PLUGIN_SERVERPATH . 'class-video.php');

Gallery::addImageHandler('flv', 'Video');
Gallery::addImageHandler('fla', 'Video');
Gallery::addImageHandler('mp3', 'Video');
Gallery::addImageHandler('mp4', 'Video');
Gallery::addImageHandler('m4v', 'Video');
Gallery::addImageHandler('m4a', 'Video');

// theme function wrapper for user convenience
function printjPlayerPlaylist($option = "playlist", $albumfolder = "") {
	global $_multimedia_extension;
	$_multimedia_extension->printjPlayerPlaylist($option, $albumfolder);
}

class jPlayer extends html5Player {

	public $name = 'jPlayer';
	public $width = '';
	public $height = '';
	public $playersize = '';
	public $mode = '';
	public $supplied = '';
	public $supplied_counterparts = '';

	function __construct() {

		if (OFFSET_PATH == 2) {
			$option = getOption('jplayer_skin');
			if (!is_null($option)) {
				setOption('jplayer_skin', str_replace('zenphoto', '', $option));
			}
			setOptionDefault('jplayer_autoplay', '');
			setOptionDefault('jplayer_poster', 1);
			setOptionDefault('jplayer_postercrop', 1);
			setOptionDefault('jplayer_showtitle', '');
			setOptionDefault('jplayer_playlist', '');
			setOptionDefault('jplayer_playlist_numbered', 1);
			setOptionDefault('jplayer_playlist_playtime', 0);
			setOptionDefault('jplayer_download', '');
			setOptionDefault('jplayer_size', 'jp-video-270p');
			setOptionDefault('jplayer_skin', 'light');
			setOptionDefault('jplayer_counterparts', 0);
			/* TODO: what are these sizes?
			  $player = new jPlayer();
			 * if (class_exists('cacheManager')) {
			  cacheManager::deleteCacheSizes('jplayer');
			  cacheManager::addCacheSize('jplayer', NULL, $player->width, $player->height, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
			 * }
			 */
		}

		$skin = self::getSkin();
		$skinCSS = file_get_contents($skin);
		preg_match_all('~\.(jp-video-(\d+)p)\s+\{\s*.*width\:\s*(\d+)px;~', $skinCSS, $matches);
		$which = array_search(getOption('jplayer_size'), $matches[1]);
		$this->playersize = $matches[1][$which]; //	incase the size option is not supported
		$this->width = $matches[3][$which];
		$this->height = $matches[2][$which];
	}

	function getOptionsSupported() {
		$skins = getPluginFiles('*', 'jPlayer/skin/', FALSE, GLOB_ONLYDIR);
		foreach ($skins as $skin => $path) {
			$skins[$skin] = $skin;
		}

		/*
		 * The player size is entirely styled via the CSS skin so there is no free size option.
		 * For audio (without thumb/poster) that is always 480px width.
		 * The original jPlayer skin comes with 270p (480x270px) and 360p (640x360px) sizes for
		 * videos but the custom skin comes with some more like 480p and 1080p.
		 * If you need different sizes than you need to make your own skin (see the skin option for info about that)
		 */


		$skin = self::getSkin();
		$skinCSS = file_get_contents($skin);
		preg_match_all('~\.(jp-video-(\d+)p)\s*\{\s*.*width\:\s*(\d+)px;~', $skinCSS, $matches);
		foreach ($matches[2] as $k => $h) {
			$w = $matches[3][$k];
			$sizeSelections[$w . 'x' . $h . 'px'] = 'jp-video-' . $h . 'p';
		}

		return array(gettext('Autoplay') => array('key' => 'jplayer_autoplay', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext("Disabled automatically if several players on one page")),
				gettext('Poster (Videothumb)') => array('key' => 'jplayer_poster', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext("If the videothumb should be shown (jplayer calls it poster).")),
				gettext('Audio poster (Videothumb)') => array('key' => 'jplayer_audioposter', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext("If the poster should be shown for audio files (mp3,m4a,fla) (does not apply for playlists which are all or none).")),
				gettext('Show title') => array('key' => 'jplayer_showtitle', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext("If the title should be shown below the player in single player mode (not needed on normal themes) (ignored in playlists naturally).")),
				gettext('Playlist support') => array('key' => 'jplayer_playlist', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext("Enable this if you wish to use the playlist mode this loads the scripts needed. NOTE: You have to add the function printjPlayerPlaylist() to your theme yourself. See the documentation for info.")),
				gettext('Playlist numbered') => array('key' => 'jplayer_playlist_numbered', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext("Enable this if you wish the playlist to be numbered.")),
				gettext('Playlist playtime') => array('key' => 'jplayer_playlist_playtime', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext("Enable if you want to show the playtime of playlist entries.")),
				gettext('Enable download') => array('key' => 'jplayer_download', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext("Enables direct file downloads (playlists only).")),
				gettext('Player size') => array('key' => 'jplayer_size', 'type' => OPTION_TYPE_SELECTOR,
						'selections' => $sizeSelections,
						'desc' => gettext("jPlayer is dependent on their HTML and CSS based skin. Sizes marked with a <strong>*</strong> are supported by the two custom skins only (these two skins are also responsive in width). If you need different sizes you need to modify a skin or make your own and also need to change values in the plugin class method getPlayerSize().")),
				gettext('Player skin') => array('key' => 'jplayer_skin', 'type' => OPTION_TYPE_SELECTOR,
						'selections' => $skins,
						'desc' => gettext("Select the skin (theme) to use. <br />NOTE: Since the skin is pure HTML/CSS only there may be display issues with certain themes that require manual adjustments. The two custom skins are responsive regarding the player width. Place custom skin within the root plugins folder. See plugin documentation for more info."))
		);
	}

	static function getMacrojplayer($albumname, $imagename, $count = 1) {
		global $_multimedia_extension;
		$movie = newImage(array('folder' => $albumname, 'filename' => $imagename), true);
		if ($movie->exists) {
			return $_multimedia_extension->getPlayerConfig($movie, NULL, (int) $count);
		} else {
			return '<span class = "error">' . sprintf(gettext('%1$s::%2$s not found.'), $albumname, $imagename) . '</span>';
		}
	}

	static function macro($macros) {
		$macros['MEDIAPLAYER'] = array(
				'class' => 'function',
				'params' => array('string', 'string', 'int*'),
				'value' => 'jplayer::getMacrojplayer',
				'owner' => 'jplayer',
				'desc' => gettext('Provide the album name (%1), media file name (%2) and a unique number (%3). (If there is only player instance on the page the unique number may be omitted.)')
		);
		return $macros;
	}

	static function CSS() {
		$skin = self::getSkin();
		scriptLoader($skin);
	}

	static function JS() {
		scriptLoader(PLUGIN_SERVERPATH . 'jPlayer/js/jquery.jplayer.min.js');
	}

	static function playlistJS() {
		scriptLoader(PLUGIN_SERVERPATH . 'jPlayer/js/jplayer.playlist.min.js');
	}

	static function getSkin() {
		$skins = getPluginFiles('*.css', 'jPlayer/skin/' . getOption('jplayer_skin'));
		$skin = strval(reset($skins));
		if (!file_exists($skin)) {
			$skin = PLUGIN_SERVERPATH . 'jPlayer/skin/light/jplayer.light.css';
		}
		return($skin);
	}

	/**
	 * Get the JS configuration of jplayer
	 *
	 * @param mixed $movie the image object
	 * @param string $movietitle the title of the movie
	 * @param string $count number (preferredly the id) of the item to append to the css for multiple players on one page
	 * @param string $width Not supported as jPlayer is dependend on its CSS based skin to change sizes. Can only be set via plugin options.
	 * @param string $height Not supported as jPlayer is dependend on its CSS based skin to change sizes. Can only be set via plugin options.
	 *
	 */
	function getPlayerConfig($movie, $movietitle = NULL, $count = NULL, $w = NULL, $h = NULL) {
		if (is_null($w)) {
			$w = $this->getWidth();
		}
		if (is_null($h)) {
			$h = $this->getHeight();
		}

		$moviepath = $movie->getImagePath(FULLWEBPATH);
		if (is_null($movietitle)) {
			$movietitle = $movie->getTitle();
		}
		$ext = getSuffix($moviepath);
		if (!in_array($ext, array('m4a', 'm4v', 'mp3', 'mp4', 'flv', 'fla'))) {
			return parent::getPlayerConfig($movie, $movietitle, $count, $w, $h);
		}
		$this->setModeAndSuppliedFormat($ext);
		if (empty($count)) {
			$multiplayer = false;
			$count = '1'; //we need different numbers in case we embed several via tinyZenpage or macros
		} else {
			$multiplayer = true; // since we need extra JS if multiple players on one page
			$count = $count;
		}
		$autoplay = '';
		if (getOption('jplayer_autoplay') && !$multiplayer) {
			$autoplay = '.jPlayer("play")';
		}
		$videoThumb = '';
		if (getOption('jplayer_poster') && !is_null($movie->objectsThumb) && ($this->mode == 'video' || ($this->mode == 'audio' && getOption('jplayer_audioposter')))) {
			//$splashimagerwidth = $w;
			//$splashimageheight = $h;
			//getMaxSpaceContainer($splashimagerwidth, $splashimageheight, $movie, true); // jplayer squishes always if not the right aspect ratio
			$videoThumb = ',poster:"' . $movie->getCustomImage(array('width' => $w, 'height' => $h, 'cw' => $w, 'ch' => $h, 'thumb' => 3)) . '"';
		}

		$playerconfig = '
		<script>
			//<![CDATA[
		$(document).ready(function(){
			$("#jquery_jplayer_' . $count . '").jPlayer({
				ready: function (event) {
					$(this).jPlayer("setMedia", {
						' . $this->supplied . ':"' . pathurlencode($moviepath) . '"
						' . $this->getCounterpartFiles($moviepath, $ext) . '
						' . $videoThumb . '
					})' . $autoplay . ';
				},
				swfPath: "' . WEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER . '/jPlayer/js",
				supplied: "' . $this->supplied . $this->supplied_counterparts . '",
				cssSelectorAncestor: "#jp_container_' . $count . '"';

		if ($multiplayer) {
			$playerconfig .= ',
				play: function() { // To avoid both jPlayers playing together.
				$(this).jPlayer("pauseOthers");
			}
			';
		}

		if ($this->mode == 'video' || ($this->mode == 'audio' && getOption('jplayer_poster') && getOption('jplayer_audioposter'))) {
			$playerconfig .= '
				,	size: {
			width: "100%",
			height: "100%",
			cssClass: "' . $this->playersize . '"
		},';
		} else {
			$playerconfig .= ',';
		}

		$playerconfig .= '
			useStateClassSkin: true,
			remainingDuration: true,
			toggleDuration: true
			});
		});
	//]]>
	</script>';

// I am really too lazy to figure everything out to optimize this quite complex html nesting so I generalized only parts.
// This will also make it easier and more convenient to spot any html changes the jplayer developer might come up with later on (as he did from 2.0 to 2.1!)
		if ($this->mode == 'video' || !empty($videoThumb)) {
			$playerconfig .= '
			<div id="jp_container_' . $count . '" class="jp-video ' . $this->playersize . '" role="application" aria-label="media player">
			<div class="jp-type-single">
				<div id="jquery_jplayer_' . $count . '" class="jp-jplayer"></div>
				<div class="jp-gui">
					<div class="jp-interface">
						<div class="jp-progress">
							<div class="jp-seek-bar">
								<div class="jp-play-bar"></div>
							</div>
						</div>
						<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
						<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
						<div class="jp-controls-holder">';
			$playerconfig .= $this->getPlayerHTMLparts($this->mode, 'controls');
			$playerconfig .= '
						<div class="jp-volume-controls">
							<button class="jp-mute" role="button" tabindex="0">' . gettext('mute') . '</button>
							<button class="jp-volume-max" role="button" tabindex="0">' . gettext('max volume') . '</button>
							<div class="jp-volume-bar">
								<div class="jp-volume-bar-value"></div>
							</div>
						</div>';
			$playerconfig .= $this->getPlayerHTMLparts($this->mode, 'toggles');
			$playerconfig .= '
						</div>';
			$playerconfig .= '
						</div>
					</div>';
			if (getOption('jplayer_showtitle')) {
				$playerconfig .= '
					<div class="jp-details">
						<div class="jp-title" aria-label="title">' . html_encode($movietitle) . '</div>
					</div>';
			}
			$playerconfig .= $this->getPlayerHTMLparts($this->mode, 'no-solution');
			$playerconfig .= '
			</div>
		</div>
		';
		} else { // audio
			$playerconfig .= '
		<div id="jquery_jplayer_' . $count . '" class="jp-jplayer"></div>
		<div id="jp_container_' . $count . '" class="jp-audio" role="application" aria-label="media player">
			<div class="jp-type-single">
				<div class="jp-gui jp-interface">';
			$playerconfig .= $this->getPlayerHTMLparts($this->mode, 'controls');
			$playerconfig .= '
					<div class="jp-progress">
						<div class="jp-seek-bar">
							<div class="jp-play-bar"></div>
						</div>
					</div>
					<div class="jp-volume-controls">
						<button class="jp-mute" role="button" tabindex="0">' . gettext('mute') . '</button>
						<button class="jp-volume-max" role="button" tabindex="0">' . gettext('max volume') . '</button>
						<div class="jp-volume-bar">
							<div class="jp-volume-bar-value"></div>
						</div>
					</div>
					<div class="jp-time-holder">
						<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
						<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>';
			$playerconfig .= $this->getPlayerHTMLparts($this->mode, 'toggles');
			$playerconfig .= '
					</div>
				</div>';
			if (getOption('jplayer_showtitle')) {
				$playerconfig .= '
					<div class="jp-details">
						<div class="jp-title" aria-label="title">' . html_encode($movietitle) . '</div>
					</div>';
			}
			$playerconfig .= $this->getPlayerHTMLparts($this->mode, 'no-solution');
			$playerconfig .= '
			</div>
		</div>
		';
		} // video/audio if else end
		return $playerconfig;
	}

	/**
	 * outputs the player configuration HTML
	 *
	 * @param mixed $movie the image object if empty (within albums) the current image is used
	 * @param string $movietitle the title of the movie. if empty the Image Title is used
	 * @param string $count unique text for when there are multiple player items on a page
	 */
	function printPlayerConfig($movie = NULL, $movietitle = NULL, $count = NULL) {
		global $_current_image;
		if (empty($movie)) {
			$movie = $_current_image;
		}
		echo $this->getPlayerConfig($movie, $movietitle, $count, NULL, NULL);
	}

	/**
	 * gets commonly used html parts for the player config
	 *
	 * @param string $mode 'video' or 'audio'
	 * @param string $part part to get: 'controls', 'controls-playlist', 'toggles', 'toggles-playlist','no-solution'
	 */
	function getPlayerHTMLparts($mode = '', $part = '') {
		$htmlpart = '';
		switch ($part) {
			case 'controls':
			case 'controls-playlist':
				$htmlpart = '<div class="jp-controls">';
				if ($part == 'controls-playlist') {
					$htmlpart .= '<button class="jp-previous" role="button" tabindex="0">' . gettext('previous') . '</button>';
				}
				$htmlpart .= '<button class="jp-play" role="button" tabindex="0">' . gettext('play') . '</button>';
				if ($part == 'controls-playlist') {
					$htmlpart .= '<button class="jp-next" role="button" tabindex="0">' . gettext('next') . '</button>	';
				}
				$htmlpart .= '<button class="jp-stop" role="button" tabindex="0">' . gettext('stop') . '</button>';
				$htmlpart .= '</div>';
				break;
			case 'toggles':
			case 'toggles-playlist':
				$htmlpart = '<div class="jp-toggles">';
				$htmlpart .= '<button class="jp-repeat" role="button" tabindex="0">' . gettext('repeat') . '</button>';
				if ($part == 'toggles-playlist') {
					$htmlpart .= '<button class="jp-shuffle" role="button" tabindex="0">' . gettext('shuffle') . '</button>';
				}
				if ($mode == 'video') {
					$htmlpart .= '<button class="jp-full-screen" role="button" tabindex="0">' . gettext('full screen') . '</button>';
				}
				$htmlpart .= '</div>';
				break;
			case 'no-solution':
				$htmlpart = '
			<div class="jp-no-solution">
				<span>' . gettext('Update Required') . '</span>
				' . gettext('To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.') . '
			</div>';
				break;
		}
		return $htmlpart;
	}

	/**
	 * Returns the width of the player
	 *
	 * @return int
	 */
	function getWidth() {
		if ($this->mode == 'audio' && !getOption('jplayer_poster') && !getOption('jplayer_audioposter')) {
			return 420; //audio default
		}
		return $this->width;
	}

	/**
	 * Returns the height of the player
	 *
	 * @return int
	 */
	function getHeight() {
		if ($this->mode == 'audio' && !getOption('jplayer_poster') && !getOption('jplayer_audioposter')) {
			return 0;
		}
		return $this->height;
	}

	/**
	 * Sets the properties $mode, $supplied and $supplied_counterparts
	 *
	 */
	function setModeAndSuppliedFormat($ext) {
		$this->supplied = $ext;
		switch ($ext) {
			case 'm4a':
			case 'mp3':
			case 'fla':
				$this->mode = 'audio';
				break;
			case 'mp4':
				$this->supplied = 'm4v';
			case 'm4v':
			case 'flv':
				$this->mode = 'video';
				break;
		}
	}

	/** TODO: Could not get this to work with Firefox. Low priority so postponed for sometime later...
	 * Gets the mp3, m4v,m4a,mp4 counterpart formats (webm,ogg) for html5 browser compatibilty
	 * NOTE: These formats need to be uploaded via FTP as they are not valid file types for netPhotoGraphics to avoid confusion
	 *
	 * @param string $moviepath full link to the multimedia file to get counterpart formats to.
	 * @param string $ext the file format extention to search the counterpart for (as we already have fetched that)
	 */
	function getCounterpartFiles($moviepath, $ext) {
		$counterparts = '';
		switch ($ext) {
			case 'mp3':
			case 'm4a':
			case 'fla':
				$suffixes = array('oga', 'webma', 'webm');
				break;
			case 'mp4':
			case 'm4v':
			case 'flv':
				$suffixes = array('ogv', 'webmv', 'webm');
				break;
			default:
				$suffixes = array();
				break;
		}
		foreach ($suffixes as $suffix) {
			$filesuffix = $suffix;
			/* if($suffix == 'oga') {
			  $filesuffix = 'ogg';
			  } */
			$counterpart = str_replace($ext, $filesuffix, $moviepath);
//$suffix = str_replace('.','',$suffix);
			if (file_exists(str_replace(FULLWEBPATH, SERVERPATH, $counterpart))) {
				$this->supplied_counterparts .= ',' . $suffix;
				$counterparts .= ',' . $suffix . ':"' . pathurlencode($counterpart) . '"';
			}
		}
		return $counterparts;
	}

	/**
	 * Prints a playlist using jPlayer. Several playlists per page supported.
	 *
	 * The playlist is meant to replace the 'next_image()' loop on a theme's album.php.
	 * It can be used with a special 'album theme' that can be assigned to media albums with with .flv/.mp4/.mp3s, although Flowplayer 3 also supports images
	 * Replace the entire 'next_image()' loop on album.php with this:
	 * <?php printjPlayerPlaylist("playlist"); ?> or <?php printjPlayerPlaylist("playlist-audio"); ?>
	 *
	 * @param string $option "playlist" use for pure video and mixed video/audio playlists or if you want to show the poster/videothumb with audio only playlists,
	 * 											 "playlist-audio" use for pure audio playlists (m4a,mp3,fla supported only) if you don't need the poster/videothumb to be shown only.
	 * @param string $albumfolder album name to get a playlist from directly
	 */
	function printjPlayerPlaylist($option = "playlist", $albumfolder = "") {
		global $_current_album, $_current_search;
		if (empty($albumfolder)) {
			if (in_context(NPG_SEARCH)) {
				$albumobj = $_current_search;
			} else {
				$albumobj = $_current_album;
			}
		} else {
			$albumobj = newAlbum($albumfolder);
		}
		$entries = $albumobj->getImages(0);
		if (($numimages = count($entries)) != 0) {
			switch ($option) {
				case 'playlist':
					$suffixes = array('m4a', 'm4v', 'mp3', 'mp4', 'flv', 'fla');
					break;
				case 'playlist-audio':
					$suffixes = array('m4a', 'mp3', 'fla');
					break;
				default:
//	an invalid option parameter!
					return;
			}
			$id = $albumobj->getID();
			?>
			<script>
				//<![CDATA[
				$(document).ready(function(){
				new jPlayerPlaylist({
				jPlayer: "#jquery_jplayer_<?php echo $id; ?>",
								cssSelectorAncestor: "#jp_container_<?php echo $id; ?>"
				}, [
			<?php
			$count = '';
			$number = '';

			foreach ($entries as $entry) {
				$count++;
				if (is_array($entry)) {
					$ext = getSuffix($entry['filename']);
				} else {
					$ext = getSuffix($entry);
				}
				$numbering = '';
				if (in_array($ext, $suffixes)) {
					$number++;
					if (getOption('jplayer_playlist_numbered')) {
						$numbering = '<span>' . $number . '</span>';
					}
					$video = newImage($albumobj, $entry);
					$videoThumb = '';
					$this->setModeAndSuppliedFormat($ext);
					if ($option == 'playlist' && getOption('jplayer_poster')) {
						$videoThumb = ',poster:"' . $video->getCustomImage(array('width' => $this->width, 'height' => $this->height, 'cw' => $this->width, 'ch' => $this->height, 'thumb' => 3)) . '"';
					}
					$playtime = '';
					if (getOption('jplayer_playlist_playtime')) {
						$playtime = ' (' . $video->get('VideoPlaytime') . ')';
					}
					?>
						{
						title:"<?php echo $numbering . html_encode($video->getTitle()) . $playtime; ?>",
					<?php if (getOption('jplayer_download')) { ?>
							free:true,
					<?php } ?>
					<?php echo $this->supplied; ?>:"<?php echo $url = $video->getFullImageURL(FULLWEBPATH); ?>"
					<?php echo $this->getCounterpartFiles($url, $ext); ?>
					<?php echo $videoThumb; ?>
						}
					<?php
					if ($numimages != $count) {
						echo ',';
					}
				} // if video
			} // foreach
// for some reason the playlist must run with supplied: "flash,html" because otherwise neither videothumbs(poster) nor flv/flv work on Safari 4.1.
// Seems the flash fallback fails here
			?>
				], {
				swfPath: "<?php echo WEBPATH . '/' . CORE_FOLDER . '/' . PLUGIN_FOLDER; ?>/jPlayer/js",
								solution: "flash,html",
			<?php if ($option == 'playlist') { ?>
					supplied: "m4v, mp4, m4a, mp3, fla, flv<?php echo $this->supplied_counterparts; ?>"
			<?php } else { ?>
					supplied: "m4a, mp3, fla<?php echo $this->supplied_counterparts; ?>"
				<?php
			}
			if ($option != 'playlist-audio') {
				?>
					, size: {
					width: "<?php echo $this->width; ?>px",
									height: "<?php echo $this->height; ?>px",
									cssClass: "<?php echo $this->playersize; ?>"
					}
			<?php } ?>
				useStateClassSkin: true,
								autoBlur: false,
								smoothPlayBar: true,
								keyEnabled: true,
								remainingDuration: true,
								toggleDuration: true
				});
				});
				//]]>
			</script>
			<?php
			if ($option == 'playlist') {
				?>
				<div id="jp_container_<?php echo $id; ?>" class="jp-video <?php echo $this->playersize; ?>" role="application" aria-label="media player">
					<div class="jp-type-playlist">
						<div id="jquery_jplayer_<?php echo $id; ?>" class="jp-jplayer"></div>
						<div class="jp-gui">
							<div class="jp-video-play">
								<button class="jp-video-play-icon" role="button" tabindex="0"><?php echo gettext('play'); ?></button>
							</div>
							<div class="jp-interface">
								<div class="jp-progress">
									<div class="jp-seek-bar">
										<div class="jp-play-bar"></div>
									</div>
								</div>
								<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
								<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
								<div class="jp-controls-holder">
									<?php echo $this->getPlayerHTMLparts('video', 'controls-playlist'); ?>
									<div class="jp-volume-controls">
										<button class="jp-mute" role="button" tabindex="0"><?php echo gettext('mute'); ?></button>
										<button class="jp-volume-max" role="button" tabindex="0"><?php echo gettext('max volume'); ?></button>
										<div class="jp-volume-bar">
											<div class="jp-volume-bar-value"></div>
										</div>
									</div>
									<?php echo $this->getPlayerHTMLparts('video', 'toggles-playlist'); ?>
								</div>
								<div class="jp-details">
									<div class="jp-title" aria-label="title">&nbsp;</div>
								</div>
							</div>
						</div>
						<div class="jp-playlist">
							<ul>
								<!-- The method Playlist.displayPlaylist() uses this unordered list -->
								<li>&nbsp;</li>
							</ul>
						</div>
						<?php echo $this->getPlayerHTMLparts('video', 'no-solution'); ?>
					</div>
				</div>
				<?php
			} else { // playlist-audio
				?>
				<div id="jquery_jplayer_<?php echo $id; ?>" class="jp-jplayer"></div>
				<div id="jp_container_<?php echo $id; ?>" class="jp-audio" role="application" aria-label="media player">
					<div class="jp-type-playlist">
						<div class="jp-gui jp-interface">
							<?php echo $this->getPlayerHTMLparts('audio', 'controls-playlist'); ?>
							<div class="jp-progress">
								<div class="jp-seek-bar">
									<div class="jp-play-bar"></div>
								</div>
							</div>
							<div class="jp-volume-controls">
								<button class="jp-mute" role="button" tabindex="0"><?php echo gettext('mute'); ?></button>
								<button class="jp-volume-max" role="button" tabindex="0"><?php echo gettext('max volume'); ?></button>
								<div class="jp-volume-bar">
									<div class="jp-volume-bar-value"></div>
								</div>
							</div>
							<div class="jp-time-holder">
								<div class="jp-current-time" role="timer" aria-label="time">&nbsp;</div>
								<div class="jp-duration" role="timer" aria-label="duration">&nbsp;</div>
							</div>
							<?php echo $this->getPlayerHTMLparts('audio', 'toggles-playlist'); ?>
						</div>
						<div class="jp-playlist">
							<ul>
								<li>&nbsp;</li>
							</ul>
						</div>
						<?php echo $this->getPlayerHTMLparts('audio', 'no-solution'); ?>
					</div>
				</div>

				<?php
			} // if else playlist
		} // if no images at all end
	}

// function playlist
}

$_multimedia_extension = new jPlayer(); // claim to be the flash player.
npgFilters::register('content_macro', 'jPlayer::macro');
npgFilters::register('theme_head', 'jplayer::CSS');
npgFilters::register('theme_body_close', 'jplayer::JS');
if (getOption('jplayer_playlist')) {
	npgFilters::register('theme_body_close', 'jplayer::playlistJS');
}