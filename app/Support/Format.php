<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * Fungsi bantu tampilan dan validasi ringan.
 */
class Format
{
    /** Contoh: 25000 menjadi "Rp 25.000". */
    public static function rupiah(float|int|string|null $amount): string
    {
        $n   = (float) $amount;
        $neg = $n < 0;
        $n   = abs($n);
        $dec = floor($n) == $n ? 0 : 2;
        $s   = 'Rp ' . number_format($n, $dec, ',', '.');

        return $neg ? '-' . $s : $s;
    }

    /** Angka tanpa pecahan nol: "3000000.00" menjadi "3000000", "1500.50" tetap "1500.50". */
    public static function plainNumber(float|int|string|null $amount): string
    {
        $s = number_format((float) $amount, 2, '.', '');

        return str_ends_with($s, '.00') ? substr($s, 0, -3) : $s;
    }

    public static function validMonth(string $ym): bool
    {
        return (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $ym);
    }

    /** Batas awal (inklusif) dan akhir (eksklusif) sebuah bulan, format Y-m-d. */
    public static function monthBounds(string $ym): array
    {
        $start = Carbon::createFromFormat('!Y-m-d', $ym . '-01');

        return [$start->toDateString(), $start->copy()->addMonth()->toDateString()];
    }

    public static function monthLabel(string $ym): string
    {
        $names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                  'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        [$y, $m] = explode('-', $ym);

        return $names[(int) $m] . ' ' . $y;
    }

    /** Contoh: "Kamis, 12 November 2026" (tanpa bergantung pada locale Carbon). */
    public static function dateLabel(\DateTimeInterface $date): string
    {
        $days   = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
                   'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        return $days[(int) $date->format('w')] . ', ' . (int) $date->format('j') . ' '
            . $months[(int) $date->format('n')] . ' ' . $date->format('Y');
    }

    /** Contoh: 11 menjadi "Nov". */
    public static function monthShort(int $month): string
    {
        $short = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return $short[$month] ?? '';
    }

    /** Bulan sebelum/sesudah "Y-m" (untuk tombol navigasi bulan). $delta: -1 atau 1. */
    public static function shiftMonth(string $ym, int $delta): string
    {
        return Carbon::createFromFormat('!Y-m-d', $ym . '-01')->addMonths($delta)->format('Y-m');
    }

    /** Huruf pertama (kapital) untuk avatar/ikon kategori. */
    public static function initial(?string $text): string
    {
        $text = trim((string) $text);

        return $text === '' ? '?' : mb_strtoupper(mb_substr($text, 0, 1));
    }

    /** Persentase 0-100 (dibulatkan 2 desimal) sebagai string aman untuk atribut SVG. */
    public static function pct(float|int $part, float|int $total): string
    {
        if ($total <= 0) {
            return '0';
        }

        return number_format(max(0, min(100, $part / $total * 100)), 2, '.', '');
    }

    /**
     * Cegah CSV/Formula Injection: sel yang diawali = + - @ dianggap rumus oleh Excel.
     * Diberi awalan petik tunggal agar diperlakukan sebagai teks biasa.
     */
    public static function csvSafe(?string $value): string
    {
        $value = (string) $value;
        if ($value !== '' && str_contains("=+-@\t\r", $value[0])) {
            return "'" . $value;
        }

        return $value;
    }

    /** Password sementara acak (CSPRNG random_int), selalu berisi huruf dan angka. */
    public static function tempPassword(int $length = 12): string
    {
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
        $digits  = '23456789';
        $all     = $letters . $digits;
        $chars   = [
            $letters[random_int(0, strlen($letters) - 1)],
            $digits[random_int(0, strlen($digits) - 1)],
        ];
        while (count($chars) < $length) {
            $chars[] = $all[random_int(0, strlen($all) - 1)];
        }
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }

        return implode('', $chars);
    }
}
