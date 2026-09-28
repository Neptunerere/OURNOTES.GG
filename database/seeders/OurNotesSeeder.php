<?php

namespace Database\Seeders;

use App\Models\Character;
use App\Models\Member;
use App\Models\Snapshot;
use App\Models\Song;
use Illuminate\Database\Seeder;

class OurNotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $characters = [
            ['타카마츠 토모리', 'MyGO!!!!!', '보컬', '#4a90e2'], ['치하야 아논', 'MyGO!!!!!', '기타', '#f59bb2'], ['카나메 라나', 'MyGO!!!!!', '기타', '#7ac943'], ['나가사키 소요', 'MyGO!!!!!', '베이스', '#f0c95a'], ['시이나 타키', 'MyGO!!!!!', '드럼', '#6f79d8'],
            ['오블리비오니스', 'Ave Mujica', '키보드', '#7058a8'], ['아모리스', 'Ave Mujica', '드럼', '#c84b73'], ['티모리스', 'Ave Mujica', '베이스', '#3f6f9f'], ['모르티스', 'Ave Mujica', '기타', '#4a8f76'], ['돌로리스', 'Ave Mujica', '보컬', '#ad4f65'],
            ['시노미야 시즈쿠', 'Mugendai MewType', '보컬', '#8d68c7'], ['우메자토 치에리', 'Mugendai MewType', '기타', '#e580a9'], ['야쿠라 요모기', 'Mugendai MewType', '베이스', '#d1a333'], ['마하시 미쿠', 'Mugendai MewType', '키보드', '#df695e'], ['스가 라이카', 'Mugendai MewType', '드럼', '#599a65'],
        ];
        foreach ($characters as [$name, $band, $part, $color]) {
            Character::updateOrCreate(['slug' => md5($name)], compact('name', 'band', 'part', 'color'));
        }

        $members = [
            ['낙원에 군림한 여신', '오블리비오니스', '그린', 13900, 13780, 13450, '판정', 90], ['매혹의 빛과 리듬', '아모리스', '블루', 13620, 14010, 13490, '심플', 80], ['짊어진 소망', '티모리스', '퍼플', 13110, 13950, 14040, '라이프', 80], ['손끝이 전하는 선율', '모르티스', '옐로우', 13930, 13620, 13530, '판정', 85], ['찰나에 마음을 울리며', '돌로리스', '레드', 14120, 13470, 13500, '심플', 80],
            ['지키고픈 고동', '시이나 타키', '블루', 14200, 13320, 13540, '심플', 80], ['노을에 새긴 발자취', '나가사키 소요', '레드', 13380, 14210, 13470, '라이프', 80], ['비가 갠 뒤, 울려 퍼지다', '카나메 라나', '그린', 13809, 13710, 13611, '판정', 90], ['RESTART MELODY♪', '치하야 아논', '퍼플', 13510, 13930, 13630, '심플', 80], ['유성을 향한 외침', '타카마츠 토모리', '옐로우', 16198, 12402, 12529, '라이프', 80],
            ['미지의 세상을 향해', '시노미야 시즈쿠', '퍼플', 13810, 13520, 13720, '심플', 75], ['환호성의 한가운데!!', '우메자토 치에리', '블루', 14020, 13410, 13610, '판정', 80], ['작은 용기를 연주하다', '야쿠라 요모기', '옐로우', 13420, 14030, 13580, '라이프', 75], ['숨길 수 없는 고조된 마음', '마하시 미쿠', '레드', 13610, 13520, 13930, '심플', 75], ['가 볼까, 마이 패밀리!!', '스가 라이카', '그린', 13720, 13680, 13650, '판정', 80],
        ];
        foreach ($members as [$name, $characterName, $type, $performance, $technique, $visual, $skillType, $scoreUp]) {
            $character = Character::where('name', $characterName)->firstOrFail();
            Member::updateOrCreate(['slug' => md5($characterName.$name)], [
                'character_id' => $character->id, 'name' => $name, 'rarity' => 'SSR', 'type' => $type, 'performance' => $performance, 'technique' => $technique, 'visual' => $visual,
                'skill_type' => $skillType, 'score_up' => $scoreUp, 'leader_skill' => $character->band.' 멤버의 모든 파라미터 UP', 'live_skill' => "[{$skillType}] 5초간 스코어 {$scoreUp}% UP",
                'released_at' => '2026-09-24', 'source_url' => 'https://spica.wiki/ournotes/ko/members',
            ]);
        }

        $songs = [
            ['焚音打', 'MyGO!!!!!', 10, 15, 23, 29, 'JUST'], ['往欄印', 'Ave Mujica', 9, 14, 23, 28, 'LUCK'], ['KiLLKiSS', 'Ave Mujica', 8, 13, 21, 28, 'JUST'], ['기사개전', 'Ave Mujica', 9, 14, 21, 28, '3종'], ['everscape', 'MyGO!!!!!', 8, 13, 20, 28, 'COMBO'], ['한 방울', 'MyGO!!!!!', 7, 14, 21, 27, 'JUST'], ['Ave Mujica', 'Ave Mujica', 8, 13, 21, 27, 'COMBO'], ['멜로디', 'MyGO!!!!!', 7, 12, 17, 27, 'LUCK'], ['증명찬가', 'MyGO!!!!!', 8, 13, 22, 26, '3종'], ['MUGEN MY WORLD', 'Mugendai MewType', 8, 14, 19, 26, 'JUST'], ['청춘 콤플렉스', 'MyGO!!!!!', 8, 13, 20, 25, 'JUST'], ['UNDEAD', 'Mugendai MewType', 8, 14, 20, 24, '3종'],
        ];
        foreach ($songs as [$title, $band, $easy, $normal, $hard, $expert, $gekiso]) {
            Song::updateOrCreate(['slug' => md5($title)], compact('title', 'band', 'easy', 'normal', 'hard', 'expert', 'gekiso') + ['type' => '오리지널', 'source_url' => 'https://spica.wiki/ournotes/ko/songs']);
        }

        $snapshots = [
            ['선잠 속에서, 우리', '돌로리스·오블리비오니스', 'Ave Mujica', '퍼플', 12.60, 15.86, 13.01, 'doloris-in-our-reverie'],
            ['함께, 앞으로', '타카마츠 토모리·치하야 아논', 'MyGO!!!!!', '블루', 14.20, 13.40, 13.85, 'tomori-hand-in-hand'],
            ['헤매면서도, 평생', '타카마츠 토모리 외 4명', 'MyGO!!!!!', '레드', 15.10, 12.90, 13.40, 'tomori-lost-for-a-lifetime'],
            ['3학년', '카나메 라나', 'MyGO!!!!!', '퍼플', 13.25, 14.90, 13.10, 'rana-third-year'],
            ['GREAT IDEA!!!!!', '치하야 아논', 'MyGO!!!!!', '옐로우', 12.95, 13.30, 15.05, 'anon-great-idea'],
            ['좋아하는 펭귄들', '타카마츠 토모리', 'MyGO!!!!!', '그린', 14.60, 12.85, 13.75, 'tomori-my-favorite-penguins'],
            ['카르페 디엠의 향연', '돌로리스 외 4명', 'Ave Mujica', '그린', 13.70, 14.10, 13.55, 'doloris-the-feast-of-carpe-diem'],
            ['비공개 푸념', '아모리스', 'Ave Mujica', '퍼플', 12.80, 15.15, 13.35, 'amoris-a-private-moment-of-weakness'],
            ['충동구매', '티모리스', 'Ave Mujica', '블루', 13.40, 13.20, 14.75, 'timoris-a-case-of-stress-shopping'],
            ['눈을 감으며', '모르티스', 'Ave Mujica', '레드', 14.80, 13.30, 13.25, 'mortis-lowering-my-eyelids'],
        ];
        foreach ($snapshots as [$name, $characterName, $band, $type, $performance, $technique, $visual, $sourceSlug]) {
            Snapshot::updateOrCreate(['slug' => $sourceSlug], compact('name', 'band', 'type', 'performance', 'technique', 'visual') + [
                'character_name' => $characterName, 'rarity' => 'SSR', 'live_support' => '장착한 멤버의 라이브 스킬 효과를 강화', 'gekiso_support' => '격주 라이브 판정 및 획득량 보조', 'source_url' => 'https://spica.wiki/ournotes/ko/snap/'.$sourceSlug,
            ]);
        }
    }
}
