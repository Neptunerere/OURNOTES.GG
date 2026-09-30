<?php

namespace Tests\Feature;

use Tests\TestCase;

class EventTest extends TestCase
{
    public function test_event_page_displays_current_events(): void
    {
        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee('이벤트')
            ->assertSee('사랑의 격류 AtoZ')
            ->assertSee('2026. 10. 08. 20:59')
            ->assertSee(route('events.show', 1));
    }

    public function test_event_detail_displays_bonus_and_rewards(): void
    {
        $this->get(route('events.show', 1))
            ->assertOk()
            ->assertSee('href="'.route('events.index').'" class="flex h-11 shrink-0 items-center border-b-2 px-4 text-xs font-bold border-[#8b7cf6] text-white"', false)
            ->assertSee('사랑의 격류 AtoZ')
            ->assertSee('이벤트 배지(감청)')
            ->assertSee('夢我夢中')
            ->assertSee('무겐다이 뮤타입')
            ->assertSee('이벤트 보너스')
            ->assertSee('센고쿠 유노')
            ->assertSee('미야나가 노노카')
            ->assertSee('미네츠키 리츠')
            ->assertSee('하트 리부트')
            ->assertSee('큐트 플로트')
            ->assertSee('푸르게 타오르는 마음')
            ->assertSee('나카마치 아라레')
            ->assertSee('매지컬 피지컬 파이팅!')
            ->assertSee('사랑의 격류 AtoZ 보상 스탬프')
            ->assertSee('/images/events/1/reward-star.webp')
            ->assertSee('보상 합계')
            ->assertSee('3,000,000 pt 달성 기준')
            ->assertSee('×280')
            ->assertSee('×400,000')
            ->assertSee('이벤트 포인트 보상')
            ->assertSee('라이브 보상');

        $this->get(route('events.show', 999))->assertNotFound();
    }
}
