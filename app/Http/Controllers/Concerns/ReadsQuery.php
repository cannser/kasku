<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

trait ReadsQuery
{
    /**
     * Ambil parameter query string sebagai string aman.
     * Jika penyerang mengirim array (?month[]=x), hasilnya string kosong (bukan error).
     */
    protected function qs(Request $request, string $key): string
    {
        $value = $request->query($key);

        return is_string($value) ? trim($value) : '';
    }
}
