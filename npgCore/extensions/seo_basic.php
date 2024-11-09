<?php

/**
 * Translates characters with diacritical marks to simple equivalents
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/seo_basic
 * @pluginCategory seo
 */
$plugin_is_filter = defaultExtension(5 | ADMIN_PLUGIN);
$plugin_description = gettext("SEO filter to translate extended characters into their basic alpha-numeric equivalents.");
$plugin_disable = npgFilters::has_filter('seoFriendly') && !extensionEnabled('seo_basic') ? gettext('Only one SEO filter plugin can be enabled.') : '';

$option_interface = 'seo_basic';

if ($plugin_disable) {
	enableExtension('seo_null', 0);
} else {
	npgFilters::register('seoFriendly', 'seo_basic::filter');
	npgFilters::register('seoFriendly_js', 'seo_basic::js');
}

/**
 * Option handler class
 *
 */
class seo_basic {

	private static $specialchars = array(
			"ἄ" => "a",
			"ᾀ" => "a",
			"α" => "a",
			"ა" => "a",
			"ᾷ" => "a",
			"ἀ" => "a",
			"ἁ" => "a",
			"ἂ" => "a",
			"ἃ" => "a",
			"ą" => "a",
			"ἅ" => "a",
			"ἆ" => "a",
			"ἇ" => "a",
			"ᾁ" => "a",
			"ᾶ" => "a",
			"ᾂ" => "a",
			"ᾃ" => "a",
			"ᾄ" => "a",
			"ᾅ" => "a",
			"ᾆ" => "a",
			"ᾇ" => "a",
			"ὰ" => "a",
			"ά" => "a",
			"ᾰ" => "a",
			"ᾱ" => "a",
			"ᾲ" => "a",
			"ᾳ" => "a",
			"ά" => "a",
			"ᾴ" => "a",
			"à" => "a",
			"ẚ" => "a",
			"ặ" => "a",
			"ậ" => "a",
			"ǻ" => "a",
			"ǟ" => "a",
			"ǡ" => "a",
			"ẳ" => "a",
			"ẵ" => "a",
			"ắ" => "a",
			"ằ" => "a",
			"ẩ" => "a",
			"ẫ" => "a",
			"ấ" => "a",
			"ầ" => "a",
			"ḁ" => "a",
			"á" => "a",
			"ạ" => "a",
			"ȃ" => "a",
			"ȁ" => "a",
			"ǎ" => "a",
			"å" => "a",
			"ả" => "a",
			"ȧ" => "a",
			"ă" => "a",
			"ā" => "a",
			"ã" => "a",
			"â" => "a",
			"ᾍ" => "A",
			"ᾌ" => "A",
			"ᾋ" => "A",
			"ᾊ" => "A",
			"Ᾰ" => "A",
			"ᾎ" => "A",
			"ᾏ" => "A",
			"ᾈ" => "A",
			"Ᾱ" => "A",
			"Ὰ" => "A",
			"Ά" => "A",
			"ᾉ" => "A",
			"À" => "A",
			"Ἇ" => "A",
			"Ἆ" => "A",
			"Ἅ" => "A",
			"Ἄ" => "A",
			"Ἃ" => "A",
			"Ἁ" => "A",
			"Ἀ" => "A",
			"א" => "A",
			"Α" => "A",
			"Ά" => "A",
			"Á" => "A",
			"А" => "A",
			"а" => "A",
			"ᾼ" => "A",
			"Ἂ" => "A",
			"Ằ" => "A",
			"Ấ" => "A",
			"Å" => "A",
			"Ắ" => "A",
			"Ā" => "A",
			"Ă" => "A",
			"Ą" => "A",
			"Ǟ" => "A",
			"Ǡ" => "A",
			"Ǻ" => "A",
			"Ȁ" => "A",
			"Ȃ" => "A",
			"Ã" => "A",
			"Ḁ" => "A",
			"Ả" => "A",
			"Ȧ" => "A",
			"Â" => "A",
			"Ặ" => "A",
			"Ầ" => "A",
			"Ẩ" => "A",
			"Ẫ" => "A",
			"Ậ" => "A",
			"Ẳ" => "A",
			"Ẵ" => "A",
			"æ" => "ae",
			"ǽ" => "ae",
			"ä" => "ae",
			"Ä" => "AE",
			"Æ" => "AE",
			"Ǽ" => "AE",
			"Ǣ" => "AE",
			"ƀ" => "b",
			"β" => "b",
			"ɓ" => "b",
			"ḃ" => "b",
			"ƃ" => "b",
			"ƅ" => "b",
			"ḅ" => "b",
			"ბ" => "b",
			"ḇ" => "b",
			"Ḇ" => "B",
			"ב" => "B",
			"Ƃ" => "B",
			"Ḃ" => "B",
			"Ɓ" => "B",
			"б" => "B",
			"Б" => "B",
			"Ḅ" => "B",
			"Β" => "B",
			"Ƅ" => "B",
			"ċ" => "c",
			"ć" => "c",
			"c" => "c",
			"č" => "c",
			"ĉ" => "c",
			"ƈ" => "c",
			"ç" => "c",
			"ḉ" => "c",
			"Č" => "C",
			"Ĉ" => "C",
			"Ƈ" => "C",
			"Ç" => "C",
			"Ḉ" => "C",
			"Ċ" => "C",
			"Ć" => "C",
			"ჭ" => "ch",
			"ჩ" => "ch",
			"ч" => "CH",
			"Ч" => "CH",
			"ḑ" => "d",
			"ḏ" => "d",
			"ȡ" => "d",
			"ƌ" => "d",
			"đ" => "d",
			"ď" => "d",
			"ð" => "d",
			"დ" => "d",
			"ḓ" => "d",
			"δ" => "d",
			"ḍ" => "d",
			"ד" => "D",
			"Ḋ" => "D",
			"Ɖ" => "D",
			"Đ" => "D",
			"Ď" => "D",
			"Ḓ" => "D",
			"Ḑ" => "D",
			"Ɗ" => "D",
			"Ḍ" => "D",
			"Δ" => "D",
			"Ð" => "D",
			"Ḏ" => "D",
			"д" => "D",
			"Д" => "D",
			"ძ" => "dz",
			"ề" => "e",
			"ɛ" => "e",
			"ǝ" => "e",
			"ḝ" => "e",
			"ệ" => "e",
			"ḗ" => "e",
			"ḕ" => "e",
			"ἓ" => "e",
			"ể" => "e",
			"ễ" => "e",
			"ế" => "e",
			"ẹ" => "e",
			"ȇ" => "e",
			"è" => "e",
			"é" => "e",
			"ê" => "e",
			"ẽ" => "e",
			"ē" => "e",
			"ĕ" => "e",
			"ė" => "e",
			"ë" => "e",
			"ě" => "e",
			"ę" => "e",
			"ȩ" => "e",
			"ε" => "e",
			"έ" => "e",
			"ȅ" => "e",
			"ḙ" => "e",
			"ἔ" => "e",
			"ἒ" => "e",
			"ἑ" => "e",
			"ἐ" => "e",
			"ἕ" => "e",
			"ὲ" => "e",
			"έ" => "e",
			"ე" => "e",
			"Ề" => "E",
			"Ǝ" => "E",
			"Ε" => "E",
			"Ể" => "E",
			"Έ" => "E",
			"Ḕ" => "E",
			"Ḗ" => "E",
			"È" => "E",
			"Ḝ" => "E",
			"Ё" => "E",
			"Ɛ" => "E",
			"Ἓ" => "E",
			"Ἒ" => "E",
			"Ἑ" => "E",
			"Ế" => "E",
			"ё" => "E",
			"Е" => "E",
			"е" => "E",
			"Ἐ" => "E",
			"Ễ" => "E",
			"Ệ" => "E",
			"Ė" => "E",
			"É" => "E",
			"Ē" => "E",
			"Ĕ" => "E",
			"Ἔ" => "E",
			"Ë" => "E",
			"Ẻ" => "E",
			"Ě" => "E",
			"Э" => "E",
			"э" => "E",
			"Ȅ" => "E",
			"Ὲ" => "E",
			"Ê" => "E",
			"Έ" => "E",
			"Ȇ" => "E",
			"Ẹ" => "E",
			"Ȩ" => "E",
			"Ἕ" => "E",
			"Ę" => "E",
			"Ḙ" => "E",
			"Ḛ" => "E",
			"Ẽ" => "E",
			"φ" => "f",
			"ḟ" => "f",
			"ƒ" => "f",
			"ף" => "F",
			"Φ" => "F",
			"Ƒ" => "F",
			"Ḟ" => "F",
			"Ф" => "F",
			"ф" => "F",
			"გ" => "g",
			"ǵ" => "g",
			"ĝ" => "g",
			"ḡ" => "g",
			"ğ" => "g",
			"ġ" => "g",
			"ǧ" => "g",
			"ɠ" => "g",
			"ģ" => "g",
			"ǥ" => "g",
			"γ" => "g",
			"Ġ" => "G",
			"Γ" => "G",
			"Ǥ" => "G",
			"Ģ" => "G",
			"г" => "G",
			"Г" => "G",
			"Ɠ" => "G",
			"Ğ" => "G",
			"Ḡ" => "G",
			"Ĝ" => "G",
			"Ǵ" => "G",
			"ג" => "G",
			"Ǧ" => "G",
			"ღ" => "gh",
			"ჰ" => "h",
			"ĥ" => "h",
			"ḣ" => "h",
			"ȟ" => "h",
			"ƕ" => "h",
			"ḥ" => "h",
			"ḩ" => "h",
			"ḫ" => "h",
			"ẖ" => "h",
			"ħ" => "h",
			"ḧ" => "h",
			"Ĥ" => "H",
			"Ḥ" => "H",
			"Ƕ" => "H",
			"Ȟ" => "H",
			"Ḧ" => "H",
			"Ḣ" => "H",
			"Ḩ" => "H",
			"Ħ" => "H",
			"Ḫ" => "H",
			"ך" => "H",
			"ח" => "H",
			"ה" => "Ha",
			"ί" => "i",
			"ὶ" => "i",
			"ἶ" => "i",
			"ἵ" => "i",
			"ἷ" => "i",
			"ῑ" => "i",
			"ῐ" => "i",
			"ǐ" => "i",
			"ῒ" => "i",
			"ΐ" => "i",
			"ῖ" => "i",
			"ῗ" => "i",
			"ი" => "i",
			"ἳ" => "i",
			"ỉ" => "i",
			"ï" => "i",
			"ἴ" => "i",
			"ῂ" => "i",
			"ἲ" => "i",
			"ι" => "i",
			"ἣ" => "i",
			"ἢ" => "i",
			"ἡ" => "i",
			"ἠ" => "i",
			"ĭ" => "i",
			"η" => "i",
			"ή" => "i",
			"ί" => "i",
			"ἥ" => "i",
			"ϊ" => "i",
			"ΐ" => "i",
			"ḯ" => "i",
			"ɨ" => "i",
			"ȋ" => "i",
			"ȉ" => "i",
			"į" => "i",
			"ἤ" => "i",
			"ἦ" => "i",
			"ἱ" => "i",
			"ὴ" => "i",
			"ἰ" => "i",
			"ῇ" => "i",
			"ῆ" => "i",
			"ῄ" => "i",
			"ῃ" => "i",
			"ị" => "i",
			"ή" => "i",
			"ᾗ" => "i",
			"ἧ" => "i",
			"ᾖ" => "i",
			"ᾕ" => "i",
			"ᾔ" => "i",
			"ᾓ" => "i",
			"ᾒ" => "i",
			"ᾑ" => "i",
			"ᾐ" => "i",
			"ı" => "i",
			"ḭ" => "i",
			"ī" => "i",
			"î" => "i",
			"í" => "i",
			"ì" => "i",
			"ĩ" => "i",
			"Ἧ" => "I",
			"ᾟ" => "I",
			"ᾞ" => "I",
			"ᾝ" => "I",
			"ᾜ" => "I",
			"ᾛ" => "I",
			"ᾚ" => "I",
			"ᾙ" => "I",
			"ᾘ" => "I",
			"Ἤ" => "I",
			"Ἦ" => "I",
			"Ἥ" => "I",
			"Ἣ" => "I",
			"Ἡ" => "I",
			"Ἠ" => "I",
			"Ì" => "I",
			"Í" => "I",
			"Î" => "I",
			"Ĩ" => "I",
			"Ī" => "I",
			"Ὴ" => "I",
			"ῌ" => "I",
			"Ή" => "I",
			"Ῑ" => "I",
			"Й" => "I",
			"Ϊ" => "I",
			"Ί" => "I",
			"Ι" => "I",
			"Ή" => "I",
			"Η" => "I",
			"Ί" => "I",
			"Ὶ" => "I",
			"Ῐ" => "I",
			"İ" => "I",
			"Ἷ" => "I",
			"Ἶ" => "I",
			"Ἵ" => "I",
			"Ἴ" => "I",
			"י" => "I",
			"Ἳ" => "I",
			"Ἲ" => "I",
			"Ἱ" => "I",
			"Ἰ" => "I",
			"Ĭ" => "I",
			"Ἢ" => "I",
			"Ï" => "I",
			"Ɨ" => "I",
			"й" => "I",
			"И" => "I",
			"и" => "I",
			"Ḯ" => "I",
			"Ỉ" => "I",
			"Ḭ" => "I",
			"Ȋ" => "I",
			"Ǐ" => "I",
			"Ị" => "I",
			"Į" => "I",
			"ĳ" => "ij",
			"Ĳ" => "IJ",
			"ჯ" => "j",
			"ĵ" => "j",
			"ǰ" => "j",
			"Ĵ" => "J",
			"ķ" => "k",
			"კ" => "k",
			"ĸ" => "k",
			"κ" => "k",
			"ḱ" => "k",
			"ǩ" => "k",
			"ḵ" => "k",
			"ƙ" => "k",
			"ქ" => "k",
			"ḳ" => "k",
			"Ḳ" => "K",
			"Ƙ" => "K",
			"Ķ" => "K",
			"Ḵ" => "K",
			"Ǩ" => "K",
			"Ļ" => "K",
			"Ĺ" => "K",
			"Ḱ" => "K",
			"Ľ" => "K",
			"Ŀ" => "K",
			"к" => "K",
			"כ" => "K",
			"Κ" => "K",
			"К" => "K",
			"ק" => "K",
			"ხ" => "kh",
			"х" => "KH",
			"Х" => "KH",
			"ξ" => "ks",
			"Ξ" => "KS",
			"ḷ" => "l",
			"ł" => "l",
			"λ" => "l",
			"ĺ" => "l",
			"ļ" => "l",
			"ḽ" => "l",
			"ḻ" => "l",
			"ŀ" => "l",
			"ľ" => "l",
			"ƚ" => "l",
			"ḹ" => "l",
			"ȴ" => "l",
			"ლ" => "l",
			"Ḽ" => "L",
			"Ł" => "L",
			"Ḹ" => "L",
			"Л" => "L",
			"л" => "L",
			"ל" => "L",
			"Λ" => "L",
			"Ḷ" => "L",
			"Ḻ" => "L",
			"მ" => "m",
			"μ" => "m",
			"ɯ" => "m",
			"ṃ" => "m",
			"ṁ" => "m",
			"ḿ" => "m",
			"м" => "M",
			"Ḿ" => "M",
			"Ɯ" => "M",
			"М" => "M",
			"Ṁ" => "M",
			"Μ" => "M",
			"Ṃ" => "M",
			"מ" => "M",
			"ם" => "M",
			"ṅ" => "n",
			"ň" => "n",
			"ŋ" => "n",
			"ñ" => "n",
			"ṇ" => "n",
			"ɲ" => "n",
			"ń" => "n",
			"ņ" => "n",
			"ṋ" => "n",
			"ṉ" => "n",
			"ŉ" => "n",
			"ნ" => "n",
			"ƞ" => "n",
			"ȵ" => "n",
			"ν" => "n",
			"ǹ" => "n",
			"נ" => "N",
			"ן" => "N",
			"Ṅ" => "N",
			"Ν" => "N",
			"Ň" => "N",
			"Ɲ" => "N",
			"Ṇ" => "N",
			"Ñ" => "N",
			"Ņ" => "N",
			"Ṋ" => "N",
			"Ṉ" => "N",
			"Ƞ" => "N",
			"Ń" => "N",
			"Ǹ" => "N",
			"н" => "N",
			"Н" => "N",
			"Ŋ" => "N",
			"ồ" => "o",
			"ố" => "o",
			"ỗ" => "o",
			"ώ" => "o",
			"ổ" => "o",
			"ȱ" => "o",
			"ȫ" => "o",
			"ȭ" => "o",
			"ọ" => "o",
			"ṍ" => "o",
			"ɵ" => "o",
			"ő" => "o",
			"ǫ" => "o",
			"ơ" => "o",
			"ȏ" => "o",
			"ȍ" => "o",
			"ǒ" => "o",
			"ω" => "o",
			"ỏ" => "o",
			"ȯ" => "o",
			"ŏ" => "o",
			"ō" => "o",
			"õ" => "o",
			"ô" => "o",
			"ó" => "o",
			"ò" => "o",
			"ṑ" => "o",
			"ṏ" => "o",
			"ό" => "o",
			"ṓ" => "o",
			"ῶ" => "o",
			"ὅ" => "o",
			"ὸ" => "o",
			"ო" => "o",
			"ῷ" => "o",
			"ό" => "o",
			"ὠ" => "o",
			"ὡ" => "o",
			"ὢ" => "o",
			"ὣ" => "o",
			"ὤ" => "o",
			"ὥ" => "o",
			"ὦ" => "o",
			"ῴ" => "o",
			"ὃ" => "o",
			"ῳ" => "o",
			"ῲ" => "o",
			"ώ" => "o",
			"ὼ" => "o",
			"ᾧ" => "o",
			"ᾦ" => "o",
			"ᾥ" => "o",
			"ᾤ" => "o",
			"ᾣ" => "o",
			"ᾢ" => "o",
			"ᾡ" => "o",
			"ὧ" => "o",
			"ο" => "o",
			"ờ" => "o",
			"ὄ" => "o",
			"ὂ" => "o",
			"ὁ" => "o",
			"ớ" => "o",
			"ỡ" => "o",
			"ở" => "o",
			"ᾠ" => "o",
			"ǭ" => "o",
			"ộ" => "o",
			"ǿ" => "o",
			"ɔ" => "o",
			"ợ" => "o",
			"ὀ" => "o",
			"Ὠ" => "O",
			"ᾩ" => "O",
			"Ὀ" => "O",
			"Ὁ" => "O",
			"Ὂ" => "O",
			"Ὃ" => "O",
			"Ὄ" => "O",
			"Ὅ" => "O",
			"Ὸ" => "O",
			"ᾨ" => "O",
			"Ὡ" => "O",
			"Ὧ" => "O",
			"Ὦ" => "O",
			"Ὥ" => "O",
			"Ὤ" => "O",
			"ᾬ" => "O",
			"Ό" => "O",
			"ᾫ" => "O",
			"Ὢ" => "O",
			"ᾪ" => "O",
			"Ὣ" => "O",
			"ᾭ" => "O",
			"Ò" => "O",
			"ᾮ" => "O",
			"Ớ" => "O",
			"Ỡ" => "O",
			"Ở" => "O",
			"Ǭ" => "O",
			"Ộ" => "O",
			"Ɔ" => "O",
			"Ώ" => "O",
			"Ό" => "O",
			"ע" => "O",
			"Ο" => "O",
			"о" => "O",
			"О" => "O",
			"Ó" => "O",
			"Ő" => "O",
			"ῼ" => "O",
			"Ώ" => "O",
			"Ὼ" => "O",
			"Ờ" => "O",
			"Ṓ" => "O",
			"Ṑ" => "O",
			"Ṏ" => "O",
			"Ô" => "O",
			"Õ" => "O",
			"Ō" => "O",
			"Ŏ" => "O",
			"Ȍ" => "O",
			"Ω" => "O",
			"Ơ" => "O",
			"Ǫ" => "O",
			"Ọ" => "O",
			"ᾯ" => "O",
			"Ɵ" => "O",
			"Ồ" => "O",
			"Ố" => "O",
			"Ỗ" => "O",
			"Ổ" => "O",
			"Ȱ" => "O",
			"Ȫ" => "O",
			"Ȭ" => "O",
			"Ṍ" => "O",
			"ø" => "oe",
			"ö" => "oe",
			"œ" => "oe",
			"Ö" => "Oe",
			"Ȏ" => "OE",
			"Ø" => "OE",
			"Ǿ" => "OE",
			"Œ" => "OE",
			"ṝ" => "p",
			"ȓ" => "p",
			"π" => "p",
			"ṙ" => "p",
			"ȑ" => "p",
			"ṛ" => "p",
			"ƥ" => "p",
			"პ" => "p",
			"ṗ" => "p",
			"ṕ" => "p",
			"ṟ" => "p",
			"ფ" => "p",
			"П" => "P",
			"п" => "P",
			"Ṗ" => "P",
			"Ƥ" => "P",
			"Ṕ" => "P",
			"Π" => "P",
			"פ" => "P",
			"ψ" => "ps",
			"Ψ" => "PS",
			"ყ" => "q",
			"ř" => "r",
			"ρ" => "r",
			"ŕ" => "r",
			"რ" => "r",
			"ῥ" => "r",
			"ῤ" => "r",
			"ŗ" => "r",
			"Ŕ" => "R",
			"Ṙ" => "R",
			"Ρ" => "R",
			"Р" => "R",
			"р" => "R",
			"Ȓ" => "R",
			"Ṟ" => "R",
			"Ʀ" => "R",
			"ר" => "R",
			"Ῥ" => "R",
			"Ř" => "R",
			"Ȑ" => "R",
			"Ṝ" => "R",
			"Ṛ" => "R",
			"Ŗ" => "R",
			"ṥ" => "s",
			"ς" => "s",
			"σ" => "s",
			"ş" => "s",
			"ŝ" => "s",
			"ṡ" => "s",
			"ṩ" => "s",
			"ს" => "s",
			"ș" => "s",
			"ś" => "s",
			"ṣ" => "s",
			"š" => "s",
			"ṧ" => "s",
			"Ś" => "S",
			"Ṧ" => "S",
			"Ş" => "S",
			"Ṩ" => "S",
			"ס" => "S",
			"Ș" => "S",
			"Ṣ" => "S",
			"Š" => "S",
			"Ṥ" => "S",
			"С" => "S",
			"с" => "S",
			"Ŝ" => "S",
			"Σ" => "S",
			"Ṡ" => "S",
			"შ" => "sh",
			"Ш" => "SH",
			"ш" => "SH",
			"ש" => "SH",
			"Щ" => "SHCH",
			"щ" => "SHCH",
			"ß" => "ss",
			"ſ" => "ss",
			"ẞ" => "SS",
			"ტ" => "t",
			"τ" => "t",
			"თ" => "t",
			"ṭ" => "t",
			"ƭ" => "t",
			"ʈ" => "t",
			"ȶ" => "t",
			"ŧ" => "t",
			"ṯ" => "t",
			"ṱ" => "t",
			"ţ" => "t",
			"ț" => "t",
			"ẛ" => "t",
			"ṫ" => "t",
			"ẗ" => "t",
			"ť" => "t",
			"ƫ" => "t",
			"ט" => "T",
			"Ŧ" => "T",
			"т" => "T",
			"Ṯ" => "T",
			"Τ" => "T",
			"Ț" => "T",
			"Ţ" => "T",
			"Т" => "T",
			"Ṭ" => "T",
			"Ʈ" => "T",
			"Ƭ" => "T",
			"Ť" => "T",
			"Ṫ" => "T",
			"Ṱ" => "T",
			"θ" => "th",
			"Θ" => "TH",
			"Þ" => "TH",
			"წ" => "ts",
			"ც" => "ts",
			"Ц" => "TS",
			"ц" => "TS",
			"צ" => "TZ",
			"ץ" => "TZ",
			"ǜ" => "u",
			"ǖ" => "u",
			"ǘ" => "u",
			"ǚ" => "u",
			"ừ" => "u",
			"ṻ" => "u",
			"ṹ" => "u",
			"ṵ" => "u",
			"ṷ" => "u",
			"ų" => "u",
			"ū" => "u",
			"ụ" => "u",
			"ư" => "u",
			"ȗ" => "u",
			"ȕ" => "u",
			"ǔ" => "u",
			"ű" => "u",
			"ů" => "u",
			"ủ" => "u",
			"ŭ" => "u",
			"ữ" => "u",
			"ũ" => "u",
			"û" => "u",
			"ú" => "u",
			"ù" => "u",
			"ứ" => "u",
			"ṳ" => "u",
			"ử" => "u",
			"უ" => "u",
			"ṿ" => "u",
			"ự" => "u",
			"Ů" => "U",
			"Ű" => "U",
			"Ǔ" => "U",
			"Ȕ" => "U",
			"Ȗ" => "U",
			"Ư" => "U",
			"Ụ" => "U",
			"Ṳ" => "U",
			"Ų" => "U",
			"Ṷ" => "U",
			"Ṹ" => "U",
			"Ṵ" => "U",
			"Ṻ" => "U",
			"Ǜ" => "U",
			"Ǘ" => "U",
			"Ǖ" => "U",
			"У" => "U",
			"у" => "U",
			"Ự" => "U",
			"Ử" => "U",
			"Ữ" => "U",
			"Ứ" => "U",
			"Ừ" => "U",
			"Ủ" => "U",
			"Ǚ" => "U",
			"Ú" => "U",
			"Ŭ" => "U",
			"Û" => "U",
			"Ũ" => "U",
			"Ū" => "U",
			"Ù" => "U",
			"ü" => "ue",
			"Ü" => "UE",
			"ṽ" => "v",
			"ვ" => "v",
			"Ṿ" => "V",
			"В" => "V",
			"Ʋ" => "V",
			"в" => "V",
			"ו" => "V",
			"Ṽ" => "V",
			"ẉ" => "w",
			"ẁ" => "w",
			"ẘ" => "w",
			"ẃ" => "w",
			"ẅ" => "w",
			"ẇ" => "w",
			"ŵ" => "w",
			"Ẇ" => "W",
			"Ẉ" => "W",
			"Ẅ" => "W",
			"Ŵ" => "W",
			"Ẃ" => "W",
			"Ẁ" => "W",
			"χ" => "x",
			"ẍ" => "x",
			"ẋ" => "x",
			"Ẋ" => "X",
			"Ẍ" => "X",
			"Χ" => "X",
			"ƴ" => "y",
			"ẙ" => "y",
			"ỵ" => "y",
			"ὓ" => "y",
			"ῧ" => "y",
			"ὗ" => "y",
			"ὑ" => "y",
			"ὐ" => "y",
			"ὔ" => "y",
			"ỳ" => "y",
			"ý" => "y",
			"ŷ" => "y",
			"ỹ" => "y",
			"υ" => "y",
			"ὕ" => "y",
			"ύ" => "y",
			"ὖ" => "y",
			"ϋ" => "y",
			"ȳ" => "y",
			"ῦ" => "y",
			"ẏ" => "y",
			"ÿ" => "y",
			"ỷ" => "y",
			"ὺ" => "y",
			"ύ" => "y",
			"ῠ" => "y",
			"ῡ" => "y",
			"ῢ" => "y",
			"ΰ" => "y",
			"ὒ" => "y",
			"ΰ" => "y",
			"Ы" => "Y",
			"ы" => "Y",
			"Υ" => "Y",
			"Ỳ" => "Y",
			"Ỵ" => "Y",
			"Ὺ" => "Y",
			"Ῡ" => "Y",
			"Ύ" => "Y",
			"Ϋ" => "Y",
			"Ῠ" => "Y",
			"Ý" => "Y",
			"Ὗ" => "Y",
			"Ὕ" => "Y",
			"Ὓ" => "Y",
			"Ὑ" => "Y",
			"Ύ" => "Y",
			"Ƴ" => "Y",
			"Ȳ" => "Y",
			"Ŷ" => "Y",
			"Ỷ" => "Y",
			"Ÿ" => "Y",
			"Ẏ" => "Y",
			"Ỹ" => "Y",
			"Я" => "YA",
			"я" => "YA",
			"Ю" => "YU",
			"ю" => "YU",
			"ẑ" => "z",
			"ზ" => "z",
			"ż" => "z",
			"ž" => "z",
			"ȥ" => "z",
			"ẓ" => "z",
			"ź" => "z",
			"ƶ" => "z",
			"ζ" => "z",
			"ẕ" => "z",
			"Ẕ" => "Z",
			"Ż" => "Z",
			"З" => "Z",
			"з" => "Z",
			"Ź" => "Z",
			"Ẑ" => "Z",
			"Ž" => "Z",
			"Ζ" => "Z",
			"Ȥ" => "Z",
			"Ẓ" => "Z",
			"Ƶ" => "Z",
			"ז" => "Z",
			"ჟ" => "zh",
			"ж" => "ZH",
			"Ж" => "ZH"
	);

