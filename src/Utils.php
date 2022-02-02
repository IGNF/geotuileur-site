<?php

namespace App;

class Utils
{
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
}
