<?php include('includes/header.php'); ?>

<header class="page-header">
    <h1 class="page-title"><?php printPageTitle(); ?></h1>
</header>

<?php if (hasPages()) { nova_printSubPages(); } ?>

<div class="content-layout<?php echo getPageExtraContent() ? ' has-aside' : ''; ?>">
    <article class="post">
        <div class="post-content prose">
            <?php printPageContent(); ?>
            <?php printCodeblock(1); ?>
        </div>
    </article>
    <?php if (getPageExtraContent()) { ?>
        <aside class="extra-content prose"><?php printPageExtraContent(); ?></aside>
    <?php } ?>
</div>

<?php if (extensionEnabled('comment_form')) { include('includes/print_comment.php'); } ?>

<?php include('includes/footer.php'); ?>
