<?php

namespace App\Core;

use PDO;
use PDOStatement;
use App\Core\DbConnection;

abstract class AbstractManager
{
    private function getTableName(string $class): string
    {
        if (defined($class . '::TABLE_NAME')) {
            $table = $class::TABLE_NAME;
        } else {
            $tmp = explode('\\', $class);
            $table = strtolower(end($tmp));
        }
        return $table;
    }

    private function executeQuery(string $query, array $params = []): PDOStatement
    {
        $db = DbConnection::getInstance();
        $stmt = $db->prepare($query);
        foreach ($params as $key => $param) {
            $stmt->bindValue($key, $param);
        }
        $stmt->execute();
        return $stmt;
    }

    protected function readOne(string $class, array $filters): mixed
    {
        $query = 'SELECT * FROM ' . $this->getTableName($class) . ' WHERE ';
        foreach (array_keys($filters) as $filter) {
            $query .= $filter . " = :" . $filter;
            if ($filter != array_key_last($filters)) {
                $query .= ' AND ';
            }
        }
        $stmt = $this->executeQuery($query, $filters);
        $stmt->setFetchMode(\PDO::FETCH_CLASS, $class);
        return $stmt->fetch();
    }

    protected function readMany(string $class, array $filters = [], array $order = [], int $limit = -1, int $offset = -1): mixed
    {
        $query = 'SELECT * FROM ' . $this->getTableName($class);
        if (!empty($filters)) {
            $query .= ' WHERE ';
            foreach (array_keys($filters) as $filter) {
                $query .= $filter . " = :" . $filter;
                if ($filter != array_key_last($filters)) {
                    $query .= ' AND ';
                }
            }
        }
        if (!empty($order)) {
            $query .= ' ORDER BY ';
            foreach ($order as $key => $val) {
                $query .= $key . ' ' . $val;
                if ($key != array_key_last($order)) {
                    $query .= ', ';
                }
            }
        }
        if ($limit !== -1) {
            $query .= ' LIMIT ' . $limit;
            if ($offset !== -1) {
                $query .= ' OFFSET ' . $offset;
            }
        }
        $stmt = $this->executeQuery($query, $filters);
        $stmt->setFetchMode(\PDO::FETCH_CLASS, $class);
        return $stmt->fetchAll();
    }

    protected function create(string $class, array $fields): PDOStatement
    {
        $query = "INSERT INTO " . $this->getTableName($class) . " (";
        foreach (array_keys($fields) as $field) {
            $query .= $field;
            if ($field != array_key_last($fields)) {
                $query .= ', ';
            }
        }
        $query .= ') VALUES (';
        foreach (array_keys($fields) as $field) {
            $query .= ':' . $field;
            if ($field != array_key_last($fields)) {
                $query .= ', ';
            }
        }
        $query .= ')';
        return $this->executeQuery($query, $fields);
    }

    protected function update(string $class, array $fields, int $id): PDOStatement
    {
        $query = "UPDATE " . $this->getTableName($class) . " SET ";
        foreach (array_keys($fields) as $field) {
            $query .= $field . " = :" . $field;
            if ($field != array_key_last($fields)) {
                $query .= ', ';
            }
        }
        $query .= ' WHERE id = :id';
        $fields['id'] = $id;
        return $this->executeQuery($query, $fields);
    }

    protected function remove(string $class, int $id): \PDOStatement
    {
        $query = "DELETE FROM " . $this->getTableName($class) . " WHERE id = :id";
        return $this->executeQuery($query, [ 'id' => $id ]);
    }

}
