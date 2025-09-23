<?php

/**
 * @author Anibal Gomez <balerocms@gmail.com>
 * @copyright Copyright (c) 2025 Anibal Gomez
 * @license GNU General Public License
 */


namespace Framework\Rendering;


class ProcessorFlattenParams
{

    /**
     * Aplana un array multidimensional para claves como 'errors.username'
     */
    public function process(array $params, string $prefix = ''): array
    {
        $result = [];

        foreach ($params as $key => $value) {
            $fullKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                // Recursividad para aplanar
                $result += $this->process($value, $fullKey);
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }

}