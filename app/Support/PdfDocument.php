<?php

namespace App\Support;

/**
 * Penulis PDF minimal tanpa library pihak ketiga.
 *
 * Hanya memakai fitur dasar PDF 1.4 dan font bawaan (Helvetica), jadi tidak ada dependency
 * baru, tidak ada akses jaringan/berkas saat membuat PDF, dan tidak ada HTML yang di-parse
 * (berbeda dengan konverter HTML ke PDF, yang rawan SSRF dan injeksi markup).
 * Semua teks pengguna masuk lewat escape() sehingga tidak bisa menyisipkan perintah PDF.
 *
 * Koordinat memakai satuan point (1/72 inci) dengan titik asal di KIRI ATAS halaman,
 * sumbu y turun, supaya mudah dipikirkan seperti layout biasa.
 */
class PdfDocument
{
    public const WIDTH  = 595.28; // A4
    public const HEIGHT = 841.89;

    /** Lebar glyph Helvetica per 1000 em untuk karakter ASCII 32..126 (dari berkas AFM resmi). */
    private const W_REGULAR = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    ];

    private const W_BOLD = [
        278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 333, 333, 584, 584, 584, 611,
        975, 722, 722, 722, 722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 333, 278, 333, 584, 556,
        333, 556, 611, 556, 611, 556, 333, 611, 611, 278, 278, 556, 278, 889, 611, 611,
        611, 611, 389, 556, 333, 611, 556, 778, 556, 556, 500, 389, 280, 389, 584,
    ];

    /** @var list<string> isi (content stream) tiap halaman */
    private array $pages = [];
    private int $current = -1;

    public function __construct(private string $title = '')
    {
        $this->addPage();
    }

    public function addPage(): void
    {
        $this->pages[] = '';
        $this->current = count($this->pages) - 1;
    }

    public function pageCount(): int
    {
        return count($this->pages);
    }

    /** Pindah ke halaman tertentu (0-based), dipakai untuk menggambar footer setelah semua halaman jadi. */
    public function selectPage(int $index): void
    {
        $this->current = max(0, min($index, count($this->pages) - 1));
    }

    /** Lebar teks dalam point. Karakter di luar ASCII dihitung dengan lebar rata-rata. */
    public function textWidth(string $text, float $size, bool $bold = false): float
    {
        $table = $bold ? self::W_BOLD : self::W_REGULAR;
        $sum   = 0;
        foreach (str_split($this->encode($text)) as $ch) {
            $o    = ord($ch);
            $sum += ($o >= 32 && $o <= 126) ? $table[$o - 32] : ($bold ? 611 : 556);
        }

        return $sum * $size / 1000;
    }

    /** Potong teks dengan "..." agar muat di lebar tertentu (satu baris). */
    public function fit(string $text, float $size, bool $bold, float $maxWidth): string
    {
        $text = $this->clean($text);
        if ($this->textWidth($text, $size, $bold) <= $maxWidth) {
            return $text;
        }
        while ($text !== '' && $this->textWidth($text . '...', $size, $bold) > $maxWidth) {
            $text = mb_substr($text, 0, mb_strlen($text) - 1);
        }

        return rtrim($text) . '...';
    }

    /**
     * Pecah teks menjadi beberapa baris sesuai lebar. Kata yang lebih panjang dari lebar
     * dipotong paksa. Jika melebihi $maxLines, baris terakhir diberi "...".
     *
     * @return list<string>
     */
    public function wrap(string $text, float $size, bool $bold, float $maxWidth, int $maxLines = 3): array
    {
        $words = preg_split('/\s+/u', $this->clean($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lines = [];
        $line  = '';
        $cut   = false;

        foreach ($words as $word) {
            while ($this->textWidth($word, $size, $bold) > $maxWidth) {
                // kata terlalu panjang: ambil bagian terbesar yang muat
                $part = $word;
                while (mb_strlen($part) > 1 && $this->textWidth($part, $size, $bold) > $maxWidth) {
                    $part = mb_substr($part, 0, mb_strlen($part) - 1);
                }
                if ($line !== '') {
                    $lines[] = $line;
                    $line    = '';
                }
                $lines[] = $part;
                $word    = mb_substr($word, mb_strlen($part));
                if ($word === '') {
                    break;
                }
            }
            if ($word === '') {
                continue;
            }
            $try = $line === '' ? $word : $line . ' ' . $word;
            if ($this->textWidth($try, $size, $bold) <= $maxWidth) {
                $line = $try;
            } else {
                $lines[] = $line;
                $line    = $word;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $cut   = true;
        }
        if ($cut) {
            $lines[$maxLines - 1] = $this->fit($lines[$maxLines - 1] . ' ...', $size, $bold, $maxWidth);
        }

        return $lines ?: [''];
    }

    /**
     * Tulis teks. $y adalah garis dasar (baseline). $align: left, right, atau center terhadap $x.
     *
     * @param array{0:int,1:int,2:int} $color RGB 0-255
     */
    public function text(float $x, float $y, string $text, float $size = 10, bool $bold = false,
        array $color = [0, 0, 0], string $align = 'left'): void
    {
        $text = $this->clean($text);
        if ($text === '') {
            return;
        }
        $w = $this->textWidth($text, $size, $bold);
        if ($align === 'right') {
            $x -= $w;
        } elseif ($align === 'center') {
            $x -= $w / 2;
        }

        [$r, $g, $b] = $this->rgb($color);
        $this->emit(sprintf(
            "BT /%s %s Tf %s %s %s rg 1 0 0 1 %s %s Tm (%s) Tj ET\n",
            $bold ? 'F2' : 'F1', $this->n($size), $r, $g, $b,
            $this->n($x), $this->n(self::HEIGHT - $y), $this->escape($this->encode($text))
        ));
    }

    /** Persegi (opsional sudut membulat). $fill dan $stroke berupa RGB atau null. */
    public function rect(float $x, float $y, float $w, float $h, ?array $fill = null, float $radius = 0,
        ?array $stroke = null, float $strokeWidth = 0.6): void
    {
        if ($fill === null && $stroke === null) {
            return;
        }
        $out = '';
        if ($fill !== null) {
            $out .= vsprintf('%s %s %s rg ', $this->rgb($fill));
        }
        if ($stroke !== null) {
            $out .= vsprintf('%s %s %s RG ', $this->rgb($stroke)) . $this->n($strokeWidth) . ' w ';
        }
        $out .= $this->rectPath($x, $y, $w, $h, $radius);
        $out .= $fill !== null && $stroke !== null ? 'B' : ($fill !== null ? 'f' : 'S');
        $this->emit($out . "\n");
    }

    public function line(float $x1, float $y1, float $x2, float $y2, array $color, float $width = 0.6): void
    {
        [$r, $g, $b] = $this->rgb($color);
        $this->emit(sprintf(
            "%s %s %s RG %s w %s %s m %s %s l S\n",
            $r, $g, $b, $this->n($width), $this->n($x1), $this->n(self::HEIGHT - $y1),
            $this->n($x2), $this->n(self::HEIGHT - $y2)
        ));
    }

    /** Hasilkan berkas PDF lengkap sebagai string biner. */
    public function output(): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $firstPage = 5;
        foreach ($this->pages as $i => $_) {
            $kids[] = ($firstPage + $i * 2) . ' 0 R';
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($this->pages) . ' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        foreach ($this->pages as $i => $content) {
            $pageNo = $firstPage + $i * 2;
            $objects[$pageNo] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %s %s] /Contents %d 0 R '
                . '/Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> >>',
                $this->n(self::WIDTH), $this->n(self::HEIGHT), $pageNo + 1
            );
            $objects[$pageNo + 1] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . 'endstream';
        }
        $infoNo = $firstPage + count($this->pages) * 2;
        $objects[$infoNo] = '<< /Title (' . $this->escape($this->encode($this->title)) . ') /Producer (KasKu) >>';

        $pdf     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $no => $body) {
            $offsets[$no] = strlen($pdf);
            $pdf .= $no . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($infoNo + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $infoNo; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= 'trailer << /Size ' . ($infoNo + 1) . " /Root 1 0 R /Info {$infoNo} 0 R >>\n"
            . "startxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }

    // ---------------------------------------------------------------- internal

    private function emit(string $ops): void
    {
        $this->pages[$this->current] .= $ops;
    }

    /** Buang karakter kontrol (termasuk baris baru) agar tidak merusak aliran teks. */
    private function clean(string $text): string
    {
        return trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $text));
    }

    /** UTF-8 ke Windows-1252 (encoding bawaan font). Karakter yang tidak ada diganti "?". */
    private function encode(string $text): string
    {
        return mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
    }

    /** Escape string literal PDF: backslash dan kurung. */
    private function escape(string $s): string
    {
        return strtr($s, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)']);
    }

    /** Angka dengan titik desimal, tidak tergantung locale. */
    private function n(float $v): string
    {
        return rtrim(rtrim(sprintf('%.2F', $v), '0'), '.') ?: '0';
    }

    /** @return array{0:string,1:string,2:string} */
    private function rgb(array $c): array
    {
        return [$this->n($c[0] / 255), $this->n($c[1] / 255), $this->n($c[2] / 255)];
    }

    private function rectPath(float $x, float $y, float $w, float $h, float $r): string
    {
        $py = self::HEIGHT - $y - $h; // titik kiri bawah dalam sistem PDF
        if ($r <= 0) {
            return sprintf('%s %s %s %s re ', $this->n($x), $this->n($py), $this->n($w), $this->n($h));
        }
        $r = min($r, $w / 2, $h / 2);
        $k = $r * 0.5523;
        [$x0, $x1, $y0, $y1] = [$x, $x + $w, $py, $py + $h];
        $f = fn(float ...$v) => implode(' ', array_map(fn($a) => $this->n($a), $v));

        return $f($x0 + $r, $y0) . ' m '
            . $f($x1 - $r, $y0) . ' l ' . $f($x1 - $r + $k, $y0, $x1, $y0 + $r - $k, $x1, $y0 + $r) . ' c '
            . $f($x1, $y1 - $r) . ' l ' . $f($x1, $y1 - $r + $k, $x1 - $r + $k, $y1, $x1 - $r, $y1) . ' c '
            . $f($x0 + $r, $y1) . ' l ' . $f($x0 + $r - $k, $y1, $x0, $y1 - $r + $k, $x0, $y1 - $r) . ' c '
            . $f($x0, $y0 + $r) . ' l ' . $f($x0, $y0 + $r - $k, $x0 + $r - $k, $y0, $x0 + $r, $y0) . ' c h ';
    }
}
