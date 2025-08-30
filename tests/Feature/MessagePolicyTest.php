<?php

declare(strict_types=1);

use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Models\MessageType;
use Alessandronuunes\FilamentCommunicate\Policies\MessagePolicy;
use Alessandronuunes\FilamentCommunicate\Tests\Models\User;

describe('MessagePolicy', function () {

    describe('delete method', function () {

        it('allows super admin to delete any message', function () {
            $policy = new MessagePolicy();
            $superAdmin = User::factory()->create(['name' => 'Super Admin']);
            $superAdmin->assignRole('super_admin');
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::SENT,
                'message_type_id' => $messageType->id,
            ]);

            $result = $policy->delete($superAdmin, $message);

            expect($result)->toBeTrue();
        });

        it('allows supervisor to delete pending messages from other users', function () {
            $policy = new MessagePolicy();
            $supervisor = User::factory()->create(['name' => 'Supervisor']);
            $supervisor->assignRole('supervisor');
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::PENDING,
                'message_type_id' => $messageType->id,
            ]);

            $result = $policy->delete($supervisor, $message);

            expect($result)->toBeTrue();
        });

        it('denies supervisor to delete sent messages from other users', function () {
            $policy = new MessagePolicy();
            $supervisor = User::factory()->create(['name' => 'Supervisor']);
            $supervisor->assignRole('supervisor');
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::SENT,
                'message_type_id' => $messageType->id,
            ]);

            $result = $policy->delete($supervisor, $message);

            expect($result)->toBeFalse();
        });

        it('allows user to delete their own draft messages', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::DRAFT,
                'message_type_id' => $messageType->id,
            ]);

            $result = $policy->delete($regularUser, $message);

            expect($result)->toBeTrue();
        });

        it('allows user to delete their own pending messages', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::PENDING,
                'message_type_id' => $messageType->id,
            ]);

            $result = $policy->delete($regularUser, $message);

            expect($result)->toBeTrue();
        });

        it('allows user to delete sent/read messages they created without replies', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::SENT,
                'read_at' => now(),
                'message_type_id' => $messageType->id,
            ]);

            $result = $policy->delete($regularUser, $message);

            expect($result)->toBeTrue();
        });

        it('denies user to delete approved messages they created', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::APPROVED,
                'message_type_id' => $messageType->id,
            ]);

            $result = $policy->delete($regularUser, $message);

            expect($result)->toBeFalse();
        });

        it('denies user to delete rejected messages they created', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::REJECTED,
                'message_type_id' => $messageType->id,
            ]);

            $result = $policy->delete($regularUser, $message);

            expect($result)->toBeFalse();
        });

        it('denies user to delete messages from other users', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $anotherUser->id,
                'recipient_id' => $regularUser->id,
                'status' => MessageStatus::DRAFT,
                'message_type_id' => $messageType->id,
            ]);

            $result = $policy->delete($regularUser, $message);

            expect($result)->toBeFalse();
        });

        it('denies user to delete received messages from other users', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $anotherUser->id,
                'recipient_id' => $regularUser->id,
                'status' => MessageStatus::SENT,
                'read_at' => now(),
                'message_type_id' => $messageType->id,
            ]);

            $result = $policy->delete($regularUser, $message);

            expect($result)->toBeFalse();
        });
    });

    describe('forceDelete method', function () {

        it('allows super admin to force delete any message', function () {
            $policy = new MessagePolicy();
            $superAdmin = User::factory()->create(['name' => 'Super Admin']);
            $superAdmin->assignRole('super_admin');
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::SENT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(), // Soft deleted
            ]);

            $result = $policy->forceDelete($superAdmin, $message);

            expect($result)->toBeTrue();
        });

        it('denies supervisor to force delete any message', function () {
            $policy = new MessagePolicy();
            $supervisor = User::factory()->create(['name' => 'Supervisor']);
            $supervisor->assignRole('supervisor');
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::SENT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(), // Soft deleted
            ]);

            $result = $policy->forceDelete($supervisor, $message);

            expect($result)->toBeFalse();
        });

        it('denies regular user to force delete any message', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::DRAFT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(), // Soft deleted
            ]);

            $result = $policy->forceDelete($regularUser, $message);

            expect($result)->toBeFalse();
        });
    });

    describe('restore method', function () {

        it('allows super admin to restore any message', function () {
            $policy = new MessagePolicy();
            $superAdmin = User::factory()->create(['name' => 'Super Admin']);
            $superAdmin->assignRole('super_admin');
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::SENT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(), // Soft deleted
            ]);

            $result = $policy->restore($superAdmin, $message);

            expect($result)->toBeTrue();
        });

        it('allows supervisor to restore any message', function () {
            $policy = new MessagePolicy();
            $supervisor = User::factory()->create(['name' => 'Supervisor']);
            $supervisor->assignRole('supervisor');
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::SENT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(), // Soft deleted
            ]);

            $result = $policy->restore($supervisor, $message);

            expect($result)->toBeTrue();
        });

        it('denies user to restore their own messages', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $regularUser->id,
                'recipient_id' => $anotherUser->id,
                'status' => MessageStatus::DRAFT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(), // Soft deleted
            ]);

            $result = $policy->restore($regularUser, $message);

            expect($result)->toBeFalse();
        });

        it('denies user to restore messages they received', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');
            $anotherUser = User::factory()->create(['name' => 'Another User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $anotherUser->id,
                'recipient_id' => $regularUser->id,
                'status' => MessageStatus::SENT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(), // Soft deleted
            ]);

            $result = $policy->restore($regularUser, $message);

            expect($result)->toBeFalse();
        });

        it('denies user to restore messages from other users', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');
            $anotherUser = User::factory()->create(['name' => 'Another User']);
            $thirdUser = User::factory()->create(['name' => 'Third User']);

            $messageType = MessageType::factory()->create([
                'name' => 'Mensagem Teste',
                'requires_approval' => false,
                'is_active' => true,
            ]);

            $message = Message::factory()->create([
                'sender_id' => $anotherUser->id,
                'recipient_id' => $thirdUser->id, // Outro destinatário
                'status' => MessageStatus::SENT,
                'message_type_id' => $messageType->id,
                'deleted_at' => now(), // Soft deleted
            ]);

            $result = $policy->restore($regularUser, $message);

            expect($result)->toBeFalse();
        });
    });

    describe('deleteAny method', function () {

        it('allows super admin to delete any messages', function () {
            $policy = new MessagePolicy();
            $superAdmin = User::factory()->create(['name' => 'Super Admin']);
            $superAdmin->assignRole('super_admin');

            $result = $policy->deleteAny($superAdmin);

            expect($result)->toBeTrue();
        });

        it('allows supervisor to delete any messages', function () {
            $policy = new MessagePolicy();
            $supervisor = User::factory()->create(['name' => 'Supervisor']);
            $supervisor->assignRole('supervisor');

            $result = $policy->deleteAny($supervisor);

            expect($result)->toBeTrue();
        });

        it('denies regular user to delete any messages', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');

            $result = $policy->deleteAny($regularUser);

            expect($result)->toBeFalse();
        });
    });

    describe('forceDeleteAny method', function () {

        it('allows super admin to force delete any messages', function () {
            $policy = new MessagePolicy();
            $superAdmin = User::factory()->create(['name' => 'Super Admin']);
            $superAdmin->assignRole('super_admin');

            $result = $policy->forceDeleteAny($superAdmin);

            expect($result)->toBeTrue();
        });

        it('denies supervisor to force delete any messages', function () {
            $policy = new MessagePolicy();
            $supervisor = User::factory()->create(['name' => 'Supervisor']);
            $supervisor->assignRole('supervisor');

            $result = $policy->forceDeleteAny($supervisor);

            expect($result)->toBeFalse();
        });

        it('denies regular user to force delete any messages', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');

            $result = $policy->forceDeleteAny($regularUser);

            expect($result)->toBeFalse();
        });
    });

    describe('restoreAny method', function () {

        it('allows super admin to restore any messages', function () {
            $policy = new MessagePolicy();
            $superAdmin = User::factory()->create(['name' => 'Super Admin']);
            $superAdmin->assignRole('super_admin');

            $result = $policy->restoreAny($superAdmin);

            expect($result)->toBeTrue();
        });

        it('allows supervisor to restore any messages', function () {
            $policy = new MessagePolicy();
            $supervisor = User::factory()->create(['name' => 'Supervisor']);
            $supervisor->assignRole('supervisor');

            $result = $policy->restoreAny($supervisor);

            expect($result)->toBeTrue();
        });

        it('denies regular user to restore any messages', function () {
            $policy = new MessagePolicy();
            $regularUser = User::factory()->create(['name' => 'Regular User']);
            $regularUser->assignRole('atendente');

            $result = $policy->restoreAny($regularUser);

            expect($result)->toBeFalse();
        });
    });
});
