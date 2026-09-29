<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    public function index(Request $request): View
    {
        $query = mb_strtolower(trim($request->string('q')->toString()));
        $guides = collect($this->guides())
            ->when($query !== '', fn ($items) => $items->filter(
                fn (array $guide): bool => str_contains(mb_strtolower($guide['title'].' '.$guide['summary'].' '.$guide['category']), $query)
            ));

        return view('guides.index', [
            'guides' => $guides,
            'query' => $request->string('q')->toString(),
        ]);
    }

    public function show(string $slug): View
    {
        $guide = collect($this->guides())->firstWhere('slug', $slug);

        abort_if($guide === null, 404);

        return view('guides.show', ['guide' => $guide]);
    }

    /**
     * @return list<array{
     *     slug: string,
     *     title: string,
     *     summary: string,
     *     category: string,
     *     author: string,
     *     published_at: string,
     *     updated_at: string,
     *     reading_time: string,
     *     image: string,
     *     image_alt: string,
     *     codes: list<array{code: string, rewards: string, expires_at: string}>
     * }>
     */
    private function guides(): array
    {
        return [
            [
                'slug' => 'redeem-code',
                'title' => '리딤코드 등록 방법과 사용 가능한 코드',
                'summary' => '게임 안에서 리딤코드를 입력하는 위치와 보상 수령 방법을 순서대로 정리했습니다.',
                'category' => '시작하기',
                'author' => 'OURNOTES.GG',
                'published_at' => '2026.09.29',
                'updated_at' => '2026.09.29',
                'reading_time' => '약 2분',
                'image' => '/images/snapshots/tomori-pre-registration-kv.webp',
                'image_alt' => 'BanG Dream! Our Notes 캐릭터 키 비주얼',
                'codes' => [
                    ['code' => '924RELEASE', 'rewards' => '멤버 EXP ×50,000 · 스냅 EXP ×50,000', 'expires_at' => '2026.10.24 23:59'],
                    ['code' => 'Abracadabra', 'rewards' => 'Ave Mujica 프리즘 ×10 · 코인 ×20,000', 'expires_at' => '2026.10.24 23:59'],
                    ['code' => 'READYON924', 'rewards' => '부스트 드링크(소) ×3', 'expires_at' => '2026.10.24 23:59'],
                    ['code' => 'OURNOTES', 'rewards' => '스타 ×200', 'expires_at' => '2026.10.24 23:59'],
                ],
            ],
        ];
    }
}
