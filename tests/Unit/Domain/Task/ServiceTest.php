<?php
namespace Tests\Domain\Task;

use PHPUnit\Framework\TestCase;
use \Domain\Task\Repository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use \Domain\Task\Service;
use \Domain\Task\Entity;

class ServiceTest extends TestCase
{

    public function testCreateSuccessful()
    {
        $repository = $this->getRepositoryMock([
            'create',
            'get',
            'hasSortOrder',
            'beginTransaction',
            'commitTransaction'
        ]);
        $repository->method('hasSortOrder')
            ->willReturn(false);
        $repository->method('create')
            ->willReturn(1);
        $repository->method('get')
            ->willReturn([ 'valid' ]);

        $service = new Service($repository);
        $request = $this->getRequest([ 'sort_order' => 1 ]);
        $created = $service->create($request);
        $this->assertEquals(JsonResponse::HTTP_CREATED, $created->getStatusCode());
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 500
     */
    public function testCreateFail()
    {
        $repository = $this->getRepositoryMock([
            'create',
            'hasSortOrder',
            'beginTransaction',
            'rollbackTransaction'
        ]);
        $repository->method('hasSortOrder')
            ->willReturn(false);
        $repository->method('create')
            ->willReturn(null);

        $service = new Service($repository);
        $request = $this->getRequest([ 'sort_order' => 1 ]);
        $service->create($request);
    }

    public function testAllSuccessful()
    {
        $repository = $this->getRepositoryMock([
            'all'
        ]);
        $repository->method('all')
            ->willReturn([['id_task' => 1]]);

        $service = new Service($repository);
        $this->assertEquals(
            JsonResponse::HTTP_OK,
            $service->all()->getStatusCode()
        );
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 200
     * @expectedExceptionMessage Wow. You have nothing else to do. Enjoy the rest of your day!
     */
    public function testAllEmpty()
    {
        $repository = $this->getRepositoryMock([
            'all'
        ]);
        $repository->method('all')
            ->willReturn([]);

        $service = new Service($repository);
        $service->all();
    }

    public function testGetSuccessful()
    {
        $entity = $this->getDefaultEntity();
        $repository = $this->getRepositoryMock([
            'get'
        ]);
        $repository->method('get')
            ->willReturn($entity->toArray());

        $service = new Service($repository);
        $this->assertEquals(
            JsonResponse::HTTP_OK,
            $service->get(1)->getStatusCode()
        );
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 404
     */
    public function testGetFail()
    {
        $repository = $this->getRepositoryMock([
            'get'
        ]);
        $repository->method('get')
            ->willReturn([]);

        $service = new Service($repository);
        $service->get(1);
    }

    public function testRemoveSuccessful()
    {
        $repository = $this->getRepositoryMock([
            'remove'
        ]);
        $repository->method('remove')
            ->willReturn(true);

        $service = new Service($repository);
        $this->assertEquals(
            JsonResponse::HTTP_NO_CONTENT,
            $service->remove(1)->getStatusCode()
        );
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 404
     */
    public function testRemoveFail()
    {
        $repository = $this->getRepositoryMock([
            'remove'
        ]);
        $repository->method('remove')
            ->willReturn(false);

        $service = new Service($repository);
        $service->remove(1);
    }

    public function testPutPatchSuccessful()
    {
        $entity = $this->getDefaultEntity();
        $repository = $this->getRepositoryMock([
            'update',
            'get',
            'hasSortOrder',
            'beginTransaction',
            'commitTransaction'
        ]);
        $repository->method('hasSortOrder')
            ->willReturn(false);
        $repository->method('get')
            ->willReturn($entity->toArray());
        $repository->method('update')
            ->willReturn(true);

        $service = new Service($repository);
        $request = $this->getRequest($entity->toArray(), [ 'id' => 1 ]);
        $this->assertEquals(
            JsonResponse::HTTP_OK,
            $service->put($request)->getStatusCode()
        );
        $this->assertEquals(
            JsonResponse::HTTP_NO_CONTENT,
            $service->patch($request)->getStatusCode()
        );
    }

    public function testPutPatchReorderSuccessful()
    {
        $entity = $this->getDefaultEntity();
        $repository = $this->getRepositoryMock([
            'update',
            'get',
            'hasSortOrder',
            'reorderSortOrder',
            'beginTransaction',
            'commitTransaction'
        ]);
        $repository->method('hasSortOrder')
            ->willReturn(true);
        $repository->expects($this->exactly(2))
            ->method('reorderSortOrder')
            ->willReturn(true);
        $repository->method('get')
            ->willReturn($entity->toArray());
        $repository->method('update')
            ->willReturn(true);

        $service = new Service($repository);
        $request = $this->getRequest($entity->toArray(), [ 'id' => 1 ]);
        $this->assertEquals(
            JsonResponse::HTTP_OK,
            $service->put($request)->getStatusCode()
        );
        $this->assertEquals(
            JsonResponse::HTTP_NO_CONTENT,
            $service->patch($request)->getStatusCode()
        );
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Are you a hacker or something? The task you were trying to edit doesn't exist.
     */
    public function testPutNotFound()
    {
        $entity = $this->getDefaultEntity();
        $repository = $this->getRepositoryMock([
            'get'
        ]);
        $repository->method('get')
            ->willReturn(null);

        $service = new Service($repository);
        $request = $this->getRequest($entity->toArray(), [ 'id' => 1 ]);
        $service->put($request);
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 404
     * @expectedExceptionMessage Are you a hacker or something? The task you were trying to edit doesn't exist.
     */
    public function testPatchNotFound()
    {
        $entity = $this->getDefaultEntity();
        $repository = $this->getRepositoryMock([
            'get'
        ]);
        $repository->method('get')
            ->willReturn(null);

        $service = new Service($repository);
        $request = $this->getRequest($entity->toArray(), [ 'id' => 1 ]);
        $service->patch($request);
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 500
     */
    public function testPutInternalError()
    {
        $entity = $this->getDefaultEntity();
        $repository = $this->getRepositoryMock([
            'update',
            'get',
            'hasSortOrder',
            'beginTransaction',
            'rollbackTransaction'
        ]);
        $repository->method('hasSortOrder')
            ->willReturn(false);
        $repository->method('get')
            ->willReturn([ 'valid' ]);
        $repository->method('update')
            ->willReturn(false);

        $service = new Service($repository);
        $request = $this->getRequest($entity->toArray(), [ 'id' => 1 ]);
        $service->put($request);
    }

    /**
     * @expectedException \Domain\Task\Exception
     * @expectedExceptionCode 500
     */
    public function testPatchInternalError()
    {
        $entity = $this->getDefaultEntity();
        $repository = $this->getRepositoryMock([
            'update',
            'get',
            'hasSortOrder',
            'beginTransaction',
            'rollbackTransaction'
        ]);
        $repository->method('hasSortOrder')
            ->willReturn(false);
        $repository->method('get')
            ->willReturn([ 'valid' ]);
        $repository->method('update')
            ->willReturn(false);

        $service = new Service($repository);
        $request = $this->getRequest($entity->toArray(), [ 'id' => 1 ]);
        $service->patch($request);
    }

    private function getRequest(array $body = [], array $query = [])
    {
        $body = json_encode($body);
        return new Request($query, [], [], [], [], [], $body);
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

    private function getRepositoryMock(array $methods)
    {
        return $this->getMockBuilder(Repository::class)
            ->disableOriginalConstructor()
            ->setMethods($methods)
            ->getMock();
    }
}