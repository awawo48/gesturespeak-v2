<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bahasa Melayu Text-to-Speech (TTS) Controller for Kiko & Child Learning Mode.
 * 1. Disk cache per provider/model/voice (0 API credits/quota used on repeat plays)
 * 2. Primary + secondary AI TTS providers (Google AI Studio Gemini TTS & ElevenLabs eleven_v4)
 * 3. Google Translate Bahasa Melayu TTS (tl=ms) automatic fallback
 */
class TtsController extends Controller
{
    /** Default ElevenLabs premade voice (Sarah) supported on Free Tier with eleven_v4. */
    private const FREE_PREMADE_VOICE_ID = 'EXAVITQu4vr4xnSDxMaL';

    private const RATE_LIMIT_COOLDOWN_SECONDS = 60;

    private const VOICE_BLOCKED_COOLDOWN_SECONDS = 21600;

    public function speak(Request $request): Response
    {
        $rawText = (string) $request->query('text', '');

        // Strip emojis and clean whitespace so TTS reads only Malay words cleanly
        $cleanText = preg_replace('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE00}-\x{FE0F}]/u', '', $rawText);
        $cleanText = trim(preg_replace('/\s+/u', ' ', (string) $cleanText));

        if ($cleanText === '') {
            return response('', 204);
        }

        // Cap length to protect quota
        $cleanText = mb_substr($cleanText, 0, 260);

        $cacheDir = storage_path('app/public/tts');
        if (! File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }

        $primary = strtolower(trim((string) config('services.tts.primary', 'gemini')));
        $providers = $primary === 'elevenlabs'
            ? ['elevenlabs', 'gemini']
            : ['gemini', 'elevenlabs'];

        foreach ($providers as $provider) {
            $result = $provider === 'elevenlabs'
                ? $this->resolveElevenLabs($cacheDir, $cleanText)
                : $this->resolveGemini($cacheDir, $cleanText);

            if ($result !== null) {
                return $this->audioResponse($result['bytes'], $result['content_type'], $result['source'], 31536000);
            }
        }

        // Fallback: Google Translate Bahasa Melayu TTS (tl=ms)
        $fallback = $this->requestGoogleMalayFallback($cacheDir, $cleanText);
        if ($fallback !== null) {
            return $this->audioResponse($fallback['bytes'], 'audio/mpeg', $fallback['source'], 86400);
        }

        return response('', 503);
    }

    /**
     * @return array{bytes: string, content_type: string, source: string}|null
     */
    private function resolveGemini(string $cacheDir, string $cleanText): ?array
    {
        $apiKey = trim((string) config('services.gemini.key'));
        $model = preg_replace('#^models/#', '', trim((string) config('services.gemini.tts_model', 'gemini-3.8-flash-tts'))) ?: 'gemini-3.8-flash-tts';
        $voice = trim((string) config('services.gemini.tts_voice', 'Zephyr')) ?: 'Zephyr';

        $cacheKey = md5('gemini|'.$model.'|'.$voice.'|'.mb_strtolower($cleanText));

        foreach (['wav' => 'audio/wav', 'mp3' => 'audio/mpeg'] as $ext => $contentType) {
            $cacheFile = $cacheDir.DIRECTORY_SEPARATOR."gem_{$cacheKey}.{$ext}";
            if (File::exists($cacheFile) && File::size($cacheFile) > 256) {
                return ['bytes' => File::get($cacheFile), 'content_type' => $contentType, 'source' => 'gemini-cache'];
            }
        }

        if ($apiKey === '') {
            return null;
        }

        $cooldownFlag = $cacheDir.DIRECTORY_SEPARATOR.'gem_rl_'.md5($apiKey).'.flag';
        if ($this->isInCooldown($cooldownFlag, self::RATE_LIMIT_COOLDOWN_SECONDS)) {
            return null;
        }

        $generated = $this->requestGeminiTts($apiKey, $model, $voice, $cleanText, $cooldownFlag);
        if ($generated === null) {
            return null;
        }

        $cacheFile = $cacheDir.DIRECTORY_SEPARATOR."gem_{$cacheKey}.{$generated['ext']}";
        File::put($cacheFile, $generated['bytes']);

        return ['bytes' => $generated['bytes'], 'content_type' => $generated['content_type'], 'source' => 'gemini-live'];
    }

