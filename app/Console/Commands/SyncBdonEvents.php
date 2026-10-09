<?php

namespace App\Console\Commands;

use App\Services\BdonEvents;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

#[Signature('ournotes:sync-bdon-events {--dry-run : Fetch and display changes without saving them} {--no-images : Skip downloading event art} {--refresh : Refresh existing event data} {--all : Update existing data and add new events}')]
#[Description('BDon 이벤트 목록, 상세 정보와 이벤트 배너를 가져와 사이트 데이터를 갱신합니다.')]
class SyncBdonEvents extends Command
{
    public function handle(BdonEvents $bdon): int
    {
        try {
            $events = $bdon->list();
            $records = array_map(fn (array $event): array => $bdon->detail($event), $events);
        } catch (\Throwable $exception) {
            $this->warn('BDon 이벤트를 불러오지 못했습니다: '.$exception->getMessage());
            $this->line('저장된 이벤트 파일과 화면 데이터는 그대로 유지했습니다.');

            return self::FAILURE;
        }

        $path = 'bdon/events.json';
        $previous = Storage::disk('local')->exists($path) ? Storage::disk('local')->json($path) : null;
        $previous = is_array($previous) ? $previous : [];
        $existingIds = collect($previous)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $records = array_values(array_filter($records, fn (array $event): bool => $this->option('refresh') || $this->option('all') || ! in_array((int) $event['id'], $existingIds, true)));
        if ($this->option('refresh') || $this->option('all')) {
            $records = array_map(function (array $record) use ($previous): array {
                $old = collect($previous)->firstWhere('id', $record['id']);
                if (! $old) return $record;
                foreach ($record['images'] as &$image) {
                    $oldImage = collect($old['images'] ?? [])->firstWhere('key', $image['key']);
                    if ($oldImage && data_get($oldImage, 'local') !== data_get($oldImage, 'url')) {
                        $image['local'] = $oldImage['local'];
                    }
                }
                unset($image);

                return $record;
            }, $records);
        }
        if ($this->option('refresh')) {
            $oldById = collect($previous)->keyBy('id');
            foreach ($records as &$record) {
                $old = $oldById->get($record['id']);
                if (! $old) continue;
                $record['images'] = collect($record['images'] ?? [])->map(function (array $image) use ($old): array {
                    $previousImage = collect($old['images'] ?? [])->firstWhere('key', $image['key']);
                    if ($previousImage && data_get($previousImage, 'local') !== data_get($previousImage, 'url')) {
                        $image['local'] = $previousImage['local'];
                    }
                    return $image;
                })->all();
            }
            unset($record);
        }
        if ($records === []) {
            $this->info('새 이벤트가 없습니다. 기존 이벤트는 모두 건너뛰었습니다.');

            return self::SUCCESS;
        }
        $this->table(['ID', '이벤트', '상세 정보'], array_map(fn (array $event): array => [$event['id'], $event['title'], $event['url']], $records));
        if ($this->option('dry-run')) {
            $this->comment('미리보기입니다. 파일을 변경하지 않았습니다.');

            return self::SUCCESS;
        }
        $newEvents = $records;
        if (! $this->option('no-images')) {
            foreach ($records as $eventIndex => $event) {
                foreach ($event['images'] as $imageIndex => $image) {
                    $savedImage = data_get(collect($previous)->firstWhere('id', $event['id']), 'images', []);
                    $savedImage = collect($savedImage)->firstWhere('key', $image['key']);
                    $existingLocal = data_get($savedImage, 'local');
                    if ($existingLocal && $existingLocal !== $image['url'] && Storage::disk('public')->exists($this->publicDiskPath($existingLocal))) {
                        $records[$eventIndex]['images'][$imageIndex]['local'] = $existingLocal;
                        continue;
                    }
                    $records[$eventIndex]['images'][$imageIndex]['local'] = $image['url'];
                    try {
                        $response = Http::connectTimeout(5)->timeout(30)->retry(2, 300)->withOptions(['allow_redirects' => true])->get($image['url']);
                        if ($response->successful() && str_starts_with($response->header('Content-Type', ''), 'image/')) {
                            $extension = match (strtolower($response->header('Content-Type', ''))) {
                                'image/png' => 'png',
                                'image/jpeg' => 'jpg',
                                default => 'webp',
                            };
                            $filename = $image['key'];
                            $storagePath = 'events/'.$event['id'].'/'.$filename.'.'.$extension;
                            Storage::disk('public')->put($storagePath, $response->body());
                            Storage::disk('local')->put('events/'.$event['id'].'/'.$filename.'.'.$extension, $response->body());
                            $publicPath = public_path('event-media/'.$event['id'].'/'.$filename.'.'.$extension);
                            if (! is_dir(dirname($publicPath))) {
                                mkdir(dirname($publicPath), 0775, true);
                            }
                            file_put_contents($publicPath, $response->body());
                            $records[$eventIndex]['images'][$imageIndex]['local'] = route('events.asset', ['event' => $event['id'], 'file' => $filename.'.'.$extension], false);
                        }
                    } catch (\Throwable $exception) {
                        $this->warn('이미지 다운로드 실패, 원본 주소를 사용합니다: 이벤트 #'.$event['id'].' '.$image['key'].' ('.$exception->getMessage().')');
                    }
                }
            }
        } else {
            foreach ($records as $eventIndex => $event) {
                foreach ($event['images'] as $imageIndex => $image) {
                    $oldImage = collect(data_get(collect($previous)->firstWhere('id', $event['id']), 'images', []))->firstWhere('key', $image['key']);
                    $oldLocal = data_get($oldImage, 'local');
                    $records[$eventIndex]['images'][$imageIndex]['local'] = is_string($oldLocal) && $oldLocal !== $image['url']
                        ? $oldLocal
                        : $image['url'];
                }
            }
        }

        $saved = collect($previous)->keyBy('id');
        foreach ($records as $record) {
            $saved->put($record['id'], $record);
        }
        Storage::disk('local')->put($path, json_encode($saved->values()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        if (DB::getSchemaBuilder()->hasTable('data_sync_states')) {
            DB::table('data_sync_states')->updateOrInsert(['source' => 'bdon-events'], ['last_synced_at' => now('UTC')]);
        }
        Cache::forget('bdon-events-v2');
        $imageCount = collect($records)->sum(fn (array $event): int => count($event['images'] ?? []));
        $this->info(($this->option('refresh') || $this->option('all') ? '이벤트 ' : '새 이벤트 ').count($newEvents).'건을 '.($this->option('refresh') || $this->option('all') ? '갱신했습니다.' : '추가했습니다.').($this->option('refresh') || $this->option('all') ? '' : ' 기존 이벤트 '.count($existingIds).'건은 건너뛰었습니다.').' 이미지 '.$imageCount.'개를 처리했습니다.');

        return self::SUCCESS;
    }

    private function publicDiskPath(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        return preg_replace('~^/storage/~', '', $path) ?? $path;
    }
}
