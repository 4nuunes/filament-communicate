<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Database\Factories;

use Alessandronuunes\FilamentCommunicate\Models\MessageType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory para o modelo MessageType
 *
 * Gera dados de teste para tipos de mensagem com diferentes configurações
 */
class MessageTypeFactory extends Factory
{
    protected $model = MessageType::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['Geral', 'Urgente', 'Administrativo', 'RH', 'TI', 'Financeiro', 'Comercial', 'Suporte', 'Marketing', 'Vendas']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1, 9999),
            'description' => $this->faker->sentence(),
            'requires_approval' => $this->faker->boolean(),
            'approver_role_id' => null,
            'custom_fields' => json_encode([]),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }

    /**
     * Estado para tipo que requer aprovação
     */
    public function requiresApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => true,
        ]);
    }

    /**
     * Estado para tipo que não requer aprovação
     */
    public function noApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => false,
        ]);
    }

    /**
     * Estado para tipo ativo
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Estado para tipo inativo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Estado para tipo urgente
     */
    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Urgente',
            'slug' => 'urgente',
            'description' => 'Mensagens que requerem atenção imediata',
            'requires_approval' => true,
            'sort_order' => 1,
        ]);
    }

    /**
     * Estado para tipo geral
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Geral',
            'slug' => 'geral',
            'description' => 'Mensagens gerais do sistema',
            'requires_approval' => false,
            'sort_order' => 10,
        ]);
    }

    /**
     * Estado para tipo administrativo
     */
    public function administrative(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Administrativo',
            'slug' => 'administrativo',
            'description' => 'Mensagens administrativas',
            'requires_approval' => true,
            'sort_order' => 5,
        ]);
    }

    /**
     * Estado com campos customizados
     */
    public function withCustomFields(): static
    {
        return $this->state(fn (array $attributes) => [
            'custom_fields' => [
                'priority_level' => 'high',
                'department' => 'IT',
                'auto_close_days' => 7,
            ],
        ]);
    }

    /**
     * Estado com ordem específica
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }
}
