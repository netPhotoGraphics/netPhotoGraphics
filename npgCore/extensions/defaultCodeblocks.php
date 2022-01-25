<?php

/**
 * Supply default codeblocks to theme pages.
 *
 * This plugin provides a means to supply codeblock text for theme pages that
 * is "global" in context whereas normally you would have to insert the text
 * into each and every object.
 *
 * So you can, for instance, define a default codeblock 1 for "news articles"
 * and all your news articles will display that block. It can be overridden for
 * an individual news article by setting codeblock 1 for that article.
 *
 *
 * @author Stephen Billard (sbillard)
 *
 * @package plugins/defaultCodeblocks
 * @pluginCategory theme
 */
$plugin_is_filter = 500 | ADMIN_PLUGIN | THEME_PLUGIN;
$plugin_description = gettext('Create default codeblocks.');

$option_interface = 'defaultCodeblocks';

npgFilters::register('codeblock', 'defaultCodeblocks::codeblock');

class defaultCodeblocks {

	public $codeblocks;
	public $blocks = array();
	public $table = NULL; //	the DB table for this instantiation of defaultCodeblocks

	function __construct() {
		$this->blocks = array('gallery' => NULL, 'albums' => NULL, 'images' => NULL, 'news' => NULL, 'pages' => NULL);
		if (OFFSET_PATH == 2) {
			//	migrate the legacy codeblocks
			$oldoptions = getOptionsLike('defaultCodeblocks_object');
			if ($oldoptions) {
				$block = query_single_row("SELECT id, `aux`, `data` FROM " . prefix('plugin_storage') . " WHERE `type` = 'defaultCodeblocks' AND `subtype` IS NULL");
				if ($block) {
					query('DELETE FROM ' . prefix('plugin_storage') . ' WHERE `id`=' . $block['id']);
				} else {
					$block['data'] = serialize(array());
				}
				foreach (array_keys($oldoptions) as $target) {
					$object = preg_replace('~defaultCodeblocks_object_~i', '', $target);
					if (array_key_exists($object, $this->blocks)) {
						$sql = 'INSERT INTO ' . prefix('plugin_storage') . ' (`type`, `subtype`, `aux`,`data`) VALUES ("defaultCodeblocks",' . db_quote($object) . ',"",' . db_quote($block['data']) . ')';
						query($sql);
					}
					purgeOption($target);
				}
				purgeOption('defaultCodeblocks');
			}
		}

		$blocks = query_full_array("SELECT id, `subtype`, `aux`, `data` FROM " . prefix('plugin_storage') . " WHERE `type` = 'defaultCodeblocks'");
		foreach ($blocks as $block) {
			if ($block['subtype']) {
				if ($block['subtype'] == 'news_categories') {
					//	there are no news_category pages, thus this needs to be removed
					query('DELETE FROM ' . prefix('plugin_storage') . ' WHERE `id`=' . $block['id']);
				} else {
					$this->blocks[$block['subtype']] = $block['data'];
				}
			}
		}
		foreach ($this->blocks as $object => $block) {
			if (is_null($block)) {
				$this->blocks[$object] = serialize(array());
				$sql = 'INSERT INTO ' . prefix('plugin_storage') . ' (`type`, `subtype`, `aux`,`data`) VALUES ("defaultCodeblocks",' . db_quote($object) . ',"",' . db_quote($this->blocks[$object]) . ')';
				query($sql);
			}
		}
	}

	function getOptionsSupported() {
		$xlate = array('gallery' => gettext('Gallery'), 'albums' => gettext('Albums'), 'images' => gettext('Images'), 'news' => gettext('Articles'), 'pages' => gettext('Pages'));

		foreach ($this->blocks as $object => $block) {
			$options [$xlate[$object]] = array('key' => 'defaultCodeblocks_' . $object, 'type' => OPTION_TYPE_CUSTOM,
					'order' => 2,
					'desc' => sprintf(gettext('Codeblocks to be inserted when the one for the <em>%s</em> object is empty.'), $xlate[$object])
			);
		}
		codeblocktabsJS();
		return $options;
	}

	function handleOption($option, $currentValue) {
		$option = str_replace('defaultCodeblocks_', '', $option);
		$this->table = $option;
		printCodeblockEdit($this, $option);
	}

	function handleOptionSave($themename, $themealbum) {
		if (npg_loggedin(CODEBLOCK_RIGHTS)) {
			foreach ($this->blocks as $object => $block) {
				$this->table = $object;
				processCodeblockSave($object, $this);
			}
		}
		return false;
	}

	/**
	 * Returns the codeblocks as an serialized array
	 *
	 * @return array
	 */
	function getCodeblock() {
		return npgFunctions::unTagURLs($this->blocks[$this->table]);
	}

	/**
	 * set the codeblocks as an serialized array
	 *
	 */
	function setCodeblock($cb) {
		$this->blocks[$this->table] = npgFunctions::tagURLs($cb);
		$sql = 'UPDATE ' . prefix('plugin_storage') . ' SET `data`=' . db_quote($this->blocks[$this->table]) . ' WHERE `type`="defaultCodeblocks" AND `subtype`=' . db_quote($this->table);
		query($sql);
	}

	static function codeblock($current, $object, $number) {
		global $_defaultCodeBlocks;
		if (empty($current)) {
			if (!$_defaultCodeBlocks) {
				$_defaultCodeBlocks = new defaultCodeblocks();
			}
			$_defaultCodeBlocks->table = $object->table;
			$blocks = getSerializedArray($_defaultCodeBlocks->getCodeblock());
			if (isset($blocks[$number])) {
				$current = $blocks[$number];
			}
		}
		return $current;
	}

}

?>