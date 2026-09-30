<x-layouts.wiki :title="$event['title']">
    <main class="mx-auto max-w-[1200px] px-4 py-7 lg:px-8 lg:py-10">
        <a href="{{ route('events.index') }}" class="text-[11px] font-bold text-[#9d90ff]">‹ 이벤트 목록</a>

        <section class="mt-4 overflow-hidden rounded-2xl border border-[#343b50] bg-[#111b2f]">
            <div class="relative aspect-[2.4/1] min-h-52 overflow-hidden bg-[#0b1426]">
                <img src="{{ $event['banner'] }}" alt="{{ $event['title'] }} 배너" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-transparent to-[#071022]/40"></div>
                <img src="{{ $event['logo'] }}" alt="{{ $event['title'] }} 로고" class="absolute right-[8%] top-1/2 w-[38%] -translate-y-1/2 drop-shadow-2xl">
                <span class="absolute left-4 top-4 rounded-full border border-white/15 bg-[#202741]/90 px-3 py-1 text-[10px] font-bold text-white">● {{ $event['status'] }}</span>
            </div>
            <div class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[1fr_auto]">
                <div>
                    <div class="text-[10px] font-bold text-slate-400">이벤트 ID #{{ $event['id'] }}</div>
                    <h1 class="mt-2 text-2xl font-black text-white">{{ $event['title'] }}</h1>
                    <dl class="mt-5 grid gap-2 text-xs sm:grid-cols-[145px_1fr]">
                        <dt class="whitespace-nowrap font-bold text-slate-500">개최 기간</dt><dd class="text-slate-200">{{ $event['starts_at'] }} – {{ $event['ends_at'] }}</dd>
                        <dt class="whitespace-nowrap font-bold text-slate-500">이벤트 화면 공개 종료</dt><dd class="text-slate-200">{{ $event['display_ends_at'] }}</dd>
                        <dt class="whitespace-nowrap font-bold text-slate-500">시간대</dt><dd class="text-slate-200">현지 시간 ({{ $event['timezone'] }})</dd>
                    </dl>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 p-4 lg:self-end">
                    <img src="{{ $event['badge'] }}" alt="이벤트 아이템" class="size-14 object-contain">
                    <div><p class="text-[10px] text-slate-500">이벤트 아이템</p><p class="mt-1 text-xs font-bold text-white">이벤트 배지(감청)</p></div>
                </div>
            </div>
        </section>

        <section class="mt-7 rounded-xl border border-[#343742] bg-[#17191f] p-5 sm:p-6">
            <h2 class="text-sm font-black text-white">이벤트 곡</h2>
            <div class="mt-4 flex items-center gap-4">
                <img src="{{ $event['song']['image'] }}" alt="{{ $event['song']['title'] }}" class="size-20 rounded-lg object-cover">
                <div><h3 class="text-sm font-black text-slate-100">{{ $event['song']['title'] }}</h3><p class="mt-1 text-xs text-slate-400">{{ $event['song']['band'] }}</p></div>
            </div>
        </section>

        <section class="mt-7 rounded-xl border border-[#343742] bg-[#17191f] p-5 sm:p-6">
            <h2 class="text-sm font-black text-white">이벤트 보너스</h2>
            @foreach([['멤버 카드', $event['member_bonuses']], ['서포트 카드', $event['support_bonuses']]] as [$heading, $bonuses])
                <h3 class="mb-3 mt-6 text-xs font-black text-slate-300">{{ $heading }}</h3>
                <div class="overflow-x-auto rounded-lg border border-white/10">
                    <table class="w-full min-w-[600px] text-left text-xs">
                        <thead class="bg-[#20232a] text-[10px] text-slate-500"><tr><th class="px-4 py-3">조건</th><th class="px-4 py-3 text-center">능력치</th><th class="px-4 py-3 text-center">이벤트 아이템</th></tr></thead>
                        <tbody class="divide-y divide-white/10">
                            @foreach($bonuses as $bonus)
                                <tr><td class="flex items-center gap-3 px-4 py-3"><img src="{{ $bonus['image'] }}" alt="" class="size-11 rounded-md object-cover object-top"><span><b class="block text-slate-200">{{ $bonus['name'] }}</b><span class="mt-0.5 block text-[10px] text-slate-500">{{ $bonus['card'] }}</span></span></td><td class="px-4 py-3 text-center font-bold text-[#b8adff]">{{ $bonus['stats'] }}</td><td class="px-4 py-3 text-center font-bold text-[#b8adff]">{{ $bonus['item'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
            <p class="mt-4 text-[10px] leading-5 text-slate-500">여러 조건을 충족하면 각 보너스 수치가 합산됩니다.</p>
        </section>

        <section class="mt-7 rounded-xl border border-[#343742] bg-[#17191f] p-5 sm:p-6">
            <h2 class="text-sm font-black text-white">이벤트 카드</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach($event['event_cards'] as $card)
                    <div class="flex items-center gap-4 rounded-lg border border-white/10 bg-[#20232a] p-3"><img src="{{ $card['image'] }}" alt="{{ $card['name'] }}" class="h-24 w-20 rounded-md object-cover object-top"><p class="text-xs font-bold text-slate-200">{{ $card['name'] }}</p></div>
                @endforeach
            </div>
        </section>

        <section class="mt-7 rounded-xl border border-[#343742] bg-[#17191f] p-5 sm:p-6" x-data="{expanded:false}">
            <div class="flex items-center justify-between"><h2 class="text-sm font-black text-white">이벤트 포인트 보상</h2><button type="button" @click="expanded=!expanded" class="text-[10px] font-bold text-[#9d90ff]" x-text="expanded ? '접기' : '전체 보기'"></button></div>
            <div class="mt-5 rounded-xl border border-[#7568c9]/30 bg-[#6c5ce7]/5 p-4 sm:p-5">
                <div class="flex flex-wrap items-end justify-between gap-2">
                    <h3 class="text-xs font-black text-slate-200">보상 합계</h3>
                    <span class="text-[10px] text-slate-500">3,000,000 pt 달성 기준</span>
                </div>
                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($event['reward_totals'] as $total)
                        <div class="flex min-h-16 items-center gap-3 rounded-lg border border-white/10 bg-[#20232a] px-3 py-2.5">
                            <img src="{{ $total['image'] }}" alt="" class="size-11 shrink-0 object-contain">
                            <div class="min-w-0"><p class="truncate text-[10px] text-slate-400" title="{{ $total['name'] }}">{{ $total['name'] }}</p><b class="mt-0.5 block text-sm text-white">{{ $total['total'] }}</b></div>
                        </div>
                    @endforeach
                </div>
                <p class="mt-3 text-[10px] leading-5 text-slate-500">3,000,000 pt 이후 반복 지급되는 이벤트 배지는 합계에 포함하지 않았습니다.</p>
            </div>
            <div class="mt-4 grid gap-px overflow-hidden rounded-lg border border-white/10 bg-white/10 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($event['point_rewards'] as $index => [$points, $reward])
                    @php
                        $rewardImage = collect($event['reward_images'])->first(fn ($image, $label) => str_contains($reward, $label));
                    @endphp
                    <div x-show="expanded || {{ $index }} < 12" class="flex min-h-16 items-center gap-3 bg-[#20232a] px-4 py-3 text-xs">
                        @if($rewardImage)<img src="{{ $rewardImage }}" alt="" class="size-10 shrink-0 object-contain">@endif
                        <div class="min-w-0"><b class="block text-[#b8adff]">{{ $points }} pt</b><span class="mt-0.5 block text-slate-300">{{ $reward }}</span></div>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 flex items-center gap-2 text-[10px] text-slate-500"><img src="{{ $event['badge'] }}" alt="" class="size-6">3,000,000 pt 이후 1,000,000 pt마다 이벤트 배지(감청) ×100,000 획득</p>
        </section>

        <section class="mt-7 rounded-xl border border-[#343742] bg-[#17191f] p-5 sm:p-6">
            <h2 class="text-sm font-black text-white">라이브 보상</h2>
            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                @foreach([['라이브', $event['live_rewards']], ['챌린지 라이브', $event['challenge_rewards']]] as [$heading, $rewards])
                    <div><h3 class="mb-3 text-xs font-black text-slate-300">{{ $heading }}</h3><div class="overflow-hidden rounded-lg border border-white/10"><table class="w-full text-center text-xs"><thead class="bg-[#20232a] text-[10px] text-slate-500"><tr><th class="px-3 py-3">스코어 랭크</th><th class="px-3 py-3">pt</th><th class="px-3 py-3">아이템</th></tr></thead><tbody class="divide-y divide-white/10">@foreach($rewards as [$rank, $points, $items])<tr><td class="px-3 py-3 font-black text-white">{{ $rank }}</td><td class="px-3 py-3 text-slate-300">{{ $points }}</td><td class="px-3 py-3 text-slate-300"><span class="inline-flex items-center gap-1"><img src="{{ $event['badge'] }}" alt="" class="size-5">×{{ $items }}</span></td></tr>@endforeach</tbody></table></div></div>
                @endforeach
            </div>
        </section>
    </main>
</x-layouts.wiki>
