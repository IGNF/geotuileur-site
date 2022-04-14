<?php

namespace App;

class Utils
{
    public const HTML_ENTITIES_DICT = [
        '&' => '&amp;', // doit être en premier de la liste
        '<' => '&lt;',
    ];

    /**
     * Générer un UID.
     *
     * @param int $length
     *
     * @return string
     */
    public static function generateUid($length = 16)
    {
        $randomUid = '';

        for ($i = 0; $i < $length; ++$i) {
            if (1 == random_int(1, 2)) {
                // un chiffre entre 0 et 9
                $randomUid .= chr(random_int(48, 57));
            } else {
                // une lettre minuscule entre a et z
                $randomUid .= chr(random_int(97, 122));
            }
        }

        return $randomUid;
    }

    public static function convertCharsToHtmlEntities($string)
    {
        foreach (self::HTML_ENTITIES_DICT as $char => $htmlEntity) {
            $string = str_replace($char, $htmlEntity, $string);
        }

        return $string;
    }

    public static function convertHtmlEntitiesToChars($string)
    {
        $dict = array_reverse(array_flip(self::HTML_ENTITIES_DICT));

        foreach ($dict as $htmlEntity => $char) {
            $string = str_replace($htmlEntity, $char, $string);
        }

        return $string;
    }
}
