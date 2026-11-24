<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Sign;
use App\Models\SignProgress;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KidPortalHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_kid_portal_hub_renders_figcraft_unified_hero_and_emojis(): void
    {
        $teacher = User::factory()->create([
            'name' => 'Cikgu Sarah',
            'role' => User::ROLE_TEACHER,
            'status' => User::STATUS_APPROVED,
        ]);

        $catGreetings = Category::create([
            'slug' => 'ucapan-harian',
            'name' => 'Ucapan Harian',
            'name_en' => 'Daily Greetings',
            'description' => 'Isyarat ucapan',
            'icon' => 'ucapan',
            'color' => '#26b0e5',
            'sort_order' => 1,
        ]);

        $sign = Sign::create([
            'category_id' => $catGreetings->id,
            'label' => 'TERIMAKASIH',
            'word' => 'Terima Kasih',
            'meaning_en' => 'Thank you',
            'instructions' => 'Sentuh bibir dan gerak ke hadapan.',
            'is_static' => true,
            'sort_order' => 1,
        ]);

        $student = Student::create([
            'student_code' => 'ADAM-1234',
            'name' => 'Adam',
            'age' => 8,
            'pin' => '1234',
            'avatar' => 'lion',
            'stars' => 120,
            'teacher_note' => '5 isyarat salam · Kumpul 20 bintang bonus',
            'teacher_note_by' => $teacher->id,
            'assigned_category_id' => $catGreetings->id,
        ]);

        SignProgress::create([
            'student_id' => $student->id,
            'sign_id' => $sign->id,
            'viewed_at' => now(),
            'practice_count' => 3,
            'best_confidence' => 0.96,
            'mastered' => true,
        ]);

        $response = $this->actingAs($student, 'student')->get(route('learn.hub'));

        $response->assertStatus(200);

        // Student emoji avatar (lion 🦁) and greeting with star 🌟
        $response->assertSee('🦁', false);
        $response->assertSee('Hai, Adam! 🌟', false);

        // Unified Hero Console Elements (Frame #624 / #1136)
        $response->assertSee('hero-master-console', false);
        $response->assertSee('SELAMAT BELAJAR', false);
        $response->assertSee('DALAM TALIAN', false);
        $response->assertSee('KOD: ADAM-1234', false);
        $response->assertSee('Semangat Kiko!', false);
        $response->assertSee('BINTANG TERKUMPUL', false);
        $response->assertSee('120', false);

        // Mission Tile 1: Tugasan Cikgu
        $response->assertSee('TUGASAN CIKGU SARAH', false);
        $response->assertSee('Modul: Ucapan Harian', false);
        $response->assertSee('Buka Tugasan', false);
        $response->assertSee(route('learn.category', $catGreetings), false);

        // Verification of user requested removals:
        $response->assertDontSee('Tahun 2 Pintar', false);
        $response->assertDontSee('SK Seri Cilik', false);
        $response->assertDontSee('Hantar: Khamis', false);

        // Mission Tile 2: Cabaran Kuiz BIM
        $response->assertSee('CABARAN MINGGUAN', false);
        $response->assertSee('+50 BINTANG', false);
        $response->assertSee('Kuiz Pantas &amp; Ejaan BIM', false);
        $response->assertSee('Buka Pilihan Kuiz', false);

        // Learning Worlds Grid
        $response->assertSee('Pilih Dunia Pembelajaran', false);
        $response->assertSee('module-grid', false);
        $response->assertSee('Mula Modul Ini', false);

        // Recent Shelf
        $response->assertSee('Latihan Terkini Anda', false);
        $response->assertSee('Terima Kasih', false);
        $response->assertSee('Padanan: 96%', false);
        $response->assertDontSee('10000%', false);
        $response->assertSee('Dikuasai', false);
    }

    public function test_kid_portal_hub_handles_100_percent_confidence_and_teacher_exercise(): void
    {
        $teacher = User::factory()->create([
            'name' => 'Cikgu Rahman',
            'role' => User::ROLE_TEACHER,
            'status' => User::STATUS_APPROVED,
        ]);

        $cat = Category::create([
            'slug' => 'nombor-bim',
            'name' => 'Nombor BIM',
            'name_en' => 'Numbers',
            'description' => 'Isyarat nombor',
            'icon' => 'nombor',
            'color' => '#ffe942',
            'sort_order' => 1,
        ]);

        $sign = Sign::create([
            'category_id' => $cat->id,
            'label' => 'SATU',
            'word' => 'Satu',
            'meaning_en' => 'One',
            'instructions' => 'Tunjuk jari telunjuk ke atas.',
            'is_static' => true,
            'sort_order' => 1,
        ]);

        $student = Student::create([
            'student_code' => 'SITI-5678',
            'name' => 'Siti',
            'age' => 7,
            'pin' => '5678',
            'avatar' => 'rabbit',
            'stars' => 80,
        ]);

        $student->guardians()->attach($teacher->id, ['relationship' => User::ROLE_TEACHER]);

        // Teacher created an exercise
        $exercise = \App\Models\Exercise::create([
            'user_id' => $teacher->id,
            'title' => 'Latihan Nombor Asas',
            'type' => \App\Models\Exercise::TYPE_CHOICE,
            'items' => [$sign->id],
            'is_active' => true,
        ]);

        // Progress with 100.00 confidence
        SignProgress::create([
            'student_id' => $student->id,
            'sign_id' => $sign->id,
            'viewed_at' => now(),
            'practice_count' => 5,
            'best_confidence' => 100.00,
            'mastered' => true,
        ]);

        $response = $this->actingAs($student, 'student')->get(route('learn.hub'));

        $response->assertStatus(200);

        // Best confidence should show 100% (never 10000%)
        $response->assertSee('Padanan: 100%', false);
        $response->assertDontSee('10000%', false);

        // Buka Tugasan should link to the teacher exercise
        $response->assertSee('Latihan: Latihan Nombor Asas', false);
        $response->assertSee(route('quiz.exercise', $exercise), false);
        $response->assertDontSee('Hantar: Khamis', false);
        $response->assertDontSee('Tahun 2 Pintar', false);
    }
}
