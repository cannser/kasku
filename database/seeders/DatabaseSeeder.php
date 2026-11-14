<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Membuat akun admin_demo dan user_demo + contoh transaksi.
 * Jalankan: php artisan db:seed   (atau: php artisan migrate:fresh --seed)
 *
 * Password dibaca dari .env (SEED_ADMIN_PASSWORD, SEED_USER_PASSWORD). Jika kosong, dibuat
 * password acak dan ditampilkan SEKALI di terminal. Tidak ada password di source code ini.
 * Admin hanya bisa dibuat lewat seeder ini, bukan lewat registrasi.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding KasKu...');

        [$admin, $adminPw] = $this->makeUser('admin_demo', 'admin_demo@example.com', 'admin', 'seed_admin_password');
        [$user, $userPw]   = $this->makeUser('user_demo', 'user_demo@example.com', 'user', 'seed_user_password');

        Category::createDefaultsFor($user);
        $this->makeSampleTransactions($user);

        if ($adminPw !== null || $userPw !== null) {
            $this->command->warn('Catat kredensial berikut (hanya tampil sekali, jangan di-commit):');
            if ($adminPw !== null) {
                $this->command->line("  admin_demo : {$adminPw}");
            }
            if ($userPw !== null) {
                $this->command->line("  user_demo  : {$userPw}");
            }
        }
    }

    /** @return array{0: User, 1: ?string} akun dan password acak (null jika dari .env / sudah ada) */
    private function makeUser(string $username, string $email, string $role, string $configKey): array
    {
        $existing = User::where('username', $username)->first();
        if ($existing) {
            $this->command->line("  - {$username} sudah ada, dilewati.");

            return [$existing, null];
        }

        $fromEnv   = config('kasku.' . $configKey);
        $generated = false;

        if (is_string($fromEnv) && $fromEnv !== '') {
            $check = Validator::make(['password' => $fromEnv], ['password' => [Password::min(8)->letters()->numbers()]]);
            if ($check->fails()) {
                $this->command->error("Password {$username} di .env tidak valid: " . $check->errors()->first());
                exit(1);
            }
            $password = $fromEnv;
        } else {
            $password  = \App\Support\Format::tempPassword(14);
            $generated = true;
        }

        $user = new User(['username' => $username, 'email' => $email, 'password' => $password]);
        // role dan is_active tidak fillable (anti mass assignment), jadi diisi lewat forceFill
        $user->forceFill(['role' => $role, 'is_active' => true])->save();
        $this->command->line("  - {$username} ({$role}) siap.");

        return [$user, $generated ? $password : null];
    }

    private function makeSampleTransactions(User $user): void
    {
        if ($user->transactions()->exists()) {
            $this->command->line('  - user_demo sudah punya transaksi, contoh transaksi dilewati.');

            return;
        }

        $cats = $user->categories()->pluck('id', 'name');
        $expenses = [
            ['Makanan & Minuman', 'Makan siang', 25000], ['Makanan & Minuman', 'Kopi', 18000],
            ['Makanan & Minuman', 'Belanja bulanan', 150000], ['Transportasi', 'Bensin', 50000],
            ['Transportasi', 'Ojek online', 22000], ['Belanja', 'Alat tulis', 35000],
            ['Tagihan', 'Internet', 200000], ['Tagihan', 'Listrik', 120000],
            ['Hiburan', 'Nonton bioskop', 50000], ['Lainnya', 'Donasi', 20000],
        ];

        mt_srand(2026); // deterministik: hasil sama setiap seeding
        $count = 0;
        for ($i = 2; $i >= 0; $i--) {
            $month = Carbon::now()->startOfMonth()->subMonthsNoOverflow($i);

            $user->transactions()->create([
                'category_id' => $cats['Gaji'],
                'type'        => 'income',
                'amount'      => '3000000',
                'description' => 'Uang saku bulanan',
                'trx_date'    => $month->format('Y-m-01'),
            ]);
            $count++;

            foreach ($expenses as [$cat, $desc, $amount]) {
                $user->transactions()->create([
                    'category_id' => $cats[$cat],
                    'type'        => 'expense',
                    'amount'      => (string) $amount,
                    'description' => $desc,
                    'trx_date'    => $month->format('Y-m-') . sprintf('%02d', mt_rand(1, 28)),
                ]);
                $count++;
            }
        }
        $this->command->line("  - {$count} contoh transaksi dibuat untuk user_demo.");
    }
}
