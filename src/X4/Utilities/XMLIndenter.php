<?php
/**
 * Utility class to indent large XML files for easier analysis.
 * Uses XMLReader for memory-efficient streaming of large files.
 *
 * @package Mistralys\X4\Utilities
 */

declare(strict_types=1);

namespace Mistralys\X4\Utilities;

use AppUtils\ConvertHelper;
use RuntimeException;
use XMLReader;

class XMLIndenter
{
    private string $inputFile;
    private string $outputFile;
    private int $lineCount = 0;
    private float $startTime = 0;
    private bool $verbose = false;
    private int $progressInterval = 10000;
    private bool $replaceOriginal = false;

    public function __construct(string $inputFile)
    {
        if(!file_exists($inputFile)) {
            throw new RuntimeException("Input file not found: $inputFile");
        }

        $this->inputFile = $inputFile;
        $this->outputFile = $this->generateOutputFilePath($inputFile);
    }

    /**
     * Set the output file path.
     *
     * @param string $outputFile
     * @return $this
     */
    public function setOutputFile(string $outputFile): self
    {
        $this->outputFile = $outputFile;
        return $this;
    }

    /**
     * Get the output file path.
     *
     * @return string
     */
    public function getOutputFile(): string
    {
        return $this->outputFile;
    }

    /**
     * Enable or disable verbose output.
     *
     * @param bool $verbose
     * @return $this
     */
    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;
        return $this;
    }

    /**
     * Set the interval for progress updates (number of elements between updates).
     *
     * @param int $interval
     * @return $this
     */
    public function setProgressInterval(int $interval): self
    {
        $this->progressInterval = $interval;
        return $this;
    }

    /**
     * Replace the original file instead of creating a new one.
     * The file will be replaced atomically after successful processing.
     *
     * @param bool $replace
     * @return $this
     */
    public function setReplaceOriginal(bool $replace): self
    {
        $this->replaceOriginal = $replace;
        if($replace) {
            // Use a temporary file for output when replacing
            $this->outputFile = $this->inputFile . '.tmp';
        } else {
            // Reset to default output path
            $this->outputFile = $this->generateOutputFilePath($this->inputFile);
        }
        return $this;
    }

    /**
     * Generate a default output file path based on the input file.
     *
     * @param string $inputFile
     * @return string
     */
    private function generateOutputFilePath(string $inputFile): string
    {
        $pathInfo = pathinfo($inputFile);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-indented.' . $pathInfo['extension'];
    }

    /**
     * Indent the XML file and save to the output file.
     *
     * @return void
     * @throws RuntimeException
     */
    public function indent(): void
    {
        $this->startTime = microtime(true);
        $this->lineCount = 0;

        if($this->verbose) {
            $this->printHeader();
        }

        try {
            $this->processXML();

            // If replacing original, move temp file to original location
            if($this->replaceOriginal) {
                if(!rename($this->outputFile, $this->inputFile)) {
                    throw new RuntimeException("Failed to replace original file: {$this->inputFile}");
                }
                $this->outputFile = $this->inputFile; // Update for summary display
            }

            if($this->verbose) {
                $this->printSummary();
            }
        } catch(RuntimeException $e) {
            // Clean up temp file if it exists
            if($this->replaceOriginal && file_exists($this->outputFile)) {
                @unlink($this->outputFile);
            }
            throw $e;
        } catch(\Exception $e) {
            // Clean up temp file if it exists
            if($this->replaceOriginal && file_exists($this->outputFile)) {
                @unlink($this->outputFile);
            }
            throw new RuntimeException("Error processing XML: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Print information header.
     *
     * @return void
     */
    private function printHeader(): void
    {
        echo "Indenting XML file...\n";
        echo "Input:  {$this->inputFile}\n";
        echo "Output: {$this->outputFile}\n";
        echo "Size:   " . ConvertHelper::bytes2readable(filesize($this->inputFile)) . "\n";
        echo "\n";
    }

    /**
     * Print processing summary.
     *
     * @return void
     */
    private function printSummary(): void
    {
        $elapsed = microtime(true) - $this->startTime;

        echo "\n";
        echo "Done!\n";
        echo "Processed {$this->lineCount} elements in " . number_format($elapsed, 2) . " seconds\n";
        echo "Output file: {$this->outputFile}\n";
        echo "Output size: " . ConvertHelper::bytes2readable(filesize($this->outputFile)) . "\n";
    }

    /**
     * Process the XML file using XMLReader for streaming.
     *
     * @return void
     * @throws RuntimeException
     */
    private function processXML(): void
    {
        $reader = new XMLReader();
        $reader->open($this->inputFile);

        $output = fopen($this->outputFile, 'w');
        if($output === false) {
            throw new RuntimeException("Cannot open output file: {$this->outputFile}");
        }

        fwrite($output, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);

        $depth = 0;
        $lastNodeType = null;
        $needsNewline = false;

        while($reader->read()) {
            $nodeType = $reader->nodeType;

            switch($nodeType) {
                case XMLReader::ELEMENT:
                    $this->handleElement($reader, $output, $depth, $lastNodeType, $needsNewline);
                    break;

                case XMLReader::END_ELEMENT:
                    $this->handleEndElement($reader, $output, $depth, $lastNodeType, $needsNewline);
                    break;

                case XMLReader::TEXT:
                    $this->handleText($reader, $output, $needsNewline);
                    break;

                case XMLReader::CDATA:
                    $this->handleCData($reader, $output, $needsNewline);
                    break;

                case XMLReader::COMMENT:
                    $this->handleComment($reader, $output, $depth, $needsNewline);
                    break;
            }

            $lastNodeType = $nodeType;
        }

        $reader->close();
        fclose($output);
    }

    /**
     * Handle an XML element node.
     *
     * @param XMLReader $reader
     * @param resource $output
     * @param int $depth
     * @param int|null $lastNodeType
     * @param bool $needsNewline
     * @return void
     */
    private function handleElement(XMLReader $reader, $output, int &$depth, ?int $lastNodeType, bool &$needsNewline): void
    {
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

        $this->lineCount++;
        if($this->verbose && $this->lineCount % $this->progressInterval === 0) {
            echo "Processed {$this->lineCount} elements...\r";
        }
    }

    /**
     * Handle an XML end element node.
     *
     * @param XMLReader $reader
     * @param resource $output
     * @param int $depth
     * @param int|null $lastNodeType
     * @param bool $needsNewline
     * @return void
     */
    private function handleEndElement(XMLReader $reader, $output, int &$depth, ?int $lastNodeType, bool &$needsNewline): void
    {
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
    }

    /**
     * Handle text content.
     *
     * @param XMLReader $reader
     * @param resource $output
     * @param bool $needsNewline
     * @return void
     */
    private function handleText(XMLReader $reader, $output, bool &$needsNewline): void
    {
        $text = trim($reader->value);
        if($text !== '') {
            $needsNewline = false; // Text content means no newline needed
            fwrite($output, htmlspecialchars($text, ENT_QUOTES | ENT_XML1));
        }
    }

    /**
     * Handle CDATA section.
     *
     * @param XMLReader $reader
     * @param resource $output
     * @param bool $needsNewline
     * @return void
     */
    private function handleCData(XMLReader $reader, $output, bool &$needsNewline): void
    {
        $needsNewline = false; // CDATA means no newline needed
        fwrite($output, '<![CDATA[' . $reader->value . ']]>');
    }

    /**
     * Handle XML comment.
     *
     * @param XMLReader $reader
     * @param resource $output
     * @param int $depth
     * @param bool $needsNewline
     * @return void
     */
    private function handleComment(XMLReader $reader, $output, int $depth, bool &$needsNewline): void
    {
        if($needsNewline) {
            fwrite($output, PHP_EOL);
            $needsNewline = false;
        }
        fwrite($output, str_repeat('  ', $depth));
        fwrite($output, '<!-- ' . $reader->value . ' -->' . PHP_EOL);
    }

    /**
     * Get the number of elements processed during indentation.
     *
     * @return int
     */
    public function getLineCount(): int
    {
        return $this->lineCount;
    }

    /**
     * Get the elapsed time for the indentation process.
     *
     * @return float
     */
    public function getElapsedTime(): float
    {
        return microtime(true) - $this->startTime;
    }

    /**
     * Process all XML files in a directory.
     *
     * @param string $directory The directory to scan for XML files
     * @param bool $verbose Enable verbose output
     * @param bool $recursive Process subdirectories recursively
     * @param string|null $outputDirectory Optional custom output directory (defaults to same as input)
     * @param bool $replaceOriginal Replace original files instead of creating new ones
     * @return array{processed: int, failed: int, skipped: int, files: array<string, string>} Processing results
     * @throws RuntimeException
     */
    public static function indentFolder(
        string $directory,
        bool $verbose = false,
        bool $recursive = false,
        ?string $outputDirectory = null,
        bool $replaceOriginal = false
    ): array
    {
        if(!is_dir($directory)) {
            throw new RuntimeException("Directory not found: $directory");
        }

        if($replaceOriginal && $outputDirectory !== null) {
            throw new RuntimeException("Cannot use replaceOriginal with a custom output directory");
        }

        if($outputDirectory !== null && !is_dir($outputDirectory) && !mkdir($outputDirectory, 0755, true)) {
            throw new RuntimeException("Failed to create output directory: $outputDirectory");
        }

        $results = [
            'processed' => 0,
            'failed' => 0,
            'skipped' => 0,
            'files' => []
        ];

        $pattern = $recursive ? '**/*.xml' : '*.xml';
        $xmlFiles = self::findXMLFiles($directory, $recursive);

        if(empty($xmlFiles)) {
            if($verbose) {
                echo "No XML files found in: $directory\n";
            }
            return $results;
        }

        if($verbose) {
            echo "Found " . count($xmlFiles) . " XML file(s) to process\n";
            echo str_repeat('=', 70) . "\n\n";
        }

        foreach($xmlFiles as $index => $xmlFile) {
            $fileNumber = $index + 1;
            $totalFiles = count($xmlFiles);

            if($verbose) {
                echo "[$fileNumber/$totalFiles] Processing: " . basename($xmlFile) . "\n";
            }

            try {
                $indenter = new self($xmlFile);
                $indenter->setVerbose(false); // Don't show individual file progress in batch mode

                // Set replace mode if specified
                if($replaceOriginal) {
                    $indenter->setReplaceOriginal(true);
                }
                // Set output directory if specified
                elseif($outputDirectory !== null) {
                    $relativePath = self::getRelativePath($directory, $xmlFile);
                    $outputFile = $outputDirectory . DIRECTORY_SEPARATOR . $relativePath;

                    // Create subdirectories if needed
                    $outputDir = dirname($outputFile);
                    if(!is_dir($outputDir) && !mkdir($outputDir, 0755, true)) {
                        throw new RuntimeException("Failed to create directory: $outputDir");
                    }

                    $indenter->setOutputFile($outputFile);
                }

                $indenter->indent();
                $results['processed']++;
                $results['files'][$xmlFile] = $indenter->getOutputFile();

                if($verbose) {
                    echo "  ✓ Success: " . $indenter->getLineCount() . " elements, "
                        . number_format($indenter->getElapsedTime(), 2) . "s\n";
                    echo "  → Output: " . $indenter->getOutputFile() . "\n\n";
                }

            } catch(\Exception $e) {
                $results['failed']++;
                if($verbose) {
                    echo "  ✗ Failed: " . $e->getMessage() . "\n\n";
                }
            }
        }

        if($verbose) {
            echo str_repeat('=', 70) . "\n";
            echo "Batch processing complete!\n";
            echo "Processed: {$results['processed']}\n";
            echo "Failed: {$results['failed']}\n";
        }

        return $results;
    }

    /**
     * Find all XML files in a directory.
     *
     * @param string $directory
     * @param bool $recursive
     * @return array<string>
     */
    private static function findXMLFiles(string $directory, bool $recursive): array
    {
        $xmlFiles = [];
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        if($recursive) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            $iterator = new \DirectoryIterator($directory);
        }

        foreach($iterator as $file) {
            if($file->isFile() && strtolower($file->getExtension()) === 'xml') {
                // Skip already indented files
                if(!str_contains($file->getFilename(), '-indented.xml')) {
                    $xmlFiles[] = $file->getPathname();
                }
            }
        }

        sort($xmlFiles);
        return $xmlFiles;
    }

    /**
     * Get relative path from base directory to file.
     *
     * @param string $baseDir
     * @param string $filePath
     * @return string
     */
    private static function getRelativePath(string $baseDir, string $filePath): string
    {
        $baseDir = rtrim(str_replace('\\', '/', realpath($baseDir)), '/');
        $filePath = str_replace('\\', '/', realpath($filePath));

        if(str_starts_with($filePath, $baseDir)) {
            return ltrim(substr($filePath, strlen($baseDir)), '/');
        }

        return basename($filePath);
    }
}
