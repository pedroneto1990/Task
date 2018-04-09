<?php
namespace Tests\Domain\Task;

use PHPUnit\Framework\TestCase;
use \Symfony\Component\HttpFoundation\Request;
use \Domain\Task\Factory;

class FactoryTest extends TestCase
{
    public function testMakeFromRequestSuccessful()
    {
        $id = 1;
        $body = [
            'type' => 'work',
            'content' => 'task',
            'sort_order' => 1,
            'done' => true
        ];
        $request = new Request([ 'id' => $id ], [], [], [], [], [], json_encode($body));
        $entity = Factory::makeFromRequest($request);
        $this->assertEquals($body['type'], $entity->getType());
        $this->assertEquals($body['content'], $entity->getContent());
        $this->assertEquals($body['sort_order'], $entity->getSortOrder());
        $this->assertEquals($body['done'], $entity->isDone());
        $this->assertEquals($id, $entity->getId());
        $this->assertNull($entity->getDateCreated());
    }

    public function testMakeFromRequestToArray()
    {
        $id = 1;
        $body = [
            'type' => 'work',
            'content' => 'task',
            'sort_order' => 1,
            'done' => true
        ];
        $request = new Request([ 'id' => $id ], [], [], [], [], [], json_encode($body));
        $entity = Factory::makeFromRequest($request);
        $expectedBody = array_merge($body, [
            'id_task' => $id
        ]);
        $this->assertEquals($expectedBody, $entity->toArray());
    }

    public function testMakeFromEmptyRequest()
    {
        $request = new Request();
        $entity = Factory::makeFromRequest($request);
        $this->assertNull($entity->getType());
        $this->assertNull($entity->getContent());
        $this->assertNull($entity->getSortOrder());
        $this->assertNull($entity->isDone());
        $this->assertNull($entity->getId());
        $this->assertNull($entity->getDateCreated());
    }

    public function testMakeFromEmptyRequestToArray()
    {
        $request = new Request();
        $entity = Factory::makeFromRequest($request);
        $this->assertEquals([], $entity->toArray());
    }
}