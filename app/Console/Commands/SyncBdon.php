<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ournotes:sync-bdon-all {--no-images : Keep remote image URLs instead of downloading event and gacha images}')]
#[Description('BDon 이벤트, 뽑기, 멤버 카드, 서포트 카드, 곡 정보를 차례로 모두 동기화합니다.')]
class SyncBdon extends Command
{
    public function handle(): int
    {
        $commands = [
            ['ournotes:sync-bdon', []],
            ['ournotes:sync-bdon-events', $this->option('no-images') ? ['--no-images' => true] : []],
            ['ournotes:sync-bdon-gachas', $this->option('no-images') ? ['--no-images' => true] : []],
        ];
        $failed = [];

        foreach ($commands as [$command, $options]) {
            $this->newLine();
            $this->info("실행: php artisan {$command}");
            $exitCode = $this->call($command, $options);
            if ($exitCode !== self::SUCCESS) {
                $failed[] = $command;
                $this->error("실패: {$command}");
            }
        }

        if ($failed !== []) {
            $this->newLine();
            $this->error('일부 동기화가 실패했습니다: '.implode(', ', $failed));

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('BDon 전체 동기화를 완료했습니다.');

        return self::SUCCESS;
    }
}
