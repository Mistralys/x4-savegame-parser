<?php
/**
 * Script to indent large XML files for easier analysis.
 *
 * Usage: php indent-xml.php <input-file> [output-file]
 *
 * If output-file is not specified, it will create one with "-indented" suffix.
 */

declare(strict_types=1);

use AppUtils\FileHelper;

require_once __DIR__ . '/prepend.php';

if($argc < 2) {
    echo "Usage: php indent-xml.php <input-file> [output-file]\n";
    echo "\n";
    echo "Indents a large XML file for easier analysis.\n";
    echo "If output-file is not specified, creates <input-file>-indented.xml\n";
    exit(1);
}

$inputFile = $argv[1];

if(!file_exists($inputFile)) {
    echo "Error: Input file not found: $inputFile\n";
    exit(1);
}

// Determine output file
if(isset($argv[2])) {
    $outputFile = $argv[2];
} else {
    $pathInfo = pathinfo($inputFile);
    $outputFile = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-indented.' . $pathInfo['extension'];
}

echo "Indenting XML file...\n";
echo "Input:  $inputFile\n";
echo "Output: $outputFile\n";
echo "Size:   " . \AppUtils\ConvertHelper::bytes2readable(FileHelper::getFileInfo($inputFile)->getSize()) . "\n";
echo "\n";

$startTime = microtime(true);

try {
    // Use XMLReader for memory-efficient streaming
    $reader = new XMLReader();
    $reader->open($inputFile);

    $output = fopen($outputFile, 'w');
    if($output === false) {
        throw new RuntimeException("Cannot open output file: $outputFile");
    }

    fwrite($output, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);

    $depth = 0;
    $lastNodeType = null;
    $needsNewline = false;
    $lineCount = 0;

    while($reader->read()) {
        $nodeType = $reader->nodeType;

        switch($nodeType) {
            case XMLReader::ELEMENT:
                // Add newline if the previous element needs it
                if($needsNewline) {
                    fwrite($output, PHP_EOL);
                    $needsNewline = false;
                }

                // Write indentation (except after text content)
                if($lastNodeType !== XMLReader::TEXT && $lastNodeType !== XMLReader::CDATA) {
                    fwrite($output, str_repeat('  ', $depth));
                }

                // Write opening tag
                $tag = '<' . $reader->name;

                // Add attributes
                if($reader->hasAttributes) {
                    while($reader->moveToNextAttribute()) {
                        $tag .= ' ' . $reader->name . '="' . htmlspecialchars($reader->value, ENT_QUOTES | ENT_XML1) . '"';
                    }
                    $reader->moveToElement();
                }

                // Self-closing or opening tag
                if($reader->isEmptyElement) {
                    $tag .= ' />';
                    fwrite($output, $tag . PHP_EOL);
                } else {
                    $tag .= '>';
                    fwrite($output, $tag);
                    $depth++;
                    $needsNewline = true; // Signal that we may need a newline before next element
                }

                $lineCount++;
                if($lineCount % 10000 === 0) {
                    echo "Processed $lineCount elements...\r";
                }
                break;

            case XMLReader::END_ELEMENT:
                $depth--;

                // Add newline if needed before closing tag
                if($needsNewline) {
                    fwrite($output, PHP_EOL);
                    $needsNewline = false;
                }

                // Add indentation if previous wasn't text content
                if($lastNodeType !== XMLReader::TEXT && $lastNodeType !== XMLReader::CDATA) {
                    fwrite($output, str_repeat('  ', $depth));
                }

                fwrite($output, '</' . $reader->name . '>' . PHP_EOL);
                break;

            case XMLReader::TEXT:
                $text = trim($reader->value);
                if($text !== '') {
                    $needsNewline = false; // Text content means no newline needed
                    fwrite($output, htmlspecialchars($text, ENT_QUOTES | ENT_XML1));
                }
                break;

            case XMLReader::CDATA:
                $needsNewline = false; // CDATA means no newline needed
                fwrite($output, '<![CDATA[' . $reader->value . ']]>');
                break;

            case XMLReader::COMMENT:
                if($needsNewline) {
                    fwrite($output, PHP_EOL);
                    $needsNewline = false;
                }
                fwrite($output, str_repeat('  ', $depth));
                fwrite($output, '<!-- ' . $reader->value . ' -->' . PHP_EOL);
                break;
        }

        $lastNodeType = $nodeType;
    }

    $reader->close();
    fclose($output);

    $elapsed = microtime(true) - $startTime;

    echo "\n";
    echo "Done!\n";
    echo "Processed $lineCount elements in " . number_format($elapsed, 2) . " seconds\n";
    echo "Output file: $outputFile\n";
    echo "Output size: " . FileHelper::getFileSizeReadable($outputFile) . "\n";

} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

