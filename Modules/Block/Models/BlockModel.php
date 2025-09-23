<?php

/**
 * Balero CMS
 * @author Anibal Gomez
 * @license GNU General Public License
 */

namespace Modules\Block\Models;

use Exception;
use Framework\Core\ErrorConsole;
use Framework\Core\Model;
use Throwable;

class BlockModel extends Model
{
    public function getBlocks(): array
    {
        try {
            $sql = "SELECT * FROM block ORDER BY sort_order ASC";
            $this->db->query($sql);
            $this->db->get();

            return $this->db->getRows() ?? [];
        } catch (Throwable $e) {
            ErrorConsole::handleException(
                new Exception("Error al obtener bloques: " . $e->getMessage(), 0, $e)
            );
            return [];
        }
    }

}
