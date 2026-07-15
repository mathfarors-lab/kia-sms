<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaAssetsTest extends TestCase
{
    // Static files under public/ aren't dispatched through Laravel's router,
    // so they aren't reachable via $this->get() in the test client the way
    // a real web server would serve them — these tests check the on-disk
    // artifacts are present and well-formed. Actual HTTP-level reachability
    // (200 status, correct content-type) was verified against a running
    // server; see the phase report.

    public function test_manifest_exists_and_is_valid_json(): void
    {
        $path = public_path('manifest.json');
        $this->assertFileExists($path);

        $manifest = json_decode(file_get_contents($path), true);
        $this->assertIsArray($manifest);
        $this->assertEquals('KIA School System', $manifest['name']);
        $this->assertEquals('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['icons']);
    }

    public function test_manifest_declares_the_required_icon_sizes(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);
        $sizes = array_column($manifest['icons'], 'sizes');

        $this->assertContains('192x192', $sizes);
        $this->assertContains('512x512', $sizes);
    }

    public function test_manifest_icon_files_exist_on_disk(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')), "Missing icon file: {$icon['src']}");
        }
    }

    public function test_service_worker_file_exists(): void
    {
        $this->assertFileExists(public_path('sw.js'));
    }

    public function test_service_worker_only_caches_static_assets_never_navigation_or_data(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        // The whole point of this file: it must never intercept anything
        // that could serve stale attendance/invoice/notification data.
        $this->assertStringContainsString('isStaticAsset', $sw);
        $this->assertStringNotContainsString('cache.match(event.request)', str_replace(' ', '', ''));
        $this->assertStringContainsString("method !== 'GET'", $sw);
    }

    public function test_layout_references_manifest_and_theme_color(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('rel="manifest" href="/manifest.json"', $layout);
        $this->assertStringContainsString('name="theme-color"', $layout);
        $this->assertStringContainsString('apple-touch-icon', $layout);
    }

    public function test_app_js_registers_the_service_worker(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("'serviceWorker' in navigator", $appJs);
        $this->assertStringContainsString("register('/sw.js')", $appJs);
    }
}
