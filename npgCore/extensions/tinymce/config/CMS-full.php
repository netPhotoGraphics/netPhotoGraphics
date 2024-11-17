<?php

/**
 * The configuration parameters for TinyMCE.
 *
 * CMS plugin full-light configuration
 * @author Stephen Billard (sbillard)
 */
$MCEselector = "textarea.content,textarea.desc,textarea.extracontent";
$MCEplugins = "advlist autolink lists link image charmap anchor pagebreak " .
				"searchreplace visualchars wordcount visualblocks code fullscreen " .
				"insertdatetime media nonbreaking save table " .
				"emoticons pasteobj directionality help";
$MCEtoolbars[1] = "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image";
$MCEtoolbars[2] = "media pasteobj emoticons nonbreaking pagebreak | ltr rtl | forecolor backcolor | code fullscreen";
$MCEstatusbar = true;
$MCEmenubar = true;

include(TINYMCE . '/config/config.js.php');
