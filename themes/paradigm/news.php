<?php
// force UTF-8 Ø
if (!defined('WEBPATH'))
	die();
if (class_exists("CMS")) {
	?>

	<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/head.php'); ?>
	<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/header.php'); ?>

	<?php if (class_exists('RSS')) printRSSHeaderLink("News", NEWS_LABEL, ""); ?>


	<div id="background-main" class="background">
		<div class="container<?php
		if (getOption('full_width')) {
			echo '-fluid';
		}
		?>">
					 <?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/breadcrumbs.php'); ?>
			<div id="center" class="row" itemscope itemtype="http://schema.org/WebPage">
				<section class="col-sm-9" id="main" itemprop="mainContentOfPage">

					<?php
					// single news article
					if (is_NewsArticle()) {
						?><article itemscope itemtype="http://schema.org/Article">
							<h1 itemprop="name"><?php printNewsTitle(); ?></h1>
							<small>
								<?php
								echo '<span itemprop="datePublished">';
								printNewsDate();
								echo '</span>';
								if (function_exists('getCommentCount')) {
									?>
									|
									<?php
									echo gettext("Comments:");
									?>
									<?php
									echo '<span itemprop="commentCount">';
									echo getCommentCount();
									echo '</span>';
								}
								?>
								<?php
								echo ' | <span itemprop="articleSection">';
								printNewsCategories(", ", "Categories: ", "list-inline news-info");
								echo '</span>'
								?>
								<?php
								if (getTags()) {
									echo ' | ';
									printTags_zb("links", "Tags: ", "list-inline news-info", ",");
								}
								?>
							</small>

							<div itemprop="articleBody" class="content"><?php printNewsContent(); ?></div>

							<!-- Extra content -->
							<?php
							if (getNewsExtraContent() != '') {
								echo '<div class="content">';
								printNewsExtraContent();
								echo "</div>";
							}
							?>

							<p><?php printCodeblock(1); ?></p>


							<ul class="pager">
								<?php if (getPrevNewsURL()) { ?><li class="pull-left"><?php printPrevNewsLink(); ?></li><?php } ?>
								<?php if (getNextNewsURL()) { ?><li class="pull-right"><?php printNextNewsLink(); ?></li><?php } ?>
							</ul>

							<?php
							// COMMENTS TEST
							if (function_exists('printCommentForm')) printCommentForm();
							?>
						</article>
						<!-- pagination -->
						<?php
					} else {
						echo '<h1>';
						printCurrentNewsCategory();
						echo '</h1>';
						printNewsCategoryDesc();
						// news article loop
						while (next_news()):;
							?>
							<div>
								<h2><?php printNewsURL(); ?></h2>
								<small>
									<?php
									printNewsDate();
									if (function_exists('getCommentCount')) {
										?>
										|
										<?php
										echo gettext("Comments:");
										?>
										<?php
										echo getCommentCount();
									}
									?>
									<?php
									echo ' | ';
									printNewsCategories(", ", "Categories: ", "list-inline news-info");
									?>
									<?php
									if (getTags()) {
										echo ' | ';
										printTags("links", "Tags: ", "list-inline news-info", ",");
									}
									?>
								</small>
								<article class="content">
									<?php printNewsContent(); ?>
								</article>
								<div><?php printCodeblock(1); ?></div>

								<hr/>

							</div>
							<?php
						endwhile;
						printNewsPageListWithNav(gettext('next »'), gettext('« prev'), true, 'pagelist', true);
					}
					?>
				</section>
				<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/sidebar.php'); ?>
			</div>
		</div>
	</div>

	<?php include(SERVERPATH . '/' . THEMEFOLDER . '/paradigm/includes/footer.php'); ?>

	<?php
} else {
	include(CORE_SERVERPATH . '404.php');
}
?>