	/**
	 * class instantiation function
	 *
	 */
	function __construct() {
		if (OFFSET_PATH == 2) {
			$priority = extensionEnabled('seo_zenphoto');
			if (!is_null($priority)) {
				enableExtension('seo_basic', $priority);
				purgeOption('_plugin_seo_zenphoto');
			}

			renameOption('zenphoto_seo_lowercase', 'seo_basic_lowercase');

			setOptionDefault('seo_basic_lowercase', FALSE);
		}
	}

	/**
	 * Reports the supported options
	 *
	 * @return array
	 */
	function getOptionsSupported() {
		return array(gettext('Lowercase only') => array('key' => 'seo_basic_lowercase', 'type' => OPTION_TYPE_CHECKBOX,
						'desc' => gettext(
										'When set, all characters are converted to lower case.')));
	}

	function handleOption($option, $currentValue) {

	}

	/**
	 * translates characters with diacritical marks to simple ones
	 *
	 * @param string $string
	 * @return string
	 */
	static function filter($string) {
		$string = strtr($string, self::$specialchars);
		if (getOption('seo_basic_lowercase')) {
			$string = strtolower($string);
		}
		$string = preg_replace("/\s+/", "-", $string);
		$string = preg_replace("/[^a-zA-Z0-9_.-]/", "-", $string);

		return $string;
	}

	static function js($js) {
		$c = 1;
		$js .= "fold = {\n";
		foreach (self::$specialchars as $from => $to) {
			$js .= '"' . $from . '" : "' . $to . '", ';
			if ($c++ > 10) {
				$c = 1;
				$js .= "\n";
			}
		}
		$js .= "\n};\n";
		$js .= "fname = strtr(fname, fold);\n";

		if (getOption('seo_basic_lowercase')) {
			$js .= "fname = fname.toLowerCase();\n";
		}

		return $js;
	}

}

?>