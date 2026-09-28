<x-layouts.wiki :title="$member->name">
    @php
        $growthRates = [
            2 => [1 => 0.4086, 30 => 0.6571, 40 => 0.7429, 50 => 0.8286, 70 => 1.0],
            3 => [1 => 0.4075, 30 => 0.625, 40 => 0.7, 50 => 0.775, 70 => 0.925, 80 => 1.0],
            4 => [1 => 0.4067, 30 => 0.6, 40 => 0.6667, 50 => 0.7333, 70 => 0.8667, 80 => 0.9333, 90 => 1.0],
        ];
        $baseMax = [2 => 30, 3 => 40, 4 => 50][$member->rarity_id] ?? 50;
        $finalMax = [2 => 70, 3 => 80, 4 => 90][$member->rarity_id] ?? 90;
    @endphp
    <main class="mx-auto max-w-[1400px] px-4 py-6 lg:px-8"
        x-data="memberStats(@js([
            'max' => ['performance' => $member->max_performance, 'technique' => $member->max_technique, 'visual' => $member->max_visual],
            'rates' => $growthRates[$member->rarity_id] ?? $growthRates[4],
            'baseMax' => $baseMax,
            'finalMax' => $finalMax,
            'leader' => $member->leader_skill_levels ?? [],
            'live' => $member->live_skill_levels ?? [],
            'gekisou' => $member->gekisou_skill_levels ?? [],
        ]))">
        <a href="{{ route('members.index') }}" class="text-[11px] font-bold text-[#8b7cf6]">‹ 멤버 데이터베이스</a>
        <div class="mt-4 grid gap-6 lg:grid-cols-[400px_minmax(0,1fr)]">
            <aside class="overflow-hidden rounded-xl border bg-white">
                <div class="relative aspect-[3/4]">@if($member->image_url)<img src="{{ $member->image_url }}" class="h-full w-full object-cover" alt="{{ $member->name }}">@endif<div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/95 to-transparent p-6 pt-28 text-white"><div class="flex items-center gap-2"><x-rarity-icon :rarity="$member->rarity" /><x-type-icon :type="$member->type" /></div><h1 class="mt-3 text-2xl font-black">{{ $member->name }}</h1><div class="mt-2 flex items-center justify-between gap-3"><span class="text-sm text-white/80">{{ $member->character->name }}</span><x-band-logo :band="$member->character->band" size="md" :show-name="false" /></div></div></div>
            </aside>
            <section class="space-y-5">
                <div class="overflow-hidden rounded-xl border bg-white">
                    <div class="border-b p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4"><div><p class="text-[10px] font-bold text-[#8b7cf6]">SPICA STATUS</p><h2 class="mt-1 text-lg font-black">능력치 <span class="ml-2 text-sm text-slate-400">Lv.<span x-text="level"></span></span></h2></div><b class="text-3xl tabular-nums" x-text="number(total)"></b></div>
                        <div class="mt-5 grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end"><label class="text-xs text-slate-400">레벨 <b class="float-right text-[#8b7cf6]" x-text="level"></b><input type="range" min="1" :max="maxLevel" x-model.number="level" class="mt-2 w-full accent-[#8b7cf6]"></label><div class="flex gap-1">@foreach([1,$baseMax,$finalMax] as $lv)<button @click="level=Math.min({{$lv}},maxLevel)" class="rounded border px-3 py-2 text-xs font-bold" :class="level==={{$lv}}?'bg-[#6c5ce7] text-white':''">{{$lv}}</button>@endforeach</div></div>
                        <div class="mt-4 flex items-center gap-3"><span class="text-xs text-slate-400">각성</span>@foreach(range(0,4) as $rank)<button @click="rank={{$rank}};level=Math.min(level,maxLevel)" class="grid size-9 place-items-center rounded border text-xs font-bold" :class="rank==={{$rank}}?'bg-pink-300 text-slate-950':''">{{$rank}}</button>@endforeach</div>
                    </div>
                    <div class="grid sm:grid-cols-4 sm:divide-x">
                        <div class="p-5 text-center"><span class="text-xs text-slate-400">합계</span><b class="mt-2 block text-2xl tabular-nums" x-text="number(total)"></b></div>
                        @foreach([['퍼포먼스','performance'],['테크닉','technique'],['비주얼','visual']] as [$label,$key])<div class="p-5 text-center"><span class="text-xs text-slate-400">{{$label}}</span><b class="mt-2 block text-xl tabular-nums text-[#b8adff]" x-text="number(stat('{{$key}}'))"></b></div>@endforeach
                    </div>
                    <p class="border-t px-6 py-3 text-[10px] text-slate-400">SPICA 성장률과 각성 보정치(각 단계 +2.5%)를 적용한 능력치입니다.</p>
                </div>
                <div class="rounded-xl border bg-white p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4"><div><p class="text-[10px] font-bold text-[#8b7cf6]">SKILL LEVEL</p><h2 class="mt-1 text-lg font-black">스킬 정보</h2></div><div class="flex gap-1">@foreach(range(1,5) as $skillLevel)<button @click="skillLevel={{$skillLevel}}" class="grid size-9 place-items-center rounded border text-xs font-bold" :class="skillLevel==={{$skillLevel}}?'bg-pink-300 text-slate-950':''">{{$skillLevel}}</button>@endforeach</div></div>
                    <div class="mt-5 grid gap-4 xl:grid-cols-3">
                        <article class="rounded-lg border p-4"><p class="text-[10px] font-bold text-slate-400">LEADER SKILL</p><div class="spica-skill mt-3 text-sm leading-6" x-html="skill('leader')"></div></article>
                        <article class="rounded-lg border p-4"><p class="text-[10px] font-bold text-slate-400">LIVE SKILL</p><div class="spica-skill mt-3 text-sm leading-6" x-html="skill('live')"></div></article>
                        <article class="rounded-lg border p-4"><p class="text-[10px] font-bold text-slate-400">GEKISOU SKILL</p><div class="spica-skill mt-3 text-sm leading-6" x-html="skill('gekisou')"></div></article>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <script>
        function memberStats(data){return{level:data.baseMax,rank:0,skillLevel:1,data,get maxLevel(){return data.baseMax+this.rank*10},rate(){let points=Object.entries(data.rates).map(([l,r])=>[+l,+r]).sort((a,b)=>a[0]-b[0]);let exact=points.find(p=>p[0]===this.level);if(exact)return exact[1];let lower=points[0],upper=points.at(-1);for(let i=1;i<points.length;i++){if(this.level<points[i][0]){lower=points[i-1];upper=points[i];break}}return lower[1]+(upper[1]-lower[1])*(this.level-lower[0])/(upper[0]-lower[0])},stat(k){return Math.round(data.max[k]*this.rate()*(1+this.rank*.025))},get total(){return this.stat('performance')+this.stat('technique')+this.stat('visual')},number(n){return new Intl.NumberFormat('ko-KR').format(n)},skill(k){return data[k]?.[this.skillLevel]||data[k]?.[String(this.skillLevel)]||'해당 스킬 없음'}}}
    </script>
</x-layouts.wiki>
