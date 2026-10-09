<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BdonGachas
{
    private const BASE = 'https://bdon.moe/ko/';

    /** @return list<array{id:int,title:string,url:string}> */
    public function list(): array
    {
        $document = $this->document('gacha');
        $xpath = new \DOMXPath($document);
        $gachas = [];
        foreach ($xpath->query('//main//a[contains(@href, "/ko/gacha/")]') ?: [] as $link) {
            if (! $link instanceof \DOMElement || ! preg_match('~/ko/gacha/(\d+)$~', $link->getAttribute('href'), $match)) continue;
            $id = (int) $match[1];
            $title = trim(preg_replace('/^뽑기 보기\s*:\s*/u', '', $link->getAttribute('aria-label')) ?? '');
            if ($title === '') $title = trim(preg_replace('/\s+/u', ' ', $link->textContent) ?? '');
            if ($title !== '') $gachas[$id] = ['id' => $id, 'title' => $title, 'url' => self::BASE.'gacha/'.$id];
        }
        if ($gachas === []) throw new \RuntimeException('BDon 뽑기 목록을 찾지 못했습니다.');

        return array_values($gachas);
    }

    /** @param array{id:int,title:string,url:string} $gacha @return array<string,mixed> */
    public function detail(array $gacha): array
    {
        $document = $this->document('gacha/'.$gacha['id']);
        $xpath = new \DOMXPath($document);
        $main = $xpath->query('//main')->item(0);
        if (! $main instanceof \DOMElement) throw new \RuntimeException('뽑기 #'.$gacha['id'].' 상세 본문을 찾지 못했습니다.');

        $text = html_entity_decode(trim(preg_replace('/\s+/u', ' ', $main->textContent) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $titleNode = $xpath->query('.//h1 | .//h2', $main)->item(0);
        $title = trim($titleNode?->textContent ?? $gacha['title']);
        if ($title === '' || in_array($title, ['모집 상세', '뽑기 상세'], true)) $title = $gacha['title'];

        $record = [
            'source_id' => $gacha['id'], 'source_url' => self::BASE.'gacha/'.$gacha['id'], 'title' => $title,
            'description' => $this->description($xpath, $main, $title), 'timezone' => 'UTC+9',
            'starts_at_raw' => null, 'ends_at_raw' => null, 'limited' => false,
            'image' => null, 'banner' => null, 'image_alt' => $title.' 배너',
            'rates' => $this->rates($xpath, $main), 'pickup_member_ids' => [], 'pickup_support_ids' => [],
            'member_count' => 0, 'support_count' => 0, 'ten_pull_guarantee' => false,
        ];

        if (preg_match('/기간\s*(\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2})\s*[–-]\s*(\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2})/u', $text, $period)) {
            $record['starts_at_raw'] = $this->dateTime($period[1]);
            $record['ends_at_raw'] = $this->dateTime($period[2]);
            $record['limited'] = true;
        }
        if (preg_match('/한국 서버\s+\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2}\s*[–-]\s*\d{4}\.\s*\d{2}\.\s*\d{2}\.\s*\d{2}:\d{2}\s+(UTC[+-]\d+)/u', $text, $server)) {
            $record['timezone'] = $server[1];
        }
        if ($record['limited'] && $record['rates'] === []) {
            throw new \RuntimeException('뽑기 #'.$gacha['id'].'의 등장 확률 형식을 읽지 못했습니다.');
        }

        foreach ($xpath->query('.//img[@src]', $main) ?: [] as $image) {
            if (! $image instanceof \DOMElement) continue;
            $alt = trim($image->getAttribute('alt'));
            if (str_contains($alt, '뽑기 배너')) {
                $record['banner'] = $this->absoluteUrl($image->getAttribute('src'));
                $record['image'] = $record['banner'];
                $record['image_alt'] = $alt;
                break;
            }
        }

        foreach ($this->pickupLinks($xpath, $main) as $pickup) {
            if ($pickup['kind'] === 'member') $record['pickup_member_ids'][] = $pickup['id'];
            else $record['pickup_support_ids'][] = $pickup['id'];
        }
        $record['pickup_member_ids'] = array_values(array_unique($record['pickup_member_ids']));
        $record['pickup_support_ids'] = array_values(array_unique($record['pickup_support_ids']));
        foreach ($record['rates'] as $rate) {
            if ($rate['pickup'] !== null && in_array($rate['category'], ['멤버', '서포트'], true)) {
                $captured = count($rate['category'] === '멤버' ? $record['pickup_member_ids'] : $record['pickup_support_ids']);
                if ($captured < $rate['pickup']) {
                    throw new \RuntimeException(sprintf(
                        '뽑기 #%d의 %s 픽업 카드를 %d장 중 %d장만 읽었습니다.',
                        $gacha['id'],
                        $rate['category'],
                        $rate['pickup'],
                        $captured,
                    ));
                }
            }
            if ($rate['category'] === '멤버') $record['member_count'] += $rate['pool'];
            if ($rate['category'] === '서포트') $record['support_count'] += $rate['pool'];
        }
        $record['ten_pull_guarantee'] = (bool) preg_match('/10회[^.]{0,100}SR 이상[^.]{0,50}확정/u', $text);

        return $record;
    }

    /** @return list<array{rarity:string,category:string,rate:string,pool:int,pickup:int|null}> */
    private function rates(\DOMXPath $xpath, \DOMNode $main): array
    {
        $rates = [];
        foreach ($xpath->query('.//img[@alt="SSR" or @alt="SR" or @alt="R" or @alt="생일" or @alt="아이템"]', $main) ?: [] as $image) {
            if (! $image instanceof \DOMElement) continue;
            $rarity = trim($image->getAttribute('alt'));
            for ($node = $image->parentNode, $depth = 0; $node instanceof \DOMElement && $node !== $main && $depth < 5; $node = $node->parentNode, $depth++) {
                $row = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
                if (! preg_match('/(멤버|서포트|아이템)\s*([0-9]+(?:\.[0-9]+)?)%\s*([0-9,]+)\s*종/u', $row, $match)) continue;
                if ($match[1] === '아이템') $rarity = '아이템';
                $pickup = preg_match('/픽업\s*(\d+)\s*장?/u', $row, $pickupMatch) ? (int) $pickupMatch[1] : null;
                $rates[] = ['rarity' => $rarity, 'category' => $match[1], 'rate' => $match[2].'%', 'pool' => (int) str_replace(',', '', $match[3]), 'pickup' => $pickup];
                break;
            }
        }

        // Some BDon versions render rarity marks as accessible SVGs or split the
        // rate row into text nodes. Recover those rows in document order.
        $lastRarity = null;
        foreach ($xpath->query('.//img[@alt] | .//*[@role="img"] | .//text()[normalize-space()]', $main) ?: [] as $node) {
            if ($node instanceof \DOMElement) {
                $label = trim($node->getAttribute('alt') ?: $node->getAttribute('aria-label'));
                if (in_array($label, ['SSR', 'SR', 'R', '생일', '아이템'], true)) $lastRarity = $label;
                continue;
            }
            if (! $node instanceof \DOMText || ! $lastRarity) continue;
            $row = trim(preg_replace('/\s+/u', ' ', $node->nodeValue ?? '') ?? '');
            if (! preg_match('/(멤버|서포트|아이템)\s*([0-9]+(?:\.[0-9]+)?)%\s*([0-9,]+)\s*종/u', $row, $match)) continue;
            $rarity = $match[1] === '아이템' ? '아이템' : $lastRarity;
            $pickup = preg_match('/픽업\s*(\d+)\s*장?/u', $row, $pickupMatch) ? (int) $pickupMatch[1] : null;
            $rates[] = ['rarity' => $rarity, 'category' => $match[1], 'rate' => $match[2].'%', 'pool' => (int) str_replace(',', '', $match[3]), 'pickup' => $pickup];
        }

        $markup = $main->ownerDocument?->saveHTML($main) ?: '';
        $annotated = preg_replace_callback('/<[^>]+>/u', function (array $tag): string {
            preg_match_all('/(?:alt|aria-label|aria-description|title)=["\']([^"\']*)["\']/u', $tag[0], $labels);
            $accessibleText = collect($labels[1] ?? [])
                ->map(fn (string $label): string => html_entity_decode($label, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                ->filter()
                ->implode(' ');

            return ' '.$accessibleText.' ';
        }, $markup) ?? strip_tags($markup);
        $annotated = html_entity_decode(trim(preg_replace('/\s+/u', ' ', $annotated) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match_all('/(SSR|SR|R|생일|아이템)\s*(멤버|서포트|아이템)\s*([0-9]+(?:\.[0-9]+)?)%\s*([0-9,]+)\s*종(?:.{0,80}?픽업\s*(\d+)\s*장?)?/u', $annotated, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $rates[] = [
                    'rarity' => $match[2] === '아이템' ? '아이템' : $match[1],
                    'category' => $match[2],
                    'rate' => $match[3].'%',
                    'pool' => (int) str_replace(',', '', $match[4]),
                    'pickup' => isset($match[5]) && $match[5] !== '' ? (int) $match[5] : null,
                ];
            }
        }

        return array_values(collect($rates)->unique(fn (array $rate): string => $rate['rarity'].'-'.$rate['category'])->all());
    }

    /** @return list<array{kind:string,id:int}> */
    private function pickupLinks(\DOMXPath $xpath, \DOMNode $main): array
    {
        // Walk the mixed heading/link nodes in document order. DOMNode's
        // compareDocumentPosition flags are easy to invert and previously
        // caused only the first pickup card (or none) to be retained.
        $nodes = $xpath->query('.//*[self::h2 or self::h3 or self::h4 or self::a[contains(@href, "/ko/cards/") or contains(@href, "/ko/support-cards/")]]', $main);
        if (! $nodes) return [];
        $pickups = [];
        $inPickupSection = false;
        foreach ($nodes as $node) {
            if (in_array(strtolower($node->nodeName), ['h2', 'h3', 'h4'], true)) {
                // Card titles are headings nested inside each pickup link. They
                // are part of the pickup section, not the start of a new one.
                $pickupCardHeading = $xpath->query(
                    'ancestor::a[contains(@href, "/ko/cards/") or contains(@href, "/ko/support-cards/")]',
                    $node,
                )->length > 0;
                if ($inPickupSection && $pickupCardHeading) {
                    continue;
                }

                $headingText = trim(preg_replace('/\\s+/u', ' ', $node->textContent) ?? '');
                if (! $inPickupSection) {
                    $inPickupSection = $headingText === '픽업 카드';
                    continue;
                }

                // The first following section heading closes the pickup list.
                break;
            }
            if (! $inPickupSection || ! $node instanceof \DOMElement) continue;
            $href = $node->getAttribute('href');
            if (! preg_match('~/(cards|support-cards)/(\d+)$~', $href, $match)) continue;
            $pickups[] = ['kind' => $match[1] === 'cards' ? 'member' : 'support', 'id' => (int) $match[2]];
        }

        return array_values(collect($pickups)->unique(fn (array $pickup): string => $pickup['kind'].'-'.$pickup['id'])->all());
    }

    private function description(\DOMXPath $xpath, \DOMNode $main, string $title): string
    {
        $heading = null;
        foreach ($xpath->query('.//*[self::h1 or self::h2]', $main) ?: [] as $candidate) {
            if (trim($candidate->textContent) === $title) { $heading = $candidate; break; }
        }
        if ($heading) {
            $container = $heading->parentNode;
            for ($i = 0; $container instanceof \DOMElement && $i < 3; $container = $container->parentNode, $i++) {
                $paragraph = $xpath->query('.//p[normalize-space(.)!=""]', $container)->item(0);
                if ($paragraph) {
                    $text = trim(preg_replace('/\s+/u', ' ', $paragraph->textContent) ?? '');
                    if ($text !== '' && $text !== $title) return $text;
                }
            }
        }

        return 'BDon 모집 상세 정보';
    }

    private function dateTime(string $value): string
    {
        preg_match('/(\d{4})\.\s*(\d{2})\.\s*(\d{2})\.\s*(\d{2}):(\d{2})/u', $value, $parts);

        return sprintf('%04d-%02d-%02d %02d:%02d:00', (int) $parts[1], (int) $parts[2], (int) $parts[3], (int) $parts[4], (int) $parts[5]);
    }

    private function absoluteUrl(string $url): string
    {
        return str_starts_with($url, 'http') ? $url : 'https://bdon.moe'.$url;
    }

    private function document(string $path): \DOMDocument
    {
        $response = Http::connectTimeout(8)->timeout(45)->retry(2, 500)->withUserAgent('OurNotesLab/1.0 (fan database)')->get(self::BASE.$path);
        if ($response->failed()) throw new \RuntimeException('BDon HTTP '.$response->status().': '.$path);

        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$response->body(), LIBXML_NOERROR | LIBXML_NOWARNING);

        return $document;
    }
}
