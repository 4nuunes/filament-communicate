<?php

declare(strict_types=1);

namespace Alessandronuunes\FilamentCommunicate\Database\Factories;

use Alessandronuunes\FilamentCommunicate\Enums\MessagePriority;
use Alessandronuunes\FilamentCommunicate\Enums\MessageStatus;
use Alessandronuunes\FilamentCommunicate\Models\Message;
use Alessandronuunes\FilamentCommunicate\Models\MessageType;
use Alessandronuunes\FilamentCommunicate\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        return [
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'current_recipient_id' => User::factory(),
            'message_type_id' => MessageType::factory(),
            'subject' => $this->faker->sentence(),
            'content' => $this->faker->paragraphs(3, true),
            'priority' => $this->faker->randomElement(MessagePriority::cases()),
            'status' => MessageStatus::SENT,
            'read_at' => null,
            'code' => 'MSG-'.now()->format('Ymd-His').'-'.$this->faker->unique()->randomNumber(4),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MessageStatus::DRAFT,
        ]);
    }
}
