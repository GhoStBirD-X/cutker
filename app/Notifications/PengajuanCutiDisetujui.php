<?php

namespace App\Notifications;

use App\Models\PengajuanCuti;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sengaja tidak ShouldQueue — dikirim sinkron supaya tidak diam-diam hilang
 * kalau queue worker tidak berjalan (lihat .ai/rules untuk detail).
 */
class PengajuanCutiDisetujui extends Notification
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
            ->subject('Pengajuan Cuti Anda Disetujui')
            ->line("Pengajuan {$pengajuan->jenisCuti->nama_jenis} Anda selama {$pengajuan->jumlah_hari} hari telah disetujui.")
            ->line("Tanggal: {$pengajuan->tanggal_mulai->toDateString()} s/d {$pengajuan->tanggal_selesai->toDateString()}")
            ->action('Lihat Detail', url('/cuti/'.$pengajuan->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $pengajuan = $this->pengajuanCuti;

        return [
            'pengajuan_cuti_id' => $pengajuan->id,
            'jenis_cuti' => $pengajuan->jenisCuti->nama_jenis,
            'tanggal_mulai' => $pengajuan->tanggal_mulai->toDateString(),
            'tanggal_selesai' => $pengajuan->tanggal_selesai->toDateString(),
            'message' => "Pengajuan cuti {$pengajuan->jenisCuti->nama_jenis} Anda telah disetujui.",
        ];
    }
}
