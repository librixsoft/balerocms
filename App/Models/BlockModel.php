<?php

namespace App\Models;

use Framework\Core\Model;
use Framework\Exceptions\ModelException;
use Throwable;

class BlockModel
{

    private Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function getBlocks(): array
    {
        try {
            $sql = "SELECT * FROM block ORDER BY sort_order ASC";
            $this->model->getDb()->query($sql);
            $this->model->getDb()->get();

            return $this->model->getDb()->getRows() ?? [];
        } catch (Throwable $e) {
            throw new ModelException("Error fetching blocks: " . $e->getMessage(), previous: $e);
        }
    }
}
