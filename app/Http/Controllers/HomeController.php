<?php

namespace App\Http\Controllers;

use App\Models\Character;
use App\Models\Member;
use App\Models\Song;

class HomeController extends Controller
{
    public function __invoke()
    {
        return view('home', [
            'featured' => Member::with('character')->orderByDesc('score_up')->orderByDesc('performance')->limit(10)->get(),
            'members' => Member::count(),
            'characters' => Character::count(),
            'songs' => Song::count(),
            'hardest' => Song::orderByDesc('expert')->first(),
            'songList' => Song::orderByDesc('expert')->orderBy('title')->limit(8)->get(),
            'bands' => Character::query()->selectRaw('band, count(*) as character_count')->groupBy('band')->orderBy('band')->get(),
        ]);
    }
}
