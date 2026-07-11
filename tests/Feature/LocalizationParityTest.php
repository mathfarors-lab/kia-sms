<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Bilingual guarantee (roadmap Phase 1.3): every user-facing English string
 * must have a Khmer twin. Fails the build when either drifts:
 *
 *  1. Every lang/en/<file>.php has a lang/km/<file>.php with the SAME key set.
 *  2. Every bare English literal passed to __() anywhere in views or app code
 *     exists as a key in lang/km.json (Laravel's JSON translations use the
 *     English string itself as the key, so no view rewrites are needed).
 */
class LocalizationParityTest extends TestCase
{
    private const KEY_PATTERN = '/^[a-z0-9_\-]+(\.[a-z0-9_\-]+)+$/';

    public function test_every_en_php_lang_file_has_a_km_twin_with_identical_keys(): void
    {
        $enDir = lang_path('en');
        $kmDir = lang_path('km');

        foreach (glob($enDir . '/*.php') as $enFile) {
            $name   = basename($enFile);
            $kmFile = $kmDir . '/' . $name;

            $this->assertFileExists($kmFile, "Missing Khmer lang file: lang/km/{$name}");

            $enKeys = $this->flattenKeys(require $enFile);
            $kmKeys = $this->flattenKeys(require $kmFile);

            $missing = array_diff($enKeys, $kmKeys);
            $this->assertEmpty(
                $missing,
                "lang/km/{$name} is missing keys: " . implode(', ', array_slice($missing, 0, 10))
            );
        }
    }

    public function test_every_bare_literal_in_views_and_app_exists_in_km_json(): void
    {
        $kmJson = lang_path('km.json');
        $this->assertFileExists($kmJson, 'lang/km.json is missing.');

        $translations = json_decode(file_get_contents($kmJson), true);
        $this->assertIsArray($translations);

        $missing = [];
        foreach ($this->collectBareLiterals([resource_path('views'), app_path()]) as $literal) {
            if (!array_key_exists($literal, $translations)) {
                $missing[] = $literal;
            }
        }

        $this->assertEmpty(
            $missing,
            'lang/km.json is missing Khmer translations for: ' .
            implode(' | ', array_slice($missing, 0, 15)) .
            (count($missing) > 15 ? ' … and ' . (count($missing) - 15) . ' more' : '')
        );
    }

    public function test_km_json_has_no_empty_translations(): void
    {
        $translations = json_decode(file_get_contents(lang_path('km.json')), true);

        foreach ($translations as $en => $km) {
            $this->assertNotSame('', trim((string) $km), "Empty Khmer translation for: {$en}");
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Flatten nested lang arrays to dot-keys so structure differences surface too. */
    private function flattenKeys(array $arr, string $prefix = ''): array
    {
        $keys = [];
        foreach ($arr as $k => $v) {
            $full = $prefix === '' ? (string) $k : "{$prefix}.{$k}";
            if (is_array($v)) {
                $keys = array_merge($keys, $this->flattenKeys($v, $full));
            } else {
                $keys[] = $full;
            }
        }
        return $keys;
    }

    /** @return string[] unique bare literals passed to __() under the given roots */
    private function collectBareLiterals(array $roots): array
    {
        $literals = [];

        foreach ($roots as $root) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if (!$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                    continue;
                }
                $src = file_get_contents($file->getPathname());
                preg_match_all('/__\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $src, $m1);
                preg_match_all('/__\(\s*"((?:[^"\\\\]|\\\\.)*)"/', $src, $m2);

                foreach (array_merge($m1[1], $m2[1]) as $s) {
                    $s = stripcslashes($s);
                    if ($s === '' || preg_match(self::KEY_PATTERN, $s)) {
                        continue; // namespaced lang key, covered by the php-file parity test
                    }
                    $literals[$s] = true;
                }
            }
        }

        return array_keys($literals);
    }
}
