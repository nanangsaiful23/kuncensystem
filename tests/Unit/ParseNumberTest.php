<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ParseNumberTest extends TestCase
{
    /** Same cases as the browser version (layout/good-loading/number-js). */
    public static function cases(): array
    {
        return [
            'indonesian thousands'      => ['12.500', 12500.0],
            'indonesian millions'       => ['1.234.567', 1234567.0],
            'indonesian decimal'        => ['1.234,5', 1234.5],
            'indonesian small decimal'  => ['2,5', 2.5],
            'english thousands'         => ['12,500', 12500.0],
            'english millions'          => ['1,234,567', 1234567.0],
            'english decimal'           => ['1,234.5', 1234.5],
            'raw from database'         => ['12500.00', 12500.0],
            'raw decimal'               => ['3333.33', 3333.33],
            'raw one decimal'           => ['2.5', 2.5],
            'zero point'                => ['0.500', 0.5],
            'zero comma'                => ['0,75', 0.75],
            'plain'                     => ['12500', 12500.0],
            'with currency'             => ['Rp 12.500', 12500.0],
            'with spaces'               => [' 12 500 ', 12500.0],
            'negative'                  => ['-1.500', -1500.0],
            'number'                    => [12500.5, 12500.5],
            'int'                       => [7, 7.0],
            'empty'                     => ['', null],
            'null'                      => [null, null],
        ];
    }

    #[DataProvider('cases')]
    public function test_reads_both_number_styles($input, $expected): void
    {
        $this->assertSame($expected, parseNumber($input));
    }

    #[DataProvider('cases')]
    public function test_unformatNumber_follows_the_same_rules(mixed $input, ?float $expected): void
    {
        $this->assertSame($expected, unformatNumber($input));
    }

    public function test_unformatNumber_no_longer_turns_indonesian_thousands_into_decimals(): void
    {
        // Old behaviour: str_replace(',', '') made "12.500" -> "12.5".
        $this->assertSame(12500.0, unformatNumber('12.500'));
        $this->assertSame(1234.5, unformatNumber('1.234,5'));
        $this->assertSame(0.0, (float) unformatNumber(null));
    }

    public function test_browser_version_gives_the_same_results(): void
    {
        $node = trim((string) shell_exec(PHP_OS_FAMILY === 'Windows' ? 'where node 2>NUL' : 'command -v node 2>/dev/null'));
        if ($node === '') {
            $this->markTestSkipped('node is not installed');
        }
        $node = strtok($node, "\r\n");

        $view = file_get_contents(dirname(__DIR__, 2) . '/resources/views/layout/good-loading/number-js.blade.php');
        preg_match('~<script>(.*)</script>~s', $view, $m);
        $cases = array_filter(self::cases(), fn ($c) => $c[1] !== null);
        $inputs = array_map(fn ($c) => $c[0], array_values($cases));

        $script = tempnam(sys_get_temp_dir(), 'num') . '.js';
        file_put_contents($script, $m[1] . "\nconsole.log(JSON.stringify(" . json_encode($inputs) . ".map(numParse)));\n"
            . "console.log(JSON.stringify([numFmt(12500.5), numFmt(10000/3), numPlain('12.500'), numPlain('1.234,567')]));");
        $out = explode("\n", trim((string) shell_exec(escapeshellarg($node) . ' ' . escapeshellarg($script))));
        unlink($script);

        $this->assertSame(array_map(fn ($c) => (float) $c[1], array_values($cases)), array_map('floatval', json_decode($out[0], true)));
        $this->assertSame(['12.500,5', '3.333,33', '12500', '1234.57'], json_decode($out[1], true));
    }
}
