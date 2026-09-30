<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\GachaController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\SnapshotController;
use App\Http\Controllers\SongController;
use App\Models\Member;
use App\Models\Snapshot;
use App\Models\Song;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/members', [MemberController::class, 'index'])->name('members.index');
Route::get('/members/{member:slug}', [MemberController::class, 'show'])->name('members.show');
Route::get('/songs', [SongController::class, 'index'])->name('songs.index');
Route::get('/songs/{song:slug}', [SongController::class, 'show'])->name('songs.show');
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])->whereNumber('event')->name('events.show');
Route::get('/gacha', [GachaController::class, 'index'])->name('gacha.index');
Route::get('/snapshots', [SnapshotController::class, 'index'])->name('snapshots.index');
Route::get('/snapshots/{snapshot:slug}', [SnapshotController::class, 'show'])->name('snapshots.show');
Route::get('/formation', fn () => view('formation', [
    'members' => Member::with('character')->orderBy('name')->get(),
    'snapshots' => Snapshot::orderBy('name')->get(),
    'songs' => Song::whereNotNull('note_count')->orderByDesc('expert')->orderBy('title')->get(),
]))->name('formation');
Route::get('/guides', [GuideController::class, 'index'])->name('guides');
Route::get('/guides/{slug}', [GuideController::class, 'show'])->name('guides.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
