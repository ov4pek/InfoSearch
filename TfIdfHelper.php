<?php

include 'MatrixHelper.php';

class TfIdf_Helper
{

    public $list;
    public $tfs;
    public $idfs;
    public $count;

    public function __construct($toCalc = false, $collectionVolume = 100)
    {
        $this->list = MatrixHelper::getWordsFromMatrix();
        if ($toCalc) {
            $this->calcTfIdf();
        }
        $this->count = $collectionVolume;
        $this->tfs = $this->getWordsTf();
        $this->idfs = $this->getWordsIdf();
    }

    public function calcTfIdf()
    {
        $data = [];
        foreach ($this->list as $word) {
            $idf = log(100.0 / count(explode("\t", file_get_contents('data/matrix/words/' . $word . '.txt'))), 2);
            $tmp = [$word, $idf];
            for ($i = 1; $i <= $this->count; $i++) {
                $file = explode("\t", file_get_contents('data/lemmatized/' . $i . '.txt'));
                $tmp[] = (float)array_count_values($file)[$word] / count($file);
            }
            $data[] = implode("\t", $tmp);
        }

        file_put_contents('data/tfidf.txt', implode("\n", $data));
    }

    public function getWordsIdf()
    {
        $result = [];
        $file = explode("\n", file_get_contents('data/tfidf.txt'));
        foreach ($file as $item) {
            $info = explode("\t", $item);
            $result[$info[0]] = $info[1];
        }

        return $result;
    }

    public function getWordsTf()
    {
        $result = [];
        $file = explode("\n", file_get_contents('data/tfidf.txt'));
        foreach ($file as $item) {
            $info = explode("\t", $item);
            $word = $info[0];
            for ($i = 1; $i <= $this->count; $i++) {
                $result[$word][$i] = $info[$i + 1];
            }
        }

        return $result;
    }

    public function getWordsTfIdf()
    {
        $result = [];

        foreach ($this->list as $word) {
            foreach ((array)$this->tfs[$word] as $key => $tf) {
                $result[$word][$key] = (float)$tf * $this->idfs[$word];
            }
        }

        return $result;
    }

    public function getQueryVector($search)
    {
        $vector = [];
        $counts = array_count_values($search);
        foreach ($search as $word) {
            $vector[$word] = ((float)$counts[$word] / max($counts)) * $this->idfs[$word];
        }

        return $vector;
    }

    public function getDocVector($index)
    {
        $vector = [];

        foreach ($this->getWordsTfIdf() as $word => $docs) {
            if ($docs[$index]) {
                $vector[$word] = $docs[$index];
            }
        }

        return $vector;
    }

    public function cosSim($search)
    {
        $sim = [];
        $docVectors = unserialize(file_get_contents('data/doc_vectors.txt'));
        $queryVector = $this->getQueryVector($search);
        $queryLength = 0;
        foreach ($queryVector as $item) {
            $queryLength += pow($item, 2);
        }
        $words = array_keys($queryVector);
        for ($i = 1; $i <= $this->count; $i++) {
            $docVector = $docVectors[$i];
            $docLength = 0;
            foreach ($docVector as $word => $value) {
                $docLength += pow($value, 2);
            }
            $ch = 0;
            foreach ($words as $w) {
                if ($docVector[$w]) {
                    $ch += $docVector[$w] * $queryVector[$w];
                }
            }

            if (($ch != 0) && ((sqrt($docLength) * sqrt($queryLength)) != 0)) {
                $sim[$i] = $ch / (sqrt($docLength) * sqrt($queryLength));
            }
        }

        arsort($sim);

        return $sim;
    }

    public function calcDocVectors()
    {
        $vectors = [];

        for ($i = 1; $i <= $this->count; $i++) {
            $vector = [];

            foreach ($this->getWordsTfIdf() as $word => $docs) {
                if ($docs[$i]) {
                    $vector[$word] = $docs[$i];
                }
            }

            $vectors[] = $vector;
        }

        file_put_contents('data/doc_vectors.txt', serialize($vectors));
    }

    public function printFirst($count, $list)
    {
        $links = explode("\n", file_get_contents('data/index.txt'));
        $result = array_slice($list, 0, $count, true);

        foreach ($result as $doc => $value) {
            $result[$doc] = $links[$doc - 1];
        }

        $n = 1;
        foreach ($result as $doc => $link) {
            print_r($n . ". Document - " . $doc . " - " . $link . "\n");
            $n++;
        }
    }
}