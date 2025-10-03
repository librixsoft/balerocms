<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Utils;

class Hash
{
    /**
     * Genera un hash de contraseña con salt
     */
    public function genpwd(string $pwd = ""): string
    {
        // Generar salt
        $salt = "";
        $salt_chars = array_merge(range('A', 'Z'), range('a', 'z'), range(0, 9));

        for ($i = 0; $i < 22; $i++) {
            $salt .= $salt_chars[array_rand($salt_chars)];
        }

        return crypt($pwd, sprintf('$2a$%02d$', 7) . $salt);
    }

    /**
     * Verifica que un texto coincida con un hash
     */
    public function verify_hash(string $text, string $hash): bool
    {
        return crypt($text, $hash) === $hash;
    }
}
