<?php

namespace App\Notifications;

use App\Models\PengajuanCuti;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke HRD saat periode saldo karyawan diperbarui dan Cuti Massal
 * miliknya yang ditampung kini bisa diproses dari halaman Cuti Massal.
 * Sengaja tanpa WhatsApp & tidak ShouldQueue (lihat .ai/rules).
 */
class PenampunganCutiMassalSiapDiproses extends Notification
{
    public function __construct(
        public PengajuanCuti $pengajuanCuti,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pengajuan = $this->pengajuanCuti;

        return (new MailMessage)
            ->subject('Cuti Massal yang Ditampung Siap Diproses')
            ->line("Periode saldo {$pengajuan->jenisCuti->nama_jenis} {$pengajuan->karyawan->nama} sudah diperbarui.")
            ->line("Cuti massal tanggal {$pengajuan->tanggal_mulai->toDateString()} s/d {$pengajuan->tanggal_selesai->toDateString()} miliknya yang ditampung sekarang bisa diproses.")
            ->action('Buka Cuti Massal', url('/cuti/massal/'.$pengajuan->cuti_massal_id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $pengajuan = $this->pengajuanCuti;

        return [
            'pengajuan_cuti_id' => $pengajuan->id,
            'cuti_massal_id' => $pengajuan->cuti_massal_id,
            'karyawan_nama' => $pengajuan->karyawan->nama,
            'tanggal_mulai' => $pengajuan->tanggal_mulai->toDateString(),
            'tanggal_selesai' => $pengajuan->tanggal_selesai->toDateString(),
            'message' => "Cuti massal {$pengajuan->karyawan->nama} tanggal {$pengajuan->tanggal_mulai->toDateString()} yang ditampung siap diproses di halaman Cuti Massal.",
        ];
    }
}
