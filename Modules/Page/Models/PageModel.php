<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Modules\Page\Models;

use Exception;
use Framework\Core\ErrorConsole;
use Framework\Core\Model;
use Framework\Static\Utils;
use Throwable;

class PageModel extends Model
{

    public function getVirtualPages(): array
    {
        try {

            $sql = "SELECT * FROM page WHERE visible = 1 ORDER BY id ASC";
            $this->db->query($sql);
            $this->db->get();

            $rows = $this->db->getRows() ?? [];

            // Generar URL estática para cada página virtual
            foreach ($rows as &$row) {

                $slug = Utils::slugify($row['static_url']);
                $row['url'] = "{$slug}";
            }

            return $rows;
        } catch (Throwable $e) {
            ErrorConsole::handleException(
                new Exception("Error al obtener páginas virtuales: " . $e->getMessage(), 0, $e)
            );
            return [];
        }
    }

    public function getVirtualPageBySlug(string $slug): array
    {
        try {
            $sql = "SELECT * FROM page WHERE static_url = ? AND visible = 1 LIMIT 1";
            $params = [$slug];
            $this->db->query($sql, $params);
            $this->db->get();

            // Debug: loguea las filas obtenidas
            error_log("Rows obtenidas en getVirtualPageBySlug para slug '{$slug}': " . print_r($this->db->getRows(), true));

            return $this->db->getRow() ?? [];

        } catch (Throwable $e) {
            ErrorConsole::handleException(
                new Exception("Error al obtener página virtual por slug: " . $e->getMessage(), 0, $e)
            );
            return [];
        }
    }

}
