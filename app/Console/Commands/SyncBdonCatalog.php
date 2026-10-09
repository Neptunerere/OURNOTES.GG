<?php

namespace App\Console\Commands;

use App\Models\Character;
use App\Models\Member;
use App\Models\Snapshot;
use App\Models\Song;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

#[Signature('ournotes:sync-bdon')]
#[Description('BDon 한국어 도감에서 멤버 카드, 서포트 카드, 곡 목록과 이미지를 갱신합니다.')]
class SyncBdonCatalog extends Command
{
    private const BASE = 'https://bdon.moe/ko/';

    private const BANDS = [
        'MyGO!!!!!', 'Ave Mujica', '무겐다이 뮤타입', 'millsage', '일가 Dumb Rock!',
    ];

    public function handle(): int
    {
        try {
            $cards = $this->catalog('cards');
            $supports = $this->catalog('support-cards');
            $music = $this->catalog('music');
        } catch (\Throwable $exception) {
            $this->error('BDon 목록을 불러오지 못했습니다: '.$exception->getMessage());

            return self::FAILURE;
        }

        $cardUpdates = 0;
        $cardCreates = 0;
        $cardSkipped = 0;
        $supportUpdates = 0;
        $supportCreates = 0;
        $supportSkipped = 0;
        foreach ($cards as $row) {
            $record = Member::where('source_id', $row['id'])->first();
            if ($record) {
                $changes = ['name' => $row['title'], 'image_url' => $this->cardImage($record, $row['image']), 'source_url' => self::BASE.'cards/'.$row['id']];
                if (empty($record->leader_skill_levels) || empty($record->live_skill_levels) || empty($record->gekisou_skill_levels) || ! $record->leader_skill_id || ! $record->live_skill_id || ! $record->gekisou_skill_id) {
                    $changes = array_merge($changes, $this->memberDetailChanges($row));
                }
                if ($row['id'] === 64) {
                    $changes = array_merge($changes, [
                        'image_url' => $this->localImage('members/miku-happy-birthday.webp') ?? 'https://assets.bdon.moe/kr/zh-Hans/MemberCard/64/member_full/member_full.webp',
                        'type' => '퍼플', 'rarity' => '생일', 'rarity_id' => 4,
                        'performance' => 36213, 'technique' => 27856, 'visual' => 27856,
                        'max_performance' => 36213, 'max_technique' => 27856, 'max_visual' => 27856,
                        'leader_skill_id' => 59, 'live_skill_id' => 8, 'gekisou_skill_id' => 22, 'score_up' => 115,
                        'leader_skill' => '일가 Dumb Rock! 멤버의 퍼포먼스 85.0% UP, 추가로 퍼플 멤버는 퍼포먼스 40.0% UP',
                        'live_skill' => '[심플] 5.0초간, 스코어 115.0% UP',
                        'gekisou_skill' => 'LUCK 격주 중 추첨 게이지 획득량 200.0% UP; BAD 이하 판정의 라이프 감소를 20.0% 완화한다',
                        'released_at' => '2026-10-04',
                    ]);
                }
                $record->update($changes);
                $cardUpdates++;
            } elseif ($row['id'] !== 64 && $this->createMember($row)) {
                $cardCreates++;
            } elseif ($row['id'] !== 64) {
                $cardSkipped++;
            }
        }
        $miku = Character::where('name', '마하시 미쿠')->first();
        $birthday = collect($cards)->firstWhere('id', 64);
        if ($miku && $birthday && ! Member::where('source_id', 64)->exists()) {
            Member::create([
                'character_id' => $miku->id, 'source_id' => 64, 'slug' => 'miku-happy-birthday-26-27',
                'name' => $birthday['title'], 'rarity' => '생일', 'rarity_id' => 4, 'type' => '퍼플',
                'performance' => 36213, 'technique' => 27856, 'visual' => 27856,
                'max_performance' => 36213, 'max_technique' => 27856, 'max_visual' => 27856,
                'leader_skill_id' => 59, 'live_skill_id' => 8, 'gekisou_skill_id' => 22,
                'skill_type' => '스코어 UP', 'score_up' => 115,
                'leader_skill' => '일가 Dumb Rock! 멤버의 퍼포먼스 85.0% UP, 추가로 퍼플 멤버는 퍼포먼스 40.0% UP',
                'live_skill' => '[심플] 5.0초간, 스코어 115.0% UP',
                'gekisou_skill' => 'LUCK 격주 중 추첨 게이지 획득량 200.0% UP; BAD 이하 판정의 라이프 감소를 20.0% 완화한다',
                'image_url' => $this->localImage('members/miku-happy-birthday.webp') ?? 'https://assets.bdon.moe/kr/zh-Hans/MemberCard/64/member_full/member_full.webp',
                'source_url' => self::BASE.'cards/64', 'released_at' => '2026-10-04',
            ]);
            $cardCreates++;
        }

        foreach ($supports as $row) {
            $record = Snapshot::where('source_id', $row['id'])->first();
            if ($record) {
                $changes = ['name' => $row['title'], 'image_url' => $this->supportImage($record, $row['image']), 'source_url' => self::BASE.'support-cards/'.$row['id']];
                if (empty($record->support_skill_levels) || empty($record->gekisou_support_levels) || ! $record->support_skill_1_id || ! $record->gekisou_support_1_id) {
                    $changes = array_merge($changes, $this->supportDetailChanges($row));
                }
                if ($row['id'] === 70) {
                    $changes = array_merge($changes, ['rarity' => '스페셜', 'rarity_id' => 5, 'type' => '레드', 'image_url' => $this->localImage('snapshots/ave-mujica-abracadabra.webp') ?? 'https://assets.bdon.moe/kr/zh-Hans/SupportCard/70/snap_full/snap_full.webp']);
                }
                $record->update($changes);
                $supportUpdates++;
            } elseif ($row['id'] !== 70 && $this->createSupport($row)) {
                $supportCreates++;
            } elseif ($row['id'] !== 70) {
                $supportSkipped++;
            }
        }
        $abracadabra = collect($supports)->firstWhere('id', 70);
        if ($abracadabra && ! Snapshot::where('source_id', 70)->exists()) {
            Snapshot::create([
                'source_id' => 70, 'slug' => 'ave-mujica-abracadabra', 'name' => $abracadabra['title'],
                'character_name' => '돌로리스 / 미스미 우이카 · 모르티스 / 와카바 무츠미 · 티모리스 / 야하타 우미리 · 아모리스 / 유텐지 냐무 · 오블리비오니스 / 토가와 사키코',
                'band' => 'Ave Mujica', 'rarity' => '스페셜', 'rarity_id' => 5, 'type' => '레드',
                'performance' => 1300, 'technique' => 1400, 'visual' => 1200, 'level_growth' => 5, 'rank_growth' => 5,
                'support_skill_1_id' => 71, 'support_skill_levels' => [[1 => '장착한 멤버의 라이브 스킬 발동 시간을 3.00초 연장']],
                'live_support' => '장착한 멤버의 라이브 스킬 발동 시간 <b class="hi">3.00초</b> 연장',
                'image_url' => $this->localImage('snapshots/ave-mujica-abracadabra.webp') ?? 'https://assets.bdon.moe/kr/zh-Hans/SupportCard/70/snap_full/snap_full.webp',
                'source_url' => self::BASE.'support-cards/70', 'released_at' => '2026-09-24',
            ]);
            $supportCreates++;
        }

        $musicUpdates = 0;
        foreach ($music as $row) {
            $titleSlug = Str::slug($row['title']) ?: 'song';
            $canonicalSlug = $titleSlug.'-'.$row['id'];
            $song = Song::where('source_id', $row['id'])->first() ?? Song::firstOrNew(['slug' => $canonicalSlug]);
            if ($song->exists && (preg_match('/(?:^|-)ez[0-9]|(?:nm|hd|ex)[0-9]/i', $song->slug ?? '') || str_starts_with($song->slug ?? '', '-'))) {
                $song->slug = $canonicalSlug;
            }
            $song->fill([
                'source_id' => $row['id'],
                'title' => $row['title'],
                'slug' => $song->slug ?: $canonicalSlug,
                'band' => $row['band'] ?: ($song->band ?: '-'),
                'easy' => $row['levels'][0] ?? $song->easy,
                'normal' => $row['levels'][1] ?? $song->normal,
                'hard' => $row['levels'][2] ?? $song->hard,
                'expert' => $row['levels'][3] ?? $song->expert,
                'composer' => $row['composer'] ?: $song->composer,
                'image_url' => $row['image'] ?: $song->image_url,
                'source_url' => self::BASE.'music/'.$row['id'],
            ])->save();
            if (! preg_match('/[0-9]/', (string) $song->bpm)) {
                $song->bpm = null;
                $song->save();
            }
            if (! $song->note_count || $song->note_count > 3000) {
                $song->fill($this->songDetails($row['id']))->save();
            }
            $musicUpdates++;
        }

        DB::table('data_sync_states')->updateOrInsert(
            ['source' => 'bdon'],
            ['last_synced_at' => now('UTC')],
        );

        $this->table(['BDon 목록', '건수', '신규 추가', '기존 갱신', '미처리'], [
            ['멤버 카드', count($cards), $cardCreates, $cardUpdates, $cardSkipped],
            ['서포트 카드', count($supports), $supportCreates, $supportUpdates, $supportSkipped],
            ['곡', count($music), 0, $musicUpdates, 0],
        ]);

        return self::SUCCESS;
    }

