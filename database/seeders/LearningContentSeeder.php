<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Sign;
use Illuminate\Database\Seeder;

/**
 * BIM learning content. Videos/images come from the V1 project (public/media).
 *
 * NOTE FOR THE TEAM: the "instructions" text is generic on purpose.
 * Please replace it with accurate BIM hand-shape descriptions via Admin > Signs.
 */
class LearningContentSeeder extends Seeder
{
    public function run(): void
    {
        $videos = collect(glob(public_path('media/videos/*.mp4')))
            ->map(fn ($path) => pathinfo($path, PATHINFO_FILENAME))
            ->flip();
        $images = collect(glob(public_path('media/images/*.svg')))
            ->map(fn ($path) => pathinfo($path, PATHINFO_FILENAME))
            ->flip();

        $media = function (string $key) use ($videos, $images): array {
            return [
                'video' => $videos->has($key) ? "media/videos/{$key}.mp4" : null,
                'image' => $images->has($key) ? "media/images/{$key}.svg" : null,
            ];
        };

        $staticHelp = 'Watch the video. Copy the hand shape slowly, then hold your hand still in front of the camera.';
        $movingHelp = 'Watch the video carefully. This sign uses movement, so follow the motion from start to end.';

        $categories = [
            [
                'slug' => 'huruf', 'name' => 'Huruf', 'name_en' => 'Alphabet', 'icon' => '🔤', 'color' => '#ff7a59',
                'description' => 'Learn the BIM hand alphabet from A to Z.',
                'signs' => collect(range('A', 'Z'))->map(fn ($l) => [
                    'label' => $l, 'word' => $l, 'meaning_en' => "Letter {$l}",
                    'is_static' => ! in_array($l, ['J', 'Z']), // J and Z use movement
                    'media' => strtolower($l),
                ])->all(),
            ],
            [
                'slug' => 'nombor', 'name' => 'Nombor', 'name_en' => 'Numbers', 'icon' => '🔢', 'color' => '#ffb547',
                'description' => 'Count from 0 to 10 with your hands.',
                'signs' => collect(['Kosong', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Lapan', 'Sembilan', 'Sepuluh'])
                    ->map(fn ($w, $i) => [
                        'label' => (string) $i, 'word' => $w, 'meaning_en' => "Number {$i}",
                        'is_static' => $i < 10, 'media' => strtolower($w),
                    ])->all(),
            ],
            [
                'slug' => 'salam', 'name' => 'Salam', 'name_en' => 'Greetings', 'icon' => '👋', 'color' => '#4cc38a',
                'description' => 'Say hello, thank you and goodbye.',
                'signs' => $this->words([
                    'Assalamualaikum' => 'Peace be upon you', 'Selamat Pagi' => 'Good morning',
                    'Apa Khabar' => 'How are you', 'Terima Kasih' => 'Thank you', 'Maaf' => 'Sorry',
                    'Tolong' => 'Please / Help', 'Selamat Tinggal' => 'Goodbye',
                ]),
            ],
            [
                'slug' => 'keluarga', 'name' => 'Keluarga', 'name_en' => 'Family', 'icon' => '👨‍👩‍👧', 'color' => '#5b8def',
                'description' => 'Signs for the people you love.',
                'signs' => $this->words([
                    'Saya' => 'Me / I', 'Bapa' => 'Father', 'Emak' => 'Mother', 'Abang' => 'Older brother',
                    'Kakak' => 'Older sister', 'Adik' => 'Younger sibling', 'Datuk' => 'Grandfather', 'Nenek' => 'Grandmother',
                ]),
            ],
            [
                'slug' => 'perasaan', 'name' => 'Perasaan', 'name_en' => 'Feelings', 'icon' => '😊', 'color' => '#e468b8',
                'description' => 'Tell others how you feel.',
                'signs' => $this->words([
                    'Suka' => 'Like', 'Gembira' => 'Happy', 'Sedih' => 'Sad', 'Marah' => 'Angry',
                    'Takut' => 'Scared', 'Terkejut' => 'Surprised', 'Sakit' => 'Sick / Hurt',
                ]),
            ],
            [
                'slug' => 'soalan', 'name' => 'Soalan', 'name_en' => 'Questions', 'icon' => '❓', 'color' => '#9b6bff',
                'description' => 'Ask what, who, where and more.',
                'signs' => $this->words([
                    'Apa' => 'What', 'Siapa' => 'Who', 'Di Mana' => 'Where', 'Bila' => 'When',
                    'Kenapa' => 'Why', 'Bagaimana' => 'How', 'Berapa' => 'How many',
                ]),
            ],
            [
                'slug' => 'perkataan', 'name' => 'Perkataan Asas', 'name_en' => 'Basic Words', 'icon' => '📚', 'color' => '#22b5c9',
                'description' => 'Everyday words to start reading.',
                'signs' => $this->words([
                    'Buku' => 'Book', 'Bola' => 'Ball', 'Meja' => 'Table', 'Mata' => 'Eye', 'Kaki' => 'Leg',
                    'Pipi' => 'Cheek', 'Dada' => 'Chest', 'Kuku' => 'Nail', 'Siku' => 'Elbow', 'Kuda' => 'Horse',
                    'Rusa' => 'Deer', 'Naga' => 'Dragon', 'Biri-biri' => 'Sheep', 'Kura-kura' => 'Tortoise',
                    'Rama-rama' => 'Butterfly', 'Roda' => 'Wheel', 'Tali' => 'Rope', 'Gula' => 'Sugar', 'Dadu' => 'Dice',
                    'Baca' => 'Read', 'Beli' => 'Buy', 'Guna' => 'Use', 'Lari' => 'Run', 'Laju' => 'Fast',
                ]),
            ],
        ];

        foreach ($categories as $order => $data) {
            $category = Category::updateOrCreate(
                ['slug' => $data['slug']],
                collect($data)->except('signs')->put('sort_order', $order)->all()
            );

            foreach ($data['signs'] as $i => $sign) {
                Sign::updateOrCreate(
                    ['category_id' => $category->id, 'label' => $sign['label']],
                    [
                        'word' => $sign['word'],
                        'meaning_en' => $sign['meaning_en'],
                        'is_static' => $sign['is_static'],
                        'instructions' => $sign['is_static'] ? $staticHelp : $movingHelp,
                        'sort_order' => $i,
                        ...$media($sign['media']),
                    ]
                );
            }
        }
    }

    /** Turns ['Word' => 'English'] into moving (dynamic) sign rows. */
    private function words(array $words): array
    {
        return collect($words)->map(fn ($en, $word) => [
            'label' => strtoupper(str_replace([' ', '-'], '_', $word)),
            'word' => $word,
            'meaning_en' => $en,
            'is_static' => false,
            'media' => strtolower(preg_replace('/[^A-Za-z0-9]/', '', $word)),
        ])->values()->all();
    }
}
