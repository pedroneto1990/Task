<?php
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Routing\Route;
use Symfony\Component\DependencyInjection\Reference;
use Application\Controller\TaskController;
use Application\Event\ExceptionListener;
use Infra\Config;

require __DIR__ . '/../vendor/autoload.php';

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        return array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle()
        );
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $config = $this->getConfig();
        $c->loadFromExtension('framework', [
            'secret' => $config->get('framework.secret')
        ]);
        $c->loadFromExtension('monolog', [
            'handlers' => [
                'main' => array(
                    'type' => 'stream',
                    'level' => $config->get('logger.level'),
                    'path' => $this->getLogDir() . $config->get('logger.file'),
                ),
            ]
        ]);
        $c->register('task.controller', TaskController::class)
            ->addArgument(new Reference('logger'))
            ->addTag('controller.service_arguments');
        $c->autowire(ExceptionListener::class)
            ->addTag('kernel.event_listener', ['event' => 'kernel.exception']);
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $idParam = [ 'id' => '\d' ];
        $routes->addRoute((new Route('/task', ['_controller' => 'task.controller:listAction']))
            ->setMethods(['GET']));
        $routes->addRoute((new Route('/task/{id}', ['_controller' => 'task.controller:getAction'], $idParam))
            ->setMethods(['GET']));
        $routes->addRoute((new Route('/task', ['_controller' => 'task.controller:createAction']))
            ->setMethods(['POST']));
        $routes->addRoute((new Route('/task/{id}', ['_controller' => 'task.controller:removeAction'], $idParam))
            ->setMethods([ 'DELETE' ]));
        $routes->addRoute((new Route('/task/{id}', ['_controller' => 'task.controller:putAction']))
            ->setMethods(['PUT']));
        $routes->addRoute((new Route('/task/{id}', ['_controller' => 'task.controller:patchAction']))
            ->setMethods(['PATCH']));
    }

    public function getCacheDir()
    {
        return __DIR__ . '/../' . $this->getConfig()->get('cache.path', 'var/cache/') . $this->getEnvironment();
    }

    public function getLogDir()
    {
        return __DIR__ . '/../' . $this->getConfig()->get('logger.path', 'var/log/');
    }

    protected function getConfig()
    {
        return Config::getInstance();
    }
}

$environment = Config::getInstance()->get('environment', 'dev');
$kernel = new Kernel($environment, true);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
