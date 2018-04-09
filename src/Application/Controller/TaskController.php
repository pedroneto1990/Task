<?php
namespace Application\Controller;

use Domain\Task\Exception;
use Domain\Task\Repository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Infra\Config;
use Domain\Task\Service;
use Application\Controller\Validator\Task as TaskValidator;

class TaskController
{
    /**
     * @var \Domain\Task\Service
     */
    private $service;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param \Psr\Log\LoggerInterface
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * GET /task
     * List all tasks
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function listAction() : JsonResponse
    {
        return $this->getService()->all();
    }

    /**
     * GET /task/{id}
     * Task Details
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getAction(int $id) : JsonResponse
    {
        return $this->getService()->get($id);
    }

    /**
     * POST /task
     * Create a task
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Domain\Task\Exception
     */
    public function createAction(Request $request)
    {
        $content = $this->getContentAsArray($request);
        $validator = TaskValidator::toCreate($content);
        if ($validator !== true) {
            throw new Exception($validator, JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->getService()->create($request);
    }

    /**
     * PUT /task/{id}
     * Update all fields from task
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Domain\Task\Exception
     */
    public function putAction(Request $request) : JsonResponse
    {
        $content = $this->getContentAsArray($request);
        $validator = TaskValidator::toPut($content);
        if ($validator !== true) {
            throw new Exception($validator, JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->getService()->put($request);
    }

    /**
     * PATCH /task/{id}
     * Partial-Update task
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\jsonResponse
     */
    public function patchAction(Request $request) : JsoNResponse
    {
        return $this->getService()->patch($request);
    }

    /**
     * Remove task
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function removeAction(int $id) : JsonResponse
    {
        return $this->getService()->remove($id);
    }

    /**
     * Get task service
     *
     * @return \Domain\Task\Service
     */
    private function getService() : Service
    {
        if (!$this->service instanceof Service) {
            $config = $this->getConfig();
            $username = $config->get('database.username');
            $password = $config->get('database.password');
            $database = new \PDO(sprintf(
                'mysql:dbname=%s;host=%s;port:%d',
                $config->get('database.name'),
                $config->get('database.host'),
                $config->get('database.port')
            ), $username, $password);
            $repository = new Repository($database, $this->logger);
            $this->service = new Service($repository);
        }

        return $this->service;
    }

    /**
     * Get Config
     *
     * @return \Infra\Config
     */
    private function getConfig()
    {
        return Config::getInstance();
    }

    /**
     * Get request content as array
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     * @throws \Domain\Task\Exception
     */
    private function getContentAsArray(Request $request) : array
    {
        $content = $request->getContent();
        $content = json_decode($content, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new Exception(TaskValidator::MESSAGE_BAD_REQUEST, JsonResponse::HTTP_BAD_REQUEST);
        }

        return $content;
    }
}
