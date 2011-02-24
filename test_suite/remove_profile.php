<?php
/**
 * Example showing how to remove ICC profile from JPEG file.
 *
 * @author Richard Toth aka risko (risko@risko.org)
 * @version 1.0
 */
error_reporting(E_ALL);
require_once('../class.jpeg_icc.php');

$original_file = '../test_data/in-fogra.jpg';
$output_file = '../test_data/out-noicc.jpg';

$o = new JPEG_ICC();
$o->RemoveFromJPEG($original_file, $output_file, true);

// show the output
header('Content-Type: image/jpeg');
readfile($output_file);
?>
