<?php

/*
 * PHP QR Code encoder
 *
 * feeds a QR code image
 *
 */

require_once(dirname(dirname(__DIR__)) . '/functions-basic.php');

require_once ('qrlib.php');

$iMutex = new npgMutex('i', PROCESSING_CONCURENCY);
$iMutex->lock();
QRcode::png($_REQUEST['content']);
$iMutex->unlock();
