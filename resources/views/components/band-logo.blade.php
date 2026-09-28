@props(['band', 'size' => 'md', 'colored' => true, 'showName' => true])
@php
    $normalized = mb_strtolower(preg_replace('/[\s!！\-]/u', '', $band ?? ''));
    $bandId = match (true) {
        str_contains($normalized, 'mygo'), str_contains($normalized, '마이고') => 1,
        str_contains($normalized, 'avemujica'), str_contains($normalized, '아베무지카') => 2,
        str_contains($normalized, 'mugendai'), str_contains($normalized, 'mewtype'), str_contains($normalized, 'myutype'), str_contains($normalized, '무겐다이') => 3,
        str_contains($normalized, 'millsage'), str_contains($normalized, '밀세이지') => 4,
        str_contains($normalized, 'ikkadumbrock'), str_contains($normalized, '일가dumbrock') => 5,
        default => null,
    };
    $heightClass = ['sm' => 'h-5 max-w-24', 'md' => 'h-7 max-w-32', 'lg' => 'h-10 max-w-44'][$size] ?? 'h-7 max-w-32';
@endphp
<span {{ $attributes->class(['inline-flex min-w-0 items-center gap-2']) }}>
    @if($bandId)
        <img src="{{ asset('images/ui/band-'.$bandId.($colored ? 'c' : '').'.webp') }}" alt="{{ $band }} 로고" title="{{ $band }}" class="{{ $heightClass }} w-auto shrink-0 object-contain object-left">
    @endif
    @if($showName)<span class="truncate">{{ $band }}</span>@endif
</span>
