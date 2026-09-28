<!DOCTYPE html>
<html lang="ko" class="dark">
<head>@include('partials.head')</head>
<body class="wiki-shell flex min-h-screen flex-col bg-[#0c0d0f] text-[#eceef2] antialiased">
<header class="sticky top-0 z-50 border-b border-[#282b32] bg-[#17191f] text-white">
    <div class="mx-auto flex h-14 max-w-[1600px] items-center gap-6 px-4 lg:px-8">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="OURNOTES.GG 홈"><img src="/images/ui/ournotes-logo.webp" alt="BanG Dream! Our Notes" class="h-11 w-auto object-contain"><b class="hidden text-[13px] tracking-tight sm:block">OURNOTES.GG</b></a>
        <form action="{{ route('members.index') }}" class="wiki-top-search hidden max-w-md flex-1 md:block"><label class="flex h-10 items-center gap-2 rounded-sm border border-[#30343d] bg-[#20232a] px-3 text-slate-400"><span class="text-xs">⌕</span><input name="q" class="w-full border-0 bg-transparent p-0 text-xs text-white outline-none ring-0 placeholder:text-slate-500 focus:ring-0" placeholder="멤버, 캐릭터 검색"></label></form>
    </div>
    <div class="border-t border-white/5 bg-[#20232a]"><nav class="mx-auto flex h-11 max-w-[1600px] items-center gap-1 overflow-x-auto px-4 lg:px-8">@foreach ([['홈', 'home'], ['멤버', 'members.index'], ['스냅', 'snapshots.index'], ['곡', 'songs.index'], ['편성 분석', 'formation']] as [$label, $route])<a href="{{ route($route) }}" class="flex h-11 shrink-0 items-center border-b-2 px-4 text-xs font-bold {{ request()->routeIs($route.'*') ? 'border-[#8b7cf6] text-white' : 'border-transparent text-slate-400 hover:text-white' }}">{{ $label }}</a>@endforeach</nav></div>
</header>
<div class="flex-1">{{ $slot }}</div>
<footer class="mt-auto border-t border-slate-200 bg-white py-8 text-xs leading-6 text-slate-500"><div class="mx-auto max-w-[1600px] px-4 lg:px-8"><b class="text-slate-700">OURNOTES.GG</b><p class="mt-2">OURNOTES.GG는 비공식 한국어 팬 사이트입니다. ©BanG Dream! Project ©FROMTOKYO ©Bushiroad　이미지와 데이터의 권리는 각 권리자에게 있습니다.</p></div></footer>
@fluxScripts
</body></html>
