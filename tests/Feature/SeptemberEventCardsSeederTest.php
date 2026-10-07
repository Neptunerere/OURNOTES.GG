<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Snapshot;
use Database\Seeders\SeptemberEventCardsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeptemberEventCardsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_new_event_members_and_snapshots(): void
    {
        $this->seed(SeptemberEventCardsSeeder::class);
        $this->seed(SeptemberEventCardsSeeder::class);

        $this->assertSame(3, Member::whereIn('source_id', [61, 62, 63])->count());
        $this->assertSame(3, Snapshot::whereIn('source_id', [62, 63, 64])->count());

        $this->assertDatabaseHas('members', [
            'source_id' => 61, 'name' => '하트 리부트', 'rarity' => 'SSR',
            'performance' => 15770, 'technique' => 12466, 'visual' => 12893,
            'max_performance' => 46530, 'max_technique' => 36784, 'max_visual' => 38042,
        ]);
        $this->assertDatabaseHas('members', [
            'source_id' => 62, 'name' => '큐트 플로트', 'rarity' => 'SSR',
            'performance' => 12965, 'technique' => 12294, 'visual' => 15870,
            'max_performance' => 38253, 'max_technique' => 36276, 'max_visual' => 46827,
        ]);
        $this->assertDatabaseHas('members', [
            'source_id' => 63, 'name' => 'ナイト・フライト', 'rarity' => 'SR',
            'performance' => 6469, 'technique' => 8171, 'visual' => 6582,
            'max_performance' => 19050, 'max_technique' => 24063, 'max_visual' => 19384,
        ]);
        $this->assertDatabaseHas('snapshots', ['source_id' => 62, 'name' => '푸르게 타오르는 마음', 'rarity' => 'SSR']);
        $this->assertDatabaseHas('snapshots', ['source_id' => 63, 'name' => '매지컬 피지컬 파이팅!', 'rarity' => 'SSR']);
        $this->assertDatabaseHas('snapshots', ['source_id' => 64, 'name' => 'まあるい休息', 'rarity' => 'SR']);

        $this->get(route('members.show', Member::where('source_id', 61)->firstOrFail()))->assertOk()->assertSee('하트 리부트');
        $this->get(route('snapshots.show', Snapshot::where('source_id', 62)->firstOrFail()))->assertOk()->assertSee('푸르게 타오르는 마음');
    }
}
