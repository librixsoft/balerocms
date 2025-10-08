<?php

namespace App\Models;

use Framework\Database\MySQL;
use Framework\Core\ConfigSettings;
use Framework\Exceptions\ControllerException;
use Framework\Utils\Utils;
use Throwable;

class AdminModel
{
    private MySQL $db;
    private ConfigSettings $configSettings;
    private Utils $utils;

    public function __construct(
        MySQL $db,
        ConfigSettings $configSettings,
        Utils $utils
    )
    {
        $this->db = $db;
        $this->configSettings = $configSettings;
        $this->utils = $utils;
        if (!$this->db->isStatus()) {
            $this->db->connect(
                $this->configSettings->dbhost,
                $this->configSettings->dbuser,
                $this->configSettings->dbpass,
                $this->configSettings->dbname
            );
        }
    }

    public function getPageById(int $id): ?array
    {
        $sql = "SELECT * FROM page WHERE id = ? LIMIT 1";
        $this->db->query($sql, [$id]);
        $this->db->get();

        $rows = $this->db->getRows();
        return $rows[0] ?? null;
    }

    public function updatePage(int $id, array $data): bool
    {
        $sql = "UPDATE page SET virtual_title = ?, static_url = ?, virtual_content = ? WHERE id = ?";
        $params = [
            $data['virtual_title'],
            $data['static_url'],
            $data['virtual_content'],
            $id
        ];
        $this->db->query($sql, $params);
        return true;
    }

    public function createPage(array $data): bool
    {
        $sql = "INSERT INTO page (virtual_title, static_url, virtual_content, visible, created_at)
                VALUES (?, ?, ?, ?, ?)";
        $params = [
            $data['virtual_title'],
            $data['static_url'],
            $data['virtual_content'],
            $data['visible'],
            $data['date'],
        ];
        $this->db->query($sql, $params);
        return true;
    }

    public function getVirtualPages(): array
    {
        try {
            $sql = "SELECT * FROM page WHERE visible = 1 ORDER BY id ASC";
            $this->db->query($sql);
            $this->db->get();

            $rows = $this->db->getRows() ?? [];
            foreach ($rows as &$row) {
                $slug = $this->utils->slugify($row['static_url']);
                $row['url'] = "{$slug}";
            }
            return $rows;
        } catch (Throwable $e) {
            throw new ControllerException("Error fetching virtual pages: " . $e->getMessage(), previous: $e);
        }
    }

    public function getBlocks(): array
    {
        try {
            $sql = "SELECT * FROM block ORDER BY sort_order ASC";
            $this->db->query($sql);
            $this->db->get();

            $rows = $this->db->getRows() ?? [];
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
            throw new ControllerException("Error fetching blocks: " . $e->getMessage(), previous: $e);
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
            $this->db->query("DELETE FROM page WHERE id = ?", [$id]);
            return true;
        } catch (Throwable $e) {
            throw new ControllerException("Error deleting page: " . $e->getMessage(), previous: $e);
        }
    }

    public function getBlockById(int $id): array
    {
        try {
            $sql = "SELECT * FROM block WHERE id = ? LIMIT 1";
            $this->db->query($sql, [$id]);
            $this->db->get();
            $row = $this->db->getRow() ?? [];

            return [
                'id' => $row['id'] ?? 0,
                'name' => $row['name'] ?? '',
                'sort_order' => $row['sort_order'] ?? 1,
                'content' => $row['content'] ?? '',
            ];
        } catch (Throwable $e) {
            throw new ControllerException("Error fetching block by ID: " . $e->getMessage(), previous: $e);
        }
    }

    public function createBlock(array $data): bool
    {
        try {
            $sortOrder = is_numeric($data['sort_order'] ?? null)
                ? (int)$data['sort_order']
                : 1;

            $sql = "INSERT INTO block (name, sort_order, content) VALUES (?, ?, ?)";
            $params = [
                $data['name'] ?? '',
                $sortOrder,
                $data['content'] ?? '',
            ];
            $this->db->query($sql, $params);
            return true;
        } catch (Throwable $e) {
            throw new ControllerException("Error creating block: " . $e->getMessage(), previous: $e);
        }
    }

    public function updateBlock(int $id, array $data): bool
    {
        try {
            $sortOrder = is_numeric($data['sort_order'] ?? null)
                ? (int)$data['sort_order']
                : 1;

            $sql = "UPDATE block SET name = ?, sort_order = ?, content = ? WHERE id = ?";
            $params = [
                $data['name'] ?? '',
                $sortOrder,
                $data['content'] ?? '',
                $id
            ];

            $this->db->query($sql, $params);
            return true;
        } catch (Throwable $e) {
            throw new ControllerException("Error updating block: " . $e->getMessage(), previous: $e);
        }
    }

    public function deleteBlock(int $id): bool
    {
        try {
            $this->db->query("DELETE FROM block WHERE id = ?", [$id]);
            return true;
        } catch (Throwable $e) {
            throw new ControllerException("Error deleting block: " . $e->getMessage(), previous: $e);
        }
    }
}
