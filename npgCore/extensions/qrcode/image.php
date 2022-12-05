<?php

/*
 * PHP QR Code encoder
 *
 * feeds a QR code image
 *
 */

require_once ('qrlib.php');
QRcode::png($_REQUEST['content']);
