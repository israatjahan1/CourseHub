<?php
declare(strict_types=1);
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class ModuleController
{
    private ModuleModule    $module;
    private ModuleView      $view;
    private LoggerInterface $logger;

    public function __construct(ModuleModule $module, ModuleView $view, LoggerInterface $logger)
    {
        $this->module = $module;
        $this->view   = $view;
        $this->logger = $logger;
    }

    public function listAll(Request $req, Response $res): Response
    {
        $this->logger->info('Module list viewed');
        $modules = $this->module->getAllModules();
        $this->logger->debug('Modules fetched', ['count' => count($modules)]);
        $res->getBody()->write($this->view->renderAllModules($modules));
        return $res;
    }

    public function listByProgramme(Request $req, Response $res, array $args): Response
    {
        $pid = (int)$args['id'];
        $this->logger->info('Modules by programme viewed', ['programme_id' => $pid]);
        $modules = $this->module->getModulesByProgrammeId($pid);
        $res->getBody()->write($this->view->render($modules, $pid));
        return $res;
    }

    public function show(Request $req, Response $res, array $args): Response
    {
        $id = (int)$args['id'];
        $m  = $this->module->getModuleById($id);
        if (!$m) {
            $this->logger->warning('Module not found', ['id' => $id]);
            $_SESSION['flash_error'] = 'Module not found.';
            return $res->withHeader('Location', '/modules')->withStatus(302);
        }
        $this->logger->info('Module detail viewed', ['id' => $id, 'title' => $m['title']]);
        $programmes = $this->module->getProgrammesForModule((int)$m['id']);
        $res->getBody()->write($this->view->renderModuleDetail($m, $programmes));
        return $res;
    }
}
