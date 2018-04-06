<?php
namespace Application\Event;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $statusCode = $this->getStatusCode($exception);
        $response = new JsonResponse([
            'error' => $statusCode,
            'message' => $exception->getMessage()
        ], $statusCode);

        $event->setResponse($response);
    }

    protected function getStatusCode(\Exception $exception)
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        $code = $exception->getCode();
        if ($code < JsonResponse::HTTP_OK || $code > JsonResponse::HTTP_INTERNAL_SERVER_ERROR) {
            return JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        }

        return $code;
    }
}