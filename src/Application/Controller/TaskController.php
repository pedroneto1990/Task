<?php
namespace Application\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Infra\Config;
use Domain\Task\Service;
use Application\Controller\Validator\Task as TaskValidator;

class TaskController
{
    protected $service;
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function listAction()
    {
        return $this->getService()->all();
    }

    public function getAction(int $id)
    {
        return $this->getService()->get($id);
    }

    public function createAction(Request $request)
    {
        $content = $this->getContent($request);
        $validator = TaskValidator::toCreate($content);
        if ($validator !== true) {
            return $this->getService()->error($validator, JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->getService()->create($request);
    }

    public function putAction(Request $request)
    {
        $content = $this->getContent($request);
        $validator = TaskValidator::toPut($content);
        if ($validator !== true) {
            return $this->getService()->error($validator, JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->getService()->put($request);
    }

    public function patchAction(Request $request)
    {
        return $this->getService()->patch($request);
    }

    public function removeAction(int $id)
    {
        return $this->getService()->remove($id);
    }

    public function getService()
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
            $this->service = new Service($database, $this->logger);
        }

        return $this->service;
    }

    protected function getConfig()
    {
        return Config::getInstance();
    }

    protected function getContent(Request $request)
    {
        $content = $request->getContent();
        return json_decode($content, true);
    }
}
