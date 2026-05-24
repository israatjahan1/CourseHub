<?php
declare(strict_types=1);
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class StudentAuthController
{
    private StudentModel    $model;
    private StudentAuthView $view;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->model  = new StudentModel();
        $this->view   = new StudentAuthView();
        $this->logger = $logger;
    }

    private function h(mixed $v): string { return htmlspecialchars(trim((string)$v), ENT_QUOTES, 'UTF-8'); }

    // ── Register ─────────────────────────────────────────────────────────────
    public function registerForm(Request $req, Response $res): Response {
        if (!empty($_SESSION['student_id'])) return $res->withHeader('Location','/account')->withStatus(302);
        $res->getBody()->write($this->view->renderRegister());
        return $res;
    }

    public function register(Request $req, Response $res): Response {
        $d     = (array)$req->getParsedBody();
        $first = $this->h($d['first_name']??'');
        $last  = $this->h($d['last_name']??'');
        $email = filter_var(trim($d['email']??''), FILTER_SANITIZE_EMAIL);
        $pass  = $d['password']??'';
        $pass2 = $d['password_confirm']??'';

        $errors=[];
        if(!$first)                                     $errors[]='First name is required.';
        if(!$last)                                      $errors[]='Last name is required.';
        if(!filter_var($email,FILTER_VALIDATE_EMAIL))   $errors[]='A valid email is required.';
        if(strlen($pass)<8)                             $errors[]='Password must be at least 8 characters.';
        if($pass!==$pass2)                              $errors[]='Passwords do not match.';
        if($email && $this->model->checkEmailExists($email)) $errors[]='An account with that email already exists.';

        if($errors){
            $this->logger->warning('Student registration failed', ['email' => $email, 'errors' => $errors]);
            $_SESSION['flash_error']=implode(' ',$errors);
            return $res->withHeader('Location','/register')->withStatus(302);
        }

        $id = $this->model->create(['first_name'=>$first,'last_name'=>$last,'email'=>$email,'password'=>$pass]);
        session_regenerate_id(true);
        $_SESSION['student_id']         = $id;
        $_SESSION['student_first_name'] = $first;
        $_SESSION['student_email']      = $email;
        $_SESSION['flash_success']      = 'Welcome, '.$first.'! Your account has been created.';
        $this->logger->info('Student registered', ['id' => $id, 'email' => $email]);
        return $res->withHeader('Location','/account')->withStatus(302);
    }

    // ── Login ────────────────────────────────────────────────────────────────
    public function loginForm(Request $req, Response $res): Response {
        if(!empty($_SESSION['student_id'])) return $res->withHeader('Location','/account')->withStatus(302);
        $res->getBody()->write($this->view->renderLogin());
        return $res;
    }

    public function login(Request $req, Response $res): Response {
        $d     = (array)$req->getParsedBody();
        $email = filter_var(trim($d['email']??''),FILTER_SANITIZE_EMAIL);
        $pass  = $d['password']??'';
        $s     = $this->model->findByEmail($email);
        if(!$s||!password_verify($pass,$s['password_hash'])){
            $this->logger->warning('Student login failed', ['email' => $email]);
            $_SESSION['flash_error']='Invalid email or password.';
            return $res->withHeader('Location','/login')->withStatus(302);
        }
        session_regenerate_id(true);
        $_SESSION['student_id']         = $s['id'];
        $_SESSION['student_first_name'] = $s['first_name'];
        $_SESSION['student_email']      = $s['email'];
        $_SESSION['flash_success']      = 'Welcome back, '.$s['first_name'].'!';
        $this->logger->info('Student login', ['id' => $s['id'], 'email' => $email]);
        $redirect = $_SESSION['login_redirect']??'/account';
        unset($_SESSION['login_redirect']);
        return $res->withHeader('Location',$redirect)->withStatus(302);
    }

    // ── Logout ───────────────────────────────────────────────────────────────
    public function logout(Request $req, Response $res): Response {
        $sid = $_SESSION['student_id'] ?? null;
        unset($_SESSION['student_id'],$_SESSION['student_first_name'],$_SESSION['student_email']);
        $_SESSION['flash_success']='You have been logged out.';
        $this->logger->info('Student logout', ['student_id' => $sid]);
        return $res->withHeader('Location','/')->withStatus(302);
    }

    // ── Forgot password ──────────────────────────────────────────────────────
    public function forgotForm(Request $req, Response $res): Response {
        $res->getBody()->write($this->view->renderForgotPassword());
        return $res;
    }

    public function forgotSubmit(Request $req, Response $res): Response {
        $email = filter_var(trim(((array)$req->getParsedBody())['email']??''),FILTER_SANITIZE_EMAIL);
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
            $_SESSION['flash_error']='Please enter a valid email address.';
            return $res->withHeader('Location','/forgot-password')->withStatus(302);
        }
        $s = $this->model->findByEmail($email);
        if($s){
            $token=bin2hex(random_bytes(32));
            $this->model->setResetToken((int)$s['id'],$token);
            $_SESSION['reset_token_display'] = $token;
            $_SESSION['reset_email_display'] = $email;
            $this->logger->info('Password reset requested', ['email' => $email]);
            return $res->withHeader('Location','/forgot-password/sent')->withStatus(302);
        }
        $this->logger->info('Password reset requested for unknown email', ['email' => $email]);
        $_SESSION['reset_token_display'] = null;
        $_SESSION['reset_email_display'] = $email;
        return $res->withHeader('Location','/forgot-password/sent')->withStatus(302);
    }

    public function forgotSent(Request $req, Response $res): Response {
        $token = $_SESSION['reset_token_display'] ?? null;
        $email = $_SESSION['reset_email_display'] ?? '';
        unset($_SESSION['reset_token_display'], $_SESSION['reset_email_display']);
        $res->getBody()->write($this->view->renderForgotSent($email, $token));
        return $res;
    }

    public function resetForm(Request $req, Response $res): Response {
        $token = trim($req->getQueryParams()['token']??'');
        if(!$token||!$this->model->findByResetToken($token)){
            $_SESSION['flash_error']='This reset link is invalid or has expired.';
            return $res->withHeader('Location','/login')->withStatus(302);
        }
        $res->getBody()->write($this->view->renderResetPassword($token));
        return $res;
    }

    public function resetSubmit(Request $req, Response $res): Response {
        $d     = (array)$req->getParsedBody();
        $token = trim($d['token']??'');
        $pass  = $d['password']??'';
        $pass2 = $d['password_confirm']??'';
        $s = $this->model->findByResetToken($token);
        if(!$s){$_SESSION['flash_error']='Invalid or expired reset link.';return $res->withHeader('Location','/login')->withStatus(302);}
        if(strlen($pass)<8){$_SESSION['flash_error']='Password must be at least 8 characters.';return $res->withHeader('Location','/reset-password?token='.$token)->withStatus(302);}
        if($pass!==$pass2){$_SESSION['flash_error']='Passwords do not match.';return $res->withHeader('Location','/reset-password?token='.$token)->withStatus(302);}
        $this->model->updatePassword((int)$s['id'],password_hash($pass,PASSWORD_BCRYPT));
        $this->model->clearResetToken((int)$s['id']);
        $this->logger->info('Password reset completed', ['student_id' => $s['id']]);
        $_SESSION['flash_success']='Password reset successfully. Please sign in.';
        return $res->withHeader('Location','/login')->withStatus(302);
    }

    // ── Account dashboard ────────────────────────────────────────────────────
    public function account(Request $req, Response $res): Response {
        if(empty($_SESSION['student_id'])){$_SESSION['login_redirect']='/account';return $res->withHeader('Location','/login')->withStatus(302);}
        $id     = (int)$_SESSION['student_id'];
        $s      = $this->model->findById($id);
        $ints   = $this->model->getInterests($id);
        $favs   = $this->model->getFavourites($id);
        $this->logger->debug('Student account viewed', ['student_id' => $id]);
        $res->getBody()->write($this->view->renderAccount($s,$ints,$favs));
        return $res;
    }

    // ── Edit profile ─────────────────────────────────────────────────────────
    public function editProfileForm(Request $req, Response $res): Response {
        if(empty($_SESSION['student_id'])) return $res->withHeader('Location','/login')->withStatus(302);
        $s = $this->model->findById((int)$_SESSION['student_id']);
        $res->getBody()->write($this->view->renderEditProfile($s));
        return $res;
    }

    public function updateProfile(Request $req, Response $res): Response {
        if(empty($_SESSION['student_id'])) return $res->withHeader('Location','/login')->withStatus(302);
        $id = (int)$_SESSION['student_id'];
        $d  = (array)$req->getParsedBody();
        $fn = htmlspecialchars(trim($d['first_name']??''),ENT_QUOTES,'UTF-8');
        $ln = htmlspecialchars(trim($d['last_name']??''),ENT_QUOTES,'UTF-8');
        if(!$fn||!$ln){$_SESSION['flash_error']='First and last name are required.';return $res->withHeader('Location','/account/edit')->withStatus(302);}
        $this->model->updateProfile($id,['first_name'=>$fn,'last_name'=>$ln,'phone'=>trim($d['phone']??''),'bio'=>trim($d['bio']??'')]);
        $_SESSION['student_first_name']=$fn;
        $_SESSION['flash_success']='Profile updated.';
        $this->logger->info('Student profile updated', ['student_id' => $id]);
        return $res->withHeader('Location','/account/edit')->withStatus(302);
    }

    public function updatePassword(Request $req, Response $res): Response {
        if(empty($_SESSION['student_id'])) return $res->withHeader('Location','/login')->withStatus(302);
        $id = (int)$_SESSION['student_id'];
        $d  = (array)$req->getParsedBody();
        $s  = $this->model->findById($id);
        if(!password_verify($d['current_password']??'',$s['password_hash'])){
            $this->logger->warning('Student password change failed — wrong current password', ['student_id' => $id]);
            $_SESSION['flash_error']='Current password is incorrect.';
            return $res->withHeader('Location','/account/edit')->withStatus(302);
        }
        if(strlen($d['new_password']??'')<8){$_SESSION['flash_error']='New password must be at least 8 characters.';return $res->withHeader('Location','/account/edit')->withStatus(302);}
        if(($d['new_password']??'')!==($d['confirm_password']??'')){$_SESSION['flash_error']='Passwords do not match.';return $res->withHeader('Location','/account/edit')->withStatus(302);}
        $this->model->updatePassword($id,password_hash($d['new_password'],PASSWORD_BCRYPT));
        $this->logger->info('Student password changed', ['student_id' => $id]);
        $_SESSION['flash_success']='Password updated successfully.';
        return $res->withHeader('Location','/account/edit')->withStatus(302);
    }

    public function deleteAccount(Request $req, Response $res): Response {
        if(empty($_SESSION['student_id'])) return $res->withHeader('Location','/login')->withStatus(302);
        $id = (int)$_SESSION['student_id'];
        $this->logger->info('Student account deleted', ['student_id' => $id]);
        $this->model->deleteStudent($id);
        unset($_SESSION['student_id'],$_SESSION['student_first_name'],$_SESSION['student_email']);
        $_SESSION['flash_success']='Your account has been deleted.';
        return $res->withHeader('Location','/')->withStatus(302);
    }

    // ── Interest withdraw ────────────────────────────────────────────────────
    public function withdrawInterest(Request $req, Response $res, array $args): Response {
        if(empty($_SESSION['student_id'])) return $res->withHeader('Location','/login')->withStatus(302);
        $sid = (int)$_SESSION['student_id'];
        $iid = (int)$args['id'];
        $this->model->withdrawInterest($sid, $iid);
        $this->logger->info('Student withdrew interest', ['student_id' => $sid, 'interest_id' => $iid]);
        $_SESSION['flash_success']='Interest registration removed.';
        return $res->withHeader('Location','/account')->withStatus(302);
    }

    // ── Favourites ───────────────────────────────────────────────────────────
    public function toggleFavourite(Request $req, Response $res, array $args): Response {
        if(empty($_SESSION['student_id'])){$_SESSION['login_redirect']='/programmes/'.$args['slug'];return $res->withHeader('Location','/login')->withStatus(302);}
        $sid = (int)$_SESSION['student_id'];
        $pm  = new ProgrammeModule();
        $p   = $pm->getProgrammeBySlug($args['slug']);
        if(!$p) return $res->withHeader('Location','/programmes')->withStatus(302);
        if($this->model->isFavourite($sid,(int)$p['id'])){
            $this->model->removeFavourite($sid,(int)$p['id']);
            $this->logger->info('Favourite removed', ['student_id' => $sid, 'programme' => $p['title']]);
            $_SESSION['flash_success']='Removed from favourites.';
        } else {
            $this->model->addFavourite($sid,(int)$p['id']);
            $this->logger->info('Favourite added', ['student_id' => $sid, 'programme' => $p['title']]);
            $_SESSION['flash_success']='Saved to favourites!';
        }
        return $res->withHeader('Location','/programmes/'.$args['slug'])->withStatus(302);
    }
}
