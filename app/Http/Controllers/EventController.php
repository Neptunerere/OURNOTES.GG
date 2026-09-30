<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        return view('events.index', ['events' => [$this->event()]]);
    }

    public function show(int $event): View
    {
        abort_unless($event === 1, 404);

        return view('events.show', ['event' => $this->event()]);
    }

    /** @return array<string, mixed> */
    private function event(): array
    {
        return [
            'id' => 1,
            'title' => '사랑의 격류 AtoZ',
            'status' => '예정',
            'server' => '일본',
            'starts_at' => '2026. 09. 30. 18:00',
            'ends_at' => '2026. 10. 08. 20:59',
            'display_ends_at' => '2026. 10. 10. 20:59',
            'timezone' => 'UTC+9',
            'banner' => '/images/events/1/banner.webp',
            'logo' => '/images/events/1/logo.webp',
            'badge' => '/images/events/1/badge.webp',
            'song' => ['title' => '夢我夢中', 'band' => '무겐다이 뮤타입', 'image' => '/images/events/1/song.webp'],
            'member_bonuses' => [
                ['name' => '센고쿠 유노', 'card' => '하트 리부트', 'stats' => '+30%', 'item' => '+30%', 'image' => '/images/events/1/member-61.webp'],
                ['name' => '미야나가 노노카', 'card' => '큐트 플로트', 'stats' => '+30%', 'item' => '+30%', 'image' => '/images/events/1/member-62.webp'],
                ['name' => '미네츠키 리츠', 'card' => 'ナイト・フライト', 'stats' => '+15%', 'item' => '+15%', 'image' => '/images/events/1/member-63.webp'],
                ['name' => '무겐다이 뮤타입', 'card' => '밴드 보너스', 'stats' => '+20%', 'item' => '+20%', 'image' => '/images/events/1/song.webp'],
                ['name' => '파랑 속성', 'card' => '속성 보너스', 'stats' => '+10%', 'item' => '+10%', 'image' => '/images/ui/attr-2.webp'],
            ],
            'support_bonuses' => [
                ['name' => '센고쿠 유노', 'card' => '푸르게 타오르는 마음', 'stats' => '+30%', 'item' => '+30%', 'image' => '/images/events/1/support-62.webp'],
                ['name' => '나카마치 아라레', 'card' => '매지컬 피지컬 파이팅!', 'stats' => '+30%', 'item' => '+30%', 'image' => '/images/events/1/support-63.webp'],
                ['name' => '미야코 & 리츠', 'card' => 'まあるい休息', 'stats' => '+15%', 'item' => '+15%', 'image' => '/images/events/1/support-64.webp'],
                ['name' => '무겐다이 뮤타입', 'card' => '밴드 보너스', 'stats' => '+20%', 'item' => '+20%', 'image' => '/images/events/1/song.webp'],
                ['name' => '파랑 속성', 'card' => '속성 보너스', 'stats' => '+10%', 'item' => '+10%', 'image' => '/images/ui/attr-2.webp'],
            ],
            'event_cards' => [
                ['name' => '미네츠키 리츠 · ナイト・フライト', 'image' => '/images/events/1/member-63.webp'],
                ['name' => '미야코 & 리츠 · まあるい休息', 'image' => '/images/events/1/support-64.webp'],
            ],
            'point_rewards' => [
                ['500', '스타 ×40'], ['1,000', '코인 ×15,000'], ['2,000', '스냅 EXP ×15,000'], ['3,000', '멤버 EXP ×15,000'],
                ['4,000', '무겐다이 뮤타입 젬 ×5'], ['5,000', '라이브 스킬 강화 티켓 ×5'], ['6,000', '격주 스킬 강화 티켓 ×5'], ['7,000', '스킬 강화 티켓 ×5'],
                ['8,000', '무겐다이 뮤타입 젬 ×5'], ['9,000', '스타 ×40'], ['10,000', '무겐다이 뮤타입 젬 ×5'], ['12,000', '코인 ×15,000'],
                ['14,000', '스냅 EXP ×45,000'], ['15,000', '멤버 EXP ×45,000'], ['16,000', '무겐다이 뮤타입 젬 ×10'], ['18,000', '라이브 스킬 강화 티켓 ×5'],
                ['20,000', '격주 스킬 강화 티켓 ×5'], ['22,000', '스킬 강화 티켓 ×5'], ['24,000', 'SP 스킬 강화 티켓 ×5'], ['27,000', '스타 ×40'],
                ['30,000', '사랑의 격류 AtoZ 보상 스탬프'], ['35,000', '기적의 크리스털 ×5'], ['40,000', '코인 ×45,000'], ['45,000', '무겐다이 뮤타입 젬 ×10'],
                ['50,000', '기적의 크리스털 ×5'], ['55,000', '라이브 스킬 강화 티켓 ×25'], ['60,000', '격주 스킬 강화 티켓 ×25'], ['65,000', '스킬 강화 티켓 ×20'],
                ['70,000', '기적의 크리스털 ×5'], ['75,000', '스타 ×40'], ['80,000', '기적의 크리스털 ×5'], ['85,000', '희망의 젬 ×5'],
                ['90,000', '라이브 스킬 강화 티켓 ×25'], ['95,000', '무겐다이 뮤타입 젬 ×10'], ['100,000', '미네츠키 리츠 · ナイト・フライト'],
                ['125,000', '스타 ×40'], ['150,000', '미네츠키 리츠 · ナイト・フライト'], ['200,000', '미네츠키 리츠 · ナイト・フライト'],
                ['300,000', '미네츠키 리츠 · ナイト・フライト'], ['450,000', '스타 ×40'], ['675,000', '미네츠키 리츠 · ナイト・フライト'], ['900,000', '스타 ×40'],
                ['1,000,000', '이벤트 배지(감청) ×100,000'], ['1,500,000', '이벤트 배지(감청) ×100,000'], ['2,000,000', '이벤트 배지(감청) ×100,000'], ['3,000,000', '이벤트 배지(감청) ×100,000'],
            ],
            'reward_images' => [
                '스타' => '/images/events/1/reward-star.webp',
                '코인' => '/images/events/1/reward-coin.webp',
                '스냅 EXP' => '/images/events/1/reward-snapshot-exp.webp',
                '멤버 EXP' => '/images/events/1/reward-member-exp.webp',
                '무겐다이 뮤타입 젬' => '/images/events/1/reward-band-gem.webp',
                '희망의 젬' => '/images/events/1/reward-hope-gem.webp',
                '라이브 스킬 강화 티켓' => '/images/events/1/reward-live-ticket.webp',
                '격주 스킬 강화 티켓' => '/images/events/1/reward-duo-ticket.webp',
                'SP 스킬 강화 티켓' => '/images/events/1/reward-sp-ticket.webp',
                '스킬 강화 티켓' => '/images/events/1/reward-skill-ticket.webp',
                '보상 스탬프' => '/images/events/1/reward-stamp.webp',
                '행운의 크리스털' => '/images/events/1/reward-luck-crystal.webp',
                '기적의 크리스털' => '/images/events/1/reward-miracle-crystal.webp',
                '미네츠키 리츠' => '/images/events/1/member-63.webp',
                '이벤트 배지' => '/images/events/1/badge.webp',
            ],
            'reward_totals' => [
                ['name' => '스타', 'total' => '×280', 'image' => '/images/events/1/reward-star.webp'],
                ['name' => '코인', 'total' => '×75,000', 'image' => '/images/events/1/reward-coin.webp'],
                ['name' => '스냅 EXP', 'total' => '×60,000', 'image' => '/images/events/1/reward-snapshot-exp.webp'],
                ['name' => '멤버 EXP', 'total' => '×60,000', 'image' => '/images/events/1/reward-member-exp.webp'],
                ['name' => '무겐다이 뮤타입 젬', 'total' => '×45', 'image' => '/images/events/1/reward-band-gem.webp'],
                ['name' => '라이브 스킬 강화 티켓', 'total' => '×60', 'image' => '/images/events/1/reward-live-ticket.webp'],
                ['name' => '격주 스킬 강화 티켓', 'total' => '×35', 'image' => '/images/events/1/reward-duo-ticket.webp'],
                ['name' => '스킬 강화 티켓', 'total' => '×30', 'image' => '/images/events/1/reward-skill-ticket.webp'],
                ['name' => 'SP 스킬 강화 티켓', 'total' => '×5', 'image' => '/images/events/1/reward-sp-ticket.webp'],
                ['name' => '기적의 크리스털', 'total' => '×20', 'image' => '/images/events/1/reward-miracle-crystal.webp'],
                ['name' => '희망의 젬', 'total' => '×5', 'image' => '/images/events/1/reward-hope-gem.webp'],
                ['name' => '보상 스탬프', 'total' => '×1', 'image' => '/images/events/1/reward-stamp.webp'],
                ['name' => '미네츠키 리츠 · ナイト・フライト', 'total' => '×5', 'image' => '/images/events/1/member-63.webp'],
                ['name' => '이벤트 배지(감청)', 'total' => '×400,000', 'image' => '/images/events/1/badge.webp'],
            ],
            'live_rewards' => [
                ['D', '15', '18'], ['C', '25', '30'], ['B', '35', '42'], ['A', '50', '60'], ['S', '75', '90'], ['SS', '100', '120'],
            ],
            'challenge_rewards' => [
                ['D', '1,500', '1,450'], ['C', '2,000', '2,650'], ['B', '2,550', '3,400'], ['A', '3,250', '3,750'], ['S', '3,900', '4,450'], ['SS', '5,000', '4,950'],
            ],
        ];
    }
}
