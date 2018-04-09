<?php
namespace Domain\Task;

use Symfony\Component\HttpFoundation\JsonResponse;

class Exception extends \Exception
{
    protected $code = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
}