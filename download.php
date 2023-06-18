<?php

use edydeyemi\S3Test\FileHandler;

include 'vendor/autoload.php';
include 'FileHandler.php';

$filename = $_GET['file'];
if (!$filename) {
    header('Location:index.php');
}
$ops = new FileHandler;
$ops->downloadFile($filename);
header('Location:index.php');
