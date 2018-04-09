<?php
namespace Domain\Task;

use \PDO;
use \PDOStatement;
use \Psr\Log\LoggerInterface;

class Repository
{
    /**
     * PDO Error None
     * @var string
     */
    const ERROR_NONE = '00000';

    /**
     * Database
     * @var \PDO
     */
    private $db;

    /**
     * Logger
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * Construct repository
     *
     * @param \PDO $db
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(PDO $db, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    /**
     * Get list of tasks
     *
     * @return array
     * @throws \Domain\Task\Exception
     */
    public function all() : array
    {
        $sql = 'SELECT * FROM task ORDER BY sort_order ASC';
        $statement = $this->execute($sql);
        if ($statement->errorCode() != static::ERROR_NONE) {
            throw new Exception('Failed to list tasks');
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get details of task
     * @param int $id
     * @return array|null
     * @throws \Domain\Task\Exception
     */
    public function get(int $id) :? array
    {
        $sql = 'SELECT * FROM task WHERE id_task = ?';
        $statement = $this->execute($sql, [ $id ]);
        if ($statement->errorCode() != static::ERROR_NONE) {
            throw new Exception('Failed to get a task');
        }

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            return null;
        }

        return $result;
    }

    /**
     * Create a task
     *
     * @param \Domain\Task\Entity $entity
     * @return int
     * @throws \Domain\Task\Exception
     */
    public function create(Entity $entity)
    {
        $sql = 'INSERT INTO task
                  (`uuid`, `type`, `content`, `sort_order`, `done`, `date_created`)
                VALUES
                  (?, ?, ?, ?, ?, ?)';

        $values = $entity->toArray();
        if (count($values) != 6) {
            throw new Exception('Failed to create a task - less than required fields');
        }

        $values['done'] = (int) $values['done'];
        $statement = $this->execute($sql, array_values($values));
        if ($statement->errorCode() != static::ERROR_NONE) {
            throw new Exception('Failed to create a task');
        }

        return $this->db->lastInsertId();
    }

    /**
     * Update a task
     *
     * @param int $id
     * @param \Domain\Task\Entity $entity
     * @return bool
     * @throws \Domain\Task\Exception
     */
    public function update(int $id, Entity $entity) : bool
    {
        $entity->setId(null);
        $data = $entity->toArray();
        if (isset($data['done'])) {
            $data['done'] = (int) $data['done'];
        }

        $columns = implode(' = ?, ', array_keys($data)) . ' = ?';
        $sql = sprintf('UPDATE task SET %s WHERE id_task = ? LIMIT 1', $columns);
        $values = array_values($data);
        $values[] = $id;
        $statement = $this->execute($sql, $values);
        if ($statement->errorCode() != static::ERROR_NONE) {
            throw new Exception('Failed to update a task');
        }

        return true;
    }

    /**
     * Remove a task
     *
     * @param int $id
     * @return bool
     */
    public function remove(int $id) : bool
    {
        $statement = $this->execute('DELETE FROM task WHERE id_task = ?', [ $id ]);
        if ($statement->rowCount() == 0) {
            return false;
        }

        return true;
    }

    /**
     * Check if has exists a $sortOrder on database
     *
     * @param int $sortOrder
     * @param int $excludeId
     * @return bool
     * @throws \Domain\Task\Exception
     */
    public function hasSortOrder(int $sortOrder, int $excludeId = null) : bool
    {
        $sql = 'SELECT count(0) FROM task WHERE sort_order = ?';
        $data = [ $sortOrder ];
        if ($excludeId !== null) {
            $sql .= ' AND id_task <> ?';
            $data[] = $excludeId;
        }

        $statement = $this->execute($sql, $data);
        if ($statement->errorCode() != static::ERROR_NONE) {
            throw new Exception('Failed to check if sort order exists');
        }

        $result = $statement->fetchColumn();
        return ($result != '0');
    }

    /**
     * Reorder sort order from task
     *
     * @param int $sortOrder
     * @return bool
     * @throws \Domain\Task\Exception
     */
    public function reorderSortOrder(int $sortOrder) : bool
    {
        $sql = 'UPDATE task SET sort_order = sort_order+1 WHERE sort_order >= ? ORDER BY sort_order DESC';
        $statement = $this->execute($sql, [ $sortOrder ]);
        if ($statement->errorCode() != static::ERROR_NONE) {
            throw new Exception('Failed to reorder tasks');
        }

        return true;
    }

    /**
     * Start a transaction
     *
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * Rollback transaction
     *
     * @return bool
     */
    public function rollbackTransaction()
    {
        return $this->db->rollBack();
    }

    /**
     * Commit transaction
     *
     * @return bool
     */
    public function commitTransaction()
    {
        return $this->db->commit();
    }

    /**
     * Execute a SQL
     *
     * @param string $sql
     * @param array $data
     * @return \PDOStatement
     */
    private function execute(string $sql, array $data = null) : PDOStatement
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($data);
        if ($statement->errorCode() != static::ERROR_NONE) {
            $this->logError($statement);
        }

        return $statement;
    }

    /**
     * Log error from statement
     *
     * @param \PDOStatement
     * @return void
     */
    private function logError(PDOStatement $statement) : void
    {
        $error = $statement->errorInfo();
        $this->logger->warning(
            sprintf('Database Error: [%s][SQLSTATE: %s] %s',
                $error[1], $error[0], $error[2]
            )
        );
    }
}