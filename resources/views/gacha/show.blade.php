<x-layouts.wiki :title="$event['title']">
    <main class="mx-auto max-w-[1180px] px-4 py-7 lg:px-8 lg:py-10">
        <a href="{{ route('gacha.index') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-400 transition hover:text-white">← 뽑기 목록</a>

        <article class="mt-5 overflow-hidden rounded-2xl border border-[#34435e] bg-[#111d33] shadow-2xl shadow-black/20">
            <div class="relative h-48 overflow-hidden bg-gradient-to-br {{ $event['theme'] }} sm:h-72">
                <img src="{{ $event['banner'] ?? $event['image'] }}" alt="{{ $event['image_alt'] }}" class="block h-full w-full object-cover object-center">
                <div class="absolute inset-0 bg-gradient-to-t from-[#111d33] via-[#111d33]/10 to-transparent"></div>
                <span class="absolute left-4 top-4 rounded-full border border-sky-200/30 bg-sky-950/85 px-3 py-1.5 text-[10px] font-black text-sky-100">● {{ $event['permanent'] ? '상시' : $event['status'] }}</span>
                <div class="absolute inset-x-0 bottom-0 p-5 sm:p-7">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-sky-200">{{ $event['permanent'] ? 'Permanent' : 'Limited · Pick Up' }}</p>
                    <h1 class="mt-2 text-xl font-black text-white sm:text-2xl">{{ $event['title'] }}</h1>
                    <p class="mt-2 max-w-2xl text-xs leading-5 text-slate-300">{{ $event['description'] }}</p>
                </div>
            </div>

            <div class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[minmax(0,1fr)_300px]">
                <div class="space-y-7">
                    @if(count($event['pickup_members']) || count($event['pickup_supports']))
                    <section>
                        <h2 class="text-sm font-black text-white">픽업 카드</h2>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach($event['pickup_members'] as $member)
                                @if($member->slug)
                                <a href="{{ route('members.show', $member) }}" class="flex items-center gap-3 rounded-xl border border-white/10 bg-[#17243a] p-3 transition-colors hover:border-[#8b7cf6]/60 hover:bg-[#1b2942]">
                                @else
                                <div class="flex items-center gap-3 rounded-xl border border-white/10 bg-[#17243a] p-3">
                                @endif
                                    <img src="{{ $member->image_url }}" alt="{{ $member->name }}" class="size-16 rounded-lg bg-slate-800 object-cover">
                                    <div class="min-w-0"><span class="text-[9px] font-black text-amber-300">{{ $member->rarity }}</span><h3 class="truncate text-xs font-black text-white">{{ $member->name }}</h3><p class="mt-1 truncate text-[10px] text-slate-400">{{ $member->character?->name }}</p></div>
                                @if($member->slug)
                                </a>
                                @else
                                </div>
                                @endif
                            @endforeach
                            @foreach($event['pickup_supports'] as $snapshot)
                                <a href="{{ route('snapshots.show', $snapshot) }}" class="flex items-center gap-3 rounded-xl border border-white/10 bg-[#17243a] p-3 transition-colors hover:border-[#8b7cf6]/60 hover:bg-[#1b2942]">
                                    <img src="{{ $snapshot->image_url }}" alt="{{ $snapshot->name }}" class="size-16 rounded-lg bg-slate-800 object-cover">
                                    <div class="min-w-0"><span class="text-[9px] font-black text-amber-300">{{ $snapshot->rarity }}</span><h3 class="truncate text-xs font-black text-white">{{ $snapshot->name }}</h3><p class="mt-1 truncate text-[10px] text-slate-400">{{ $snapshot->character_name }}</p></div>
                                </a>
                            @endforeach
                        </div>
                    </section>
                    @endif

                    <section>
                        <div class="mb-3 flex items-center justify-between"><h2 class="text-sm font-black text-white">등장 확률</h2><span class="text-[10px] text-slate-500">{{ $event['timezone'] }} 기준</span></div>
                        @if(count($event['rates']))
                            <div class="overflow-hidden rounded-xl border border-white/10">
                                <table class="w-full text-left text-xs"><thead class="bg-[#202b40] text-[10px] text-slate-400"><tr><th class="px-3 py-2.5">등급</th><th class="px-3 py-2.5">종류</th><th class="px-3 py-2.5 text-right">확률</th><th class="px-3 py-2.5 text-right">카드 수</th></tr></thead>
                                    <tbody class="divide-y divide-white/10">@foreach($event['rates'] as $rate)<tr><td class="px-3 py-3 font-black text-amber-200">{{ $rate['rarity'] }}</td><td class="px-3 py-3 text-slate-300">{{ $rate['category'] }}@if($rate['pickup']) <span class="ml-1 text-[9px] text-pink-300">픽업 {{ $rate['pickup'] }}장</span>@endif</td><td class="px-3 py-3 text-right font-bold text-white">{{ $rate['rate'] }}</td><td class="px-3 py-3 text-right text-slate-400">{{ $rate['pool'] }}종</td></tr>@endforeach</tbody>
                                </table>
                            </div>
                        @endif
                    </section>

                    <section x-data="gachaSimulator(@js($event['draw_pools']), {{ $event['ten_pull_guarantee'] ? 'true' : 'false' }})" class="rounded-xl border border-white/10 bg-[#17243a] p-4 sm:p-5">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div><h2 class="text-sm font-black text-white">뽑기 시뮬레이터</h2><p class="mt-1 text-[10px] text-slate-400">확률표 기준으로 1회 또는 10회 모집 결과를 확인해 보세요.</p></div>
                            <div class="flex gap-2">
                                <button type="button" @click="draw(1)" class="rounded-lg border border-white/15 bg-[#111d33] px-3 py-2 text-[11px] font-bold text-slate-200 transition hover:border-[#9d90ff]">1회 뽑기</button>
                                <button type="button" @click="draw(10)" class="rounded-lg border border-[#7568c9] bg-[#b7d7ff] px-3 py-2 text-[11px] font-black text-[#142038] transition hover:bg-[#c8e1ff]">10연차</button>
                                <button x-show="totalDraws > 0" type="button" @click="clear()" class="px-2 text-[10px] font-bold text-slate-400 transition hover:text-white">기록 지우기</button>
                            </div>
                        </div>
                        <template x-if="results.length">
                            <div class="mt-4">
                                <div class="mb-3 flex flex-wrap items-center gap-2">
                                    <span x-text="`${totalDraws}회`" class="rounded-full bg-[#202d45] px-2.5 py-1 text-[10px] font-black text-white"></span>
                                    <template x-for="summary in summary()" :key="summary.rarity">
                                        <span x-text="`${summary.rarity} ${summary.count} ${summary.percent}%`" class="rounded-full bg-[#202d45] px-2.5 py-1 text-[10px] font-bold text-slate-300"></span>
                                    </template>
                                </div>
                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                                    <template x-for="(result, index) in results" :key="`${totalDraws}-${index}`">
                                        <div class="relative min-w-0 overflow-hidden rounded-xl border border-white/10 bg-[#101a2b]">
                                            <div class="aspect-[3/4] overflow-hidden bg-[#202d45]">
                                                <img x-show="result.image" :src="result.image" :alt="result.name" class="h-full w-full object-cover object-center">
                                                <div x-show="!result.image" x-text="result.rarity" class="grid h-full place-items-center text-lg font-black text-amber-200"></div>
                                            </div>
                                            <span x-text="result.rarity" class="absolute left-1.5 top-1.5 rounded-full bg-[#101a2b]/85 px-2 py-1 text-[9px] font-black text-amber-200"></span>
                                            <span x-show="result.pickup" class="absolute right-1.5 top-1.5 rounded-full bg-pink-600/90 px-2 py-1 text-[8px] font-black text-white">PICK UP</span>
                                            <div class="min-h-12 p-2"><p x-text="result.name" class="line-clamp-2 text-[10px] font-bold leading-4 text-slate-200"></p><p x-text="result.category" class="mt-0.5 text-[9px] text-slate-500"></p></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <p class="mt-3 text-[10px] leading-4 text-slate-500">등급·종류 확률은 이 모집의 표를 따르며, 카드명은 사이트에 등록된 카드 데이터 중에서 표시합니다. 결과는 실제 게임 모집과 무관합니다.@if($event['ten_pull_guarantee']) 10회 모집의 마지막 1회는 SR 이상 확정입니다.@endif</p>
                    </section>
                </div>

                <aside class="h-fit rounded-xl border border-white/10 bg-[#17243a] p-4">
                    <h2 class="text-sm font-black text-white">모집 정보</h2>
                    <dl class="mt-4 space-y-3 text-[11px]">
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">상태</dt><dd class="font-bold text-slate-200">{{ $event['permanent'] ? '상시' : $event['status'] }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">개최 기간</dt><dd class="text-right text-slate-200">@if($event['permanent']) 상시 모집 @else {{ \Illuminate\Support\Carbon::parse($event['starts_at_raw'], 'Asia/Seoul')->format('Y. m. d. H:i') }}<br>~ {{ \Illuminate\Support\Carbon::parse($event['ends_at_raw'], 'Asia/Seoul')->format('Y. m. d. H:i') }} @endif</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">시간대</dt><dd class="text-slate-200">{{ $event['permanent'] ? 'UTC+8' : '한국 시간 (UTC+9)' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">멤버</dt><dd class="text-slate-200">{{ $event['member_count'] }}종</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-500">서포트</dt><dd class="text-slate-200">{{ $event['support_count'] }}종</dd></div>
                    </dl>
                    <p class="mt-4 border-t border-white/10 pt-3 text-[10px] leading-4 text-slate-500">모집 결과는 시뮬레이션이며 실제 게임 모집과 무관합니다.</p>
                </aside>
            </div>
        </article>
    </main>
    <script>
        window.gachaSimulator = (pools, tenPullGuarantee) => ({
            pools,
            tenPullGuarantee,
            results: [],
            totalDraws: 0,
            statistics: {},
            summary() {
                return Object.entries(this.statistics).map(([rarity, count]) => ({
                    rarity,
                    count,
                    percent: this.totalDraws ? (count * 100 / this.totalDraws).toFixed(1) : '0.0',
                }));
            },
            draw(count) {
                const drawn = [];
                for (let index = 0; index < count; index += 1) {
                    let available = this.pools;
                    if (count === 10 && index === 9 && this.tenPullGuarantee) {
                        available = available.filter((pool) => pool.rarity === 'SSR' || pool.rarity === 'SR' || pool.rarity === '생일');
                    }
                    const totalRate = available.reduce((total, pool) => total + pool.rate, 0);
                    let roll = Math.random() * totalRate;
                    let selected = available[available.length - 1];
                    for (const pool of available) {
                        roll -= pool.rate;
                        if (roll < 0) {
                            selected = pool;
                            break;
                        }
                    }
                    const card = selected.cards[Math.floor(Math.random() * selected.cards.length)];
                    drawn.push({ ...card, rarity: selected.rarity, category: selected.category });
                    this.statistics[selected.rarity] = (this.statistics[selected.rarity] || 0) + 1;
                }
                this.results = drawn;
                this.totalDraws += count;
            },
            clear() {
                this.results = [];
                this.totalDraws = 0;
                this.statistics = {};
            },
        });
    </script>
</x-layouts.wiki>
