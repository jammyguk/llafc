<?php
// Sends private alerts (to one ntfy topic) when anything changes in Div 1, or when a Div 1 team's cup game changes.
//   Div 1 league: new results, postponements, new / moved / removed fixtures.
//   Cups: new or changed cup results and fixtures involving a Div 1 team.
// Usage: php notify.php old-data.json new-data.json [old-cups.json new-cups.json]   (DRY=1 prints instead of sending)
$TOPIC = getenv('NTFY_TOPIC') ?: 'lionstext-div1-3665cd8c9f';
$DIV = 'u12-div1';
$j = fn($f) => json_decode(@file_get_contents($f ?? ''), true);
$old = $j($argv[1] ?? ''); $new = $j($argv[2] ?? '');
$oc = isset($argv[3]) ? $j($argv[3]) : null; $nc = isset($argv[4]) ? $j($argv[4]) : null;
$dry = getenv('DRY') === '1';
$clean = fn($s) => trim(html_entity_decode((string)$s, ENT_QUOTES));
$pair = fn($h, $a) => $clean($h) . ' v ' . $clean($a);
$msgs = [];   // [title, body, tags]
$isNum = fn($v) => $v !== null && $v !== '' && ctype_digit((string)$v);

// ---- Div 1 league ----
$O = $old['leagues'][$DIV] ?? null; $N = $new['leagues'][$DIV] ?? null;
$div1Teams = [];
if ($N) {
    foreach (($N['fixtures'] ?? []) as $f) { $div1Teams[$clean($f['homeTeam'])] = 1; $div1Teams[$clean($f['awayTeam'])] = 1; }
    foreach (($N['results'] ?? []) as $r) { $div1Teams[$clean($r['homeTeam'])] = 1; $div1Teams[$clean($r['awayTeam'])] = 1; }
}
if ($O && $N) {
    $k = fn($o) => $pair($o['homeTeam'], $o['awayTeam']) . '|' . substr($o['fixtureDateTime'] ?? '', 0, 8);
    $oldRes = []; foreach (($O['results'] ?? []) as $r) $oldRes[$k($r)] = 1;
    foreach (($N['results'] ?? []) as $r) {
        if (isset($oldRes[$k($r)])) continue;
        $h = $clean($r['homeTeam']); $a = $clean($r['awayTeam']);
        if (stripos(($r['score'] ?? '') . ($r['homeScore'] ?? ''), 'postponed') !== false)
            $msgs[] = ['Div 1: postponed', "$h v $a (" . substr($r['fixtureDateTime'] ?? '', 0, 8) . ") is postponed", 'warning'];
        elseif ($isNum($r['homeScore'] ?? null) && $isNum($r['awayScore'] ?? null))
            $msgs[] = ['Div 1: full time', "$h {$r['homeScore']}-{$r['awayScore']} $a", 'soccer'];
    }
    // fixtures by pairing, so a changed date / time / pitch shows as a change and not as add + remove
    $byPair = function ($list) use ($pair) { $m = []; foreach ($list as $f) $m[$pair($f['homeTeam'], $f['awayTeam'])][] = $f; return $m; };
    $of = $byPair($O['fixtures'] ?? []); $nf = $byPair($N['fixtures'] ?? []);
    $when = fn($f) => ($f['fixtureDateTime'] ?? '?') . (($f['location'] ?? '') !== '' ? ' at ' . $clean($f['location']) : '');
    foreach ($nf as $p => $list) {
        foreach ($list as $i => $f) {
            $o = $of[$p][$i] ?? null;
            if (!$o) { $msgs[] = ['Div 1: new fixture', "$p - " . $when($f), 'calendar']; continue; }
            if ($when($o) !== $when($f) && empty($f['removed']))
                $msgs[] = ['Div 1: fixture changed', "$p\nWas: " . $when($o) . "\nNow: " . $when($f), 'calendar'];
            $fs = strtolower((string)($f['status'] ?? '')); $os = strtolower((string)($o['status'] ?? ''));
            if ($fs !== $os && preg_match('/postpone|cancel|abandon/', $fs))
                $msgs[] = ['Div 1: ' . (strpos($fs, 'postpone') !== false ? 'postponed' : 'cancelled'), "$p (" . ($f['fixtureDateTime'] ?? '') . ") - " . $f['status'], 'warning'];
            if (!empty($f['removed']) && empty($o['removed']))
                $msgs[] = ['Div 1: fixture removed', "$p (" . ($f['fixtureDateTime'] ?? '') . ") has gone from Full-Time - check with the coach", 'warning'];
        }
    }
    $newResPairs = []; foreach (($N['results'] ?? []) as $r) $newResPairs[$pair($r['homeTeam'], $r['awayTeam'])] = 1;
    foreach ($of as $p => $list) if (!isset($nf[$p]) && !isset($newResPairs[$p]))
        $msgs[] = ['Div 1: fixture removed', "$p (" . ($list[0]['fixtureDateTime'] ?? '') . ") has gone from Full-Time", 'warning'];
}

// ---- Cups (only games with a Div 1 team) ----
if ($oc && $nc && $div1Teams) {
    $involved = fn($o) => isset($div1Teams[$clean($o['home'] ?? '')]) || isset($div1Teams[$clean($o['away'] ?? '')]);
    foreach (($nc['cups'] ?? []) as $cup) {
        $oldCup = null; foreach (($oc['cups'] ?? []) as $c) if (($c['name'] ?? '') === ($cup['name'] ?? '')) $oldCup = $c;
        if (!$oldCup) continue;
        $ck = fn($o) => $pair($o['home'] ?? '', $o['away'] ?? '');
        $oldR = []; foreach (($oldCup['results'] ?? []) as $r) $oldR[$ck($r)] = 1;
        foreach (($cup['results'] ?? []) as $r) if ($involved($r) && !isset($oldR[$ck($r)]))
            $msgs[] = ['Cup: full time', $clean($r['home']) . " {$r['hs']}-{$r['as']} " . $clean($r['away']) . "\n" . $cup['name'], 'trophy'];
        $oldF = []; foreach (($oldCup['fixtures'] ?? []) as $f) $oldF[$ck($f)] = $f;
        foreach (($cup['fixtures'] ?? []) as $f) {
            if (!$involved($f)) continue;
            $o = $oldF[$ck($f)] ?? null; $w = fn($x) => ($x['date'] ?? '?') . (($x['venue'] ?? '') !== '' ? ' at ' . $clean($x['venue']) : '');
            if (!$o) $msgs[] = ['Cup: new fixture', $ck($f) . ' - ' . $w($f) . "\n" . $cup['name'], 'trophy'];
            elseif ($w($o) !== $w($f)) $msgs[] = ['Cup: fixture changed', $ck($f) . "\nWas: " . $w($o) . "\nNow: " . $w($f) . "\n" . $cup['name'], 'trophy'];
        }
    }
}

$sent = 0;
foreach ($msgs as [$title, $body, $tags]) {
    echo "$title | " . str_replace("\n", ' / ', $body) . "\n";
    if ($dry) continue;
    $ch = curl_init("https://ntfy.sh/$TOPIC");
    curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ["Title: $title", "Tags: lion,$tags", "Click: https://lionstext.co.uk/", "Icon: https://lionstext.co.uk/icon-192.png"]]);
    curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    if ($code >= 200 && $code < 300) $sent++; else echo "  (send failed: HTTP $code)\n";
    usleep(300000);
}
echo $dry ? "Dry run: " . count($msgs) . " alerts.\n" : "Sent $sent alerts.\n";
exit(0);
