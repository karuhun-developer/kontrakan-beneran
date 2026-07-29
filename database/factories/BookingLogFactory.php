<?php

namespace Database\Factories;

use App\Enums\BookingEvent;
use App\Models\Booking;
use App\Models\BookingLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingLog>
 */
class BookingLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'user_id' => null,
            'event' => BookingEvent::Created,
            'description' => null,
            'meta' => null,
        ];
    }
}
