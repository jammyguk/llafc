<?php
$season = '503728397';
$base = 'https://faapi.jwhsolutions.co.uk/api';

// ---------------------------------------------------------------
// LEAGUES. One block per league. The site finds every team in a
// league by itself, so you only add the league here.
//   id       : short name, no spaces (used in the page address)
//   name     : title shown on the site
//   division : the selectedDivision number from the Full-Time page
//   season   : the selectedSeason number from the Full-Time page
// To add a league, copy a block, paste it before the closing ];
// and change the four values.
// ---------------------------------------------------------------
$leagues = [
    [
        'id'       => 'u12-div1',
        'name'     => 'U12 Div 1 D&J Mobile Catering Ltd',
        'division' => '862501863',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div2',
        'name'     => 'U12 Division 2',
        'division' => '66157653',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div3',
        'name'     => 'U12 Division 3',
        'division' => '531402548',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div4',
        'name'     => 'U12 Division 4',
        'division' => '475633763',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div5',
        'name'     => 'U12 Division 5',
        'division' => '773748290',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div6',
        'name'     => 'U12 Division 6',
        'division' => '804709299',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div7',
        'name'     => 'U12 Division 7',
        'division' => '809305723',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div8',
        'name'     => 'U12 Division 8',
        'division' => '605578398',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div9',
        'name'     => 'U12 Division 9',
        'division' => '260569942',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div10',
        'name'     => 'U12 Division 10',
        'division' => '29812328',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div11',
        'name'     => 'U12 Division 11',
        'division' => '524981514',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div12',
        'name'     => 'U12 Division 12',
        'division' => '225046148',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div13',
        'name'     => 'U12 Division 13',
        'division' => '336151119',
        'season'   => '503728397',
    ],
    [
        'id'       => 'u12-div14',
        'name'     => 'U12 Division 14',
        'division' => '466409266',
        'season'   => '503728397',
    ],
];

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

// Manual fallback for the U12 Reds league, used only if the Results
// feed returns nothing for it.
$fallback = [
    'u12-div1' => [
        'results' => [
            ["fixtureDateTime" => "06/09/26 10:30", "homeTeam" => "Loughborough Lions AFC U12 Reds", "awayTeam" => "Harborough Town Juniors U12 Reds Sunday", "homeScore" => 0, "awayScore" => 1],
            ["fixtureDateTime" => "13/09/26 10:30", "homeTeam" => "Studs Junior U12", "awayTeam" => "Loughborough Lions AFC U12 Reds", "homeScore" => 2, "awayScore" => 5],
            ["fixtureDateTime" => "20/09/26 10:30", "homeTeam" => "Loughborough Lions AFC U12 Reds", "awayTeam" => "Melton Town U12 Tigers", "homeScore" => 6, "awayScore" => 1],
            ["fixtureDateTime" => "27/09/26 10:30", "homeTeam" => "Whetstone Athletic U12 Lions", "awayTeam" => "Loughborough Lions AFC U12 Reds", "homeScore" => 0, "awayScore" => 4],
            ["fixtureDateTime" => "04/10/26 10:30", "homeTeam" => "Loughborough Lions AFC U12 Reds", "awayTeam" => "Harborough Town Juniors U12 Reds Sunday", "homeScore" => 4, "awayScore" => 3],
        ],
        'table' => [
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
        ],
    ],
];

$old = [];
if (file_exists('data.json')) {
    $old = json_decode(file_get_contents('data.json'), true) ?: [];
}
$oldLeagues = $old['leagues'] ?? [];

$out = [];
foreach ($leagues as $lg) {
    $id = $lg['id'];
    $s  = $lg['season'] ?? $season;
    $d  = $lg['division'];

    $fixtures = fetchJson("$base/Fixtures/$d/season/$s");
    $results  = fetchJson("$base/Results/$d/season/$s");

    $entry = [
        'name'     => $lg['name'],
        'fixtures' => $fixtures ?? ($oldLeagues[$id]['fixtures'] ?? []),
    ];

    if (!empty($results)) {
        $entry['results'] = $results;
    } elseif (isset($fallback[$id])) {
        $entry['results'] = $fallback[$id]['results'];
        $entry['table']   = $fallback[$id]['table'];
    } else {
        $entry['results'] = $oldLeagues[$id]['results'] ?? [];
    }
    $out[$id] = $entry;
}

$data = [
    'updated' => gmdate('Y-m-d H:i:s') . ' UTC',
    'leagues' => $out,
];

file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Updated data.json\n";
