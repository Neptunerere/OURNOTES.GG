<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BdonEvents
{
    private const BASE = 'https://bdon.moe/ko/';

    /** @return list<array{id:int,title:string,url:string}> */
    public function list(): array
    {
        $document = $this->document('events/list');
        $xpath = new \DOMXPath($document);
        $links = $xpath->query('//main//a[contains(@href, "/ko/events/")]');
        $events = [];
        foreach ($links ?: [] as $link) {
            if (! $link instanceof \DOMElement || ! preg_match('~/ko/events/(\d+)$~', $link->getAttribute('href'), $match)) {
                continue;
            }
            $titleNode = $xpath->query('.//h1 | .//h2 | .//h3', $link)->item(0);
            $title = trim($titleNode?->textContent ?? '');
            if ($title === '') $title = $this->titleFromListText($link->textContent);
            if ($this->isGenericTitle($title)) $title = $this->titleFromListText($link->textContent);
            $id = (int) $match[1];
            if ($title !== '' && ! isset($events[$id])) {
                $events[$id] = ['id' => $id, 'title' => $title, 'url' => self::BASE.'events/'.$id];
            }
        }
        if ($events === []) {
            throw new \RuntimeException('이벤트 목록 구조를 찾지 못했습니다.');
        }

        return array_values($events);
    }

    private function titleFromListText(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        $text = preg_replace('/^(?:(?:한정|상시)\s*)+/u', '', $text) ?? $text;
        $text = preg_replace('/\s+\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2}.*$/u', '', $text) ?? $text;
        $text = preg_replace('/\s+(?:이벤트 보너스|이벤트 상세|보너스)$/u', '', $text) ?? $text;

        return trim($text);
    }

    private function isGenericTitle(string $title): bool
    {
        return in_array(trim($title), ['이벤트', '이벤트 상세', '상세', '보너스'], true);
    }

    /** @return array<string, mixed> */
    public function detail(array $event): array
    {
        $document = $this->document('events/'.$event['id']);
        $xpath = new \DOMXPath($document);
        $main = $xpath->query('//main')->item(0);
        if (! $main instanceof \DOMElement) {
            throw new \RuntimeException('이벤트 상세 본문을 찾지 못했습니다.');
        }
        $text = trim(preg_replace('/\s+/u', ' ', $main->textContent) ?? '');
        $normalizedText = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (! str_contains($normalizedText, '개최 기간') || ! str_contains($normalizedText, '이벤트 보너스')) {
            throw new \RuntimeException('이벤트 상세 데이터가 예상한 형식이 아닙니다.');
        }
        $titleNode = $xpath->query('.//h1 | .//h2', $main)->item(0);
        if ($titleNode instanceof \DOMNode) {
            $detailTitle = trim($titleNode->textContent);
            if (! $this->isGenericTitle($detailTitle)) {
                $event['title'] = $detailTitle;
            }
        }
        $event['url'] = self::BASE.'events/'.$event['id'];
        $event['description'] = mb_substr($text, 0, 500);
        $event['starts_at'] = preg_match('/개최 기간\s*(\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2})/', $normalizedText, $start) ? $start[1] : null;
        $event['ends_at'] = preg_match('/개최 기간\s*\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2}\s*[–-]\s*(\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2})/', $normalizedText, $end) ? $end[1] : null;
        if ($event['starts_at'] && ! $event['ends_at'] && preg_match('/개최 기간\s*\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2}\s*[–-]\s*(\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2})/', $normalizedText, $shortEnd)) {
            preg_match('/^(\d{4})\./', $event['starts_at'], $year);
            $event['ends_at'] = ($year[1] ?? '').'. '.$shortEnd[1];
        }
        $event['display_ends_at'] = preg_match('/이벤트 화면 공개 종료\s*(\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2})/', $normalizedText, $displayEnd) ? $displayEnd[1] : null;
        $event['images'] = [];
        foreach ($xpath->query('.//img', $main) ?: [] as $image) {
            if (! $image instanceof \DOMElement) {
                continue;
            }
            $src = $image->getAttribute('src');
            if ($src === '' || (! str_contains($src, 'assets.bdon.moe') && ! str_starts_with($src, 'http'))) {
                continue;
            }
            $alt = $image->getAttribute('alt');
            $key = $this->imageKey($src, $alt, $image, $xpath);
            if ($key !== null && ! isset($event['images'][$key])) {
                $event['images'][$key] = ['key' => $key, 'url' => $this->absoluteUrl($src), 'alt' => $alt];
            }
        }
        $event['images'] = array_values($event['images']);
        $event['member_bonuses'] = $this->bonusRows($xpath, $main, '멤버 카드');
        $event['support_bonuses'] = $this->bonusRows($xpath, $main, '서포트 카드');
        $event['event_cards'] = $this->eventCards($xpath, $main, $event['member_bonuses'], $event['support_bonuses']);
        $event['song'] = $this->eventSong($xpath, $main);
        $event['bonuses'] = [];
        $event['point_rewards'] = $this->pointRewards($xpath, $main);
        $event['live_rewards'] = $this->tables($xpath, $main, '라이브 보상');

        return $event;
    }

    private function imageKey(string $src, string $alt, ?\DOMElement $image = null, ?\DOMXPath $xpath = null): ?string
    {
        $path = parse_url($src, PHP_URL_PATH) ?: '';
        if (str_contains($alt, '이벤트 아이템') || str_contains($path, 'item_icon_event_badge')) return 'badge';
        if (str_contains($alt, '배너') || str_contains($path, '/Image/Event/')) return 'banner';
        if (str_contains($path, 'event_logo_')) return 'logo';
        if (str_contains($path, '/Image/Jacket/')) return 'song';
        if (str_contains($path, 'MemberCard/') || str_contains($path, 'SupportCard/')) {
            $href = $image && $xpath ? ($xpath->query('ancestor::a[1]', $image)->item(0)?->attributes?->getNamedItem('href')?->nodeValue ?? '') : '';
            $prefix = str_contains($path, 'MemberCard/') ? 'member-' : 'support-';
            if (preg_match('~/(?:cards|support-cards)/(\d+)$~', $href, $match)) return $prefix.$match[1];
        }

        return null;
    }

    /** @return list<array{name:string,card:string,stats:string,item:string,image:?string,image_key:?string,url:?string}> */
    private function bonusRows(\DOMXPath $xpath, \DOMNode $main, string $sectionTitle): array
    {
        $heading = $xpath->query('.//h2[normalize-space(.)="'.$sectionTitle.'"] | .//h3[normalize-space(.)="'.$sectionTitle.'"] | .//h4[normalize-space(.)="'.$sectionTitle.'"]', $main)->item(0);
        if (! $heading) return [];
        $table = $xpath->query('following::table[1]', $heading)->item(0);
        if (! $table instanceof \DOMElement) return [];

        $rows = [];
        foreach ($xpath->query('.//tr', $table) ?: [] as $row) {
            $cells = $xpath->query('./th | ./td', $row);
            if (! $cells || $cells->length < 3) continue;
            $condition = $cells->item(0);
            $link = $xpath->query('.//a[contains(@href, "/ko/cards/") or contains(@href, "/ko/support-cards/")]', $condition)->item(0);
            $name = trim(preg_replace('/\\s+/u', ' ', $link?->textContent ?? $condition->textContent) ?? '');
            if ($name === '' || $name === '조건') continue;
            $href = $link instanceof \DOMElement ? $link->getAttribute('href') : null;
            $image = $link ? $xpath->query('.//img', $link)->item(0) : $xpath->query('.//img', $condition)->item(0);
            $id = $href && preg_match('~/(\\d+)$~', $href, $match) ? (int) $match[1] : null;
            $isSupport = $href && str_contains($href, '/support-cards/');
            $rows[] = [
                'name' => $name,
                'card' => $link ? ($isSupport ? '서포트 카드' : '멤버 카드') : (str_contains($name, '속성') ? '속성 보너스' : '밴드 보너스'),
                'stats' => trim($cells->item(1)->textContent),
                'item' => trim($cells->item(2)->textContent),
                'image' => $image instanceof \DOMElement ? $this->absoluteUrl($image->getAttribute('src')) : null,
                'image_key' => $id ? (($isSupport ? 'support-' : 'member-').$id) : null,
                'url' => $href ? $this->absoluteUrl($href) : null,
            ];
        }

        return $rows;
    }

    /** @return list<array{name:string,image:?string,image_key:string,url:string}> */
    private function eventCards(\DOMXPath $xpath, \DOMNode $main, array $memberBonuses, array $supportBonuses): array
    {
        $heading = $xpath->query('.//h2[normalize-space(.)="이벤트 카드"] | .//h3[normalize-space(.)="이벤트 카드"] | .//h4[normalize-space(.)="이벤트 카드"]', $main)->item(0);
        if (! $heading) return [];
        $nextHeading = $xpath->query('following::*[self::h2 or self::h3 or self::h4][1]', $heading)->item(0);
        $bonusByImage = collect($memberBonuses)->merge($supportBonuses)->keyBy('image_key');
        $cards = [];
        foreach ($xpath->query('.//a[contains(@href, "/ko/cards/") or contains(@href, "/ko/support-cards/")]', $main) ?: [] as $link) {
            if (! $link instanceof \DOMElement || ($heading->compareDocumentPosition($link) & \DOMNode::DOCUMENT_POSITION_PRECEDING)) continue;
            if ($nextHeading && ($nextHeading->compareDocumentPosition($link) & \DOMNode::DOCUMENT_POSITION_FOLLOWING)) continue;
            $href = $link->getAttribute('href');
            if (! preg_match('~/(cards|support-cards)/(\\d+)$~', $href, $match)) continue;
            $imageKey = ($match[1] === 'support-cards' ? 'support-' : 'member-').$match[2];
            $image = $xpath->query('.//img', $link)->item(0);
            $cards[] = [
                'name' => $bonusByImage->get($imageKey)['name'] ?? trim(preg_replace('/\\s+/u', ' ', $link->textContent) ?? ''),
                'image' => $image instanceof \DOMElement ? $this->absoluteUrl($image->getAttribute('src')) : $bonusByImage->get($imageKey)['image'] ?? null,
                'image_key' => $imageKey,
                'url' => $this->absoluteUrl($href),
            ];
        }

        return collect($cards)->unique('url')->values()->all();
    }

    /** @return list<list<array{0:string,1:string}>> */
    private function pointRewards(\DOMXPath $xpath, \DOMNode $main): array
    {
        $heading = $xpath->query('.//h2[normalize-space(.)="이벤트 포인트 보상"] | .//h3[normalize-space(.)="이벤트 포인트 보상"] | .//h4[normalize-space(.)="이벤트 포인트 보상"]', $main)->item(0);
        if (! $heading) return [];
        $nextHeading = $xpath->query('following::*[self::h2 or self::h3 or self::h4][1]', $heading)->item(0);
        $rows = [];
        foreach ($xpath->query('.//a[@href]', $main) ?: [] as $link) {
            if (! $link instanceof \DOMElement || ($heading->compareDocumentPosition($link) & \DOMNode::DOCUMENT_POSITION_PRECEDING)) continue;
            if ($nextHeading && ($nextHeading->compareDocumentPosition($link) & \DOMNode::DOCUMENT_POSITION_FOLLOWING)) continue;

            for ($container = $link->parentNode; $container instanceof \DOMElement && $container !== $main; $container = $container->parentNode) {
                $text = trim(preg_replace('/\\s+/u', ' ', $container->textContent) ?? '');
                if (preg_match('/^(\\d[\\d,]*)\\s*pt\\s*(.+)$/u', $text, $match)) {
                    $reward = trim(preg_replace('/\\s+/u', ' ', $link->textContent) ?? '');
                    if ($reward !== '') $rows[] = [$match[1], $reward];
                    break;
                }
            }
        }

        return $rows === [] ? [] : [$rows];
    }

    /** @return array{title:string,band:string,image_key:string,url:string}|null */
    private function eventSong(\DOMXPath $xpath, \DOMNode $main): ?array
    {
        $heading = $xpath->query('.//h2[normalize-space(.)="이벤트 곡"] | .//h3[normalize-space(.)="이벤트 곡"] | .//h4[normalize-space(.)="이벤트 곡"]', $main)->item(0);
        if (! $heading) return null;
        $nextHeading = $xpath->query('following::*[self::h2 or self::h3 or self::h4][1]', $heading)->item(0);
        foreach ($xpath->query('.//a[contains(@href, "/ko/music/")]', $main) ?: [] as $link) {
            if (! $link instanceof \DOMElement || ($heading->compareDocumentPosition($link) & \DOMNode::DOCUMENT_POSITION_PRECEDING)) continue;
            if ($nextHeading && ($nextHeading->compareDocumentPosition($link) & \DOMNode::DOCUMENT_POSITION_FOLLOWING)) continue;
            $title = trim(preg_replace('/\\s+/u', ' ', $link->textContent) ?? '');
            if ($title === '') continue;
            $band = '';
            if (preg_match('/^(.*?)\\s+(millsage|MyGO!!!!!|Roselia|Morfonica|Afterglow|Pastel\\*Palettes|ハロー、ハッピーワールド！)$/u', $title, $match)) {
                $title = trim($match[1]);
                $band = $match[2];
            }

            return ['title' => $title, 'band' => $band, 'image_key' => 'song', 'url' => $this->absoluteUrl($link->getAttribute('href'))];
        }

        return null;
    }

    /** @return list<array{url:string,name:string}> */
    private function linkedCards(\DOMXPath $xpath, \DOMNode $main, string $path): array
    {
        $cards = [];
        foreach ($xpath->query('.//a[contains(@href, "'.$path.'")]', $main) ?: [] as $link) {
            if (! $link instanceof \DOMElement || ! preg_match('~'.preg_quote($path, '~').'\d+$~', $link->getAttribute('href'))) {
                continue;
            }
            $image = $xpath->query('.//img', $link)->item(0);
            $cards[] = [
                'url' => $this->absoluteUrl($link->getAttribute('href')),
                'name' => trim(preg_replace('/\s+/u', ' ', $link->textContent) ?? ''),
                'image' => $image instanceof \DOMElement ? $this->absoluteUrl($image->getAttribute('src')) : null,
            ];
        }

        return array_values(array_unique($cards, SORT_REGULAR));
    }

    /** @return list<list<string>> */
    private function tables(\DOMXPath $xpath, \DOMNode $main, string $sectionTitle): array
    {
        $tables = [];
        $found = false;
        foreach ($xpath->query('.//h2 | .//h3 | .//h4 | .//table', $main) ?: [] as $node) {
            if ($node instanceof \DOMElement && $node->tagName !== 'table') {
                $found = $found || trim($node->textContent) === $sectionTitle;
                continue;
            }
            if (! $found || ! $node instanceof \DOMElement) {
                continue;
            }
            $rows = [];
            foreach ($xpath->query('.//tr', $node) ?: [] as $row) {
                $cells = [];
                foreach ($xpath->query('./th | ./td', $row) ?: [] as $cell) {
                    $cells[] = trim(preg_replace('/\s+/u', ' ', $cell->textContent) ?? '');
                }
                if ($cells !== []) $rows[] = $cells;
            }
            if ($rows !== []) $tables[] = $rows;
            $found = false;
        }

        return $tables;
    }

    private function document(string $path): \DOMDocument
    {
        $response = Http::timeout(45)->retry(2, 500)->withUserAgent('OurNotesLab/1.0 (fan database)')->get(self::BASE.$path);
        if ($response->failed()) {
            throw new \RuntimeException("BDon HTTP {$response->status()}: {$path}");
        }
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->body(), LIBXML_NOERROR | LIBXML_NOWARNING);

        return $document;
    }

    private function absoluteUrl(string $url): string
    {
        return Str::startsWith($url, 'http') ? $url : 'https://bdon.moe'.$url;
    }
}
