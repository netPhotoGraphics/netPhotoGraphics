<div id="header">
    <h3 style="float:left; padding-left: 32px;">
        <a href="<?php echo getGalleryIndexURL(); ?>"><img src="<?php echo $_themeroot; ?>/images/banner.png"/></a>
    </h3>
    <h3 style="float:right; padding-top: 22px;">
        <?php if (getOption('Allow_search')) {  
        	printSearchForm(NULL, 'search', NULL, gettext('Search'), $_themeroot . '/images/filter.png'); 
        } ?>
    </h2>
</div>