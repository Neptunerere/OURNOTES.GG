<x-layouts.wiki :title="'스냅 데이터베이스'">
    <main class="mx-auto grid max-w-[1600px] gap-5 px-4 py-5 lg:grid-cols-[240px_minmax(0,1fr)] lg:px-8">
        <aside>
            <form class="overflow-hidden rounded-lg border border-slate-200 bg-white lg:sticky lg:top-28">
                <div class="border-b p-3"><input name="q" value="{{request('q')}}" placeholder="스냅 또는 캐릭터 검색" class="w-full rounded-sm border-slate-200 bg-slate-50 px-3 py-2 text-xs"></div>
                <div class="border-b p-3"><label class="text-[10px] font-bold text-slate-400">밴드</label><select name="band" class="mt-2 w-full border-slate-200 bg-slate-50 text-xs"><option value="">모든 밴드</option>@foreach($bands as $band)<option @selected(request('band')===$band)>{{$band}}</option>@endforeach</select></div>
                <div class="border-b p-3"><label class="text-[10px] font-bold text-slate-400">카드 타입</label><div class="mt-2 space-y-1">@foreach($types as $type)<label class="flex items-center gap-2 rounded px-2 py-1.5 text-xs hover:bg-slate-50"><input type="radio" name="type" value="{{$type}}" @checked(request('type')===$type) class="text-[#6c5ce7] focus:ring-[#6c5ce7]"><x-type-icon :type="$type" size="sm" />{{$type}}</label>@endforeach</div></div>
                <div class="border-b p-3"><label class="text-[10px] font-bold text-slate-400">희귀도</label><div class="mt-2 grid grid-cols-3 gap-1">@foreach($rarities as $rarity)<label class="flex cursor-pointer items-center justify-center rounded-sm border border-slate-200 p-2 transition hover:border-[#8b7cf6] has-[:checked]:border-[#8b7cf6] has-[:checked]:bg-[#6c5ce7]/10"><input type="radio" name="rarity" value="{{$rarity}}" @checked(request('rarity')===$rarity) class="sr-only"><x-rarity-icon :rarity="$rarity" size="sm" /></label>@endforeach</div></div>
                <div class="p-3"><button class="w-full rounded-sm bg-[#6c5ce7] py-2.5 text-xs font-bold text-white">필터 적용</button><a href="{{route('snapshots.index')}}" class="mt-2 block text-center text-[10px] text-slate-400 hover:text-white">초기화</a></div>
            </form>
        </aside>
        <section class="min-w-0">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse($snapshots as $snapshot)
                <a href="{{route('snapshots.show',$snapshot)}}" class="group overflow-hidden rounded-lg border bg-white hover:border-[#aaa0ee] hover:shadow-lg">
                    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">@if($snapshot->image_url)<img src="{{$snapshot->image_url}}" class="h-full w-full object-cover transition group-hover:scale-[1.02]" loading="lazy" alt="{{$snapshot->name}}">@endif<div class="absolute left-3 top-3 flex items-center gap-2 rounded-md bg-black/55 px-2 py-1.5 backdrop-blur"><x-rarity-icon :rarity="$snapshot->rarity" size="sm" /><x-type-icon :type="$snapshot->type" size="sm" /></div></div>
                    <div class="p-3"><x-band-logo :band="$snapshot->band" size="sm" /><h2 class="mt-2 truncate text-sm font-black">{{$snapshot->name}}</h2><p class="mt-1 truncate text-[10px] text-slate-400">{{$snapshot->character_name}}</p><div class="mt-3 grid grid-cols-3 border-t pt-2 text-center text-[9px]"><span>Pfm <b>{{number_format($snapshot->performance)}}</b></span><span>Tec <b>{{number_format($snapshot->technique)}}</b></span><span>Vis <b>{{number_format($snapshot->visual)}}</b></span></div></div>
                </a>
            @empty<div class="col-span-full rounded-lg border bg-white p-14 text-center text-sm text-slate-400">조건에 맞는 스냅이 없습니다.</div>@endforelse
        </div>
        <div class="mt-5">{{$snapshots->links()}}</div>
        </section>
    </main>
</x-layouts.wiki>
