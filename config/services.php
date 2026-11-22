<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'elevenlabs' => [
        'key' => env('ELEVENLABS_API_KEY', 'sk_f0c59da1375d4b69f2c02e51e8851123447057d7b17a3c60'),
        'voice_id' => env('ELEVENLABS_VOICE_ID', 'JOCyls6Fdo4qfmTUnFrJ'),
        'model_id' => env('ELEVENLABS_MODEL_ID', 'eleven_v4'),
        'language_code' => env('ELEVENLABS_LANGUAGE_CODE', 'ms'),
        'stability' => (float) env('ELEVENLABS_STABILITY', 0.50),
        'similarity_boost' => (float) env('ELEVENLABS_SIMILARITY_BOOST', 0.75),
        'clone_strength' => (float) env('ELEVENLABS_CLONE_STRENGTH', 0.0),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'tts_model' => env('GEMINI_TTS_MODEL', 'gemini-3.8-flash-tts'),
        'tts_voice' => env('GEMINI_TTS_VOICE', 'Zephyr'),
    ],

    'tts' => [
        'primary' => env('TTS_PRIMARY', 'elevenlabs'),
    ],

];
