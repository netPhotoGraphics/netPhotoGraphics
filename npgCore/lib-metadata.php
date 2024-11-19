<?php

class Metadata {

	const IPTCtags = array(
			'SKIP' => '2#000', //	Record Version										Size:64
			'ObjectType' => '2#003', //	Object Type	Ref										Size:67
			'ObjectAttr' => '2#004', //	Object Attribute Ref							Size:67
			'ObjectName' => '2#005', //	Object name												Size:64
			'EditStatus' => '2#007', //	Edit Status												Size:64
			'EditorialUpdate' => '2#008', //	Editorial Update									Size:2
			'Urgency' => '2#010', //	Urgency														Size:1
			'SubRef' => '2#012', //	Subject	Reference									Size:236
			'Category' => '2#015', //	Category 													Size:3
			'SuppCategory' => '2#020', //	Supplemental category							Size:32
			'FixtureID' => '2#022', //	Fixture	ID 												Size:32
			'Keywords' => '2#025', //	Keywords 													Size:64
			'ContentLocationCode' => '2#026', //	Content	Location Code							Size:3
			'ContentLocationName' => '2#027', //	Content	Location Name							Size:64
			'ReleaseDate' => '2#030', //	Release	Date 											Size:8
			'ReleaseTime' => '2#035', //	Release	Time											Size:11
			'ExpireDate' => '2#037', //	Expiration Date										Size:8
			'ExpireTime' => '2#038', //	Expiration Time										Size:11
			'SpecialInstru' => '2#040', //	Special Instructions							Size:256
			'ActionAdvised' => '2#042', //	Action Advised										Size:2
			'RefService' => '2#045', //	Reference Service									Size:10
			'RefDate' => '2#047', //	Reference Date										Size:8
			'RefNumber' => '2#050', //	Reference Number									Size:8
			'DateCreated' => '2#055', //	Date created											Size:8
			'TimeCreated' => '2#060', //	Time created											Size:11
			'DigitizeDate' => '2#062', //	Digital Creation Date							Size:8
			'DigitizeTime' => '2#063', //	Digital Creation Time							Size:11
			'OriginatingProgram' => '2#065', //	Originating Program								Size:32
			'ProgramVersion' => '2#070', //	Program version										Size:10
			'ObjectCycle' => '2#075', //	Object Cycle											Size:1
			'ByLine' => '2#080', //	ByLine 														Size:32
			'ByLineTitle' => '2#085', //	ByLine Title											Size:32
			'City' => '2#090', //	City															Size:32
			'SubLocation' => '2#092', //	Sublocation												Size:32
			'State' => '2#095', //	Province/State										Size:32
			'LocationCode' => '2#100', //	Country/Primary	Location Code			Size:3
			'LocationName' => '2#101', //	Country/Primary	Location Name			Size:64
			'TransmissionRef' => '2#103', //	Original Transmission Reference		Size:32
			'ImageHeadline' => '2#105', //	Image headline										Size:256
			'ImageCredit' => '2#110', //	Image credit											Size:32
			'Source' => '2#115', //	Source														Size:32
			'Copyright' => '2#116', //	Copyright Notice									Size:128
			'Contact' => '2#118', //	Contact														Size:128
			'ImageCaption' => '2#120', //	Image caption											Size:2000
			'ImageCaptionWriter' => '2#122', //	Image caption writer							Size:32
			'ImageType' => '2#130', //	Image type												Size:2
			'Orientation' => '2#131', //	Image	orientation									Size:1
			'LangID' => '2#135', //	Language ID												Size:3
			'Subfile' => '8#010' //	Subfile														Size:2
	);
	const EXIF_SOURCE = array(
			'Artist' => ['METADATA_SOURCE' => 'IFD0', 'METADATA_KEY' => 'EXIFArtist'],
			'Contrast' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFContrast'],
			'Copyright' => ['METADATA_SOURCE' => 'IFD0', 'METADATA_KEY' => 'EXIFCopyright'],
			'DateTime' => ['METADATA_SOURCE' => 'IFD0', 'METADATA_KEY' => 'EXIFDateTime'],
			'DateTimeDigitized' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFDateTimeDigitized'],
			'DateTimeOriginal' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFDateTimeOriginal'],
			'ExifImageLength' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFImageWidth'],
			'ExifImageWidth' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFImageHeight'],
			'ExposureBiasValue' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFExposureBiasValue'],
			'ExposureTime' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFExposureTime'],
			'Flash' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFFlash'],
			'FNumber' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFFNumber'],
			'FocalLength' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFFocalLength'],
			'FocalLengthIn35mmFilm' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFFocalLengthIn35mmFilm'],
			'GPSAltitude' => ['METADATA_SOURCE' => 'GPS', 'METADATA_KEY' => 'EXIFGPSAltitude'],
			'GPSAltitudeRef' => ['METADATA_SOURCE' => 'GPS', 'METADATA_KEY' => 'EXIFGPSAltitudeRef'],
			'GPSLatitude' => ['METADATA_SOURCE' => 'GPS', 'METADATA_KEY' => 'EXIFGPSLatitude'],
			'GPSLatitudeRef' => ['METADATA_SOURCE' => 'GPS', 'METADATA_KEY' => 'EXIFGPSLatitudeRef'],
			'GPSLongitude' => ['METADATA_SOURCE' => 'GPS', 'METADATA_KEY' => 'EXIFGPSLongitude'],
			'GPSLongitudeRef' => ['METADATA_SOURCE' => 'GPS', 'METADATA_KEY' => 'EXIFGPSLongitudeRef'],
			'ImageDescription' => ['METADATA_SOURCE' => 'IFD0', 'METADATA_KEY' => 'EXIFDescription'],
			'ISOSpeedRatings' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFISOSpeedRatings'],
			'LensInfo' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFLensInfo'],
			'LensType' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFLensType'],
			'Make' => ['METADATA_SOURCE' => 'IFD0', 'METADATA_KEY' => 'EXIFMake'],
			'MeteringMode' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFMeteringMode'],
			'Model' => ['METADATA_SOURCE' => 'IFD0', 'METADATA_KEY' => 'EXIFModel'],
			'Orientation' => ['METADATA_SOURCE' => 'IFD0', 'METADATA_KEY' => 'EXIFOrientation'],
			'Saturation' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFSaturation'],
			'Sharpness' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFSharpness'],
			'ShutterSpeedValue' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFShutterSpeedValue'],
			'Software' => ['METADATA_SOURCE' => 'IFD0', 'METADATA_KEY' => 'EXIFSoftware'],
			'SubjectDistanceRange' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFSubjectDistance'],
			'WhiteBalance' => ['METADATA_SOURCE' => 'SubIFD', 'METADATA_KEY' => 'EXIFWhiteBalance']
	);

