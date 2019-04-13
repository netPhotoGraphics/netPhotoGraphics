<?php
include('inc_header.php');
require_once (SERVERPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/tag_extras.php');
?>

<!-- .container main -->
<!-- .page-header -->
<!-- .header -->
<h3><?php printGalleryTitle(); ?></h3>
</div><!-- .header -->
</div><!-- /.page-header -->

<div class="breadcrumb">
	<h4>
		<?php printGalleryIndexURL(' » ', getGalleryTitle(), false); ?><?php printParentBreadcrumb('', ' » ', ' » '); ?><?php printAlbumTitle(); ?>
	</h4>
</div>

<div class="page-header margin-bottom-reset">
	<?php printAlbumDesc(); ?>
</div>

<?php
$name = $_zp_current_album->name;
$tags_album = getAllTagsFromAlbum($name, false, 'images');
?>
<?php if (!empty($tags_album)) { ?>
	<div class="pager">
		<div class="btn-group filters-button-group">
			<button class="btn btn-default btn-sm active" data-filter="*">Toutes</button>
			<?php foreach ($tags_album as $tag) { ?>
				<button class="btn btn-default btn-sm" data-filter=".<?php echo $tag['name']; ?>"><?php echo $tag['name']; ?></button>
			<?php } ?>
		</div>
	</div>
<?php } ?>

<div id="isotope-wrap" class="margin-bottom-double">
	<div class="gutter-sizer"></div>
	<?php
	while (next_image(true)) {
		$fullimage = getFullImageURL();
		if (!empty($fullimage)) {
			$image_item_size_2 = '';
			if (getFullWidth() > getFullHeight()) {
				$image_item_size_2 = ' image-item-width2';
			} else if (getFullWidth() < getFullHeight()) {
				$image_item_size_2 = ' image-item-height2';
			}

			$tags_image = getTags();
			$tags_list = implode(' ', $tags_image);

			if ($tags_list <> '') {
				$class = $image_item_size_2 . ' ' . $tags_list;
			} else {
				$class = $image_item_size_2;
			}
			?>

			<div class="isotope-item image-item<?php echo $class; ?>">
				<a class="thumb" href="<?php echo html_encode(pathurlencode($fullimage)); ?>" title="<?php echo html_encode(getBareImageTitle()); ?>" data-fancybox="images">
					<?php
					if (getFullWidth() > getFullHeight()) {
						printCustomSizedImage(getBareImageTitle(), NULL, 235, 150, 235, 150, NULL, NULL, 'remove-attributes img-responsive', NULL, true);
					} else if (getFullWidth() < getFullHeight()) {
						printCustomSizedImage(getBareImageTitle(), NULL, 150, 235, 150, 235, NULL, NULL, 'remove-attributes img-responsive', NULL, true);
					} else {
						printCustomSizedImage(getBareImageTitle(), NULL, 150, 150, NULL, NULL, NULL, NULL, 'remove-attributes img-responsive', NULL, true);
					}
					?>
				</a>
			</div>
		<?php } ?>
	<?php } ?>
</div>
<?php
scriptLoader($_zp_themeroot . '/js/imagesloaded.pkgd.min.js');
scriptLoader($_zp_themeroot . '/js/isotope.pkgd.min.js');
scriptLoader($_zp_themeroot . '/js/packery-mode.pkgd.min.js');
?>
<script type="text/javascript">
//<![CDATA[
	// init Isotope after all images have loaded
	var $containter = $('#isotope-wrap').imagesLoaded(function () {
		$containter.isotope({
			itemSelector: '.isotope-item',
			layoutMode: 'packery',
			// packery layout
			packery: {
				gutter: '.gutter-sizer',
			}
		});
	});

	// bind filter button click
	$('.filters-button-group').on('click', 'button', function () {
		var filterValue = $(this).attr('data-filter');
		$containter.isotope({filter: filterValue});
	});

	// change is-active class on buttons
	$('.btn-group').each(function (i, buttonGroup) {
		var $buttonGroup = $(buttonGroup);
		$buttonGroup.on('click', 'button', function () {
			$buttonGroup.find('.active').removeClass('active');
			$(this).addClass('active');
		});
	});
//]]>
</script>

<?php if ((zp_loggedin()) && (extensionEnabled('favoritesHandler'))) { ?>
	<div class="favorites panel-group" role="tablist">
		<?php printAddToFavorites($_zp_current_album); ?>
	</div>
<?php } ?>

<?php if (simplemap::mapPlugin()) { ?>
	<?php include('inc_print_googlemap.php'); ?>
<?php } ?>

<?php if (extensionEnabled('comment_form')) { ?>
	<?php include('inc_print_comment.php'); ?>
<?php } ?>

</div><!-- /.container main -->

<?php include('inc_footer.php'); ?>