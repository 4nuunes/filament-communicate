<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Jobs;

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBatchMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Message $originalMessage
    ) {}

    public function handle(): void
    {
        $recipients = $this->originalMessage->getBatchRecipients();
        $primaryRecipient = $this->originalMessage->recipient_id;

        // Processar apenas os destinatários restantes (excluir o principal)
        $remainingRecipients = array_filter(
            $recipients,
            fn ($id) => $id != $primaryRecipient
        );

        foreach ($remainingRecipients as $recipientId) {
            try {
                // Criar nova mensagem para cada destinatário
                $newMessage = $this->originalMessage->replicate();

                $newMessage->recipient_id = $recipientId;
                $newMessage->status = MessageStatus::SENT;
                // Gerar código único baseado no original
                $newMessage->code = $this->originalMessage->code.'-'.$recipientId;

                // Limpar dados de lote para evitar recursão
                $newMessage->batch_recipients = null;

                // Garantir que custom_data seja um array antes do merge
                $currentCustomData = $newMessage->custom_data ?? [];
                if (! is_array($currentCustomData)) {
                    $currentCustomData = [];
                }

                $newMessage->custom_data = array_merge(
                    $currentCustomData,
                    [
                        'is_batch_copy' => true,
                        'original_message_id' => $this->originalMessage->id,
                        'batch_processed' => true,
                    ]
                );

                // Salvar a mensagem primeiro
                $newMessage->save();

                // Sincronizar as tags da mensagem original
                if ($this->originalMessage->tags()->exists()) {
                    $originalTags = $this->originalMessage->tags()->pluck('tags.id')->toArray();
                    $newMessage->tags()->sync($originalTags);
                }

            } catch (\Exception $e) {
                Log::error('Failed to create batch message', [
                    'original_id' => $this->originalMessage->id,
                    'recipient_id' => $recipientId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
