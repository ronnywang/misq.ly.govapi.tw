<?php

$url = "http://misq.ly.gov.tw" . $_SERVER['REQUEST_URI'];
$obj = new StdClass;
$obj->{'來源'} = $url;
$content = Helper::fetch($url, array('cache' => 86400));
$doc = new DOMDocument;
@$doc->loadHTML($content);
foreach ($doc->getElementById('table')->getElementsByTagName('tr') as $tr_dom) {
    $th_doms = $tr_dom->getElementsByTagName('th');
    $td_doms = $tr_dom->getElementsByTagName('td');
    if ($th_doms->length == 0 or $td_doms->length == 0 or $td_doms->item(0)->getAttribute('colspan') != 2) {
        continue;
    }
    $td_dom = $tr_dom->getElementsByTagName('td')->item(0);
    $key = explode('：', $th_doms->item(0)->nodeValue)[0];
    if ($key == '相關附件') {
        $obj->{'相關附件'} = array();
        foreach ($td_dom->getElementsByTagName('a') as $a_dom) {
            $obj->{'相關附件'}[] = array(
                '名稱' => trim(str_replace('　', '', $a_dom->nodeValue)),
                '連結' => $a_dom->getAttribute('href'),
                '檔案上傳時間' => str_replace('檔案上傳時間：', '', $a_dom->getAttribute('title')),

                'API' => Helper::url(str_replace('http://lci.ly.gov.tw', '', $a_dom->getAttribute('href'))),
            );
        }
    } elseif ($key == '關連議案') {
        $obj->{$key} = array();
        foreach ($td_dom->getElementsByTagName('a') as $a_dom) {
            if (!preg_match("#queryBillDetail\('([^']*)'#", $a_dom->getAttribute('onclick'), $matches)) {
                continue;
            }
            $billno = $matches[1];
            $obj->{$key}[] = array(
                'billno' => $billno,
                'desc' => trim($a_dom->nodeValue),
                'api' => Helper::url("/MISQ/IQuery/misq5000QueryBillDetail.action?billNo=" . urlencode($billno)),
            );
        }
    } elseif (in_array($key, array('提案人', '連署人'))) {
        if (!preg_match("#getLawMakerName\('[^']*', '([^']*)'\)#", $td_dom->nodeValue, $matches)) {
            Helper::error("找不到 {$key} 的 getLawMakerName");
            exit;
        }
        $v = $matches[1];
        $v = preg_replace('#[　 ]*$#u', '', $v);
        $names = preg_split('#　+#u', $v);
        $obj->{$key} = array_map(function($n) {
            return array(
                '姓名' => $n,
                'API' => Helper::url("/MISQ/IQuery/misq5000QueryCommitteeDetail.action?queryType=all&committeeName=" . urlencode($n)),
            );
        }, $names);
        if ($key == '提案人') {
            $obj->{$key} = $obj->{$key}[0];
        }
    } elseif ('議案流程' == $key) {
        $obj->{$key} = array();
        $columns = array();
        foreach ($td_dom->getElementsByTagName('tr') as $tr_dom) {
            $th_doms = $tr_dom->getElementsByTagName('th');
            $td_doms = $tr_dom->getElementsByTagName('td');

            if ($th_doms->length > $td_doms->length) {
                foreach ($th_doms as $th_dom) {
                    $columns[] = trim($th_dom->nodeValue);
                }
            } else {
                $values = array();
                foreach ($td_doms as $i => $td_dom) {
                    if ($columns[$i] == '狀態') {
                        preg_match('#changeName\(\'([0-9]*)\'\);\s+(.*)#s', trim($td_dom->nodeValue), $matches);
                        $values[] = array(
                            '代碼' => $matches[1],
                            '名稱' => $matches[2],
                        );
                    } else {
                        $values[] = trim($td_dom->nodeValue);
                    }
                }
                $obj->{$key}[] = array_combine($columns, $values);
            }
        }
    } else {
        $obj->{$key} = trim($td_dom->nodeValue);
    }
}

header('Content-Type: application/json');
echo json_encode($obj);