    /** @param array{id:int,title:string,image:?string,band:?string,composer:?string,levels:list<?float>} $row */
    private function createMember(array $row): bool
    {
        $detail = $this->catalogDetail('cards', $row['id']);
        if (! $detail) return false;
        $character = $this->charactersIn($detail['text'])->first();
        $rarity = $this->rarity($detail['text']);
        $type = $this->type($detail['text']);
        $stats = $this->cardStats($detail['text']);
        if (! $character || ! $rarity || ! $type || ! $stats) {
            $this->warn("신규 멤버 카드 #{$row['id']}에 필요한 캐릭터/등급/속성/능력치를 읽지 못해 건너뜁니다.");
            return false;
        }

        $skills = $detail['skills'];
        $leaderSkill = $skills['leader']['levels'][5] ?? '';
        $liveSkill = $skills['live']['levels'][5] ?? '';
        $gekisouSkill = $skills['gekisou']['levels'][5] ?? '';
        $scoreUp = preg_match('/스코어\s*([0-9]+(?:\.[0-9]+)?)\s*%\s*UP/u', $liveSkill, $score) ? (int) round((float) $score[1]) : 0;
        $slug = Str::slug($character->name.'-'.$row['title'].'-'.$row['id']) ?: 'member-'.$row['id'];
        Member::create([
            'source_id' => $row['id'], 'character_id' => $character->id, 'slug' => $slug,
            'name' => $row['title'], 'rarity' => $rarity['name'], 'rarity_id' => $rarity['id'], 'type' => $type,
            'performance' => $stats['performance'], 'technique' => $stats['technique'], 'visual' => $stats['visual'],
            'max_performance' => $stats['performance'], 'max_technique' => $stats['technique'], 'max_visual' => $stats['visual'],
            'leader_skill_id' => $skills['leader']['id'], 'live_skill_id' => $skills['live']['id'],
            'gekisou_skill_id' => $skills['gekisou']['id'], 'skill_type' => $scoreUp ? '스코어 UP' : null, 'score_up' => $scoreUp,
            'leader_skill' => $leaderSkill ?: null, 'live_skill' => $liveSkill ?: null, 'gekisou_skill' => $gekisouSkill ?: null,
            'leader_skill_levels' => $skills['leader']['levels'], 'live_skill_levels' => $skills['live']['levels'], 'gekisou_skill_levels' => $skills['gekisou']['levels'],
            'image_url' => $this->fullAssetUrl($row['image'], 'member_thumbnail/member_thumbnail.webp', 'member_full/member_full.webp'),
            'source_url' => self::BASE.'cards/'.$row['id'], 'released_at' => $this->releaseDate($detail['text']),
        ]);

        return true;
    }

