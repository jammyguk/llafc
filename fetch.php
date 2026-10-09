<?php
require 'vendor/autoload.php';
use Jadgray\FullTimeApi\Division;

$season = 503728397;
$group  = '1_29099243';

try {
    $d = new Division();
    $data = [
        'teams'    => $d->getTeams($season, $group),
        'fixtures' => $d->getFormattedFixtures($season, $group),
        'results'  => $d->getFormattedResults($season, $group),
        'updated'  => gmdate('Y-m-d H:i:s') . ' UTC',
    ];

    if (!empty($data['teams']) || !empty($data['fixtures']) || !empty($data['results'])) {
        file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
        echo "Updated data.json\n";
    } else {
        echo "Fetch returned empty, leaving existing data.json untouched\n";
    }
} catch (Exception $e) {
    echo "Fetch failed: " . $e->getMessage() . "\n";
}
