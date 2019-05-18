/**
 *
 * pasteobj plugin for tinyMCE
 * @author Stephen Billard (sbillard)
 *
 * @Copyright 2014 by Stephen L Billard for use in {@link https://%GITHUB% netPhotoGraphics} and derivatives
 *
 */

tinymce.PluginManager.add('pasteobj', function (editor, url) {

	// Add a button that opens a window
	editor.ui.registry.addButton('pasteobj', {
		icon: "paste",
		tooltip: "netPhotoGraphics:obj",
		onAction: function () {
			editor.windowManager.openUrl(pasteObjConfig);
		}
	});
	// Adds a menu item to the tools menu
	editor.ui.registry.addMenuItem('pasteobj', {
		icon: "paste",
		text: 'netPhotoGraphics:obj...',
		onAction: function () {
			editor.windowManager.openUrl(pasteObjConfig);
		}
	});

});