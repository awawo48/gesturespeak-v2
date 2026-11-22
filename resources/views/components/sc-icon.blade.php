@props([
    'name',
    'size' => 24,
    'alt' => '',
    'class' => '',
])

@php
    $allowed = [
        'abjad-nombor', 'bintang-kuiz', 'buku', 'guru', 'haiwan',
        'ibu-bapa', 'kamera-ai', 'keluarga', 'keluarga-guru', 'kod-pin',
        'nombor', 'pelajar', 'pelajar-cemerlang', 'perasaan', 'robot-ai',
        'soalan', 'suara-bm', 'trofi', 'ucapan',
    ];
    $src = $name === 'mascot'
        ? asset('media/brand/signcilik-icon.svg')
        : (in_array($name, $allowed, true)
            ? asset("media/icons/icon-{$name}.svg")
            : asset('media/icons/icon-buku.svg'));
@endphp

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    width="{{ $size }}"
    height="{{ $size }}"
    @if($alt === '') aria-hidden="true" @endif
    decoding="async"
    {{ $attributes->merge(['class' => trim("sc-icon {$class}")]) }}
>
