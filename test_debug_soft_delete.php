<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Models\MessageType;
use Alessandronuunes\FilamentCommunicate\Tests\Models\User;

// Configurar ambiente de teste
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';

// Inicializar Laravel
$app = require_once __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Executar migrations
Artisan::call('migrate', ['--database' => 'testing']);

echo "=== Teste de Soft Delete ===\n";

// Criar dados de teste
$user1 = User::factory()->create(['name' => 'Sender']);
$user2 = User::factory()->create(['name' => 'Recipient']);
$messageType = MessageType::factory()->create(['name' => 'Test Type']);

// Criar mensagem
$message = Message::factory()->create([
    'sender_id' => $user1->id,
    'recipient_id' => $user2->id,
    'message_type_id' => $messageType->id,
    'subject' => 'Teste de soft delete',
    'content' => 'Conteúdo de teste',
]);

echo "Mensagem criada com ID: {$message->id}\n";
echo 'deleted_at antes do delete: '.($message->deleted_at ? $message->deleted_at : 'NULL')."\n";

// Teste 1: Delete individual
echo "\n--- Teste 1: Delete individual ---\n";
$message->delete();
$message->refresh();
echo 'deleted_at após delete(): '.($message->deleted_at ? $message->deleted_at : 'NULL')."\n";

// Restaurar para próximo teste
$message->restore();
echo "Mensagem restaurada\n";

// Teste 2: Bulk delete usando whereIn
echo "\n--- Teste 2: Bulk delete usando whereIn ---\n";
$messages = Message::factory()->count(3)->create([
    'sender_id' => $user1->id,
    'recipient_id' => $user2->id,
    'message_type_id' => $messageType->id,
]);

$messageIds = $messages->pluck('id')->toArray();
echo 'IDs criados: '.implode(', ', $messageIds)."\n";

// Verificar antes do delete
foreach ($messageIds as $id) {
    $msg = Message::find($id);
    echo "Mensagem {$id} antes do delete: deleted_at = ".($msg->deleted_at ?? 'NULL')."\n";
}

// Fazer bulk delete
Message::whereIn('id', $messageIds)->delete();

// Verificar após o delete
foreach ($messageIds as $id) {
    $msg = Message::withTrashed()->find($id);
    echo "Mensagem {$id} após bulk delete: deleted_at = ".($msg->deleted_at ?? 'NULL')."\n";
}

echo "\n=== Fim do teste ===\n";
