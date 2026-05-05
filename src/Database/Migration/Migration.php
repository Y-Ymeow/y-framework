<?php

declare(strict_types=1);

namespace Framework\Database\Migration;

use Framework\Database\Connection\Manager;
use Framework\Database\Schema\Schema;

abstract class Migration
{
    protected Manager $manager;
    protected Schema $schema;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
        $this->schema = new Schema($manager);
    }

    abstract public function up(): void;

    abstract public function down(): void;
}