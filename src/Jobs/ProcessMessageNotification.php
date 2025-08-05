<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Jobs;

use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Notifications\MessageNotification;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessMessageNotification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $timeout = 60;

    /**
     * Cria uma nova instância do job
     */
    public function __construct(
        public Message $message,
        public User $recipient,
        public string $notificationType = 'new_message'
    ) {
        // Configurar delay baseado na prioridade da mensagem
        if ($message->priority === 'urgent') {
            $this->delay(0); // Imediato
        } elseif ($message->priority === 'high') {
            $this->delay(now()->addSeconds(30)); // 30 segundos
        } else {
            $this->delay(now()->addMinutes(2)); // 2 minutos
        }
    }

    /**
     * Executa o job
     */
    public function handle(): void
    {
        try {
            // Verificar se o destinatário ainda existe e está ativo
            if (! $this->recipient || $this->recipient->trashed()) {
                Log::warning('Attempt to notify inactive user', [
                    'message_id' => $this->message->id,
                    'recipient_id' => $this->recipient?->id,
                ]);

                return;
            }

            // Verificar se a mensagem ainda existe
            if (! $this->message || $this->message->trashed()) {
                Log::warning('Attempt to notify about non-existent message', [
                    'message_id' => $this->message?->id,
                    'recipient_id' => $this->recipient->id,
                ]);

                return;
            }

            // Enviar notificação
            if (method_exists($this->recipient, 'notify')) {
                $this->recipient->notify(
                    new MessageNotification($this->message, $this->notificationType)
                );
            }

            Log::info('Message notification processed successfully', [
                'message_id' => $this->message->id,
                'recipient_id' => $this->recipient->id,
                'notification_type' => $this->notificationType,
            ]);

        } catch (Exception $e) {
            Log::error('Error processing message notification', [
                'message_id' => $this->message->id,
                'recipient_id' => $this->recipient->id,
                'notification_type' => $this->notificationType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Manipula falha do job
     */
    public function failed(Exception $exception): void
    {
        Log::error('Message notification job failed permanently', [
            'message_id' => $this->message->id,
            'recipient_id' => $this->recipient->id,
            'notification_type' => $this->notificationType,
            'error' => $exception->getMessage(),
        ]);
    }
}
