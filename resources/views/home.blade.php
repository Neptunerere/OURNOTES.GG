<x-layouts.wiki :title="'OURNOTES.GG'">
    <main class="mx-auto grid max-w-[1600px] gap-6 px-4 py-5 lg:grid-cols-[230px_minmax(0,1fr)_300px] lg:px-8">
        <aside class="space-y-4">
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <h2 class="border-b bg-slate-50 px-4 py-3 text-xs font-black">탐색</h2>
                <nav class="py-1">@foreach ([['멤버 전체 보기',$members,route('members.index')],['곡 난이도 보기',$songs,route('songs.index')],['편성 시뮬레이터','5인',route('formation')]] as [$label,$count,$url])<a href="{{ $url }}" class="flex items-center justify-between px-4 py-2.5 text-xs hover:bg-slate-50"><span>{{ $label }}</span><b class="text-slate-400">{{ $count }}</b></a>@endforeach</nav>
            </section>
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <h2 class="border-b bg-slate-50 px-4 py-3 text-xs font-black">밴드</h2>
                <div class="py-1">@foreach($bands as $band)<a href="{{ route('members.index',['band'=>$band->band]) }}" class="flex items-center gap-2 px-4 py-2.5 text-xs hover:bg-slate-50"><x-band-logo :band="$band->band" size="sm" class="min-w-0 flex-1" /><span class="text-slate-400">{{ $band->character_count }}</span></a>@endforeach</div>
            </section>
            <div class="rounded-lg bg-[#262932] p-4 text-white"><p class="text-[10px] font-bold text-[#a99eff]">TEAM BUILDER</p><h3 class="mt-2 text-sm font-black">내 멤버로 최적 편성 찾기</h3><p class="mt-2 text-[11px] leading-5 text-slate-400">같은 밴드 보너스와 세부 능력치를 실시간 비교합니다.</p><a href="{{ route('formation') }}" class="mt-4 block rounded bg-[#6c5ce7] py-2 text-center text-xs font-bold">편성 분석 시작</a></div>
        </aside>
        <div class="min-w-0 space-y-5">
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b px-4 py-3"><div><h2 class="text-sm font-black">추천 멤버</h2><p class="mt-0.5 text-[10px] text-slate-400">스킬 상승량 기준 · Lv.90</p></div><a href="{{ route('members.index') }}" class="text-[11px] font-bold text-[#6c5ce7]">전체 보기 ›</a></div>
                <div class="grid lg:grid-cols-2">@foreach($featured as $member)<x-member-card :member="$member" />@endforeach</div>
            </section>
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b px-4 py-3"><div><h2 class="text-sm font-black">곡 난이도 순위</h2><p class="mt-0.5 text-[10px] text-slate-400">EXPERT 기준 상위 곡</p></div><a href="{{ route('songs.index') }}" class="text-[11px] font-bold text-[#6c5ce7]">전체 보기 ›</a></div>
                <div class="grid grid-cols-[36px_1fr_100px_52px] bg-slate-50 px-4 py-2 text-[10px] font-bold text-slate-400"><span>#</span><span>곡명</span><span>밴드</span><span class="text-center">EX</span></div>
                @foreach($songList as $song)<div class="grid grid-cols-[36px_1fr_100px_52px] items-center border-t border-slate-100 px-4 py-2.5 text-xs"><b class="text-slate-400">{{ $loop->iteration }}</b><span class="truncate font-bold">{{ $song->title }}</span><x-band-logo :band="$song->band" size="sm" :show-name="false" /><b class="mx-auto grid size-7 place-items-center rounded bg-[#f0edff] text-[#5d4bd8]">{{ $song->expert }}</b></div>@endforeach
            </section>
        </div>
        <aside class="space-y-4">
            <section class="rounded-lg border border-slate-200 bg-white p-4"><div class="flex items-center justify-between"><h2 class="text-xs font-black">DB 현황</h2></div><div class="mt-4 grid grid-cols-3 divide-x text-center"><div><b class="block text-lg">{{ $members }}</b><span class="text-[10px] text-slate-400">멤버</span></div><div><b class="block text-lg">{{ $characters }}</b><span class="text-[10px] text-slate-400">캐릭터</span></div><div><b class="block text-lg">{{ $songs }}</b><span class="text-[10px] text-slate-400">곡</span></div></div><p class="mt-4 border-t pt-3 text-[10px] leading-5 text-slate-400">데이터 · {{ $dataSyncedAt ? $dataSyncedAt.' 동기화' : '동기화 기록 없음' }}</p></section>
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white"><div class="border-b px-4 py-3"><h2 class="text-xs font-black">빠른 필터</h2></div><div class="p-3"><p class="mb-2 text-[10px] font-bold text-slate-400">카드 타입</p><div class="grid grid-cols-5 gap-1">@foreach(['레드','블루','그린','옐로우','퍼플'] as $type)<a href="{{ route('members.index',['type'=>$type]) }}" title="{{ $type }}" class="grid h-10 place-items-center rounded border border-slate-200 transition hover:border-[#6c5ce7] hover:bg-[#6c5ce7]/10"><x-type-icon :type="$type" /></a>@endforeach</div></div></section>
            <section class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-[10px] font-bold text-slate-400">최고 난이도</p><div class="mt-3 flex items-center justify-between"><div><b class="block text-sm">{{ $hardest?->title }}</b><x-band-logo :band="$hardest?->band" size="sm" class="mt-1" /></div><span class="grid size-10 place-items-center rounded bg-[#262932] text-sm font-black text-white">{{ $hardest?->expert }}</span></div></section>
        </aside>
    </main>
</x-layouts.wiki>
