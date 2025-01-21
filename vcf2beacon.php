<?php
ini_set('memory_limit', '2G'); 

/**
 * Convert VCF files (including compressed .vcf.gz) in the "vcf" folder to Beacon API version 1 JSON.
 * Save the output JSON files in the "beacon" folder with the same filenames.
 */

define('VCF_FOLDER', __DIR__ . '/vcf'); // Folder containing VCF files
define('BEACON_FOLDER', __DIR__ . '/beacon'); // Folder to store Beacon API JSON files
define('BEACON_PATH_FILE', __DIR__ . '/beaconPaths.php'); // File to store the beacon file paths array
define('BASE_PATH', __DIR__ . '/'); // Set the base path that needs to be removed

// Load existing beacon paths if the file exists
$existingBeaconPaths = [];
if (file_exists(BEACON_PATH_FILE)) {
    $existingBeaconPaths = include BEACON_PATH_FILE;
}

// Main function to process all VCF files
function convertVCFFolderToBeacon()
{
    global $existingBeaconPaths;

    // Check if the VCF folder exists
    if (!is_dir(VCF_FOLDER)) {
        die("The 'vcf' folder does not exist. Please create it and add VCF files.\n");
    }

    // Create the 'beacon' folder if it doesn't exist
    if (!is_dir(BEACON_FOLDER)) {
        mkdir(BEACON_FOLDER, 0777, true);
    }

    // Get all VCF files, including compressed ones
    $vcfFiles = array_merge(
        glob(VCF_FOLDER . '/*.vcf'),
        glob(VCF_FOLDER . '/*.vcf.gz')
    );

    if (empty($vcfFiles)) {
        die("No VCF files found in the 'vcf' folder.\n");
    }

    $beaconPaths = $existingBeaconPaths; // Start with the existing beacon paths
    $beaconCounter = count($existingBeaconPaths) + 1; // Continue from the next available beacon number

    foreach ($vcfFiles as $vcfFile) {
        // Generate the Beacon API file name (without .vcf and .gz)
        $vcfFileName = basename($vcfFile);

        // Remove .vcf.gz or .vcf extension
        if (substr($vcfFileName, -7) === '.vcf.gz') {
            $vcfFileName = substr($vcfFileName, 0, -7); // Remove .vcf.gz extension
        } elseif (substr($vcfFileName, -4) === '.vcf') {
            $vcfFileName = substr($vcfFileName, 0, -4); // Remove .vcf extension
        }

        // Set the final Beacon API JSON file name
        $beaconFileName = BEACON_FOLDER . "/$vcfFileName.json";

        // Check if the Beacon API file already exists
        if (file_exists($beaconFileName)) {
            echo "Beacon API already exists for " . basename($vcfFile) . ". Skipping...\n";
            // If it already exists, store the path without regeneration
            $relativeBeaconPath = str_replace(BASE_PATH, '', $beaconFileName);
            // Add to beaconPaths only if the path doesn't already exist
            if (!in_array($relativeBeaconPath, $beaconPaths)) {
                $beaconPaths["beacon$beaconCounter"] = $relativeBeaconPath;
                $beaconCounter++; // Increment the beacon counter for the next beacon
            }
            continue; // Skip generating Beacon API if it already exists
        }

        echo "Processing: " . basename($vcfFile) . "\n";

        $beaconData = convertVCFToBeaconAPI($vcfFile, $vcfFileName);
        if ($beaconData) {
            file_put_contents($beaconFileName, json_encode($beaconData, JSON_PRETTY_PRINT));
            echo "Saved: $beaconFileName\n";
            // Remove the base path and store only the relative path
            $relativeBeaconPath = str_replace(BASE_PATH, '', $beaconFileName);
            // Add the beacon to the array only if it's not already present
            if (!in_array($relativeBeaconPath, $beaconPaths)) {
                $beaconPaths["beacon$beaconCounter"] = $relativeBeaconPath;
                $beaconCounter++; // Increment the beacon counter for the next beacon
            }
        } else {
            echo "Error processing: " . basename($vcfFile) . "\n";
        }
    }

    echo "All files have been processed.\n";

    // Save the updated beacon paths to the PHP file
    file_put_contents(BEACON_PATH_FILE, '<?php return ' . var_export($beaconPaths, true) . ';');
}

// Convert a single VCF file (or compressed VCF) to Beacon API JSON format
function convertVCFToBeaconAPI($vcfFile, $vcfFileName)
{
    // Initialize Beacon API structure
    $beaconData = [
        'beaconId' => $vcfFileName,
        'apiVersion' => '1.0.0',
        'organization' => [
            'id' => 'example-org',
            'name' => 'Example Organization'
        ],
        'createDateTime' => date('c'),
        'datasets' => []
    ];

    // Open the VCF file (handle both regular and compressed VCF files)
    $handle = null;
    if (substr($vcfFile, -3) === '.gz') {
        $handle = gzopen($vcfFile, 'r'); // Open compressed file
    } else {
        $handle = fopen($vcfFile, 'r'); // Open regular file
    }

    if ($handle === false) {
        echo "Failed to open file: $vcfFile\n";
        return false;
    }

    // Variables to hold sample and call counts
    $sampleCount = 0;
    $callCount = 0;
    $assemblyId = 'GRCh38'; // Default assembly ID (could be changed based on header data)
    $description = ''; // Initialize description 
    $genotypeDescription = ''; // Initialize genotype description

    // Process the VCF file header to extract metadata
    while (($line = ($handle instanceof resource ? fgets($handle) : gzgets($handle))) !== false) {
        if (substr($line, 0, 1) === '#') {
            // Header lines
            if (strpos($line, '##assembly') !== false) {
                // Extract assembly ID from header (if available)
                if (preg_match('/##assembly=(\S+)/', $line, $matches)) {
                    $assemblyId = $matches[1];
                }
            }

            // Extract genotype description from ##FORMAT line for GT field
            if (strpos($line, '##FORMAT=<ID=GT') === 0) {
                if (preg_match('/Description="([^"]+)"/', $line, $matches)) {
                    $genotypeDescription = $matches[1];
                }
            }

            if (strpos($line, '#CHROM') === 0) {
                // The line starting with "#CHROM" contains sample names
                $sampleCount = count(explode("\t", trim($line))) - 9; // Subtract 9 for standard columns
            }
        } else {
            // Process variant lines
            $fields = explode("\t", trim($line));
            if (count($fields) < 5) {
                continue; // Skip invalid lines
            }

            $chrom = $fields[0];
            $pos = $fields[1];
            $id = $fields[2];
            $ref = $fields[3];
            $alt = $fields[4];

            // Add variant to the dataset
            $dataset['variants'][] = [
                'chromosome' => $chrom,
                'position' => (int)$pos,
                'referenceBases' => $ref,
                'alternateBases' => explode(',', $alt),
                'variantId' => $id
            ];

            $callCount++;
        }
    }

    if ($handle instanceof resource) {
        fclose($handle);
    } else {
        gzclose($handle);
    }

     // If description was not found, set a default
     if (!$description) {
        $description = "No description available.";
    }

    // Prepare dataset information
    $dataset = [
        'id' => basename($vcfFile, '.vcf'),
        'description' => $genotypeDescription ,
        'assemblyId' => $assemblyId,
        'sampleCount' => $sampleCount,
        'callCount' => $callCount,
        'variants' => $dataset['variants'] ?? []
    ];

    $beaconData['datasets'][] = $dataset;

    return $beaconData;
}

// Execute the conversion process
convertVCFFolderToBeacon();
?>
