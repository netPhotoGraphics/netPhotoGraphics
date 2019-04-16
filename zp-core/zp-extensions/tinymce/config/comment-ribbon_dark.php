<?php

/**
 * The configuration functions for TinyMCE
 *
 * Comment form ribbon-dark configuration
 *
 * @author Stephen Billard (sbillard)
 */
$MCEcss = 'dark_content.css';
$MCEskin = "oxide-dark";
$MCEselector = "textarea.textarea_inputbox, textarea.texteditor_comments";
$MCEplugins = "advlist autolink lists link image charmap hr anchor pagebreak " .
				"searchreplace wordcount visualblocks visualchars code fullscreen " .
				"insertdatetime save directionality " .
				"emoticons paste ";
$MCEmenubar = "edit insert view format tools";
$MCEtoolbars = array();
$MCEstatusbar = false;
include(TINYMCE . '/config/config.js.php');
