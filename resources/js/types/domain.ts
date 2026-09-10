import type { JenisKelamin, Karyawan } from './auth';

export type Departemen = {
    id: number;
    nama_departemen: string;
    kode: string;
    karyawans_count?: number;
};

export type Jabatan = {
    id: number;
    nama_jabatan: string;
    karyawans_count?: number;
};

export type JenisCuti = {
    id: number;
    nama_jenis: string;
    kuota_default: number | null;
    masa_kerja_minimal_bulan: number | null;
    khusus_gender: JenisKelamin | null;
    keterangan: string | null;
};

export type AlasanCuti = {
    id: number;
    jenis_cuti_id: number;
    nama_alasan: string;
    jumlah_hari: number | null;
    keterangan: string | null;
    jenis_cuti?: JenisCuti;
};

export type Shift = {
    id: number;
    nama_shift: string;
    jam_mulai: string;
    jam_selesai: string;
};

export type StatusPengajuan =
    'pending' | 'disetujui' | 'ditolak' | 'dibatalkan';
export type StatusApproval = 'pending' | 'disetujui' | 'ditolak' | 'dibatalkan';

export type SaldoCuti = {
    id: number;
    karyawan_id: number;
    jenis_cuti_id: number;
    tahun: number;
    periode_ke: number | null;
    periode_mulai: string | null;
    periode_selesai: string | null;
    kuota: number | null;
    terpakai: number;
    sisa: number | null;
    ditutup_pada: string | null;
    catatan: string | null;
    diubah_oleh_id: number | null;
    diubah_pada: string | null;
    karyawan?: Karyawan;
    jenis_cuti?: JenisCuti;
    diubah_oleh?: Karyawan;
};

export type PengajuanCuti = {
    id: number;
    karyawan_id: number;
    jenis_cuti_id: number;
    alasan_cuti_id: number | null;
    cuti_massal_id: number | null;
    tanggal_mulai: string;
    tanggal_selesai: string;
    jumlah_hari: number;
    jumlah_hari_kalender: number;
    alasan: string;
    status: StatusPengajuan;
    tanggal_pengajuan: string;
    lampiran: string | null;
    karyawan?: Karyawan;
    jenis_cuti?: JenisCuti;
    alasan_cuti?: AlasanCuti;
    approvals?: Approval[];
    cuti_massal?: CutiMassal;
};

export type StatusCutiMassal = 'aktif' | 'dibatalkan';

export type CutiMassalDilewati = {
    karyawan_id: number;
    nama: string;
    alasan: string;
};

export type CutiMassal = {
    id: number;
    jenis_cuti_id: number;
    tanggal_mulai: string;
    tanggal_selesai: string;
    jumlah_hari: number;
    jumlah_hari_kalender: number;
    alasan: string;
    dibuat_oleh_id: number;
    jumlah_karyawan: number;
    dilewati: CutiMassalDilewati[] | null;
    status: StatusCutiMassal;
    dibatalkan_oleh_id: number | null;
    dibatalkan_pada: string | null;
    catatan_pembatalan: string | null;
    jenis_cuti?: JenisCuti;
    dibuat_oleh?: Karyawan;
    dibatalkan_oleh?: Karyawan;
    pengajuan_cutis?: PengajuanCuti[];
};

export type KaryawanEligiblePreview = {
    karyawan: Karyawan;
    saldo: SaldoCuti | null;
    akan_minus: boolean;
};

export type HariLibur = {
    id: number;
    tanggal: string;
    keterangan: string;
    sumber: 'nasional' | 'perusahaan';
};

export type RiwayatSaldoCuti = {
    id: number;
    karyawan_id: number;
    jenis_cuti_id: number;
    periode_ke: number;
    periode_mulai: string;
    periode_selesai: string;
    kuota: number;
    terpakai: number;
    sisa: number;
    karyawan?: Karyawan;
    jenis_cuti?: JenisCuti;
};

export type StatusKompensasiCuti = 'menunggu_diproses' | 'diproses';

export type KompensasiCuti = {
    id: number;
    karyawan_id: number;
    jenis_cuti_id: number;
    riwayat_saldo_cuti_id: number | null;
    jumlah_hari: number;
    rate_per_hari: string | null;
    total_rupiah: string | null;
    status: StatusKompensasiCuti;
    diproses_oleh_id: number | null;
    diproses_pada: string | null;
    catatan: string | null;
    karyawan?: Karyawan;
    jenis_cuti?: JenisCuti;
    diproses_oleh?: Karyawan;
};

export type StatusKonfirmasiKontrak =
    'menunggu' | 'diperpanjang' | 'tidak_diperpanjang';

export type KonfirmasiKontrakCuti = {
    id: number;
    karyawan_id: number;
    saldo_cuti_id: number;
    periode_ke: number;
    tanggal_batas: string;
    status: StatusKonfirmasiKontrak;
    dikonfirmasi_oleh_id: number | null;
    dikonfirmasi_pada: string | null;
    catatan: string | null;
    karyawan?: Karyawan;
    saldo_cuti?: SaldoCuti;
    dikonfirmasi_oleh?: Karyawan;
};

export type Approval = {
    id: number;
    pengajuan_cuti_id: number;
    approver_id: number;
    level: number;
    status: StatusApproval;
    tanggal_approval: string | null;
    catatan: string | null;
    approver?: Karyawan;
    pengajuan_cuti?: PengajuanCuti;
};

export type AppNotification = {
    id: string;
    data: {
        message: string;
        pengajuan_cuti_id?: number;
        [key: string]: unknown;
    };
    read_at: string | null;
    created_at: string;
};

export type JadwalShift = {
    id: number;
    karyawan_id: number;
    shift_id: number;
    tanggal: string;
    jam_lembur: string | null;
    catatan_lembur: string | null;
    karyawan?: Karyawan;
    shift?: Shift;
};
