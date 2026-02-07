# XMLIndenter Class Documentation

## Overview

The `XMLIndenter` class provides functionality to indent XML files for easier analysis and readability. It uses `XMLReader` for memory-efficient streaming, making it suitable for processing very large XML files.

## Location

- **Class**: `Mistralys\X4\Utilities\XMLIndenter`
- **File**: `/src/X4/Utilities/XMLIndenter.php`
- **CLI Script**: `/bin/php/indent-xml.php`

## Features

- ✅ Memory-efficient streaming (handles large files)
- ✅ Single file processing
- ✅ Batch folder processing
- ✅ Recursive folder processing
- ✅ Custom output paths
- ✅ Progress tracking with verbose mode
- ✅ Quiet mode for minimal output
- ✅ Skips already-indented files (files containing `-indented.xml`)
- ✅ Handles all XML node types (elements, text, CDATA, comments, attributes)

## CLI Usage

### Basic Usage

```bash
# Process a single file
php bin/php/indent-xml.php input.xml

# Process a single file with custom output
php bin/php/indent-xml.php input.xml output.xml

# Replace the original file in-place
php bin/php/indent-xml.php input.xml --replace

# Process all XML files in a folder
php bin/php/indent-xml.php /path/to/xmls

# Process folder with custom output directory
php bin/php/indent-xml.php /path/to/xmls /path/to/output

# Process folder recursively
php bin/php/indent-xml.php /path/to/xmls --recursive

# Replace all files in a folder recursively
php bin/php/indent-xml.php /path/to/xmls --recursive --replace

# Quiet mode (minimal output)
php bin/php/indent-xml.php /path/to/xmls --quiet
```

### Options

- `-r, --recursive` - Process subdirectories recursively
- `-q, --quiet` - Suppress progress output (shows only summary)
- `--replace` - Replace original files in-place (cannot be used with output path)

## Programmatic Usage

### Single File Processing

```php
use Mistralys\X4\Utilities\XMLIndenter;

// Basic usage
$indenter = new XMLIndenter('input.xml');
$indenter->indent();

// With custom output and verbose mode
$indenter = new XMLIndenter('input.xml');
$indenter->setOutputFile('output.xml')
         ->setVerbose(true)
         ->setProgressInterval(5000)
         ->indent();

// Replace the original file in-place
$indenter = new XMLIndenter('input.xml');
$indenter->setReplaceOriginal(true)
         ->setVerbose(true)
         ->indent();

// Get processing statistics
echo "Processed " . $indenter->getLineCount() . " elements\n";
echo "Time: " . $indenter->getElapsedTime() . " seconds\n";
```

### Batch Folder Processing

```php
use Mistralys\X4\Utilities\XMLIndenter;

// Process all XML files in a folder
$results = XMLIndenter::indentFolder(
    directory: '/path/to/xmls',
    verbose: true,
    recursive: false,
    outputDirectory: null,  // Optional: custom output directory
    replaceOriginal: false
);

// Replace all original files in-place
$results = XMLIndenter::indentFolder(
    directory: '/path/to/xmls',
    verbose: true,
    recursive: true,
    outputDirectory: null,
    replaceOriginal: true
);

// Check results
echo "Processed: {$results['processed']}\n";
echo "Failed: {$results['failed']}\n";
echo "Files:\n";
foreach ($results['files'] as $input => $output) {
    echo "  $input -> $output\n";
}
```

### Recursive Processing with Custom Output

```php
$results = XMLIndenter::indentFolder(
    directory: '/path/to/xmls',
    verbose: true,
    recursive: true,
    outputDirectory: '/path/to/output'
);
```

## API Reference

### Instance Methods

#### `__construct(string $inputFile)`
Create a new indenter instance for a specific file.

**Throws**: `RuntimeException` if file not found

#### `setOutputFile(string $outputFile): self`
Set custom output file path.

#### `setVerbose(bool $verbose): self`
Enable or disable verbose progress output.

#### `setProgressInterval(int $interval): self`
Set how often progress updates are shown (default: 10000 elements).

#### `setReplaceOriginal(bool $replace): self`
Replace the original file instead of creating a new one. The file will be replaced atomically after successful processing using a temporary file.

**Note**: Cannot be used together with `setOutputFile()`.

#### `indent(): void`
Process the XML file and write indented output.

**Throws**: `RuntimeException` on processing errors

#### `getOutputFile(): string`
Get the output file path.

#### `getLineCount(): int`
Get number of elements processed.

#### `getElapsedTime(): float`
Get processing time in seconds.

### Static Methods

#### `indentFolder(string $directory, bool $verbose, bool $recursive, ?string $outputDirectory, bool $replaceOriginal): array`
Process all XML files in a directory.

**Parameters**:
- `$directory` - The directory to scan for XML files
- `$verbose` - Enable verbose progress output
- `$recursive` - Process subdirectories recursively
- `$outputDirectory` - Optional custom output directory (defaults to same as input)
- `$replaceOriginal` - Replace original files instead of creating new ones

**Returns**: Array with keys:
- `processed` (int) - Number of successfully processed files
- `failed` (int) - Number of failed files
- `skipped` (int) - Number of skipped files
- `files` (array) - Map of input file => output file paths

**Throws**: `RuntimeException` if directory not found or cannot create output directory

## Output Format

- Properly indented with 2 spaces per level
- Preserves XML declaration
- Preserves all attributes
- Preserves text content, CDATA sections, and comments
- Self-closing tags for empty elements
- UTF-8 encoding

## Examples

### Example 1: Indent All Save Files

```php
$results = XMLIndenter::indentFolder(
    directory: 'F:/Games/X4/saves',
    verbose: true,
    recursive: false
);
```

### Example 2: Process with Error Handling

```php
try {
    $indenter = new XMLIndenter('large-file.xml');
    $indenter->setVerbose(true)
             ->indent();
    
    echo "Success! Processed {$indenter->getLineCount()} elements\n";
} catch (RuntimeException $e) {
    echo "Error: {$e->getMessage()}\n";
}
```

### Example 3: Batch Process with Custom Output

```php
// Create output directory if it doesn't exist
$outputDir = __DIR__ . '/indented-files';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Process all XML files
$results = XMLIndenter::indentFolder(
    directory: __DIR__ . '/xml-files',
    verbose: false,
    recursive: true,
    outputDirectory: $outputDir
);

// Report results
echo "Batch processing complete!\n";
echo "Successfully processed: {$results['processed']}\n";
if ($results['failed'] > 0) {
    echo "Failed: {$results['failed']}\n";
}
```

## Notes

- Files named with `-indented.xml` are automatically skipped during batch processing
- When no output path is specified, creates files with `-indented` suffix
- Directory structure is preserved when using custom output directory with recursive mode
- The class uses streaming for memory efficiency - suitable for files of any size
- **Atomic replacement**: When using `--replace`, files are processed to a temporary file first, then atomically replaced on success. If processing fails, the original file remains unchanged and the temporary file is cleaned up.

