<?php

require 'helpers.php';

$regions = json_decode(file_get_contents('./config/territories.json'), true);

getRegionsVotingResultsForYear($regions, 2024);

?>
