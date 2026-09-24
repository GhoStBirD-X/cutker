<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Saldo Cuti</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #1a1a1a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        p.meta { margin: 0 0 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #D1D5DB; padding: 5px 6px; }
        thead th { background-color: #4F46E5; color: #fff; text-align: center; vertical-align: middle; }
        thead tr.sub th { background-color: #EEF2FF; color: #3730A3; font-weight: normal; font-size: 9px; }
        tbody tr:nth-child(even) { background-color: #F9FAFB; }
        td.nama { text-align: left; font-weight: bold; }
        td.center { text-align: center; }
        td.muted { color: #9CA3AF; }
        td.sisa { font-weight: bold; }
        .badge { display: inline-block; border: 1px solid #D1D5DB; border-radius: 3px; padding: 1px 6px; font-size: 9px; }
    </style>
</head>
<body>
    <h1>Laporan Saldo Cuti Karyawan</h1>
    <p class="meta">
        Dicetak: {{ now()->format('d M Y H:i') }}
        @if ($filters['search'] ?? null) &middot; Cari: "{{ $filters['search'] }}" @endif
        @if ($departemen) &middot; Departemen: {{ $departemen->nama_departemen }} @endif
    </p>

    <table>
        <thead>
            <tr>
                <th rowspan="2">Nama</th>
                <th rowspan="2">NIP</th>
                <th rowspan="2">Status</th>
                @foreach ($kolomJenisCuti as $nama)
                    <th colspan="2">{{ $nama }}</th>
                @endforeach
            </tr>
            <tr class="sub">
                @foreach ($kolomJenisCuti as $nama)
                    <th>Terpakai</th>
                    <th>Sisa</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($karyawans as $karyawan)
                <tr>
                    <td class="nama">{{ $karyawan['nama'] }}</td>
                    <td class="center">{{ $karyawan['nip'] }}</td>
                    <td class="center"><span class="badge">{{ $karyawan['status_kontrak'] }}</span></td>
                    @foreach ($karyawan['kolom'] as $kolom)
                        @if ($kolom === null)
                            <td class="center muted">&mdash;</td>
                            <td class="center muted">&mdash;</td>
                        @else
                            <td class="center">{{ $kolom['terpakai'] }}</td>
                            <td class="center sisa" style="color: #{{ $kolom['warna'] }};">{{ $kolom['sisa'] ?? 'Tanpa Batas' }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 3 + count($kolomJenisCuti) * 2 }}" class="center muted">Tidak ada karyawan pada filter ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
