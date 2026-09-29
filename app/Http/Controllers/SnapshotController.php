<?php

namespace App\Http\Controllers;

use App\Models\Snapshot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SnapshotController extends Controller
{
    public function index(Request $request): View
    {
        $snapshots = Snapshot::query()
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->q.'%')
                ->orWhere('character_name', 'like', '%'.$request->q.'%')))
            ->when($request->filled('band'), fn ($query) => $query->where('band', $request->band))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->type))
            ->when($request->filled('rarity'), fn ($query) => $query->where('rarity', $request->rarity))
            ->orderByDesc('rarity_id')
            ->orderByDesc('released_at')
            ->paginate(24)
            ->withQueryString();

        return view('snapshots.index', [
            'snapshots' => $snapshots,
            'bands' => Snapshot::distinct()->orderBy('band')->pluck('band'),
            'types' => Snapshot::distinct()->orderBy('type')->pluck('type'),
            'rarities' => Snapshot::query()
                ->select('rarity')
                ->selectRaw('MAX(rarity_id) as max_rarity_id')
                ->groupBy('rarity')
                ->orderByDesc('max_rarity_id')
                ->pluck('rarity'),
        ]);
    }

    public function show(Snapshot $snapshot): View
    {
        return view('snapshots.show', compact('snapshot'));
    }
}
