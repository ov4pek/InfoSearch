<?php

class SiteMap
{
    public $host;
    public $host_paths;
    public $ignore_list;
    public $limit;

    public function __construct($host = 'https://www.brown.edu/', $count = 100)
    {
        $this->host = $host;
        $this->limit = $count;
        $this->host_paths = explode("\n", file_get_contents('data/index.txt'));
        $this->ignore_list = ["javascript:", ".css", ".js", ".ico", ".jpg", ".png", ".jpeg", ".swf", ".gif", '#', '@'];
    }

    public function findPaths()
    {
        return $this->makeList($this->findPages($this->host));
    }

    public function refreshHostPaths()
    {
        $this->host_paths = $this->findPaths();
        file_put_contents('data/index.txt', implode("\n", $this->host_paths));
    }

    public function findPages($page)
    {
        preg_match_all("/<a[^>]*href\s*=\s*'([^']*)'|" . '<a[^>]*href\s*=\s*"([^"]*)"' . "/is", file_get_contents($page), $match);

        foreach ($match[2] as $key => $url) {
            $match[2][$key] = $this->makeFullLink($url);
        }

        return $match[2];
    }

    private function makeList($list)
    {
        $result = $this->filter($list);
        if (count($result) == $this->limit) {
            return $result;
        }

        $newList = $result;
        for ($i = 0; $i < count($result); $i++) {
            if (count($newList) >= $this->limit) {
                break;
            }
            $newList = array_merge($newList, $this->filter($this->findPages($result[$i+1]), $newList));
        }

        return array_slice($newList, 0, 100);
    }

    private function filter($list, $result_list = [])
    {
        $urls = [];
        foreach ($list as $key => $url) {
            if (count($urls) >= $this->limit) {
                break;
            }
            if ($this->validate($url) && !in_array($url, array_merge($urls, $result_list)) && $this->cleanContent($url) != "") {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    public function makeFullLink($url) {
        if (preg_match('/^\\/{1}(\w+-?\w+)+/', $url)) {
            return $this->host.$url;
        }
        return $url;
    }

    private function validate($url)
    {
        $valid = true;

        if (strpos($url, substr($this->host, 12)) === false
            || strpos($url, ' ') !== false
            || substr(get_headers($url)[0], 9, 3) != 200
        ) {
            return false;
        }

        foreach ($this->ignore_list as $val) {
            if (stripos($url, $val) !== false) {
                $valid = false;
                break;
            }
        }

        return $valid;
    }

    public function updateFiles()
    {
        for ($i = 0; $i < $this->limit; $i++) {
            file_put_contents('data/pages/' . ($i + 1) . '.txt', $this->cleanContent($this->host_paths[$i]));
        }
    }

    private function cleanContent($url)
    {
        $content = preg_replace("'<script[^>]*?>.*?</script>'si", "", file_get_contents($url));
        $content = preg_replace("'<style[^>]*?>.*?</style>'si", "", $content);
        $content = str_replace("><", "> <", $content);
        $content = str_replace("\n", " ", $content);
        $content = preg_replace("/(\s){2,}/u", " ", $content);

        while (strpos($content, "  ") !== false) {
            $content = str_replace("  ", "", $content);
        }

        return strip_tags($content);
    }

    public static function getLemmatizedFile($filepath)
    {
        return explode("\t", file_get_contents($filepath));
    }

    public function getWords($filepath)
    {
        if (preg_match_all("/\b(\w+)\b/ui", file_get_contents($filepath), $matches)) {
            foreach ($matches[1] as $key => $word) {
                $matches[1][$key] = mb_strtoupper($word);
            }

            return $matches[1];
        }

        return [];
    }

    private function saveLemmatizedFile($words, $filepath)
    {
        $str = implode("\t", $words);
        if (strpos($str, "\t") === 0) {
            $str = substr($str, 1);
        }
        if (strrpos($str, "\t") === strlen($str) - 1) {
            $str = substr($str, 0, strlen($str) - 1);
        }
        while (strpos($str, "\t\t") !== false) {
            $str = str_replace("\t\t", "\t", $str);
        }

        file_put_contents($filepath, strtolower($str));
    }

    public function lemmatizeFiles(phpMorphy $morphy)
    {
        for ($i = 1; $i <= $this->limit; $i++) {
            $filename = $i . '.txt';
            $words = $this->getWords('data/pages/' . $filename);
            foreach ($words as $key => $word) {
                if ($morphy->findWord($word)) {
                    $words[$key] = mb_convert_case($morphy->lemmatize($word)[0], MB_CASE_LOWER);
                } else {
                    unset($words[$key]);
                }
            }

            $this->saveLemmatizedFile($words, 'data/lemmatized/' . $filename);
        }
    }
}
