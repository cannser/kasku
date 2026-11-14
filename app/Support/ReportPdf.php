<?php

namespace App\Support;

/**
 * Menyusun tata letak PDF laporan bulanan KasKu di atas PdfDocument.
 *
 * Data masuk sudah berupa array biasa (tanpa model Eloquent) dan sudah dibatasi ke milik
 * pengguna yang sedang login oleh ReportController, jadi kelas ini hanya urusan tampilan.
 */
class ReportPdf
{
    private const INK     = [15, 27, 61];
    private const SAFFRON = [245, 165, 36];
    private const MUTED   = [100, 110, 140];
    private const LINE    = [226, 230, 240];
    private const BG      = [241, 243, 248];
    private const ZEBRA   = [247, 248, 251];
    private const IN      = [8, 122, 90];
    private const OUT     = [200, 50, 63];
    private const WHITE   = [255, 255, 255];
    private const BAR_SUB = [197, 205, 233];

    private const M       = 42.0;   // margin kiri dan kanan
    private const PAD     = 8.0;    // padding dalam sel
    private const ROW_PAD = 6.0;    // padding atas/bawah baris
    private const LEAD    = 11.5;   // jarak antar baris teks

    private PdfDocument $pdf;
    private float $y = 0;
    private float $right;
    private float $bottom;

    /**
     * @param array{
     *   user: string, month: string, generated: \DateTimeInterface, income: float, expense: float,
     *   recap: list<array{category: string, type: string, count: int, total: float}>,
     *   rows: list<array{date: \DateTimeInterface, type: string, category: string, description: string, amount: float}>
     * } $d
     */
    public static function render(array $d): string
    {
        return (new self())->build($d);
    }

    private function build(array $d): string
    {
        $label = Format::monthLabel($d['month']);
        $this->pdf    = new PdfDocument('Laporan KasKu ' . $label);
        $this->right  = PdfDocument::WIDTH - self::M;
        $this->bottom = PdfDocument::HEIGHT - 62;

        $this->header($d, $label);
        $this->summary($d['income'], $d['expense']);

        if ($d['rows'] === []) {
            $this->pdf->text(self::M, $this->y + 14, 'Tidak ada transaksi pada bulan ini.', 10.5, false, self::MUTED);
        } else {
            $this->recapTable($d['recap']);
            $this->transactionTable($d['rows']);
        }

        $this->footers($label);

        return $this->pdf->output();
    }

    private function header(array $d, string $label): void
    {
        $p = $this->pdf;
        $p->rect(0, 0, PdfDocument::WIDTH, 80, self::INK);
        // logo: kotak safron dengan huruf K, sama seperti di aplikasi
        $p->rect(self::M, 25, 30, 30, self::SAFFRON, 8);
        $p->text(self::M + 15, 46.5, 'K', 17, true, self::INK, 'center');
        $p->text(self::M + 40, 46, 'KasKu', 19, true, self::WHITE);

        $p->text($this->right, 40, 'Laporan keuangan', 12, true, self::WHITE, 'right');
        $p->text($this->right, 56, $label, 10.5, false, self::BAR_SUB, 'right');

        $p->text(self::M, 106, 'Pengguna: ' . $d['user'], 9, false, self::MUTED);
        $p->text($this->right, 106, 'Dibuat ' . Format::dateLabel($d['generated']), 9, false, self::MUTED, 'right');
        $this->y = 124;
    }

    private function summary(float $income, float $expense): void
    {
        $net   = $income - $expense;
        $gap   = 12.0;
        $w     = ($this->right - self::M - 2 * $gap) / 3;
        $items = [
            ['Total pemasukan', $income, self::IN],
            ['Total pengeluaran', $expense, self::OUT],
            ['Selisih', $net, $net >= 0 ? self::IN : self::OUT],
        ];

        foreach ($items as $i => [$label, $value, $color]) {
            $x = self::M + $i * ($w + $gap);
            $this->pdf->rect($x, $this->y, $w, 58, self::BG, 8);
            $this->pdf->text($x + 14, $this->y + 21, $label, 9, false, self::MUTED);

            $text = Format::rupiah($value);
            $size = 15.0;
            while ($size > 9 && $this->pdf->textWidth($text, $size, true) > $w - 28) {
                $size -= 0.5; // nominal sangat besar: kecilkan agar tetap muat
            }
            $this->pdf->text($x + 14, $this->y + 44, $text, $size, true, $color);
        }
        $this->y += 58 + 30;
    }

