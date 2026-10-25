<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the landing page renders with the two big login options.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed(\Database\Seeders\LearningContentSeeder::class);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Saya Murid');
        $response->assertSee('Ibu Bapa & Guru', false);
    }
}
