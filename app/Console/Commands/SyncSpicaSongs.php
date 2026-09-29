<?php

namespace App\Console\Commands;

use App\Models\Song;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

#[Signature('ournotes:sync-songs {--file= : 이미 내려받은 SPICA JSON 파일} {--skip-images : 재킷 다운로드를 건너뜁니다}')]
#[Description('SPICA 한국어 원본에서 전체 곡과 재킷 이미지를 동기화합니다.')]
class SyncSpicaSongs extends Command
{
    private const DATA_URL = 'https://spica.wiki/ournotes/data/d-5b79324a26.json';
    private const BANDS = [1=>'MyGO!!!!!',2=>'Ave Mujica',3=>'무겐다이 뮤타입',4=>'millsage',5=>'일가 Dumb Rock!'];
    private const ATTRS = [1=>'레드',2=>'블루',3=>'그린',4=>'옐로우',5=>'퍼플'];

    public function handle(): int
    {
        if ($file = $this->option('file')) {
            $data = is_file($file) ? json_decode(file_get_contents($file), true) : null;
        } else {
            $response = Http::timeout(60)->retry(2, 500)->withUserAgent('OurNotesLab/1.0')->get(self::DATA_URL);
            $data = $response->successful() ? $response->json() : null;
        }
        if (! is_array($data)) {
            $this->error('SPICA 데이터를 읽을 수 없습니다.');
            return self::FAILURE;
        }

        $directory = public_path('images/songs');
        if (! $this->option('skip-images') && ! is_dir($directory)) mkdir($directory, 0755, true);
        $slugs = [];
        $images = 0;

        foreach ($data['songs'] ?? [] as $source) {
            $slug = $source['slug'];
            $slugs[] = $slug;
            $song = Song::firstOrNew(['slug' => $slug]);
            $filename = $slug.'.webp';
            $bundledImage = $directory.DIRECTORY_SEPARATOR.$filename;
            $imagePath = is_file($bundledImage) ? '/images/songs/'.$filename : $song->image_url;
            if (! $this->option('skip-images') && filled($source['jacket'] ?? null)) {
                $image = Http::timeout(45)->retry(2, 300)->withUserAgent('OurNotesLab/1.0')->get('https://spica.wiki'.$source['jacket']);
                if ($image->successful()) {
                    file_put_contents($directory.DIRECTORY_SEPARATOR.$filename, $image->body());
                    $imagePath = '/images/songs/'.$filename;
                    $images++;
                }
            }
            $levels = $source['levels'] ?? [];
            $expertChart = collect($source['ch'] ?? [])->firstWhere('d', 'expert');
            $song->fill([
                'source_id' => (int) $source['id'],
                'title' => data_get($source, 'title.ko') ?: data_get($source, 'title.en') ?: data_get($source, 'title.ja'),
                'band' => self::BANDS[(int) $source['band']] ?? '-',
                'type' => (int) ($source['cov'] ?? 0) === 1 ? '커버' : '오리지널',
                'attribute' => self::ATTRS[(int) ($source['attr'] ?? 0)] ?? null,
                'easy' => $levels[0] ?? null,
                'normal' => $levels[1] ?? null,
                'hard' => $levels[2] ?? null,
                'expert' => $levels[3] ?? null,
                'gekiso' => filled($source['ch'] ?? []) ? '채보 지원' : null,
                'composer' => $source['cmp'] ?? null,
                'lyricist' => $source['lyr'] ?? null,
                'arranger' => $source['arr'] ?? null,
                'bpm' => implode(' / ', $source['bpm'] ?? []),
                'note_count' => $expertChart['n'] ?? null,
                'chart_url' => filled($expertChart['svg'] ?? null) ? 'https://spica.wiki'.$expertChart['svg'] : null,
                'image_url' => $imagePath,
                'source_url' => 'https://spica.wiki/ournotes/ko/song/'.$slug,
            ])->save();
        }
        Song::whereNotIn('slug', $slugs)->delete();
        $this->info('곡 '.count($slugs)."개, 재킷 {$images}장을 동기화했습니다.");
        return self::SUCCESS;
    }
}
