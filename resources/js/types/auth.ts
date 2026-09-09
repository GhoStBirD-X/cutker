export type JenisKelamin = 'laki_laki' | 'perempuan';
export type TipeKaryawan = 'tetap' | 'kontrak';

export type Karyawan = {
    id: number;
    nip: string;
    nama: string;
    email: string;
    no_hp: string | null;
    jenis_kelamin: JenisKelamin;
    departemen_id: number;
    jabatan_id: number;
    tanggal_masuk: string;
    status: 'aktif' | 'nonaktif';
    tipe_karyawan: TipeKaryawan;
    tanggal_akhir_kontrak: string | null;
    departemen?: { id: number; nama_departemen: string; kode: string };
    jabatan?: { id: number; nama_jabatan: string };
};

export type User = {
    id: number;
    karyawan_id: number | null;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    karyawan?: Karyawan | null;
    [key: string]: unknown;
};

export type Role =
    | 'karyawan'
    | 'kepala_bagian'
    | 'koordinator_shift'
    | 'hrd'
    | 'manager'
    | 'admin';

export type Auth = {
    user: User;
    roles: Role[];
    permissions: string[];
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
