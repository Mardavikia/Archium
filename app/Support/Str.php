<?php
declare(strict_types=1);

namespace Archium\Support;

final class Str
{
    /** Slug ASCII sicuro per URL. Fallback casuale se il risultato e' vuoto. */
    public static function slug(string $value): string
    {
        $t = $value;
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($converted !== false) {
                $t = $converted;
            }
        } else {
            $t = strtr($t, [
                'à'=>'a','è'=>'e','é'=>'e','ì'=>'i','ò'=>'o','ù'=>'u',
                'À'=>'a','È'=>'e','É'=>'e','Ì'=>'i','Ò'=>'o','Ù'=>'u',
            ]);
        }
        $t = strtolower($t);
        $t = preg_replace('/[^a-z0-9]+/', '-', $t) ?? '';
        $t = trim($t, '-');
        return $t !== '' ? substr($t, 0, 180) : 'doc-' . bin2hex(random_bytes(4));
    }
}