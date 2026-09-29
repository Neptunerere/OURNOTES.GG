<?php

namespace App\Console\Commands;

use App\Models\Character;
use App\Models\Member;
use App\Models\Snapshot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

#[Signature('ournotes:sync-all {--skip-images : 이미지 다운로드를 건너뜁니다} {--file= : 이미 내려받은 SPICA JSON 파일을 사용합니다}')]
#[Description('SPICA 한국어 원본에서 5개 밴드의 캐릭터와 전체 카드를 동기화합니다.')]
class SyncAllSpicaData extends Command
{
    private const DATA_URL = 'https://spica.wiki/ournotes/data/d-5b79324a26.json';

    private const BAND_NAMES = [
        1 => 'MyGO!!!!!',
        2 => 'Ave Mujica',
        3 => '무겐다이 뮤타입',
        4 => 'millsage',
        5 => '일가 Dumb Rock!',
    ];

    private const ATTR_NAMES = [1 => '레드', 2 => '블루', 3 => '그린', 4 => '옐로우', 5 => '퍼플'];

    private const RARITY_NAMES = [2 => 'R', 3 => 'SR', 4 => 'SSR'];

    public function handle(): int
    {
        $this->info('SPICA 한국어 전체 데이터를 불러오는 중...');
        if ($file = $this->option('file')) {
            if (! is_file($file) || ! is_array($data = json_decode(file_get_contents($file), true))) {
                $this->error('지정한 SPICA JSON 파일을 읽을 수 없습니다.');
                return self::FAILURE;
            }
        } else {
            $response = Http::timeout(60)->retry(2, 500)->withUserAgent('OurNotesLab/1.0 (fan database)')->get(self::DATA_URL);
            if ($response->failed() || ! is_array($data = $response->json())) {
                $this->error("SPICA 응답 오류: {$response->status()}");
                return self::FAILURE;
            }
        }

        $characters = [];
        foreach ((array) ($data['chars'] ?? []) as $id => $source) {
            $bandId = (int) ($source['band'] ?? 0);
            if (! isset(self::BAND_NAMES[$bandId])) {
                continue;
            }

            $name = data_get($source, 'name.ko') ?: data_get($source, 'name.en') ?: data_get($source, 'name.ja');
            $character = Character::query()->where('name', $name)->first() ?? new Character;
            $character->fill([
                'name' => $name,
                'slug' => $source['slug'] ?? Str::slug($name),
                'band' => self::BAND_NAMES[$bandId],
                'part' => $source['part'] ?? '-',
                'color' => $source['color'] ?? '#6c5ce7',
            ])->save();
            $characters[(int) $id] = $character;
        }

        $imageDirectory = public_path('images/members');
        if (! $this->option('skip-images') && ! is_dir($imageDirectory)) {
            mkdir($imageDirectory, 0755, true);
        }

        $memberCount = 0;
        $imageCount = 0;
        $sourceSlugs = [];
        foreach (($data['members'] ?? []) as $source) {
            $character = $characters[(int) ($source['char'] ?? 0)] ?? null;
            if (! $character) {
                continue;
            }

            $name = data_get($source, 'title.ko') ?: data_get($source, 'title.en') ?: data_get($source, 'title.ja');
            $slug = $source['slug'] ?? md5($character->name.$name);
            $sourceSlugs[] = $slug;
            $member = Member::query()->where('slug', $slug)->first()
                ?? Member::query()->where('character_id', $character->id)->where('name', $name)->first()
                ?? new Member;

            $filename = $slug.'.webp';
            $bundledImage = $imageDirectory.DIRECTORY_SEPARATOR.$filename;
            $imagePath = is_file($bundledImage) ? '/images/members/'.$filename : $member->image_url;
            if (! $this->option('skip-images') && filled($source['full'] ?? null)) {
                $image = Http::timeout(45)->retry(2, 300)->withUserAgent('OurNotesLab/1.0')->get('https://spica.wiki'.$source['full']);
                if ($image->successful()) {
                    file_put_contents($imageDirectory.DIRECTORY_SEPARATOR.$filename, $image->body());
                    $imagePath = '/images/members/'.$filename;
                    $imageCount++;
                }
            }

            $member->fill([
                'source_id' => (int) $source['id'],
                'character_id' => $character->id,
                'name' => $name,
                'slug' => $slug,
                'rarity' => self::RARITY_NAMES[(int) ($source['rarity'] ?? 0)] ?? 'R',
                'rarity_id' => (int) ($source['rarity'] ?? 0),
                'type' => self::ATTR_NAMES[(int) ($source['attr'] ?? 0)] ?? '레드',
                'performance' => (int) ($source['perf'] ?? 0),
                'technique' => (int) ($source['tech'] ?? 0),
                'visual' => (int) ($source['vis'] ?? 0),
                'max_performance' => (int) ($source['mx'][0] ?? $source['perf'] ?? 0),
                'max_technique' => (int) ($source['mx'][1] ?? $source['tech'] ?? 0),
                'max_visual' => (int) ($source['mx'][2] ?? $source['vis'] ?? 0),
                'leader_skill_id' => (int) ($source['leader'] ?? 0),
                'live_skill_id' => (int) ($source['live'] ?? 0),
                'gekisou_skill_id' => (int) ($source['gekisou'] ?? 0),
                'skill_type' => $this->skillName($data, 'live', (int) ($source['live'] ?? 0)),
                'score_up' => $this->firstPercent($this->skillText($data, 'live', (int) ($source['live'] ?? 0))),
                'leader_skill' => $this->skillText($data, 'leader', (int) ($source['leader'] ?? 0)),
                'live_skill' => $this->skillText($data, 'live', (int) ($source['live'] ?? 0)),
                'gekisou_skill' => $this->skillText($data, 'gekisou', (int) ($source['gekisou'] ?? 0)),
                'leader_skill_levels' => $this->skillLevels($data, 'leader', (int) ($source['leader'] ?? 0)),
                'live_skill_levels' => $this->skillLevels($data, 'live', (int) ($source['live'] ?? 0)),
                'gekisou_skill_levels' => $this->skillLevels($data, 'gekisou', (int) ($source['gekisou'] ?? 0)),
                'image_url' => $imagePath,
                'source_url' => 'https://spica.wiki/ournotes/ko/member/'.$slug,
                'released_at' => $source['rel'] ?? null,
            ])->save();
            $memberCount++;
        }

        // 이전 샘플 시드나 사라진 원본 레코드가 중복 노출되지 않도록 정리합니다.
        Member::query()->whereNotIn('slug', $sourceSlugs)->delete();
        Character::query()->doesntHave('members')->delete();

        $snapshotSlugs = [];
        $snapshotCount = 0;
        $snapshotImageCount = 0;
        $characterNames = collect($characters)->map(fn (Character $character) => $character->name);
        $snapshotDirectory = public_path('images/snapshots');
        if (! $this->option('skip-images') && ! is_dir($snapshotDirectory)) {
            mkdir($snapshotDirectory, 0755, true);
        }

        foreach (($data['snaps'] ?? []) as $source) {
            $slug = $source['slug'];
            $snapshotSlugs[] = $slug;
            $snapshot = Snapshot::firstOrNew(['slug' => $slug]);
            $characterName = collect($source['chars'] ?? [$source['char'] ?? null])->filter()->map(fn ($id) => $characterNames->get((int) $id))->filter()->join(' · ');
            $filename = $slug.'.webp';
            $bundledImage = $snapshotDirectory.DIRECTORY_SEPARATOR.$filename;
            $imagePath = is_file($bundledImage) ? '/images/snapshots/'.$filename : $snapshot->image_url;

            if (! $this->option('skip-images') && filled($source['full'] ?? null)) {
                $image = Http::timeout(45)->retry(2, 300)->withUserAgent('OurNotesLab/1.0')->get('https://spica.wiki'.$source['full']);
                if ($image->successful()) {
                    file_put_contents($snapshotDirectory.DIRECTORY_SEPARATOR.$filename, $image->body());
                    $imagePath = '/images/snapshots/'.$filename;
                    $snapshotImageCount++;
                }
            }

            $supportIds = array_values(array_filter([(int) ($source['s1'] ?? 0), (int) ($source['s2'] ?? 0)]));
            $gekisouIds = array_values(array_filter([(int) ($source['g1'] ?? 0), (int) ($source['g2'] ?? 0)]));
            $snapshot->fill([
                'source_id' => (int) $source['id'],
                'name' => data_get($source, 'title.ko') ?: data_get($source, 'title.en') ?: data_get($source, 'title.ja'),
                'character_name' => $characterName,
                'band' => self::BAND_NAMES[(int) $source['band']] ?? '-',
                'rarity' => self::RARITY_NAMES[(int) ($source['rarity'] ?? 0)] ?? 'SSR',
                'rarity_id' => (int) ($source['rarity'] ?? 0),
                'type' => self::ATTR_NAMES[(int) ($source['attr'] ?? 0)] ?? '레드',
                'performance' => (float) ($source['perf'] ?? 0),
                'technique' => (float) ($source['tech'] ?? 0),
                'visual' => (float) ($source['vis'] ?? 0),
                'level_growth' => (int) ($source['lg'] ?? 0),
                'rank_growth' => (int) ($source['rg'] ?? 0),
                'support_skill_1_id' => $supportIds[0] ?? null,
                'support_skill_2_id' => $supportIds[1] ?? null,
                'gekisou_support_1_id' => $gekisouIds[0] ?? null,
                'gekisou_support_2_id' => $gekisouIds[1] ?? null,
                'live_support' => collect($supportIds)->map(fn ($id) => $this->skillText($data, 'support', $id))->filter()->join('<hr>'),
                'gekiso_support' => collect($gekisouIds)->map(fn ($id) => $this->skillText($data, 'gsupport', $id))->filter()->join('<hr>'),
                'support_skill_levels' => collect($supportIds)->map(fn ($id) => $this->skillLevels($data, 'support', $id))->values()->all(),
                'gekisou_support_levels' => collect($gekisouIds)->map(fn ($id) => $this->skillLevels($data, 'gsupport', $id))->values()->all(),
                'diary' => data_get($source, 'diary.ko'),
                'released_at' => $source['rel'] ?? null,
                'image_url' => $imagePath,
                'source_url' => 'https://spica.wiki/ournotes/ko/snap/'.$slug,
            ])->save();
            $snapshotCount++;
        }
        Snapshot::query()->whereNotIn('slug', $snapshotSlugs)->delete();

        $this->table(['밴드', '캐릭터'], collect(self::BAND_NAMES)->map(fn ($band) => [$band, Character::where('band', $band)->count()]));
        $this->info("캐릭터 ".count($characters)."명, 멤버 카드 {$memberCount}장, 스냅 {$snapshotCount}장, 이미지 ".($imageCount + $snapshotImageCount)."장을 동기화했습니다.");

        return self::SUCCESS;
    }

    private function skillName(array $data, string $group, int $id): ?string
    {
        return data_get($data, "skills.{$group}.{$id}.name.ko");
    }

    private function skillText(array $data, string $group, int $id, int $level = 5): ?string
    {
        if ($id === 0) {
            return null;
        }
        $levels = data_get($data, "skills.{$group}.{$id}.levels", []);
        $selected = collect($levels)->firstWhere('lv', $level) ?? collect($levels)->last();
        return $selected['ko'] ?? null;
    }

    private function firstPercent(?string $text): int
    {
        preg_match('/([0-9]+(?:\.[0-9]+)?)%/', strip_tags($text ?? ''), $match);
        return (int) round((float) ($match[1] ?? 0));
    }

    private function skillLevels(array $data, string $group, int $id): array
    {
        if ($id === 0) {
            return [];
        }
        return collect(data_get($data, "skills.{$group}.{$id}.levels", []))
            ->mapWithKeys(fn ($level) => [(int) $level['lv'] => $level['ko'] ?? ''])
            ->all();
    }
}
