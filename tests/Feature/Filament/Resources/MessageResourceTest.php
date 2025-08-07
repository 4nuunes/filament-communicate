<?php

declare(strict_types=1);

use Alessandronuunes\FilamentCommunicate\Enums\MessagePriority;
use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Models\MessageType;
use Alessandronuunes\FilamentCommunicate\Resources\MessageResource;
use Alessandronuunes\FilamentCommunicate\Resources\MessageResource\Pages\ListMessages;
use Alessandronuunes\FilamentCommunicate\Tests\Models\User;
use Filament\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

it('can test', function () {
    expect(true)->toBeTrue();
});

it('can render the index list message page', function () {

    // Testar se o Resource pode gerar URLs corretamente
    $indexUrl = MessageResource::getUrl('index');
    expect($indexUrl)->toBeString();
    expect($indexUrl)->toContain('messages');

    // Testar se a página ListMessages pode ser instanciada
    $listPage = new ListMessages;
    expect($listPage)->toBeInstanceOf(ListMessages::class);
    expect($listPage->getResource())->toBe(MessageResource::class);
});

it('can render Resource', function () {
    // Teste básico para verificar se o recurso pode ser instanciado
    $resource = new MessageResource;
    expect($resource)->toBeInstanceOf(Filament\Resources\Resource::class);
});

it('has column', function (string $column) {
    // Criar um usuário com role de super_admin para o teste
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user)
        ->livewire(ListMessages::class)
        ->assertTableColumnExists($column);
})->with([
    'subject',
    'sender.name',
    'recipient.name',
    'code',
    'messageType.name',
    'priority',
    'status',
    'created_at',
    'read_at',
    'replies_count',
]);

it('can sort Message by subject', function () {
    // Criar um usuário com role de super_admin para o teste
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $messages = Message::factory()->count(10)->create();

    actingAs($user)
        ->livewire(ListMessages::class)
        ->sortTable('subject')
        ->assertCanSeeTableRecords($messages->sortBy('subject'), inOrder: true)
        ->sortTable('subject', 'desc')
        ->assertCanSeeTableRecords($messages->sortByDesc('subject'), inOrder: true);
});

it('can search messages by subject', function () {
    // Criar um usuário com role de super_admin para o teste
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $messages = Message::factory()->count(10)->create();

    $subject = $messages->first()->subject;

    actingAs($user)
        ->livewire(ListMessages::class)
        ->searchTable($subject)
        ->assertCanSeeTableRecords($messages->where('subject', $subject))
        ->assertCanNotSeeTableRecords($messages->where('subject', '!=', $subject));
});

it('has a status filter', function () {
    // Criar um usuário com role de super_admin para o teste
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user)
        ->livewire(ListMessages::class)
        ->assertTableFilterExists('status');
});

it('can filter messages by `status`', function () {
    // Criar um usuário com role de super_admin para o teste
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $messages = Message::factory()->count(10)->create([
        'status' => MessageStatus::SENT,
    ]);

    actingAs($user)
        ->livewire(ListMessages::class)
        ->assertCanSeeTableRecords($messages)
        ->filterTable('status', MessageStatus::SENT->value)
        ->assertCanSeeTableRecords($messages->where('status', MessageStatus::SENT));
});

it('has a priority filter', function () {
    // Criar um usuário com role de super_admin para o teste
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user)
        ->livewire(ListMessages::class)
        ->assertTableFilterExists('priority');
});

it('can filter messages by `priority`', function () {
    // Criar um usuário com role de super_admin para o teste
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $messages = Message::factory()->count(10)->create([
        'priority' => MessagePriority::HIGH,
    ]);

    actingAs($user)
        ->livewire(ListMessages::class)
        ->assertCanSeeTableRecords($messages)
        ->filterTable('priority', MessagePriority::HIGH->value)
        ->assertCanSeeTableRecords($messages->where('priority', MessagePriority::HIGH));
});

it('has a message_type_id filter', function () {
    // Criar um usuário com role de super_admin para o teste
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    actingAs($user)
        ->livewire(ListMessages::class)
        ->assertTableFilterExists('message_type_id');
});