    /** @param array{id:int,title:string,image:?string,band:?string,composer:?string,levels:list<?float>} $row */
    private function createSupport(array $row): bool
    {
        $detail = $this->catalogDetail('support-cards', $row['id']);
        if (! $detail) return false;
        $characters = $detail['characters']->isNotEmpty() ? $detail['characters'] : $this->charactersIn($detail['text']);
        $rarity = $this->rarity($detail['text']);
        $type = $this->type($detail['text']);
        $stats = $this->cardStats($detail['text']);
        $band = $row['band'] ?: $characters->first()?->band;
        if ($characters->isEmpty() || ! $rarity || ! $type || ! $stats || blank($band)) {
            $missing = collect(['캐릭터' => $characters->isNotEmpty(), '등급' => (bool) $rarity, '속성' => (bool) $type, '능력치' => (bool) $stats, '밴드' => filled($band)])
                ->filter(fn (bool $found): bool => ! $found)->keys()->implode('/');
            $this->warn("신규 서포트 카드 #{$row['id']}에서 {$missing} 정보를 읽지 못해 건너뜁니다.");
            return false;
        }
        $characterName = $characters->pluck('name')->implode(' · ');
        $slug = Str::slug($row['title'].'-'.$row['id']) ?: 'support-'.$row['id'];
        $skills = $detail['skills'];
        Snapshot::create([
            'source_id' => $row['id'], 'slug' => $slug, 'name' => $row['title'], 'character_name' => $characterName,
            'band' => $band, 'rarity' => $rarity['name'], 'rarity_id' => $rarity['id'], 'type' => $type,
            'performance' => $stats['performance'], 'technique' => $stats['technique'], 'visual' => $stats['visual'],
            'support_skill_1_id' => $skills['live']['id'], 'support_skill_2_id' => null,
            'gekisou_support_1_id' => $skills['gekisou']['id'], 'gekisou_support_2_id' => null,
            'support_skill_levels' => $skills['live']['levels'] ? [$skills['live']['levels']] : null,
            'gekisou_support_levels' => $skills['gekisou']['levels'] ? [$skills['gekisou']['levels']] : null,
            'live_support' => $skills['live']['levels'][5] ?? null, 'gekiso_support' => $skills['gekisou']['levels'][5] ?? null,
            'image_url' => $this->fullAssetUrl($row['image'], 'snap_thumbnail/snap_thumbnail.webp', 'snap_full/snap_full.webp'),
            'source_url' => self::BASE.'support-cards/'.$row['id'], 'released_at' => $this->releaseDate($detail['text']),
        ]);

        return true;
    }