    /**
     * @return array{bytes: string, content_type: string, source: string}|null
     */
    private function resolveElevenLabs(string $cacheDir, string $cleanText): ?array
    {
        $apiKey = trim((string) config('services.elevenlabs.key'));
        $voiceId = trim((string) config('services.elevenlabs.voice_id', 'JOCyls6Fdo4qfmTUnFrJ'));
        $modelId = trim((string) config('services.elevenlabs.model_id', 'eleven_v4')) ?: 'eleven_v4';
        $languageCode = trim((string) config('services.elevenlabs.language_code', 'ms')) ?: 'ms';
        $voiceSettings = [
            'stability' => (float) config('services.elevenlabs.stability', 0.50),
            'similarity_boost' => (float) config('services.elevenlabs.similarity_boost', 0.75),
            'clone_strength' => (float) config('services.elevenlabs.clone_strength', 0.0),
            'use_speaker_boost' => true,
        ];

        $attempts = array_values(array_unique(array_filter([
            $voiceId,
            self::FREE_PREMADE_VOICE_ID,
        ])));

        foreach ($attempts as $tryVoiceId) {
            $cacheFile = $cacheDir.DIRECTORY_SEPARATOR.md5($tryVoiceId.'|'.$modelId.'|'.$languageCode.'|'.mb_strtolower($cleanText)).'.mp3';
            if (File::exists($cacheFile) && File::size($cacheFile) > 256) {
                return ['bytes' => File::get($cacheFile), 'content_type' => 'audio/mpeg', 'source' => 'elevenlabs-cache'];
            }
        }

        if ($apiKey === '') {
            return null;
        }

        $rateLimitFlag = $cacheDir.DIRECTORY_SEPARATOR.'el_rl_'.md5($apiKey).'.flag';
        if ($this->isInCooldown($rateLimitFlag, self::RATE_LIMIT_COOLDOWN_SECONDS)) {
            return null;
        }

        foreach ($attempts as $tryVoiceId) {
            // Skip library voices already known to be blocked on ElevenLabs Free Tier (HTTP 402)
            $blockedMarker = $cacheDir.DIRECTORY_SEPARATOR.'blocked_'.md5($apiKey.'|'.$tryVoiceId).'.flag';
            if ($this->isInCooldown($blockedMarker, self::VOICE_BLOCKED_COOLDOWN_SECONDS)) {
                continue;
            }

            $audioBytes = $this->requestElevenLabs($apiKey, $tryVoiceId, $modelId, $languageCode, $voiceSettings, $cleanText, $blockedMarker, $rateLimitFlag);
            if ($audioBytes !== null) {
                $cacheFile = $cacheDir.DIRECTORY_SEPARATOR.md5($tryVoiceId.'|'.$modelId.'|'.$languageCode.'|'.mb_strtolower($cleanText)).'.mp3';
                File::put($cacheFile, $audioBytes);

                return [
                    'bytes' => $audioBytes,
                    'content_type' => 'audio/mpeg',
                    'source' => $tryVoiceId === $voiceId ? 'elevenlabs-custom' : 'elevenlabs-premade',
                ];
            }
        }

        return null;
    }

