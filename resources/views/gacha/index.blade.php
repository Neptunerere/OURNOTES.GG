<x-layouts.wiki :title="'진행 중 뽑기'">
    <main class="mx-auto max-w-[1500px] px-4 py-7 lg:px-8 lg:py-10">
        <h1 class="mb-5 text-xl font-black text-white">현재 진행 중인 뽑기</h1>
        <div class="space-y-9">
            <section>
                <h2 class="mb-3 text-sm font-black text-slate-100">기간 한정 픽업 뽑기</h2>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($limitedGachas as $gacha)
                        <article class="group grid min-h-28 grid-cols-[112px_minmax(0,1fr)] gap-4 overflow-hidden rounded-lg border border-[#343742] bg-[#17191f] p-3 transition duration-200 hover:-translate-y-0.5 hover:border-[#7568c9] hover:bg-[#1c1e25] hover:shadow-xl hover:shadow-black/20">
                            <div class="overflow-hidden rounded-md bg-white"><img src="{{ $gacha['image'] }}" alt="{{ $gacha['image_alt'] }}" class="h-full min-h-24 w-full object-cover object-top transition duration-300 group-hover:scale-105" loading="lazy"></div>
                            <div class="min-w-0 self-center py-1">
                                <span class="text-[9px] font-black text-pink-400">PICK UP</span>
                                <h3 class="mt-1 text-sm font-black leading-5 text-slate-100 transition group-hover:text-[#b8adff]">{{ $gacha['title'] }}</h3>
                                <p class="mt-1 line-clamp-1 text-[10px] text-slate-500">{{ $gacha['starts_at'] }} – {{ $gacha['ends_at'] }}</p>
                                <p class="mt-2 line-clamp-2 text-xs leading-5 text-slate-400">{{ $gacha['description'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
            <section>
                <h2 class="mb-3 text-sm font-black text-slate-100">그 외 진행 중인 뽑기</h2>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($regularGachas as $gacha)
                        <article class="group grid min-h-28 grid-cols-[112px_minmax(0,1fr)] gap-4 overflow-hidden rounded-lg border border-[#343742] bg-[#17191f] p-3 transition duration-200 hover:-translate-y-0.5 hover:border-[#7568c9] hover:bg-[#1c1e25] hover:shadow-xl hover:shadow-black/20">
                            <div class="overflow-hidden rounded-md bg-white"><img src="{{ $gacha['image'] }}" alt="{{ $gacha['title'] }}" class="h-full min-h-24 w-full object-cover object-top transition duration-300 group-hover:scale-105" loading="lazy"></div>
                            <div class="min-w-0 self-center py-1">
                                <span class="text-[9px] font-black text-[#9d90ff]">{{ $gacha['badge'] }}</span>
                                <h3 class="mt-1 text-sm font-black leading-5 text-slate-100 transition group-hover:text-[#b8adff]">{{ $gacha['title'] }}</h3>
                                <p class="mt-2 line-clamp-2 text-xs leading-5 text-slate-400">{{ $gacha['description'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </main>
</x-layouts.wiki>
