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
        visible = ?
        WHERE id = ?";

        $params = [
            $data['virtual_title'],
            $data['static_url'],
            $data['virtual_content'],
            $data['visible'],
            $id
        ];

        $this->model->getDb()->query($sql, $params);

        return true;
    }

    public function createPage(array $data): bool
    {
        $sql = "INSERT INTO page (virtual_title, static_url, virtual_content, visible, created_at) VALUES (?, ?, ?, ?, ?)";

        $params = [
            $data['virtual_title'],
            $data['static_url'],
            $data['virtual_content'],
            $data['visible'],
            $data['date'],
        ];

        $this->model->getDb()->query($sql, $params);

        return true;
    }

    public function getPagesCount(): int
    {
        $pages = $this->getVirtualPages();
        return count($pages);
    }

    public function getVirtualPages(): array
    {
        try {
            $sql = "SELECT * FROM page ORDER BY id ASC";
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

    public function updateSettings(array $data): void
    {
        $this->configSettings->title = $data['title'] ?? '';
        $this->configSettings->description = $data['description'] ?? '';
        $this->configSettings->keywords = $data['keywords'] ?? '';
        $this->configSettings->theme = $data['theme'] ?? '';
        $this->configSettings->language = $data['language'] ?? '';
        $this->configSettings->footer = $data['footer'] ?? '';
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

    public function createBlock(array $data): bool
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
            return true;
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
