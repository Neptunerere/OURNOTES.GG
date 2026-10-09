<?php

namespace App\Console\Commands;

use App\Services\BdonGachas;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

#[Signature('ournotes:sync-bdon-gachas {--dry-run : Show changes without saving} {--no-images : Keep remote banner URLs instead of downloading images}')]
#[Description('BDon 뽑기 목록과 상세 정보를 기존 뽑기 데이터 형식을 유지하며 동기화합니다.')]
class SyncBdonGachas extends Command
{
    public function handle(BdonGachas $bdon): int
    {
        try {
            $list = $bdon->list();
            $fetched = array_map(fn (array $gacha): array => $bdon->detail($gacha), $list);
        } catch (\Throwable $exception) {
            $this->error('BDon 뽑기 정보를 불러오지 못했습니다: '.$exception->getMessage());
            $this->line('저장된 뽑기 데이터는 변경하지 않았습니다.');

            return self::FAILURE;
        }

        $path = 'bdon/gachas.json';
        $previous = Storage::disk('local')->json($path);
        $previous = is_array($previous) ? $previous : [];
        $previousBySource = collect($previous)->keyBy('source_id');
        $limited = array_values(array_filter($fetched, fn (array $gacha): bool => $gacha['limited'] && $gacha['starts_at_raw'] && $gacha['ends_at_raw']));
        if ($limited === []) {
            $this->error('BDon에서 기간 한정 뽑기를 찾지 못했습니다. 기존 파일은 변경하지 않았습니다.');

            return self::FAILURE;
        }

        $created = 0;
        $updated = 0;
        foreach ($limited as &$gacha) {
            $old = $previousBySource->get($gacha['source_id']);
            $gacha['id'] = $old['id'] ?? $this->routeId((int) $gacha['source_id']);
            $gacha['theme'] = $old['theme'] ?? $this->theme((int) $gacha['source_id']);
            $gacha['source_timezone'] = $gacha['timezone'];
            $gacha['source_starts_at_raw'] = $gacha['starts_at_raw'];
            $gacha['source_ends_at_raw'] = $gacha['ends_at_raw'];
            $gacha['starts_at_raw'] = $this->toKoreaTime($gacha['starts_at_raw'], $gacha['source_timezone']);
            $gacha['ends_at_raw'] = $this->toKoreaTime($gacha['ends_at_raw'], $gacha['source_timezone']);
            $gacha['timezone'] = 'UTC+9';
            $gacha['source_banner'] = $gacha['banner'];
            $existingLocal = $old['banner'] ?? null;
            $existingPath = is_string($existingLocal) ? public_path(ltrim($existingLocal, '/')) : '';
            $sameBanner = $existingLocal && ($old['source_banner'] ?? null) === $gacha['source_banner'];
            if ($sameBanner && $existingLocal !== $gacha['source_banner'] && is_file($existingPath)) {
                $gacha['banner'] = $existingLocal;
            } elseif ($this->option('no-images')) {
                $gacha['banner'] = $sameBanner && $existingLocal !== $gacha['source_banner'] ? $existingLocal : ($gacha['source_banner'] ?? null);
            } elseif ($this->option('dry-run')) {
                $gacha['banner'] = $gacha['source_banner'];
            } else {
                $gacha['banner'] = $this->downloadBanner($gacha) ?? ($gacha['source_banner'] ?? null);
            }
            $gacha['image'] = $gacha['banner'];
            $old ? $updated++ : $created++;
        }
        unset($gacha);

        $this->table(['BDon ID', '우리 ID', '뽑기', '기간', '픽업 멤버', '픽업 서포트'], array_map(fn (array $gacha): array => [
            $gacha['source_id'], $gacha['id'], $gacha['title'], $gacha['starts_at_raw'].' ~ '.$gacha['ends_at_raw'],
            implode(',', $gacha['pickup_member_ids']), implode(',', $gacha['pickup_support_ids']),
        ], $limited));

        if ($this->option('dry-run')) {
            $this->comment('미리보기입니다. 파일과 이미지를 변경하지 않았습니다.');

            return self::SUCCESS;
        }

        $saved = $previousBySource;
        foreach ($limited as $gacha) $saved->put($gacha['source_id'], $gacha);
        Storage::disk('local')->put($path, json_encode($saved->values()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        if (DB::getSchemaBuilder()->hasTable('data_sync_states')) {
            DB::table('data_sync_states')->updateOrInsert(['source' => 'bdon-gachas'], ['last_synced_at' => now('UTC')]);
        }

        $this->info("한정 뽑기 {$created}건 추가, {$updated}건 갱신했습니다. 상시 뽑기 ".(count($fetched) - count($limited))."건은 기존 상시 목록 형식을 유지했습니다.");

        return self::SUCCESS;
    }

    private function routeId(int $sourceId): int
    {
        return match ($sourceId) {
            11 => 0,
            1, 2 => $sourceId,
            10 => 3,
            default => 1000 + $sourceId,
        };
    }

    private function theme(int $sourceId): string
    {
        return match ($sourceId) {
            1 => 'from-sky-500/30 via-indigo-500/20 to-[#17191f]',
            2 => 'from-violet-600/35 via-fuchsia-500/15 to-[#17191f]',
            10 => 'from-violet-600/35 via-fuchsia-500/20 to-[#17191f]',
            11 => 'from-cyan-400/30 via-pink-400/20 to-[#17191f]',
            default => 'from-indigo-500/30 via-sky-500/15 to-[#111d33]',
        };
    }

    private function toKoreaTime(string $dateTime, string $timezone): string
    {
        if (! preg_match('/^UTC([+-])(\d{1,2})$/', $timezone, $offset)) return $dateTime;
        $sourceOffset = sprintf('%s%02d:00', $offset[1], (int) $offset[2]);

        return Carbon::parse($dateTime, $sourceOffset)->setTimezone('Asia/Seoul')->format('Y-m-d H:i:s');
    }

    /** @param array<string,mixed> $gacha */
    private function downloadBanner(array $gacha): ?string
    {
        $url = $gacha['source_banner'] ?? null;
        if (! is_string($url) || $url === '') return null;
        try {
            $response = Http::connectTimeout(8)->timeout(35)->retry(2, 400)->get($url);
            $contentType = strtolower($response->header('Content-Type', ''));
            if (! $response->successful() || ! in_array($contentType, ['image/webp', 'image/png', 'image/jpeg', 'image/avif', 'image/gif'], true)) {
                $this->warn('배너를 내려받지 못해 원본 주소를 사용합니다: 뽑기 #'.$gacha['source_id']);
                return null;
            }
            $extension = match ($contentType) {'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/avif' => 'avif', 'image/gif' => 'gif', default => 'webp'};
            $relative = 'gacha-media/'.$gacha['source_id'].'/banner.'.$extension;
            $directory = dirname(public_path($relative));
            if (! is_dir($directory)) mkdir($directory, 0775, true);
            file_put_contents(public_path($relative), $response->body());
            Storage::disk('public')->put($relative, $response->body());
            Storage::disk('local')->put('gachas/'.$gacha['source_id'].'/banner.'.$extension, $response->body());

            return '/'.$relative;
        } catch (\Throwable $exception) {
            $this->warn('배너 다운로드 실패, 원본 주소를 사용합니다: 뽑기 #'.$gacha['source_id'].' ('.$exception->getMessage().')');

            return null;
        }
    }
}
