<?php

declare(strict_types=1);

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Enums\MessagePriority;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Models\MessageType;
use Alessandronuunes\FilamentCommunicate\Resources\MessageResource\Pages\ListMessages;
use Alessandronuunes\FilamentCommunicate\Tests\Models\User;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

describe('MessageResource Advanced Policy Tests', function () {
    
    describe('Restore Actions Tests', function () {
        
        it('shows restore bulk action for super admin', function () {
            $superAdmin = User::factory()->create(['name' => 'Super Admin']);
            $superAdmin->assignRole('super_admin');
            
            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);
            
            actingAs($superAdmin);
            
            livewire(ListMessages::class)
                ->assertTableBulkActionExists(RestoreBulkAction::class);
        });
        
        it('shows restore bulk action for supervisor', function () {
            $supervisor = User::factory()->create(['name' => 'Supervisor']);
            $supervisor->assignRole('supervisor');
            
            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);
            
            // Criar uma mensagem excluída para que a ação de restauração seja visível
            $deletedMessage = Message::factory()->create([
                'sender_id' => $supervisor->id,
                'status' => MessageStatus::DRAFT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(),
            ]);
            
            actingAs($supervisor);
            
            livewire(ListMessages::class)
                ->filterTable('trashed', 'only')
                ->assertTableBulkActionExists(RestoreBulkAction::class);
        });
        
        it('hides restore bulk action for regular user', function () {
            $regularUser = User::factory()->create(['name' => 'Usuário Regular']);
            $regularUser->assignRole('atendente');
            
            actingAs($regularUser);
            
            livewire(ListMessages::class)
                ->assertTableBulkActionHidden(RestoreBulkAction::class);
        });
        
        it('can restore deleted messages as super admin', function () {
            $superAdmin = User::factory()->create(['name' => 'Super Admin']);
            $superAdmin->assignRole('super_admin');
            
            $regularUser = User::factory()->create(['name' => 'Usuário Regular']);
            $regularUser->assignRole('atendente');
            
            $anotherUser = User::factory()->create(['name' => 'Outro Usuário']);
            $anotherUser->assignRole('atendente');
            
            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);
            
            actingAs($superAdmin);
            
            $deletedMessage = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::DRAFT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(),
            ]);
            
            livewire(ListMessages::class)
                ->filterTable('trashed', 'only')
                ->callTableBulkAction(RestoreBulkAction::class, [$deletedMessage->id])
                ->assertSuccessful();
            
            // Verificar se a mensagem foi restaurada
            $deletedMessage->refresh();
            expect($deletedMessage->deleted_at)->toBeNull();
        });
    });
    
    describe('Force Delete Actions Tests', function () {
        
        it('shows force delete bulk action for super admin', function () {
            $superAdmin = User::factory()->create(['name' => 'Super Admin']);
            $superAdmin->assignRole('super_admin');
            
            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);
            
            // Criar uma mensagem excluída para que a ação de exclusão forçada seja visível
            $deletedMessage = Message::factory()->create([
                'sender_id' => $superAdmin->id,
                'status' => MessageStatus::DRAFT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(),
            ]);
            
            actingAs($superAdmin);
            
            livewire(ListMessages::class)
                ->filterTable('trashed', 'only')
                ->assertTableBulkActionExists(ForceDeleteBulkAction::class);
        });
        
        it('shows force delete bulk action for supervisor', function () {
            $supervisor = User::factory()->create(['name' => 'Supervisor']);
            $supervisor->assignRole('supervisor');
            
            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);
            
            // Criar uma mensagem excluída para que a ação de exclusão forçada seja visível
            $deletedMessage = Message::factory()->create([
                'sender_id' => $supervisor->id,
                'status' => MessageStatus::DRAFT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(),
            ]);
            
            actingAs($supervisor);
            
            livewire(ListMessages::class)
                ->filterTable('trashed', 'only')
                ->assertTableBulkActionExists(ForceDeleteBulkAction::class);
         });
        
        it('hides force delete bulk action for regular user', function () {
            $regularUser = User::factory()->create(['name' => 'Usuário Regular']);
            $regularUser->assignRole('atendente');
            
            actingAs($regularUser);
            
            livewire(ListMessages::class)
                ->assertTableBulkActionHidden(ForceDeleteBulkAction::class);
        });
        
        it('can force delete messages as super admin', function () {
            $superAdmin = User::factory()->create(['name' => 'Super Admin']);
            $superAdmin->assignRole('super_admin');
            
            $regularUser = User::factory()->create(['name' => 'Usuário Regular']);
            $regularUser->assignRole('atendente');
            
            $anotherUser = User::factory()->create(['name' => 'Outro Usuário']);
            $anotherUser->assignRole('atendente');
            
            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);
            
            actingAs($superAdmin);
            
            $deletedMessage = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::SENT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(),
            ]);
            
            $messageId = $deletedMessage->id;
            
            livewire(ListMessages::class)
                ->filterTable('trashed', 'only')
                ->callTableBulkAction(ForceDeleteBulkAction::class, [$messageId])
                ->assertSuccessful();
            
            // Verificar se a mensagem foi permanentemente deletada
            $message = Message::withTrashed()->find($messageId);
            expect($message)->toBeNull();
        });
    });
    
    describe('Complex Scenarios Tests', function () {
        
        it('ensures super admin can delete draft messages', function () {
            $superAdmin = User::factory()->create(['name' => 'Super Admin']);
            $superAdmin->assignRole('super_admin');
            
            $regularUser = User::factory()->create(['name' => 'Usuário Regular']);
            $regularUser->assignRole('atendente');
            
            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);
            
            actingAs($superAdmin);
            
            $draftMessage = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'status' => MessageStatus::DRAFT,
                'message_type_id' => $messageType->id,
            ]);
            
            livewire(ListMessages::class)
                ->assertTableActionVisible('delete', $draftMessage);
        });
        
        it('ensures supervisor can delete pending messages', function () {
            $supervisor = User::factory()->create(['name' => 'Supervisor']);
            $supervisor->assignRole('supervisor');
            
            $regularUser = User::factory()->create(['name' => 'Usuário Regular']);
            $regularUser->assignRole('atendente');
            
            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);
            
            actingAs($supervisor);
            
            $pendingMessage = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'status' => MessageStatus::PENDING,
                'message_type_id' => $messageType->id,
            ]);
            
            livewire(ListMessages::class)
                ->assertTableActionVisible('delete', $pendingMessage);
        });
        
        it('respects policy for messages with different priorities', function () {
            $regularUser = User::factory()->create(['name' => 'Usuário Regular']);
            $regularUser->assignRole('atendente');
            
            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);
            
            actingAs($regularUser);
            
            $urgentDraft = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'status' => MessageStatus::DRAFT,
                'priority' => MessagePriority::URGENT,
                'message_type_id' => $messageType->id,
            ]);
            
            $normalSent = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'status' => MessageStatus::SENT,
                'priority' => MessagePriority::NORMAL,
                'read_at' => now(),
                'message_type_id' => $messageType->id,
            ]);
            
            livewire(ListMessages::class)
                ->assertTableActionVisible('delete', $urgentDraft)
                ->assertTableActionVisible('delete', $normalSent); // Pode deletar pois é mensagem própria sem respostas
        });
    });
});