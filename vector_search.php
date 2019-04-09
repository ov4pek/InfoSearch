<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

include 'TfIdf_Helper.php';
require_once 'Matrix.php';
require_once 'phpmorphy-0.3.7/src/common.php';

$dir = 'phpmorphy-0.3.7/dicts';
$lang = 'en_EN';
$opts = ['storage' => PHPMORPHY_STORAGE_FILE];

try {
    $morphy = new phpMorphy($dir, $lang, $opts);
} catch (phpMorphy_Exception $e) {
    die('Error occured while creating phpMorphy instance: ' . $e->getMessage());
}

//print_r($_GET['find']);
$words = prepareSearch($morphy);
if (empty($words)) {
    echo 'No result';
    exit;
}

$helper = new TfIdf_Helper();

//$helper->calcTfIdf();
//$helper->calcDocVectors();
$cosSim = $helper->cosSim($words);
$helper->printFirst(20, $cosSim);

function prepareSearch(phpMorphy $morphy)
{
    $result = explode(" ", strtolower($morphy->lemmatize(strtoupper($_GET['find']))[0]));

    $list = Matrix::getWordsFromMatrix();

    foreach ($result as $key => $word) {
        if (!in_array($word, $list)) {
            unset($result[$key]);
        }
    }

    return $result;
}
