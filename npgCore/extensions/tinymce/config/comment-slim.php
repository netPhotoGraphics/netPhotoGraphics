<?php

/**
 * The configuration parameters for TinyMCE 4.x.
 *
 * Comment form slim-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.textarea_inputbox, textarea.texteditor_comments";
$MCEplugins = "advlist autolink lists link image charmap anchor pagebreak " .
				"searchreplace visualchars visualblocks code " .
				"insertdatetime media paste ";
$MCEtoolbars[1] = "bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | ltr rtl code";
$MCEstatusbar = false;
$MCEmenubar = false;
include(TINYMCE . '/config/config.js.php');
