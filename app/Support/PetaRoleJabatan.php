<?php

namespace App\Support;

class PetaRoleJabatan
{
    public const ROLE_DEFAULT = 'karyawan';

    /**
     * Peta nama jabatan → role sistem, dipakai saat import CSV karyawan
     * untuk menentukan role akun login yang dibuat otomatis. Pencocokan
     * PERSIS (bukan sebagian/substring) dan tidak case-sensitive — sengaja
     * begitu supaya jabatan seperti "Staff Administrasi" tidak salah
     * ke-anggap role "admin" hanya karena mengandung kata itu. Jabatan
     * yang tidak cocok persis dengan salah satu di bawah dapat role
     * ROLE_DEFAULT ("karyawan"), lalu bisa dipromosikan manual lewat
     * Kelola User. Daftar ini juga dipakai sebagai sumber untuk sheet
     * "Panduan Role" di template import (lihat KaryawanImportTemplateExport
     * / PanduanRoleSheet) — ubah di satu tempat ini saja.
     *
     * @var array<string, string>
     */
    public const PETA = [
        'kepala bagian' => 'kepala_bagian',
        'koordinator shift' => 'koordinator_shift',
        'staff hrd' => 'hrd',
        'manager' => 'manager',
        'administrator sistem' => 'admin',
    ];

    public static function roleUntuk(string $namaJabatan): string
    {
        return self::PETA[mb_strtolower(trim($namaJabatan))] ?? self::ROLE_DEFAULT;
    }
}
