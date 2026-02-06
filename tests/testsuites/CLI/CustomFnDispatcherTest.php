<?php
/**
 * Tests for CustomFnDispatcher - JMESPath custom functions
 *
 * These tests verify that custom JMESPath functions work correctly,
 * including case-insensitive searching and string transformations.
 *
 * @package X4SaveViewer
 * @subpackage Tests
 */

declare(strict_types=1);

namespace testsuites\CLI;

use JmesPath\AstRuntime;
use Mistralys\X4\SaveViewer\CLI\JMESPath\CustomFnDispatcher;
use X4\SaveGameParserTests\TestClasses\X4ParserTestCase;

class CustomFnDispatcherTest extends X4ParserTestCase
{
    private AstRuntime $runtime;

    protected function setUp(): void
    {
        parent::setUp();
        $this->runtime = new AstRuntime(null, new CustomFnDispatcher());
    }

    // =========================================================================
    // Test: to_lower()
    // =========================================================================

    public function test_to_lower_basic(): void
    {
        $result = ($this->runtime)('to_lower(@)', 'ARGON');
        $this->assertSame('argon', $result);
    }

    public function test_to_lower_mixed_case(): void
    {
        $result = ($this->runtime)('to_lower(@)', 'ArGoN ScOuT');
        $this->assertSame('argon scout', $result);
    }

    public function test_to_lower_already_lowercase(): void
    {
        $result = ($this->runtime)('to_lower(@)', 'argon');
        $this->assertSame('argon', $result);
    }

    public function test_to_lower_empty_string(): void
    {
        $result = ($this->runtime)('to_lower(@)', '');
        $this->assertSame('', $result);
    }

    public function test_to_lower_utf8(): void
    {
        $result = ($this->runtime)('to_lower(@)', 'ÑOÑO');
        $this->assertSame('ñoño', $result);
    }

    public function test_to_lower_german_umlaut(): void
    {
        $result = ($this->runtime)('to_lower(@)', 'ÜBER');
        $this->assertSame('über', $result);
    }

    // =========================================================================
    // Test: to_upper()
    // =========================================================================

    public function test_to_upper_basic(): void
    {
        $result = ($this->runtime)('to_upper(@)', 'argon');
        $this->assertSame('ARGON', $result);
    }

    public function test_to_upper_mixed_case(): void
    {
        $result = ($this->runtime)('to_upper(@)', 'ArGoN ScOuT');
        $this->assertSame('ARGON SCOUT', $result);
    }

    public function test_to_upper_already_uppercase(): void
    {
        $result = ($this->runtime)('to_upper(@)', 'ARGON');
        $this->assertSame('ARGON', $result);
    }

    public function test_to_upper_empty_string(): void
    {
        $result = ($this->runtime)('to_upper(@)', '');
        $this->assertSame('', $result);
    }

    public function test_to_upper_utf8(): void
    {
        $result = ($this->runtime)('to_upper(@)', 'ñoño');
        $this->assertSame('ÑOÑO', $result);
    }

    public function test_to_upper_german_umlaut(): void
    {
        $result = ($this->runtime)('to_upper(@)', 'über');
        $this->assertSame('ÜBER', $result);
    }

    // =========================================================================
    // Test: trim()
    // =========================================================================

    public function test_trim_leading_spaces(): void
    {
        $result = ($this->runtime)('trim(@)', '  Scout');
        $this->assertSame('Scout', $result);
    }

    public function test_trim_trailing_spaces(): void
    {
        $result = ($this->runtime)('trim(@)', 'Scout  ');
        $this->assertSame('Scout', $result);
    }

    public function test_trim_both_spaces(): void
    {
        $result = ($this->runtime)('trim(@)', '  Scout  ');
        $this->assertSame('Scout', $result);
    }

    public function test_trim_tabs(): void
    {
        $result = ($this->runtime)('trim(@)', "\t\tScout\t\t");
        $this->assertSame('Scout', $result);
    }

    public function test_trim_newlines(): void
    {
        $result = ($this->runtime)('trim(@)', "\n\nScout\n\n");
        $this->assertSame('Scout', $result);
    }

    public function test_trim_mixed_whitespace(): void
    {
        $result = ($this->runtime)('trim(@)', " \t\n Scout \n\t ");
        $this->assertSame('Scout', $result);
    }

