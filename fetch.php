<?php
$season = '503728397';
$division = '862501863';
$base = 'https://faapi.jwhsolutions.co.uk/api';

function fetchJson($url, $tries = 3) {
    for ($i = 1; $i <= $tries; $i++) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\nUser-Agent: Mozilla/5.0\r\n",
                'timeout' => 90,
                'ignore_errors' => true,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
        $status = $http_response_header[0] ?? 'no response';
        echo "Try $i: $url -> $status\n";
        if ($raw !== false && strpos($status, '200') !== false) {
            $json = json_decode($raw, true);
            if (is_array($json)) {
                return $json;
            }
        }
        sleep(5);
    }
    return null;
}

$old = [];
if (file_exists('data.json')) {
    $old = json_decode(file_get_contents('data.json'), true) ?: [];
}

$fixtures = fetchJson("$base/Fixtures/$division/season/$season");
$results  = fetchJson("$base/Results/$division/season/$season");

$data = [
    'teams'    => [],
    'fixtures' => $fixtures ?? ($old['fixtures'] ?? []),
    'results'  => $results  ?? ($old['results']  ?? []),
    'updated'  => gmdate('Y-m-d H:i:s') . ' UTC',
];

file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Updated data.json\n";
