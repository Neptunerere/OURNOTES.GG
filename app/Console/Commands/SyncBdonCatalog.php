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
        $supportUpdates = 0;
        foreach ($cards as $row) {
            $record = Member::where('source_id', $row['id'])->first();
            if ($record) {
                $changes = ['name' => $row['title'], 'image_url' => $this->cardImage($record, $row['image']), 'source_url' => self::BASE.'cards/'.$row['id']];
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
            $cardUpdates++;
        }

        foreach ($supports as $row) {
            $record = Snapshot::where('source_id', $row['id'])->first();
            if ($record) {
                $changes = ['name' => $row['title'], 'image_url' => $this->supportImage($record, $row['image']), 'source_url' => self::BASE.'support-cards/'.$row['id']];
                if ($row['id'] === 70) {
                    $changes = array_merge($changes, ['rarity' => '스페셜', 'rarity_id' => 5, 'type' => '레드', 'image_url' => $this->localImage('snapshots/ave-mujica-abracadabra.webp') ?? 'https://assets.bdon.moe/kr/zh-Hans/SupportCard/70/snap_full/snap_full.webp']);
                }
                $record->update($changes);
                $supportUpdates++;
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
            $supportUpdates++;
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

        $this->table(['BDon 목록', '건수', '기존 레코드 갱신'], [
            ['멤버 카드', count($cards), $cardUpdates],
            ['서포트 카드', count($supports), $supportUpdates],
            ['곡', count($music), $musicUpdates],
        ]);

        return self::SUCCESS;
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
