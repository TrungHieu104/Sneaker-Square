<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->refuseToRunAgainstARealDatabase($app);

        return $app;
    }

    /**
     * Aborts unless the suite is pointed at the in-memory test database.
     *
     * phpunit.xml sets DB_CONNECTION to sqlite `:memory:`, but those <env>
     * entries are ignored the moment a cached config file exists: config()
     * then reads bootstrap/cache/config.php, which names the MySQL development
     * database. Running the suite in that state points RefreshDatabase — and
     * the migrate:fresh it performs — at real data and drops every table.
     *
     * The check has to happen here rather than in TestCase::setUp(), because
     * setUp() runs the trait hooks first: by the time a check there could fire,
     * RefreshDatabase has already dropped the tables.
     */
    private function refuseToRunAgainstARealDatabase(Application $app): void
    {
        $config = $app->make('config');
        $connection = $config->get('database.default');
        $database = $config->get("database.connections.{$connection}.database");

        if ($connection === 'sqlite' && $database === ':memory:') {
            return;
        }

        $hint = file_exists($app->bootstrapPath('cache/config.php'))
            ? 'Có file bootstrap/cache/config.php — chạy `php artisan config:clear` rồi thử lại.'
            : 'Kiểm tra lại phần <env> trong phpunit.xml.';

        throw new RuntimeException(
            "Bộ test đang trỏ vào '{$connection}' / '{$database}' thay vì sqlite :memory:. "
            . $hint . ' Nếu chạy tiếp, RefreshDatabase sẽ xóa sạch database thật.'
        );
    }
}
