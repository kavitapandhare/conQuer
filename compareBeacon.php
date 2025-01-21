<?php
// Include beacon paths
$beaconPaths = include 'beaconPaths.php';

// Validate beacon paths
if (!$beaconPaths || !is_array($beaconPaths)) {
    die("No beacon paths found. Ensure 'beaconPaths.php' is properly configured.\n");
}

// Parameters from the user request
$params = [
    'beacon1Id' => $_GET['beacon1Id'] ?? null,
    'beacon2Id' => $_GET['beacon2Id'] ?? null
];

// Ensure beacon IDs are provided
if (!$params['beacon1Id'] || !$params['beacon2Id']) {
    die("Both beacon1Id and beacon2Id must be provided.\n");
}

// Check if the beacon IDs are valid
if (!isset($beaconPaths[$params['beacon1Id']]) || !isset($beaconPaths[$params['beacon2Id']])) {
    die("Invalid beacon IDs provided.\n");
}

// Fetch the beacon files
$beacon1File = $beaconPaths[$params['beacon1Id']];
$beacon2File = $beaconPaths[$params['beacon2Id']];

// Function to extract variants from the beacon file
function extractVariants($file) {
    $variants = [];

    // Check if the file exists
    if (!file_exists($file)) {
        die("File not found: $file\n");
    }

    // Read the contents of the file
    $fileContent = file_get_contents($file);

    // Convert file content to UTF-8 if necessary (removing control characters)
    $fileContent = mb_convert_encoding($fileContent, 'UTF-8', 'auto');

    // Decode the JSON content
    $beaconData = json_decode($fileContent, true);

    // Check if JSON is valid
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Failed to decode JSON from file: $file. Error: " . json_last_error_msg() . "\n");
    }

    // Iterate over datasets and extract variants
    foreach ($beaconData['datasets'] as $dataset) {
        foreach ($dataset['variants'] as $variant) {
            // Use chromosome, position, referenceBases, and alternateBases to identify variants
            $key = $variant['chromosome'] . '-' . $variant['position'] . '-' . $variant['referenceBases'];
            // Convert alternateBases array to a comma-separated string for comparison
            $alternateBases = implode(",", $variant['alternateBases']);
            $variants[$key] = [
                'chromosome' => $variant['chromosome'],
                'position' => $variant['position'],
                'referenceBases' => $variant['referenceBases'],
                'alternateBases' => $alternateBases
            ];
        }
    }

    return $variants;
}

// Function to find common variants between two beacons
function findCommonVariants($beacon1Variants, $beacon2Variants) {
    $commonVariants = [];

    // Compare the variants based on the key (chromosome-position-referenceBases)
    foreach ($beacon1Variants as $key => $variant) {
        if (isset($beacon2Variants[$key]) && $beacon2Variants[$key]['alternateBases'] === $variant['alternateBases']) {
            $commonVariants[$key] = $variant;
        }
    }

    return $commonVariants;
}

// Extract variants from both beacons
$beacon1Variants = extractVariants($beacon1File);
$beacon2Variants = extractVariants($beacon2File);

// Find common variants
$commonVariants = findCommonVariants($beacon1Variants, $beacon2Variants);

// If no common variants found, do not generate an output file
if (empty($commonVariants)) {
    echo "No common variants found. No output file will be generated.\n";
    exit;
}

// Prepare output file name based on beacon IDs
$outputFileName = "common_variants_{$params['beacon1Id']}_{$params['beacon2Id']}.json";
$outputFilePath = "commonVariants/" . $outputFileName;

// Write common variants to the output file
file_put_contents($outputFilePath, json_encode($commonVariants, JSON_PRETTY_PRINT));

echo "Common variants found and saved to: {$outputFilePath}\n";
?>
