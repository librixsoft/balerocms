<?php

namespace App\Models\Admin;

use Framework\Core\Model;
use Framework\Exceptions\ModelException;
use Throwable;

class AdminMediaModel
{
    private Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

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
