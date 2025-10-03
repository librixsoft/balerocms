<?php

namespace Modules\Block\Models;

use Framework\Core\Model;
use Throwable;
use Modules\Block\Exceptions\BlockException;

class BlockModel extends Model
{
    public function getBlocks(): array
    {
        try {
            $sql = "SELECT * FROM block ORDER BY sort_order ASC";
            $this->db->query($sql);
            $this->db->get();

            return $this->db->getRows() ?? [];
        } catch (Throwable $e) {
            throw new BlockException("Error fetching blocks: " . $e->getMessage(), previous: $e);
        }
    }
}
