<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Tests;

use Alessandronuunes\FilamentCommunicate\FilamentCommunicateServiceProvider;
use Alessandronuunes\FilamentCommunicate\Tests\Models\User;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

abstract class TestCase extends Orchestra
{
    //
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (
                string $modelName
            ) => 'Alessandronuunes\\FilamentCommunicate\\Database\\Factories\\'.
                class_basename($modelName).
                'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentCommunicateServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configurar chave de criptografia para testes
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        config()->set('app.cipher', 'AES-256-CBC');

        // Configurar painel Filament para testes
        config()->set('filament.default_filesystem_disk', 'local');
        config()->set('auth.providers.users.model', User::class);

        // Configurar Spatie Permission para testes
        config()->set('permission.models.role', 'Spatie\Permission\Models\Role');
        config()->set('permission.models.permission', 'Spatie\Permission\Models\Permission');
        config()->set('permission.table_names.roles', 'roles');
        config()->set('permission.table_names.permissions', 'permissions');
        config()->set('permission.table_names.model_has_permissions', 'model_has_permissions');
        config()->set('permission.table_names.model_has_roles', 'model_has_roles');
        config()->set('permission.table_names.role_has_permissions', 'role_has_permissions');
        config()->set('permission.column_names.model_morph_key', 'model_id');
        config()->set('permission.column_names.team_foreign_key', 'team_id');
        config()->set('permission.register_permission_check_method', true);
        config()->set('permission.cache.expiration_time', 60 * 24);
        config()->set('permission.cache.key', 'spatie.permission.cache');
        config()->set('permission.cache.store', 'default');
        config()->set('permission.teams', false);
        config()->set('permission.use_passport_client_credentials', false);

        // Criar tabela users para os testes
        $app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        // Criar tabelas do Spatie Permission para os testes
        $app['db']->connection()->getSchemaBuilder()->create('roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('permissions', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        $app['db']->connection()->getSchemaBuilder()->create('model_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        $app['db']->connection()->getSchemaBuilder()->create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        $app['db']->connection()->getSchemaBuilder()->create('role_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['permission_id', 'role_id']);
        });

        // Criar as roles necessárias para os testes
        $app['db']->connection()->table('roles')->insert([
            ['name' => 'super_admin', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'supervisor', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'atendente', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ]);

    }

    protected function defineDatabaseMigrations()
    {
        // Executar migrações do pacote
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getApplicationProviders($app)
    {
        return array_merge(parent::getApplicationProviders($app), [
            TestPanelProvider::class,
        ]);
    }
}
