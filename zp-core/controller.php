<?php

/**
 * Root-level include that handles all user requests.
 * @package core
 */
// force UTF-8 Ø

/* * * Request Handler **********************
 * **************************************** */
// This is the main top-level action handler for user requests. It parses a
// request, validates the input, loads the appropriate objects, and sets
// the context. All that is done in lib-controller.php.

Controller::load_gallery(); //	load the gallery and set the context to be on the front-end
$_requested_object = Controller::load_request();
// handle any passwords that might have been posted
if (!npg_loggedin()) {
	handle_password();
}

// Handle any comments that might be posted.
$_comment_error = npgFilters::apply('handle_comment', false);

/* * * Consistent URL redirection ***********
 * **************************************** */
// Check to see if we use mod_rewrite, but got a query-string request for a page.
// If so, redirect with a 301 to the correct URL.
// This is mostly helpful for SEO, but also for users. Consistent URLs are a Good Thing.

Controller::fix_path_redirect();
?>
