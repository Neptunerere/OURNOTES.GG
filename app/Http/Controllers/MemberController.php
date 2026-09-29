<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $members = Member::with('character')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$request->q.'%')->orWhereHas('character', fn ($c) => $c->where('name', 'like', '%'.$request->q.'%'))))
            ->when($request->filled('band'), fn ($query) => $query->whereHas('character', fn ($q) => $q->where('band', $request->band)))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
            ->when($request->filled('rarity'), fn ($query) => $query->where('rarity', $request->rarity))
            ->orderByDesc('score_up')->paginate(24)->withQueryString();

        return view('members.index', [
            'members' => $members,
            'bands' => Member::join('characters', 'characters.id', '=', 'members.character_id')->distinct()->orderBy('characters.band')->pluck('characters.band'),
            'rarities' => Member::query()
                ->select('rarity')
                ->selectRaw('MAX(rarity_id) as max_rarity_id')
                ->groupBy('rarity')
                ->orderByDesc('max_rarity_id')
                ->pluck('rarity'),
        ]);
    }

    public function show(Member $member): View
    {
        return view('members.show', ['member' => $member->load('character')]);
    }
}
