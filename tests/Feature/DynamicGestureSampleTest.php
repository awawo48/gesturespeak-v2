<?php

namespace Tests\Feature;

use App\Models\GestureSample;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicGestureSampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalise_preserves_78_and_normalises_146_with_and_without_second_hand(): void
    {
        $hand1 = [];
        for ($i = 0; $i < 21; $i++) {
            $hand1[] = 0.5 + ($i * 0.01);
            $hand1[] = 0.4 - ($i * 0.005);
            $hand1[] = -0.02 * $i;
        }
        $face15 = [0.1, -0.2, 0.05, 0.3, 0.8, 0.1, 0.0, 0.0, 0.0, 0.6, 0.0, 0.0, 0.0, 0.0, 1.0];

        $vec78 = array_merge($hand1, $face15);
        $norm78 = GestureSample::normalise($vec78);
        $this->assertCount(78, $norm78);

        $zeroHand2 = array_fill(0, 63, 0.0);
        $dwiFlagsOff = [0.0, 0.0, 0.0, 0.0, 1.0];
        $vec146NoHand2 = array_merge($hand1, $face15, $zeroHand2, $dwiFlagsOff);

        $norm146NoHand2 = GestureSample::normalise($vec146NoHand2);
        $this->assertCount(146, $norm146NoHand2);
        for ($k = 78; $k < 141; $k++) {
            $this->assertSame(0.0, $norm146NoHand2[$k]);
        }
        $this->assertSame(0.0, $norm146NoHand2[141]);
        $this->assertSame(1.0, $norm146NoHand2[145]);

        $hand2 = [];
        for ($i = 0; $i < 21; $i++) {
            $hand2[] = 0.2 + ($i * 0.015);
            $hand2[] = 0.6 + ($i * 0.01);
            $hand2[] = 0.01 * $i;
        }
        $dwiFlagsOn = [1.0, -0.45, 0.12, 0.47, 1.0];
        $vec146TwoHands = array_merge($hand1, $face15, $hand2, $dwiFlagsOn);

        $norm146TwoHands = GestureSample::normalise($vec146TwoHands);
        $this->assertCount(146, $norm146TwoHands);
        $this->assertGreaterThan(0.0, max(array_slice($norm146TwoHands, 78, 63)));
        for ($k = 78; $k < 141; $k++) {
            $this->assertGreaterThanOrEqual(-1.0, $norm146TwoHands[$k]);
            $this->assertLessThanOrEqual(1.0, $norm146TwoHands[$k]);
        }
        $this->assertSame(1.0, $norm146TwoHands[141]);
        $this->assertSame($norm146TwoHands, GestureSample::normalise($norm146TwoHands));
    }

    public function test_admin_can_store_146_feature_sequence_and_legacy_78_feature_sequence(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        $frame78 = array_fill(0, 78, 0.1);
        $frame78[77] = 1.0;

        $resp78 = $this->actingAs($admin)->postJson(route('admin.ai.sample.store'), [
            'sample_type' => 'sequence',
            'label' => 'ASSALAMUALAIKUM',
            'frames' => array_fill(0, 10, $frame78),
        ]);
        $resp78->assertStatus(200)->assertJson(['success' => true, 'sample_type' => 'sequence']);

        $frame146 = array_fill(0, 146, 0.15);
        $frame146[77] = 0.5;
        $frame146[141] = 1.0;
        $frame146[145] = 1.0;

        $resp146 = $this->actingAs($admin)->postJson(route('admin.ai.sample.store'), [
            'sample_type' => 'sequence',
            'label' => 'TOLONG',
            'frames' => array_fill(0, 10, $frame146),
        ]);
        $resp146->assertStatus(200)->assertJson(['success' => true, 'label' => 'TOLONG']);

        $stored = GestureSample::where('label', 'TOLONG')->where('sample_type', 'sequence')->firstOrFail();
        $this->assertCount(146, $stored->features[0]);

        $frameInvalid = array_fill(0, 147, 0.1);
        $respInvalid = $this->actingAs($admin)->postJson(route('admin.ai.sample.store'), [
            'sample_type' => 'sequence',
            'label' => 'TOLONG',
            'frames' => array_fill(0, 10, $frameInvalid),
        ]);
        $respInvalid->assertStatus(422);
    }

    public function test_static_export_csv_remains_valid_with_mixed_sequence_rows(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        GestureSample::create([
            'label' => 'A',
            'sample_type' => 'static',
            'features' => array_fill(0, 78, 0.2),
            'user_id' => $admin->id,
        ]);
        GestureSample::create([
            'label' => 'TOLONG',
            'sample_type' => 'sequence',
            'features' => array_fill(0, 10, array_fill(0, 146, 0.1)),
            'user_id' => $admin->id,
        ]);

        $resp = $this->actingAs($admin)->get(route('admin.ai.export'));
        $resp->assertStatus(200);
        $csv = $resp->streamedContent();
        $this->assertStringContainsString('label,x0,y0,z0', $csv);
        $this->assertStringContainsString("\nA,", str_replace("\r\n", "\n", $csv));
        $this->assertStringNotContainsString('TOLONG', $csv);
    }
}
