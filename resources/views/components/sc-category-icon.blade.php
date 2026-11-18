@props([
    'category',
    'size' => 52,
    'class' => '',
])

@php
    $key = strtolower(trim(($category->slug ?? '') . ' ' . ($category->name ?? '') . ' ' . ($category->name_en ?? '')));
    $iconName = match (true) {
        str_contains($key, 'abjad') || str_contains($key, 'alphabet') => 'abjad-nombor',
        str_contains($key, 'nombor') || str_contains($key, 'number') => 'bintang-kuiz',
        str_contains($key, 'salam') || str_contains($key, 'ucap') || str_contains($key, 'greet') => 'ucapan',
        str_contains($key, 'keluarga') || str_contains($key, 'family') => 'keluarga',
        str_contains($key, 'haiwan') || str_contains($key, 'animal') => 'haiwan',
        str_contains($key, 'perasaan') || str_contains($key, 'feeling') => 'pelajar-cemerlang',
        str_contains($key, 'soalan') || str_contains($key, 'question') => 'robot-ai',
        default => 'buku',
    };
@endphp

<x-sc-icon :name="$iconName" :size="$size" :class="$class" />