    public function test_trim_empty_string(): void
    {
        $result = ($this->runtime)('trim(@)', '');
        $this->assertSame('', $result);
    }

    public function test_trim_already_trimmed(): void
    {
        $result = ($this->runtime)('trim(@)', 'Scout');
        $this->assertSame('Scout', $result);
    }

    public function test_trim_whitespace_only(): void
    {
        $result = ($this->runtime)('trim(@)', '   ');
        $this->assertSame('', $result);
    }

    // =========================================================================
    // Test: contains_i()
    // =========================================================================

    public function test_contains_i_basic_match(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?contains_i(name, \'scout\')]', $data);
        $this->assertCount(1, $result);
        $this->assertSame('Argon Scout', $result[0]['name']);
    }

    public function test_contains_i_uppercase_needle(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?contains_i(name, \'SCOUT\')]', $data);
        $this->assertCount(1, $result);
        $this->assertSame('Argon Scout', $result[0]['name']);
    }

    public function test_contains_i_uppercase_haystack(): void
    {
        $data = [['name' => 'ARGON SCOUT']];
        $result = ($this->runtime)('[?contains_i(name, \'scout\')]', $data);
        $this->assertCount(1, $result);
        $this->assertSame('ARGON SCOUT', $result[0]['name']);
    }

    public function test_contains_i_no_match(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?contains_i(name, \'frigate\')]', $data);
        $this->assertCount(0, $result);
    }

    public function test_contains_i_empty_needle(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?contains_i(name, \'\')]', $data);
        $this->assertCount(1, $result); // Empty string matches everything
    }

    public function test_contains_i_empty_haystack(): void
    {
        $data = [['name' => '']];
        $result = ($this->runtime)('[?contains_i(name, \'scout\')]', $data);
        $this->assertCount(0, $result);
    }

    public function test_contains_i_utf8(): void
    {
        $data = [['name' => 'Ñoño Fighter']];
        $result = ($this->runtime)('[?contains_i(name, \'ÑOÑO\')]', $data);
        $this->assertCount(1, $result);
        $this->assertSame('Ñoño Fighter', $result[0]['name']);
    }

    public function test_contains_i_partial_match(): void
    {
        $data = [['name' => 'Argon Vanguard Scout MK2']];
        $result = ($this->runtime)('[?contains_i(name, \'guard\')]', $data);
        $this->assertCount(1, $result);
    }

    // =========================================================================
    // Test: starts_with_i()
    // =========================================================================

    public function test_starts_with_i_basic_match(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?starts_with_i(name, \'argon\')]', $data);
        $this->assertCount(1, $result);
        $this->assertSame('Argon Scout', $result[0]['name']);
    }

    public function test_starts_with_i_uppercase_prefix(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?starts_with_i(name, \'ARGON\')]', $data);
        $this->assertCount(1, $result);
    }

    public function test_starts_with_i_uppercase_string(): void
    {
        $data = [['name' => 'ARGON SCOUT']];
        $result = ($this->runtime)('[?starts_with_i(name, \'argon\')]', $data);
        $this->assertCount(1, $result);
    }

    public function test_starts_with_i_no_match(): void
    {
        $data = [['name' => 'Teladi Scout']];
        $result = ($this->runtime)('[?starts_with_i(name, \'argon\')]', $data);
        $this->assertCount(0, $result);
    }

    public function test_starts_with_i_empty_prefix(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?starts_with_i(name, \'\')]', $data);
        $this->assertCount(1, $result); // Empty prefix matches everything
    }

    public function test_starts_with_i_utf8(): void
    {
        $data = [['name' => 'Ñoño Fighter']];
        $result = ($this->runtime)('[?starts_with_i(name, \'ÑOÑO\')]', $data);
        $this->assertCount(1, $result);
    }

    public function test_starts_with_i_middle_match_fails(): void
    {
        $data = [['name' => 'Heavy Argon Scout']];
        $result = ($this->runtime)('[?starts_with_i(name, \'argon\')]', $data);
        $this->assertCount(0, $result); // 'argon' is not at the start
    }

    // =========================================================================
    // Test: ends_with_i()
    // =========================================================================

