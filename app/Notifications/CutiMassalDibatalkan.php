<?php

namespace App\Notifications;

use App\Models\PengajuanCuti;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke tiap karyawan saat batch Cuti Massal dibatalkan HRD.
 * Sengaja tanpa WhatsApp — kiriman ke puluhan nomor sekaligus membuat nomor
 * WhatsApp kantor di-suspend. Sengaja tidak ShouldQueue (lihat .ai/rules).
 */
class CutiMassalDibatalkan extends Notification
{
    public function __construct(
        public PengajuanCuti $pengajuanCuti,
        public ?string $catatan = null,
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

        $mail = (new MailMessage)
            ->subject('Cuti Massal Dibatalkan')
            ->line("Cuti massal {$pengajuan->jenisCuti->nama_jenis} pada tanggal {$pengajuan->tanggal_mulai->toDateString()} s/d {$pengajuan->tanggal_selesai->toDateString()} dibatalkan.")
            ->line("Saldo cuti Anda sebanyak {$pengajuan->jumlah_hari} hari telah dikembalikan.");

        if ($this->catatan) {
            $mail->line("Catatan: {$this->catatan}");
        }

        return $mail->action('Lihat Detail', url('/cuti/'.$pengajuan->id));
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
            'message' => "Cuti massal {$pengajuan->jenisCuti->nama_jenis} tanggal {$pengajuan->tanggal_mulai->toDateString()} dibatalkan, saldo cuti dikembalikan.",
        ];
    }
}
