# conQuer
conQuer: Genomic Data Management and Comparison Tool

**conQuer** is a versatile tool for genomic data processing developed by <b>VS-Lab at [CSIR-Insitute of Genomics an Integrative Biology](https://www.igib.res.in/)</b>, to help researchers with the conversion of VCF files into the Beacon API format, querying Beacon APIs, and finding common variants between two Beacon API files. The tool is modular, with three main components:

1. **VCF to Beacon API Conversion**: Converts genomic data in the VCF format into the Beacon API format.
2. **Beacon API Querying**: Allows users to query one or more Beacon APIs to retrieve genomic variants.
3. **Common Variant Finder**: Compares two Beacon API files and finds common variants between them.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
  - [VCF to Beacon API Conversion](#vcf-to-beacon-api-conversion)
  - [Querying Beacon APIs](#querying-beacon-apis)
  - [Finding Common Variants](#finding-common-variants)
- [Directory Structure](#directory-structure)
- [Configuration](#configuration)

## Installation

Follow these steps to install and set up **conQuer** on your system:

1. **Clone the Repository**:
   Clone the **conQuer** repository from GitHub:

   ```bash
   git clone https://github.com/yourusername/conQuer.git
   cd conQuer
   ```

2. **Install PHP**:
   **conQuer** requires PHP to run. If you do not have PHP installed, follow the installation instructions below based on your system:

   ### For Linux (Ubuntu/Debian):
   Run the following commands to install PHP:

   ```bash
   sudo apt update
   sudo apt install php php-cli php-json php-mbstring
   ```

   ### For macOS (using Homebrew):
   If you have Homebrew installed, you can install PHP with:

   ```bash
   brew install php
   ```

   ### For Windows:
   Download and install PHP from [php.net](https://www.php.net/downloads.php). Make sure to add PHP to your system's PATH variable.

3. **Set PHP Configuration**:
   **conQuer** may require increased PHP execution time and memory. To adjust the PHP settings:

   - Open your `php.ini` file (usually found in `/etc/php/{version}/cli/php.ini` on Linux or `C:\xampp\php\php.ini` on Windows).
   - Modify the following settings to ensure that PHP can handle large VCF files and lengthy processes:

   ```ini
   max_execution_time = 1000  ; Set execution time to 5 minutes
   memory_limit = 5G     ; Set memory limit to 512 MB or more if necessary
   ```

   After making these changes, restart your web server or PHP CLI.

4. **Install Composer (optional)**:
   If your system doesn't already have Composer (a PHP dependency manager), you can install it globally:

   ```bash
   curl -sS https://getcomposer.org/installer | php
   mv composer.phar /usr/local/bin/composer
   ```

## Usage

### VCF to Beacon API Conversion

To convert VCF files to Beacon API format:

1. Upload your VCF files into the `vcf/` folder inside the **conQuer** directory. You can upload multiple VCF files at once.

2. Run the following command to convert the VCF files:

   ```bash
   php vcf2beacon.php
   ```

   This will generate Beacon API-compliant JSON files, which will be stored in the `beacon/` folder.

### Querying Beacon APIs

You can query Beacon APIs to retrieve genomic variants using the following formats:

#### Querying a Single Beacon API
To query a specific Beacon API, you can specify the Beacon ID (e.g., `beacon1`). The request URL format is:

```bash
http://localhost/conQuer/queryBeacon.php?chromosome=MT&position=10&referenceBases=T&alternateBases=C&beaconId=beacon1
```

Here, you specify the **chromosome**, **position**, **referenceBases**, **alternateBases**, and the **beaconId** (e.g., `beacon1`). 

#### Querying All Beacon APIs
If you want to query all available Beacon APIs (without specifying a particular Beacon), simply omit the `beaconId` parameter:

```bash
http://localhost/conQuer/queryBeacon.php?chromosome=MT&position=10&referenceBases=T&alternateBases=C
```

This will query all Beacon APIs specified in the `beaconPaths.php` configuration file.

#### beaconPaths.php Configuration

The `beaconPaths.php` file is responsible for storing the mapping of Beacon IDs to their respective API URLs or file paths. This file is located in the **conQuer** directory. Ensure that it contains the correct Beacon configurations for your setup. Here’s an example of how the file might look:

```php
<?php
return [
    'beacon1' => 'http://localhost/beacon1/api',  // Example Beacon 1 API
    'beacon2' => 'http://localhost/beacon2/api',  // Example Beacon 2 API
    // Add more beacons as needed
];
```

This file allows the tool to dynamically look up Beacon API URLs based on the `beaconId` parameter passed in the query.

### Finding Common Variants

To find common variants between two Beacon API files:

1. Ensure that the Beacon API JSON files are available in the `beacon/` folder.

2. Run the following command to find common variants:

   ```bash
   php compareBeacon.php beacon1 beacon2
   ```

   This will compare the two Beacon API files and output the common variants to a JSON file in the `commonVariants/` folder.

## Directory Structure

Here’s how the directory structure should look:

```
conQuer/
├── vcf2beacon.php              # VCF to Beacon API conversion script
├── queryBeacon.php               # Script to query Beacon APIs
├── compareBeacon.php       # Script to find common variants between two Beacon API files
├── vcf/                           # Folder to store input VCF files
│   ├── input1.vcf
│   ├── input2.vcf
│   └── input3.vcf
├── beacon/                        # Folder to store converted Beacon API files
│   ├── beacon1_converted.json
│   └── beacon2_converted.json
├── commonVariants/                        # Folder to store common variants JSON files
│   ├── common_variants_beacon1_beacon2.json
├── beaconPaths.php                # Configuration file for Beacon paths (URLs)
└── README.md                      # This file
```

## Configuration

1. **VCF Files**: To convert to the Beacon API format, you need to provide one or more VCF files. These files should be placed in the `vcf/` folder. You can upload multiple VCF files at a time.

2. **Beacon API Files**: After converting VCF files to Beacon API format, the resulting Beacon API files will be stored in the `beacon/` folder. You can then use these files for querying and comparing genomic variants.

3. **beaconPaths.php**: This configuration file holds the mapping of Beacon IDs to their respective API URLs. Make sure to update this file with the correct paths or URLs for your Beacon APIs.

## Example Commands

1. **Convert VCF to Beacon API**:

   ```bash
   php vcf2beacon.php
   ```

2. **Query a Single Beacon API**:

   ```bash
   http://localhost/conQuer/queryBeacon.php?chromosome=MT&position=10&referenceBases=T&alternateBases=C&beaconId=beacon1
   ```

3. **Query All Beacon APIs**:

   ```bash
   http://localhost/conQuer/queryBeacon.php?chromosome=MT&position=10&referenceBases=T&alternateBases=C
   ```

4. **Find Common Variants**:

   ```bash
   php compareBeacon.php beacon1 beacon2
   ```

## Conclusion

The **conQuer** tool is designed to streamline the process of working with genomic data, particularly for researchers working with VCF files and Beacon APIs. With three key components—VCF to Beacon API conversion, querying Beacon APIs, and finding common variants—it offers a complete solution for processing and analyzing genomic variants.
