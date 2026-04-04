<?php

namespace App\Models\Admin;

use Framework\Core\Model;
use Framework\Exceptions\ModelException;
use Framework\Utils\Utils;
use Throwable;

class AdminPagesModel
{
    private Model $model;
    private Utils $utils;

    public function __construct(Model $model, Utils $utils)
    {
        $this->model = $model;
        $this->utils = $utils;
    }

    public function getPageById(int $id): ?array
    {
        $id = (int)$id;
        $sql = "SELECT * FROM page WHERE id = {$id} LIMIT 1";

        $this->model->getDb()->query($sql);
        $this->model->getDb()->get();

        $rows = $this->model->getDb()->getRows();

        return $rows[0] ?? null;
    }

    public function updatePage(int $id, array $data): bool
    {
        $sql = "UPDATE page SET 
        virtual_title = ?, 
        static_url = ?, 
        virtual_content = ?,
        visible = ?,
        sort_order = ?
        WHERE id = ?";

        $params = [
            $data['virtual_title'],
            $this->utils->slugify($data['static_url']),
            $data['virtual_content'],
            $data['visible'],
            $data['sort_order'] ?? 0,
            $id
        ];

        $this->model->getDb()->query($sql, $params);
        return true;
    }

    public function createPage(array $data): int
    {
        $sql = "INSERT INTO page (virtual_title, static_url, virtual_content, visible, created_at, sort_order) VALUES (?, ?, ?, ?, ?, ?)";

        $params = [
            $data['virtual_title'],
            $this->utils->slugify($data['static_url']),
            $data['virtual_content'],
            $data['visible'],
            $data['date'],
            $data['sort_order'] ?? 0,
        ];

        $this->model->getDb()->query($sql, $params);
        return $this->model->getDb()->getInsertId();
    }

    public function getPagesCount(): int
    {
        $pages = $this->getVirtualPages();
        return count($pages);
    }

    public function getVirtualPages(): array
    {
        try {
            $sql = "SELECT * FROM page ORDER BY sort_order ASC";
            $this->model->getDb()->query($sql);
            $this->model->getDb()->get();

            $rows = $this->model->getDb()->getRows() ?? [];

            return $rows;
        } catch (Throwable $e) {
            throw new ModelException("Error fetching virtual pages: " . $e->getMessage(), previous: $e);
        }
    }

    public function deletePage(int $id): bool
    {
        try {
            $sql = "DELETE FROM page WHERE id = ?";
            $this->model->getDb()->query($sql, [$id]);
            return true;
        } catch (Throwable $e) {
            throw new ModelException("Error deleting page: " . $e->getMessage(), previous: $e);
        }
    }
}
