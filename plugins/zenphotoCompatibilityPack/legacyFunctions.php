<?php

/*
 * These functions are supplied for compatibility with legacy ZenPhoto themes
 * and plugins. They were never defined in netPhotoGraphics.
 */

function printLangAttribute($locale = null) {
	i18n::htmlLanguageCode();
}
