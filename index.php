<?php

class Helper
{
    public function fetch($url, $options = array())
    {
        $cache_key = $url;
        if (array_key_exists('post_params', $options)) {
            $cache_key .= 'POST' . json_encode($options['post_params']);
        }

        $cache_file = "/tmp/misq-cache-" . crc32($cache_key);
        if (array_key_exists('cache', $options) and file_exists($cache_file) and time() - filectime($cache_file) < intval($options['cache'])) {
            return file_get_contents($cache_file);
        }

        $agent = "misq.ly.govapi.tw by IP: {$_SERVER['REMOTE_ADDR']}";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
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
        if (array_key_exists('cache', $options)) {
            file_put_contents($cache_file, $content);
        }
        return $content;
    }

    public function error($message) {
        self::json(array('error' => true, 'message' => $message));
        exit;
    }

    public function url($path)
    {
        $ret = '';
        if (array_key_exists('HTTPS', $_SERVER) and $_SERVER['HTTPS']) {
            $ret = 'https://';
        } else {
            $ret = 'http://';
        }
        $ret .= $_SERVER['HTTP_HOST'];
        $ret .= $path;
        return $ret;
    }

    public function parseMeetTime($str)
    {
        // 99/10/26  09:00-18:00
        $str = trim($str);
        
        $ret = array(
            'str' => $str,
        );

        if (!preg_match('#^(\d+)/(\d+)/(\d+)[^0-9]*(\d+:\d+)-(\d+:\d+)$#', trim($str), $matches)) {
            return $ret;
        }
        $ret['日期'] = sprintf("%d/%02d/%02d", $matches[1] + 1911, $matches[2], $matches[3]);
        $ret['開始時間'] = $matches[4];
        $ret['結束時間'] = $matches[4];
        return $ret;
    }

    public function json($obj)
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        echo json_encode($obj, JSON_UNESCAPED_UNICODE);
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

// 院會列表
if ('/listmeeting' == $_SERVER['REQUEST_URI']) {
    include('listmeeting.php');
    exit;
}

// 提案列表
if (preg_match('#^/listbill/(\d+)/(\d+)/(\d+)#', $_SERVER['REQUEST_URI'], $matches)) {
    list(, $term, $session_period, $session_times) = $matches;
    include('listbill.php');
    exit;
}

// 會議查詢明細
// /MISQ/IQuery/misq5000QueryMeetingDetail.action?
if (strpos($_SERVER['REQUEST_URI'], '/MISQ/IQuery/misq5000QueryMeetingDetail.action?') === 0) {
    include('meetingdetail.php');
    exit;
}

// 議案查詢明細
// /MISQ/IQuery/misq5000QueryBillDetail.action?
if (strpos($_SERVER['REQUEST_URI'], '/MISQ/IQuery/misq5000QueryBillDetail.action?') === 0) {
    include('billdetail.php');
    exit;
}

// 關係文書
// http://lci.ly.gov.tw/LyLCEW/agenda1/02/word/09/02/14/LCEWA01_090214_00007.doc
// http://lci.ly.gov.tw/LyLCEW/agenda1/02/pdf/09/02/14/LCEWA01_090214_00007.pdf
// http://lis.ly.gov.tw/lylgqrc/mtcdoc?DN090215:LCEWA01_090215_00008
if (preg_match('#^/LyLCEW/agenda1/([0-9]*)/(word|pdf)/([0-9]*)/([0-9]*)/([0-9]*)/LCEWA01_([0-9_]*).(doc|pdf)#', $_SERVER['REQUEST_URI'], $matches)) {
    $url = sprintf("http://lci.ly.gov.tw/LyLCEW/agenda1/%s/word/%s/%s/%s/LCEWA01_%s.doc", $matches[1], $matches[3], $matches[4], $matches[5], $matches[6]);
    include('related.php');
    exit;
} elseif (strpos($_SERVER['REQUEST_URI'], '/lylgqrc/mtcdoc?') === 0) {
    $url = 'http://lis.ly.gov.tw' . $_SERVER['REQUEST_URI'];
    include('related.php');
    exit;
}

header('HTTP/1.0 404 Not Found', true, 404);
echo 404;
exit;
