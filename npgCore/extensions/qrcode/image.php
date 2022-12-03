<?php

/*
 * PHP QR Code encoder
 *
 * feeds a QR code image
 *
 */

require_once(dirname(dirname(__DIR__)) . '/functions-basic.php');

require_once ('qrlib.php');
QRcode::png($_REQUEST['content']);
