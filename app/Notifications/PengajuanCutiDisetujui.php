<?php

namespace App\Notifications;

use App\Models\PengajuanCuti;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PengajuanCutiDisetujui extends Notification implements ShouldQueue
{
    use Queueable;

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
