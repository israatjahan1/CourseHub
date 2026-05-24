<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/bootstrap.php';

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_name('coursehub_sess');
    session_set_cookie_params(['lifetime'=>7200,'path'=>'/','httponly'=>true,'samesite'=>'Lax']);
    session_start();
}

// Models
require_once __DIR__ . '/../app/model/ProgrammeModule.php';
require_once __DIR__ . '/../app/model/StaffModule.php';
require_once __DIR__ . '/../app/model/ModuleModule.php';
require_once __DIR__ . '/../app/model/InterestModel.php';
require_once __DIR__ . '/../app/model/AdminModel.php';
require_once __DIR__ . '/../app/model/StudentModel.php';

// Views
require_once __DIR__ . '/../app/views/Layout.php';
require_once __DIR__ . '/../app/views/HomeView.php';
require_once __DIR__ . '/../app/views/ProgrammeView.php';
require_once __DIR__ . '/../app/views/StaffView.php';
require_once __DIR__ . '/../app/views/ModuleView.php';
require_once __DIR__ . '/../app/views/AdminView.php';
require_once __DIR__ . '/../app/views/StudentAuthView.php';

// Controllers
require_once __DIR__ . '/../app/controllers/ProgrammeController.php';
require_once __DIR__ . '/../app/controllers/StaffController.php';
require_once __DIR__ . '/../app/controllers/ModuleController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/StudentAuthController.php';

// Routes
require __DIR__ . '/../app/routes/web.php';

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

$logger = createLogger();

registerRoutes($app, $logger);
$app->run();
