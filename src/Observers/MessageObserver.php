<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Observers;

use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Services\MessageService;
use Illuminate\Support\Facades\Log;

class MessageObserver
{
    public function __construct(
        private MessageService $messageService
    ) {
    }

    public function creating(Message $message): void
    {
        //
        $message->current_recipient_id = $message->recipient_id;
    }

    /**
     * Handle the Message "created" event.
     * Verifica se a mensagem precisa de aprovação e ajusta o status
     */
    public function created(Message $message): void
    {
        try {

            $this->messageService->handleMessageCreated($message);

        } catch (\Exception $e) {
            Log::error('Error in MessageObserver::created', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updating(Message $message): void
    {
        //
    }

    /**
     * Handle the Message "updated" event.
     */
    public function updated(Message $message): void
    {
        //

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
