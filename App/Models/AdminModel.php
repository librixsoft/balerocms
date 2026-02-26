<?php

namespace App\Models;

use Framework\Core\ConfigSettings;
use Framework\Core\Model;
use Framework\Exceptions\ModelException;
use Framework\Utils\Utils;
use Throwable;

class AdminModel
{

    private ConfigSettings $configSettings;
    private Model $model;
    private Utils $utils;


    public function __construct(ConfigSettings $configSettings, Model $model, Utils $utils)
    {
        $this->configSettings = $configSettings;
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
            $this->utils->slugify($data['static_url']), // <-- limpia al guardar
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
            $this->utils->slugify($data['static_url']), // <-- limpia al guardar
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

    public function getBlocksCount(): int
    {
        $blocks = $this->getBlocks();
        return count($blocks);
    }

    public function getBlocks(): array
    {
        try {
            $sql = "SELECT * FROM block ORDER BY sort_order ASC";
            $this->model->getDb()->query($sql);
            $this->model->getDb()->get();

            $rows = $this->model->getDb()->getRows() ?? [];

            foreach ($rows as &$row) {
                $row = [
                    'id' => $row['id'] ?? 0,
                    'name' => $row['name'] ?? '',
                    'sort_order' => $row['sort_order'] ?? 1,
                    'content' => $row['content'] ?? '',
                ];
            }

            return $rows;
        } catch (Throwable $e) {
            throw new ModelException("Error fetching blocks: " . $e->getMessage(), previous: $e);
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

    public function getBlockById(int $id): array
    {
        try {
            $sql = "SELECT * FROM block WHERE id = ? LIMIT 1";
            $this->model->getDb()->query($sql, [$id]);
            $this->model->getDb()->get();
            $row = $this->model->getDb()->getRow() ?? [];

            return [
                'id' => $row['id'] ?? 0,
                'name' => $row['name'] ?? '',
                'sort_order' => $row['sort_order'] ?? 1,
                'content' => $row['content'] ?? '',
            ];
        } catch (Throwable $e) {
            throw new ModelException("Error fetching block by ID: " . $e->getMessage(), previous: $e);
        }
    }

    public function createBlock(array $data): int
    {
        try {
            $sortOrder = isset($data['sort_order']) && is_numeric($data['sort_order'])
                ? (int)$data['sort_order']
                : 1;

            $sql = "INSERT INTO block (name, sort_order, content) VALUES (?, ?, ?)";
            $params = [
                $data['name'] ?? '',
                $sortOrder,
                $data['content'] ?? '',
            ];
            $this->model->getDb()->query($sql, $params);
            return $this->model->getDb()->getInsertId();
        } catch (Throwable $e) {
            throw new ModelException("Error creating block: " . $e->getMessage(), previous: $e);
        }
    }

    public function updateBlock(int $id, array $data): bool
    {
        try {
            $sortOrder = (isset($data['sort_order']) && is_numeric($data['sort_order']))
                ? (int)$data['sort_order']
                : 1;

            $sql = "UPDATE block SET name = ?, sort_order = ?, content = ? WHERE id = ?";
            $params = [
                $data['name'] ?? '',
                $sortOrder,
                $data['content'] ?? '',
                $id
            ];

            $this->model->getDb()->query($sql, $params);
            return true;
        } catch (Throwable $e) {
            throw new ModelException("Error updating block: " . $e->getMessage(), previous: $e);
        }
    }

    public function deleteBlock(int $id): bool
    {
        try {
            $sql = "DELETE FROM block WHERE id = ?";
            $this->model->getDb()->query($sql, [$id]);
            return true;
        } catch (Throwable $e) {
            throw new ModelException("Error deleting block: " . $e->getMessage(), previous: $e);
        }
    }
}
