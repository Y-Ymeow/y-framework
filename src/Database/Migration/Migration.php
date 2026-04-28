<?php

declare(strict_types=1);

namespace Framework\Database\Migration;

use Framework\Database\Connection;
use Framework\Database\Schema\Schema;

abstract class Migration
{
    protected Connection $connection;
    protected Schema $schema;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->schema = new Schema($connection);
    }

    abstract public function up(): void;

    abstract public function down(): void;
}
