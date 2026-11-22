<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TtsControllerTest extends TestCase
{
    /** @var list<string> */
    private array $createdCacheFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdCacheFiles as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }

        parent::tearDown();
    }

    public function test_empty_or_emoji_only_text_returns_204(): void
    {
        $this->get(route('tts.speak', ['text' => '   🎉👏  ']))
            ->assertStatus(204);
    }

    public function test_uses_gemini_ai_studio_tts_with_zephyr_and_caches_on_disk(): void
    {
        $apiKey = 'test-gemini-key-1';
        $model = 'gemini-3.8-flash-tts';
        $voice = 'Zephyr';
        $phrase = 'Selamat pagi uji cache satu';

        config([
            'services.tts.primary' => 'gemini',
            'services.gemini.key' => $apiKey,
            'services.gemini.tts_model' => $model,
            'services.gemini.tts_voice' => $voice,
            'services.elevenlabs.key' => '',
        ]);

        $fakeWav = 'RIFF'.pack('V', 36 + 300).'WAVEfmt '.pack('V', 16).pack('vvVVvv', 1, 1, 24000, 48000, 2, 16).'data'.pack('V', 300).str_repeat("\x01\x02", 150);

        $cacheDir = storage_path('app/public/tts');
        $cacheFile = $cacheDir.DIRECTORY_SEPARATOR.'gem_'.md5('gemini|'.$model.'|'.$voice.'|'.mb_strtolower($phrase)).'.wav';
        $this->createdCacheFiles[] = $cacheFile;
        $this->createdCacheFiles[] = $cacheDir.DIRECTORY_SEPARATOR.'gem_rl_'.md5($apiKey).'.flag';
        if (File::exists($cacheFile)) {
            File::delete($cacheFile);
        }

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'inlineData' => [
                                        'mimeType' => 'audio/wav',
                                        'data' => base64_encode($fakeWav),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $first = $this->get(route('tts.speak', ['text' => $phrase]));
        $first->assertStatus(200)
            ->assertHeader('Content-Type', 'audio/wav')
            ->assertHeader('X-TTS-Source', 'gemini-live');

        Http::assertSent(function ($request) use ($apiKey, $voice, $phrase) {
            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash-tts:generateContent'
                && $request->hasHeader('x-goog-api-key', $apiKey)
                && data_get($request->data(), 'contents.0.parts.0.text') === $phrase
                && data_get($request->data(), 'generationConfig.responseModalities') === ['AUDIO']
                && data_get($request->data(), 'generationConfig.speechConfig.voiceConfig.prebuiltVoiceConfig.voiceName') === $voice;
        });

        // Second request must be served from disk cache with zero additional HTTP calls
        Http::fake();
        $second = $this->get(route('tts.speak', ['text' => $phrase]));
        $second->assertStatus(200)
            ->assertHeader('Content-Type', 'audio/wav')
            ->assertHeader('X-TTS-Source', 'gemini-cache');
        Http::assertNothingSent();
    }

    public function test_wraps_raw_pcm_l16_from_gemini_into_valid_wav(): void
    {
        $apiKey = 'test-gemini-key-pcm';
        $model = 'gemini-2.5-flash-preview-tts';
        $voice = 'Zephyr';
        $phrase = 'Ujian audio pcm mentah';

        config([
            'services.tts.primary' => 'gemini',
            'services.gemini.key' => $apiKey,
            'services.gemini.tts_model' => $model,
            'services.gemini.tts_voice' => $voice,
            'services.elevenlabs.key' => '',
        ]);

        $cacheDir = storage_path('app/public/tts');
        $cacheFile = $cacheDir.DIRECTORY_SEPARATOR.'gem_'.md5('gemini|'.$model.'|'.$voice.'|'.mb_strtolower($phrase)).'.wav';
        $this->createdCacheFiles[] = $cacheFile;
        if (File::exists($cacheFile)) {
            File::delete($cacheFile);
        }

        $rawPcm = str_repeat("\x10\x00", 200);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'inlineData' => [
                                        'mimeType' => 'audio/L16;codec=pcm;rate=24000',
                                        'data' => base64_encode($rawPcm),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $resp = $this->get(route('tts.speak', ['text' => $phrase]));
        $resp->assertStatus(200)
            ->assertHeader('Content-Type', 'audio/wav')
            ->assertHeader('X-TTS-Source', 'gemini-live');

        $body = $resp->getContent();
        $this->assertSame('RIFF', substr($body, 0, 4));
        $this->assertSame('WAVE', substr($body, 8, 4));
        $this->assertSame(44 + strlen($rawPcm), strlen($body));
    }

    public function test_falls_back_to_elevenlabs_eleven_v4_when_gemini_is_rate_limited(): void
    {
        $geminiKey = 'test-gemini-key-429-el';
        $elKey = 'test-el-key-v4';
        $customVoice = 'voice-custom-402';
        $premadeVoice = 'EXAVITQu4vr4xnSDxMaL';
        $elModel = 'eleven_v4';
        $elLang = 'ms';
        $phrase = 'Ujian fallback elevenlabs versi empat';

        config([
            'services.tts.primary' => 'gemini',
            'services.gemini.key' => $geminiKey,
            'services.gemini.tts_model' => 'gemini-3.8-flash-tts',
            'services.gemini.tts_voice' => 'Zephyr',
            'services.elevenlabs.key' => $elKey,
            'services.elevenlabs.voice_id' => $customVoice,
            'services.elevenlabs.model_id' => $elModel,
            'services.elevenlabs.language_code' => $elLang,
            'services.elevenlabs.stability' => 0.50,
            'services.elevenlabs.similarity_boost' => 0.75,
            'services.elevenlabs.clone_strength' => 0.0,
        ]);

        $cacheDir = storage_path('app/public/tts');
        $gemCooldown = $cacheDir.DIRECTORY_SEPARATOR.'gem_rl_'.md5($geminiKey).'.flag';
        $elBlocked = $cacheDir.DIRECTORY_SEPARATOR.'blocked_'.md5($elKey.'|'.$customVoice).'.flag';
        $elPremadeCache = $cacheDir.DIRECTORY_SEPARATOR.md5($premadeVoice.'|'.$elModel.'|'.$elLang.'|'.mb_strtolower($phrase)).'.mp3';
        $this->createdCacheFiles = array_merge($this->createdCacheFiles, [$gemCooldown, $elBlocked, $elPremadeCache]);
        foreach ([$gemCooldown, $elBlocked, $elPremadeCache] as $f) {
            if (File::exists($f)) {
                File::delete($f);
            }
        }

        $fakeMp3 = str_repeat('ELV4MP3', 60);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['error' => ['code' => 429]], 429),
            "https://api.elevenlabs.io/v1/text-to-speech/{$customVoice}*" => Http::response(['detail' => ['code' => 'paid_plan_required']], 402),
            "https://api.elevenlabs.io/v1/text-to-speech/{$premadeVoice}*" => Http::response($fakeMp3, 200),
        ]);

        $resp = $this->get(route('tts.speak', ['text' => $phrase]));
        $resp->assertStatus(200)
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertHeader('X-TTS-Source', 'elevenlabs-premade');

        Http::assertSent(function ($request) use ($premadeVoice, $elModel, $elLang, $phrase) {
            return str_contains($request->url(), "/v1/text-to-speech/{$premadeVoice}")
                && data_get($request->data(), 'model_id') === $elModel
                && data_get($request->data(), 'language_code') === $elLang
                && data_get($request->data(), 'voice_settings.stability') === 0.5
                && data_get($request->data(), 'voice_settings.similarity_boost') === 0.75
                && data_get($request->data(), 'voice_settings.clone_strength') === 0.0
                && data_get($request->data(), 'text') === $phrase;
        });

        $this->assertFileExists($gemCooldown);
        $this->assertFileExists($elBlocked);
    }

    public function test_falls_back_to_google_malay_tts_when_all_ai_providers_fail(): void
    {
        $geminiKey = 'test-gemini-key-429-all';
        $phrase = 'Ujian had kuota semua penyedia';

        config([
            'services.tts.primary' => 'gemini',
            'services.gemini.key' => $geminiKey,
            'services.gemini.tts_model' => 'gemini-3.8-flash-tts',
            'services.gemini.tts_voice' => 'Zephyr',
            'services.elevenlabs.key' => '',
        ]);

        $cacheDir = storage_path('app/public/tts');
        $cooldownFlag = $cacheDir.DIRECTORY_SEPARATOR.'gem_rl_'.md5($geminiKey).'.flag';
        $fallbackFile = $cacheDir.DIRECTORY_SEPARATOR.'g_'.md5(mb_strtolower($phrase)).'.mp3';
        $this->createdCacheFiles[] = $cooldownFlag;
        $this->createdCacheFiles[] = $fallbackFile;
        foreach ([$cooldownFlag, $fallbackFile] as $f) {
            if (File::exists($f)) {
                File::delete($f);
            }
        }

        $fakeMp3 = str_repeat('MP3DATA', 60);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['error' => ['code' => 429]], 429),
            'https://translate.google.com/translate_tts*' => Http::response($fakeMp3, 200),
        ]);

        $resp = $this->get(route('tts.speak', ['text' => $phrase]));
        $resp->assertStatus(200)
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertHeader('X-TTS-Source', 'google-ms-live');
        $this->assertFileExists($cooldownFlag);
    }
}