	/**
	 * convert a fractional representation to something more user friendly
	 *
	 * @param $v string
	 * @return string
	 */
	static function rationalNum($v) {
		if (!empty($v)) {
			if (preg_match('~^(\d*)/(\d*)$~', $v, $matches)) {
				if (isset($matches[2]) && $matches[2]) {
					if ($matches[2] == 1) {
						return $matches[1];
					} else if ($matches[2] != 0) {
						return (float) $matches[1] / $matches[2];
					}
				}
			}
		}
		return $v;
	}

	/**
	 * Converts a floating point number into a simple fraction.
	 *
	 * @author Jake Olefsky jake@olefsky.com, modified by Stephen Billard stephen@sbillard.org
	 *
	 * @param float $v
	 * @param float $tol the tolerance, e.g. how simple a fraction
	 * @return string fractional representation of $v
	 */
	static function toFraction($v, $tol = 0.05) {
		if ($v == 0 || is_int($v)) {
			return intval($v) . '/1';
		}
		for ($n = 1; $n < 100; $n++) {
			$v1 = 1 / $v * $n;
			$d = round($v1, 0);
			if (abs($d - $v1) < $tol) {
				break; // within tolerance
			}
		}
		return $n . '/' . $d;
	}

	/**
	 * Formats gps coordinate to degree min sec format
	 *
	 * @param type $dec
	 * @param type $ref
	 * @return type
	 */
	static function toDMS($dec, $ref) {
		// strange things happen with locales, so best to be "separator blind"
		$d = preg_split('/[:,\.]/', str_replace('-', '', $dec) . '.0');
		$tempma = $d[1] * pow(10, -strlen($d[1]));
		$tempma = $tempma * 3600;
		$min = floor($tempma / 60);
		$sec = round($tempma - ($min * 60), 2);
		if ($sec >= 60) {
			$min++;
			$sec = $sec - 60;
		}
		return sprintf('%d° %d\' %.2f" %s', $d[0], $min, $sec, $ref);
	}

