<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Sign;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicViewsSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dynamic_sign_page_renders_pose_segmenter_and_mirror_status_hooks(): void
    {
        $student = Student::create([
            'student_code' => 'MURID01',
            'name' => 'Ali',
            'age' => 8,
            'pin' => '1234',
            'avatar' => 'lion',
        ]);

        $cat = Category::create([
            'slug' => 'kata-nama',
            'name' => 'Kata Nama',
            'name_en' => 'Nouns',
            'description' => 'Isyarat harian',
            'icon' => 'book',
            'color' => '#6c5ce7',
            'sort_order' => 1,
        ]);

        $dynSign = Sign::create([
            'category_id' => $cat->id,
            'label' => 'BUKU',
            'word' => 'Buku',
            'meaning_en' => 'Book',
            'instructions' => 'Buka kedua tapak tangan seperti membaca buku.',
            'is_static' => false,
            'sort_order' => 1,
        ]);

        $staticSign = Sign::create([
            'category_id' => $cat->id,
            'label' => 'A',
            'word' => 'A',
            'meaning_en' => 'A',
            'instructions' => 'Genggam jari.',
            'is_static' => true,
            'sort_order' => 2,
        ]);

        $dynResp = $this->actingAs($student, 'student')
            ->get(route('learn.sign', [$cat, $dynSign]));

        $dynResp->assertStatus(200);
        $dynResp->assertSee('MotionSegmenter', false);
        $dynResp->assertSee('DynamicGestureEngine', false);
        $dynResp->assertSee('PoseLandmarker', false);
        $dynResp->assertSee('buildMultimodalVector', false);
        $dynResp->assertSee('drawPoseAnchors', false);
        $dynResp->assertSee('dynamic-mode-pill', false);
        $dynResp->assertSee('audience: "student"', false);
        $dynResp->assertSee('StepSequenceClassifier', false);
        $dynResp->assertSee('step-progress-bar', false);
        $dynResp->assertSee('data.steps', false);
        $dynResp->assertDontSee('dynamicBannerText.innerHTML =', false);

        $staticResp = $this->actingAs($student, 'student')
            ->get(route('learn.sign', [$cat, $staticSign]));

        $staticResp->assertStatus(200);
        $staticResp->assertSee('if (hasSucceeded || isSaving) return;', false);
        $staticResp->assertSee('if (consecutiveMatches === 2 && !hasShownHoldHint)', false);
        $staticResp->assertDontSee('setTimeout(() => { isSaving = false; }, 2000);', false);
    }

    public function test_admin_ai_studio_renders_dual_mode_live_tester_and_auto_sequence_hooks(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        $cat = Category::create([
            'slug' => 'ucapan',
            'name' => 'Ucapan',
            'name_en' => 'Greetings',
            'description' => 'Ucapan harian',
            'icon' => 'hand',
            'color' => '#0284c7',
            'sort_order' => 1,
        ]);

        Sign::create([
            'category_id' => $cat->id,
            'label' => 'ASSALAMUALAIKUM',
            'word' => 'Assalamualaikum',
            'meaning_en' => 'Peace be upon you',
            'instructions' => 'Angkat tangan ke dahi.',
            'is_static' => false,
            'sort_order' => 1,
        ]);

        $resp = $this->actingAs($admin)->get(route('admin.ai.studio'));

        $resp->assertStatus(200);
        $resp->assertSee('btn-test-mode-open', false);
        $resp->assertSee('btn-test-mode-target', false);
        $resp->assertSee('Open-Set', false);
        $resp->assertSee('Simulasi Pelajar', false);
        $resp->assertSee('Auto Mula–Gerak–Tamat', false);
        $resp->assertSee('MotionSegmenter', false);
        $resp->assertSee('DynamicGestureEngine', false);
        $resp->assertSee('PoseLandmarker', false);
        $resp->assertSee('buildMultimodalVector', false);
        $resp->assertSee('drawPoseAnchors', false);
        $resp->assertSee('btn-cam-mode-steps', false);
        $resp->assertSee('steps-recorder-panel', false);
        $resp->assertSee('StepSequenceClassifier', false);
        $resp->assertSee('data.steps', false);
    }

    public function test_all_admin_portal_pages_render_cleanly_under_admin_layout(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_APPROVED,
        ]);

        $cat = Category::create([
            'slug' => 'ucapan',
            'name' => 'Ucapan',
            'name_en' => 'Greetings',
            'description' => 'Ucapan harian',
            'icon' => 'hand',
            'color' => '#0284c7',
            'sort_order' => 1,
        ]);

        Sign::create([
            'category_id' => $cat->id,
            'label' => 'BUKU',
            'word' => 'Buku',
            'meaning_en' => 'Book',
            'instructions' => 'Buka tapak tangan.',
            'is_static' => false,
            'sort_order' => 1,
        ]);

        $student = Student::create([
            'student_code' => 'MURID99',
            'name' => 'Zara',
            'age' => 7,
            'pin' => '1234',
            'avatar' => 'cat',
        ]);

        \App\Models\QuizAttempt::create([
            'student_id' => $student->id,
            'category_id' => $cat->id,
            'score' => 4,
            'total' => 5,
            'stars' => 3,
        ]);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertStatus(200)
            ->assertSee('Analisis aktiviti 7 hari terkini', false)
            ->assertSee('80%', false)
            ->assertSee('Liputan data AI mengikut modul BIM', false);

        $this->actingAs($admin)->get(route('admin.users'))
            ->assertStatus(200)
            ->assertSee('Pengguna &amp; guru', false);

        $this->actingAs($admin)->get(route('admin.students'))
            ->assertStatus(200)
            ->assertSee('MURID99', false);

        $this->actingAs($admin)->get(route('admin.students.show', $student))
            ->assertStatus(200)
            ->assertSee('Zara', false);

        $this->actingAs($admin)->get(route('admin.signs'))
            ->assertStatus(200)
            ->assertSee('BUKU', false);
    }
}
