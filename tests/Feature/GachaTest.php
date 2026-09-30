<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GachaTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_gacha_page_displays_limited_and_regular_gachas(): void
    {
        $this->get(route('gacha.index'))
            ->assertOk()
            ->assertSee('현재 진행 중인 뽑기')
            ->assertSee('오픈 기념! MyGO!!!!! 픽업 뽑기')
            ->assertSee('오픈 기념! Ave Mujica 픽업 뽑기')
            ->assertSee('아워 노트 뽑기')
            ->assertSee('2026.10.08 23:59');
    }
}
