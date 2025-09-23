<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Framework\Http;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Get
{
    public string $target;

    public function __construct(string $target = '')
    {
        $this->target = $target;
    }
}
