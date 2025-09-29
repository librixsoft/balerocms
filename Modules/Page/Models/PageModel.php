<?php

namespace Modules\Page\Models;

use Framework\Core\Model;
use Framework\Static\Utils;
use Throwable;
use Modules\Page\Exceptions\PageException;

class PageModel extends Model
{
    public function getVirtualPages(): array
    {
        try {
            $sql = "SELECT * FROM page WHERE visible = 1 ORDER BY id ASC";
            $this->db->query($sql);
            $this->db->get();

            $rows = $this->db->getRows() ?? [];

            foreach ($rows as &$row) {
                $slug = Utils::slugify($row['static_url']);
                $row['url'] = "{$slug}";
            }

            return $rows;
        } catch (Throwable $e) {
            throw new PageException("Error fetching virtual pages: " . $e->getMessage(), previous: $e);
        }
    }

    public function getVirtualPageBySlug(string $slug): array
    {
        try {
            $sql = "SELECT * FROM page WHERE static_url = ? AND visible = 1 LIMIT 1";
            $params = [$slug];
            $this->db->query($sql, $params);
            $this->db->get();

            // Debug: log the retrieved rows
            error_log("Rows retrieved in getVirtualPageBySlug for slug '{$slug}': " . print_r($this->db->getRows(), true));

            return $this->db->getRow() ?? [];
        } catch (Throwable $e) {
            throw new PageException("Error fetching virtual page by slug: " . $e->getMessage(), previous: $e);
        }
    }
}
