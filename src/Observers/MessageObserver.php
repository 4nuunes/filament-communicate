<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Observers;

use Illuminate\Support\Facades\Log;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Services\MessageService;
use Alessandronuunes\FilamentCommunicate\Jobs\ProcessBatchMessageJob;

class MessageObserver
{
    public function __construct(
        private MessageService $messageService
    ) {}

    public function creating(Message $message): void
    {
        if (!empty($message->batch_recipients) && is_array($message->batch_recipients)) {
            $primaryRecipient = $message->batch_recipients[0];
            $message->recipient_id = $primaryRecipient;
            $message->current_recipient_id = $message->recipient_id;
            
            $message->custom_data = array_merge(
                $message->custom_data ?? [],
                [
                    'is_batch_message' => true,
                    'batch_processed' => false,
                    'total_recipients' => count($message->batch_recipients)
                ]
            );
        }
    
        if (! $message->parent_id && ! $message->code) {
            $message->code = Message::generateCode();
        }
    }

    /**
     * Handle the Message "created" event.
     * Processa lote apenas se não for rascunho
     */
    public function created(Message $message): void
    {
        try {
            // Só processar se tem destinatários em lote, não foi processado E não é rascunho
            if ($message->hasBatchRecipients() && 
                !data_get($message->custom_data, 'batch_processed', false) &&
                $message->status !== MessageStatus::DRAFT->value) {
                
                // Marcar como processado IMEDIATAMENTE para evitar loops
                $message->update([
                    'custom_data' => array_merge(
                        $message->custom_data ?? [],
                        ['batch_processed' => true]
                    )
                ]);
                
                // Disparar job para os demais destinatários
                ProcessBatchMessageJob::dispatch($message);
            }
            
            $this->messageService->handleMessageCreated($message);
        } catch (\Exception $e) {
            Log::error('Error in MessageObserver::created', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updating(Message $message): void
    {
        //
    }

    /**
     * Handle the Message "updated" event.
     * Processa lote APENAS quando sai do status DRAFT
     */
    public function updated(Message $message): void
    {
        try {
            // Verificar se a mensagem saiu do status DRAFT
            $originalStatus = $message->getOriginal('status');
            $currentStatus = $message->status;
            
            // APENAS processar quando sai do DRAFT para outro status
            if ($originalStatus === MessageStatus::DRAFT->value && 
                $currentStatus !== MessageStatus::DRAFT->value &&
                $message->hasBatchRecipients() && 
                !data_get($message->custom_data, 'batch_processed', false)) {
                
                // Marcar como processado IMEDIATAMENTE para evitar loops
                $message->update([
                    'custom_data' => array_merge(
                        $message->custom_data ?? [],
                        ['batch_processed' => true]
                    )
                ]);
                
                // Disparar job para os demais destinatários
                ProcessBatchMessageJob::dispatch($message);
            }
        } catch (\Exception $e) {
            Log::error('Error in MessageObserver::updated', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Message "deleted" event.
     */
    public function deleted(Message $message): void
    {
        //
    }

    /**
     * Handle the Message "restored" event.
     */
    public function restored(Message $message): void
    {
        //
    }

    /**
     * Handle the Message "force deleted" event.
     */
    public function forceDeleted(Message $message): void
    {
        //
    }
}
