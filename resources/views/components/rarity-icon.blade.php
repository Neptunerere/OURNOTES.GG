@props(['rarity', 'size' => 'md', 'name' => null, 'sourceId' => null])
@php $heightClass = ['sm' => 'h-4', 'md' => 'h-5', 'lg' => 'h-7'][$size] ?? 'h-5'; @endphp
@if($rarity === '생일' || $sourceId === 64 || str_contains(mb_strtolower((string) $name), 'happy birthday'))
    <img src="https://bdon.moe/assets/RarityIcon_BD.png" alt="생일 카드" title="생일" {{ $attributes->class([$heightClass, 'w-auto shrink-0 object-contain']) }}>
@elseif($rarity === '스페셜' || $sourceId === 70 || str_contains(mb_strtolower((string) $name), 'abracadabra') || str_contains((string) $name, '아브라카다브라'))
    <img src="https://bdon.moe/assets/SP_CardRarityIcon_EX.png" alt="스페셜 카드" title="스페셜" {{ $attributes->class([$heightClass, 'w-auto shrink-0 object-contain']) }}>
@elseif(in_array($rarity, ['R', 'SR', 'SSR'], true))
    <img src="{{ asset('images/ui/rar-'.$rarity.'.webp') }}" alt="{{ $rarity }} 희귀도" title="{{ $rarity }}" {{ $attributes->class([$heightClass, 'w-auto shrink-0 object-contain']) }}>
@else
    <span {{ $attributes->class(['text-xs font-bold']) }}>{{ $rarity }}</span>
@endif
