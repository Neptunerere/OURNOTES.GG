<?php

namespace Tests\Feature;

use App\Models\Song;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SongDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_song_detail_displays_available_information(): void
    {
        $song = Song::query()->create([
            'title' => '테스트 곡',
            'slug' => 'test-song',
            'band' => 'MyGO!!!!!',
            'type' => '오리지널',
            'attribute' => '그린',
            'easy' => 9,
            'normal' => 13,
            'hard' => 20,
            'expert' => 25,
            'composer' => '테스트 작곡가',
            'lyricist' => '테스트 작사가',
            'arranger' => '테스트 편곡가',
            'bpm' => '180',
            'note_count' => 768,
        ]);

        $this->get(route('songs.show', $song))
            ->assertOk()
            ->assertSee('테스트 곡')
            ->assertSee('테스트 작곡가')
            ->assertSee('난이도 정보')
            ->assertSee('768');
    }

    public function test_unknown_song_returns_not_found(): void
    {
        $this->get('/songs/unknown-song')->assertNotFound();
    }
}
