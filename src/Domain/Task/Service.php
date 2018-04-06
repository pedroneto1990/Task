<?php
namespace Domain\Task;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Ramsey\Uuid\Uuid;
use Application\Controller\Validator\Task as TaskValidator;

class Service
{
    protected $repository;
    protected $logger;

    public function __construct(\PDO $db, LoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->repository = new Repository($db, $logger);
    }

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
            return $this->error(TaskValidator::MESSAGE_INTERNAL_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $repository->commitTransaction();
        $headers = [
            sprintf('Location: /task/%d', $id)
        ];

        return new JsonResponse(null, JsonResponse::HTTP_CREATED, $headers);
    }

    public function all() : JsonResponse
    {
        $result = $this->getRepository()->all();
        if (empty($result)) {
            return $this->error(TaskValidator::MESSAGE_EMPTY_LIST, JsonResponse::HTTP_OK);
        }

        return new JsonResponse($result);
    }

    public function get(int $id) : JsonResponse
    {
        $result = $this->getRepository()->get($id);
        if (!$result) {
            return $this->error(TaskValidator::MESSAGE_GET_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }

        $entity = Factory::fromArray($result);
        return new JsonResponse($entity->toArray());
    }

    public function remove(int $id) : JsonResponse
    {
        if (!$this->getRepository()->remove($id)) {
            return $this->error(TaskValidator::MESSAGE_REMOVE_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function put(Request $request) : JsonResponse
    {
        $update = $this->update($request);
        if ($update instanceof JsonResponse) {
            return $update;
        }

        return $this->get($request->get('id'));
    }

    public function patch(Request $request) : JsonResponse
    {
        $update = $this->update($request);
        if ($update instanceof JsonResponse) {
            return $update;
        }

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    protected function update(Request $request)
    {
        $entity = Factory::makeFromRequest($request);
        if ($this->get($entity->getId())->getStatusCode() == JsonResponse::HTTP_NOT_FOUND) {
            return $this->error(TaskValidator::MESSAGE_UPDATE_NOT_FOUND, JsonResponse::HTTP_NOT_FOUND);
        }

        $repository = $this->getRepository();
        $sortOrder = $entity->getSortOrder();
        $repository->beginTransaction();
        if ($sortOrder !== null && $repository->hasSortOrder($sortOrder, $entity->getId())) {
            $repository->reorderSortOrder($sortOrder);
        }

        if (!$repository->update($entity->getId(), $entity)) {
            $repository->rollbackTransaction();
            return $this->error(TaskValidator::MESSAGE_INTERNAL_ERROR, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $repository->commitTransaction();
        return true;
    }

    protected function generateUuid() : string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, uniqid(microtime(true)));
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    protected function getRepository() : Repository
    {
        return $this->repository;
    }

    public function error($message, $statusCode = 400) : JsonResponse
    {
        $response = [
            'code' => $statusCode,
            'message' => $message
        ];

        return new JsonResponse($response, $statusCode);
    }
}
