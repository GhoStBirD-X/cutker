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
            $data['approvalPendingCount'] = Approval::query()
                ->where('approver_id', $karyawan?->id)
                ->where('status', StatusApproval::Pending)
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
