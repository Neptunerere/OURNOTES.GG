<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Member;
use App\Models\Song;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $lastSyncedAt = DB::table('data_sync_states')->where('source', 'bdon')->value('last_synced_at');

        return view('home', [
            'featured' => Member::with('character')->orderByDesc('score_up')->orderByDesc('performance')->limit(10)->get(),
            'members' => Member::count(),
            'characters' => Character::count(),
            'songs' => Song::count(),
            'dataSyncedAt' => $lastSyncedAt
                ? Carbon::parse($lastSyncedAt)->timezone('Asia/Seoul')->format('Y.m.d')
                : null,
            'hardest' => Song::orderByDesc('expert')->first(),
            'songList' => Song::orderByDesc('expert')->orderBy('title')->limit(8)->get(),
            'bands' => Character::query()->selectRaw('band, count(*) as character_count')->groupBy('band')->orderBy('band')->get(),
        ]);
    }
}
