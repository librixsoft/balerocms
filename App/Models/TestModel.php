<?php

namespace App\Models;

use Framework\Core\Model;

class TestModel
{

    private string $hello = "hi";
    private Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function connect() {
        return "success";
    }

    /**
     * @return string
     */
    public function getHello(): string
    {
        return $this->hello;
    }

    /**
     * @param string $hello
     */
    public function setHello(string $hello): void
    {
        $this->hello = $hello;
    }

}
