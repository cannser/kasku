<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * PENGAMAN: tes memakai RefreshDatabase (migrate:fresh = hapus semua tabel).
     * Kalau koneksi aktif bukan SQLite memori, tes dihentikan SEBELUM menyentuh database apa pun,
     * supaya database sungguhan (TiDB/XAMPP) tidak pernah terhapus.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        $default  = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$default}.database");

        if ($default !== 'sqlite' || $database !== ':memory:') {
            throw new \RuntimeException(
                "Tes DIHENTIKAN: koneksi database tes adalah '{$default}', bukan SQLite memori. "
                . 'Periksa phpunit.xml (DB_CONNECTION=sqlite, DB_DATABASE=:memory:).'
            );
        }

        return $app;
    }
}
