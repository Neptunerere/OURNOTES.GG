<x-layouts.wiki :title="$song->title">
    @php
        $difficulties = [
            ['key' => 'easy', 'short' => 'EZ', 'label' => 'EASY', 'level' => $song->easy, 'color' => '#22c55e'],
            ['key' => 'normal', 'short' => 'NM', 'label' => 'NORMAL', 'level' => $song->normal, 'color' => '#3b82f6'],
            ['key' => 'hard', 'short' => 'HD', 'label' => 'HARD', 'level' => $song->hard, 'color' => '#f59e0b'],
            ['key' => 'expert', 'short' => 'EX', 'label' => 'EXPERT', 'level' => $song->expert, 'color' => '#ef476f'],
        ];
    @endphp

    <main class="mx-auto max-w-[1300px] px-4 py-6 lg:px-8">
        <a href="{{ route('songs.index') }}" class="text-[11px] font-bold text-[#8b7cf6]">‹ 곡 목록</a>

        <div class="mt-4 grid gap-6 lg:grid-cols-[430px_minmax(0,1fr)]">
            <aside class="h-fit overflow-hidden rounded-xl border border-slate-200 bg-white">
                <div class="aspect-square bg-slate-100">
                    @if($song->image_url)
                        <img src="{{ $song->image_url }}" alt="{{ $song->title }} 재킷" class="h-full w-full object-cover">
                    @else
                        <div class="grid h-full place-items-center text-5xl text-slate-300">♫</div>
                    @endif
                </div>
                <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-4">
                    <div class="flex min-w-0 items-center gap-2">
                        <x-type-icon :type="$song->attribute" size="sm" />
                        <span class="truncate text-xs font-bold text-slate-300">{{ $song->type }}</span>
                    </div>
                    @if($song->source_id)<span class="shrink-0 text-[10px] text-slate-500">곡 ID #{{ $song->source_id }}</span>@endif
                </div>
            </aside>

            <section class="min-w-0 space-y-5">
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <header class="border-b border-slate-200 p-6 sm:p-8">
                        <x-band-logo :band="$song->band" size="md" />
                        <h1 class="mt-5 break-keep text-3xl font-black tracking-tight text-white sm:text-4xl">{{ $song->title }}</h1>
                        <p class="mt-3 text-sm font-bold text-[#b8adff]">{{ $song->band }}</p>
                    </header>

                    <div class="grid sm:grid-cols-2">
                        <dl class="divide-y divide-slate-200 p-6 sm:border-r sm:p-8">
                            <div class="flex justify-between gap-5 py-3 first:pt-0"><dt class="text-xs text-slate-500">작곡</dt><dd class="text-right text-xs font-bold text-slate-200">{{ $song->composer ?: '정보 없음' }}</dd></div>
                            <div class="flex justify-between gap-5 py-3"><dt class="text-xs text-slate-500">작사</dt><dd class="text-right text-xs font-bold text-slate-200">{{ $song->lyricist ?: '정보 없음' }}</dd></div>
                            <div class="flex justify-between gap-5 py-3"><dt class="text-xs text-slate-500">편곡</dt><dd class="text-right text-xs font-bold text-slate-200">{{ $song->arranger ?: '정보 없음' }}</dd></div>
                        </dl>
                        <dl class="divide-y divide-slate-200 p-6 sm:p-8">
                            <div class="flex justify-between gap-5 py-3 first:pt-0"><dt class="text-xs text-slate-500">밴드</dt><dd class="text-right text-xs font-bold text-slate-200">{{ $song->band }}</dd></div>
                            <div class="flex justify-between gap-5 py-3"><dt class="text-xs text-slate-500">BPM</dt><dd class="text-right text-xs font-bold text-slate-200">{{ $song->bpm ?: '정보 없음' }}</dd></div>
                            <div class="flex justify-between gap-5 py-3"><dt class="text-xs text-slate-500">격주</dt><dd class="text-right text-xs font-bold text-slate-200">{{ $song->gekiso ?: '정보 없음' }}</dd></div>
                        </dl>
                    </div>
                </article>

                <article class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8">
                    <div>
                        <p class="text-[10px] font-bold tracking-[0.14em] text-[#8b7cf6]">DIFFICULTY</p>
                        <h2 class="mt-1 text-lg font-black text-white">난이도 정보</h2>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach($difficulties as $difficulty)
                            <section class="relative overflow-hidden rounded-lg border border-slate-200 bg-[#17191f] p-5">
                                <span class="absolute inset-y-0 left-0 w-1" style="background-color: {{ $difficulty['color'] }}"></span>
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[10px] font-black tracking-[0.12em] text-slate-500">{{ $difficulty['short'] }}</p>
                                        <h3 class="mt-1 text-sm font-black text-slate-100">{{ $difficulty['label'] }}</h3>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[10px] font-bold text-slate-500">LEVEL</span>
                                        <b class="ml-2 text-2xl tabular-nums text-white">{{ $difficulty['level'] ?? '-' }}</b>
                                    </div>
                                </div>
                                <div class="mt-5 border-t border-slate-200 pt-3 text-xs text-slate-500">
                                    @if($difficulty['key'] === 'expert' && $song->note_count)
                                        노트 수 <b class="float-right tabular-nums text-slate-200">{{ number_format($song->note_count) }}</b>
                                    @else
                                        노트 수 <b class="float-right text-slate-600">정보 없음</b>
                                    @endif
                                </div>
                            </section>
                        @endforeach
                    </div>
                    <p class="mt-4 text-[10px] leading-5 text-slate-500">현재 수집된 노트 수는 EXPERT 난이도에만 표시됩니다.</p>
                </article>
            </section>
        </div>
    </main>
</x-layouts.wiki>
