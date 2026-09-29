<x-layouts.wiki :title="'공략 글 목록'">
    <main class="mx-auto max-w-[1500px] px-4 py-7 lg:px-8 lg:py-10">
        @if($query !== '')
            <div class="flex items-center justify-between rounded-lg border border-[#343742] bg-[#17191f] px-4 py-3 text-xs text-slate-400">
                <span>‘{{ $query }}’ 검색 결과</span>
                <a href="{{ route('guides') }}" class="font-bold text-[#9d90ff]">검색 해제</a>
            </div>
        @endif

        <div class="space-y-9 {{ $query !== '' ? 'mt-7' : '' }}">
            @forelse($guides->groupBy('category') as $category => $categoryGuides)
                <section>
                    <h2 class="mb-3 text-sm font-black text-slate-100">{{ $category }}</h2>
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach($categoryGuides as $guide)
                            <a href="{{ route('guides.show', $guide['slug']) }}" class="group grid min-h-28 grid-cols-[112px_minmax(0,1fr)] gap-4 overflow-hidden rounded-lg border border-[#343742] bg-[#17191f] p-3 transition duration-200 hover:-translate-y-0.5 hover:border-[#7568c9] hover:bg-[#1c1e25] hover:shadow-xl hover:shadow-black/20">
                                <div class="overflow-hidden rounded-md bg-white">
                                    <img src="{{ $guide['image'] }}" alt="{{ $guide['image_alt'] }}" class="h-full min-h-24 w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                </div>
                                <div class="min-w-0 self-center py-1">
                                    <h3 class="text-sm font-black leading-5 text-slate-100 transition group-hover:text-[#b8adff]">{{ $guide['title'] }}</h3>
                                    <p class="mt-2 line-clamp-2 text-xs leading-5 text-slate-400">{{ $guide['summary'] }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @empty
                <section class="rounded-lg border border-[#343742] bg-[#17191f] px-6 py-16 text-center">
                    <p class="text-sm font-bold text-slate-300">검색 결과가 없습니다.</p>
                    <a href="{{ route('guides') }}" class="mt-3 inline-block text-xs font-bold text-[#9d90ff]">전체 공략 보기</a>
                </section>
            @endforelse
        </div>
    </main>
</x-layouts.wiki>
