<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Everything under public/ is handed out by the web server to anyone who asks
 * for it by name, so no key may sit there.
 */
class SecretFilesTest extends TestCase
{
    public function test_khoa_google_analytics_khong_nam_trong_thu_muc_public(): void
    {
        $path = (string) config('analytics.service_account_credentials_json');

        $this->assertStringStartsNotWith(public_path(), realpath($path) ?: $path);
    }

    public function test_thu_muc_public_khong_chua_file_khoa_rieng(): void
    {
        $leaks = [];

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(public_path(), \FilesystemIterator::SKIP_DOTS)) as $file) {
            if (in_array($file->getExtension(), ['json', 'pem', 'key'], true)
                && $file->getSize() < 100_000
                && str_contains((string) file_get_contents($file->getPathname()), 'PRIVATE KEY')) {
                $leaks[] = $file->getPathname();
            }
        }

        $this->assertSame([], $leaks);
    }
}
