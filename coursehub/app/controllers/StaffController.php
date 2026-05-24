<?php
declare(strict_types=1);
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class StaffController
{
    private StaffModule $model;
    private StaffView   $view;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->model  = new StaffModule();
        $this->view   = new StaffView();
    }

    private function h(string $v): string { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }

    public function index(Request $req, Response $res): Response
    {
        $staff = $this->model->getAllStaff();
        $res->getBody()->write($this->view->renderStaffList($staff));
        return $res;
    }

    public function show(Request $req, Response $res, array $args): Response
    {
        $staff = $this->model->getStaffById((int)$args['id']);
        if (!$staff) {
            $_SESSION['flash_error'] = 'Staff member not found.';
            return $res->withHeader('Location', '/staff')->withStatus(302);
        }
        $modules    = $this->model->getModulesLedBy((int)$staff['id']);
        $programmes = $this->model->getProgrammesLedBy((int)$staff['id']);
        $res->getBody()->write($this->view->renderStaffProfile($staff, $modules, $programmes));
        return $res;
    }

    public function loginForm(Request $req, Response $res): Response
    {
        if (!empty($_SESSION['staff_id'])) return $res->withHeader('Location', '/staff/portal')->withStatus(302);
        $res->getBody()->write($this->view->renderLoginForm());
        return $res;
    }

    public function login(Request $req, Response $res): Response
    {
        $data  = (array)$req->getParsedBody();
        $email = filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $pass  = $data['password'] ?? '';
        $staff = $this->model->findByEmail($email);

        if (!$staff || !password_verify($pass, $staff['password_hash'])) {
            $this->logger->warning('Staff login failed', ['email' => $email]);
            $_SESSION['flash_error'] = 'Invalid email or password.';
            return $res->withHeader('Location', '/staff/login')->withStatus(302);
        }

        session_regenerate_id(true);
        $_SESSION['staff_id']         = $staff['id'];
        $_SESSION['staff_name']       = $staff['name'];
        $_SESSION['staff_first_name'] = explode(' ', $staff['name'])[0];
        $_SESSION['staff_email']      = $staff['email'];
        $_SESSION['flash_success']    = 'Welcome back, ' . explode(' ', $staff['name'])[0] . '!';
        $this->logger->info('Staff login', ['email' => $email]);
        return $res->withHeader('Location', '/staff/portal')->withStatus(302);
    }

    public function logout(Request $req, Response $res): Response
    {
        $this->logger->info('Staff logout', ['id' => $_SESSION['staff_id'] ?? null, 'email' => $_SESSION['staff_email'] ?? '']);
        unset($_SESSION['staff_id'], $_SESSION['staff_name'], $_SESSION['staff_first_name'], $_SESSION['staff_email']);
        $_SESSION['flash_success'] = 'You have been logged out.';
        return $res->withHeader('Location', '/staff')->withStatus(302);
    }

    public function portal(Request $req, Response $res): Response
    {
        if (empty($_SESSION['staff_id'])) {
            $_SESSION['flash_error'] = 'Please sign in to access the staff portal.';
            return $res->withHeader('Location', '/staff/login')->withStatus(302);
        }
        $staffId  = (int)$_SESSION['staff_id'];
        $staff    = $this->model->getStaffById($staffId);
        $mm       = new ModuleModule();
        $all      = $mm->getAllModules();
        $myMods   = $this->model->getModulesLedBy($staffId);
        $myIds    = array_column($myMods, 'id');
        // Attach prog_count to all modules
        $res->getBody()->write($this->view->renderPortal($staff, $all, $myIds));
        return $res;
    }

    public function editProfileForm(Request $req, Response $res): Response
    {
        if (empty($_SESSION['staff_id'])) return $res->withHeader('Location', '/staff/login')->withStatus(302);
        $staff = $this->model->getStaffById((int)$_SESSION['staff_id']);
        $res->getBody()->write($this->view->renderEditProfile($staff));
        return $res;
    }

    public function updateProfile(Request $req, Response $res): Response
    {
        if (empty($_SESSION['staff_id'])) return $res->withHeader('Location', '/staff/login')->withStatus(302);
        $id   = (int)$_SESSION['staff_id'];
        $data = (array)$req->getParsedBody();
        $this->model->updateStaff($id, [
            'name'       => $this->h($data['name'] ?? ''),
            'role'       => $this->h($data['role'] ?? ''),
            'department' => $this->h($data['department'] ?? ''),
            'email'      => filter_var(trim($data['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'bio'        => htmlspecialchars(trim($data['bio'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'phone'      => $this->h($data['phone'] ?? ''),
            'office'     => $this->h($data['office'] ?? ''),
            'photo_url'  => $this->h($data['photo_url'] ?? ''),
        ]);
        $_SESSION['staff_name'] = $this->h($data['name'] ?? '');
        $this->logger->info('Staff profile updated', ['id' => $id, 'name' => $this->h($data['name'] ?? '')]);
        $_SESSION['flash_success'] = 'Profile updated successfully.';
        return $res->withHeader('Location', '/staff/profile/edit')->withStatus(302);
    }

    public function updatePassword(Request $req, Response $res): Response
    {
        if (empty($_SESSION['staff_id'])) return $res->withHeader('Location', '/staff/login')->withStatus(302);
        $id      = (int)$_SESSION['staff_id'];
        $data    = (array)$req->getParsedBody();
        $current = $data['current_password'] ?? '';
        $new     = $data['new_password'] ?? '';
        $confirm = $data['confirm_password'] ?? '';
        $staff   = $this->model->getStaffById($id);

        if (!password_verify($current, $staff['password_hash'])) {
            $this->logger->warning('Staff password change failed — wrong current password', ['id' => $id]);
            $_SESSION['flash_error'] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $_SESSION['flash_error'] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $_SESSION['flash_error'] = 'Passwords do not match.';
        } else {
            $this->model->updatePassword($id, password_hash($new, PASSWORD_BCRYPT), $new);
            $this->logger->info('Staff password changed', ['id' => $id]);
            $_SESSION['flash_success'] = 'Password updated successfully.';
        }
        return $res->withHeader('Location', '/staff/profile/edit')->withStatus(302);
    }

    public function deleteProfile(Request $req, Response $res): Response
    {
        if (empty($_SESSION['staff_id'])) return $res->withHeader('Location', '/staff/login')->withStatus(302);
        $id = (int)$_SESSION['staff_id'];
        $this->logger->info('Staff profile deleted', ['id' => $id, 'email' => $_SESSION['staff_email'] ?? '']);
        $this->model->deleteStaff($id);
        unset($_SESSION['staff_id'], $_SESSION['staff_name'], $_SESSION['staff_first_name'], $_SESSION['staff_email']);
        $_SESSION['flash_success'] = 'Your staff profile has been deleted.';
        return $res->withHeader('Location', '/')->withStatus(302);
    }
}