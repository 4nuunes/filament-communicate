<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Services;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Helpers\MessageNotificationHelper;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Exception;
use Illuminate\Support\Facades\Log;

class MessageDeliveryService
{
    /**
     * Entrega a mensagem (envia notificação)
     */
    public function deliverMessage(Message $message): void
    {
        try {
            // Atualizar timestamp de entrega
            $message->update([
                'delivered_at' => now(),
            ]);

            // Carregar relacionamento se necessário
            if (! $message->relationLoaded('messageType')) {
                $message->load('messageType');
            }

            // Verificar se é uma resposta (tem parent_id)
            $isReply = ! is_null($message->parent_id);

            Log::info('Delivering message', [
                'message_id' => $message->id,
                'is_reply' => $isReply,
                'requires_approval' => $message->messageType->requires_approval,
                'status' => $message->status->value,
            ]);

            // Para respostas, sempre notificar o destinatário (respostas não precisam de aprovação)
            if ($isReply) {
                MessageNotificationHelper::notifyNewMessage($message->recipient, $message);

                Log::info('Reply delivered successfully', [
                    'message_id' => $message->id,
                    'recipient_id' => $message->recipient_id,
                    'delivered_at' => $message->delivered_at,
                ]);
            }
            // Para mensagens originais, notificar apenas se não precisar de aprovação ou já foi aprovada
            elseif (! $message->messageType->requires_approval || $message->status === MessageStatus::SENT) {
                MessageNotificationHelper::notifyNewMessage($message->recipient, $message);

                Log::info('Original message delivered successfully', [
                    'message_id' => $message->id,
                    'recipient_id' => $message->recipient_id,
                    'delivered_at' => $message->delivered_at,
                ]);
            } else {
                Log::info('Original message not delivered - awaiting approval', [
                    'message_id' => $message->id,
                    'status' => $message->status->value,
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error delivering message', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Verifica se a mensagem pode ser entregue
     */
    public function canDeliver(Message $message): bool
    {
        // Carregar relacionamento se necessário
        if (! $message->relationLoaded('messageType')) {
            $message->load('messageType');
        }

        // Mensagem pode ser entregue se:
        // 1. Não requer aprovação OU
        // 2. Já foi aprovada (status SENT)
        return ! $message->messageType->requires_approval || $message->status === MessageStatus::SENT;
    }

    /**
     * Entrega mensagem em lote
     */
    public function deliverBatch(array $messages): array
    {
        $results = [
            'delivered' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($messages as $message) {
            try {
                if ($this->canDeliver($message)) {
                    $this->deliverMessage($message);
                    $results['delivered']++;
                } else {
                    $results['errors'][] = [
                        'message_id' => $message->id,
                        'reason' => 'Mensagem não pode ser entregue - aguardando aprovação',
                    ];
                    $results['failed']++;
                }
            } catch (Exception $e) {
                $results['errors'][] = [
                    'message_id' => $message->id,
                    'reason' => $e->getMessage(),
                ];
                $results['failed']++;
            }
        }

        Log::info('Batch delivery completed', $results);

        return $results;
    }

    /**
     * Reentrega uma mensagem (para casos de falha)
     */
    public function redeliverMessage(Message $message): void
    {
        if (! $this->canDeliver($message)) {
            throw new Exception(__('filament-communicate::default.exceptions.cannot_redeliver_invalid_status'));
        }

        // Resetar timestamp de entrega para forçar nova entrega
        $message->update(['delivered_at' => null]);

        $this->deliverMessage($message);

        Log::info('Message redelivered', [
            'message_id' => $message->id,
        ]);
    }

    /**
     * Marca mensagem como entregue sem enviar notificação
     * Útil para casos especiais ou migrações
     */
    public function markAsDelivered(Message $message): void
    {
        $message->update([
            'delivered_at' => now(),
        ]);

        Log::info('Message marked as delivered (without notification)', [
            'message_id' => $message->id,
        ]);
    }

    /**
     * Obtém estatísticas de entrega
     */
    public function getDeliveryStats(): array
    {
        return [
            'total_delivered' => Message::whereNotNull('delivered_at')->count(),
            'pending_delivery' => Message::whereNull('delivered_at')
                ->where('status', MessageStatus::SENT)
                ->count(),
            'failed_delivery' => Message::whereNull('delivered_at')
                ->where('status', MessageStatus::SENT)
                ->where('created_at', '<', now()->subHours(1))
                ->count(),
        ];
    }
}
