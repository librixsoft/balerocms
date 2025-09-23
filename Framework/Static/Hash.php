<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Static;

class Hash
{

    public static function genpwd($pwd = "")
    {

        /**
         *
         * generar salt
         */

        $salt = "";
        $salt_chars = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9));

        for ($i = 0; $i < 22; $i++) {
            $salt .= $salt_chars[array_rand($salt_chars)];
        }

        return crypt($pwd, sprintf('$2a$%02d$', 7) . $salt);

    }

    public static function verify_hash($text, $hash)
    {

        if (crypt($text, $hash) == $hash) {
            return TRUE;
        } else {
            return FALSE;
        }

    }

}