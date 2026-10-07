<?php

namespace Database\Seeders;

use App\Models\Character;
use App\Models\Member;
use App\Models\Snapshot;
use Illuminate\Database\Seeder;

class SeptemberEventCardsSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureCharacters();
        $this->seedMembers();
        $this->seedSnapshots();
    }

    private function ensureCharacters(): void
    {
        foreach ([
            ['센고쿠 유노', 'yuno-sengoku', 'DJ&Mp.', '#EE5577'],
            ['미야나가 노노카', 'nonoka-miyanaga', 'Gt.', '#FFBBCC'],
            ['미네츠키 리츠', 'ritsu-minetsuki', 'Gt.', '#4477CC'],
        ] as [$name, $slug, $part, $color]) {
            Character::updateOrCreate(['slug' => $slug], compact('name', 'slug', 'part', 'color') + ['band' => '무겐다이 뮤타입']);
        }
    }

    private function seedMembers(): void
    {
        $members = [
            [
                'source_id' => 61, 'character' => '센고쿠 유노', 'name' => '하트 리부트', 'slug' => 'yuno-heart-reboot', 'rarity' => 'SSR', 'rarity_id' => 4,
                'base' => [15770, 12466, 12893], 'max' => [46530, 36784, 38042], 'leader_skill_id' => 53, 'live_skill_id' => 5, 'gekisou_skill_id' => 11, 'skill_type' => '스코어 UP (PERFECT)', 'score_up' => 150,
                'leader' => $this->leaderLevels('블루 멤버의 퍼포먼스 %s, 추가로 무겐다이 뮤타입 멤버의 퍼포먼스 %s', [[72, 18], [82, 18], [92, 18], [102, 18], [102, 48]]),
                'live' => $this->perfectScoreLevels([90, 100, 110, 120, 150]),
                'gekisou' => $this->justQuantityLevels([6, 5, 4, 3, 1], 5),
                'image_url' => '/images/members/yuno-heart-reboot.webp',
            ],
            [
                'source_id' => 62, 'character' => '미야나가 노노카', 'name' => '큐트 플로트', 'slug' => 'nonoka-cute-float', 'rarity' => 'SSR', 'rarity_id' => 4,
                'base' => [12965, 12294, 15870], 'max' => [38253, 36276, 46827], 'leader_skill_id' => 54, 'live_skill_id' => 3, 'gekisou_skill_id' => 3, 'skill_type' => '스코어 UP', 'score_up' => 130,
                'leader' => $this->leaderLevels('무겐다이 뮤타입 멤버의 비주얼 %s, 추가로 격주 스킬 [JUST] 멤버는 비주얼 %s', [[72, 18], [82, 18], [92, 18], [102, 18], [132, 18]]),
                'live' => $this->simpleScoreLevels([70, 80, 90, 100, 130]),
                'gekisou' => $this->fixedLevels('JUST 격주 중<br>JUST 획득량 <b class="hi">3 UP</b>'),
                'image_url' => '/images/members/nonoka-cute-float.webp',
            ],
            [
                'source_id' => 63, 'character' => '미네츠키 리츠', 'name' => 'ナイト・フライト', 'slug' => 'ritsu-night-flight', 'rarity' => 'SR', 'rarity_id' => 3,
                'base' => [6469, 8171, 6582], 'max' => [19050, 24063, 19384], 'leader_skill_id' => 30, 'live_skill_id' => 4, 'gekisou_skill_id' => 10, 'skill_type' => '스코어 UP (PERFECT)', 'score_up' => 120,
                'leader' => $this->singleLeaderLevels('무겐다이 뮤타입 멤버의<br>테크닉', [42, 51, 60, 72, 102]),
                'live' => $this->perfectScoreLevels([60, 70, 80, 90, 120]),
                'gekisou' => $this->justQuantityLevels([6, 5, 4, 3, 1], 4),
                'image_url' => '/images/members/ritsu-night-flight.webp',
            ],
        ];

        foreach ($members as $member) {
            $character = Character::where('name', $member['character'])->firstOrFail();
            Member::updateOrCreate(['source_id' => $member['source_id']], [
                'character_id' => $character->id,
                'name' => $member['name'],
                'slug' => $member['slug'],
                'rarity' => $member['rarity'],
                'rarity_id' => $member['rarity_id'],
                'type' => '블루',
                'performance' => $member['base'][0],
                'technique' => $member['base'][1],
                'visual' => $member['base'][2],
                'max_performance' => $member['max'][0],
                'max_technique' => $member['max'][1],
                'max_visual' => $member['max'][2],
                'skill_type' => $member['skill_type'],
                'score_up' => $member['score_up'],
                'leader_skill_id' => $member['leader_skill_id'],
                'live_skill_id' => $member['live_skill_id'],
                'gekisou_skill_id' => $member['gekisou_skill_id'],
                'leader_skill_levels' => $member['leader'],
                'live_skill_levels' => $member['live'],
                'gekisou_skill_levels' => $member['gekisou'],
                'leader_skill' => $member['leader'][5],
                'live_skill' => $member['live'][5],
                'gekisou_skill' => $member['gekisou'][5],
                'image_url' => $member['image_url'],
                'source_url' => 'https://bdon.moe/ko/cards/'.$member['source_id'],
                'released_at' => '2026-09-30',
            ]);
        }
    }

    private function seedSnapshots(): void
    {
        $snapshots = [
            [
                'source_id' => 62, 'name' => '푸르게 타오르는 마음', 'slug' => 'yuno-blue-burning-heart', 'character_name' => '센고쿠 유노', 'rarity' => 'SSR', 'rarity_id' => 4,
                'stats' => [3200, 3000, 4000], 'support_skill_1_id' => 13, 'gekisou_support_1_id' => 13,
                'support' => [$this->supportDurationLevels()], 'gekisou' => [$this->justScoreLevels()],
                'live_support' => '장착한 멤버의 라이브 스킬 발동 시간 <b class="hi">2.50초</b> 연장<br>「무겐다이 뮤타입」 멤버일 경우, 라이브 스킬 발동 시간 <b class="hi">5.00초</b> 연장',
                'gekiso_support' => 'JUST 격주 중 JUST 판정 1회마다 스코어 <b class="hi">1%씩 UP</b>(최대 <b class="hi">40%</b>)<br>「무겐다이 뮤타입」 멤버일 경우 스코어 <b class="hi">3%씩 UP</b>',
                'diary' => "かっこいいだの、ビッグラブだの\n照れ臭いことをまっすぐに\nキラキラの目で、あいつらは言ってくる\n\nなんだかちょっと懐かしい\nそうだよね\nバンドって、こんな感じだった\n\n音楽を仕事にするのに慣れてきて\n望まれたものは、作れるようになったけど\n今回はなんか、それじゃ嫌かも\n\n胸の奥で鼓動が鳴ってる\n体がいつもより、ほんのちょっとだけ軽い\nひんやりしているはずのこいつも\n今は少し、熱く感じる",
                'image_url' => '/images/snapshots/yuno-blue-burning-heart.webp',
            ],
            [
                'source_id' => 63, 'name' => '매지컬 피지컬 파이팅!', 'slug' => 'arale-magical-physical-fighting', 'character_name' => '나카마치 아라레 · 미야나가 노노카 · 미네츠키 리츠 · 후지 미야코', 'rarity' => 'SSR', 'rarity_id' => 4,
                'stats' => [3900, 3100, 3200], 'support_skill_1_id' => 38, 'gekisou_support_1_id' => 53,
                'support' => [$this->judgementSupportLevels([5, 6, 7, 8, 10])], 'gekisou' => [$this->justTimingLevels([100, 125, 150, 175, 200], 100)],
                'live_support' => '장착한 멤버의 라이브 스킬 발동 중 GREAT를 PERFECT로 변환(<b class="hi">10회</b>까지)<br>「무겐다이 뮤타입」 멤버일 경우, GOOD 시에도 발동',
                'gekiso_support' => 'JUST 격주 중 JUST 판정 영역 <b class="hi">200% UP</b><br>「무겐다이 뮤타입」 멤버일 경우 JUST 판정 영역 <b class="hi">300% UP</b>',
                'diary' => "ユノさんちゃん、こちらをどうぞ！\n\nあっ、これは劇場版にて初めて出てきたオブリークニーレイズバージョンでして、見てくださいここのお腹のあたり！\nちょ〜っとわかりにくいかもなんですけど、マジアームストロングのお腹のところが、ほら！　ムキって！　ムキってなってるんですよ！\nこれは映画の中で強敵・カネスキヤーネンと戦ったときに、マジアームストロングもマジハムスプリングも一度負けちゃうんですけど、そこからこのままじゃダメだ！　って特訓パートに入るんです！\n二人はカネスキヤーネンのパワーの前になすすべもなく〜って感じだったので、じゃあどんな攻撃にも負けない強いボディを手に入れようってことになって！\nそこで編み出したのが、この！　オブリークニーレイズなんですよ！\nこれはお腹の横のところを鍛えるトレーニングなんですけど、そのおかげでもう一度戦った時にはもう全然ムキムキで！\nほんと〜に二人がかっこよくってですね〜！　……え？\n\n長くてよくわかんない？　そ、そんな〜〜〜！",
                'image_url' => '/images/snapshots/arale-magical-physical-fighting.webp',
            ],
            [
                'source_id' => 64, 'name' => 'まあるい休息', 'slug' => 'miyako-ritsu-round-rest', 'character_name' => '후지 미야코 · 미네츠키 리츠', 'rarity' => 'SR', 'rarity_id' => 3,
                'stats' => [1600, 2000, 1500], 'support_skill_1_id' => 33, 'gekisou_support_1_id' => 48,
                'support' => [$this->judgementSupportLevels([3, 4, 5, 6, 8])], 'gekisou' => [$this->justTimingLevels([20, 40, 60, 80, 100], 100)],
                'live_support' => '장착한 멤버의 라이브 스킬 발동 중 GREAT를 PERFECT로 변환(<b class="hi">8회</b>까지)<br>「무겐다이 뮤타입」 멤버일 경우, GOOD 시에도 발동',
                'gekiso_support' => 'JUST 격주 중 JUST 판정 영역 <b class="hi">100% UP</b><br>「무겐다이 뮤타입」 멤버일 경우 JUST 판정 영역 <b class="hi">200% UP</b>',
                'diary' => "ドーナツとは、摩訶不思議である\n\n常に締め切りに追われる日々\n疲れた脳は糖分を欲する\nそれでも右手のペンを手放せない時\n空いた左手にジャストフィット\n\n美しい円形\nその真ん中にぽっかりと空いた穴\n\nまるでそこに吸い込まれるように\n気が付けば私の手には\n美味しい丸が、収まっているのだ",
                'image_url' => '/images/snapshots/miyako-ritsu-round-rest.webp',
            ],
        ];

        foreach ($snapshots as $snapshot) {
            Snapshot::updateOrCreate(['source_id' => $snapshot['source_id']], [
                'name' => $snapshot['name'],
                'slug' => $snapshot['slug'],
                'character_name' => $snapshot['character_name'],
                'band' => '무겐다이 뮤타입',
                'rarity' => $snapshot['rarity'],
                'rarity_id' => $snapshot['rarity_id'],
                'type' => '블루',
                'performance' => $snapshot['stats'][0],
                'technique' => $snapshot['stats'][1],
                'visual' => $snapshot['stats'][2],
                'level_growth' => $snapshot['rarity_id'] === 4 ? 3 : 2,
                'rank_growth' => $snapshot['rarity_id'] === 4 ? 3 : 2,
                'support_skill_1_id' => $snapshot['support_skill_1_id'],
                'support_skill_2_id' => null,
                'gekisou_support_1_id' => $snapshot['gekisou_support_1_id'],
                'gekisou_support_2_id' => null,
                'support_skill_levels' => $snapshot['support'],
                'gekisou_support_levels' => $snapshot['gekisou'],
                'live_support' => $snapshot['live_support'],
                'gekiso_support' => $snapshot['gekiso_support'],
                'diary' => $snapshot['diary'],
                'image_url' => $snapshot['image_url'],
                'source_url' => 'https://bdon.moe/ko/support-cards/'.$snapshot['source_id'],
                'released_at' => '2026-09-30',
            ]);
        }
    }

    /** @param list<array{int, int}> $values */
    private function leaderLevels(string $template, array $values): array
    {
        return collect($values)->mapWithKeys(fn (array $value, int $index) => [$index + 1 => sprintf($template, '<b class="hi">'.$value[0].'.0% UP</b>', '<b class="hi">'.$value[1].'.0% UP</b>')])->all();
    }

    /** @param list<int> $values */
    private function singleLeaderLevels(string $prefix, array $values): array
    {
        return collect($values)->mapWithKeys(fn (int $value, int $index) => [$index + 1 => $prefix.' <b class="hi">'.$value.'.0% UP</b>'])->all();
    }

    /** @param list<int> $values */
    private function perfectScoreLevels(array $values): array
    {
        return collect($values)->mapWithKeys(fn (int $value, int $index) => [$index + 1 => '[판정] 5.0초간 PERFECT 이상의 스코어 <b class="hi">'.$value.'.0%</b> UP'])->all();
    }

    /** @param list<int> $values */
    private function simpleScoreLevels(array $values): array
    {
        return collect($values)->mapWithKeys(fn (int $value, int $index) => [$index + 1 => '[심플] 5.0초간 스코어 <b class="hi">'.$value.'.0% UP</b>'])->all();
    }

    /** @param list<int> $intervals */
    private function justQuantityLevels(array $intervals, int $maximum): array
    {
        return collect($intervals)->mapWithKeys(fn (int $interval, int $index) => [$index + 1 => 'JUST 격주 중 JUST <b class="hi">'.$interval.'</b>마다<br>JUST 획득량 1씩 UP('.$maximum.'회까지)'])->all();
    }

    private function fixedLevels(string $text): array
    {
        return array_fill(1, 5, $text);
    }

    private function supportDurationLevels(): array
    {
        return collect([1.25, 1.50, 1.75, 2.00, 2.50])->mapWithKeys(fn (float $value, int $index) => [$index + 1 => sprintf('장착한 멤버의 라이브 스킬 발동 시간 <b class="hi">%.2f초</b> 연장<br>「무겐다이 뮤타입」 멤버일 경우,<br>라이브 스킬 발동 시간 <b class="hi">%.2f초</b> 연장', $value, $value * 2)])->all();
    }

    private function justScoreLevels(): array
    {
        return collect([15, 20, 25, 30, 40])->mapWithKeys(fn (int $maximum, int $index) => [$index + 1 => 'JUST 격주 중 JUST 판정 1회마다<br>스코어 <b class="hi">1%씩</b> UP(최대 <b class="hi">'.$maximum.'%</b>)<br>「무겐다이 뮤타입」 멤버일 경우<br>스코어 <b class="hi">3%씩</b> UP'])->all();
    }

    /** @param list<int> $counts */
    private function judgementSupportLevels(array $counts): array
    {
        return collect($counts)->mapWithKeys(fn (int $count, int $index) => [$index + 1 => '장착한 멤버의 라이브 스킬 발동 중<br>GREAT를 PERFECT로 변환(<b class="hi">'.$count.'회</b>까지)<br>「무겐다이 뮤타입」 멤버일 경우, GOOD 시에도 발동'])->all();
    }

    /** @param list<int> $values */
    private function justTimingLevels(array $values, int $bonus): array
    {
        return collect($values)->mapWithKeys(fn (int $value, int $index) => [$index + 1 => 'JUST 격주 중<br>JUST 판정 영역 <b class="hi">'.$value.'% UP</b><br>「무겐다이 뮤타입」 멤버일 경우<br>JUST 판정 영역 <b class="hi">'.($value + $bonus).'% UP</b>'])->all();
    }
}
