<?php
declare(strict_types=1);
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class ProgrammeController
{
    private ProgrammeModule $module;
    private ProgrammeView   $view;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->module = new ProgrammeModule();
        $this->view   = new ProgrammeView();
    }

    private function h(string $v): string { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }

    public function index(Request $req, Response $res): Response {
        $p    = $req->getQueryParams();
        $kw   = trim($p['search']??'');
        $lv   = trim($p['level']??'');
        $progs = ($kw||$lv) ? $this->module->searchProgrammes($kw,$lv) : $this->module->getAllPublishedProgrammes();
        $this->logger->info('Programme list viewed', ['search' => $kw ?: null, 'level' => $lv ?: null, 'results' => count($progs)]);
        $res->getBody()->write($this->view->renderProgrammeList($progs));
        return $res;
    }

    public function show(Request $req, Response $res, array $args): Response {
        $p = $this->module->getProgrammeBySlug($args['slug']);
        if (!$p) { $this->logger->warning('Programme not found', ['slug' => $args['slug']]); $_SESSION['flash_error']='Programme not found.'; return $res->withHeader('Location','/programmes')->withStatus(302); }
        $mm = new ModuleModule();
        $mods = $mm->getModulesByProgrammeId((int)$p['id']);
        $byYear = [];
        foreach ($mods as $m) {
            $m['prog_count'] = count($mm->getProgrammesForModule((int)$m['id']));
            $byYear[(int)$m['year_of_study']][] = $m;
        }
        ksort($byYear);
        // Related programmes (share at least one module)
        $shared = [];
        $db = getDatabase();
        $st = $db->prepare('SELECT DISTINCT p.title,p.slug,p.level FROM programmes p JOIN programme_modules pm ON pm.programme_id=p.id WHERE pm.module_id IN (SELECT module_id FROM programme_modules WHERE programme_id=?) AND p.id!=? AND p.published=1 ORDER BY p.title LIMIT 6');
        $st->execute([(int)$p['id'],(int)$p['id']]);
        $shared = $st->fetchAll();
        $this->logger->info('Programme detail viewed', ['slug' => $args['slug'], 'title' => $p['title']]);
        $res->getBody()->write($this->view->renderProgrammeDetail($p,$byYear,$shared));
        return $res;
    }

    public function registerInterest(Request $req, Response $res, array $args): Response {
        $p = $this->module->getProgrammeBySlug($args['slug']);
        if (!$p) return $res->withHeader('Location','/programmes')->withStatus(302);
        $d      = (array)$req->getParsedBody();
        $sid    = !empty($_SESSION['student_id']) ? (int)$_SESSION['student_id'] : null;
        if ($sid) {
            $sm   = new StudentModel();
            $st   = $sm->findById($sid);
            $first = $this->h($st['first_name']);
            $last  = $this->h($st['last_name']);
            $email = $st['email'];
        } else {
            $first = $this->h($d['first_name']??'');
            $last  = $this->h($d['last_name']??'');
            $email = filter_var(trim($d['email']??''),FILTER_SANITIZE_EMAIL);
        }
        $phone = $this->h($d['phone']??'');
        $msg   = $this->h($d['message']??'');
        if (!$first||!$last||!filter_var($email,FILTER_VALIDATE_EMAIL)){
            $_SESSION['flash_error']='Please fill in all required fields with a valid email.';
            return $res->withHeader('Location','/programmes/'.$args['slug'])->withStatus(302);
        }
        $im = new InterestModel();
        if ($im->isDuplicate($email,(int)$p['id'])){
            $_SESSION['flash_warning']='You have already registered interest in this programme.';
            return $res->withHeader('Location','/programmes/'.$args['slug'])->withStatus(302);
        }
        $im->create(['first_name'=>$first,'last_name'=>$last,'email'=>$email,'phone'=>$phone,'programme_id'=>$p['id'],'student_id'=>$sid,'message'=>$msg]);
        $this->logger->info('Interest registered',['email'=>$email,'programme'=>$p['title']]);
        return $res->withHeader('Location','/interest/confirmed?programme='.urlencode($p['title']))->withStatus(302);
    }

    public function confirmed(Request $req, Response $res): Response {
        $prog = $req->getQueryParams()['programme']??'';
        $res->getBody()->write($this->view->renderConfirmed($prog));
        return $res;
    }

    public function withdrawForm(Request $req, Response $res): Response {
        $res->getBody()->write($this->view->renderWithdrawForm());
        return $res;
    }

    public function withdraw(Request $req, Response $res): Response {
        $email = filter_var(trim(((array)$req->getParsedBody())['email']??''),FILTER_SANITIZE_EMAIL);
        if (!filter_var($email,FILTER_VALIDATE_EMAIL)) { $_SESSION['flash_error']='Enter a valid email.'; return $res->withHeader('Location','/interest/withdraw')->withStatus(302); }
        $n = (new InterestModel())->deleteByEmail($email);
        if ($n) { $this->logger->info('Interest withdrawn via email', ['email' => $email, 'count' => $n]); $_SESSION['flash_success']=$n . ' registration(s) withdrawn for '.$email.'.'; }
        else { $this->logger->info('Interest withdrawal — no records found', ['email' => $email]); $_SESSION['flash_info']='No registrations found for that email.'; }
        return $res->withHeader('Location','/interest/withdraw')->withStatus(302);
    }
}
