<?php

namespace App\Notifications;

use App\Models\WorkRealization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RealizationSubmitted extends Notification
{
    use Queueable;

    public function __construct(public WorkRealization $realization, public string $submittedByName) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Hasil pekerjaan dikirim',
            'body' => $this->submittedByName.' mengirim hasil kerja untuk '.($this->realization->product_name_snapshot ?? 'realisasi').'.',
            'url' => route('realizations.show', $this->realization),
        ];
    }
}