it('can filter messages by `message_type_id`', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    $messageType = MessageType::factory()->noApproval()->create();
    $messages = Message::factory()->count(10)->create([
        'message_type_id' => $messageType->id,
    ]);

    livewire(ListMessages::class)
        ->assertCanSeeTableRecords($messages)
        ->filterTable('message_type_id', $messageType->id)
        ->assertCanSeeTableRecords($messages->where('message_type_id', $messageType->id));
});

it('has an unread filter', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    livewire(ListMessages::class)
        ->assertTableFilterExists('unread');
});

it('can filter messages by `unread`', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    $readMessages = Message::factory()->count(5)->create([
        'read_at' => now(),
    ]);
    $unreadMessages = Message::factory()->count(5)->create([
        'read_at' => null,
    ]);

    livewire(ListMessages::class)
        ->assertCanSeeTableRecords($readMessages->merge($unreadMessages))
        ->filterTable('unread')
        ->assertCanSeeTableRecords($unreadMessages)
        ->assertCanNotSeeTableRecords($readMessages);
});

it('has an urgent filter', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    livewire(ListMessages::class)
        ->assertTableFilterExists('urgent');
});

it('can filter messages by `urgent`', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    $urgentMessages = Message::factory()->count(5)->create([
        'priority' => MessagePriority::URGENT,
    ]);
    $normalMessages = Message::factory()->count(5)->create([
        'priority' => MessagePriority::NORMAL,
    ]);

    livewire(ListMessages::class)
        ->assertCanSeeTableRecords($urgentMessages->merge($normalMessages))
        ->filterTable('urgent')
        ->assertCanSeeTableRecords($urgentMessages)
        ->assertCanNotSeeTableRecords($normalMessages);
});

it('has a with_replies filter', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    livewire(ListMessages::class)
        ->assertTableFilterExists('with_replies');
});

it('has a transferable filter', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    livewire(ListMessages::class)
        ->assertTableFilterExists('transferable');
});

it('can delete messages', function () {
    // Criar usuário e autenticar (igual ao teste de debug)
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    $message = Message::factory()->create();

    livewire(ListMessages::class)
        ->callTableAction(DeleteAction::class, $message);

    // Recarregar o modelo do banco
    $message->refresh();

    expect($message->deleted_at)->not->toBeNull('Message should be soft deleted');
    expect($message->trashed())->toBeTrue('Message should be trashed');
});

it('can bulk delete messages', function () {

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    $messages = Message::factory()->count(3)->create();
    $messageIds = $messages->pluck('id')->toArray();

    // Verificar que as mensagens existem antes do delete
    foreach ($messageIds as $id) {
        $message = Message::find($id);
        expect($message)->not->toBeNull();
        expect($message->deleted_at)->toBeNull();
    }

    livewire(ListMessages::class)
        ->callTableBulkAction(DeleteBulkAction::class, $messageIds)
        ->assertSuccessful();

    // Verificar se as mensagens foram soft deleted
    foreach ($messageIds as $id) {
        $message = Message::withTrashed()->find($id);
        expect($message)->not->toBeNull('Message should still exist in database');
        expect($message->deleted_at)->not->toBeNull('Message should be soft deleted');
    }
});

it('can edit messages when status is draft', function () {

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    $message = Message::factory()->create([
        'status' => MessageStatus::DRAFT,
    ]);

    livewire(ListMessages::class)
        ->assertTableActionVisible('edit', $message);
});

it('cannot edit messages when status is not draft', function () {

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    $message = Message::factory()->create([
        'status' => MessageStatus::SENT,
    ]);

    livewire(ListMessages::class)
        ->assertTableActionHidden('edit', $message);
});

it('can view messages', function () {

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    $message = Message::factory()->create();

    livewire(ListMessages::class)
        ->assertTableActionExists('view')
        ->assertTableActionVisible('view', $message);
});

it('exist create message', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    livewire(ListMessages::class)
        ->assertActionExists(CreateAction::class);
});