    /** @return array{text:string,skills:array<string,array{id:?int,levels:array<int,string>}>,characters:\Illuminate\Support\Collection}|null */
    private function catalogDetail(string $catalog, int $id): ?array
    {
        try {
            $response = Http::connectTimeout(8)->timeout(45)->retry(2, 500)->withUserAgent('OurNotesLab/1.0 (fan database)')->get(self::BASE.$catalog.'/'.$id);
            if ($response->failed()) throw new \RuntimeException('HTTP '.$response->status());
            $document = new \DOMDocument;
            @$document->loadHTML('<?xml encoding="UTF-8">'.$response->body(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($document);
            $main = $xpath->query('//main')->item(0);
            if (! $main instanceof \DOMNode) throw new \RuntimeException('상세 본문을 찾을 수 없습니다.');
            $text = trim(preg_replace('/\\s+/u', ' ', $main->textContent) ?? '');
            if ($text === '') throw new \RuntimeException('상세 본문이 비어 있습니다.');

            $characters = collect();
            foreach ($xpath->query('.//img[@alt]', $main) ?: [] as $image) {
                if (! $image instanceof \DOMElement) continue;
                $character = Character::where('name', trim($image->getAttribute('alt')))->first();
                if ($character) $characters->push($character);
            }

            return ['text' => html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'skills' => $this->detailSkills($xpath, $main, $catalog), 'characters' => $characters->unique('id')->values()];
        } catch (\Throwable $exception) {
            $this->warn("신규 항목 {$catalog}/{$id} 상세 조회 실패: {$exception->getMessage()}");

            return null;
        }
    }

    /** @return array<string,array{id:?int,levels:array<int,string>}> */
    private function detailSkills(\DOMXPath $xpath, \DOMNode $main, string $catalog): array
    {
        $kinds = $catalog === 'cards'
            ? ['leader' => '리더 스킬', 'live' => '라이브 스킬', 'gekisou' => '격주 스킬']
            : ['live' => '라이브 서포트 스킬', 'gekisou' => '격주 서포트 스킬'];
        $result = [];
        foreach ($kinds as $key => $label) {
            $result[$key] = ['id' => null, 'levels' => []];
            $labelNodes = $xpath->query('.//*[normalize-space(.)="'.$label.'"]', $main);
            $labelNode = null;
            foreach ($labelNodes ?: [] as $candidate) {
                if ($candidate->childNodes->length <= 2) { $labelNode = $candidate; break; }
            }
            if (! $labelNode) continue;

            $block = $labelNode;
            for ($depth = 0; $depth < 6 && $block->parentNode instanceof \DOMNode; $depth++) {
                $block = $block->parentNode;
                if (preg_match('/ID\s*#\s*\d+/u', $block->textContent)) break;
            }
            $blockText = trim(preg_replace('/\s+/u', ' ', $block->textContent) ?? '');
            if (preg_match('/ID\s*#\s*(\d+)/u', $blockText, $idMatch)) $result[$key]['id'] = (int) $idMatch[1];
            $description = '';
            $nodes = $xpath->query('.//p|.//li|.//dd', $block);
            foreach ($nodes ?: [] as $node) {
                $value = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
                if ($value === '' || preg_match('/^(?:'.preg_quote($label, '/').'|Lv\.\d+\s*·\s*ID|스킬 레벨|리더 스킬 레벨은)/u', $value)) continue;
                if (preg_match('/(?:UP|연장|회복|증가|변환|완화|발동|격주|스코어|판정)/u', $value) && mb_strlen($value) > mb_strlen($description)) $description = $value;
            }
            if ($description === '') $description = $blockText;
            $result[$key]['levels'][5] = $description;
        }

        return $result;
    }

    /** @param array{id:int,title:string,image:?string,band:?string,composer:?string,levels:list<?float>} $row @return array<string,mixed> */
    private function memberDetailChanges(array $row): array
    {
        $detail = $this->catalogDetail('cards', $row['id']);
        if (! $detail) return [];
        $skills = $detail['skills'];
        $leader = $skills['leader']['levels'][5] ?? null;
        $live = $skills['live']['levels'][5] ?? null;
        $gekisou = $skills['gekisou']['levels'][5] ?? null;
        $changes = [];
        foreach (['leader' => 'leader_skill', 'live' => 'live_skill', 'gekisou' => 'gekisou_skill'] as $kind => $field) {
            if ($skills[$kind]['id'] !== null) $changes[$field.'_id'] = $skills[$kind]['id'];
            if ($skills[$kind]['levels']) $changes[$field.'_levels'] = $skills[$kind]['levels'];
        }
        foreach (['leader_skill' => $leader, 'live_skill' => $live, 'gekisou_skill' => $gekisou] as $field => $value) if ($value !== null && $value !== '') $changes[$field] = $value;
        if (preg_match('/스코어\s*([0-9]+(?:\.[0-9]+)?)\s*%\s*UP/u', (string) $live, $score)) { $changes['skill_type'] = '스코어 UP'; $changes['score_up'] = (int) round((float) $score[1]); }
        return $changes;
    }

    /** @param array{id:int,title:string,image:?string,band:?string,composer:?string,levels:list<?float>} $row @return array<string,mixed> */
    private function supportDetailChanges(array $row): array
    {
        $detail = $this->catalogDetail('support-cards', $row['id']);
        if (! $detail) return [];
        $skills = $detail['skills'];
        $live = $skills['live']['levels'][5] ?? null;
        $gekisou = $skills['gekisou']['levels'][5] ?? null;
        $changes = [];
        if ($skills['live']['id'] !== null) $changes['support_skill_1_id'] = $skills['live']['id'];
        if ($skills['gekisou']['id'] !== null) $changes['gekisou_support_1_id'] = $skills['gekisou']['id'];
        if ($skills['live']['levels']) $changes['support_skill_levels'] = [$skills['live']['levels']];
        if ($skills['gekisou']['levels']) $changes['gekisou_support_levels'] = [$skills['gekisou']['levels']];
        if ($live !== null) $changes['live_support'] = $live;
        if ($gekisou !== null) $changes['gekiso_support'] = $gekisou;
        return $changes;
    }

    private function charactersIn(string $text): \Illuminate\Support\Collection
    {
        $characters = Character::query()->get()->sortByDesc(fn (Character $character): int => mb_strlen($character->name));

        return $characters->filter(function (Character $character) use ($text): bool {
            $aliases = preg_split('/\\s*[\/·]\\s*/u', $character->name) ?: [];
            return collect(array_merge([$character->name], $aliases))->contains(fn (string $alias): bool => mb_strlen(trim($alias)) > 1 && mb_strpos($text, trim($alias)) !== false);
        })->values();
    }

    /** @return array{name:string,id:int}|null */
    private function rarity(string $text): ?array
    {
        $raw = preg_match('/레어도\\s*(SSR|SR|R|생일|스페셜)/u', $text, $match) ? $match[1] : null;
        if (! $raw && preg_match('/\\b(SSR|SR|R)\\b/u', $text, $match)) $raw = $match[1];
        if (! $raw) return null;

        return ['name' => $raw, 'id' => match ($raw) {'SSR', '생일' => 4, 'SR' => 3, 'R' => 2, '스페셜' => 5, default => 4}];
    }

    private function type(string $text): ?string
    {
        if (! preg_match('/속성\\s*(빨강|파랑|노랑|초록|보라|레드|블루|옐로우|그린|퍼플)/u', $text, $match)) return null;

        return ['빨강' => '레드', '파랑' => '블루', '노랑' => '옐로우', '초록' => '그린', '보라' => '퍼플'][$match[1]] ?? $match[1];
    }

    /** @return array{performance:int,technique:int,visual:int}|null */
    private function cardStats(string $text): ?array
    {
        if (! preg_match('/Performance\\s*([0-9,]+).*?Technique\\s*([0-9,]+).*?Visual\\s*([0-9,]+)/iu', $text, $match)) return null;

        return ['performance' => (int) str_replace(',', '', $match[1]), 'technique' => (int) str_replace(',', '', $match[2]), 'visual' => (int) str_replace(',', '', $match[3])];
    }

    private function releaseDate(string $text): ?string
    {
        if (! preg_match('/출시일\\s*(\\d{4})년\\s*(\\d{1,2})월\\s*(\\d{1,2})일/u', $text, $match)) return null;

        return sprintf('%04d-%02d-%02d', (int) $match[1], (int) $match[2], (int) $match[3]);
    }

    private function skillSection(string $text, string $start, string $end): string
    {
        if (! preg_match('/'.preg_quote($start, '/').'\\s*(.+?)\\s*'.preg_quote($end, '/').'/u', $text, $match)) return '';

        return trim($match[1]);
    }

    private function liveSkill(string $text): string
    {
        if (preg_match('/\\[[^\\]]+\\]\\s*\\d+(?:\\.\\d+)?초간.{0,160}?(?:UP|회복|증가)/u', $text, $match)) return trim($match[0]);

        return $this->skillSection($text, '라이브 스킬', '격주 스킬');
    }

    private function skillId(string $text): ?int
    {
        return preg_match('/ID\\s*#(\\d+)/u', $text, $match) ? (int) $match[1] : null;
    }

    private function leaderDescription(string $text): ?string
    {
        if (preg_match('/리더 스킬 레벨은.*?변경하면 각성도 함께 바뀝니다\\.\\s*(.+)$/u', $text, $match)) return trim($match[1]);

        return $text !== '' ? $text : null;
    }

    /** @return list<array{id:int,title:string,image:?string,band:?string,composer:?string,levels:list<?float>}> */
    private function catalog(string $name): array
    {
        $response = Http::timeout(45)->retry(2, 500)->withUserAgent('OurNotesLab/1.0 (fan database)')->get(self::BASE.$name);
        if ($response->failed()) {
            throw new \RuntimeException("{$name} HTTP {$response->status()}");
        }

        $document = new \DOMDocument;
        @$document->loadHTML($response->body(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//a[starts-with(@href, "/ko/'.$name.'/")]');
        if (! $links || $links->length === 0) {
            throw new \RuntimeException("{$name} 도감 항목을 찾을 수 없습니다.");
        }

        $rows = [];
        foreach ($links as $link) {
            if (! $link instanceof \DOMElement || ! preg_match('~^/ko/'.preg_quote($name, '~').'/([0-9]+)$~', $link->getAttribute('href'), $match)) {
                continue;
            }
            $id = (int) $match[1];
            $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $link->textContent) ?: [])));
            $images = $xpath->query('.//img', $link);
            $image = null;
            $band = null;
            foreach ($images ?: [] as $img) {
                if (! $img instanceof \DOMElement) {
                    continue;
                }
                $src = $img->getAttribute('src');
                if (str_contains($src, '/MemberCard/') || str_contains($src, '/SupportCard/') || str_contains($src, '/Image/Jacket/')) {
                    $image = $this->absoluteUrl($src);
                }
                if (in_array($img->getAttribute('alt'), self::BANDS, true)) {
                    $band = $img->getAttribute('alt');
                }
            }

            $titleNode = $this->firstNode($xpath, './/h3', $link);
            $title = $titleNode instanceof \DOMNode ? trim($titleNode->textContent) : ($lines[0] ?? '');
            if (! $titleNode instanceof \DOMNode && in_array($name, ['cards', 'support-cards'], true) && count($lines) >= 3) {
                $title = $lines[1];
            }
            $composerNode = $this->firstNode($xpath, './/p[contains(normalize-space(.), "작곡")]', $link);
            $composerText = $composerNode instanceof \DOMNode ? trim($composerNode->textContent) : '';
            $composer = preg_replace('/^작곡\s*:\s*/u', '', $composerText) ?: null;
            $levels = [];
            foreach (['EZ', 'NM', 'HD', 'EX'] as $difficulty) {
                $levelNode = $this->firstNode($xpath, './/span[normalize-space(.)="'.$difficulty.'"]/following-sibling::span[1]', $link);
                $level = $levelNode instanceof \DOMNode ? trim($levelNode->textContent) : '';
                $levels[] = is_numeric($level) ? (float) $level : null;
            }
            $rows[] = [
                'id' => $id,
                'title' => $title,
                'image' => $image,
                'band' => $band,
                'composer' => $composer,
                'levels' => $levels,
            ];
        }

        return array_values(collect($rows)->unique('id')->all());
    }

