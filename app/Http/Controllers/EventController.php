<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventController extends Controller
{
    public function index(): View
    {
        return view('events.index', ['events' => $this->events()]);
    }

    public function show(int $event): View
    {
        $events = collect($this->events())->keyBy('id');
        if (! $events->has($event)) {
            $legacy = $this->event();
            abort_unless($event === 1, 404);
            $events->put(1, $legacy);
        }

        return view('events.show', ['event' => $events->get($event)]);
    }

    public function image(int $event, string $file): StreamedResponse
    {
        abort_unless(preg_match('/^(?:banner|badge|logo|song|member-\d+|support-\d+)\.\w+$/', $file), 404);
        $path = storage_path('app/public/events/'.$event.'/'.$file);
        abort_unless(is_file($path), 404);

        return response()->stream(function () use ($path): void {
            $handle = fopen($path, 'rb');
            if ($handle !== false) {
                fpassthru($handle);
                fclose($handle);
            }
        }, 200, ['Content-Type' => mime_content_type($path) ?: 'application/octet-stream', 'Cache-Control' => 'public, max-age=86400']);
    }

    /** @return list<array<string, mixed>> */
    private function events(): array
    {
        return Cache::remember('bdon-events-v2', now()->addMinutes(15), function (): array {
            $path = 'bdon/events.json';
            $records = Storage::disk('local')->exists($path) ? Storage::disk('local')->json($path) : null;
            $current = $this->event();
            if (! is_array($records)) {
                return [$current];
            }

            $mapped = collect($records)->filter(fn ($record): bool => is_array($record) && isset($record['id'], $record['title']))->map(function (array $record) use ($current): array {
                $start = $this->parseEventDate($record['starts_at'] ?? null);
                $end = $this->parseEventDate($record['ends_at'] ?? null) ?? $this->endDateFromDescription($record['description'] ?? null);
                $status = match (true) {
                    $start && now('Asia/Seoul')->lt($start) => '예정',
                    $end && now('Asia/Seoul')->gt($end) => '종료',
                    $start !== null => '진행 중',
                    default => '예정',
                };
                $startLabel = $start?->format('Y. m. d. H:i') ?? ($record['starts_at'] ?? '');
                $endLabel = $end?->format('Y. m. d. H:i') ?? ($record['ends_at'] ?? '');
                if ((int) $record['id'] === 1) {
                    return array_replace($current, [
                        'title' => $record['title'],
                        'starts_at' => $startLabel ?: $current['starts_at'],
                        'ends_at' => $endLabel ?: $current['ends_at'],
                        'display_ends_at' => $record['display_ends_at'] ?? $current['display_ends_at'],
                        'status' => $status,
                        'url' => $record['url'],
                        'member_bonuses' => $this->mapBonusRows($record['member_bonuses'] ?? [], collect($record['images'] ?? [])->keyBy('key')),
                        'support_bonuses' => $this->mapBonusRows($record['support_bonuses'] ?? [], collect($record['images'] ?? [])->keyBy('key')),
                        'event_cards' => $this->mapEventCards($record['event_cards'] ?? [], collect($record['images'] ?? [])->keyBy('key')),
                        'song' => $this->mapEventSong($record['song'] ?? null, collect($record['images'] ?? [])->keyBy('key')) ?? $current['song'],
                    ]);
                }

                $fallback = $current;
                $fallback['id'] = (int) $record['id'];
                $fallback['title'] = $record['title'];
                $fallback['status'] = $status;
                $fallback['starts_at'] = $startLabel;
                $fallback['ends_at'] = $endLabel;
                $fallback['display_ends_at'] = $record['display_ends_at'] ?? '';
                $images = collect($record['images'] ?? [])->keyBy('key');
                $fallback['banner'] = $this->imageUrl($images->get('banner'), '/images/events/1/banner.webp');
                $fallback['logo'] = $this->imageUrl($images->get('logo'));
                $fallback['badge'] = $this->imageUrl($images->get('badge'));
                $fallback['song'] = $this->mapEventSong($record['song'] ?? null, $images);
                $fallback['member_bonuses'] = $this->mapBonusRows($record['member_bonuses'] ?? [], $images);
                $fallback['support_bonuses'] = $this->mapBonusRows($record['support_bonuses'] ?? [], $images);
                $fallback['event_cards'] = $this->mapEventCards($record['event_cards'] ?? [], $images);
                $fallback['bonus_tables'] = $record['bonuses'] ?? [];
                $fallback['related_url'] = $record['url'];
                $fallback['point_rewards'] = collect($record['point_rewards'] ?? [])->flatten(1)->map(fn (array $row): array => [$row[0] ?? '', $row[1] ?? ''])->filter(fn (array $row): bool => $row[0] !== '' && $row[1] !== '' && preg_match('/\d/', $row[0]))->values()->all();
                $fallback['reward_totals'] = [];
                $fallback['reward_images'] = [];
                $fallback['live_rewards'] = collect($record['live_rewards'][0] ?? [])->skip(1)->all();
                $fallback['challenge_rewards'] = collect($record['live_rewards'][1] ?? [])->skip(1)->all();
                $fallback['url'] = $record['url'];

                return $fallback;
            })->all();

            return collect($mapped)->contains(fn (array $event): bool => (int) $event['id'] === 1)
                ? $mapped
                : array_merge([$current], $mapped);
        });
    }

    private function imageUrl(?array $image, ?string $fallback = null): ?string
    {
        $local = data_get($image, 'local');
        if (is_string($local) && (str_starts_with($local, '/event-assets/') || str_starts_with($local, '/events/'))) {
            return $local;
        }
        if (is_string($local) && preg_match('~/storage/events/(\d+)/([^/]+)$~', parse_url($local, PHP_URL_PATH) ?: $local, $match)) {
            return route('events.asset', ['event' => $match[1], 'file' => $match[2]], false);
        }

        return data_get($image, 'url', $fallback);
    }

    /** @return list<array<string, mixed>> */
    private function mapBonusRows(array $rows, \Illuminate\Support\Collection $images): array
    {
        return collect($rows)->map(function (array $bonus) use ($images): array {
            $bonus['image'] = $this->imageUrl($images->get($bonus['image_key'] ?? ''), $bonus['image'] ?? null) ?? '/images/ui/card-placeholder.webp';

            return $bonus;
        })->values()->all();
    }

    /** @return list<array<string, mixed>> */
    private function mapEventCards(array $cards, \Illuminate\Support\Collection $images): array
    {
        return collect($cards)->map(function (array $card) use ($images): array {
            $card['image'] = $this->imageUrl($images->get($card['image_key'] ?? ''), $card['image'] ?? null) ?? '/images/ui/card-placeholder.webp';

            return $card;
        })->values()->all();
    }

    /** @return array<string, mixed>|null */
    private function mapEventSong(?array $song, \Illuminate\Support\Collection $images): ?array
    {
        if (! $song || empty($song['title'])) return null;
        $song['image'] = $this->imageUrl($images->get($song['image_key'] ?? 'song'), $song['image'] ?? null);

        return $song['image'] ? $song : null;
    }

    private function parseEventDate(?string $date): ?Carbon
    {
        if (! is_string($date) || $date === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y. m. d. H:i', $date, 'Asia/Seoul');
        } catch (\Throwable) {
            return null;
        }
    }

    private function endDateFromDescription(?string $description): ?Carbon
    {
        if (! is_string($description) || ! preg_match('/개최 기간\s*\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2}\s*[–-]\s*(\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2})/u', $description, $match)) {
            return null;
        }

        return $this->parseEventDate($match[1]);
    }

    /** @return array<string, mixed> */
    private function event(): array
    {
        $startsAt = '2026-09-30 18:00:00';
        $endsAt = '2026-10-08 20:59:00';
        $now = now('Asia/Seoul');
        $start = Carbon::parse($startsAt, 'Asia/Seoul');
        $end = Carbon::parse($endsAt, 'Asia/Seoul');

        $status = match (true) {
            $now->lt($start) => '예정',
            $now->lte($end) => '진행 중',
            default => '종료',
        };

        return [
            'id' => 1,
            'title' => '사랑의 격류 AtoZ',
            'status' => $status,
            'server' => '일본',
            'starts_at' => $start->format('Y. m. d. H:i'),
            'ends_at' => $end->format('Y. m. d. H:i'),
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
