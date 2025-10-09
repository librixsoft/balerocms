<?php

namespace App\Models;

use Framework\Core\Model;

class NotificationModel
{

    private Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function connect() {
        return "success";
    }

}
