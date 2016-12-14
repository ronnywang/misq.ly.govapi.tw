<?php

$url = sprintf("http://lci.ly.gov.tw/LyLCEW/agenda1/%s/word/%s/%s/%s/LCEWA01_%s.doc", $matches[1], $matches[3], $matches[4], $matches[5], $matches[6]);
$cache_file = "/tmp/misq-cache-" . md5($url);
if (!file_exists($cache_file)) {
    file_put_contents($cache_file . '.doc', Helper::fetch($url));
    $content = `curl 'https://soffice.sheethub.net/' -F 'file=@{$cache_file}.doc' -F output_type=html`;
    unlink($cache_file . '.doc');
    file_put_contents($cache_file, $content);
}

$obj = json_decode(file_get_contents($cache_file));
$content = base64_decode($obj->content);
foreach ($obj->attachments as $attachment) {
    $content = str_replace($attachment->file_name, "data:text/plain:;base64," . $attachment->content, $content);
}
$doc = new DOMDocument;
$doc->loadHTML($content);
$obj = new STdclass;
// 院總第

$p_doms = $doc->getElementsByTagName('p');
for ($i = 0; $i < $p_doms->length; $i ++) {
    $p_dom = $p_doms->item($i);
    if (preg_match('#^立法院第[0-9]*屆第[0-9]*會期第[0-9]*次會議#', $p_dom->nodeValue, $matches)) {
        $obj->{'會期'} = $matches[0];
    } elseif (trim($p_dom->nodeValue) == '') {
        continue;
    } elseif (strpos($p_dom->nodeValue, '立法院議案關係文書') === 0) {
        continue;
    } elseif (strpos($p_dom->nodeValue, '院總第') === 0) {
        $obj->{'院號'} = trim($p_dom->nodeValue);
        $node = $p_dom;
        while ($node->parentNode and $node->nodeName != 'tr') {
            $node = $node->parentNode;
        }
        $i += $node->getElementsByTagName('p')->length;
        $obj->{'提案號'} = trim(str_replace($obj->{'院號'}, '', $node->nodeValue));
    } elseif (strpos(trim($p_dom->nodeValue), '案由：') === 0) {
        $obj->{'案由'} = str_replace('案由：', '', trim($p_dom->nodeValue));
    } elseif (strpos(trim($p_dom->nodeValue), '說明：') === 0) {
        $obj->{'說明'} = array();
        while (($p = strpos($p_doms->item($i + 1)->nodeValue, '、')) < 10 and $p !== false) {
            $obj->{'說明'}[] = trim($p_doms->item($i + 1)->nodeValue);
            $i ++;
        }
    } elseif (strpos(trim($p_dom->nodeValue), '提案人：') === 0) {
        $obj->{'提案人'} = str_replace('　', '', str_replace('提案人：', '', trim($p_dom->nodeValue)));
    } elseif (strpos(trim($p_dom->nodeValue), '連署人：') === 0) {
        $obj->{'連署人'} = preg_split('#\s+#u', trim(str_replace('　', '  ', str_replace('連署人：', '', $p_dom->nodeValue))));
    } elseif (preg_match('#草案對照表$#', $p_dom->nodeValue)) {
        $tbody_dom = $p_dom->parentNode;
        while ($tbody_dom and $tbody_dom->nodeName != 'tbody') {
            $tbody_dom = $tbody_dom->parentNode;
        }
        $obj->{'對照表'} = array();
        while ($tbody_dom = $tbody_dom->nextSibling) {
            $td_doms = $tbody_dom->getElementsByTagName('td');
            if ($td_doms->length != 3) {
                break;
            }
            if (trim($td_doms->item(0)->nodeValue) == '修正條文') {
                continue;
            }
            $obj->{'對照表'}[] = array(
                '修正條文' => trim($td_doms->item(0)->nodeValue),
                '現行條文' => trim($td_doms->item(1)->nodeValue),
                '說明' => trim($td_doms->item(2)->nodeValue),
            );
        }
        break;
        // skip
    } else {
        var_dump($obj);
        echo "<br>";
        var_dump($p_dom->nodeValue);
        echo $doc->saveHTML($doc);
        exit;
    }
}
header('Content-Type: application/json');
echo json_encode($obj);
