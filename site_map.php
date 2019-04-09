<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'phpmorphy-0.3.7/src/common.php';
include 'SiteMapCreator.php';
include 'MatrixHelper.php';

$dir = 'phpmorphy-0.3.7/dicts';
$lang = 'en_EN';
$opts = ['storage' => PHPMORPHY_STORAGE_FILE];

try {
    $morphy = new phpMorphy($dir, $lang, $opts);
} catch (phpMorphy_Exception $e) {
    die('Error occured while creating phpMorphy instance: ' . $e->getMessage());
}

$map = new SiteMapCreator();

//$map->refreshHostPaths();
//$map->updateFiles();
//sleep(1);
//$map->lemmatizeFiles($morphy);

$matrix = new MatrixHelper($map);
$matrix->buildMatrix();
