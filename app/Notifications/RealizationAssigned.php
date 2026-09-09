<?php

namespace App\Notifications;

use App\Models\WorkRealization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RealizationAssigned extends Notification
{
    use Queueable;

    public function __construct(public WorkRealization $realization) {}

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
            'title' => 'Tugas baru ditugaskan',
            'body' => 'Anda ditugaskan mengerjakan '.($this->realization->product_name_snapshot ?? 'realisasi').' pada '.($this->realization->work_date?->format('d/m/Y') ?? 'tanggal yang belum ditentukan').'.',
            'url' => route('realizations.show', $this->realization),
        ];
    }
}
