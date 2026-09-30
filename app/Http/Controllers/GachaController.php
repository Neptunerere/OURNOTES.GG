<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GachaController extends Controller
{
    public function index(): View
    {
        $pickups = Member::query()
            ->whereIn('source_id', range(51, 60))
            ->get()
            ->keyBy('source_id');

        return view('gacha.index', [
            'limitedGachas' => $this->limitedGachas($pickups),
            'regularGachas' => $this->regularGachas(),
        ]);
    }

    /**
     * @param  Collection<int, Member>  $pickups
     * @return list<array{id: int, title: string, description: string, starts_at: string, ends_at: string, timezone: string, theme: string, members: list<Member>}>
     */
    private function limitedGachas(Collection $pickups): array
    {
        return [
            [
                'id' => 1,
                'title' => '오픈 기념! MyGO!!!!! 픽업 뽑기',
                'description' => 'MyGO!!!!! SSR 멤버 5명의 등장 확률이 올라가는 기간 한정 뽑기',
                'starts_at' => '2026.09.01 00:00',
                'ends_at' => '2026.10.08 23:59',
                'timezone' => 'UTC+8',
                'theme' => 'from-sky-500/30 via-indigo-500/20 to-[#17191f]',
                'image' => '/images/members/tomori-a-cry-cast-at-the-shooting-stars.webp',
                'image_alt' => 'MyGO!!!!! 픽업 멤버',
                'members' => $this->members($pickups, range(51, 55)),
            ],
            [
                'id' => 2,
                'title' => '오픈 기념! Ave Mujica 픽업 뽑기',
                'description' => 'Ave Mujica SSR 멤버 5명의 등장 확률이 올라가는 기간 한정 뽑기',
                'starts_at' => '2026.09.01 00:00',
                'ends_at' => '2026.10.08 23:59',
                'timezone' => 'UTC+8',
                'theme' => 'from-violet-600/35 via-fuchsia-500/15 to-[#17191f]',
                'image' => '/images/members/oblivionis-the-goddess-who-reigns-over-paradise.webp',
                'image_alt' => 'Ave Mujica 픽업 멤버',
                'members' => $this->members($pickups, range(56, 60)),
            ],
        ];
    }

    /**
     * @return list<array{id: int, title: string, description: string, badge: string}>
     */
    private function regularGachas(): array
    {
        return [
            ['id' => 3, 'title' => '오픈 기념! SSR 확정 뽑기', 'description' => 'SSR 멤버 또는 스냅을 획득할 수 있는 뽑기', 'badge' => 'SSR 확정', 'image' => '/images/members/taki-the-beat-to-keep.webp'],
            ['id' => 4, 'title' => '오픈 기념! SSR 멤버 확정 뽑기', 'description' => 'SSR 멤버를 확정으로 획득할 수 있는 뽑기', 'badge' => '멤버 확정', 'image' => '/images/members/doloris-fleeting-echo.webp'],
            ['id' => 5, 'title' => '오픈 기념! SSR 스냅 확정 뽑기', 'description' => 'SSR 스냅을 확정으로 획득할 수 있는 뽑기', 'badge' => '스냅 확정', 'image' => '/images/snapshots/tomori-hand-in-hand.webp'],
            ['id' => 6, 'title' => '오픈 기념! SR 이상 확정 뽑기', 'description' => 'SR 이상의 멤버 또는 스냅을 획득할 수 있는 뽑기', 'badge' => 'SR 이상', 'image' => '/images/members/anon-sparkling-stage.webp'],
            ['id' => 7, 'title' => '아워 노트 뽑기', 'description' => 'R 이상 멤버 또는 스냅을 획득할 수 있는 기본 뽑기', 'badge' => '상시', 'image' => '/images/members/mortis-answering-melody.webp'],
            ['id' => 8, 'title' => '아워 노트 패스 뽑기', 'description' => '아워 노트 패스로 이용할 수 있는 전용 뽑기', 'badge' => '패스', 'image' => '/images/snapshots/oblivionis-noble-grace.webp'],
            ['id' => 9, 'title' => '광고 시청 뽑기', 'description' => '광고 시청으로 이용할 수 있는 뽑기', 'badge' => '광고', 'image' => '/images/members/amoris-dazzling-rhythm.webp'],
        ];
    }

    /**
     * @param  Collection<int, Member>  $pickups
     * @param  list<int>  $ids
     * @return list<Member>
     */
    private function members(Collection $pickups, array $ids): array
    {
        return collect($ids)
            ->map(fn (int $id): ?Member => $pickups->get($id))
            ->filter()
            ->values()
            ->all();
    }
}
