<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Jobs;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Notifications\MessageNotification;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeliverApprovedMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 120;

    /**
     * Cria uma nova instância do job
     */
    public function __construct(
        public Message $message
    ) {
        // Configurar prioridade da queue baseada na prioridade da mensagem
        if ($message->priority === 'urgent') {
            $this->queue = 'urgent';
        } elseif ($message->priority === 'high') {
            $this->queue = 'high';
        } else {
            $this->queue = 'default';
        }
    }

    /**
     * Executa o job
     */
    public function handle(): void
    {
        try {
            // Verificar se a mensagem existe e foi aprovada
            if (! $this->message || $this->message->trashed()) {
                Log::warning('Attempt to deliver non-existent message', [
                    'message_id' => $this->message?->id,
                ]);

                return;
            }

            // Verificar se a mensagem está no status correto
            if ($this->message->status !== MessageStatus::SENT) {
                Log::warning('Attempt to deliver message with incorrect status', [
                    'message_id' => $this->message->id,
                    'current_status' => $this->message->status->value,
                ]);

                return;
            }

            // Verificar se o destinatário existe e está ativo
            if (! $this->message->recipient || $this->message->recipient->trashed()) {
                Log::error('Message recipient not found or inactive', [
                    'message_id' => $this->message->id,
                    'recipient_id' => $this->message->recipient_id,
                ]);

                return;
            }

            // Entregar a mensagem
            $this->deliverMessage();

            Log::info('Approved message delivered successfully', [
                'message_id' => $this->message->id,
                'recipient_id' => $this->message->recipient_id,
                'sender_id' => $this->message->sender_id,
            ]);

        } catch (Exception $e) {
            Log::error('Error delivering approved message', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Entrega a mensagem para o destinatário
     */
    private function deliverMessage(): void
    {
        // Notificar o destinatário
        if (method_exists($this->message->recipient, 'notify')) {
            $this->message->recipient->notify(
                new MessageNotification($this->message, 'new_message')
            );
        }

        // Atualizar timestamp de entrega
        $this->message->update([
            'delivered_at' => now(),
        ]);

        // Se for uma mensagem urgente, enviar notificação adicional
        if ($this->message->priority === 'urgent') {
            // Pode implementar notificação por SMS, push, etc.
            Log::info('Urgent message delivered', [
                'message_id' => $this->message->id,
            ]);
        }
    }

    /**
     * Manipula falha do job
     */
    public function failed(Exception $exception): void
    {
        Log::error('Message delivery job failed permanently', [
            'message_id' => $this->message->id,
            'error' => $exception->getMessage(),
        ]);

        // Marcar mensagem como falha na entrega
        $this->message->update([
            'status' => MessageStatus::FAILED,
            'failure_reason' => $exception->getMessage(),
        ]);
    }
}
