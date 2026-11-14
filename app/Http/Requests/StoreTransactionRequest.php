<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi server-side untuk tambah dan ubah transaksi.
 */
class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Akses dijaga middleware auth + role:user, dan semua query berkondisi user_id.
        return true;
    }

    public function rules(): array
    {
        $uid = $this->user()->id;

        return [
            'type' => ['required', Rule::in(['income', 'expense'])],

            // Kategori harus ada DAN milik user ini (cegah memakai kategori orang lain)
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('user_id', $uid)),
            ],

            // Angka positif, maks 13 digit + 2 desimal, titik sebagai pemisah desimal
            'amount' => [
                'required', 'string', 'regex:/^\d{1,13}(\.\d{1,2})?$/',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ((float) $value <= 0) {
                        $fail('Nominal harus lebih dari 0.');
                    }
                },
            ],

            // Format Y-m-d dan benar-benar ada di kalender (2026-02-30 ditolak)
            'trx_date' => [
                'required', 'date_format:Y-m-d',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $year = (int) substr((string) $value, 0, 4);
                    if ($year < 2000 || $year > 2100) {
                        $fail('Tanggal harus antara tahun 2000 dan 2100.');
                    }
                },
            ],

            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'        => 'Jenis transaksi wajib dipilih.',
            'type.in'              => 'Jenis transaksi tidak valid.',
            'category_id.required' => 'Pilih kategori yang valid.',
            'category_id.integer'  => 'Pilih kategori yang valid.',
            'category_id.exists'   => 'Pilih kategori yang valid.',
            'amount.required'      => 'Nominal wajib diisi.',
            'amount.regex'         => 'Nominal harus angka positif (contoh 25000 atau 25000.50), maksimal 13 digit.',
            'trx_date.required'    => 'Tanggal wajib diisi.',
            'trx_date.date_format' => 'Tanggal tidak valid.',
            'description.max'      => 'Keterangan maksimal 255 karakter.',
        ];
    }
}