    public function test_ends_with_i_basic_match(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?ends_with_i(name, \'scout\')]', $data);
        $this->assertCount(1, $result);
        $this->assertSame('Argon Scout', $result[0]['name']);
    }

    public function test_ends_with_i_uppercase_suffix(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?ends_with_i(name, \'SCOUT\')]', $data);
        $this->assertCount(1, $result);
    }

    public function test_ends_with_i_uppercase_string(): void
    {
        $data = [['name' => 'ARGON SCOUT']];
        $result = ($this->runtime)('[?ends_with_i(name, \'scout\')]', $data);
        $this->assertCount(1, $result);
    }

    public function test_ends_with_i_no_match(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?ends_with_i(name, \'frigate\')]', $data);
        $this->assertCount(0, $result);
    }

    public function test_ends_with_i_empty_suffix(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?ends_with_i(name, \'\')]', $data);
        $this->assertCount(1, $result); // Empty suffix matches everything
    }

    public function test_ends_with_i_utf8(): void
    {
        $data = [['name' => 'Fighter Ñoño']];
        $result = ($this->runtime)('[?ends_with_i(name, \'ÑOÑO\')]', $data);
        $this->assertCount(1, $result);
    }

    public function test_ends_with_i_middle_match_fails(): void
    {
        $data = [['name' => 'Scout Heavy Fighter']];
        $result = ($this->runtime)('[?ends_with_i(name, \'scout\')]', $data);
        $this->assertCount(0, $result); // 'scout' is not at the end
    }

    public function test_ends_with_i_mk2(): void
    {
        $data = [['name' => 'Argon Scout Mk2']];
        $result = ($this->runtime)('[?ends_with_i(name, \'MK2\')]', $data);
        $this->assertCount(1, $result);
    }

    // =========================================================================
    // Test: Combined functions
    // =========================================================================

    public function test_combined_to_lower_with_contains(): void
    {
        $data = [
            ['name' => 'Argon Scout'],
            ['name' => 'XENON FIGHTER'],
            ['name' => 'Teladi Freighter']
        ];
        $result = ($this->runtime)('[?contains(to_lower(name), \'argon\')]', $data);
        $this->assertCount(1, $result);
        $this->assertSame('Argon Scout', $result[0]['name']);
    }

    public function test_combined_trim_with_contains_i(): void
    {
        $data = [
            ['name' => '  Argon Scout  '],
            ['name' => 'XENON FIGHTER'],
        ];
        $result = ($this->runtime)('[?contains_i(trim(name), \'scout\')]', $data);
        $this->assertCount(1, $result);
        $this->assertSame('  Argon Scout  ', $result[0]['name']);
    }

    public function test_multiple_case_insensitive_conditions(): void
    {
        $data = [
            ['name' => 'Argon Scout Mk2', 'faction' => 'argon'],
            ['name' => 'Argon Fighter', 'faction' => 'argon'],
            ['name' => 'Teladi Scout', 'faction' => 'teladi'],
        ];
        $result = ($this->runtime)('[?starts_with_i(name, \'argon\') && contains_i(name, \'scout\')]', $data);
        $this->assertCount(1, $result);
        $this->assertSame('Argon Scout Mk2', $result[0]['name']);
    }

    // =========================================================================
    // Test: Standard JMESPath functions still work
    // =========================================================================

    public function test_standard_length_function(): void
    {
        $data = ['alpha', 'beta', 'gamma'];
        $result = ($this->runtime)('length(@)', $data);
        $this->assertSame(3, $result);
    }

    public function test_standard_sort_by_function(): void
    {
        $data = [
            ['name' => 'Charlie'],
            ['name' => 'Alpha'],
            ['name' => 'Beta']
        ];
        $result = ($this->runtime)('sort_by(@, &name)', $data);
        $this->assertSame('Alpha', $result[0]['name']);
        $this->assertSame('Beta', $result[1]['name']);
        $this->assertSame('Charlie', $result[2]['name']);
    }

    public function test_standard_contains_function(): void
    {
        $data = [['name' => 'Argon Scout']];
        $result = ($this->runtime)('[?contains(name, \'Scout\')]', $data);
        $this->assertCount(1, $result);
    }
}
