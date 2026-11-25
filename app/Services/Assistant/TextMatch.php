<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

/**
 * Plain word matching for the assistant's rules: Vietnamese folded to ASCII
 * and lower case, so "Đổi trả" and "doi tra" read the same.
 */
class TextMatch
{
    // Words every shoe name shares; matching on them would find everything.
    private const GENERIC = [
        'giay', 'the', 'thao', 'nam', 'nu', 'shoes', 'shoe', 'sneaker', 'sneakers', 'size', 'mau',
        'cho', 'co', 'khong', 'con', 'va', 'cua', 'nay', 'do', 'dep',
    ];

    // A name counts as named at this weight: one word only this name has, or two shared with others.
    private const NAMED_WEIGHT = 2;

    public const EMAIL_MASK = '[email]';

    public const NUMBER_MASK = '[số]';

    // A Vietnamese phone number, spaced or not: 0 or 84 then nine digits, the
    // same shape IntentRouter::ORDER_CODE refuses to read as an order code.
    // Prices and order codes do not match it.
    private const PHONE = '/(?<![\d.,])(?:\+?84[ .-]?|0)\d(?:[ .-]?\d){8}(?![\d])/';

    private const EMAIL = '/[^\s@,;()<>]+@[^\s@,;()<>]+\.[A-Za-z]{2,}/';

    public static function fold(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }

    /**
     * The customer's text with the phone numbers and emails they typed
     * blanked out, for anything sent outside the shop.
     */
    public static function withoutContacts(string $text): string
    {
        return (string) preg_replace([self::EMAIL, self::PHONE], [self::EMAIL_MASK, self::NUMBER_MASK], $text);
    }

    /**
     * @param  array<int, string>  $phrases  already folded
     */
    public static function mentions(string $folded, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (preg_match('/\b'.preg_quote($phrase, '/').'\b/', $folded)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    public static function words(string $text): array
    {
        $words = preg_split('/[^a-z0-9]+/', self::fold($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $words,
            fn (string $word) => strlen($word) >= 2 && ! in_array($word, self::GENERIC, true),
        )));
    }

    /**
     * The key whose name the question names best. A word found in one name
     * only ("Joyride") names it alone; a word several names share counts
     * half, and so does a Vietnamese word written with tone marks ("Bóng"),
     * since everyday speech uses it too ("bong keo").
     *
     * @param  iterable<int, string>  $names  name keyed by id
     */
    public static function bestNamed(string $question, iterable $names): ?int
    {
        $asked = self::words($question);
        $wordsOf = [];
        $markedOf = [];

        foreach ($names as $id => $name) {
            $wordsOf[$id] = self::words($name);
            $markedOf[$id] = self::marked($name);
        }

        $spread = array_count_values(array_merge(...array_values($wordsOf ?: [[]])));
        $best = null;
        $bestWeight = self::NAMED_WEIGHT - 1;

        foreach ($wordsOf as $id => $words) {
            $weight = 0;

            foreach (array_intersect($words, $asked) as $word) {
                $weight += $spread[$word] === 1 && ! in_array($word, $markedOf[$id], true) ? 2 : 1;
            }

            if ($weight > $bestWeight) {
                $best = $id;
                $bestWeight = $weight;
            }
        }

        return $best;
    }

    /**
     * The folded form of each word that was written with tone marks.
     *
     * @return array<int, string>
     */
    private static function marked(string $text): array
    {
        $marked = [];

        foreach (preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            if (self::fold($word) !== $word) {
                $marked[] = self::fold($word);
            }
        }

        return $marked;
    }
}
