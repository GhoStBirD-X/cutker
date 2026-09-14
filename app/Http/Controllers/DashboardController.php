<?php

namespace App\Http\Controllers;

use App\Enums\StatusApproval;
use App\Enums\StatusKaryawan;
use App\Enums\StatusKompensasiCuti;
use App\Enums\StatusKonfirmasiKontrak;
use App\Enums\StatusPengajuan;
use App\Models\Approval;
use App\Models\Karyawan;
use App\Models\KompensasiCuti;
use App\Models\KonfirmasiKontrakCuti;
use App\Models\PengajuanCuti;
use App\Models\SaldoCuti;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $karyawan = $user->karyawan;

        $data = [];

        if ($karyawan) {
            $data['saldoCuti'] = SaldoCuti::query()
                ->with('jenisCuti')
                ->where('karyawan_id', $karyawan->id)
                ->aktif()
                ->whereHas('jenisCuti', fn ($query) => $query->whereNull('khusus_gender')->orWhere('khusus_gender', $karyawan->jenis_kelamin->value))
                ->get();

            $data['riwayatCuti'] = PengajuanCuti::query()
                ->with('jenisCuti')
                ->where('karyawan_id', $karyawan->id)
                ->latest('tanggal_pengajuan')
                ->limit(5)
                ->get();

            $data['resumeCuti'] = $karyawan->riwayatSaldoCutis()
                ->with('jenisCuti')
                ->latest('periode_selesai')
                ->limit(5)
                ->get();

            $data['menungguKonfirmasiKontrak'] = KonfirmasiKontrakCuti::query()
                ->where('karyawan_id', $karyawan->id)
                ->where('status', StatusKonfirmasiKontrak::Menunggu)
                ->exists();
        }

        if ($user->hasAnyRole(['kepala_bagian', 'hrd', 'manager'])) {
            // Level HRD & Manager adalah kolam bersama: dihitung dari SELURUH
            // approval pending di level itu, bukan cuma yang approver_id-nya
            // cocok dengan diri sendiri (lihat ApprovalController::index()
            // & ApprovalPolicy untuk pola yang sama).
            $data['approvalPendingCount'] = Approval::query()
                ->where('status', StatusApproval::Pending)
                ->where(function ($query) use ($user, $karyawan) {
                    $query->where('approver_id', $karyawan?->id);

                    if ($user->hasRole('hrd')) {
                        $query->orWhere('level', ApprovalService::LEVEL_HRD);
                    }

                    if ($user->hasRole('manager')) {
                        $query->orWhere('level', ApprovalService::LEVEL_MANAGER);
                    }
                })
                ->count();
        }

        if ($user->hasAnyRole(['hrd', 'admin'])) {
            $data['ringkasanPabrik'] = [
                'total_karyawan_aktif' => Karyawan::query()->where('status', StatusKaryawan::Aktif)->count(),
                'total_pengajuan_pending' => PengajuanCuti::query()->where('status', StatusPengajuan::Pending)->count(),
                'total_pengajuan_bulan_ini' => PengajuanCuti::query()->whereMonth('tanggal_pengajuan', now()->month)->whereYear('tanggal_pengajuan', now()->year)->count(),
            ];

            $data['konfirmasiKontrakPendingCount'] = KonfirmasiKontrakCuti::query()->where('status', StatusKonfirmasiKontrak::Menunggu)->count();
            $data['kompensasiCutiPendingCount'] = KompensasiCuti::query()->where('status', StatusKompensasiCuti::MenungguDiproses)->count();
        }

        return Inertia::render('dashboard', $data);
    }
}
