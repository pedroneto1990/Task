<?php
namespace Domain\Task;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Ramsey\Uuid\Uuid;
use Application\Controller\Validator\Task as TaskValidator;

class Service
{
    /**
     * @var \Domain\Task\Repository
     */
    protected $repository;

    /**
     * Construct a service object
     *
     * @param \Domain\Task\Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Create a task
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Domain\Task\Exception
     */
    public function create(Request $request) : JsonResponse
    {
        $entity = Factory::makeFromRequest($request);
        $sortOrder = $entity->getSortOrder();
        $repository = $this->getRepository();
        $repository->beginTransaction();
        if ($repository->hasSortOrder($sortOrder)) {
            $repository->reorderSortOrder($sortOrder);
        }

        $entity->setUuid($this->generateUuid());
        $entity->setDateCreated(new \DateTime());
        $id = $repository->create($entity);
        if (!$id) {
            $repository->rollbackTransaction();
            throw new Exception(TaskValidator::MESSAGE_INTERNAL_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $repository->commitTransaction();
        $headers = [
            sprintf('Location: /task/%d', $id)
        ];

        $response = $this->get($id);
        $response->setStatusCode(JsonResponse::HTTP_CREATED);
        $response->headers->add($headers);
        return $response;
    }

    /**
     * List all tasks
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Domain\Task\Exception
     */
    public function all() : JsonResponse
    {
        $taskList = $this->getRepository()->all();
        if (empty($taskList)) {
            throw new Exception(TaskValidator::MESSAGE_EMPTY_LIST, JsonResponse::HTTP_OK);
        }

        $result = [];
        foreach ($taskList as $row) {
            $result[] = Factory::fromArray($row)->toArray();
        }

        return new JsonResponse($result);
    }

    /**
     * Get details of task
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Domain\Task\Exception
     */
    public function get(int $id) : JsonResponse
    {
        $result = $this->getRepository()->get($id);
        if (!$result) {
            throw new Exception(TaskValidator::MESSAGE_GET_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }

        $entity = Factory::fromArray($result);
        return new JsonResponse($entity->toArray());
    }

    /**
     * Remove task
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Domain\Task\Exception
     */
    public function remove(int $id) : JsonResponse
    {
        if (!$this->getRepository()->remove($id)) {
            throw new Exception(TaskValidator::MESSAGE_REMOVE_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Update all fields from task
     *
     * @param \Symfony\Component\HttpFoundation\Request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function put(Request $request) : JsonResponse
    {
        $this->update($request);
        return $this->get($request->get('id'));
    }

    /**
     * Update partial of fields from task
     *
     * @param \Symfony\Component\HttpFoundation\Request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function patch(Request $request) : JsonResponse
    {
        $this->update($request);
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Update task
     *
     * @param \Symfony\Component\HttpFoundation\Request
     * @return bool
     * @throws \Domain\Task\Exception
     */
    protected function update(Request $request) : bool
    {
        $entity = Factory::makeFromRequest($request);
        if (!$this->getRepository()->get($entity->getId())) {
            throw new Exception(TaskValidator::MESSAGE_UPDATE_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }

        $repository = $this->getRepository();
        $sortOrder = $entity->getSortOrder();
        $repository->beginTransaction();
        if ($sortOrder !== null && $repository->hasSortOrder($sortOrder, $entity->getId())) {
            $repository->reorderSortOrder($sortOrder);
        }

        if (!$repository->update($entity->getId(), $entity)) {
            $repository->rollbackTransaction();
            throw new Exception(TaskValidator::MESSAGE_INTERNAL_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $repository->commitTransaction();
        return true;
    }

    /**
     * Generate Uuid
     *
     * @return string
     */
    protected function generateUuid() : string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, uniqid(microtime(true)));
    }

    /**
     * Get Repository
     *
     * @return \Domain\Task\Repository
     */
    protected function getRepository() : Repository
    {
        return $this->repository;
    }
}
