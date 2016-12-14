<?php

$url = sprintf("http://lci.ly.gov.tw/LyLCEW/agenda1/%s/word/%s/%s/%s/LCEWA01_%s.doc", $matches[1], $matches[3], $matches[4], $matches[5], $matches[6]);
$cache_file = "/tmp/misq-cache-" . md5($url);
if (!file_exists($cache_file)) {
    file_put_contents($cache_file . '.doc', Helper::fetch($url));
    $content = `curl 'https://soffice.sheethub.net/' -F 'file=@{$cache_file}.doc' -F output_type=html`;
    unlink($cache_file . '.doc');
    file_put_contents($cache_file, $content);
}

$attr_black_list = array(
    'table' => array('width', 'cellpadding', 'cellspacing', 'style'),
    'td' => array('width', 'valign', 'style'),
);

function clean_table($dom)
{
    global $attr_black_list;

    if ($dom->nodeName == 'col') {
        return '';
    }

    $ret = '';
    $attrs = array();
    foreach ($dom->attributes as $attr) {
        $n = $attr->name;
        $v = $attr->value;
        if (array_key_exists($dom->nodeName, $attr_black_list) and in_array($n, $attr_black_list[$dom->nodeName])) {
            continue;
        }
        $attrs[] = $n . '="' . htmlspecialchars($v) . '"';
    }
    $ret = sprintf("<%s%s%s>", $dom->nodeName, count($attrs) ? ' ' : '', implode(' ', $attrs));
    if ($dom->nodeName == 'td') {
        $ret .= trim($dom->nodeValue);
    } else {
        foreach ($dom->childNodes as $childNode) {
            if ($childNode->nodeName == '#text') {
                $ret .= $childNode->nodeValue;
            } else {
                $ret .= clean_table($childNode);
            }
        }
    }
    $ret .= '</' . $dom->nodeName . '>';
    return $ret;
};

$obj = json_decode(file_get_contents($cache_file));
$content = base64_decode($obj->content);
foreach ($obj->attachments as $attachment) {
    $content = str_replace($attachment->file_name, "data:text/plain:;base64," . $attachment->content, $content);
}
$doc = new DOMDocument;
@$doc->loadHTML($content);
$obj = new STdclass;
// 院總第

$header_div_dom = null;
foreach ($doc->getElementsByTagName('div') as $div_dom) {
    if ($div_dom->getAttribute('type') == 'HEADER') {
        $header_div_dom = $div_dom;
        break;
    }
}

if (is_null($header_div_dom)) {
    Helper::error("找不到 div[type=HEADER]");
    exit;
}
if (!preg_match('#^立法院第[0-9]*屆第[0-9]*會期第[0-9]*次會議#', trim($header_div_dom->nodeValue), $matches)) {
    Helper::error("找不到 div[type=HEADER] 內的會期");
    exit;
}
$obj->{'會期'} = $matches[0];

$p_doms = array();
$p_dom = $header_div_dom;
while ($p_dom = $p_dom->nextSibling) {
    if ($p_dom->nodeName == '#text' and trim($p_dom->nodeValue) == '') {
        continue;
    }
    $p_doms[] = $p_dom;
}

