<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bahasa Melayu Text-to-Speech (TTS) Controller for Kiko & Child Learning Mode.
 * 1. Disk MP3 cache (0 credits used on repeat plays)
 * 2. ElevenLabs API (configured Voice ID -> Premade Free-Tier Voice fallback if Library voice requires paid plan)
 * 3. Google Translate Bahasa Melayu TTS (tl=ms) automatic fallback
 */
class TtsController extends Controller
{
    /** Default ElevenLabs premade cartoon/childlike voice (Gigi) allowed on Free Tier API. */
    private const FREE_PREMADE_VOICE_ID = 'jBpfuIE2acCO8z3wKNLl';

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

        $apiKey = trim((string) config('services.elevenlabs.key'));
        $voiceId = trim((string) config('services.elevenlabs.voice_id', 'hVL8yfcfFgmDBjBybu2e'));
        $modelId = trim((string) config('services.elevenlabs.model_id', 'eleven_flash_v2_5'));

        $cacheDir = storage_path('app/public/tts');
        if (! File::isDirectory($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
        }

        $cacheKey = md5($voiceId.'|'.$modelId.'|'.mb_strtolower($cleanText));
        $cacheFile = $cacheDir.DIRECTORY_SEPARATOR.$cacheKey.'.mp3';

        // 1. Return cached MP3 immediately if already generated (0 API credits used!)
        if (File::exists($cacheFile) && File::size($cacheFile) > 256) {
            return response(File::get($cacheFile), 200, [
                'Content-Type' => 'audio/mpeg',
                'Cache-Control' => 'public, max-age=31536000',
                'X-TTS-Source' => 'elevenlabs-cache',
            ]);
        }

        // 2. Generate via ElevenLabs API (using Guzzle's `force_ip_resolve => v4` for Windows)
        if ($apiKey !== '') {
            $attempts = array_values(array_unique(array_filter([
                $voiceId,
                self::FREE_PREMADE_VOICE_ID,
            ])));

            foreach ($attempts as $tryVoiceId) {
                // Skip library voices already known to be blocked by ElevenLabs Free Tier (HTTP 402)
                $blockedMarker = $cacheDir.DIRECTORY_SEPARATOR.'blocked_'.md5($apiKey.'|'.$tryVoiceId).'.flag';
                if (File::exists($blockedMarker) && (time() - File::lastModified($blockedMarker)) < 21600) {
                    continue;
                }

                $tryCacheFile = $cacheDir.DIRECTORY_SEPARATOR.md5($tryVoiceId.'|'.$modelId.'|'.mb_strtolower($cleanText)).'.mp3';
                if (File::exists($tryCacheFile) && File::size($tryCacheFile) > 256) {
                    return response(File::get($tryCacheFile), 200, [
                        'Content-Type' => 'audio/mpeg',
                        'Cache-Control' => 'public, max-age=31536000',
                        'X-TTS-Source' => 'elevenlabs-cache',
                    ]);
                }

                $audioBytes = $this->requestElevenLabs($apiKey, $tryVoiceId, $modelId, $cleanText, $blockedMarker);
                if ($audioBytes !== null) {
                    File::put($tryCacheFile, $audioBytes);

                    return response($audioBytes, 200, [
                        'Content-Type' => 'audio/mpeg',
                        'Cache-Control' => 'public, max-age=31536000',
                        'X-TTS-Source' => $tryVoiceId === $voiceId ? 'elevenlabs-custom' : 'elevenlabs-premade',
                    ]);
                }
            }
        }

        // 3. Fallback: Google Translate Bahasa Melayu TTS (tl=ms)
        try {
            $fallbackFile = $cacheDir.DIRECTORY_SEPARATOR.'g_'.md5(mb_strtolower($cleanText)).'.mp3';
            if (File::exists($fallbackFile) && File::size($fallbackFile) > 256) {
                return response(File::get($fallbackFile), 200, [
                    'Content-Type' => 'audio/mpeg',
                    'Cache-Control' => 'public, max-age=86400',
                    'X-TTS-Source' => 'google-ms-cache',
                ]);
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

                return response($gResp->body(), 200, [
                    'Content-Type' => 'audio/mpeg',
                    'Cache-Control' => 'public, max-age=86400',
                    'X-TTS-Source' => 'google-ms-live',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Google Malay TTS fallback failed: '.$e->getMessage());
        }

        return response('', 503);
    }

    private function requestElevenLabs(string $apiKey, string $voiceId, string $modelId, string $text, ?string $blockedMarker = null): ?string
    {
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
                    'voice_settings' => [
                        'stability' => 0.45,
                        'similarity_boost' => 0.80,
                        'style' => 0.35,
                        'use_speaker_boost' => true,
                    ],
                ]);

            if ($resp->successful() && strlen($resp->body()) > 256) {
                return $resp->body();
            }

            if ($resp->status() === 402 && $blockedMarker) {
                File::put($blockedMarker, (string) time());
            }

            Log::warning("ElevenLabs TTS notice for voice {$voiceId} (HTTP {$resp->status()}): ".mb_substr($resp->body(), 0, 300));
        } catch (\Throwable $e) {
            Log::warning("ElevenLabs TTS connection error for voice {$voiceId}: ".$e->getMessage());
        }

        return null;
    }
}
