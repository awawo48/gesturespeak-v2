<?php

namespace Tests\Feature;

use App\Models\GestureSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StepSequenceSampleTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);
    }

    private function makeFrame146(float $offset = 0.0, bool $twoHands = true): array
    {
        $hand1 = [];
        for ($i = 0; $i < 21; $i++) {
            $hand1[] = 0.5 + $offset + ($i * 0.01);
            $hand1[] = 0.4 - ($i * 0.005);
            $hand1[] = -0.01 * $i;
        }
        $face15 = [0.1 + $offset, -0.2, 0.05, 0.3, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.5];
        $hand2 = $twoHands ? array_map(fn ($v) => $v + 0.1, $hand1) : array_fill(0, 63, 0.0);
        $dwi5 = $twoHands ? [1.0, -0.3, 0.05, 0.31, 1.0] : [0.0, 0.0, 0.0, 0.0, 1.0];

        return array_merge($hand1, $face15, $hand2, $dwi5);
    }

    public function test_normalise_steps_normalises_146_vectors_and_fills_default_names(): void
    {
        $raw = [
            ['name' => '   ', 'f' => $this->makeFrame146(0.0)],
            ['name' => '  Buka  ', 'f' => $this->makeFrame146(0.2)],
        ];

        $norm = GestureSample::normaliseSteps($raw);

        $this->assertCount(2, $norm);
        $this->assertSame('Langkah 1', $norm[0]['name']);
        $this->assertSame('Buka', $norm[1]['name']);
        $this->assertCount(146, $norm[0]['f']);
        $this->assertSame($norm[0]['f'], GestureSample::normalise($norm[0]['f']));
    }

    public function test_admin_can_store_steps_sample_and_rejects_out_of_range_step_counts(): void
    {
        $admin = $this->makeAdmin();

        $ok = $this->actingAs($admin)->postJson(route('admin.ai.sample.store'), [
            'sample_type' => 'steps',
            'label' => 'buku',
            'steps' => [
                ['name' => 'Rapat', 'f' => $this->makeFrame146(0.0)],
                ['name' => 'Buka', 'f' => $this->makeFrame146(0.25)],
            ],
        ]);

        $ok->assertStatus(200)->assertJson([
            'success' => true,
            'sample_type' => 'steps',
            'label' => 'BUKU',
            'saved' => 1,
            'steps_count' => 2,
            'step_counts' => ['BUKU' => 1],
            'total_steps' => 1,
        ]);

        $tooFew = $this->actingAs($admin)->postJson(route('admin.ai.sample.store'), [
            'sample_type' => 'steps',
            'label' => 'BUKU',
            'steps' => [['name' => 'Satu', 'f' => $this->makeFrame146(0.0)]],
        ]);
        $tooFew->assertStatus(422);

        $tooMany = $this->actingAs($admin)->postJson(route('admin.ai.sample.store'), [
            'sample_type' => 'steps',
            'label' => 'BUKU',
            'steps' => array_fill(0, 7, ['name' => 'X', 'f' => $this->makeFrame146(0.0)]),
        ]);
        $tooMany->assertStatus(422);
    }

    public function test_dataset_endpoint_includes_steps_without_breaking_samples_or_sequences(): void
    {
        GestureSample::create([
            'label' => 'A',
            'sample_type' => 'static',
            'features' => array_fill(0, 63, 0.1),
        ]);
        GestureSample::create([
            'label' => 'J',
            'sample_type' => 'sequence',
            'features' => array_fill(0, 10, array_fill(0, 78, 0.1)),
        ]);
        GestureSample::create([
            'label' => 'BUKU',
            'sample_type' => 'steps',
            'features' => [
                ['name' => 'Rapat', 'f' => $this->makeFrame146(0.0)],
                ['name' => 'Buka', 'f' => $this->makeFrame146(0.2)],
            ],
        ]);

        $resp = $this->getJson(route('ai.public_dataset'));
        $resp->assertStatus(200)
            ->assertJsonCount(1, 'samples')
            ->assertJsonCount(1, 'sequences')
            ->assertJsonCount(1, 'steps')
            ->assertJsonPath('steps.0.label', 'BUKU')
            ->assertJsonCount(2, 'steps.0.steps');
    }

    public function test_clear_samples_with_sample_type_steps_preserves_sequence_and_static_and_export_stays_static_only(): void
    {
        $admin = $this->makeAdmin();

        GestureSample::create([
            'label' => 'BUKU',
            'sample_type' => 'static',
            'features' => array_fill(0, 63, 0.2),
        ]);
        GestureSample::create([
            'label' => 'BUKU',
            'sample_type' => 'sequence',
            'features' => array_fill(0, 10, array_fill(0, 78, 0.2)),
        ]);
        GestureSample::create([
            'label' => 'BUKU',
            'sample_type' => 'steps',
            'features' => [
                ['name' => 'Langkah 1', 'f' => $this->makeFrame146(0.0)],
                ['name' => 'Langkah 2', 'f' => $this->makeFrame146(0.2)],
            ],
        ]);

        $clear = $this->actingAs($admin)->postJson(route('admin.ai.clear'), [
            'label' => 'BUKU',
            'sample_type' => 'steps',
        ]);
        $clear->assertStatus(200)->assertJson([
            'success' => true,
            'step_counts' => [],
        ]);

        $this->assertSame(0, GestureSample::where('label', 'BUKU')->where('sample_type', 'steps')->count());
        $this->assertSame(1, GestureSample::where('label', 'BUKU')->where('sample_type', 'sequence')->count());
        $this->assertSame(1, GestureSample::where('label', 'BUKU')->where('sample_type', 'static')->count());

        $csv = $this->actingAs($admin)->get(route('admin.ai.export'));
        $csv->assertStatus(200);
        $lines = array_values(array_filter(explode("\n", trim($csv->streamedContent()))));
        $this->assertCount(2, $lines); // header + 1 static row only
    }
}
