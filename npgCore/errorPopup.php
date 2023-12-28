<?php

/**
 * Displays database failure message
 *
 * @author Stephen Billard (sbillard)
 *
 * @package core
 */
function displayQueryError($what, $brief, $whom) {
	if (!empty($whom)) {
		$whom .= "\n" . gettext('caused') . "\n";
	}
	$log = $what . "\n" . $whom . $brief;
	if (defined('TESTING_MODE') && TESTING_MODE) {
		trigger_error($log, E_USER_ERROR);
	} else {
		debugLogBacktrace($log);
		?>
		<style type="text/css">
			.reasonbox {
				text-align: left;
				padding-top: 10px;
				padding-bottom: 10px;
				padding-left: 10px;
				padding-right: 10px;
				color: black;
				background-color: #FFEFB7;
				border-width: 1px 1px 2px 1px;
				border-color: #FFDEB5;
				border-style: solid;
				margin-bottom: 10px;
				font-size: 100%;
			}

			#error_content {
				text-align: left;
				padding-left: 1em;
				padding-right: 1em;
				padding-top: 10px;
				padding-bottom: 5px;
				top: 50px;
				min-width: 640px;
			}

			#error_content h1 {
				color: orangered;
				font-size: 2em;
				font-weight: bold;
				margin-bottom: 1em;
			}

			#dragbox {
				position: fixed;
				top: 50px;
				left: 100px;
				text-align: left;
				padding-left: 1em;
				padding-right: 1em;
				padding-top: 10px;
				padding-bottom: 5px;
				background-color: rgba(255,255,244,0.85);
				border: 3px solid #CBCBCB;
				min-width: 640px;
				z-index: 89990;
				clear: both;
			}

			#dragboxheader {
				cursor: move;
				z-index: 89995;
				color: gray;
				float: right;
			}
		</style>

		<!-- Draggable DIV -->
		<div id="dragbox">
			<!-- Include a header DIV with the same name as the draggable DIV, followed by "header" -->
			<span id="dragboxheader">
				<?php echo DRAG_HANDLE; ?>
			</span>
			<div id="error_content">
				<h1><?php echo $what; ?></h1>
				<div class="reasonbox">
					<?php echo $brief; ?>
				</div>

			</div>
		</div>



		<script type="text/javascript">
			// Make the DIV element draggable:
			dragElement(document.getElementById("dragbox"));

			function dragElement(elmnt) {
				var pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
				if (document.getElementById(elmnt.id + "header")) {
					// if present, the header is where you move the DIV from:
					document.getElementById(elmnt.id + "header").onmousedown = dragMouseDown;
				} else {
					// otherwise, move the DIV from anywhere inside the DIV:
					elmnt.onmousedown = dragMouseDown;
				}

				function dragMouseDown(e) {
					e = e || window.event;
					e.preventDefault();
					// get the mouse cursor position at startup:
					pos3 = e.clientX;
					pos4 = e.clientY;
					document.onmouseup = closeDragElement;
					// call a function whenever the cursor moves:
					document.onmousemove = elementDrag;
				}

				function elementDrag(e) {
					e = e || window.event;
					e.preventDefault();
					// calculate the new cursor position:
					pos1 = pos3 - e.clientX;
					pos2 = pos4 - e.clientY;
					pos3 = e.clientX;
					pos4 = e.clientY;
					// set the element's new position:
					elmnt.style.top = (elmnt.offsetTop - pos2) + "px";
					elmnt.style.left = (elmnt.offsetLeft - pos1) + "px";
				}

				function closeDragElement() {
					// stop moving when mouse button is released:
					document.onmouseup = null;
					document.onmousemove = null;
				}
			}
		</script>
		<?php
	}
	