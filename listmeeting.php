<?php

$url = 'http://misq.ly.gov.tw/MISQ/IQuery/misq5000QueryMeeting.action?queryCondition=0703';
$content = Helper::fetch($url, array('cache' => 86400));
$doc = new DOMDocument;
@$doc->loadHTML($content);

$ret = array();
$ret['來源'] = $url;
$ret['會議'] = array();
foreach ($doc->getElementsByTagName('a') as $a_dom) {
    if (strpos($a_dom->getAttribute('onclick'), 'queryDetail(') !== 0) {
        continue;
    }
    $title = $a_dom->getAttribute('title');
    preg_match('#第(\d+)屆第(\d+)會期第(\d+)次會議(.*)#', $title, $matches);
    $meet_id = implode(',', array($matches[1], $matches[2], $matches[3]));
    if (!array_key_exists($meet_id, $ret['會議'])) {
        preg_match("#queryDetail\('([^']*)','([^']*)','([^']*)'\)#", $a_dom->getAttribute('onclick'), $detail_matches);
        $ret['會議'][$meet_id] = array();
        $ret['會議'][$meet_id]['屆次'] = intval($matches[1]);
        $ret['會議'][$meet_id]['會期'] = intval($matches[2]);
        $ret['會議'][$meet_id]['會次'] = intval($matches[3]);
        $ret['會議'][$meet_id]['事由'] = $matches[4];
        $ret['會議'][$meet_id]['時間'] = array();
        $ret['會議'][$meet_id]['api'] = Helper::url(
            sprintf("/MISQ/IQuery/misq5000QueryMeetingDetail.action?meetingNo=%s&meetingTime=%s&departmentCode=%s", urlencode($detail_matches[1]), urlencode($detail_matches[2]), urlencode($detail_matches[3]))
        );
    }
    $ret['會議'][$meet_id]['時間'][] = trim($a_dom->parentNode->parentNode->getElementsByTagName('td')->item(0)->nodeValue);
}
$ret['會議'] = array_values($ret['會議']);

Helper::json($ret);