    private function absoluteUrl(string $url): string
    {
        return str_starts_with($url, 'http') ? $url : 'https://bdon.moe'.$url;
    }

    private function cardImage(Member $member, ?string $fallback): ?string
    {
        return $this->localImage('members/'.$member->slug.'.webp')
            ?? $this->fullAssetUrl($fallback, 'member_thumbnail/member_thumbnail.webp', 'member_full/member_full.webp');
    }

    private function supportImage(Snapshot $snapshot, ?string $fallback): ?string
    {
        return $this->localImage('snapshots/'.$snapshot->slug.'.webp')
            ?? $this->fullAssetUrl($fallback, 'snap_thumbnail/snap_thumbnail.webp', 'snap_full/snap_full.webp');
    }

    private function localImage(string $relativePath): ?string
    {
        $publicPath = public_path('images/'.$relativePath);

        return is_file($publicPath) ? '/images/'.$relativePath : null;
    }

    private function fullAssetUrl(?string $url, string $thumbnailPath, string $fullPath): ?string
    {
        return $url ? str_replace($thumbnailPath, $fullPath, $url) : null;
    }

    private function firstNode(\DOMXPath $xpath, string $expression, ?\DOMNode $context = null): ?\DOMNode
    {
        $nodes = $xpath->query($expression, $context);

        return $nodes === false ? null : $nodes->item(0);
    }

