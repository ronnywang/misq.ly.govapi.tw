<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>misq.ly.gov.tw API</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.6/css/bootstrap.css">
</head>
<body>
<div class="container">
    <h1>misq.ly.gov.tw API</h1>
    <p>只需要將 misq.ly.gov.tw 改成 misq.ly.gov<strong>api</strong>.tw，就可以把網頁變成 API ，更好使用</p>
    <h3>注意事項</h3>
    <ol>
        <li>本服務程式碼位於 <a href="https://github.com/ronnywang/misq.ly.govapi.tw">https://github.com/ronnywang/misq.ly.govapi.tw</a> 程式碼以 BSD License 開放授權</li>
        <li>由於是每次都直接存取原始網頁，若原始網頁故障本服務也會一起故障</li>
        <li>在存取原始網頁時，會在 user agent 中帶入存取者的 IP，以便讓原始網頁管理者可以知道確切存取人是誰，若無法接受自己的 IP 被 misq.ly.gov.tw 得知，請勿使用本服務</li>
        <li>若原始網頁改版，本服務未一起改版也會出現不正確的結果，若發現有異常請至 github 送出 issues ，或者也可協助送出 pull request</li>
        <li>本 API 會 cache 原網站資料一天</li>
        <li>本 API 開放 CORS ，歡迎直接在前端直接利用</li>
        <li>若您覺得這服務很有幫助，可以至 <a href="http://ronny.tw">ronny.tw</a> 贊助 ronny</li>
</div>
</html>