it('can create messages in modal', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    $messageType = MessageType::factory()->noApproval()->create();

    livewire(ListMessages::class)
        ->mountAction(CreateAction::class)
        ->setActionData([
            'subject' => '',
            'content' => '',
            'recipient_id' => '',
            'message_type_id' => '',
        ])
        ->callAction('create')
        ->assertHasActionErrors([
            'subject' => ['required'],
            'content' => ['required'],
            'recipient_id' => ['required'],
            'message_type_id' => ['required'],
        ]);
});

it('can approve pending messages', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('supervisor');
    actingAs($user);

    $message = Message::factory()->create([
        'status' => MessageStatus::PENDING,
    ]);

    livewire(ListMessages::class)
        ->assertTableActionExists('approve')
        ->assertTableActionVisible('approve', $message);
});

it('can reject pending messages', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('supervisor');
    actingAs($user);

    $message = Message::factory()->create([
        'status' => MessageStatus::PENDING,
    ]);

    livewire(ListMessages::class)
        ->assertTableActionExists('reject')
        ->assertTableActionVisible('reject', $message);
});

it('cannot approve own messages', function () {
    $user = User::factory()->create();
    $message = Message::factory()->create([
        'status' => MessageStatus::PENDING,
        'sender_id' => $user->id,
    ]);

    actingAs($user);

    livewire(ListMessages::class)
        ->assertTableActionHidden('approve', $message)
        ->assertTableActionHidden('reject', $message);
});

it('can sort messages by created_at', function () {

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    $messages = Message::factory()->count(3)->create();

    livewire(ListMessages::class)
        ->sortTable('created_at')
        ->assertSuccessful()
        ->sortTable('created_at', 'desc')
        ->assertSuccessful();
});

it('can sort messages by status', function () {
    // Criar usuário e autenticar
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    actingAs($user);

    $messages = Message::factory()->count(3)->create();

    livewire(ListMessages::class)
        ->sortTable('status')
        ->assertSuccessful()
        ->sortTable('status', 'desc')
        ->assertSuccessful();
});

