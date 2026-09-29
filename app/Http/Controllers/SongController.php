<?php

namespace App\Http\Controllers;

use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SongController extends Controller
{
    public function index(Request $request): View
    {
        $difficulty = in_array($request->difficulty, ['easy', 'normal', 'hard', 'expert'], true)
            ? $request->difficulty
            : null;
        $level = $request->filled('level') ? (int) $request->level : null;

        $songs = Song::query()
            ->when($request->filled('q'), fn ($query) => $query->where('title', 'like', '%'.$request->q.'%'))
            ->when($request->filled('band'), fn ($query) => $query->where('band', $request->band))
            ->when($difficulty && $level, fn ($query) => $query->where($difficulty, $level))
            ->when(! $difficulty && $level, fn ($query) => $query->where(fn ($levels) => $levels
                ->where('easy', $level)
                ->orWhere('normal', $level)
                ->orWhere('hard', $level)
                ->orWhere('expert', $level)))
            ->orderByDesc('expert')->orderBy('title')->paginate(28)->withQueryString();

        $levels = Song::query()->get(['easy', 'normal', 'hard', 'expert'])
            ->flatMap(fn (Song $song) => [$song->easy, $song->normal, $song->hard, $song->expert])
            ->filter()->unique()->sort()->values();

        return view('songs.index', [
            'songs' => $songs,
            'bands' => Song::distinct()->orderBy('band')->pluck('band'),
            'levels' => $levels,
        ]);
    }
}
