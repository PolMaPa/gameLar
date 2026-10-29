<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseIsolationTest extends TestCase
{
    /**
     * Garantiza que la suite nunca apunta al SQLite real del proyecto.
     */
    public function test_the_test_suite_runs_against_an_in_memory_sqlite_database(): void
    {
        DB::connection()->getPdo();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertNotSame(database_path('database.sqlite'), DB::connection()->getDatabaseName());
    }
}