    /**
     * Call Google AI Studio Gemini TTS endpoint and normalize returned audio into WAV/MP3 bytes.
     *
     * @return array{bytes: string, ext: string, content_type: string}|null
     */
    private function requestGeminiTts(string $apiKey, string $model, string $voice, string $text, string $cooldownFlag): ?array
    {
        try {
            $resp = Http::when(app()->environment('local'), fn ($h) => $h->withoutVerifying())
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->connectTimeout(5)
                ->timeout(20)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $text],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseModalities' => ['AUDIO'],
                        'speechConfig' => [
                            'voiceConfig' => [
                                'prebuiltVoiceConfig' => [
                                    'voiceName' => $voice,
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($resp->status() === 429) {
                File::put($cooldownFlag, (string) time());
            }

            if (! $resp->successful()) {
                Log::warning("Gemini TTS notice ({$model}/{$voice}, HTTP {$resp->status()}): ".mb_substr($resp->body(), 0, 300));

                return null;
            }

            $parts = data_get($resp->json(), 'candidates.0.content.parts', []);
            if (! is_array($parts)) {
                return null;
            }

            foreach ($parts as $part) {
                $b64 = data_get($part, 'inlineData.data');
                if (! is_string($b64) || $b64 === '') {
                    continue;
                }

                $decoded = base64_decode($b64, true);
                if ($decoded === false || strlen($decoded) <= 128) {
                    continue;
                }

                $mimeType = strtolower((string) data_get($part, 'inlineData.mimeType', 'audio/wav'));

                return $this->normalizeGeminiAudio($decoded, $mimeType);
            }

            Log::warning("Gemini TTS returned no inline audio data for model {$model}.");
        } catch (\Throwable $e) {
            Log::warning("Gemini TTS connection error ({$model}): ".$e->getMessage());
        }

        return null;
    }

    private function requestElevenLabs(
        string $apiKey,
        string $voiceId,
        string $modelId,
        string $languageCode,
        array $voiceSettings,
        string $text,
        string $blockedMarker,
        string $rateLimitFlag,
    ): ?string {
        try {
            $resp = Http::when(app()->environment('local'), fn ($h) => $h->withoutVerifying())
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->connectTimeout(5)
                ->timeout(12)
                ->withHeaders([
                    'xi-api-key' => $apiKey,
                    'Accept' => 'audio/mpeg',
                    'Content-Type' => 'application/json',
                ])
                ->post("https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}?output_format=mp3_44100_128", [
                    'text' => $text,
                    'model_id' => $modelId,
                    'language_code' => $languageCode,
                    'voice_settings' => $voiceSettings,
                ]);

            if ($resp->successful() && strlen($resp->body()) > 256) {
                return $resp->body();
            }

            if ($resp->status() === 402) {
                File::put($blockedMarker, (string) time());
            } elseif (in_array($resp->status(), [401, 429], true)) {
                File::put($rateLimitFlag, (string) time());
            }

            Log::warning("ElevenLabs TTS notice for voice {$voiceId} ({$modelId}, HTTP {$resp->status()}): ".mb_substr($resp->body(), 0, 300));
        } catch (\Throwable $e) {
            Log::warning("ElevenLabs TTS connection error for voice {$voiceId}: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Ensure Gemini audio bytes carry a browser-playable container (WAV or MP3).
     *
     * @return array{bytes: string, ext: string, content_type: string}
     */
    private function normalizeGeminiAudio(string $bytes, string $mimeType): array
    {
        if (str_contains($mimeType, 'mpeg') || str_contains($mimeType, 'mp3')) {
            return ['bytes' => $bytes, 'ext' => 'mp3', 'content_type' => 'audio/mpeg'];
        }

        if (str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WAVE') {
            return ['bytes' => $bytes, 'ext' => 'wav', 'content_type' => 'audio/wav'];
        }

        // Raw Linear PCM (e.g. audio/L16;codec=pcm;rate=24000) needs a 44-byte RIFF/WAV header
        $sampleRate = 24000;
        if (preg_match('/rate=(\d+)/i', $mimeType, $matches)) {
            $sampleRate = max(8000, (int) $matches[1]);
        }

        return [
            'bytes' => $this->wrapPcmAsWav($bytes, $sampleRate),
            'ext' => 'wav',
            'content_type' => 'audio/wav',
        ];
    }

    private function wrapPcmAsWav(string $pcmBytes, int $sampleRate = 24000, int $channels = 1, int $bitsPerSample = 16): string
    {
        $dataSize = strlen($pcmBytes);
        $byteRate = $sampleRate * $channels * ($bitsPerSample / 8);
        $blockAlign = $channels * ($bitsPerSample / 8);

        $header = 'RIFF'
            .pack('V', 36 + $dataSize)
            .'WAVEfmt '
            .pack('V', 16)
            .pack('v', 1)
            .pack('v', $channels)
            .pack('V', $sampleRate)
            .pack('V', (int) $byteRate)
            .pack('v', (int) $blockAlign)
            .pack('v', $bitsPerSample)
            .'data'
            .pack('V', $dataSize);

        return $header.$pcmBytes;
    }

    /**
     * @return array{bytes: string, source: string}|null
     */
    private function requestGoogleMalayFallback(string $cacheDir, string $cleanText): ?array
    {
        try {
            $fallbackFile = $cacheDir.DIRECTORY_SEPARATOR.'g_'.md5(mb_strtolower($cleanText)).'.mp3';
            if (File::exists($fallbackFile) && File::size($fallbackFile) > 256) {
                return ['bytes' => File::get($fallbackFile), 'source' => 'google-ms-cache'];
            }

            $gResp = Http::when(app()->environment('local'), fn ($h) => $h->withoutVerifying())
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->connectTimeout(4)
                ->timeout(8)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                ])
                ->get('https://translate.google.com/translate_tts', [
                    'ie' => 'UTF-8',
                    'client' => 'tw-ob',
                    'tl' => 'ms',
                    'q' => mb_substr($cleanText, 0, 195),
                ]);

            if ($gResp->successful() && strlen($gResp->body()) > 256) {
                File::put($fallbackFile, $gResp->body());

                return ['bytes' => $gResp->body(), 'source' => 'google-ms-live'];
            }
        } catch (\Throwable $e) {
            Log::warning('Google Malay TTS fallback failed: '.$e->getMessage());
        }

        return null;
    }

    private function isInCooldown(string $flagPath, int $seconds): bool
    {
        return File::exists($flagPath) && (time() - File::lastModified($flagPath)) < $seconds;
    }

    private function audioResponse(string $bytes, string $contentType, string $source, int $maxAge): Response
    {
        return response($bytes, 200, [
            'Content-Type' => $contentType,
            'Content-Length' => (string) strlen($bytes),
            'Cache-Control' => "public, max-age={$maxAge}",
            'X-TTS-Source' => $source,
        ]);
    }
}
