<?php

namespace App\Models;

use Framework\Core\Model;
use Framework\Exceptions\ModelException;
use Framework\Utils\Utils;
use Throwable;

class PageModel
{

    private Model $model;
    private Utils $utils;

    public function __construct(Model $model, Utils $utils)
    {
        $this->model = $model;
        $this->utils = $utils;
    }

    public function getVirtualPages(): array
    {
        try {
            $sql = "SELECT * FROM page WHERE visible = 1 ORDER BY sort_order ASC";
            $this->model->getDb()->query($sql);
            $this->model->getDb()->get();

            $rows = $this->model->getDb()->getRows() ?? [];

            foreach ($rows as &$row) {
                $slug = $this->utils->slugify($row['static_url']);
                $row['url'] = "{$slug}";
            }

            return $rows;
        } catch (Throwable $e) {
            throw new ModelException("Error fetching virtual pages: " . $e->getMessage(), previous: $e);
        }
    }

    public function getVirtualPageBySlug(string $slug): array
    {
        try {
            $sql = "SELECT * FROM page WHERE static_url = ? LIMIT 1";
            $params = [$slug];
            $this->model->getDb()->query($sql, $params);
            $this->model->getDb()->get();

            // Debug: log the retrieved rows
            error_log("Rows retrieved in getVirtualPageBySlug for slug '{$slug}': " . print_r($this->model->getDb()->getRows(), true));

            return $this->model->getDb()->getRow() ?? [];
        } catch (Throwable $e) {
            throw new ModelException("Error fetching virtual page by slug: " . $e->getMessage(), previous: $e);
        }
    }
}
