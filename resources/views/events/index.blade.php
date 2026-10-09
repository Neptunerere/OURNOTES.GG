<x-layouts.wiki :title="'이벤트'">
    <main class="mx-auto max-w-[1500px] px-4 py-7 lg:px-8 lg:py-10">
        <div class="mb-7 border-b border-[#30343d] pb-6">
            <h1 class="text-xl font-black text-white">이벤트</h1>
            <p class="mt-2 text-xs text-slate-400">개최 기간과 이벤트 보너스, 이벤트 곡과 보상을 확인하세요.</p>
        </div>

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($events as $event)
                @php
                    $statusClasses = match ($event['status']) {
                        '진행 중' => 'border-emerald-300/40 bg-emerald-950/90 text-emerald-100',
                        '종료' => 'border-red-300/40 bg-red-950/90 text-red-100',
                        default => 'border-amber-300/40 bg-amber-950/90 text-amber-100',    // default가 예정이라고 생각되어 추가함
                    };
                @endphp
                <a href="{{ route('events.show', $event['id']) }}" class="group overflow-hidden rounded-2xl border border-[#343b50] bg-[#111b2f] transition duration-200 hover:-translate-y-1 hover:border-[#7568c9] hover:shadow-2xl hover:shadow-black/30">
                    <div class="relative aspect-[2/0.86] overflow-hidden bg-[#0b1426]">
                        <img src="{{ $event['banner'] ?? '/images/events/1/banner.webp' }}" alt="{{ $event['title'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]">
                        @if(!empty($event['logo']))<img src="{{ $event['logo'] }}" alt="" class="absolute right-5 top-1/2 w-[46%] -translate-y-1/2 drop-shadow-2xl">@endif
                        <span class="absolute left-4 top-3 rounded-full border px-3 py-1 text-[10px] font-bold shadow {{ $statusClasses }}"><span class="mr-1.5 inline-block size-1.5 rounded-full bg-current"></span>{{ $event['status'] }}</span>
                    </div>
                    <div class="p-4">
                        <h2 class="text-sm font-black text-white group-hover:text-[#c4bbff]">{{ $event['title'] }}</h2>
                        <p class="mt-1.5 text-[11px] text-slate-400">{{ $event['starts_at'] }} – {{ $event['ends_at'] }}</p>
                        <div class="mt-4 flex items-center gap-3 border-t border-white/10 pt-3">
                            <span class="text-[10px] font-bold text-slate-300">이벤트 보너스</span>
                            <div class="flex -space-x-1.5">
                                @foreach(array_slice($event['member_bonuses'] ?? [], 0, 5) as $bonus)
                                    <img src="{{ $bonus['image'] }}" alt="{{ $bonus['name'] }}" class="size-7 rounded-full border-2 border-[#111b2f] object-cover object-top">
                                @endforeach
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </main>
</x-layouts.wiki>
