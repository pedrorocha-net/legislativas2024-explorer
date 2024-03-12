<?php

global $parties;
$parties = [];

function getTerritoryChildren($code, $year)
{
    if ($year == 2024) {
        $url = "https://www.legislativas2024.mai.gov.pt/frontend/data/TerritoryChildren?territoryKey=$code";
    } else if ($year == 2022) {
        $url = "https://www.eleicoes.mai.gov.pt/legislativas2022/assets/static/territory-children/territory-children-$code.json";
    } else {
        $url = "https://www.eleicoes.mai.gov.pt/legislativas$year/static-data/territory-children/TERRITORY-CHILDREN-$code.json";
    }
    $json = file_get_contents($url);
    return json_decode($json, true);
}

function getVotingResults($code, $year)
{
    if ($year == 2024) {
        $url = "https://www.legislativas2024.mai.gov.pt/frontend/data/TerritoryResults?territoryKey=$code&electionId=AR";
    } else if ($year == 2022) {
        $url = "https://www.eleicoes.mai.gov.pt/legislativas2022/assets/static/territory-results/territory-results-$code-AR.json";
    } else {
        $url = "https://www.eleicoes.mai.gov.pt/legislativas$year/static-data/territory-results/TERRITORY-RESULTS-$code-AR.json";
    }
    $json = file_get_contents($url);
    return json_decode($json, true);
}

function getHeader(array $parties = null): array
{
    $header = ['Ano', 'Região', 'Concelho', 'Freguesia', 'Eleitores Registrados', 'Votos Nulos', 'Votos em Branco', 'Votos Válidos', 'Abstenção'];
    foreach ($parties as $party) {
        $header[] = $party;
    }
    return $header;
}

function formatResultItem($regiao, $concelho, $freguesia, $year, $item, $parties): array
{
    $row = [];
    $row['ano'] = $year;
    $row['regiao'] = $regiao;
    $row['concelho'] = $concelho;
    $row['freguesia'] = $freguesia;
    $row['subscribedVoters'] = $item['subscribedVoters'];
    $row['nullVotes'] = $item['nullVotes'];
    $row['blankVotes'] = $item['blankVotes'];
    $row['totalVoters'] = $item['totalVoters'];
    $row['missing'] = $item['subscribedVoters'] - $item['totalVoters'];

    foreach ($parties as $party) {
        $row[$party] = 0;
    }

    foreach ($item['resultsParty'] as $party) {
        $row[$party['acronym']] = $party['votes'];
    }
    return $row;
}

function getRegionsVotingResultsForYear($regions, $year)
{
    global $parties;
    $parties = [];
    $header_raw_data = [];
    $rows_raw_data = [];
    $rows = [];

//    $a = 0;
    foreach ($regions as $region) {
        $concelhos = getTerritoryChildren($region['territoryKey'], $year);
        foreach ($concelhos as $concelho) {
            $freguesias = getTerritoryChildren($concelho['territoryKey'], $year);
            foreach ($freguesias as $freguesia) {
                $freguesia_results = getVotingResults($freguesia['territoryKey'], $year);
                foreach ($freguesia_results['currentResults']['resultsParty'] as $party) {
                    $parties[$party['acronym']] = $party['acronym'];
                }
                $rows_raw_data[] = [
                    $region['name'],
                    $concelho['name'],
                    $freguesia_results['territoryFullName'],
                    $year,
                    $freguesia_results['currentResults']
                ];
            }
        }
        ksort($header_raw_data);

//        $a++;
//        if ($a == 5) {
//            break;
//        }
    }
    $rows[] = getHeader($parties);
    foreach ($rows_raw_data as $row) {
        $rows[] = formatResultItem($row[0], $row[1], $row[2], $row[3], $row[4], $parties);
    }

    save_as_csv($rows, $year);
}

function save_as_csv($rows, $year)
{
    $fp = fopen('php://memory', 'r+');
    foreach ($rows as $row) {
        fputcsv($fp, $row);
    }
    rewind($fp);
    $csv = stream_get_contents($fp);
    fclose($fp);
    file_put_contents('./data/legislativas-' . $year . '.csv', $csv);
}

?>
