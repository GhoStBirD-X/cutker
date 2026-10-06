/** Tanggal hari ini (zona waktu lokal browser) dalam format YYYY-MM-DD untuk atribut `min` input date. */
export function tanggalHariIni(): string {
    const sekarang = new Date();
    const bulan = String(sekarang.getMonth() + 1).padStart(2, '0');
    const hari = String(sekarang.getDate()).padStart(2, '0');

    return `${sekarang.getFullYear()}-${bulan}-${hari}`;
}

export type RincianHariKerja = {
    hariKalender: number;
    hariLibur: number;
    hariKerja: number;
};

/**
 * Cermin dari HariLiburService::hitungHariLibur() di backend: Sabtu, Minggu,
 * dan tanggal libur terdaftar tidak dihitung sebagai hari cuti. Hanya untuk
 * pratinjau — angka final tetap dihitung server saat pengajuan disimpan.
 */
export function hitungHariKerja(
    mulai: string,
    selesai: string,
    tanggalLibur: string[],
): RincianHariKerja | null {
    if (!mulai || !selesai || selesai < mulai) {
        return null;
    }

    const libur = new Set(tanggalLibur);
    const [tahun, bulan, hari] = mulai.split('-').map(Number);
    const tanggal = new Date(Date.UTC(tahun, bulan - 1, hari));
    let iso = mulai;
    let hariKalender = 0;
    let hariLibur = 0;

    while (iso <= selesai) {
        hariKalender++;

        const hariDalamMinggu = tanggal.getUTCDay();

        if (hariDalamMinggu === 0 || hariDalamMinggu === 6 || libur.has(iso)) {
            hariLibur++;
        }

        tanggal.setUTCDate(tanggal.getUTCDate() + 1);
        iso = tanggal.toISOString().slice(0, 10);
    }

    return { hariKalender, hariLibur, hariKerja: hariKalender - hariLibur };
}
