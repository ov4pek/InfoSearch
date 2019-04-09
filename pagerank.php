<?php

include 'SiteMap.php';

//calcMatrix();
$links = explode("\n", file_get_contents('data/index.txt'));
$doc_num = count($links);
$iter_num = 300;
$d = 0.85;
$matrix = prepareMatrix(getMatrix());

$vector = [];
for ($i = 0; $i < $doc_num; $i++) {
    $vector[] = 1/$doc_num;
}

for ($i = 0; $i < $iter_num; $i++) {
    for ($j = 0; $j < count($vector); $j++) {
        $sum = (1-$d)/$doc_num;
        for ($n = 0; $n < $doc_num; $n++) {
            $sum += $vector[$j] * $matrix[$n][$j];
        }
        $vector[$j] = $sum;
    }
}

arsort($vector);
$n = 1;
foreach ($vector as $doc => $rank) {
    print_r($n . ". Document №".($doc+1)." - PageRank = " . $rank . " - " . $links[$doc] . "\n");
    $n++;
}

function calcMatrix()
{
    $pages = explode("\n", file_get_contents('data/index.txt'));
    $map = new SiteMap();

    $table = [];
    foreach ($pages as $page) {
        $links = $map->findPages($page);
        $doc = "";
        foreach ($pages as $link) {
            $doc .= in_array($link, $links) ? '1' : '0';
            $doc .= "\t";
        }
        $doc = substr($doc, 0, -1);
        $table[] = $doc;
    }

    file_put_contents('data/pagerank.txt', implode("\n", $table));
}

function getMatrix()
{
    $result = [];
    $lines = explode("\n", file_get_contents('data/pagerank.txt'));

    foreach ($lines as $line) {
        $result[] = explode("\t", $line);
    }

    return $result;
}

function prepareMatrix($matrix)
{
    for ($i = 0; $i < 100; $i++) {
        $k = array_count_values($matrix[$i])[1];

        for ($j = 0; $j < 100; $j++) {
            if ($matrix[$i][$j] == 1) {
                $matrix[$i][$j] = (double)1/$k;
            }
        }
    }

    return $matrix;
}
