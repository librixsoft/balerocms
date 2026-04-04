<?php

namespace App\Models\Admin;

use Framework\Core\Model;
use Framework\Exceptions\ModelException;
use Throwable;

class AdminBlocksModel
{
    private Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
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
