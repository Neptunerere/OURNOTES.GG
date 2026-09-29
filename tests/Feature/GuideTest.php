<?php

namespace Tests\Feature;

use Tests\TestCase;

class GuideTest extends TestCase
{
    public function test_guide_board_displays_the_redeem_code_article(): void
    {
        $this->get(route('guides'))
            ->assertOk()
            ->assertSee('시작하기')
            ->assertSee('리딤코드 등록 방법과 사용 가능한 코드');
    }

    public function test_guide_board_can_be_searched(): void
    {
        $this->get(route('guides', ['q' => '리딤코드']))
            ->assertOk()
            ->assertSee('리딤코드 등록 방법과 사용 가능한 코드');

        $this->get(route('guides', ['q' => '없는 공략']))
            ->assertOk()
            ->assertSee('검색 결과가 없습니다.');
    }

    public function test_redeem_code_guide_displays_codes_and_registration_steps(): void
    {
        $this->get(route('guides.show', 'redeem-code'))
            ->assertOk()
            ->assertSee('OURNOTES')
            ->assertSee('Serial Code')
            ->assertSee('리딤코드 등록 순서');
    }

    public function test_unknown_guide_returns_not_found(): void
    {
        $this->get(route('guides.show', 'unknown'))->assertNotFound();
    }
}
