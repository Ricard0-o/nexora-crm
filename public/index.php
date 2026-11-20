<?php

declare(strict_types=1);

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (PHP_SAPI === 'cli-server' && $requestPath !== '/' && is_file(__DIR__ . $requestPath)) {
    return false;
}

require dirname(__DIR__) . '/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\AuditController;
use App\Controllers\DashboardController;
use App\Controllers\FinanceController;
use App\Controllers\HomeController;
use App\Controllers\LeadController;
use App\Controllers\ProjectController;
use App\Controllers\PipelineController;
use App\Controllers\ReportController;
use App\Controllers\SearchController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Controllers\PaymentController;
use App\Helpers\Env;
use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\Router;
use App\Middleware\SecurityHeadersMiddleware;

$maxBytes = (int) Env::get('MAX_REQUEST_BYTES', '6291456');
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > $maxBytes) { Response::json(false, 'Requisição excede o limite permitido.', [], 413); }
SecurityHeadersMiddleware::apply();

$router = new Router();
$router->get('/', [HomeController::class, 'index']);
$router->get('/demo', [HomeController::class, 'demo']);
$router->get('/login', [AuthController::class, 'show']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/subscription', [AuthController::class, 'subscription'], true);
$router->post('/payments/checkout', [PaymentController::class, 'checkout'], true);
$router->get('/payment/success', [PaymentController::class, 'success'], true);
$router->get('/payment/pending', [PaymentController::class, 'pending'], true);
$router->get('/payment/failure', [PaymentController::class, 'failure'], true);
$router->post('/webhooks/mercado-pago', [PaymentController::class, 'webhook'], false, false, false);
$router->post('/logout', [AuthController::class, 'logout'], true);
$router->get('/dashboard', [DashboardController::class, 'index'], true);
$router->get('/leads', [LeadController::class, 'index'], true);
$router->get('/leads/create', [LeadController::class, 'create'], true);
$router->get('/leads/view', [LeadController::class, 'show'], true);
$router->post('/leads', [LeadController::class, 'store'], true);
$router->get('/leads/edit', [LeadController::class, 'edit'], true);
$router->post('/leads/update', [LeadController::class, 'update'], true);
$router->get('/leads/import', [LeadController::class, 'importForm'], true);
$router->get('/leads/spreadsheet', [LeadController::class, 'spreadsheetForm'], true);
$router->get('/pipeline', [PipelineController::class, 'index'], true);
$router->post('/pipeline/status', [PipelineController::class, 'updateStatus'], true);
$router->post('/leads/import', [LeadController::class, 'import'], true);
$router->post('/api/leads/import', [LeadController::class, 'import'], true);
$router->get('/projects', [ProjectController::class, 'index'], true);
$router->get('/projects/create', [ProjectController::class, 'create'], true);
$router->post('/projects', [ProjectController::class, 'store'], true);
$router->get('/projects/edit', [ProjectController::class, 'edit'], true);
$router->post('/projects/update', [ProjectController::class, 'update'], true);
$router->get('/finance', [FinanceController::class, 'index'], true);
$router->get('/reports', [ReportController::class, 'index'], true);
$router->post('/reports/export', [ReportController::class, 'export'], true);
$router->get('/audit', [AuditController::class, 'index'], true);
$router->get('/settings', [SettingsController::class, 'index'], true);
$router->post('/settings', [SettingsController::class, 'update'], true);
$router->get('/users', [UserController::class, 'index'], true, true);
$router->post('/users/subscription', [UserController::class, 'updateSubscription'], true, true);
$router->get('/search', [SearchController::class, 'index'], true);
try {
    $router->dispatch(new Request());
} catch (Throwable $exception) {
    if (class_exists(Monolog\Logger::class)) {
        App\Helpers\LoggerFactory::create('http')->error($exception->getMessage(), ['exception' => $exception]);
    } else {
        error_log($exception->__toString());
    }
    $request = new Request();
    if ($request->expectsJson()) { Response::json(false, 'Não foi possível concluir a operação.', [], 500); }
    http_response_code(500);
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Indisponível</title><body style="font-family:system-ui;padding:40px"><h1>Não foi possível concluir</h1><p>Tente novamente em instantes. O evento foi registrado.</p></body></html>';
}
