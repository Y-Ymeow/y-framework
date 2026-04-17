<?php

declare(strict_types=1);

namespace Framework\Database\ORM;

use Framework\Database\Connection;
use Framework\Database\Hydrator;
use Framework\Database\Attributes\Table;
use Framework\Database\Attributes\Column;
use ReflectionClass;

/**
 * 智能映射器：利用 PHP 8 属性自动生成 SQL
 */
final class Mapper
{
    private array $metadata = [];

    public function __construct(
        private readonly Connection $db,
        private readonly Hydrator $hydrator = new Hydrator()
    ) {
    }

    /**
     * 保存实体 (自动判断 Insert 或 Update)
     */
    public function save(object $entity): bool
    {
        $meta = $this->getMetadata($entity::class);
        $data = $this->extract($entity, $meta);
        
        $primaryKey = $meta['primary'];
        $id = $data[$primaryKey] ?? null;

        if ($id && !$meta['columns'][$primaryKey]['autoIncrement']) {
             // 如果不是自增且有ID，可能需要先检查是否存在，这里简化为存在即更新
             return $this->update($meta['table'], $primaryKey, $id, $data);
        } elseif ($id) {
             return $this->update($meta['table'], $primaryKey, $id, $data);
        }

        return $this->insert($meta['table'], $data);
    }

    public function find(string $class, mixed $id): ?object
    {
        $meta = $this->getMetadata($class);
        $table = $meta['table'];
        $pk = $meta['primary'];

        $data = $this->db->first("SELECT * FROM `{$table}` WHERE `{$pk}` = ?", [$id]);
        return $data ? $this->hydrator->hydrate($data, $class) : null;
    }

    private function insert(string $table, array $data): bool
    {
        $columns = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO `{$table}` (`{$columns}`) VALUES ({$placeholders})";
        return $this->db->execute($sql, array_values($data));
    }

    private function update(string $table, string $pk, mixed $id, array $data): bool
    {
        unset($data[$pk]);
        $sets = implode('` = ?, `', array_keys($data)) . '` = ?';
        $sql = "UPDATE `{$table}` SET `{$sets}` WHERE `{$pk}` = ?";
        $params = array_values($data);
        $params[] = $id;
        return $this->db->execute($sql, $params);
    }

    private function getMetadata(string $class): array
    {
        if (isset($this->metadata[$class])) return $this->metadata[$class];

        $reflection = new ReflectionClass($class);
        $tableAttr = $reflection->getAttributes(Table::class)[0] ?? null;
        $tableName = $tableAttr ? $tableAttr->newInstance()->name : strtolower($reflection->getShortName());

        $columns = [];
        $primary = 'id';

        foreach ($reflection->getProperties() as $prop) {
            $colAttr = $prop->getAttributes(Column::class)[0] ?? null;
            if ($colAttr) {
                $instance = $colAttr->newInstance();
                $colName = $instance->name ?? $prop->getName();
                $columns[$prop->getName()] = [
                    'name' => $colName,
                    'isPrimary' => $instance->isPrimary,
                    'autoIncrement' => $instance->autoIncrement
                ];
                if ($instance->isPrimary) $primary = $prop->getName();
            }
        }

        return $this->metadata[$class] = [
            'table' => $tableName,
            'primary' => $primary,
            'columns' => $columns
        ];
    }

    private function extract(object $entity, array $meta): array
    {
        $data = [];
        $reflection = new ReflectionClass($entity);
        foreach ($meta['columns'] as $propName => $info) {
            $prop = $reflection->getProperty($propName);
            $prop->setAccessible(true);
            if ($prop->isInitialized($entity)) {
                $data[$info['name']] = $prop->getValue($entity);
            }
        }
        return $data;
    }
}