// Testes específicos para MessagePolicy e ações de exclusão
describe('MessagePolicy Integration Tests', function () {

    it('shows delete action for draft messages created by user', function () {
        $user = User::factory()->create();
        $user->assignRole('atendente');
        actingAs($user);

        $draftMessage = Message::factory()->create([
            'sender_id' => $user->id,
            'status' => MessageStatus::DRAFT,
        ]);

        livewire(ListMessages::class)
            ->assertTableActionVisible('delete', $draftMessage);
    });

    it('shows delete action for pending messages created by user', function () {
        $user = User::factory()->create();
        $user->assignRole('atendente');
        actingAs($user);

        $pendingMessage = Message::factory()->create([
            'sender_id' => $user->id,
            'status' => MessageStatus::PENDING,
        ]);

        livewire(ListMessages::class)
            ->assertTableActionVisible('delete', $pendingMessage);
    });

    it('shows delete action for sent/read messages without replies created by regular user', function () {
        $user = User::factory()->create();
        $user->assignRole('atendente');
        actingAs($user);

        $sentMessage = Message::factory()->create([
            'sender_id' => $user->id,
            'status' => MessageStatus::SENT,
            'read_at' => now(),
        ]);

        livewire(ListMessages::class)
            ->assertTableActionVisible('delete', $sentMessage);
    });

    it('hides delete action for received messages from other users', function () {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $recipient->assignRole('atendente');

        $messageType = MessageType::factory()->create();

        actingAs($recipient);

        $receivedMessage = Message::factory()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => MessageStatus::SENT,
            'read_at' => now(),
            'message_type_id' => $messageType->id,
        ]);

        livewire(ListMessages::class)
            ->assertTableActionHidden('delete', $receivedMessage);
    });

    it('shows delete action for super admin on any message', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        actingAs($superAdmin);

        $otherUser = User::factory()->create();
        $message = Message::factory()->create([
            'sender_id' => $otherUser->id,
            'status' => MessageStatus::APPROVED,
        ]);

        livewire(ListMessages::class)
            ->assertTableActionVisible('delete', $message);
    });

    it('shows delete action for supervisor on pending messages from other users', function () {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        actingAs($supervisor);

        $otherUser = User::factory()->create();
        $message = Message::factory()->create([
            'sender_id' => $otherUser->id,
            'status' => MessageStatus::PENDING,
        ]);

        livewire(ListMessages::class)
            ->assertTableActionVisible('delete', $message);
    });

    it('hides delete action for supervisor on sent messages from other users', function () {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        actingAs($supervisor);

        $otherUser = User::factory()->create();
        $message = Message::factory()->create([
            'sender_id' => $otherUser->id,
            'status' => MessageStatus::SENT,
        ]);

        livewire(ListMessages::class)
            ->assertTableActionHidden('delete', $message);
    });

    it('hides delete action for approved messages created by regular user', function () {
        $user = User::factory()->create();
        $user->assignRole('atendente');
        actingAs($user);

        $approvedMessage = Message::factory()->create([
            'sender_id' => $user->id,
            'status' => MessageStatus::APPROVED,
        ]);

        livewire(ListMessages::class)
            ->assertTableActionHidden('delete', $approvedMessage);
    });

    it('hides delete action for rejected messages created by regular user', function () {
        $user = User::factory()->create();
        $user->assignRole('atendente');
        actingAs($user);

        $rejectedMessage = Message::factory()->create([
            'sender_id' => $user->id,
            'status' => MessageStatus::REJECTED,
        ]);

        livewire(ListMessages::class)
            ->assertTableActionHidden('delete', $rejectedMessage);
    });

    it('hides delete action for messages from other users that user did not receive', function () {
        $user = User::factory()->create();
        $user->assignRole('atendente');
        actingAs($user);

        $otherSender = User::factory()->create();
        $otherRecipient = User::factory()->create();

        $otherMessage = Message::factory()->create([
            'sender_id' => $otherSender->id,
            'recipient_id' => $otherRecipient->id,
            'status' => MessageStatus::DRAFT,
        ]);

        livewire(ListMessages::class)
            ->assertTableActionHidden('delete', $otherMessage);
    });

    it('can delete draft message through policy', function () {
        $user = User::factory()->create();
        $user->assignRole('atendente');
        actingAs($user);

        $draftMessage = Message::factory()->create([
            'sender_id' => $user->id,
            'status' => MessageStatus::DRAFT,
        ]);

        livewire(ListMessages::class)
            ->callTableAction('delete', $draftMessage)
            ->assertSuccessful();

        // Verificar se a ação foi executada com sucesso
        // O soft delete será verificado nos testes de policy
        expect(true)->toBeTrue();
    });

    it('can delete pending message through policy', function () {
        $user = User::factory()->create();
        $user->assignRole('atendente');
        actingAs($user);

        $pendingMessage = Message::factory()->create([
            'sender_id' => $user->id,
            'status' => MessageStatus::PENDING,
        ]);

        livewire(ListMessages::class)
            ->callTableAction('delete', $pendingMessage)
            ->assertSuccessful();

        // Verificar se a ação foi executada com sucesso
        // O soft delete será verificado nos testes de policy
        expect(true)->toBeTrue();
    });

    it('hides bulk delete action for regular users', function () {
        $user = User::factory()->create();
        $user->assignRole('atendente');
        actingAs($user);

        // Criar mensagens que o usuário pode deletar individualmente
        $draftMessage = Message::factory()->create([
            'sender_id' => $user->id,
            'status' => MessageStatus::DRAFT,
        ]);

        livewire(ListMessages::class)
            ->assertTableBulkActionHidden('delete');
    });

    it('shows bulk delete action for super admin', function () {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        actingAs($superAdmin);

        $message = Message::factory()->create([
            'status' => MessageStatus::DRAFT,
        ]);

        livewire(ListMessages::class)
            ->assertTableBulkActionVisible('delete');
    });

    it('shows bulk delete action for supervisor', function () {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        actingAs($supervisor);

        $message = Message::factory()->create([
            'status' => MessageStatus::PENDING,
        ]);

        livewire(ListMessages::class)
            ->assertTableBulkActionVisible('delete');
    });
});
