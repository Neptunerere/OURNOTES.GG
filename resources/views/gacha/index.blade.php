<x-layouts.wiki :title="'진행 중 뽑기'">
    <main class="mx-auto max-w-[1500px] px-4 py-7 lg:px-8 lg:py-10">
        <header class="mb-7">
            <p class="mb-2 text-[10px] font-black uppercase tracking-[0.24em] text-[#9d90ff]">Recruitment</p>
            <h1 class="text-xl font-black text-white">뽑기</h1>
            <p class="mt-2 text-xs text-slate-400">현재 진행 중인 뽑기와 픽업, 상시 모집 정보를 확인하세요. 시간은 한국 시간(UTC+9) 기준입니다.</p>
        </header>

        <div class="space-y-9">
            <section>
                <div class="mb-3 flex items-end justify-between">
                    <div><h2 class="text-sm font-black text-slate-100">기간 한정 뽑기</h2><p class="mt-1 text-[10px] text-slate-500">픽업 멤버 모집 기간</p></div>
                    <span class="text-[10px] font-bold text-slate-500">{{ count($limitedGachas) }}종</span>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($limitedGachas as $gacha)
                        @php
                            $statusClasses = match ($gacha['status']) {
                                '진행 중' => 'border-emerald-300/40 bg-emerald-950/90 text-emerald-100',
                                '예정' => 'border-amber-300/40 bg-amber-950/90 text-amber-100',
                                default => 'border-red-300/40 bg-red-950/90 text-red-100',
                            };
                        @endphp
                        <a href="{{ route('gacha.show', $gacha['id']) }}" class="group block overflow-hidden rounded-[22px] border border-[#34435e] bg-[#111d33] shadow-lg shadow-black/10 transition-[transform,border-color,box-shadow] duration-200 ease-out hover:-translate-y-1 hover:border-[#7f8fb0] hover:shadow-2xl hover:shadow-black/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#9d90ff]">
                            <div class="relative h-[158px] overflow-hidden bg-gradient-to-br {{ $gacha['theme'] }} sm:h-[174px]">
                                <img src="{{ $gacha['banner'] ?? $gacha['image'] }}" alt="{{ $gacha['image_alt'] }}" class="pointer-events-none block h-full w-full object-cover object-center" loading="lazy">
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-[#101a2b]/25 via-transparent to-[#101a2b]/10"></div>
                                <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-[#111d33] to-transparent"></div>
                                <div class="absolute left-3 top-3 flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[9px] font-black shadow {{ $statusClasses }}">
                                    <span class="size-1.5 rounded-full bg-current"></span>{{ $gacha['status'] }}
                                    @if($gacha['status'] !== '종료')
                                        <span data-countdown data-status="{{ $gacha['status'] }}" data-start="{{ $gacha['start_timestamp'] }}" data-end="{{ $gacha['end_timestamp'] }}" class="font-medium opacity-80"></span>
                                    @endif
                                </div>
                                <span class="absolute right-3 top-3 rounded-full border border-white/20 bg-[#10172a]/75 px-2.5 py-1 text-[9px] font-black text-white">한정 · PICK UP</span>
                            </div>
                            <div class="px-4 pb-4 pt-1">
                                <h3 class="text-[13px] font-black leading-5 text-slate-100 transition group-hover:text-[#c4bbff]">{{ $gacha['title'] }}</h3>
                                <p class="mt-1.5 text-[10px] text-slate-400">{{ $gacha['time_label'] }}</p>
                                <time class="sr-only">{{ $gacha['legacy_time_label'] }}</time>
                                <p class="mt-0.5 text-[10px] text-slate-500">멤버 {{ $gacha['member_count'] }} · 서포트 {{ $gacha['support_count'] }}</p>
                                <div class="mt-3 flex min-h-8 items-center gap-1.5 border-t border-white/10 pt-2.5">
                                    <span class="mr-1 text-[9px] font-black text-sky-200">PICK UP</span>
                                    @foreach($gacha['members'] as $member)
                                        <img src="{{ $member->image_url }}" alt="{{ $member->character->name }}" title="{{ $member->character->name }}" class="size-7 rounded-full border border-white/20 bg-slate-800 object-cover" loading="lazy">
                                    @endforeach
                                    @foreach($gacha['supports'] as $support)
                                        <img src="{{ $support->image_url }}" alt="{{ $support->character_name }}" title="{{ $support->character_name }}" class="size-7 rounded-full border border-white/20 bg-slate-800 object-cover" loading="lazy">
                                    @endforeach
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section>
                <div class="mb-3 flex items-end justify-between">
                    <div><h2 class="text-sm font-black text-slate-100">상시 뽑기</h2><p class="mt-1 text-[10px] text-slate-500">언제든 이용할 수 있는 뽑기</p></div>
                    <span class="text-[10px] font-bold text-slate-500">{{ count($regularGachas) }}종</span>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($regularGachas as $gacha)
                        <a href="{{ route('gacha.show', $gacha['id']) }}" class="group block overflow-hidden rounded-[22px] border border-[#34435e] bg-[#111d33] shadow-lg shadow-black/10 transition-[transform,border-color,box-shadow] duration-200 ease-out hover:-translate-y-1 hover:border-[#7f8fb0] hover:shadow-2xl hover:shadow-black/30 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#9d90ff]">
                            <div class="relative h-[158px] overflow-hidden bg-gradient-to-br from-[#f2f5ff] via-[#dce9ff] to-[#a9c8f3] sm:h-[174px]">
                                <img src="{{ $gacha['banner'] ?? $gacha['image'] }}" alt="{{ $gacha['title'] }} 배너" class="pointer-events-none block h-full w-full object-cover object-center" loading="lazy">
                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#111d33]/20 to-transparent"></div>
                                <span class="absolute left-3 top-3 rounded-full border border-emerald-200/30 bg-emerald-950/85 px-2.5 py-1 text-[9px] font-black text-emerald-100"><span class="mr-1 text-emerald-300">●</span>상시</span>
                            </div>
                            <div class="px-4 pb-4 pt-3">
                                <h3 class="text-[13px] font-black leading-5 text-slate-100 transition group-hover:text-[#c4bbff]">{{ $gacha['title'] }}</h3>
                                <p class="mt-1.5 text-[10px] text-slate-400">상시</p>
                                <p class="mt-0.5 text-[10px] text-slate-500">멤버 {{ $gacha['member_count'] ?: '—' }}@if($gacha['support_count'] !== null) · 서포트 {{ $gacha['support_count'] }}@endif</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </main>
    <script>
        (() => {
            const updateCountdowns = () => {
                const now = Math.floor(Date.now() / 1000);
                document.querySelectorAll('[data-countdown]').forEach((label) => {
                    const boundary = Number(label.dataset.status === '예정' ? label.dataset.start : label.dataset.end);
                    const remaining = Math.max(0, boundary - now);
                    const days = Math.floor(remaining / 86400);
                    const hours = Math.floor((remaining % 86400) / 3600);
                    label.textContent = label.dataset.status === '예정'
                        ? `· ${days > 0 ? `${days}일 후 시작` : `${hours}시간 후 시작`}`
                        : `· ${days}일 남음`;
                });
            };
            updateCountdowns();
            window.setInterval(updateCountdowns, 60000);
        })();
    </script>
</x-layouts.wiki>
