<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BdonEventsSyncTest extends TestCase
{
    public function test_it_discovers_and_stores_bdon_events_and_dry_run_does_not_write(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Http::fake([
            'bdon.moe/ko/events/list' => Http::response('<header><a href="/ko/events/9">이벤트</a></header><main><a href="/ko/events/2">한정 다음 이벤트 2026. 11. 01. 18:00 – 2026. 11. 09. 20:59 이벤트 보너스</a><a href="/ko/events/2">중복 제목 2026. 11. 01. 18:00 – 2026. 11. 09. 20:59 이벤트 보너스</a></main>'),
            'bdon.moe/ko/events/2' => Http::response('<header><h1>이벤트 상세</h1></header><main><h1>이벤트 상세</h1><p>개최 기간 2026. 11. 01. 18:00 – 2026. 11. 09. 20:59</p><p>이벤트 화면 공개 종료 2026. 11. 11. 20:59</p><h2>이벤트 보너스</h2><img alt="다음 이벤트 배너" src="https://assets.bdon.moe/banner.webp"><img alt="이벤트 아이템" src="https://assets.bdon.moe/badge.webp"><img alt="exp" src="https://assets.bdon.moe/item_icon_exp_005.webp"><a href="/ko/cards/17"><img alt="card" src="https://assets.bdon.moe/MemberCard/17/member_thumbnail/member_thumbnail.webp">새 멤버 카드</a><img alt="이벤트 배너" src="https://assets.bdon.moe/another-event-banner.webp"></main>'),
            'assets.bdon.moe/*' => Http::response('image-content', 200, ['Content-Type' => 'image/webp']),
        ]);

        $this->artisan('ournotes:sync-bdon-events --dry-run')->assertSuccessful();
        Storage::disk('local')->assertMissing('bdon/events.json');

        $this->artisan('ournotes:sync-bdon-events')->assertSuccessful();
        $records = Storage::disk('local')->json('bdon/events.json');
        $this->assertSame(2, $records[0]['id']);
        $this->assertSame('다음 이벤트', $records[0]['title']);
        $this->assertSame('2026. 11. 01. 18:00', $records[0]['starts_at']);
        $this->assertSame('2026. 11. 11. 20:59', $records[0]['display_ends_at']);
        $this->assertSame('banner', $records[0]['images'][0]['key']);
        $this->assertCount(3, $records[0]['images']);
        Storage::disk('public')->assertExists('images/events/2/banner.webp');
        Storage::disk('public')->assertExists('images/events/2/badge.webp');

        $this->artisan('ournotes:sync-bdon-events')->expectsOutputToContain('새 이벤트가 없습니다')->assertSuccessful();
        $this->assertCount(1, Storage::disk('local')->json('bdon/events.json'));
    }

    public function test_it_skips_ids_already_saved_locally_without_overwriting_them(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('bdon/events.json', json_encode([['id' => 2, 'title' => '기존 이벤트', 'keep' => true]]));
        Http::fake([
            'bdon.moe/ko/events/list' => Http::response('<main><a href="/ko/events/2">새 제목 2026. 11. 01. 18:00 이벤트 보너스</a><a href="/ko/events/3">신규 이벤트 2026. 12. 01. 18:00 이벤트 보너스</a></main>'),
            'bdon.moe/ko/events/3' => Http::response('<main><h1>이벤트 상세</h1><p>개최 기간 2026. 12. 01. 18:00 – 2026. 12. 09. 20:59</p><h2>이벤트 보너스</h2></main>'),
        ]);

        $this->artisan('ournotes:sync-bdon-events --no-images')->assertSuccessful();
        $records = Storage::disk('local')->json('bdon/events.json');
        $this->assertSame([2, 3], array_column($records, 'id'));
        $this->assertSame('기존 이벤트', $records[0]['title']);
        $this->assertTrue($records[0]['keep']);
    }

    public function test_it_keeps_existing_data_when_remote_markup_is_unrecognized(): void
    {
        Http::fake([
            'bdon.moe/ko/events/list' => Http::response('<main><p>unexpected</p></main>'),
        ]);

        $this->artisan('ournotes:sync-bdon-events')->assertFailed();
    }
}
