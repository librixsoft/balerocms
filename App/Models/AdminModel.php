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

    /**
     * Media persistence logic
     */
    public function getMediaByName(string $name): ?array
    {
        try {
            $sql = "SELECT * FROM media WHERE name = ? LIMIT 1";
            $this->model->getDb()->query($sql, [$name]);
            $this->model->getDb()->get();
            $row = $this->model->getDb()->getRow();
            return $row ? $this->normalizeMediaRow($row) : null;
        } catch (Throwable $e) {
            throw new ModelException("Error fetching media by name: " . $e->getMessage(), previous: $e);
        }
    }

    public function insertMedia(array $metadata): void
    {
        try {
            $sql = "INSERT INTO media (name, original_name, extension, mime, size_bytes, width, height, url, uploaded_at, records)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $metadata['name'],
                $metadata['original_name'] ?? $metadata['name'],
                $metadata['extension'],
                $metadata['mime'],
                (string) ($metadata['size_bytes'] ?? 0),
                (string) ($metadata['width'] ?? 0),
                (string) ($metadata['height'] ?? 0),
                $metadata['url'],
                $metadata['uploaded_at'],
                json_encode($metadata['records'] ?? [], JSON_UNESCAPED_UNICODE),
            ];

            $this->model->getDb()->query($sql, $params);
        } catch (Throwable $e) {
            throw new ModelException("Error inserting media: " . $e->getMessage(), previous: $e);
        }
    }

    public function updateMediaRecords(string $name, array $records): void
    {
        try {
            $this->model->getDb()->query(
                "UPDATE media SET records = ? WHERE name = ?",
                [json_encode(array_values($records), JSON_UNESCAPED_UNICODE), $name]
            );
        } catch (Throwable $e) {
            throw new ModelException("Error updating media records: " . $e->getMessage(), previous: $e);
        }
    }

    public function getAllMedia(): array
    {
        try {
            $sql = "SELECT * FROM media ORDER BY uploaded_at DESC";
            $this->model->getDb()->query($sql);
            $this->model->getDb()->get();
            $rows = $this->model->getDb()->getRows() ?? [];
            return array_map(fn($row) => $this->normalizeMediaRow($row), $rows);
        } catch (Throwable $e) {
            throw new ModelException("Error fetching all media: " . $e->getMessage(), previous: $e);
        }
    }

    public function deleteMedia(string $name): void
    {
        try {
            $this->model->getDb()->query("DELETE FROM media WHERE name = ?", [$name]);
        } catch (Throwable $e) {
            throw new ModelException("Error deleting media: " . $e->getMessage(), previous: $e);
        }
    }

    public function removeRecordFromAllMediaRecords(int $id, string $type): void
    {
        try {
            $this->model->getDb()->query("SELECT name, records FROM media");
            $this->model->getDb()->get();
            $rows = $this->model->getDb()->getRows() ?? [];

            foreach ($rows as $row) {
                $records = json_decode($row['records'] ?? '[]', true);
                if (!is_array($records)) {
                    $records = [];
                }

                $filtered = array_values(array_filter(
                    $records,
                    fn($r) => !(($r['id'] ?? null) == $id && ($r['type'] ?? null) === $type)
                ));

                $this->updateMediaRecords((string) $row['name'], $filtered);
            }
        } catch (Throwable $e) {
            throw new ModelException("Error removing record from all media metadata: " . $e->getMessage(), previous: $e);
        }
    }

    private function normalizeMediaRow(array $row): array
    {
        $records = json_decode($row['records'] ?? '[]', true);
        if (!is_array($records)) {
            $records = [];
        }

        $data = [
            'name' => $row['name'] ?? '',
            'original_name' => $row['original_name'] ?? ($row['name'] ?? ''),
            'extension' => $row['extension'] ?? '',
            'mime' => $row['mime'] ?? '',
            'size_bytes' => (int) ($row['size_bytes'] ?? 0),
            'width' => (int) ($row['width'] ?? 0),
            'height' => (int) ($row['height'] ?? 0),
            'url' => $row['url'] ?? '',
            'uploaded_at' => $row['uploaded_at'] ?? '',
            'records' => $records,
        ];

        $data['records_summary'] = empty($data['records'])
            ? 'Not linked'
            : implode(', ', array_map(fn($r) => ($r['type'] ?? '?') . ' #' . ($r['id'] ?? '?'), $data['records']));

        $bytes = (int) $data['size_bytes'];
        $data['size_formatted'] = ($bytes > 1048576)
            ? round($bytes / 1048576, 2) . ' MB'
            : round($bytes / 1024, 2) . ' KB';

        return $data;
    }
}
