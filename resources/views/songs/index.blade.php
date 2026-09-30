<x-layouts.wiki :title="'곡 데이터베이스'">
    <section class="mx-auto grid max-w-[1600px] gap-5 px-4 py-5 lg:grid-cols-[240px_minmax(0,1fr)] lg:px-8">
        <aside>
            <form class="overflow-hidden rounded-lg border border-slate-200 bg-white lg:sticky lg:top-28">
                <div class="border-b p-3"><input name="q" value="{{ request('q') }}" placeholder="곡명 검색" class="w-full rounded-sm border-slate-200 bg-slate-50 px-3 py-2 text-xs"></div>
                <div class="border-b p-3"><label class="text-[10px] font-bold text-slate-400">밴드</label><select name="band" class="mt-2 w-full border-slate-200 bg-slate-50 text-xs"><option value="">모든 밴드</option>@foreach($bands as $band)<option @selected(request('band')===$band)>{{ $band }}</option>@endforeach</select></div>
                <div class="border-b p-3"><label class="text-[10px] font-bold text-slate-400">난이도</label><select name="difficulty" class="mt-2 w-full border-slate-200 bg-slate-50 text-xs"><option value="">모든 난이도</option>@foreach(['easy'=>'EASY','normal'=>'NORMAL','hard'=>'HARD','expert'=>'EXPERT'] as $value=>$label)<option value="{{$value}}" @selected(request('difficulty')===$value)>{{$label}}</option>@endforeach</select></div>
                <div class="border-b p-3"><label class="text-[10px] font-bold text-slate-400">레벨</label><select name="level" class="mt-2 w-full border-slate-200 bg-slate-50 text-xs"><option value="">모든 레벨</option>@foreach($levels as $level)<option value="{{$level}}" @selected((string)request('level')===(string)$level)>Lv. {{$level}}</option>@endforeach</select><p class="mt-2 text-[10px] leading-4 text-slate-400">난이도를 선택하지 않으면 모든 난이도에서 해당 레벨을 찾습니다.</p></div>
                <div class="p-3"><button class="w-full rounded-sm bg-[#6c5ce7] py-2.5 text-xs font-bold text-white">필터 적용</button><a href="{{ route('songs.index') }}" class="mt-2 block text-center text-[10px] text-slate-400 hover:text-white">초기화</a></div>
            </form>
        </aside>
        <div class="min-w-0">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse($songs as $song)
                <a href="{{ route('songs.show', $song) }}" class="group block overflow-hidden rounded-lg border border-slate-200 bg-white transition hover:-translate-y-0.5 hover:border-[#8b7cf6] hover:shadow-xl">
                    <div class="relative aspect-square overflow-hidden bg-slate-100">
                        @if($song->image_url)<img src="{{$song->image_url}}" alt="{{$song->title}} 재킷" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]" loading="lazy">@endif
                        <div class="absolute left-3 top-3 flex items-center gap-2 rounded bg-black/65 px-2 py-1.5 backdrop-blur"><x-type-icon :type="$song->attribute" size="sm" /><span class="text-[10px] font-bold text-white">{{$song->type}}</span></div>
                        <span class="absolute bottom-3 right-3 grid size-11 place-items-center rounded bg-[#6c5ce7] text-lg font-black text-white shadow-lg">{{$song->expert}}</span>
                    </div>
                    <div class="p-4">
                        <x-band-logo :band="$song->band" size="sm" />
                        <h2 class="mt-3 truncate text-base font-black" title="{{$song->title}}">{{$song->title}}</h2>
                        <div class="mt-4 grid grid-cols-4 gap-1 border-t pt-3 text-center">
                            @foreach([['EZ',$song->easy],['NM',$song->normal],['HD',$song->hard],['EX',$song->expert]] as [$label,$level])<div><span class="block text-[9px] font-bold text-slate-400">{{$label}}</span><b class="mt-1 block text-sm {{$label==='EX'?'text-[#9d90ff]':''}}">{{$level ?? '-'}}</b></div>@endforeach
                        </div>
                        @if($song->bpm || $song->composer)<div class="mt-3 space-y-1 border-t pt-3 text-[10px] text-slate-400">@if($song->bpm)<p><span class="font-bold text-slate-500">BPM</span> {{$song->bpm}}</p>@endif @if($song->composer)<p class="truncate" title="{{$song->composer}}"><span class="font-bold text-slate-500">작곡</span> {{$song->composer}}</p>@endif</div>@endif
                    </div>
                </a>
            @empty
                <div class="col-span-full rounded-lg border bg-white p-14 text-center text-sm text-slate-400">조건에 맞는 곡이 없습니다.</div>
            @endforelse
        </div>
        <div class="mt-6">{{ $songs->links() }}</div>
        </div>
    </section>
</x-layouts.wiki>
