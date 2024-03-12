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

function getParties(array $partyResults = null): array
{
    global $parties;
    if ($partyResults == null) {
        return $parties;
    }
    foreach ($partyResults as $party) {
        $parties[] = $party['acronym'];
    }
    return $parties;
}

function getHeader(array $partyResults = null): array
{
    global $parties;
    $parties = getParties($partyResults);

    $header = ['Ano', 'Região', 'Concelho', 'Freguesia', 'Eleitores Registrados', 'Votos Nulos', 'Votos em Branco', 'Votos Válidos', 'Abstenção'];
    foreach ($parties as $party) {
        $header[] = $party;
    }
    return $header;
}

function formatResultItem($regiao, $concelho, $freguesia, $year, $item): array
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

    foreach (getParties() as $party) {
        $row[$party] = 0;
    }

    foreach ($item['resultsParty'] as $party) {
        $row[$party['acronym']] = $party['votes'];
    }
    return $row;
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
    file_put_contents('legislativas-' . $year . '.csv', $csv);
}

function getRegionsVotingResultsForYear($regions, $year)
{
    $rows = [];
    $header_is_set = false;

    foreach ($regions as $region) {
        $concelhos = getTerritoryChildren($region['territoryKey'], $year);
        foreach ($concelhos as $concelho) {
            $freguesias = getTerritoryChildren($concelho['territoryKey'], $year);
            foreach ($freguesias as $freguesia) {
                $freguesia_results = getVotingResults($freguesia['territoryKey'], $year);
                if (!$header_is_set) {
                    $rows[] = getHeader($freguesia_results['currentResults']['resultsParty']);
                    $header_is_set = true;
                }
                $rows[] = formatResultItem($region['name'], $concelho['name'], $freguesia_results['territoryFullName'], $year, $freguesia_results['currentResults']);
            }
        }
    }

    save_as_csv($rows, $year);
}

?>
