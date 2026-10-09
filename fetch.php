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

// Manually entered results, added while the Results feed from the data
// service is unreliable for this division. Remove this block once the
// service starts returning real results and data.json fills in on its own.
$manualResults = [
    ["fixtureDateTime" => "06/09/26 10:30", "homeTeam" => "Loughborough Lions AFC U12 Reds", "awayTeam" => "Harborough Town Juniors U12 Reds Sunday", "homeScore" => 0, "awayScore" => 1, "location" => "", "competition" => "U12 Div 1 D&J Mobile Catering Ltd"],
    ["fixtureDateTime" => "13/09/26 10:30", "homeTeam" => "Studs Junior U12", "awayTeam" => "Loughborough Lions AFC U12 Reds", "homeScore" => 2, "awayScore" => 5, "location" => "", "competition" => "U12 Div 1 D&J Mobile Catering Ltd"],
    ["fixtureDateTime" => "20/09/26 10:30", "homeTeam" => "Loughborough Lions AFC U12 Reds", "awayTeam" => "Melton Town U12 Tigers", "homeScore" => 6, "awayScore" => 1, "location" => "", "competition" => "U12 Div 1 D&J Mobile Catering Ltd"],
    ["fixtureDateTime" => "27/09/26 10:30", "homeTeam" => "Whetstone Athletic U12 Lions", "awayTeam" => "Loughborough Lions AFC U12 Reds", "homeScore" => 0, "awayScore" => 4, "location" => "", "competition" => "U12 Div 1 D&J Mobile Catering Ltd"],
    ["fixtureDateTime" => "04/10/26 10:30", "homeTeam" => "Loughborough Lions AFC U12 Reds", "awayTeam" => "Harborough Town Juniors U12 Reds Sunday", "homeScore" => 4, "awayScore" => 3, "location" => "", "competition" => "U12 Div 1 D&J Mobile Catering Ltd"],
];

// Manually entered table, same reason as above. No goal difference column
// since it wasn't supplied. Position is implied by list order.
$manualTable = [
    ["teamName" => "Loughborough Lions AFC U12 Reds", "played" => 5, "won" => 4, "drawn" => 0, "lost" => 1, "points" => 12],
    ["teamName" => "Heather Juniors U12 Lions", "played" => 5, "won" => 3, "drawn" => 1, "lost" => 1, "points" => 10],
    ["teamName" => "Beaumont Park U12 Jaguars", "played" => 5, "won" => 3, "drawn" => 0, "lost" => 2, "points" => 9],
    ["teamName" => "Croft Juniors 2013 U12 Blues", "played" => 5, "won" => 3, "drawn" => 0, "lost" => 2, "points" => 9],
    ["teamName" => "Beaumont Park U12 Panthers", "played" => 4, "won" => 2, "drawn" => 1, "lost" => 1, "points" => 7],
    ["teamName" => "Harborough Town Juniors U12 ETP Sunday", "played" => 5, "won" => 2, "drawn" => 1, "lost" => 2, "points" => 7],
    ["teamName" => "Studs Junior U12", "played" => 5, "won" => 2, "drawn" => 1, "lost" => 2, "points" => 7],
    ["teamName" => "Harborough Town Juniors U12 Reds Sunday", "played" => 3, "won" => 1, "drawn" => 0, "lost" => 2, "points" => 3],
    ["teamName" => "Whetstone Athletic U12 Lions", "played" => 4, "won" => 1, "drawn" => 0, "lost" => 3, "points" => 3],
    ["teamName" => "Melton Town U12 Tigers", "played" => 5, "won" => 0, "drawn" => 0, "lost" => 5, "points" => 0],
];

$old = [];
if (file_exists('data.json')) {
    $old = json_decode(file_get_contents('data.json'), true) ?: [];
}

$fixtures = fetchJson("$base/Fixtures/$division/season/$season");
$results  = fetchJson("$base/Results/$division/season/$season");

$data = [
    'teams'    => [],
    'fixtures' => $fixtures ?? ($old['fixtures'] ?? []),
    'results'  => (!empty($results)) ? $results : $manualResults,
    'table'    => $manualTable,
    'updated'  => gmdate('Y-m-d H:i:s') . ' UTC',
];

file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Updated data.json\n";
