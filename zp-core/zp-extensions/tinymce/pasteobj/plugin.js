/**
 *
 * pasteobj plugin for tinyMCE
 * @author Stephen Billard (sbillard)
 *
 * @Copyright 2014 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics and derivatives}
 *
 */

tinymce.PluginManager.add('pasteobj', function (editor, url) {

	function _onAction(editor, url) {
		// Open window with a specific url
		var windowManagerURL = url.replace('/plugins/pasteobj', '/pasteobj/pasteobj.php'),
						windowManagerCSS = '<style type="text/css">' +
						'.tox-dialog {max-width: 100%!important; width:800px!important; overflow: hidden; height:600px!important; bborder-radius:0.25em;}' +
						'.tox-dialog__header{ border-bottom: 1px solid lightgray!important; }' + // for custom header in filemanage
						'.tox-dialog__footer { display: none!important; }' + // for custom footer in filemanage
						'.tox-dialog__body { padding: 5!important; }' +
						'.tox-dialog__body-content > div { height: 100%; overflow:hidden}' +
						'</style > ';

		editor.windowManager.open({
			title: 'netPhotoGraphics:obj',
			body: {
				type: 'panel',
				items: [{
						type: 'htmlpanel',
						html: windowManagerCSS + '<iframe src="' + windowManagerURL + '"  frameborder="0" style="width:100%; height:100%"></iframe>'
					}]
			},
			buttons: []
		});
	}

	// Add a button that opens a window
	editor.ui.registry.addButton('pasteobj', {
		icon: "paste",
		tooltip: "netPhotoGraphics:obj",
		onAction: function () {
			_onAction(editor, url);
		}
	});
	// Adds a menu item to the tools menu
	editor.ui.registry.addMenuItem('pasteobj', {
		icon: "paste",
		text: 'netPhotoGraphics:obj...',
		onAction: function () {
			_onAction(editor, url);
		}
	});

});