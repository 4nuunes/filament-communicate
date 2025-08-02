<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use Alessandronuunes\FilamentCommunicate\Models\Message;
use Illuminate\Database\Capsule\Manager as Capsule;

// Configurar banco de dados em memória
$capsule = new Capsule();
$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Criar tabela messages
Capsule::schema()->create('messages', function ($table) {
    $table->id();
    $table->string('code')->unique();
    $table->unsignedBigInteger('message_type_id');
    $table->unsignedBigInteger('sender_id');
    $table->unsignedBigInteger('recipient_id');
    $table->unsignedBigInteger('current_recipient_id');
    $table->unsignedBigInteger('parent_id')->nullable();
    $table->string('subject');
    $table->text('content');
    $table->json('custom_data')->nullable();
    $table->string('status')->default('draft');
    $table->string('priority')->default('normal');
    $table->timestamp('read_at')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->timestamp('rejected_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->json('attachments')->nullable();
    $table->timestamps();
    $table->softDeletes();
});

// Teste básico de soft delete
echo "Testando soft delete do modelo Message...\n";

// Criar uma mensagem
$message = new Message();
$message->code = 'TEST-001';
$message->message_type_id = 1;
$message->sender_id = 1;
$message->recipient_id = 2;
$message->current_recipient_id = 2;
$message->subject = 'Teste';
$message->content = 'Conteúdo de teste';
$message->status = 'sent';
$message->priority = 'normal';
$message->save();

echo "Mensagem criada com ID: {$message->id}\n";
echo 'deleted_at antes do delete: '.($message->deleted_at ? $message->deleted_at : 'null')."\n";

// Fazer soft delete
$message->delete();

echo 'deleted_at após o delete: '.($message->deleted_at ? $message->deleted_at : 'null')."\n";

// Verificar se foi soft deleted
$messageFromDb = Message::withTrashed()->find($message->id);
echo 'Mensagem encontrada com withTrashed: '.($messageFromDb ? 'sim' : 'não')."\n";
echo 'deleted_at da mensagem do DB: '.($messageFromDb->deleted_at ? $messageFromDb->deleted_at : 'null')."\n";

// Verificar se não aparece na query normal
$messageNormal = Message::find($message->id);
echo 'Mensagem encontrada sem withTrashed: '.($messageNormal ? 'sim' : 'não')."\n";

echo "\nTeste concluído!\n";
