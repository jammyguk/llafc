<?php
$season = '503728397';
$division = '862501863';

$fixturesUrl = "https://faapi.jwhsolutions.co.uk/api/Fixtures/$division/season/$season";
$resultsUrl  = "https://faapi.jwhsolutions.co.uk/api/Results/$division/season/$season";

function fetchJson($url) {
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n",
            'timeout' => 30,
        ],
    ];
    $context = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        return null;
    }
    $json = json_decode($raw, true);
    return $json;
}

try {
    $fixtures = fetchJson($fixturesUrl);
    $results  = fetchJson($resultsUrl);

    $data = [
        'teams'    => [],
        'fixtures' => $fixtures ?: [],
        'results'  => $results ?: [],
        'updated'  => gmdate('Y-m-d H:i:s') . ' UTC',
    ];

    if (!empty($data['fixtures']) || !empty($data['results'])) {
        file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
        echo "Updated data.json\n";
    } else {
        echo "Fetch returned empty, leaving existing data.json untouched\n";
    }
} catch (Exception $e) {
    echo "Fetch failed: " . $e->getMessage() . "\n";
}
