
<?php

use Alessandronuunes\FilamentCommunicate\Models\MessageType;
use Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource;
use Alessandronuunes\FilamentCommunicate\Resources\MessageTypeResource\Pages\ListMessageTypes;
use Filament\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;

use function Pest\Livewire\livewire;

it('can test', function () {
    expect(true)->toBeTrue();
});

it('can render the index list message page', function () {

    // Testar se o Resource pode gerar URLs corretamente
    $indexUrl = MessageTypeResource::getUrl('index');
    expect($indexUrl)->toBeString();
    expect($indexUrl)->toContain('message-types');

    // Testar se a página ListMessageTypes pode ser instanciada
    $listPage = new ListMessageTypes();
    expect($listPage)->toBeInstanceOf(ListMessageTypes::class);
    expect($listPage->getResource())->toBe(MessageTypeResource::class);
});

it('can render Resource', function () {
    // Teste básico para verificar se o recurso pode ser instanciado
    $resource = new MessageTypeResource();
    expect($resource)->toBeInstanceOf(Filament\Resources\Resource::class);
});

it('has column', function (string $column) {
    livewire(ListMessageTypes::class)
        ->assertTableColumnExists($column);
})->with(['name', 'is_active', 'approverRole.name', 'requires_approval', 'messages_count', 'sort_order', 'created_at']);

it('can sort MessageType by name', function () {
    $types = MessageType::factory()->count(10)->create();
    livewire(ListMessageTypes::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords($types->sortBy('name'), inOrder: true)
        ->sortTable('name', 'desc')
        ->assertCanSeeTableRecords($types->sortByDesc('name'), inOrder: true);
});

it('can search messages type by name', function () {
    $types = MessageType::factory()->count(10)->create();

    $title = $types->first()->name;

    livewire(ListMessageTypes::class)
        ->searchTable($title)
        ->assertCanSeeTableRecords($types->where('name', $title))
        ->assertCanNotSeeTableRecords($types->where('name', '!=', $title));
});

it('has an is_active filter', function () {
    livewire(ListMessageTypes::class)
        ->assertTableFilterExists('is_active');
});

it('can filter messages types by `is_active`', function () {
    $types = MessageType::factory()->count(10)->create();

    livewire(ListMessageTypes::class)
        ->assertCanSeeTableRecords($types)
        ->filterTable('is_active')
        ->assertCanSeeTableRecords($types->where('is_active', true))
        ->assertCanNotSeeTableRecords($types->where('is_active', false));
});

it('has an requires_approval filter', function () {
    livewire(ListMessageTypes::class)
        ->assertTableFilterExists('requires_approval');
});

it('can filter messages types by `requires_approval`', function () {
    $types = MessageType::factory()->count(10)->create();

    livewire(ListMessageTypes::class)
        ->assertCanSeeTableRecords($types)
        ->filterTable('requires_approval')
        ->assertCanSeeTableRecords($types->where('requires_approval', true))
        ->assertCanNotSeeTableRecords($types->where('requires_approval', false));
});

it('can delete message types', function () {
    $type = MessageType::factory()->create();

    livewire(ListMessageTypes::class)
        ->callTableAction(DeleteAction::class, $type);

    $this->assertSoftDeleted($type);
});

it('can bulk delete message types', function () {
    $types = MessageType::factory()->count(10)->create();

    livewire(ListMessageTypes::class)
        ->callTableBulkAction(DeleteBulkAction::class, $types);

    foreach ($types as $type) {
        $this->assertSoftDeleted($type);
    }
});

it('can not publish, but can delete message types', function () {
    $type = MessageType::factory()->create();

    livewire(ListMessageTypes::class)
        ->assertTableActionEnabled('delete', $type)
        ->assertTableBulkActionEnabled('delete');
});

it('can edit message types', function () {
    $type = MessageType::factory()->create();

    livewire(ListMessageTypes::class)
        ->callTableAction(EditAction::class, $type);

    $this->assertDatabaseHas('message_types', [
        'id' => $type->id,
    ]);
});

it('exist create message type', function () {
    livewire(ListMessageTypes::class)
        ->assertActionExists(CreateAction::class);
});

it('can create message types in modal', function () {
    livewire(ListMessageTypes::class)
        ->mountAction(CreateAction::class)
        ->setActionData([
            'name' => '',
        ])
        ->callAction('create')
        ->assertHasActionErrors(['name' => ['required']]);
});
