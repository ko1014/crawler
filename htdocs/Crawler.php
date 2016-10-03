<?php


class Crawler {

    private $url = "http://www.skincare-univ.com";
    private $file = "result_log.txt";
    private $error_file = "error_status.txt";
    private $url_file = "execute_urls.txt";
    private $pattern = '/<a\shref=\"(http:\/\/www\.skincare-univ\.com.*?)\"|<a\shref=\"(http:\/\/sp\.skincare-univ\.com.*?)\"|<a\shref=\"(http:\/\/mens-skincare-univ\.com.*?)\"|<a\shref=\"(http:\/\/sp\.mens-skincare-univ\.com.*?)\"|<a\shref=\"(\/.*?)\"/';

    /**
     * データset関数
     * @param string $file ファイル名
     * @param string $error_file 404URL出力エラーファイル名
     * @param string $pattern htmlから取得するパターン
     * @param string $url クロールする対象URL
     *
     */
    public function set($file = null, $error_file = null, $pattern = null, $url = null)
    {
        $this->file = !is_null($file) ? $file : $this->file;
        $this->error_file = !is_null($error_file) ? $error_file : $this->error_file;
        $this->pattern = !is_null($pattern) ? $pattern : $this->pattern;
        $this->url = !is_null($url) ? $url : $this->url;
    }

    /**
     * クローラー
     * @param void
     * @return bool クロール成功か
     *
     */
    public function crawler()
    {
        file_put_contents($this->file, "クローラー開始\n", FILE_APPEND);
        $check_done_urls = array();
        // $urlからhtmlを取得
        $contents = $this->scraping($this->url, $this->error_file);
        if ($contents) {
            // aタグからリンクを取得
            $urls = $this->get_effect_urls($contents, $this->pattern);
        } else {
            file_put_contents($this->file, "contnetsがないのでscrapingする前に終了します\n", FILE_APPEND);
        }
        // effetc_url_arrayが空になるまでひたすら繰り返す
        do {
            $effect_url_array = array();
            foreach ($urls as $url) {
                if ($contens = $this->scraping($url, $this->error_file)) {
                    $effect_urls = $this->get_effect_urls($contents, $this->pattern);
                    if (!empty($effect_urls)) {
                        $effect_url_array = array_merge_recursive($effect_urls, $effect_urls);
                    }
                }
            }
            $urls = $effect_url_array;
        } while (count($urls) > 0);
        file_put_contents($this->file, "クローラー終了", FILE_APPEND);
    }

    /**
     * 有効なaリンクURLを取得する関数
     * @param string $contents 対象のページのhtml
     * @param string $pattern preg_matchパターン
     * @return array 有効のurl群
     *
     */
    private function get_effect_urls($contents, $pattern)
    {
        $urls = array();
        // すでに回遊した(回遊する)url群
        $already_urls = array();
        if (file_exists($this->url_file)) {
            $already_urls = json_decode(file_get_contents($this->url_file));
        }
        if (preg_match_all($pattern, $contents, $matches)) {
            $counts = count($matches);
            for ($i = 1; $i < $counts; $i++) {
                foreach ($matches[$i] as $url) {
                    if (!empty($url) ) {
                        if (preg_match("/^\/.*?/",$url)) {
                            $full_url = $this->url. $url;
                            if ($this->check_url($full_url, $already_urls)) {
                                array_push($urls, $full_url);
                            }
                        } else {
                            if ($this->check_url($url, $already_urls)) {
                                array_push($urls, $url);
                            }
                        }
                    }
                }
            }
        }
        if (!empty($urls)) {
            $already_urls = array_merge_recursive($already_urls, $urls);
            // すでに回遊した(回遊する)urlをファイルに書き出す
            file_put_contents($this->url_file, json_encode($already_urls));
        }
        return $urls;
    }

    /**
     * urlが回遊したかをチェックする関数
     * @param string $check_url チェックするurl
     * @param array $list_urls すでに回遊しているリストurl
     * @return bool
     *
     */
    private function check_url($check_url, $list_urls)
    {
        if (is_array($list_urls) && !in_array($check_url, $list_urls)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * scarping関数
     * @param string $url URL
     * @param string $error_file エラー出力ファイル
     * @reutrn mixed html/false
     *
     */
    private function scraping($url, $error_file)
    {
        // html取得
        $html = file_get_contents($url);
        $status_check = strpos($http_response_header[0], '200');
        // ステータスが200でなかったらエラーファイルにURLを追記していく
        if ($status_check === false) {
            file_put_contents($error_file, "[". $url. "=>". $status_check. "]", FILE_APPEND);
            return false;
        }
        return $html;
    }

}
