<?php

namespace App\Notifications;

use App\Models\ApprovalTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $approval;
    protected $module;


    /**
     * Create a new notification instance.
     */
    public function __construct(ApprovalTransaction $approval, $module)
    {
        $this->approval = $approval;
        $this->module = $module;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $transaction = $this->approval->transaction;

        return (new MailMessage)
            ->subject("New approval request: {$this->module} Document ({$transaction->code})")
            ->view('emails.approval-email', [
                'notifiable'  => $notifiable,
                'transaction' => $transaction,
                'module'      => $this->module,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
