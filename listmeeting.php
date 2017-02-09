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
    if (preg_match('#第(\d+)屆第(\d+)會期第(\d+)次會議(.*)#', $title, $matches)) {
        $meet_id = implode(',', array('院會', $matches[1], $matches[2], $matches[3]));
        $val = array(
            '類別' => '院會',
            '屆次' => intval($matches[1]),
            '會期' => intval($matches[2]),
            '會次' => intval($matches[3]),
            '事由' => trim($matches[4]),
        );
    } elseif (preg_match('#第(\d+)屆第(\d+)會期第(\d+)次全院委員會?會議(.*)#u', $title, $matches)) {
        $meet_id = implode(',', array('全院委員會議', $matches[1], $matches[2], $matches[3]));
        $val = array(
            '類別' => '全院委員會議',
            '屆次' => intval($matches[1]),
            '會期' => intval($matches[2]),
            '會次' => intval($matches[3]),
            '事由' => trim($matches[4]),
        );
    } elseif (preg_match('#第(\d+)屆第(\d+)會期第(\d+)次臨時會第(\d+)次會議(.*)#', $title, $matches)) {
        $meet_id = implode(',', array('臨時會', $matches[1], $matches[2], $matches[3]));
        $val = array(
            '類別' => '臨時會',
            '屆次' => intval($matches[1]),
            '會期' => intval($matches[2]),
            '臨時會次' => intval($matches[3]),
            '會次' => intval($matches[4]),
            '事由' => trim($matches[5]),
        );
    } elseif (preg_match('#第(\d+)屆第(\d+)會期第(\d+)次臨時會第(\d+)次全院委員會會議(.*)#', $title, $matches)) {
        $meet_id = implode(',', array('臨時會全院委員會會議', $matches[1], $matches[2], $matches[3]));
        $val = array(
            '類別' => '臨時會全院委員會會議',
            '屆次' => intval($matches[1]),
            '會期' => intval($matches[2]),
            '臨時會次' => intval($matches[3]),
            '會次' => intval($matches[4]),
            '事由' => trim($matches[5]),
        );
    } else {
        error_log("unknown title: $title");
        continue;
    }

    if (!array_key_exists($meet_id, $ret['會議'])) {
        preg_match("#queryDetail\('([^']*)','([^']*)','([^']*)'\)#", $a_dom->getAttribute('onclick'), $detail_matches);
        $ret['會議'][$meet_id] = $val;
        $ret['會議'][$meet_id]['時間'] = array();
        $ret['會議'][$meet_id]['api'] = Helper::url(
            sprintf("/MISQ/IQuery/misq5000QueryMeetingDetail.action?meetingNo=%s&meetingTime=%s&departmentCode=%s", urlencode($detail_matches[1]), urlencode($detail_matches[2]), urlencode($detail_matches[3]))
        );
    }
    $ret['會議'][$meet_id]['時間'][] = Helper::parseMeetTime($a_dom->parentNode->parentNode->getElementsByTagName('td')->item(0)->nodeValue);
}
$ret['會議'] = array_values($ret['會議']);

Helper::json($ret);
