<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Snapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GachaController extends Controller
{
    public function index(): View
    {
        $pickups = Member::query()
            ->with('character')
            ->get()
            ->keyBy('source_id');

        $gachas = collect($this->limitedGachas($pickups));
        $supportIds = $gachas->flatMap(fn (array $gacha): array => $gacha['pickup_support_ids'] ?? ((int) $gacha['id'] === 3 ? [62, 63] : []))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $supports = $supportIds->isEmpty()
            ? collect()
            : Snapshot::query()->whereIn('source_id', $supportIds)->get()->keyBy('source_id');

        $limitedGachas = $gachas
            ->map(function (array $gacha) use ($supports): array {
                $gacha = $this->withStatus($gacha);
                $supportIds = $gacha['pickup_support_ids'] ?? ((int) $gacha['id'] === 3 ? [62, 63] : []);
                $gacha['supports'] = collect($supportIds)
                    ->map(fn (int $id) => $supports->get($id))
                    ->filter()
                    ->values();

                return $gacha;
            })
            ->sortBy(fn (array $gacha): int => match ($gacha['status']) {
                '진행 중' => 0,
                '예정' => 1,
                default => 2,
            })
            ->values()
            ->all();

        return view('gacha.index', [
            'limitedGachas' => $limitedGachas,
            'regularGachas' => $this->regularGachas(),
        ]);
    }

    public function show(int $gacha): View
    {
        $pickups = Member::query()
            ->with('character')
            ->get()
            ->keyBy('source_id');
        $allMembers = Member::query()->with('character')->get();
        $event = collect($this->limitedGachas($pickups))->firstWhere('id', $gacha);
        $permanent = false;

        if (! $event) {
            $regular = collect($this->regularGachas())->firstWhere('id', $gacha);
            abort_if($regular === null, 404);
            $permanent = true;
            $event = $regular + [
                'description' => $regular['description'],
                'image_alt' => $regular['title'].' 배너',
                'theme' => 'from-indigo-500/30 via-sky-500/15 to-[#111d33]',
                'timezone' => 'UTC+8',
                'starts_at_raw' => null,
                'ends_at_raw' => null,
                'members' => [],
            ];
        } else {
            $event = $this->withStatus($event);
        }

        $synced = isset($event['source_id']);
        $pickupMemberIds = $synced ? ($event['pickup_member_ids'] ?? []) : match ($gacha) {
            0 => [64],
            1 => [51, 52, 53, 54, 55],
            2 => [56, 57, 58, 59, 60],
            3 => [61, 62],
            default => [],
        };
        $pickupSupportIds = $synced ? ($event['pickup_support_ids'] ?? []) : ($gacha === 3 ? [62, 63] : []);
        $event['pickup_members'] = $this->members($pickups, $pickupMemberIds);
        if (! $synced && $gacha === 0) {
            $event['pickup_members'] = [$this->birthdayMember($pickups)];
        }
        $event['pickup_supports'] = $pickupSupportIds === []
            ? collect()
            : Snapshot::query()->whereIn('source_id', $pickupSupportIds)->get();
        $event['rates'] = $synced && $event['rates'] ? $event['rates'] : $this->ratesFor($gacha);
        $event['draw_pools'] = $this->drawPools(
            $event['rates'],
            $allMembers,
            $pickupMemberIds,
            Snapshot::query()->get(),
            $pickupSupportIds,
            (! $synced && $gacha === 0) || ($synced && (int) $event['source_id'] === 11),
        );
        $event['permanent'] = $permanent;
        $event['ten_pull_guarantee'] = $synced ? (bool) $event['ten_pull_guarantee'] : in_array($gacha, [1, 2, 8], true);

        return view('gacha.show', ['event' => $event]);
    }

    /** @return list<array{rarity: string, category: string, rate: string, pool: int, pickup: int|null}> */
    private function ratesFor(int $gacha): array
    {
        $standard = [
            ['rarity' => 'SSR', 'category' => '멤버', 'rate' => '3.0%', 'pool' => 10, 'pickup' => null],
            ['rarity' => 'SR', 'category' => '멤버', 'rate' => '8.5%', 'pool' => 25, 'pickup' => null],
            ['rarity' => 'R', 'category' => '멤버', 'rate' => '38.5%', 'pool' => 25, 'pickup' => null],
            ['rarity' => 'SSR', 'category' => '서포트', 'rate' => '6.0%', 'pool' => 10, 'pickup' => null],
            ['rarity' => 'SR', 'category' => '서포트', 'rate' => '17.0%', 'pool' => 25, 'pickup' => null],
            ['rarity' => 'R', 'category' => '서포트', 'rate' => '27.0%', 'pool' => 25, 'pickup' => null],
        ];

        return match ($gacha) {
            0 => [
                ['rarity' => '생일', 'category' => '멤버', 'rate' => '3.0%', 'pool' => 1, 'pickup' => 1],
                ['rarity' => 'SSR', 'category' => '멤버', 'rate' => '2.0%', 'pool' => 10, 'pickup' => null],
                ['rarity' => 'SR', 'category' => '멤버', 'rate' => '8.5%', 'pool' => 25, 'pickup' => null],
                ['rarity' => 'R', 'category' => '멤버', 'rate' => '36.5%', 'pool' => 25, 'pickup' => null],
                ['rarity' => 'SSR', 'category' => '서포트', 'rate' => '4.0%', 'pool' => 10, 'pickup' => null],
                ['rarity' => 'SR', 'category' => '서포트', 'rate' => '17.0%', 'pool' => 25, 'pickup' => null],
                ['rarity' => 'R', 'category' => '서포트', 'rate' => '29.0%', 'pool' => 25, 'pickup' => null],
            ],
            1, 2 => array_map(fn (array $rate): array => $rate['category'] === '멤버' && $rate['rarity'] === 'SSR'
                    ? array_replace($rate, ['pickup' => 5])
                    : $rate, $standard),
            3 => array_map(fn (array $rate): array => $rate['rarity'] === 'SSR'
                ? array_replace($rate, ['pool' => 12, 'pickup' => 2])
                : $rate, $standard),
            4 => [
                ['rarity' => 'SSR', 'category' => '멤버', 'rate' => '33.4%', 'pool' => 10, 'pickup' => null],
                ['rarity' => 'SSR', 'category' => '서포트', 'rate' => '66.6%', 'pool' => 10, 'pickup' => null],
            ],
            5 => [['rarity' => 'SSR', 'category' => '멤버', 'rate' => '100.0%', 'pool' => 10, 'pickup' => null]],
            6 => [['rarity' => 'SSR', 'category' => '서포트', 'rate' => '100.0%', 'pool' => 10, 'pickup' => null]],
            7, 9 => [
                ['rarity' => 'SSR', 'category' => '멤버', 'rate' => '3.0%', 'pool' => 10, 'pickup' => null],
                ['rarity' => 'SR', 'category' => '멤버', 'rate' => '47.0%', 'pool' => 25, 'pickup' => null],
                ['rarity' => 'SSR', 'category' => '서포트', 'rate' => '6.0%', 'pool' => 10, 'pickup' => null],
                ['rarity' => 'SR', 'category' => '서포트', 'rate' => '44.0%', 'pool' => 25, 'pickup' => null],
            ],
            8 => $standard,
            10 => [
                ['rarity' => 'SSR', 'category' => '멤버', 'rate' => '0.3%', 'pool' => 10, 'pickup' => null],
                ['rarity' => 'SR', 'category' => '멤버', 'rate' => '1.7%', 'pool' => 25, 'pickup' => null],
                ['rarity' => 'R', 'category' => '멤버', 'rate' => '23.0%', 'pool' => 25, 'pickup' => null],
                ['rarity' => 'SSR', 'category' => '서포트', 'rate' => '0.6%', 'pool' => 10, 'pickup' => null],
                ['rarity' => 'SR', 'category' => '서포트', 'rate' => '3.4%', 'pool' => 25, 'pickup' => null],
                ['rarity' => 'R', 'category' => '서포트', 'rate' => '21.0%', 'pool' => 25, 'pickup' => null],
                ['rarity' => '아이템', 'category' => '아이템', 'rate' => '50.0%', 'pool' => 3, 'pickup' => null],
            ],
            default => [],
        };
    }

    /**
     * @param  list<array{rarity: string, category: string, rate: string, pool: int, pickup: int|null}>  $rates
     * @param  Collection<int, Member>  $members
     * @param  Collection<int, Snapshot>  $snapshots
     * @param  list<int>  $pickupMemberIds
     * @param  list<int>  $pickupSupportIds
     * @return list<array{rarity: string, category: string, rate: float, pool: int, pickup: int|null, cards: list<array{name: string, image: ?string, pickup: bool}>}>
     */
    private function drawPools(array $rates, Collection $members, array $pickupMemberIds, Collection $snapshots, array $pickupSupportIds, bool $birthday): array
    {
        return collect($rates)->map(function (array $rate) use ($members, $pickupMemberIds, $snapshots, $pickupSupportIds, $birthday): array {
            if ($rate['category'] === '아이템') {
                $cards = collect(['멤버 EXP', '스냅 EXP', '코인'])->map(fn (string $name): array => ['name' => $name, 'image' => null, 'pickup' => false])->all();
            } elseif ($birthday && $rate['rarity'] === '생일') {
                $cards = [['name' => 'HAPPY BIRTHDAY 26-27 · 마하시 미쿠', 'image' => '/images/members/miku-happy-birthday.webp', 'pickup' => true]];
            } else {
                $source = $rate['category'] === '멤버' ? $members : $snapshots;
                $ids = $rate['category'] === '멤버' ? $pickupMemberIds : $pickupSupportIds;
                $cards = $source->where('rarity', $rate['rarity'])->map(fn ($card): array => [
                    'name' => $rate['category'] === '멤버' ? $card->name.' · '.$card->character->name : $card->name.' · '.$card->character_name,
                    'image' => $card->image_url,
                    'pickup' => in_array((int) $card->source_id, $ids, true),
                ])->values()->all();
                if ($cards === []) {
                    $cards = [['name' => $rate['rarity'].' '.$rate['category'].' 카드', 'image' => null, 'pickup' => false]];
                }
            }

            return [
                'rarity' => $rate['rarity'],
                'category' => $rate['category'],
                'rate' => (float) str_replace('%', '', $rate['rate']),
                'pool' => $rate['pool'],
                'pickup' => $rate['pickup'],
                'cards' => $cards,
            ];
        })->all();
    }

    /** @param array<string, mixed> $gacha
     * @return array<string, mixed>
     */
    private function withStatus(array $gacha): array
    {
        $start = Carbon::parse($gacha['starts_at_raw'], 'Asia/Seoul');
        $end = Carbon::parse($gacha['ends_at_raw'], 'Asia/Seoul');
        $now = now('Asia/Seoul');
        $gacha['status'] = match (true) {
            $now->lt($start) => '예정',
            $now->lte($end) => '진행 중',
            default => '종료',
        };
        $gacha['time_label'] = $gacha['status'] === '예정'
            ? $start->format('Y. m. d. H:i')
            : $end->format('Y. m. d. H:i').'까지';
        $gacha['legacy_time_label'] = $end->format('Y.m.d H:i');
        $gacha['start_timestamp'] = $start->timestamp;
        $gacha['end_timestamp'] = $end->timestamp;

        return $gacha;
    }

    /**
     * @param  Collection<int, Member>  $pickups
     * @return list<array{id: int, title: string, description: string, starts_at_raw: string, ends_at_raw: string, timezone: string, theme: string, image: string, image_alt: string, members: list<Member>}>
     */
    private function limitedGachas(Collection $pickups): array
    {
        $gachas = [
            [
                'id' => 0,
                'title' => '마하시 미쿠 HAPPY BIRTHDAY 26-27 뽑기',
                'description' => '마하시 미쿠 생일 기념 한정 뽑기',
                'starts_at_raw' => '2026-10-04 01:00:00',
                'ends_at_raw' => '2026-10-07 00:59:00',
                'timezone' => 'UTC+9',
                'theme' => 'from-cyan-400/30 via-pink-400/20 to-[#17191f]',
                'image' => '/images/members/miku-wearing-a-smile.webp',
                'banner' => '/images/gacha/banner-11.webp',
                'image_alt' => '마하시 미쿠 생일 뽑기',
                'member_count' => 61,
                'support_count' => 60,
                'members' => [$this->birthdayMember($pickups)],
            ],
            [
                'id' => 3,
                'title' => '내가 주연인 사이버 나이트 뽑기',
                'description' => '픽업 멤버와 서포트 카드의 등장 확률이 올라가는 기간 한정 뽑기',
                'starts_at_raw' => '2026-09-30 16:00:00',
                'ends_at_raw' => '2026-10-09 12:59:00',
                'timezone' => 'UTC+9',
                'theme' => 'from-violet-600/35 via-fuchsia-500/20 to-[#17191f]',
                'image' => '/images/members/miku-can-t-hide-this-heartbeat.webp',
                'banner' => '/images/gacha/banner-10.webp',
                'image_alt' => '사이버 나이트 픽업 멤버',
                'member_count' => 62,
                'support_count' => 62,
                'members' => $this->members($pickups, range(56, 60)),
            ],
            [
                'id' => 1,
                'title' => '오픈 기념! MyGO!!!!! 픽업 뽑기',
                'description' => 'MyGO!!!!! SSR 멤버 5명의 등장 확률이 올라가는 기간 한정 뽑기',
                'starts_at_raw' => '2026-09-01 00:00:00',
                'ends_at_raw' => '2026-10-08 23:59:00',
                'timezone' => 'UTC+8',
                'theme' => 'from-sky-500/30 via-indigo-500/20 to-[#17191f]',
                'image' => '/images/members/tomori-a-cry-cast-at-the-shooting-stars.webp',
                'banner' => '/images/gacha/banner-01.webp',
                'image_alt' => 'MyGO!!!!! 픽업 멤버',
                'members' => $this->members($pickups, range(51, 55)),
                'member_count' => 60,
                'support_count' => 60,
            ],
            [
                'id' => 2,
                'title' => '오픈 기념! Ave Mujica 픽업 뽑기',
                'description' => 'Ave Mujica SSR 멤버 5명의 등장 확률이 올라가는 기간 한정 뽑기',
                'starts_at_raw' => '2026-09-01 00:00:00',
                'ends_at_raw' => '2026-10-08 23:59:00',
                'timezone' => 'UTC+8',
                'theme' => 'from-violet-600/35 via-fuchsia-500/15 to-[#17191f]',
                'image' => '/images/members/oblivionis-the-goddess-who-reigns-over-paradise.webp',
                'banner' => '/images/gacha/banner-02.webp',
                'image_alt' => 'Ave Mujica 픽업 멤버',
                'members' => $this->members($pickups, range(56, 60)),
                'member_count' => 60,
                'support_count' => 60,
            ],
        ];

        $synced = Storage::disk('local')->json('bdon/gachas.json');
        if (! is_array($synced)) return $gachas;
        $byId = collect($gachas)->keyBy('id');
        foreach ($synced as $record) {
            if (! is_array($record) || ! isset($record['id'], $record['source_id'])) continue;
            $record['members'] = $this->members($pickups, $record['pickup_member_ids'] ?? []);
            $old = $byId->get((int) $record['id'], []);
            $byId->put((int) $record['id'], array_merge($old, $record));
        }

        return $byId->values()->all();
    }

    /**
     * @return list<array{id: int, title: string, description: string, badge: string, image: string, member_count: int, support_count: int|null}>
     */
    private function regularGachas(): array
    {
        return [
            ['id' => 4, 'title' => '오픈 기념! SSR 확정 뽑기', 'description' => 'SSR 멤버 또는 스냅을 확정으로 획득', 'badge' => '상시', 'image' => '/images/members/taki-the-beat-to-keep.webp', 'banner' => '/images/gacha/banner-03.webp', 'member_count' => 10, 'support_count' => 10],
            ['id' => 5, 'title' => '오픈 기념! SSR 멤버 확정 뽑기', 'description' => 'SSR 멤버를 확정으로 획득', 'badge' => '상시', 'image' => '/images/members/doloris-fleeting-echo.webp', 'banner' => '/images/gacha/banner-04.webp', 'member_count' => 10, 'support_count' => null],
            ['id' => 6, 'title' => '오픈 기념! SSR 스냅 확정 뽑기', 'description' => 'SSR 스냅을 확정으로 획득', 'badge' => '상시', 'image' => '/images/snapshots/tomori-hand-in-hand.webp', 'banner' => '/images/gacha/banner-05.webp', 'member_count' => 0, 'support_count' => 10],
            ['id' => 7, 'title' => '오픈 기념! SR 이상 확정 뽑기', 'description' => 'SR 이상의 멤버 또는 스냅을 획득', 'badge' => '상시', 'image' => '/images/members/anon-sparkling-stage.webp', 'banner' => '/images/gacha/banner-06.webp', 'member_count' => 35, 'support_count' => 35],
            ['id' => 8, 'title' => '아워 노트 뽑기', 'description' => '멤버와 서포트 카드를 모집하는 상시 뽑기', 'badge' => '상시', 'image' => '/images/members/mortis-answering-melody.webp', 'banner' => '/images/gacha/banner-07.webp', 'member_count' => 60, 'support_count' => 60],
            ['id' => 9, 'title' => '아워 노트 패스 뽑기', 'description' => '아워 노트 패스로 이용할 수 있는 전용 뽑기', 'badge' => '상시', 'image' => '/images/snapshots/oblivionis-noble-grace.webp', 'banner' => '/images/gacha/banner-08.webp', 'member_count' => 35, 'support_count' => 35],
            ['id' => 10, 'title' => '광고 시청 뽑기', 'description' => '광고 시청으로 이용할 수 있는 뽑기', 'badge' => '상시', 'image' => '/images/members/amoris-dazzling-rhythm.webp', 'banner' => '/images/gacha/banner-09.webp', 'member_count' => 60, 'support_count' => 60],
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

    private function birthdayMember(Collection $pickups): Member
    {
        $member = $pickups->get(64) ?? new Member;
        $member->setAttribute('source_id', 64);
        $member->setAttribute('slug', null);
        $member->setAttribute('name', 'HAPPY BIRTHDAY 26-27');
        $member->setAttribute('rarity', '생일');
        $member->setAttribute('image_url', '/images/members/miku-happy-birthday.webp');
        $member->setRelation('character', (object) ['name' => '마하시 미쿠']);

        return $member;
    }
}