while ($p_dom = array_shift($p_doms)) {
    $plaintext = trim(str_replace('　', '  ', $p_dom->nodeValue)); // 全形空白轉半形
    if (strpos($p_dom->nodeValue, '立法院議案關係文書') === 0) {
        continue;
    } elseif ($p_dom->nodeName == 'table' and strpos(trim($p_dom->nodeValue), '院總第') === 0) {
        list($yuan, $tee) = array_map('trim', explode('號', trim($p_dom->nodeValue), 2));
        $obj->{'院號'} = $yuan . '號';
        $obj->{'提案號'} = $tee;
    } elseif (strpos($plaintext, '收文編號：') === 0) {
        $obj->{'收文編號'} = str_replace('收文編號：', '', $plaintext);
    } elseif (strpos($plaintext, '議案編號：') === 0) {
        $obj->{'議案編號'} = str_replace('議案編號：', '', $plaintext);
    } elseif (strpos(trim($p_dom->nodeValue), '案由：') === 0) {
        $obj->{'案由'} = str_replace('案由：', '', trim($p_dom->nodeValue));
    } elseif (preg_match('#^附錄([0-9]*)：?(.*)：#u', trim($p_dom->nodeValue), $matches)) {
        if (!property_exists($obj, '附錄')) {
            $obj->{'附錄'} = array();
        }
        $r = new StdClass;
        $r->no = $matches[1];
        $r->title = $matches[2];
        if ($p_dom->nodeName == 'p') { // 如果是 <p> 的就要一直往下抓 <p>
            $content = '';
            while ($p_doms[0]->nodeName == 'p') {
                $content .= trim($p_doms[0]->nodeValue) . "\n";
                array_shift($p_doms);
            }
            $r->content = $content;
        } elseif ($p_dom->nodeName == 'table') { // 如果是 <table> 直接回傳 html
            $r->content = clean_table($p_dom);
            if (strpos(trim($p_doms[0]->nodeValue), '資料來源：') === 0) {
                $p_dom = array_shift($p_doms);
                $r->source = $p_dom->nodeValue;
            }
        }

        $obj->{'附錄'}[] = $r;
    } elseif (strpos(trim($p_dom->nodeValue), '附表') === 0) {
        if (!property_exists($obj, '附表')) {
            $obj->{'附表'} = array();
        }
        $r = new StdClass;
        $r->no = trim($p_dom->nodeValue);
        if ($p_doms[0]->nodeName != 'table') {
            Helper::error('預期附表後會是 table');
        }
        $p_dom = array_shift($p_doms);
        $r->content = clean_table($p_dom);
        if (strpos(trim($p_doms[0]->nodeValue), '資料來源：') === 0) {
            $p_dom = array_shift($p_doms);
            $r->source = $p_dom->nodeValue;
        }
        $obj->{'附表'}[] = $r;

    } elseif (strpos(trim($p_dom->nodeValue), '說明：') === 0) {
        $obj->{'說明'} = array();
        while ($p_doms[0]) {
            if (strpos(trim($p_doms[0]->nodeValue), '提案人：') === 0) {
                break;
            }
            $p_dom = array_shift($p_doms);
            $obj->{'說明'}[] = trim($p_dom->nodeValue);
        }
    } elseif (strpos(trim($p_dom->nodeValue), '提案人：') === 0) {
        $obj->{'提案人'} = str_replace('　', '', str_replace('提案人：', '', trim($p_dom->nodeValue)));
    } elseif (strpos(trim($p_dom->nodeValue), '連署人：') === 0) {
        $obj->{'連署人'} = preg_split('#\s+#u', trim(str_replace('　', '  ', str_replace('連署人：', '', $p_dom->nodeValue))));
    } elseif ($p_dom->nodeName == 'div' and $tr_dom = $p_dom->getElementsByTagName('tr')->item(1) and in_array(preg_replace('#\s#', '', $tr_dom->getElementsByTagName('td')->item(0)->nodeValue), array('增訂條文', '修正條文'))) {
        $td_dom = $p_dom->getElementsByTagName('td')->item(0);
        $tr_doms = $p_dom->getElementsByTagName('tr');
        $obj->{'對照表'} = array();

        $columns = null;
        for ($i = 1; $i < $tr_doms->length; $i ++) {
            $tr_dom = $tr_doms->item($i);
            $td_doms = $tr_dom->getElementsByTagName('td');
            if ($td_doms->length == 0) {
                continue;
            }
            if (is_null($columns)) {
                $columns = array();
                foreach ($td_doms as $td_dom) {
                    $columns[] = preg_replace('#\s#', '', $td_dom->nodeValue);
                }
                $columns = implode(',', $columns);
                continue;
            }
            $values = array();
            foreach ($td_doms as $td_dom) {
                $values[] = trim($td_dom->nodeValue);
            }
            if ($columns == '增訂條文,說明') {
                $obj->{'對照表'}[] = array(
                    '修正條文' => $values[0],
                    '現行條文' => '',
                    '說明' => $values[1],
                );
            } elseif ($columns == '修正條文,現行條文,說明') {
                $obj->{'對照表'}[] = array(
                    '修正條文' => $values[0],
                    '現行條文' => $values[1],
                    '說明' => $values[2],
                );
            } else {
                Helper::error("未知表頭: " . $columns);
            }
        }
        break;
        // skip
    } elseif (trim($p_dom->nodeValue) == '') {
    } else {
        continue;
        var_dump($obj);
        echo "<br>";
        $c = htmlspecialchars($doc->saveHTML($p_dom));
        var_dump($c);
        echo "<br>";
        echo $doc->saveHTML($doc);
        exit;
    }
}
header('Content-Type: application/json');
echo json_encode($obj);
