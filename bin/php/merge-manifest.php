<?php

declare(strict_types=1);

/**
 * Merges all project manifest documents into a single Markdown file
 * suitable for NotebookLM or other single-file documentation tools.
 *
 * Features:
 * - Dynamically discovers all .md files in the project-manifest directory
 * - Converts file links to jump marks (anchors)
 * - Adds an additional # to all existing headers
 * - Always starts with README.md, followed by other files alphabetically
 * - No manual maintenance required when adding/renaming files
 */

// Standalone script - no dependencies required

const MANIFEST_DIR = __DIR__.'/../../docs/agents/project-manifest';
const OUTPUT_FILE = __DIR__.'/../../docs/X4-Savegame-Parser-Manifest.md';

/**
 * Dynamically discovers all markdown files in the manifest directory.
 * README.md is always first, followed by all other .md files in alphabetical order.
 */
function getDocumentOrder(): array
{
    $files = glob(MANIFEST_DIR . '/*.md');

    if ($files === false) {
        throw new RuntimeException('Failed to read manifest directory: ' . MANIFEST_DIR);
    }

    // Extract just the filenames
    $filenames = array_map('basename', $files);

    // Separate README.md from other files
    $readme = null;
    $others = [];

    foreach ($filenames as $filename) {
        if ($filename === 'README.md') {
            $readme = $filename;
        } else {
            $others[] = $filename;
        }
    }

    // Sort other files alphabetically
    sort($others, SORT_STRING | SORT_FLAG_CASE);

    // README.md first, then others
    $order = [];
    if ($readme !== null) {
        $order[] = $readme;
    }
    $order = array_merge($order, $others);

    return $order;
}

/**
 * Converts a filename to an anchor link
 * Example: "tech-stack-and-patterns.md" -> "tech-stack-and-patterns"
 */
function filenameToAnchor(string $filename): string
{
    return str_replace('.md', '', $filename);
}

/**
 * Converts a header to an anchor
 * Example: "## Tech Stack & Patterns" -> "tech-stack--patterns"
 */
function headerToAnchor(string $header): string
{
    // Remove leading #'s and whitespace
    $text = trim(preg_replace('/^#+\s*/', '', $header));

    // Convert to lowercase and replace spaces/special chars with hyphens
    $anchor = strtolower($text);
    $anchor = preg_replace('/[^\w\s-]/', '', $anchor);
    $anchor = preg_replace('/[\s_]+/', '-', $anchor);
    $anchor = trim($anchor, '-');

    return $anchor;
}

/**
 * Transforms markdown content:
 * - Increases header levels by 1 (add one more #)
 * - Converts file links to anchor links
 */
function transformContent(string $content, string $currentFile): string
{
    $lines = explode("\n", $content);
    $transformed = [];

    foreach ($lines as $line) {
        // Transform headers: add one more #
        if (preg_match('/^(#+)\s+(.+)$/', $line, $matches)) {
            $transformed[] = '#' . $matches[1] . ' ' . $matches[2];
            continue;
        }

        // Transform file links to anchor links
        // Pattern: [text](./filename.md) or [text](filename.md)
        if (preg_match_all('/\[([^\]]+)\]\(\.?\/?([^)]+\.md)(?:#([^)]+))?\)/', $line, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $linkText = $match[1];
                $filename = basename($match[2]);
                $fragment = isset($match[3]) ? $match[3] : '';

                // Convert to anchor link
                $anchor = filenameToAnchor($filename);
                if ($fragment) {
                    $anchor .= '#' . $fragment;
                }

                $oldLink = $match[0];
                $newLink = '[' . $linkText . '](#' . $anchor . ')';
                $line = str_replace($oldLink, $newLink, $line);
            }
        }

        $transformed[] = $line;
    }

    return implode("\n", $transformed);
}

/**
 * Main merge function
 */
function mergeManifest(): void
{
    echo "Merging project manifest documents...\n";
    echo "Output file: " . OUTPUT_FILE . "\n\n";

    // Dynamically discover documents
    $documentOrder = getDocumentOrder();

    echo "Found " . count($documentOrder) . " document(s) to merge:\n";
    foreach ($documentOrder as $filename) {
        echo "  - $filename\n";
    }
    echo "\n";

    $mergedContent = [];

    // Add header
    $mergedContent[] = "# X4 Savegame Monitor & Viewer - Complete Project Manifest";
    $mergedContent[] = "";
    $mergedContent[] = "> **Generated**: " . date('Y-m-d H:i:s');
    $mergedContent[] = "> ";
    $mergedContent[] = "> This is a merged version of all project manifest documents,";
    $mergedContent[] = "> optimized for single-file documentation tools like NotebookLM.";
    $mergedContent[] = "";
    $mergedContent[] = "---";
    $mergedContent[] = "";

    // Process each document in order
    foreach ($documentOrder as $filename) {
        $filepath = MANIFEST_DIR . '/' . $filename;

        if (!file_exists($filepath)) {
            echo "Warning: File not found: $filename\n";
            continue;
        }

        echo "Processing: $filename\n";

        $content = file_get_contents($filepath);

        // Add document separator and anchor
        $anchor = filenameToAnchor($filename);
        $mergedContent[] = "";
        $mergedContent[] = "<a id=\"$anchor\"></a>";
        $mergedContent[] = "";

        // Transform and add content
        $transformed = transformContent($content, $filename);
        $mergedContent[] = $transformed;

        // Add separator between documents
        $mergedContent[] = "";
        $mergedContent[] = "---";
        $mergedContent[] = "";
    }

    // Write merged file
    $finalContent = implode("\n", $mergedContent);
    file_put_contents(OUTPUT_FILE, $finalContent);

    echo "\n✓ Merge complete!\n";
    echo "Output file: " . OUTPUT_FILE . "\n";
    echo "File size: " . number_format(strlen($finalContent)) . " bytes\n";
    echo "Total documents merged: " . count($documentOrder) . "\n";
}

// Run the merge
try {
    mergeManifest();
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}


