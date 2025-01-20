<?php

$cacheDurationInHours = 15;
$cacheDurationInSeconds = 60 * 60 * $cacheDurationInHours;

return [
    'loosen_participation_rules' => [
        'user_ids' => [93]
    ],
    'max_mob_size' => 10,
    'cache_duration_in_seconds' => $cacheDurationInSeconds,
];
