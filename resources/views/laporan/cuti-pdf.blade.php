<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Cuti</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p.meta { margin-top: 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Laporan Pengajuan Cuti</h1>
    <p class="meta">
        Dicetak: {{ now()->format('d M Y H:i') }}
        @if ($filters['dari'] ?? null) &middot; Dari: {{ $filters['dari'] }} @endif
        @if ($filters['sampai'] ?? null) &middot; Sampai: {{ $filters['sampai'] }} @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>Nama Karyawan</th>
                <th>Departemen</th>
                <th>Jenis Cuti</th>
                <th>Tanggal Mulai</th>
                <th>Tanggal Selesai</th>
                <th>Jumlah Hari</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pengajuans as $pengajuan)
                <tr>
                    <td>{{ $pengajuan->karyawan->nama }}</td>
                    <td>{{ $pengajuan->karyawan->departemen->nama_departemen }}</td>
                    <td>{{ $pengajuan->jenisCuti->nama_jenis }}</td>
                    <td>{{ $pengajuan->tanggal_mulai->toDateString() }}</td>
                    <td>{{ $pengajuan->tanggal_selesai->toDateString() }}</td>
                    <td>{{ $pengajuan->jumlah_hari }}</td>
                    <td>{{ $pengajuan->status->label() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Tidak ada data pengajuan cuti pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
