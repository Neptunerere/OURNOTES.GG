<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\Snapshot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('ournotes:sync-spica {--dry-run : 저장하지 않고 수집 결과만 확인합니다}')]
#[Description('한국어 멤버 정보와 대표 카드 이미지를 로컬 데이터베이스에 동기화합니다.')]
class SyncSpicaData extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $url = 'https://spica.wiki/ournotes/ko/members';
        $this->info('한국어 멤버 목록을 불러오는 중...');

        $response = Http::timeout(30)->withHeaders(['User-Agent' => 'OurNotesLab/1.0 (fan database; source attribution)'])->get($url);
        if ($response->failed()) {
            $this->error("SPICA 응답 오류: {$response->status()}");

            return self::FAILURE;
        }

        $document = new \DOMDocument;
        @$document->loadHTML($response->body(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//a[contains(@href, "/ournotes/ko/member/")]');
        if ($links === false) {
            $this->error('SPICA 멤버 링크를 분석할 수 없습니다.');

            return self::FAILURE;
        }

        $members = Member::with('character')->get();
        $updated = 0;
        $images = 0;

        if (! $this->option('dry-run') && ! is_dir(public_path('images/members'))) {
            mkdir(public_path('images/members'), 0755, true);
        }

        foreach ($links as $link) {
            if (! $link instanceof \DOMElement) {
                continue;
            }

            $label = trim(preg_replace('/\s+/u', ' ', $link->textContent));
            $href = $link->getAttribute('href');
            $sourceUrl = str_starts_with($href, 'http') ? $href : 'https://spica.wiki'.$href;
            $member = $members->first(fn (Member $item) => str_contains($label, $item->name) && str_contains($label, $item->character->name));
            if ($member && ! $this->option('dry-run')) {
                $detail = Http::timeout(30)->withHeaders(['User-Agent' => 'OurNotesLab/1.0'])->get($sourceUrl);
                $imagePath = $member->image_url;

                if ($detail->successful() && preg_match('/<meta[^>]+property="og:image"[^>]+content="([^"]+)"/i', $detail->body(), $match)) {
                    $image = Http::timeout(30)->withHeaders(['User-Agent' => 'OurNotesLab/1.0'])->get(html_entity_decode($match[1]));
                    if ($image->successful()) {
                        $extension = str_contains($image->header('Content-Type'), 'png') ? 'png' : (str_contains($image->header('Content-Type'), 'jpeg') ? 'jpg' : 'webp');
                        $filename = $member->slug.'.'.$extension;
                        file_put_contents(public_path('images/members/'.$filename), $image->body());
                        $imagePath = '/images/members/'.$filename;
                        $images++;
                    }
                }

                $member->update(['source_url' => $sourceUrl, 'image_url' => $imagePath]);
                $updated++;
            }
        }

        $this->table(['수집 링크', '연결된 멤버', '저장 이미지', '모드'], [[count($links), $updated, $images, $this->option('dry-run') ? '미리보기' : '저장']]);

        if (! $this->option('dry-run')) {
            if (! is_dir(public_path('images/snapshots'))) {
                mkdir(public_path('images/snapshots'), 0755, true);
            }
            foreach (Snapshot::all() as $snapshot) {
                $detail = Http::timeout(30)->withHeaders(['User-Agent' => 'OurNotesLab/1.0'])->get($snapshot->source_url);
                if ($detail->successful() && preg_match('/<meta[^>]+property="og:image"[^>]+content="([^"]+)"/i', $detail->body(), $match)) {
                    $image = Http::timeout(30)->get(html_entity_decode($match[1]));
                    if ($image->successful()) {
                        $filename = $snapshot->slug.'.webp';
                        file_put_contents(public_path('images/snapshots/'.$filename), $image->body());
                        $snapshot->update(['image_url' => '/images/snapshots/'.$filename]);
                    }
                }
            }
        }

        return self::SUCCESS;
    }
}
