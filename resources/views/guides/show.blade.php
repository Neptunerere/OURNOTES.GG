<x-layouts.wiki :title="$guide['title']">
    <main class="mx-auto max-w-[1100px] px-4 py-6 lg:px-8 lg:py-10">
        <a href="{{ route('guides') }}" class="inline-flex items-center gap-1 text-xs font-bold text-[#9d90ff] hover:text-[#b8adff]">‹ 공략 게시판으로 돌아가기</a>

        <article class="mt-4 overflow-hidden rounded-xl border border-[#30343d] bg-[#17191f]">
            <header class="border-b border-[#30343d] px-6 py-7 sm:px-10 sm:py-9">
                <span class="rounded-full bg-[#6c5ce7]/15 px-2.5 py-1 text-[10px] font-black text-[#b8adff]">{{ $guide['category'] }}</span>
                <h1 class="mt-4 text-2xl font-black leading-tight tracking-tight text-white sm:text-3xl">{{ $guide['title'] }}</h1>
                <p class="mt-3 text-sm leading-6 text-slate-400">{{ $guide['summary'] }}</p>
                <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 border-t border-[#30343d] pt-4 text-[11px] text-slate-500">
                    <span><b class="mr-1.5 text-slate-300">업데이트</b>{{ $guide['updated_at'] }}</span>
                </div>
            </header>

            <div class="grid lg:grid-cols-[minmax(0,1fr)_230px]">
                <div class="space-y-10 px-6 py-8 sm:px-10 sm:py-10">
                    <figure class="overflow-hidden rounded-lg border border-[#30343d] bg-white">
                        <img src="{{ $guide['image'] }}" alt="{{ $guide['image_alt'] }}" class="aspect-[16/7] w-full object-cover object-center">
                    </figure>

                    <section aria-labelledby="available-codes">
                        <p class="text-[10px] font-black tracking-[0.18em] text-[#9d90ff]">REDEEM CODES</p>
                        <h2 id="available-codes" class="mt-2 text-xl font-black text-white">사용 가능한 리딤코드</h2>
                        <p class="mt-3 text-sm leading-7 text-slate-400">아래 코드는 한 계정당 한 번만 사용할 수 있습니다. 입력 시 대소문자와 유효기간을 확인해 주세요.</p>

                        <div class="mt-5 space-y-3">
                            @foreach($guide['codes'] as $code)
                                <div class="rounded-lg border border-[#353945] bg-[#20232a] p-4" x-data="{ copied: false }">
                                    <div class="flex items-center justify-between gap-4">
                                        <code class="min-w-0 truncate text-sm font-black text-[#c4bbff]">{{ $code['code'] }}</code>
                                        <button type="button" @click="navigator.clipboard.writeText(@js($code['code'])); copied = true; setTimeout(() => copied = false, 1500)" class="shrink-0 rounded-md border border-[#494e5b] px-3 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-[#8b7cf6] hover:text-white" x-text="copied ? '복사됨' : '코드 복사'">코드 복사</button>
                                    </div>
                                    <p class="mt-3 text-xs font-bold text-slate-200">{{ $code['rewards'] }}</p>
                                    <p class="mt-1.5 text-[10px] text-slate-500">입력 기한 {{ $code['expires_at'] }} (KST)</p>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section aria-labelledby="how-to-register">
                        <p class="text-[10px] font-black tracking-[0.18em] text-[#9d90ff]">HOW TO</p>
                        <h2 id="how-to-register" class="mt-2 text-xl font-black text-white">리딤코드 등록 순서</h2>
                        <ol class="mt-5 space-y-4">
                            @foreach([
                                ['홈 화면에서 메뉴 열기', '홈 화면 오른쪽 위의 ≡ 버튼을 눌러 메뉴를 엽니다.'],
                                ['Serial Code 선택', '메뉴 목록에서 Serial Code를 선택합니다.'],
                                ['코드 입력 후 OK', '입력 칸에 코드를 정확히 붙여 넣고 OK 버튼을 누릅니다.'],
                                ['보상 확인', '등록이 완료되면 보상 화면이 표시되며 아이템이 바로 지급됩니다.'],
                            ] as $index => [$title, $description])
                                <li class="flex gap-4 rounded-lg border border-[#30343d] p-4">
                                    <span class="grid size-8 shrink-0 place-items-center rounded-full bg-[#6c5ce7] text-xs font-black text-white">{{ $index + 1 }}</span>
                                    <div><h3 class="text-sm font-black text-slate-100">{{ $title }}</h3><p class="mt-1 text-xs leading-6 text-slate-400">{{ $description }}</p></div>
                                </li>
                            @endforeach
                        </ol>
                    </section>

                    <aside class="rounded-lg border border-amber-300/20 bg-amber-300/[0.06] p-5">
                        <h2 class="text-sm font-black text-amber-200">입력이 되지 않을 때</h2>
                        <ul class="mt-3 list-disc space-y-2 pl-5 text-xs leading-6 text-slate-400">
                            <li>이미 같은 계정에서 사용한 코드인지 확인해 주세요.</li>
                            <li>앞뒤 공백이 함께 복사되지 않았는지 확인해 주세요.</li>
                            <li>영문 대소문자와 숫자 0, 영문 O를 구분해 입력해 주세요.</li>
                            <li>유효기간이 지난 코드는 등록할 수 없습니다.</li>
                        </ul>
                    </aside>
                </div>

                <aside class="border-t border-[#30343d] bg-[#14161b] p-6 lg:border-l lg:border-t-0">
                    <nav class="lg:sticky lg:top-28" aria-label="글 목차">
                        <p class="text-[10px] font-black tracking-[0.16em] text-slate-500">목차</p>
                        <a href="#available-codes" class="mt-3 block border-l-2 border-[#6c5ce7] py-1 pl-3 text-xs font-bold text-slate-200">사용 가능한 리딤코드</a>
                        <a href="#how-to-register" class="mt-2 block border-l-2 border-[#30343d] py-1 pl-3 text-xs text-slate-500 hover:text-slate-200">리딤코드 등록 순서</a>
                    </nav>
                </aside>
            </div>
        </article>
    </main>
</x-layouts.wiki>
