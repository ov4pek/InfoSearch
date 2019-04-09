<?php

class Matrix
{
    public $map;

    public function __construct(SiteMap $map)
    {
        $this->map = $map;
    }

    public static function getWordsFromMatrix()
    {
        $list = scandir('data/matrix/words');

        foreach ($list as $key => $word) {
            $p = strpos($word, '.txt');
            if ($p === false) {
                unset($list[$key]);
            } else {
                $list[$key] = substr($list[$key], 0, $p);
            }
        }

        return $list;
    }

    private function getUniqueWords()
    {
        $words = [];

        for ($i = 0; $i < $this->map->limit; $i++) {
            $words = array_merge($words, $this->map->getLemmatizedFile('data/lemmatized/'.($i+1).'.txt'));
        }

        $result = [];
        $stop_words = explode("\n", file_get_contents('data/stop_words.txt'));
        foreach ($words as $word) {
            $word = strtolower($word);
            if (!in_array($word, array_merge($result, $stop_words)) && preg_match('/^[A-Za-z0-9-]+$/', $word)) {
                $result[] = $word;
            }
        }

        return $result;
    }

    public function buildMatrix()
    {
        $unique = $this->getUniqueWords();

        foreach ($unique as $word) {
            $result = [];
            for ($i = 1; $i <= $this->map->limit; $i++) {
                $file = $this->map->getLemmatizedFile('data/lemmatized/'.$i.'.txt');
                if (in_array($word, $file)) {
                    $result[] = $i;
                }
            }
            file_put_contents('data/matrix/words/'.$word.'.txt', implode("\t", $result));
        }
    }
}
