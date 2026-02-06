<?php
/**
 * @package X4SaveViewer
 * @subpackage CLI\JMESPath
 * @see \Mistralys\X4\SaveViewer\CLI\JMESPath\CustomFnDispatcher
 */

declare(strict_types=1);

namespace Mistralys\X4\SaveViewer\CLI\JMESPath;

use JmesPath\FnDispatcher;

/**
 * Custom JMESPath function dispatcher with additional string functions
 * for case-insensitive searching and text manipulation.
 *
 * Uses composition to wrap the standard FnDispatcher and add custom functions.
 *
 * @package X4SaveViewer
 * @subpackage CLI\JMESPath
 * @author Sebastian Mordziol <s.mordziol@mistralys.eu>
 */
class CustomFnDispatcher
{
    private FnDispatcher $standardDispatcher;

    public function __construct()
    {
        $this->standardDispatcher = FnDispatcher::getInstance();
    }

    /**
     * Dispatch function calls to either custom or standard functions.
     *
     * @param string $fn Function name
     * @param array $args Function arguments
     * @return mixed
     */
    public function __invoke($fn, array $args)
    {
        $method = 'fn_' . $fn;

        // Check if we have a custom function
        if (method_exists($this, $method)) {
            return $this->$method($args);
        }

        // Otherwise delegate to standard dispatcher
        return ($this->standardDispatcher)($fn, $args);
    }

    /**
     * Convert a string to lowercase.
     *
     * Example: to_lower('ARGON') => 'argon'
     *
     * @param array $args Single argument: string to convert
     * @return string Lowercase string
     */
    private function fn_to_lower(array $args): string
    {
        return mb_strtolower($args[0], 'UTF-8');
    }

    /**
     * Convert a string to uppercase.
     *
     * Example: to_upper('argon') => 'ARGON'
     *
     * @param array $args Single argument: string to convert
     * @return string Uppercase string
     */
    private function fn_to_upper(array $args): string
    {
        return mb_strtoupper($args[0], 'UTF-8');
    }

    /**
     * Remove leading and trailing whitespace from a string.
     *
     * Example: trim('  Scout  ') => 'Scout'
     *
     * @param array $args Single argument: string to trim
     * @return string Trimmed string
     */
    private function fn_trim(array $args): string
    {
        return trim($args[0]);
    }

    /**
     * Case-insensitive string contains check.
     *
     * Example: contains_i('Argon Scout', 'SCOUT') => true
     *
     * @param array $args Two arguments: [haystack, needle]
     * @return bool True if haystack contains needle (case-insensitive)
     */
    private function fn_contains_i(array $args): bool
    {
        $haystack = mb_strtolower($args[0], 'UTF-8');
        $needle = mb_strtolower($args[1], 'UTF-8');
        return mb_strpos($haystack, $needle, 0, 'UTF-8') !== false;
    }

    /**
     * Case-insensitive string starts with check.
     *
     * Example: starts_with_i('Argon Scout', 'argon') => true
     *
     * @param array $args Two arguments: [string, prefix]
     * @return bool True if string starts with prefix (case-insensitive)
     */
    private function fn_starts_with_i(array $args): bool
    {
        $string = mb_strtolower($args[0], 'UTF-8');
        $prefix = mb_strtolower($args[1], 'UTF-8');

        if ($prefix === '') {
            return true;
        }

        return mb_substr($string, 0, mb_strlen($prefix, 'UTF-8'), 'UTF-8') === $prefix;
    }

    /**
     * Case-insensitive string ends with check.
     *
     * Example: ends_with_i('Argon Scout', 'SCOUT') => true
     *
     * @param array $args Two arguments: [string, suffix]
     * @return bool True if string ends with suffix (case-insensitive)
     */
    private function fn_ends_with_i(array $args): bool
    {
        $string = mb_strtolower($args[0], 'UTF-8');
        $suffix = mb_strtolower($args[1], 'UTF-8');

        if ($suffix === '') {
            return true;
        }

        return mb_substr($string, -mb_strlen($suffix, 'UTF-8'), null, 'UTF-8') === $suffix;
    }
}