    /** @param list<array{category: string, type: string, count: int, total: float}> $recap */
    private function recapTable(array $recap): void
    {
        $cols = [
            ['Kategori', self::M, 'left'],
            ['Jenis', self::M + 230, 'left'],
            ['Transaksi', self::M + 360, 'left'],
            ['Total', $this->right, 'right'],
        ];
        $this->sectionTitle('Rekap per kategori');
        $this->tableHead($cols);

        foreach ($recap as $i => $r) {
            if ($this->y + 24 > $this->bottom) {
                $this->newPage();
                $this->tableHead($cols);
            }
            $this->zebra($i, 24);
            $base = $this->y + 15.5;
            $this->pdf->text(self::M + self::PAD, $base, $this->pdf->fit($r['category'], 9.5, false, 210), 9.5, false, self::INK);
            $this->pdf->text(self::M + 230 + self::PAD, $base, $r['type'] === 'income' ? 'Pemasukan' : 'Pengeluaran', 9.5, false, self::MUTED);
            $this->pdf->text(self::M + 360 + self::PAD, $base, (string) $r['count'], 9.5, false, self::INK);
            $this->pdf->text($this->right - self::PAD, $base, Format::rupiah($r['total']), 9.5, true,
                $r['type'] === 'income' ? self::IN : self::OUT, 'right');
            $this->y += 24;
        }
        $this->y += 28;
    }

    /** @param list<array{date: \DateTimeInterface, type: string, category: string, description: string, amount: float}> $rows */
    private function transactionTable(array $rows): void
    {
        $xDate = self::M;
        $xCat  = self::M + 74;
        $xDesc = self::M + 200;
        $wCat  = $xDesc - $xCat - self::PAD * 2;
        $wDesc = ($this->right - 112) - $xDesc - self::PAD;
        $cols  = [
            ['Tanggal', $xDate, 'left'], ['Kategori', $xCat, 'left'],
            ['Keterangan', $xDesc, 'left'], ['Nominal', $this->right, 'right'],
        ];

        // judul dan baris pertama jangan terpisah dari tabelnya
        if ($this->y + 22 + 22 + 30 > $this->bottom) {
            $this->newPage();
        }
        $this->sectionTitle('Daftar transaksi (' . count($rows) . ')');
        $this->tableHead($cols);

        foreach ($rows as $i => $r) {
            $desc  = $r['description'] === '' ? ['-'] : $this->pdf->wrap($r['description'], 9.5, false, $wDesc, 3);
            $h     = max(1, count($desc)) * self::LEAD + self::ROW_PAD * 2;
            if ($this->y + $h > $this->bottom) {
                $this->newPage();
                $this->tableHead($cols);
            }
            $this->zebra($i, $h);
            $base = $this->y + self::ROW_PAD + 8.5;

            $d = (int) $r['date']->format('j') . ' ' . Format::monthShort((int) $r['date']->format('n')) . ' ' . $r['date']->format('Y');
            $this->pdf->text($xDate + self::PAD, $base, $d, 9.5, false, self::INK);
            $this->pdf->text($xCat + self::PAD, $base, $this->pdf->fit($r['category'], 9.5, false, $wCat), 9.5, false, self::INK);
            foreach ($desc as $n => $line) {
                $this->pdf->text($xDesc + self::PAD, $base + $n * self::LEAD, $line, 9.5, false,
                    $r['description'] === '' ? self::MUTED : self::INK);
            }
            $income = $r['type'] === 'income';
            $this->pdf->text($this->right - self::PAD, $base, ($income ? '+ ' : '- ') . Format::rupiah($r['amount']), 9.5, true,
                $income ? self::IN : self::OUT, 'right');
            $this->y += $h;
        }
        $this->pdf->line(self::M, $this->y, $this->right, $this->y, self::LINE, 0.8);
    }

    private function sectionTitle(string $text): void
    {
        $this->pdf->text(self::M, $this->y + 10, $text, 12, true, self::INK);
        $this->y += 22;
    }

    /** @param list<array{0:string,1:float,2:string}> $cols judul, posisi x, rata */
    private function tableHead(array $cols): void
    {
        $this->pdf->rect(self::M, $this->y, $this->right - self::M, 22, self::INK, 4);
        foreach ($cols as [$title, $x, $align]) {
            $pos = $align === 'right' ? $x - self::PAD : $x + self::PAD;
            $this->pdf->text($pos, $this->y + 14.5, $title, 8.5, true, self::WHITE, $align);
        }
        $this->y += 22;
    }

    private function zebra(int $index, float $height): void
    {
        if ($index % 2 === 1) {
            $this->pdf->rect(self::M, $this->y, $this->right - self::M, $height, self::ZEBRA);
        }
    }

    private function newPage(): void
    {
        $this->pdf->addPage();
        $this->y = 48;
    }

    private function footers(string $label): void
    {
        $total = $this->pdf->pageCount();
        for ($i = 0; $i < $total; $i++) {
            $this->pdf->selectPage($i);
            $y = PdfDocument::HEIGHT - 38;
            $this->pdf->line(self::M, $y, $this->right, $y, self::LINE, 0.8);
            $this->pdf->text(self::M, $y + 16, 'KasKu - Laporan ' . $label, 8, false, self::MUTED);
            $this->pdf->text($this->right, $y + 16, 'Halaman ' . ($i + 1) . ' dari ' . $total, 8, false, self::MUTED, 'right');
        }
    }
}
