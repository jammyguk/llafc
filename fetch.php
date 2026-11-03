<?php
$season = '503728397';
$base = getenv('API_BASE') ?: 'https://faapi.jwhsolutions.co.uk/api';

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

// Asks for pages several at a time: a rolling queue that starts the next page
// as soon as one finishes. $parallel = how many at once. Pages that fail are
// asked for again, up to $tries times.
// Returns [key => decoded JSON, or null if it never worked].
function fetchPool(array $urls, $parallel) {
    $mh = curl_multi_init();
    $queue = $urls;
    $active = [];
    $out = [];
    $add = function () use (&$queue, &$active, $mh) {
        $key = array_key_first($queue);
        if ($key === null) {
            return false;
        }
        $url = $queue[$key];
        unset($queue[$key]);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_TIMEOUT        => 150,
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'User-Agent: Mozilla/5.0'],
        ]);
        curl_multi_add_handle($mh, $ch);
        $active[spl_object_id($ch)] = $key;
        return true;
    };
    while (count($active) < $parallel && $add()) {
    }
    do {
        curl_multi_exec($mh, $running);
        while (($info = curl_multi_info_read($mh)) !== false) {
            $ch  = $info['handle'];
            $key = $active[spl_object_id($ch)];
            $raw  = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $secs = round(curl_getinfo($ch, CURLINFO_TOTAL_TIME));
            $json = ($code === 200 && is_string($raw)) ? json_decode($raw, true) : null;
            echo "  $key -> HTTP $code, {$secs}s" . (is_array($json) ? " ok" : " FAILED") . "\n";
            $out[$key] = is_array($json) ? $json : null;
            curl_multi_remove_handle($mh, $ch);
            unset($active[spl_object_id($ch)]);
            $add();
        }
        if (!empty($active)) {
            curl_multi_select($mh, 1.0);
        }
    } while (!empty($active));
    curl_multi_close($mh);
    return $out;
}

function fetchAll(array $urls, $parallel = 6, $tries = 3) {
    $got = [];
    $pending = $urls;
    for ($round = 1; $round <= $tries && $pending; $round++) {
        echo "Round $round: " . count($pending) . " pages, $parallel at a time\n";
        foreach (fetchPool($pending, $parallel) as $key => $json) {
            if ($json !== null) {
                $got[$key] = $json;
                unset($pending[$key]);
            }
        }
        if ($pending && $round < $tries) {
            sleep(5);
        }
    }
    foreach ($pending as $key => $url) {
        $got[$key] = null;
    }
    return $got;
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

// Ask for every league's fixtures and results in one go.
$urls = [];
foreach ($leagues as $lg) {
    $s = $lg['season'] ?? $season;
    $d = $lg['division'];
    $urls['fx:' . $lg['id']] = "$base/Fixtures/$d/season/$s";
    $urls['rs:' . $lg['id']] = "$base/Results/$d/season/$s";
}
$got = fetchAll($urls, (int)(getenv("MAX_PARALLEL") ?: 6));

// If nothing at all came back, the data service is down: leave data.json
// exactly as it is (including its "updated" time) instead of pretending.
if (count(array_filter($got, fn($v) => $v !== null)) === 0) {
    echo "No data came back from the service - leaving data.json unchanged.\n";
    exit(0);
}

$out = [];
foreach ($leagues as $lg) {
    $id = $lg['id'];

    $fixtures = $got['fx:' . $id] ?? null;
    $results  = $got['rs:' . $id] ?? null;

    $entry = [
        'name'     => $lg['name'],
        'fixtures' => $fixtures ?? ($oldLeagues[$id]['fixtures'] ?? []),
    ];

    if (!empty($results)) {
        $entry['results'] = $results;
    } elseif (!empty($oldLeagues[$id]['results'])) {
        // feed gave nothing this time: keep the last good results
        $entry['results'] = $oldLeagues[$id]['results'];
        if (isset($oldLeagues[$id]['table'])) {
            $entry['table'] = $oldLeagues[$id]['table'];
        }
    } elseif (isset($fallback[$id])) {
        $entry['results'] = $fallback[$id]['results'];
        $entry['table']   = $fallback[$id]['table'];
    } else {
        $entry['results'] = $oldLeagues[$id]['results'] ?? [];
    }
    // A fixture that vanishes from the feed within a week of kick-off (and has not become a result or moved to a
    // new date) is kept and flagged "removed", so the site can warn people to check with their coach.
    if ($fixtures !== null && !empty($oldLeagues[$id]['fixtures'])) {
        $pairOf = fn($o) => trim(html_entity_decode((string)($o['homeTeam'] ?? ''))) . '|' . trim(html_entity_decode((string)($o['awayTeam'] ?? '')));
        $present = [];
        foreach (array_merge($entry['fixtures'], $entry['results']) as $o) $present[$pairOf($o)] = true;
        $tz = new DateTimeZone('Europe/London');
        $today = new DateTime('today', $tz);
        $limit = (clone $today)->modify('+7 days');
        foreach ($oldLeagues[$id]['fixtures'] as $o) {
            if (isset($present[$pairOf($o)])) continue;
            $d = DateTime::createFromFormat('d/m/y H:i', $o['fixtureDateTime'] ?? '', $tz);
            if (!$d || $d < $today || $d > $limit) continue;
            $o['removed'] = true;
            $entry['fixtures'][] = $o;
        }
    }
    $out[$id] = $entry;
}

// Only write a new data.json if the fixtures/results actually changed since
// last time (so a quiet scan makes no commit and no site rebuild).
if (($old['leagues'] ?? null) == $out) {
    echo "Scanned all leagues: no changes.\n";
    exit(0);
}

$data = [
    'updated' => gmdate('Y-m-d H:i:s') . ' UTC',
    'leagues' => $out,
];

file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Updated data.json\n";
