<?php

/*
 * Pesan validasi berbahasa Indonesia. Kunci yang tidak ada di sini otomatis memakai
 * bahasa cadangan (en) bawaan framework, jadi file ini aman walau tidak lengkap.
 */
return [
    'accepted'         => ':attribute harus diterima.',
    'array'            => ':attribute harus berupa array.',
    'boolean'          => ':attribute harus bernilai benar atau salah.',
    'confirmed'        => 'Konfirmasi :attribute tidak sama.',
    'current_password' => 'Password saat ini salah.',
    'date'             => ':attribute bukan tanggal yang valid.',
    'date_format'      => ':attribute tidak sesuai format :format.',
    'different'        => ':attribute dan :other tidak boleh sama.',
    'email'            => 'Format :attribute tidak valid.',
    'exists'           => ':attribute yang dipilih tidak valid.',
    'in'               => ':attribute yang dipilih tidak valid.',
    'integer'          => ':attribute harus berupa bilangan bulat.',
    'numeric'          => ':attribute harus berupa angka.',
    'regex'            => 'Format :attribute tidak valid.',
    'required'         => ':attribute wajib diisi.',
    'string'           => ':attribute harus berupa teks.',
    'unique'           => ':attribute sudah terdaftar.',

    'max' => [
        'array'   => ':attribute maksimal :max item.',
        'file'    => ':attribute maksimal :max kilobyte.',
        'numeric' => ':attribute maksimal :max.',
        'string'  => ':attribute maksimal :max karakter.',
    ],
    'min' => [
        'array'   => ':attribute minimal :min item.',
        'file'    => ':attribute minimal :min kilobyte.',
        'numeric' => ':attribute minimal :min.',
        'string'  => ':attribute minimal :min karakter.',
    ],

    // Aturan Password::min(8)->letters()->numbers()
    'password' => [
        'letters'       => ':attribute harus mengandung setidaknya satu huruf.',
        'mixed'         => ':attribute harus mengandung huruf besar dan huruf kecil.',
        'numbers'       => ':attribute harus mengandung setidaknya satu angka.',
        'symbols'       => ':attribute harus mengandung setidaknya satu simbol.',
        'uncompromised' => ':attribute pernah bocor di internet. Pilih :attribute lain.',
    ],

    'custom' => [],

    'attributes' => [
        'username'         => 'Username',
        'email'            => 'Email',
        'password'         => 'Password',
        'current_password' => 'Password saat ini',
        'name'             => 'Nama',
        'type'             => 'Jenis',
        'category_id'      => 'Kategori',
        'amount'           => 'Nominal',
        'trx_date'         => 'Tanggal',
        'description'      => 'Keterangan',
    ],
];
