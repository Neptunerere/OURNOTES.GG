@props(['type', 'size' => 'md'])
@php
    $typeId = ['레드' => 1, '블루' => 2, '그린' => 3, '옐로우' => 4, '퍼플' => 5][$type] ?? null;
    $sizeClass = ['sm' => 'size-5', 'md' => 'size-7', 'lg' => 'size-9'][$size] ?? 'size-7';
@endphp
@if($typeId)
    <img src="{{ asset('images/ui/attr-'.$typeId.'.webp') }}" alt="{{ $type }} 타입" title="{{ $type }}" {{ $attributes->class([$sizeClass, 'shrink-0 object-contain']) }}>
@else
    <span {{ $attributes->class(['text-xs text-slate-400']) }}>{{ $type }}</span>
@endif
