<?php

$url = "http://misq.ly.gov.tw" . $_SERVER['REQUEST_URI'];
$obj = new StdClass;
$obj->{'來源'} = $url;
$content = Helper::fetch($url, array('cache' => 86400));
$doc = new DOMDocument;
@$doc->loadHTML($content);
foreach ($doc->getElementById('queryForm')->getElementsByTagName('tr') as $tr_dom) {
    $th_doms = $tr_dom->getElementsByTagName('th');
    if ($th_doms->length != 1) {
        continue;
    }
    $td_dom = $tr_dom->getElementsByTagName('td')->item(0);
    $key = explode('：', $th_doms->item(0)->nodeValue)[0];
    if ($key == '會議時間') {
        $obj->{$key} = preg_split("#\s+#", trim($td_dom->nodeValue));
    } elseif ($key == '關係文書') {
        $obj->{$key} = new StdClass;
        foreach ($td_dom->getElementsByTagName('a') as $a_dom) {
            if (!preg_match("#controlcatalogTypeDiv\('([^']*)'\)#", $a_dom->getAttribute('onclick'), $matches)) {
                continue;
            }
            $category_id = $matches[1];
            $category = $a_dom->nodeValue;
            $obj->{$key}->{$category} = array();
            foreach ($doc->getElementById($category_id)->getElementsByTagName('tr') as $tr_dom) {
                $case = new StdClass;
                foreach ($tr_dom->getElementsByTagName('a') as $a_dom) {
                    if (preg_match("#queryDetail\('([^']*)'\)#", $a_dom->getAttribute('onclick'), $matches)) {
                        $case->bill_no = $matches[1];
                        $case->{'描述'} = trim($a_dom->nodeValue);
                        $case->{'議案API'} = Helper::url("/MISQ/IQuery/misq5000QueryBillDetail.action?billNo=" . urlencode($case->bill_no));
                    } elseif (strpos($a_dom->getAttribute('href'), 'http://lci.ly.gov.tw/') === 0 and trim($a_dom->nodeValue) === '關係文書') {
                        $case->{'關係文書API'} = Helper::url(str_replace("http://lci.ly.gov.tw", "", $a_dom->getAttribute('href')));
                    }
                }
                $obj->{$key}->{$category}[] = $case;
            }
        }
    } else {
        $obj->{$key} = trim($td_dom->nodeValue);
    }
}

header('Content-Type: application/json');
echo json_encode($obj);
