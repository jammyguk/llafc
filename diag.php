<?php
$u = 'https://faapi.jwhsolutions.co.uk/api/Fixtures/862501863/season/503728397';
echo "== curl (default)\n";
$ch = curl_init($u); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>100,CURLOPT_CONNECTTIMEOUT=>30,CURLOPT_HTTPHEADER=>['Accept: application/json','User-Agent: Mozilla/5.0']]);
$t=microtime(true); $r=curl_exec($ch);
echo "code=".curl_getinfo($ch,CURLINFO_HTTP_CODE)." err=[".curl_error($ch)."] errno=".curl_errno($ch)." bytes=".strlen((string)$r)." t=".round(microtime(true)-$t,1)."s ip=".curl_getinfo($ch,CURLINFO_PRIMARY_IP)." ver=".curl_getinfo($ch,CURLINFO_HTTP_VERSION)."\n";
echo "== curl HTTP/1.1\n";
$ch = curl_init($u); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>100,CURLOPT_CONNECTTIMEOUT=>30,CURLOPT_HTTP_VERSION=>CURL_HTTP_VERSION_1_1,CURLOPT_HTTPHEADER=>['Accept: application/json','User-Agent: Mozilla/5.0']]);
$t=microtime(true); $r=curl_exec($ch);
echo "code=".curl_getinfo($ch,CURLINFO_HTTP_CODE)." err=[".curl_error($ch)."] bytes=".strlen((string)$r)." t=".round(microtime(true)-$t,1)."s\n";
echo "== file_get_contents (the old method)\n";
$ctx = stream_context_create(['http'=>['method'=>'GET','header'=>"Accept: application/json\r\nUser-Agent: Mozilla/5.0\r\n",'timeout'=>100,'ignore_errors'=>true]]);
$t=microtime(true); $raw=@file_get_contents($u,false,$ctx);
echo "status=".($http_response_header[0]??'none')." bytes=".strlen((string)$raw)." t=".round(microtime(true)-$t,1)."s\n";
echo "== second page\n";
$t=microtime(true); $raw=@file_get_contents('https://faapi.jwhsolutions.co.uk/api/Results/862501863/season/503728397',false,$ctx);
echo "status=".($http_response_header[0]??'none')." bytes=".strlen((string)$raw)." t=".round(microtime(true)-$t,1)."s\n";
