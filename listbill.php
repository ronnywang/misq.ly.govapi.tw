<?php

$content = Helper::fetch('http://misq.ly.gov.tw/MISQ/IQuery/queryMore5003vData.action',
    array(
        'post_params' => array(
            'term' => sprintf("%02d", $term),
            'sessionPeriod' => sprintf("%02d", $session_period),
            'sessionTimes' => sprintf("%02d", $session_times),
            'meetingTimes' => '',
            'specialTimesRadio' => 'on',
            'agandaNo' => '',
            'billName' => '',
            'catalogType' => 1,
            'as_fid' => '',
        ),
    )
);
$doc = new DOMDocument;
@$doc->loadHTML($content);
$ret = array();
foreach ($doc->getElementsByTagName('div') as $div_dom) {
    if (strpos($div_dom->getAttribute('id'), 'div_queryList_') !== 0) {
        continue;
    }
    foreach ($div_dom->getElementsByTagName('tr') as $tr_dom) {
        $td_doms = $tr_dom->getElementsByTagName('td');
        if ($td_doms->length != 2) {
            continue;
        }
        $a_dom = $td_doms->item(0)->getElementsByTagName('a')->item(0);
        if (!preg_match("#queryDetail\('([^']*)'\)#", $a_dom->getAttribute('onclick'), $matches)) {
            Helper::error("找不到 queryDetail");
            exit;
        } 
        $billno = $matches[1];
        $ret[] = array(
            'billno' => $billno,
            'desc' => trim($a_dom->nodeValue),
            'api' => Helper::url("/MISQ/IQuery/misq5000QueryBillDetail.action?billNo=" . urlencode($billno)),
        );
    }
}
header('Content-Type: application/json');
echo json_encode($ret);
