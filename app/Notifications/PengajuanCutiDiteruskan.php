<?php

namespace App\Notifications;

use App\Models\Karyawan;
use App\Models\PengajuanCuti;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\ApprovalService;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Dikirim ke pengaju setiap kali satu level (Kepala Bagian/HRD) menyetujui
 * dan pengajuan diteruskan ke level berikutnya. Persetujuan final memakai
 * PengajuanCutiDisetujui.
 *
 * Sengaja tidak ShouldQueue — dikirim sinkron supaya tidak diam-diam hilang
 * kalau queue worker tidak berjalan (lihat .ai/rules untuk detail).
 */
class PengajuanCutiDiteruskan extends Notification
{
    public function __construct(
        public PengajuanCuti $pengajuanCuti,
        public Karyawan $approver,
        public int $levelDisetujui,
        public ?string $catatan = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', WhatsAppChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $pengajuan = $this->pengajuanCuti;

        $mail = (new MailMessage)
            ->subject('Pengajuan Cuti Anda Disetujui '.$this->namaLevelDisetujui())
            ->line("Pengajuan {$pengajuan->jenisCuti->nama_jenis} Anda selama {$pengajuan->jumlah_hari} hari telah disetujui {$this->namaLevelDisetujui()} ({$this->approver->nama}).")
            ->line("Tanggal: {$pengajuan->tanggal_mulai->toDateString()} s/d {$pengajuan->tanggal_selesai->toDateString()}")
            ->line("Saat ini menunggu persetujuan {$this->namaLevelBerikutnya()}.");

        if ($this->catatan) {
            $mail->line("Catatan: {$this->catatan}");
        }

        return $mail->action('Lihat Detail', url('/cuti/'.$pengajuan->id));
    }

    public function toWhatsApp(object $notifiable): string
    {
        $pengajuan = $this->pengajuanCuti;

        $pesan = "Update Pengajuan Cuti\n\n".
            "Pengajuan {$pengajuan->jenisCuti->nama_jenis} Anda ({$pengajuan->tanggal_mulai->toDateString()} s/d {$pengajuan->tanggal_selesai->toDateString()}) ".
            "telah *disetujui {$this->namaLevelDisetujui()}* ({$this->approver->nama}).\n".
            "Saat ini menunggu persetujuan *{$this->namaLevelBerikutnya()}*.\n";

        if ($this->catatan) {
            $pesan .= "Catatan: {$this->catatan}\n";
        }

        return $pesan."\nLihat detail: ".url('/cuti/'.$pengajuan->id);
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
            'level_disetujui' => $this->levelDisetujui,
            'catatan' => $this->catatan,
            'message' => "Pengajuan cuti {$pengajuan->jenisCuti->nama_jenis} Anda disetujui {$this->namaLevelDisetujui()}, menunggu persetujuan {$this->namaLevelBerikutnya()}.",
        ];
    }

    protected function namaLevelDisetujui(): string
    {
        return ApprovalService::namaLevel($this->levelDisetujui);
    }

    protected function namaLevelBerikutnya(): string
    {
        return ApprovalService::namaLevel($this->levelDisetujui + 1);
    }
}
