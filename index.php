<?php

class Helper
{
    public function fetch($url, $options = array())
    {
        $agent = "misq.ly.govapi.tw by IP: {$_SERVER['REMOTE_ADDR']}";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (array_key_exists('post_params', $options)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', array_map(function($k) use ($options) {
                return urlencode($k) . '=' . urlencode($options['post_params'][$k]);
            }, array_keys($options['post_params']))));
        }
        curl_setopt($curl, CURLOPT_USERAGENT, $agent);
        $content = curl_exec($curl);
        $info = curl_getinfo($curl);
        if ($info['http_code'] != 200) {
            Helper::error("找不到這資料, $url (code={$info['http_code']})");
        }
        curl_close($curl);
        return $content;
    }

    public function error($message) {
        echo json_encode(array('error' => true, 'message' => $message));
        exit;
    }

    public function url($path)
    {
        $ret = '';
        if ($_SERVER['HTTPS']) {
            $ret = 'https://';
        } else {
            $ret = 'http://';
        }
        $ret .= $_SERVER['HTTP_HOST'];
        $ret .= $path;
        return $ret;
    }
}


// /_static/...
if (strpos($_SERVER['REQUEST_URI'], '/_static/')) {
    if (strpos($_SERVER['REQUEST_URI'], '/..')) {
        header('HTTP/1.0 404 Not Found', true, 404);
        echo 'bad url';
        exit;
    }
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (!file_exists($file) or !is_file($file)) {
        header('HTTP/1.0 404 Not Found', true, 404);
        echo '404';
        exit;
    }
    readfile($file);
    exit;
}

// mainpage
if ($_SERVER['REQUEST_URI'] == '/') {
    include('mainpage.php');
    exit;
}

// 關係文書
// http://lci.ly.gov.tw/LyLCEW/agenda1/02/word/09/02/14/LCEWA01_090214_00007.doc
// http://lci.ly.gov.tw/LyLCEW/agenda1/02/pdf/09/02/14/LCEWA01_090214_00007.pdf
if (preg_match('#^/LyLCEW/agenda1/([0-9]*)/(word|pdf)/([0-9]*)/([0-9]*)/([0-9]*)/LCEWA01_([0-9_]*).(doc|pdf)#', $_SERVER['REQUEST_URI'], $matches)) {
    include('related.php');
    exit;
}

header('HTTP/1.0 404 Not Found', true, 404);
echo 404;
exit;