	/**
	 * Fetches the IPTC array for a single tag.
	 *
	 * @param string $tag the metadata tag sought
	 * @return array
	 */
	private static function getIPTCTagArray($tag, $iptc) {
		if (array_key_exists($tag, $iptc)) {
			return $iptc[$tag];
		}
		return NULL;
	}

	/**
	 * Fetches a single tag from IPTC data
	 *
	 * @param string $tag the metadata tag sought
	 * @return string
	 */
	private static function getIPTCTag($tag, $iptc) {
		if (isset($iptc[$tag])) {
			$iptcTag = $iptc[$tag];
			$r = "";
			$ct = count($iptcTag);
			for ($i = 0; $i < $ct; $i++) {
				$w = $iptcTag[$i];
				if (!empty($r)) {
					$r .= ", ";
				}
				$r .= $w;
			}
			return trim($r);
		}
		return '';
	}

	/**
	 * Returns the IPTC data converted into UTF8
	 *
	 * @param string $iptcstring the IPTC data
	 * @param string $characterset the internal encoding of the data
	 * @return string
	 */
	private static function prepIPTCString($iptcstring, $characterset) {
		global $_UTF8;
		// Remove null byte at the end of the string if it exists.
		if (substr($iptcstring, -1) === 0x0) {
			$iptcstring = substr($iptcstring, 0, -1);
		}
		if ($characterset != LOCAL_CHARSET) {
			$iptcstring = $_UTF8->convert($iptcstring, $characterset, LOCAL_CHARSET);
		}
		return trim(sanitize($iptcstring, 1));
	}

	/**
	 * Formats the exposure value.
	 * @author Jake Olefsky jake@olefsky.com
	 *
	 */
	static function exposure($data) {
		if (strpos($data, '/') === false) {
			$data = floatval(str_replace(',', '.', $data)); // deal with European decimal separator
			if ($data >= 1) {
				return round($data, 2);
			} else {
				return self::toFraction($data);
			}
		} else {
			return 'B';
		}
	}

	/**
	  The ShutterSpeedValue is given in the APEX mode. Many thanks to Matthieu Froment for this code
	  The formula is : Shutter = - log2(exposureTime) (Appendix C of EXIF spec.)
	  Where shutter is in APEX, log2(exposure) = ln(exposure)/ln(2)
	  So final formula is : exposure = exp(-ln(2).shutter)
	  The formula can be developed : exposure = 1/(exp(ln(2).shutter))
	 */
	static function shutterSpeed($datum) {
		$datum = self::rationalNum($datum);
		if (is_numeric($datum)) {
			$datum = exp($datum * log(2));
			if ($datum != 0) {
				$datum = 1 / $datum;
			}
		}
		$data = self::exposure($datum);
		return $data;
	}

	/**
	 * Provides an error protected read of image EXIF/IPTC data
	 *
	 * @param string $path image path
	 * @return array
	 *
	 */
	static function read_exif($path) {
		static $php_fixes = [
				//	definitions for some "undefined" tags
				'UndefinedTag:0xA432' => 'LensInfo',
				'UndefinedTag:0xA434' => 'LensType'
		];

		$rslt = [];
		if (exif_imagetype($path)) {
			$e = error_reporting(0);
			$php_rslt = exif_read_data($path);
			error_reporting($e);

			//	cleanup some phpEXIF shortcommings
			foreach ($php_fixes as $php => $exif) {
				if (!isset($php_rslt[$exif]) && isset($php_rslt[$php])) {
					$php_rslt[$exif] = $php_rslt[$php];
				}
			}

			if (!empty($php_rslt)) {
				//	first "handle" the "COMPUTED" elements whose EXIF data may be missing.
				if (!isset($php_rslt['ExifImageLength']) && isset($php_rslt['COMPUTED']['Height'])) {
					$php_rslt['ExifImageLength'] = $php_rslt['COMPUTED']['Height'];
				}
				if (!isset($php_rslt['ExifImageWidth']) && isset($php_rslt['COMPUTED']['Width'])) {
					$php_rslt['ExifImageWidth'] = $php_rslt['COMPUTED']['Width'];
				}
				if (!isset($php_rslt['FNumber']) && isset($php_rslt['COMPUTED']['ApertureFNumber'])) {
					$php_rslt['FNumber'] = self::toFraction(floatval(str_replace('f/', '', $php_rslt['COMPUTED']['ApertureFNumber'])));
				}
				if (!isset($php_rslt['Copyright']) && isset($php_rslt['COMPUTED']['Copyright'])) {
					$php_rslt['Copyright'] = $php_rslt['COMPUTED']['Copyright'];
				}

				//	add EXIF_SOURCE
				foreach (self::EXIF_SOURCE as $phpExif => $data) {
					if (array_key_exists($phpExif, $php_rslt)) {
						$v = $php_rslt[$phpExif];
						if (is_array($v)) {
							$t = '';
							foreach ($v as $f) {
								$f = self::rationalNum($f);
								$t .= $f . ':';
							}
							$v = rtrim($t, ':');
						} else {
							$v = self::rationalNum($v);
						}
						$rslt[$data['METADATA_SOURCE']][$data['METADATA_KEY']] = $v;
					}
				}
			}
		}
		return $rslt;
	}

