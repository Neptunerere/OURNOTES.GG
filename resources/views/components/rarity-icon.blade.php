@props(['rarity', 'size' => 'md'])
@php $heightClass = ['sm' => 'h-4', 'md' => 'h-5', 'lg' => 'h-7'][$size] ?? 'h-5'; @endphp
@if(in_array($rarity, ['R', 'SR', 'SSR'], true))
    <img src="{{ asset('images/ui/rar-'.$rarity.'.webp') }}" alt="{{ $rarity }} 희귀도" title="{{ $rarity }}" {{ $attributes->class([$heightClass, 'w-auto shrink-0 object-contain']) }}>
@else
    <span {{ $attributes->class(['text-xs font-bold']) }}>{{ $rarity }}</span>
@endif
