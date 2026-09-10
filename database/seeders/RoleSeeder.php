<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    public const PERMISSIONS = [
        'cuti.view-own',
        'cuti.create',
        'cuti.approve-kepala-bagian',
        'cuti.approve-hrd',
        'cuti.approve-manager',
        'cuti.massal.manage',
        'master-data.manage',
        'jadwal-shift.manage',
        'laporan.view',
        'user.manage',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission);
        }

        $karyawan = Role::findOrCreate('karyawan');
        $karyawan->syncPermissions(['cuti.view-own', 'cuti.create']);

        $kepalaBagian = Role::findOrCreate('kepala_bagian');
        $kepalaBagian->syncPermissions(['cuti.view-own', 'cuti.create', 'cuti.approve-kepala-bagian']);

        $hrd = Role::findOrCreate('hrd');
        $hrd->syncPermissions([
            'cuti.view-own',
            'cuti.create',
            'cuti.approve-hrd',
            'cuti.massal.manage',
            'master-data.manage',
            'jadwal-shift.manage',
            'laporan.view',
        ]);

        $manager = Role::findOrCreate('manager');
        $manager->syncPermissions(['cuti.view-own', 'cuti.create', 'cuti.approve-manager', 'laporan.view']);

        $koordinatorShift = Role::findOrCreate('koordinator_shift');
        $koordinatorShift->syncPermissions(['cuti.view-own', 'cuti.create', 'jadwal-shift.manage']);

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions(self::PERMISSIONS);
    }
}
