<?php
/**
 * Script to indent large XML files for easier analysis.
 *
 * Usage:
 *   php indent-xml.php <input-path> [output-path] [options]
 *
 * Arguments:
 *   input-path   - File or folder to process
 *   output-path  - Output file/folder (optional)
 *
 * Options:
 *   -r, --recursive  - Process folders recursively
 *   -q, --quiet      - Suppress progress output
 *   --replace        - Replace original files (cannot be used with output-path)
 *
 * Examples:
 *   php indent-xml.php file.xml
 *   php indent-xml.php file.xml output.xml
 *   php indent-xml.php file.xml --replace
 *   php indent-xml.php /path/to/xmls
 *   php indent-xml.php /path/to/xmls /path/to/output
 *   php indent-xml.php /path/to/xmls --recursive
 *   php indent-xml.php /path/to/xmls --recursive --replace
 */

declare(strict_types=1);

use Mistralys\X4\Utilities\XMLIndenter;

require_once __DIR__ . '/prepend.php';

// Parse arguments
$args = array_slice($argv, 1);
$inputPath = null;
$outputPath = null;
$recursive = false;
$verbose = true;
$replaceOriginal = false;

foreach($args as $arg) {
    if($arg === '-r' || $arg === '--recursive') {
        $recursive = true;
    } elseif($arg === '-q' || $arg === '--quiet') {
        $verbose = false;
    } elseif($arg === '--replace') {
        $replaceOriginal = true;
    } elseif($inputPath === null) {
        $inputPath = $arg;
    } elseif($outputPath === null) {
        $outputPath = $arg;
    }
}

// Validate arguments
if($replaceOriginal && $outputPath !== null) {
    echo "Error: --replace cannot be used with a custom output path\n";
    exit(1);
}

if($inputPath === null) {
    echo "XML Indenter - Formats XML files for easier analysis\n";
    echo str_repeat('=', 70) . "\n\n";
    echo "Usage: php indent-xml.php <input-path> [output-path] [options]\n\n";
    echo "Arguments:\n";
    echo "  input-path   File or folder containing XML files\n";
    echo "  output-path  Output file or folder (optional)\n\n";
    echo "Options:\n";
    echo "  -r, --recursive  Process folders recursively\n";
    echo "  -q, --quiet      Suppress progress output\n";
    echo "  --replace        Replace original files (cannot be used with output-path)\n\n";
    echo "Examples:\n";
    echo "  php indent-xml.php file.xml\n";
    echo "  php indent-xml.php file.xml output.xml\n";
    echo "  php indent-xml.php file.xml --replace\n";
    echo "  php indent-xml.php /path/to/xmls\n";
    echo "  php indent-xml.php /path/to/xmls /path/to/output\n";
    echo "  php indent-xml.php /path/to/xmls --recursive\n";
    echo "  php indent-xml.php /path/to/xmls --recursive --replace\n";
    exit(1);
}

try {
    // Check if input is a directory
    if(is_dir($inputPath)) {
        // Process folder
        if($verbose) {
            echo "Processing folder: $inputPath\n";
            if($recursive) {
                echo "Mode: Recursive\n";
            }
            if($outputPath !== null) {
                echo "Output folder: $outputPath\n";
            }
            echo "\n";
        }

        $results = XMLIndenter::indentFolder(
            $inputPath,
            $verbose,
            $recursive,
            $outputPath,
            $replaceOriginal
        );

        if(!$verbose) {
            echo "Processed: {$results['processed']}, Failed: {$results['failed']}\n";
        }

    } else {
        // Process single file
        if(!file_exists($inputPath)) {
            throw new RuntimeException("File not found: $inputPath");
        }

        $indenter = new XMLIndenter($inputPath);
        $indenter->setVerbose($verbose);

        // Set replace mode if specified
        if($replaceOriginal) {
            $indenter->setReplaceOriginal(true);
        }
        // Set custom output file if specified
        elseif($outputPath !== null) {
            $indenter->setOutputFile($outputPath);
        }

        $indenter->indent();
    }

} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

