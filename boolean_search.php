<?php

include 'MatrixHelper.php';
require_once 'phpmorphy-0.3.7/src/common.php';

$dir = 'phpmorphy-0.3.7/dicts';
$lang = 'en_EN';
$opts = ['storage' => PHPMORPHY_STORAGE_FILE];

try {
    $morphy = new phpMorphy($dir, $lang, $opts);
} catch (phpMorphy_Exception $e) {
    die('Error occured while creating phpMorphy instance: ' . $e->getMessage());
}

$words = explode(" ", strtolower($morphy->lemmatize(strtoupper($_GET['find']))[0]));
$list = MatrixHelper::getWordsFromMatrix();

$result = [];
foreach ($words as $word) {
    if (!in_array($word, $list)) {
        exit($word . " not found");
    } else {
        $result[$word] = explode("\t", file_get_contents('data/matrix/words/'.$word.'.txt'));
    }
}

if (count($result) == 0) {
    exit();
} elseif (count($result) == 1) {
    printResult($result[$words[0]]);
    exit();
}

$intersect = [];
for ($i = 0; $i < count($result)-1; $i++) {
    if (empty($intersect)) {
        if ($i == 0) {
            $intersect = array_intersect($result[$words[$i]], $result[$words[$i+1]]);
        } else {
            exit("Not found.");
        }
    } else {
        $intersect = array_intersect($intersect, $result[$words[$i+1]]);
    }
}

printResult($intersect);

function printResult($result)
{
    $links = explode("\n", file_get_contents('data/index.txt'));
    $print = [];

    foreach ($result as $key => $doc) {
        $print[$doc] = $links[$doc-1];
    }

    $n = 1;
    foreach ($print as $doc => $link) {
        print_r($n . ". Document - ".$doc." - " . $link . "\n");
        $n++;
    }
}
