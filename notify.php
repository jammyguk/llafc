<?php
// Sends full-time score alerts for Loughborough Lions teams by comparing the old and new data.json.
// Fixture changes are deliberately not alerted: coaches handle those.
// Usage: php notify.php old.json new.json   (set DRY=1 to print instead of send)
// Each team has its own ntfy topic: lionstext-<team name as a slug>. Parents subscribe from the site.
$old = json_decode(@file_get_contents($argv[1] ?? ''), true);
$new = json_decode(@file_get_contents($argv[2] ?? ''), true);
if (!$old || !$new) { echo "No old or new data, nothing to compare.\n"; exit(0); }
$dry = getenv('DRY') === '1';
$clean = fn($s) => trim(html_entity_decode((string)$s, ENT_QUOTES));
$slug  = fn($s) => trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($clean($s))), '-');
$isLions = fn($s) => stripos($clean($s), 'Loughborough Lions') === 0;
$key = fn($o) => $clean($o['homeTeam']) . '|' . $clean($o['awayTeam']) . '|' . substr($o['fixtureDateTime'] ?? '', 0, 8);
$pair = fn($o) => $clean($o['homeTeam']) . '|' . $clean($o['awayTeam']);

$msgs = [];  // topic => list of [title, body, tags]
$add = function ($team, $title, $body, $tags) use (&$msgs, $slug) { $msgs['lionstext-' . $slug($team)][] = [$title, $body, $tags]; };

foreach ($new['leagues'] as $id => $L) {
    $O = $old['leagues'][$id] ?? null;
    if (!$O) continue;  // a league seen for the first time never alerts
    // 1. New results
    $oldRes = [];
    foreach ($O['results'] ?? [] as $r) $oldRes[$key($r)] = 1;
    foreach ($L['results'] ?? [] as $r) {
        if (isset($oldRes[$key($r)])) continue;
        if (!isset($r['homeScore']) || $r['homeScore'] === '' || !isset($r['awayScore']) || $r['awayScore'] === '') continue;
        $h = $clean($r['homeTeam']); $a = $clean($r['awayTeam']);
        $hs = (int)$r['homeScore']; $as = (int)$r['awayScore'];
        foreach ([[$h, $hs, $as, $a], [$a, $as, $hs, $h]] as [$team, $f, $ag, $opp]) {
            if (!$isLions($team)) continue;
            $res = $f > $ag ? 'Win' : ($f < $ag ? 'Defeat' : 'Draw');
            $add($team, "Full time: $res", "$h $hs-$as $a", $f > $ag ? 'white_check_mark' : ($f < $ag ? 'x' : 'handshake'));
        }
    }
}
$sent = 0;
foreach ($msgs as $topic => $list) foreach ($list as [$title, $body, $tags]) {
    echo "$topic | $title | $body\n";
    if ($dry) continue;
    $ch = curl_init("https://ntfy.sh/$topic");
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ["Title: $title", "Tags: $tags", "Click: https://jammyguk.github.io/LLAFC/"]]);
    curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($code >= 200 && $code < 300) $sent++; else echo "  (send failed: HTTP $code)\n";
    usleep(300000);
}
echo $dry ? "Dry run.\n" : "Sent $sent alerts.\n";
exit(0);
