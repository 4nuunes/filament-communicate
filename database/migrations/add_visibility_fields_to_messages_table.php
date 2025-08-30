<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Campos para controlar visibilidade por usuário
            $table->timestamp('sender_hidden_at')->nullable()->after('delivered_at');
            $table->timestamp('recipient_hidden_at')->nullable()->after('sender_hidden_at');
            $table->string('sender_hidden_reason')->nullable()->after('recipient_hidden_at');
            $table->string('recipient_hidden_reason')->nullable()->after('sender_hidden_reason');

            // Índices para performance
            $table->index(['sender_id', 'sender_hidden_at']);
            $table->index(['recipient_id', 'recipient_hidden_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex(['sender_id', 'sender_hidden_at']);
            $table->dropIndex(['recipient_id', 'recipient_hidden_at']);

            $table->dropColumn([
                'sender_hidden_at',
                'recipient_hidden_at',
                'sender_hidden_reason',
                'recipient_hidden_reason',
            ]);
        });
    }
};