    /** @return array<string, mixed> */
    private function songDetails(int $id): array
    {
        $response = Http::timeout(30)->retry(2, 300)->withUserAgent('OurNotesLab/1.0 (fan database)')->get(self::BASE.'music/'.$id);
        if ($response->failed()) {
            return [];
        }

        $document = new \DOMDocument;
        @$document->loadHTML($response->body(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $details = [];
        foreach (['작곡' => 'composer', '작사' => 'lyricist', '편곡' => 'arranger'] as $label => $column) {
            $node = $this->firstNode($xpath, '//main//span[normalize-space(.)="'.$label.'"]/following-sibling::span[1]');
            if ($node instanceof \DOMNode && filled(trim($node->textContent))) {
                $details[$column] = trim($node->textContent);
            }
        }

        $bpm = $this->firstNode($xpath, '//main//dt[normalize-space(.)="BPM"]/following-sibling::dd[1]');
        if ($bpm instanceof \DOMNode && preg_match('/[0-9]+(?:\.[0-9]+)?/', $bpm->textContent)) {
            $details['bpm'] = trim($bpm->textContent);
        }

        $expert = $this->firstNode($xpath, '//main//a[contains(@href, "difficulty=expert")]');
        if ($expert instanceof \DOMElement) {
            $chart = $expert->parentNode?->parentNode;
            $note = $chart instanceof \DOMNode
                ? $this->firstNode($xpath, './/div[contains(@class, "font-semibold") and contains(normalize-space(.), "노트:")]', $chart)
                : null;
            if ($note instanceof \DOMNode && preg_match('/노트\s*:\s*([0-9,]+)/u', $note->textContent, $match)) {
                $details['note_count'] = (int) str_replace(',', '', $match[1]);
            }
            $details['chart_url'] = 'https://bdon.moe/ko/tools/chart-preview?music='.$id.'&difficulty=expert';
        }

        return $details;
    }
}
