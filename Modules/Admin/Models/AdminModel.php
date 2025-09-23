<?php

/**
 * Balero CMS
 * @author Anibal Gomez <balerocms@gmail.com>
 * @license GNU General Public License
 */

namespace Modules\Admin\Models;

use Exception;
use Framework\Core\ErrorConsole;
use Framework\Core\Model;
use Framework\Static\Utils;
use Throwable;

class AdminModel extends Model
{

    public function getPageById(int $id): ?array
    {
        $id = (int)$id;
        $sql = "SELECT * FROM page WHERE id = {$id} LIMIT 1";

        $this->db->query($sql);
        $this->db->get();

        $rows = $this->db->getRows();

        return $rows[0] ?? null;
    }

    public function updatePage(int $id, array $data): bool
    {
        $sql = "UPDATE page SET 
        virtual_title = ?, 
        static_url = ?, 
        virtual_content = ? 
        WHERE id = ?";

        $params = [
            $data['virtual_title'],
            $data['static_url'],
            $data['virtual_content'],
            $id
        ];

        $this->db->query($sql, $params);

        // Puedes devolver true si no hubo excepción
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


        $this->db->query($sql, $params);

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

    public function getBlocksCount(): int
    {
        $blocks = $this->getBlocks();
        return count($blocks);
    }

    /**
     * Obtener todos los bloques, ordenados por sort_order ascendente.
     */
    public function getBlocks(): array
    {
        try {
            $sql = "SELECT * FROM block ORDER BY sort_order ASC";
            $this->db->query($sql);
            $this->db->get();

            $rows = $this->db->getRows() ?? [];

            // Aseguramos que cada bloque tenga todas las claves
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
            ErrorConsole::handleException(
                new Exception("Error al obtener bloques: " . $e->getMessage(), 0, $e)
            );
            return [];
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
            $this->db->query($sql, [$id]);
            return true;
        } catch (Throwable $e) {
            ErrorConsole::handleException(
                new Exception("Error al eliminar página: " . $e->getMessage(), 0, $e)
            );
            return false;
        }
    }

    /**
     * Obtener un bloque por ID
     */
    public function getBlockById(int $id): array
    {
        try {
            $sql = "SELECT * FROM block WHERE id = ? LIMIT 1";
            $this->db->query($sql, [$id]);
            $this->db->get();
            $row = $this->db->getRow() ?? [];

            // Retornar con valores por defecto si alguna clave falta
            return [
                'id' => $row['id'] ?? 0,
                'name' => $row['name'] ?? '',
                'sort_order' => $row['sort_order'] ?? 1,
                'content' => $row['content'] ?? '',
            ];
        } catch (Throwable $e) {
            ErrorConsole::handleException(
                new Exception("Error al obtener bloque por ID: " . $e->getMessage(), 0, $e)
            );
            return [
                'id' => 0,
                'name' => '',
                'sort_order' => 1,
                'content' => '',
            ];
        }
    }

    /**
     * Crear un nuevo bloque
     */
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
            $this->db->query($sql, $params);
            return true;
        } catch (Throwable $e) {
            ErrorConsole::handleException(
                new Exception("Error al crear bloque: " . $e->getMessage(), 0, $e)
            );
            return false;
        }
    }

    /**
     * Actualizar un bloque existente
     */
    public function updateBlock(int $id, array $data): bool
    {
        try {
            // Convertir a entero asegurando valor válido
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

            $this->db->query($sql, $params);
            return true;
        } catch (Throwable $e) {
            ErrorConsole::handleException(
                new Exception("Error al actualizar bloque: " . $e->getMessage(), 0, $e)
            );
            return false;
        }
    }

    /**
     * Eliminar un bloque por ID
     */
    public function deleteBlock(int $id): bool
    {
        try {
            $sql = "DELETE FROM block WHERE id = ?";
            $this->db->query($sql, [$id]);
            return true;
        } catch (Throwable $e) {
            ErrorConsole::handleException(
                new Exception("Error al eliminar bloque: " . $e->getMessage(), 0, $e)
            );
            return false;
        }
    }

}
