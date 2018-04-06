<?php
namespace Domain\Task;

use \PDO;
use \PDOStatement;
use Psr\Log\LoggerInterface;

class Repository
{
    private $db;
    private $logger;

    public function __construct(PDO $db, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function all()
    {
        $sql = 'SELECT * FROM task ORDER BY sort_order ASC';
        return $this->execute($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get($id)
    {
        $sql = 'SELECT * FROM task WHERE id_task = ?';
        return $this->execute($sql, [ $id ])->fetch(PDO::FETCH_ASSOC);
    }

    public function create(Entity $entity)
    {
        $sql = 'INSERT INTO task
                  (`uuid`, `type`, `content`, `sort_order`, `done`, `date_created`)
                VALUES
                  (?, ?, ?, ?, ?, ?)';

        $values = $entity->toArray();
        $values['done'] = (int) $values['done'];
        if (!$this->execute($sql, array_values($values))) {
            return false;
        }

        return $this->db->lastInsertId();
    }

    public function update(int $id, Entity $entity)
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
        if ($statement->errorCode() == '00000') {
            return true;
        }

        $this->logError($statement);
        return false;
    }

    public function remove(int $id)
    {
        $statement = $this->execute('DELETE FROM task WHERE id_task = ?', [ $id ]);
        if ($statement->rowCount() == 0) {
            return false;
        }

        return true;
    }

    private function execute(string $sql, array $data = null)
    {
        $statement = $this->db->prepare($sql);
        $statement->execute($data);
        if ($statement->errorCode() != '0000') {
            $this->logError($statement);
        }

        return $statement;
    }

    public function hasSortOrder($sortOrder, int $excludeId = null)
    {
        $sql = 'SELECT count(0) FROM task WHERE sort_order = ?';
        $data = [ $sortOrder ];
        if ($excludeId !== null) {
            $sql .= ' AND id_task <> ?';
            $data[] = $excludeId;
        }

        $result = $this->execute($sql, $data)->fetchColumn();
        return ($result != '0');
    }

    public function reorderSortOrder($sortOrder)
    {
        $sql = 'UPDATE task SET sort_order = sort_order+1 WHERE sort_order >= ? ORDER BY sort_order DESC';
        $this->execute($sql, [ $sortOrder ]);
    }

    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    public function rollbackTransaction()
    {
        return $this->db->rollBack();
    }

    public function commitTransaction()
    {
        return $this->db->commit();
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function logError(PDOStatement $statement)
    {
        $error = $statement->errorInfo();
        $this->logger->warning(
            sprintf('Database Error: [%s][SQLSTATE: %s] %s',
                $error[1], $error[0], $error[2]
            )
        );
    }
}