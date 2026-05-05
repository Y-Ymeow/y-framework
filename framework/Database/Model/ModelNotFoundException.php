<?php

declare(strict_types=1);

namespace Framework\Database\Model;

class ModelNotFoundException extends \RuntimeException
{
    private string $model;

    private mixed $id = null;

    public function __construct(string $model, mixed $id = null)
    {
        $this->model = $model;
        $this->id = $id;

        $message = "No query results for model [{$model}]";
        if ($id !== null) {
            $message .= " with id " . (string) $id;
        }

        parent::__construct($message);
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getId(): mixed
    {
        return $this->id;
    }
}