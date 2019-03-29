<?php

/*
 * creates a token and rewrite rule for "page/gallery" (gallery.php). This plugin will
 * allow you to change the script that displays the album index. However that is
 * probably not a good idea--scripts should standardize on gallery.php for this purpose
 * if they do not display the album index on the index.php page.
 *
 * Rather this plugin serves as an example of how one would create new rewrite tokens and
 * rules.
 *
 * @author Stephen Billard (sbillard)
 * @package plugins/albumIndexToken
 * @pluginCategory example
 */

$plugin_is_filter = 5 | FEATURE_PLUGIN;
$plugin_description = gettext('Rewrite rule for the album index (<em>gallery.php</em>) custom page.');
$plugin_disable = (MOD_REWRITE) ? '' : gettext('<em>albumIndexToken</em> requires the <code>mod_rewrite</code> option be enabled.');

$option_interface = 'albumIndexToken_link';
/**
 * Technically the statements below are not needed for the "ALBUM_PAGE" token since they are also defined by the
 * rewrite.php script. But they are included here as a template for creating original rewrite tokens.
 */
$_zp_conf_vars['special_pages']['album'] = array('define' => '_ALBUM_PAGE_', 'rewrite' => getOption('albumIndexToken_link'),
		'option' => 'albumIndexToken_link', 'default' => '_PAGE_/gallery');
$_zp_conf_vars['special_pages'][] = array('definition' => '%ALBUM_PAGE%', 'rewrite' => '_ALBUM_PAGE_');
$_zp_conf_vars['special_pages'][] = array('define' => false, 'rewrite' => '%ALBUM_PAGE%/([0-9]+)', 'rule' => '^%REWRITE%/*gallery.php?p=gallery&page=$1' . ' [L,QSA]');
$_zp_conf_vars['special_pages'][] = array('define' => false, 'rewrite' => '%ALBUM_PAGE%', 'rule' => '^%REWRITE%/*$gallery.php?p=gallery [L,QSA]');

class albumIndexToken_link {

	function getOptionsSupported() {
		return array('» ' . gettext('Criteria') => array('key' => 'albumIndexToken_link', 'type' => OPTION_TYPE_TEXTBOX,
						'desc' => gettext("Set to the theme script that handles the album index page."))
		);
	}

}
