<?php

/**
 * The configuration parameters for TinyMCE 4.x.
 *
 * Comment form slim-dark configuration
 * @author Stephen Billard (sbillard)
 */
$MCEcss = 'dark_content.css';
$MCEskin = "oxide-dark";
$MCEselector = "textarea.textarea_inputbox, textarea.texteditor_comments";
$MCEplugins = "advlist autolink lists link image charmap anchor pagebreak " .
				"searchreplace visualchars visualblocks code " .
				"insertdatetime media paste directionality directionality ";
$MCEtoolbars[1] = "bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | ltr rtl code";
$MCEstatusbar = false;
$MCEmenubar = false;
include(TINYMCE . '/config/config.js.php');