	/**
	 * Parses Exif/IPTC data
	 *
	 */
	static function update($obj) {
		global $_exifvars, $_gallery;
		if ($_exifvars) {
			$obj->set('hasMetadata', 0);
			foreach ($_exifvars as $field => $exifvar) {
				$obj->set($field, NULL);
			}

			if (get_class($obj) == 'Image') {
				$localpath = $obj->localpath;
			} else {
				$localpath = $obj->getThumbImageFile();
			}

			if (!empty($localpath)) { // there is some kind of image to get metadata from

				/* check EXIF data */
				$exifraw = self::read_exif($localpath);
				if (!empty($exifraw)) {
					$obj->set('hasMetadata', 1);
					foreach ($exifraw as $source => $content) {
						foreach ($content as $field => $value) {
							switch ($field) {
								case 'EXIFShutterSpeedValue':
									$value = self::shutterSpeed($value);
									break;
								case 'EXIFExposureTime':
									$value = self::exposure($value);
									break;
								case 'EXIFFNumber':
									$value = round(self::rationalNum($value), 1);
									break;
							}
							$obj->set($field, $value);
						}
					}
				}

				/* check IPTC data */
				$iptcdata = gl_imageIPTC($localpath);
				if (!empty($iptcdata)) {
					$iptc = iptcparse($iptcdata);
					if ($iptc) {
						$obj->set('hasMetadata', 1);
						$characterset = self::getIPTCTag('1#090', $iptc);
						if (!$characterset) {
							$characterset = getOption('IPTC_encoding');
						} else if (substr($characterset, 0, 1) == chr(27)) { // IPTC escape encoding
							$characterset = substr($characterset, 1);
							if ($characterset == '%G') {
								$characterset = 'UTF-8';
							} else {
								// we don't know, need to understand the IPTC standard here. In the mean time, default it.
								$characterset = getOption('IPTC_encoding');
							}
						} else if ($characterset == 'UTF8') {
							$characterset = 'UTF-8';
						}
						// Extract IPTC fields of interest
						foreach ($_exifvars as $field => $exifvar) {
							if ($exifvar[METADATA_SOURCE] == 'IPTC') {
								$datum = self::getIPTCTag(self::IPTCtags[$exifvar[METADATA_KEY]], $iptc);
								$value = self::prepIPTCString($datum, $characterset);
								switch ($exifvar[METADATA_FIELD_TYPE]) {
									case 'time':
										$value = substr($value, 0, 6); //	strip off any timezone indicator
									case 'date':
									case 'datetime':
										if (!$value) {
											$value = NULL;
										}
										break;
								}
								$obj->set($field, $value);
							}
						}
						/* iptc keywords (tags) */
						if ($_exifvars['IPTCKeywords'][METADATA_FIELD_ENABLED]) {
							$datum = self::getIPTCTagArray(self::IPTCtags['Keywords'], $iptc);
							if (is_array($datum)) {
								$tags = array();
								foreach ($datum as $item) {
									$tags[] = self::prepIPTCString(sanitize($item, 3), $characterset);
								}
								$obj->setTags($tags);
							}
						}
					}
				}
			}
			/* "import" metadata into database fields as makes sense */

			/* Image Rotation */
			$rotation = $obj->fetchMetadata('EXIFOrientation');
			if ($rotation) {
				$rotation = substr(trim($rotation, '!'), 0, 1);
			} else {
				$rotation = 0;
			}
			$obj->set('rotation', $rotation);

			/* "date" field population */
			if ($date = $obj->fetchMetadata('IPTCDateCreated')) {
				if (strlen($date) > 8) {
					$time = substr($date, 8);
				} else {
					/* got date from IPTC, now must get time */
					$time = $obj->get('IPTCTimeCreated');
				}
				$date = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
				if (!empty($time)) {
					$date = $date . ' ' . substr($time, 0, 2) . ':' . substr($time, 2, 2) . ':' . substr($time, 4, 2);
				}
			}
			if (empty($date)) {
				$date = $obj->fetchMetadata('EXIFDateTime');
			}
			if (empty($date)) {
				$date = $obj->fetchMetadata('EXIFDateTimeOriginal');
			}
			if (empty($date)) {
				$date = $obj->fetchMetadata('EXIFDateTimeDigitized');
			}
			if (empty($date)) {
				$obj->setDateTime(date('Y-m-d H:i:s', $obj->filemtime));
			} else {
				$obj->setDateTime($date);
			}

			/* "title" field population */
			$title = $obj->fetchMetadata('IPTCObjectName');
			if (empty($title)) {
				$title = $obj->fetchMetadata('IPTCImageHeadline');
			}
			if (empty($title)) {
				$title = $obj->fetchMetadata('EXIFDescription'); //EXIF title [sic]
			}
			if (!empty($title)) {
				$title = str_replace('&#xA;', "\n", $title); //	line feed so nl2br works
				if (getoption('transform_newlines')) {
					$title = str_replace("\n", '', nl2br($title)); //	nl2br leaves the linefeed in
				}
				$obj->setTitle($title);
			}

			/* "description" field population */
			$desc = $obj->fetchMetadata('IPTCImageCaption');
			if (!empty($desc)) {
				$desc = str_replace('&#xA;', "\n", $desc); //	line feed so nl2br works
				if (getoption('transform_newlines')) {
					$desc = str_replace("\n", '', nl2br($desc)); //	nl2br leaves the linefeed in
				}
				$obj->setDesc($desc);
			}

			/* GPS data */
			foreach (array('EXIFGPSLatitude', 'EXIFGPSLongitude') as $source) {
				$data = $obj->fetchMetadata($source);
				if (!empty($data)) {
					$ref = strtoupper($obj->get($source . 'Ref'));
					$obj->set($source, self::toDMS($data, $ref));
					if (in_array($ref, array('S', 'W'))) {
						$data = '-' . $data;
					}
					$obj->set(substr($source, 4), $data);
				}
			}

			$alt = $obj->fetchMetadata('EXIFGPSAltitude');
			if (!empty($alt)) {
				if ($obj->fetchMetadata('EXIFGPSAltitudeRef') == '-') {
					$alt = -$alt;
				}
				$obj->set('GPSAltitude', $alt);
			}

			/* simple field imports */
			$import = array(
					'location' => 'IPTCSubLocation',
					'city' => 'IPTCCity',
					'state' => 'IPTCState',
					'country' => 'IPTCLocationName',
					'copyright' => 'IPTCCopyright'
			);
			foreach ($import as $key => $source) {
				$data = $obj->fetchMetadata($source);
				$obj->set($key, $data);
			}

			/* "credit" field population */
			$credit = $obj->fetchMetadata('IPTCByLine');
			if (empty($credit)) {
				$credit = $obj->fetchMetadata('IPTCImageCredit');
			}
			if (empty($credit)) {
				$credit = $obj->fetchMetadata('IPTCSource');
			}
			if (!empty($credit)) {
				$obj->setCredit($credit);
			}

			npgFilters::apply('image_metadata', $obj);

			$alb = $obj->album;
			if (is_object($alb)) {
				if (!$obj->get('owner')) {
					$obj->setOwner($alb->getOwner());
				}
				$save = false;
				if (strtotime($alb->getUpdatedDate()) < strtotime($obj->getDateTime())) {
					$alb->setUpdatedDate($obj->getDateTime());
					$save = true;
				}
				if (!($albdate = $alb->getDateTime()) || ($_gallery->getAlbumUseImagedate() && strtotime($albdate) < strtotime($obj->getDateTime()))) {
					$alb->setDateTime($obj->getDateTime()); //  not necessarily the right one, but will do. Can be changed in Admin
					$save = true;
				}
				if ($save) {
					$alb->save();
				}
			}
		}
	}

}
