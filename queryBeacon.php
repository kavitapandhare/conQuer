<?php
// List of available Beacons (map beaconId to file paths)
// $availableBeacons = [
//     "beacon1" => "beacon/ALL.chrMT.phase3_callmom-v0_4.20130502.genotypes.json",
//     "beacon2" => "beacon/ALL.chr21.phase3_shapeit2_mvncall_integrated_v5b.20130502.genotypes.json",
// ];
// Include beacon paths from the external file
$beaconPaths = include 'beaconPaths.php';

// Validate beacon paths
if (!$beaconPaths || !is_array($beaconPaths)) {
    die("No beacon paths found. Ensure 'beaconPaths.php' is properly configured.\n");
}

// List of available Beacons (map beaconId to file paths)
$availableBeacons = $beaconPaths;

// Parameters from the user request
$params = [
    'chromosome' => $_GET['chromosome'] ?? null,
    'position' => $_GET['position'] ?? null,
    'referenceBases' => $_GET['referenceBases'] ?? null,
    'alternateBases' => $_GET['alternateBases'] ?? null,
    'beaconId' => $_GET['beaconId'] ?? null // Optional: Specific Beacon ID
];

$response = [
    'id' => '',
    'apiVersion' => '1.0.0',
    'createDateTime' => date(DATE_ATOM),
    'alleleRequest' => $params,
    'datasetAlleleResponses' => []
];

// Function to process a single Beacon file
function processBeaconFile($beaconFile, $params) {
    if (!file_exists($beaconFile)) {
        return null;
    }

    $beaconData = json_decode(file_get_contents($beaconFile), true);

    // Get the beacon ID from the data
    $beaconId = isset($beaconData['beaconId']) ? $beaconData['beaconId'] : 'unknown-beacon-id';

    $responses = [];
    $datasetAlleleResponses = [];

    foreach ($beaconData['datasets'] as $dataset) {
        $exists = false;
        $variantCount = 0;
        $callCount = 0;
        $sampleCount = 0;

        foreach ($dataset['variants'] as $variant) {
            if (
                (empty($params['chromosome']) || $variant['chromosome'] === $params['chromosome']) &&
                (empty($params['position']) || $variant['position'] === (int)$params['position']) &&
                (empty($params['referenceBases']) || $variant['referenceBases'] === $params['referenceBases']) &&
                (empty($params['alternateBases']) || in_array($params['alternateBases'], $variant['alternateBases']))
            ) {
                $exists = true;
                $variantCount++;
                $callCount += $variant['callCount'] ?? 0;
                $sampleCount += $variant['sampleCount'] ?? 0;
            }
        }

        if ($exists) {
            $datasetAlleleResponses[] = [
                'datasetId' => $dataset['id'],
                'exists' => $exists,
                'assemblyId' => $dataset['assemblyId'] ?? 'Unknown',
                'variantCount' => $variantCount,
                'callCount' => $callCount,
                'sampleCount' => $sampleCount,
                'note' => $dataset['description'] ?? 'No description available'
            ];
        }
    }

    return [
        'id' => $beaconId,
        'datasetAlleleResponses' => $datasetAlleleResponses
    ];
}

// Query a specific beacon or all beacons
if (!empty($params['beaconId'])) {
    if (isset($availableBeacons[$params['beaconId']])) {
        $beaconFile = $availableBeacons[$params['beaconId']];
        $result = processBeaconFile($beaconFile, $params);

        if ($result) {
            $response['id'] = $result['id'];
            $response['datasetAlleleResponses'] = $result['datasetAlleleResponses'];
        } else {
            $response['error'] = "No matching variants found in the specified Beacon.";
        }
    } else {
        $response['error'] = "Invalid beaconId specified.";
    }
} else {
    foreach ($availableBeacons as $beaconId => $beaconFile) {
        $result = processBeaconFile($beaconFile, $params);

        if ($result && !empty($result['datasetAlleleResponses'])) {
            $response['id'] = $result['id']; // Set ID dynamically based on the beacon
            $response['datasetAlleleResponses'] = array_merge(
                $response['datasetAlleleResponses'] ?? [],
                $result['datasetAlleleResponses']
            );
        }
    }

    if (empty($response['datasetAlleleResponses'])) {
        $response['error'] = "No matching variants found in any Beacon.";
    }
}

// Output the JSON response
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);

?>
