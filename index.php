<?php

class Helper
{
    public function fetch($url)
    {
        $agent = "misq.ly.govapi.tw by IP: {$_SERVER['REMOTE_ADDR']}";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERAGENT, $agent);
        $content = curl_exec($curl);
        $info = curl_getinfo($curl);
        if ($info['http_code'] != 200) {
            error("找不到這資料, $url (code={$info['http_code']})");
        }
        curl_close($curl);
        return $content;
    }

    public function error($message) {
        echo json_encode(array('error' => true, 'message' => $message));
        exit;
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

header('HTTP/1.0 404 Not Found', true, 404);
echo 404;
exit;
