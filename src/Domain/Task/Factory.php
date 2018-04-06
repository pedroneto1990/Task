<?php
namespace Domain\Task;

use Symfony\Component\HttpFoundation\Request;

class Factory
{
    static public function makeFromRequest(Request $request) : Entity
    {
        $json = $request->getContent();
        $data = json_decode($json, true);
        $entity = new Entity;
        if ($request->get('id')) {
            $entity->setId($request->get('id'));
        }

        if ($request->query->get('id')) {
            $entity->setId($request->query->get('id'));
        }

        if (isset($data['type'])) {
            $entity->setType($data['type']);
        }

        if (isset($data['content'])) {
            $entity->setContent($data['content']);
        }

        if (isset($data['sort_order'])) {
            $entity->setSortOrder($data['sort_order']);
        }

        if (isset($data['done'])) {
            $entity->setDone($data['done']);
        }

        return $entity;
    }

    static public function fromArray(array $data) : Entity
    {
        $entity = new Entity();
        if (isset($data['id'])) {
            $entity->setId($data['id']);
        }

        if (isset($data['id_task'])) {
            $entity->setId($data['id_task']);
        }

        if (isset($data['uuid'])) {
            $entity->setUuid($data['uuid']);
        }

        if (isset($data['type'])) {
            $entity->setType($data['type']);
        }

        if (isset($data['sort_order'])) {
            $entity->setSortOrder($data['sort_order']);
        }

        if (isset($data['done'])) {
            $entity->setDone($data['done']);
        }

        if (isset($data['date_created'])) {
            $entity->setDateCreated($data['date_created']);
        }

        if (isset($data['content'])) {
            $entity->setContent($data['content']);
        }

        return $entity;
    }
}
