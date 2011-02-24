<?php
/**
 * Example showing how to copy ICC profile from one JPEG file to another.
 *
 * This is usefull (and purpose for this class) in photo gallery to maintain
 * the color profile after resampling the image or other image manipulation
 * with GD library, which doesn't support the ICC profiles.
 *
 * @author Richard Toth aka risko (risko@risko.org)
 * @version 1.0
 */
error_reporting(E_ALL);
require_once('../class.jpeg_icc.php');

$source_file = '../test_data/in-fogra.jpg';
$destination_file = '../test_data/out-noicc.jpg';

$o = new JPEG_ICC();
$o->LoadFromJPEG($source_file);
$o->SaveToJPEG($destination_file);

// show the output
header('Content-Type: image/jpeg');
readfile($destination_file);
?>
