<?php
namespace Tests\Domain\Task;

use Domain\Task\Entity;
use \PHPUnit\Framework\TestCase;
use \Domain\Task\Repository;
use \Monolog\Handler\TestHandler;
use \Monolog\Logger;
use \PDOStatement;
use \PDO;

class RepositoryTest extends TestCase
{
    private $loggerMock;

    public function setUp()
    {
        parent::setUp();
        $this->loggerMock = new Logger('task');
        $this->loggerMock->pushHandler(new TestHandler());
    }

    public function testAllSuccessful()
    {
        $expected = [
            $this->getDefaultEntity()->toArray()
        ];
        $pdoMock = $this->mockStatementMethod('fetchAll', $expected);
        $repository = new Repository($pdoMock, $this->loggerMock);
        $this->assertSame($expected, $repository->all());
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 500
     */
    public function testAllFail()
    {
        $pdoMock = $this->mockStatementMethod(null, null, '01');
        $repository = new Repository($pdoMock, $this->loggerMock);
        $repository->all();
    }

    public function testGetSuccessful()
    {
        $expected = $this->getDefaultEntity()->toArray();
        $pdoMock = $this->mockStatementMethod('fetch', $expected);
        $repository = new Repository($pdoMock, $this->loggerMock);
        $this->assertSame($expected, $repository->get(1));
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 500
     */
    public function testGetFail()
    {
        $pdoMock = $this->mockStatementMethod(null, null, '01');
        $repository = new Repository($pdoMock, $this->loggerMock);
        $repository->get(1);
    }

    public function testCreateSuccessful()
    {
        $id = 1;
        $pdoMock = $this->getPdoMock(['prepare', 'lastInsertId']);
        $statementMock = $this->getStatementMock(['execute', 'errorCode']);
        $statementMock->method('errorCode')
            ->will($this->returnValue(Repository::ERROR_NONE));
        $pdoMock->method('prepare')
            ->will($this->returnValue($statementMock));
        $pdoMock->method('lastInsertId')
            ->will($this->returnValue($id));

        $entity = $this->getDefaultEntity();
        $entity->setId(null);
        $repository = new Repository($pdoMock, $this->loggerMock);
        $this->assertEquals($id, $repository->create($entity));
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 500
     */
    public function testCreateFailLessFields()
    {
        $pdoMock = $this->mockStatementMethod(null, null, '01');
        $repository = new Repository($pdoMock, $this->loggerMock);
        $repository->create(new Entity);
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 500
     */
    public function testCreateFailInternalError()
    {
        $entity = $this->getDefaultEntity();
        $pdoMock = $this->mockStatementMethod(null, null, '01');
        $repository = new Repository($pdoMock, $this->loggerMock);
        $repository->create($entity);
    }

    public function testUpdateSuccessful()
    {
        $entity = $this->getDefaultEntity();
        $pdoMock = $this->mockStatementMethod();
        $repository = new Repository($pdoMock, $this->loggerMock);
        $this->assertTrue($repository->update(1, $entity));
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 500
     */
    public function testUpdateFail()
    {
        $entity = $this->getDefaultEntity();
        $pdoMock = $this->mockStatementMethod(null, null, '01');
        $repository = new Repository($pdoMock, $this->loggerMock);
        $repository->update(1, $entity);
    }

    public function testRemoveSuccessful()
    {
        $pdoMock = $this->mockStatementMethod('rowCount', 1);
        $repository = new Repository($pdoMock, $this->loggerMock);
        $this->assertTrue($repository->remove(1));
    }

    public function testRemoveFail()
    {
        $pdoMock = $this->mockStatementMethod('rowCount', 0);
        $repository = new Repository($pdoMock, $this->loggerMock);
        $this->assertFalse($repository->remove(1));
    }

    public function testHasSortOrderFalse()
    {
        $pdoMock = $this->mockStatementMethod('fetchColumn', 0);
        $repository = new Repository($pdoMock, $this->loggerMock);
        $this->assertFalse($repository->hasSortOrder(1));
    }

    public function testHasSortOrderTrue()
    {
        $pdoMock = $this->mockStatementMethod('fetchColumn', 1);
        $repository = new Repository($pdoMock, $this->loggerMock);
        $this->assertTrue($repository->hasSortOrder(1));
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 500
     */
    public function testHasSortOrderFail()
    {
        $pdoMock = $this->mockStatementMethod(null, null, '01');
        $repository = new Repository($pdoMock, $this->loggerMock);
        $repository->hasSortOrder(1);
    }

    public function testReorderSortOrderSuccessful()
    {
        $pdoMock = $this->mockStatementMethod();
        $repository = new Repository($pdoMock, $this->loggerMock);
        $this->assertTrue($repository->reorderSortOrder(1));
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 500
     */
    public function testReorderSortOrderFail()
    {
        $pdoMock = $this->mockStatementMethod(null, null, '01');
        $repository = new Repository($pdoMock, $this->loggerMock);
        $repository->reorderSortOrder(1);
    }

    private function getDefaultEntity()
    {
        $entity = new Entity();
        $entity->setId(1);
        $entity->setUuid('123');
        $entity->setContent('test');
        $entity->setDateCreated(date('Y-m-d'));
        $entity->setDone(true);
        $entity->setType('work');
        $entity->setSortOrder(1);
        return $entity;
    }

    private function getPdoMock(array $methods = [])
    {
        return $this->getMockBuilder(PDO::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    private function getStatementMock(array $methods = [])
    {
        return $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }

    private function mockStatementMethod($method = null, $expected = null, $errorCode = Repository::ERROR_NONE)
    {
         $methods = [ 'execute', 'errorCode' ];
         if ($method) {
             $methods[] = $method;
         }

         $statementMock = $this->getStatementMock($methods);
         $statementMock->method('errorCode')
            ->will($this->returnValue($errorCode));
         if ($method) {
             $returnValue = $this->returnValue($expected);
             $statementMock->method($method)->will($returnValue);
         }

         $pdoMock = $this->getPdoMock(['prepare']);
         $pdoMock->method('prepare')
            ->will($this->returnValue($statementMock));
         return $pdoMock;
    }
}