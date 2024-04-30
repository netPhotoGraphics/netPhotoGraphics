<?php

/**
 * This plugin allows a site to automatically create and assign tags based on image
 * metadata. Plugin options allow the site administrator to specify which image metadata
 * fields to be processed. When metadata is imported from an image the plugin will
 * look for data in the specified fields. If found, the image will be tagged with
 * the value of these selected fields.
 *
 * E.g. The when the EXIFModel field is selected the information the camera places in
 * this field will become a tag. (Typically this is the camera name--<i>NIKON D300</i>
 * for instance.) Thus it will be possible to tag search for images taken by a <i>NIKON D300</i>.
 *
 * <b>NOTE:</b> Only metadata fields that are enabled for processing may be chosen for tag candidates.
 * It is not necessary to have chosen to display the field in metadata lists, though, so
 * it is possible to search for fields that do not present in the <i>Image Info</i> display.
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/tagsFromMetadata
 * @pluginCategory media
 *
 * @Copyright 2015 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 */
$plugin_is_filter = 9 | CLASS_PLUGIN;
if (defined('SETUP_PLUGIN')) { //	gettext debugging aid
	$plugin_description = gettext('Extracts metadata from images and <code>XMP</code> sidecar files.');
}

$option_interface = 'tagsFromMetadata';

npgFilters::register('image_metadata', 'tagsFromMetadata::new_image', -99999);

class tagsFromMetadata {

	function getOptionsSupported() {
		global $_exifvars;
		$allowed = array();

		foreach ($_exifvars as $key => $meta) {
			if ($meta[METADATA_FIELD_ENABLED] && $key != 'IPTCKeywords') {
				$allowed[$key] = 'tagsFromMetadata_tag_' . $key;
			}
		}
		$options = array(gettext('Metadata to tag') => array('key' => 'tagsFromMetadata_tag', 'type' => OPTION_TYPE_CHECKBOX_UL,
						'checkboxes' => $allowed,
						'desc' => gettext('Select the metadata items that will be tagged.'))
		);
		return $options;
	}

	static function getTaggingItems() {
		global $_exifvars;
		$result = array();
		foreach ($_exifvars as $key => $meta) {
			if ($meta[METADATA_FIELD_ENABLED] && $key != 'IPTCKeywords') {
				if (getOption('tagsFromMetadata_tag_' . $key)) {
					$result[] = $key;
				}
			}
		}
		return $result;
	}

	static function new_image($image) {
		$element = array();
		$candidates = self::getTaggingItems();
		foreach ($candidates as $key) {
			if ($meta = $image->get($key)) {
				$element[] = $meta;
			}
		}
		$element = array_unique(array_merge($image->getTags(), $element));
		$image->setTags($element);
		$image->save();
		return $image;
	}

}
