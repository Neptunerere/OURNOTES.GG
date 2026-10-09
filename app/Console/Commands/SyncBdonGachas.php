<?php

namespace App\Console\Commands;

use App\Services\BdonGachas;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

#[Signature('ournotes:sync-bdon-gachas {--dry-run : Show changes without saving} {--no-images : Deprecated; images are never downloaded}')]
#[Description('BDon 뽑기 목록과 상세 정보를 동기화합니다. 이미지는 원본 URL만 기록합니다.')]
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
        if (! is_array($previous) && DB::getSchemaBuilder()->hasTable('data_sync_states')) {
            $storedPayload = DB::table('data_sync_states')->where('source', 'bdon-gachas')->value('payload');
            $previous = is_string($storedPayload) ? json_decode($storedPayload, true) : null;
        }
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
            // Store the source URL only. BDon artwork is not downloaded or
            // copied into our repository/server filesystem.
            $gacha['banner'] = $gacha['source_banner'] ?? null;
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
        $payload = json_encode($saved->values()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        Storage::disk('local')->put($path, $payload);
        if (DB::getSchemaBuilder()->hasTable('data_sync_states')) {
            DB::table('data_sync_states')->updateOrInsert(['source' => 'bdon-gachas'], ['last_synced_at' => now('UTC'), 'payload' => $payload]);
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
}
