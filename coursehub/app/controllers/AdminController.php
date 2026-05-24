<?php
declare(strict_types=1);
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class AdminController
{
    private AdminModel $admin;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->admin  = new AdminModel();
    }

    private function h(string $v): string { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }

    private function requireAdmin(Response $res): ?Response
    {
        if (empty($_SESSION['admin_id'])) {
            $_SESSION['flash_error'] = 'Please sign in as admin.';
            return $res->withHeader('Location', '/admin/login')->withStatus(302);
        }
        return null;
    }

    public function loginForm(Request $req, Response $res): Response
    {
        if (!empty($_SESSION['admin_id'])) return $res->withHeader('Location', '/admin/dashboard')->withStatus(302);
        $res->getBody()->write($this->renderLoginPage());
        return $res;
    }

    public function login(Request $req, Response $res): Response
    {
        $d    = (array)$req->getParsedBody();
        $user = $this->h($d['username'] ?? '');
        $pass = $d['password'] ?? '';
        $a    = $this->admin->findByUsername($user);
        if (!$a || !password_verify($pass, $a['password_hash'])) {
            $this->logger->warning('Admin login failed', ['username' => $user]);
            $_SESSION['flash_error'] = 'Invalid credentials.';
            return $res->withHeader('Location', '/admin/login')->withStatus(302);
        }
        session_regenerate_id(true);
        $_SESSION['admin_id']       = $a['id'];
        $_SESSION['admin_username'] = $a['username'];
        $_SESSION['flash_success']  = 'Welcome, ' . $a['username'] . '!';
        $this->logger->info('Admin login', ['username' => $user]);
        return $res->withHeader('Location', '/admin/dashboard')->withStatus(302);
    }

    public function logout(Request $req, Response $res): Response
    {
        $this->logger->info('Admin logout', ['username' => $_SESSION['admin_username'] ?? '']);
        unset($_SESSION['admin_id'], $_SESSION['admin_username']);
        $_SESSION['flash_success'] = 'Logged out successfully.';
        return $res->withHeader('Location', '/admin/login')->withStatus(302);
    }

    public function dashboard(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $pm   = new ProgrammeModule();
        $mm   = new ModuleModule();
        $sm   = new StaffModule();
        $im   = new InterestModel();
        $stats = [
            'programmes' => $pm->count(),
            'published'  => $pm->countPublished(),
            'modules'    => $mm->count(),
            'staff'      => $sm->count(),
            'registrations' => $im->count(),
        ];
        $recentRegs = $im->getAll();
        $res->getBody()->write($this->renderDashboard($stats, array_slice($recentRegs, 0, 8)));
        return $res;
    }

    // ── Programmes ────────────────────────────────────────────────────────────
    public function programmes(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $pm   = new ProgrammeModule();
        $all  = $pm->getAllProgrammes();
        $sm   = new StaffModule();
        $staff = $sm->getAllStaff();
        $res->getBody()->write($this->renderProgrammeList($all, $staff));
        return $res;
    }

    public function createProgrammeForm(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $sm   = new StaffModule();
        $mm   = new ModuleModule();
        $res->getBody()->write($this->renderProgrammeForm([], $sm->getAllStaff(), $mm->getAllModules(), []));
        return $res;
    }

    public function storeProgramme(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $d    = (array)$req->getParsedBody();
        $pm   = new ProgrammeModule();
        $id   = $pm->createProgramme([
            'title'              => $this->h($d['title'] ?? ''),
            'level'              => $d['level'] ?? 'Undergraduate',
            'description'        => $this->h($d['description'] ?? ''),
            'duration_years'     => (int)($d['duration_years'] ?? 3),
            'image_url'          => $this->h($d['image_url'] ?? ''),
            'published'          => isset($d['published']) ? 1 : 0,
            'programme_leader_id'=> ($d['programme_leader_id'] ?? '') ?: null,
        ]);
        $pm->syncModules($id, $d['modules'] ?? []);
        $this->logger->info('Programme created', ['id' => $id, 'title' => $this->h($d['title'] ?? '')]);
        $_SESSION['flash_success'] = 'Programme created.';
        return $res->withHeader('Location', '/admin/programmes')->withStatus(302);
    }

    public function editProgrammeForm(Request $req, Response $res, array $args): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $pm   = new ProgrammeModule();
        $p    = $pm->getProgrammeById((int)$args['id']);
        if (!$p) { $_SESSION['flash_error'] = 'Not found.'; return $res->withHeader('Location', '/admin/programmes')->withStatus(302); }
        $sm   = new StaffModule();
        $mm   = new ModuleModule();
        $db   = getDatabase();
        $cur  = $db->prepare('SELECT module_id FROM programme_modules WHERE programme_id=?');
        $cur->execute([$p['id']]);
        $currentMods = array_column($cur->fetchAll(), 'module_id');
        $res->getBody()->write($this->renderProgrammeForm($p, $sm->getAllStaff(), $mm->getAllModules(), $currentMods));
        return $res;
    }

    public function updateProgramme(Request $req, Response $res, array $args): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $id   = (int)$args['id'];
        $d    = (array)$req->getParsedBody();
        $pm   = new ProgrammeModule();
        $pm->updateProgramme($id, [
            'title'              => $this->h($d['title'] ?? ''),
            'level'              => $d['level'] ?? 'Undergraduate',
            'description'        => $this->h($d['description'] ?? ''),
            'duration_years'     => (int)($d['duration_years'] ?? 3),
            'image_url'          => $this->h($d['image_url'] ?? ''),
            'published'          => isset($d['published']) ? 1 : 0,
            'programme_leader_id'=> ($d['programme_leader_id'] ?? '') ?: null,
        ]);
        $pm->syncModules($id, $d['modules'] ?? []);
        $this->logger->info('Programme updated', ['id' => $id, 'title' => $this->h($d['title'] ?? '')]);
        $_SESSION['flash_success'] = 'Programme updated.';
        return $res->withHeader('Location', '/admin/programmes')->withStatus(302);
    }

    public function deleteProgramme(Request $req, Response $res, array $args): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        (new ProgrammeModule())->deleteProgramme((int)$args['id']);
        $this->logger->info('Programme deleted', ['id' => (int)$args['id']]);
        $_SESSION['flash_success'] = 'Programme deleted.';
        return $res->withHeader('Location', '/admin/programmes')->withStatus(302);
    }

    public function togglePublish(Request $req, Response $res, array $args): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        (new ProgrammeModule())->togglePublished((int)$args['id']);
        $this->logger->info('Programme publish toggled', ['id' => (int)$args['id']]);
        $_SESSION['flash_success'] = 'Published status updated.';
        return $res->withHeader('Location', '/admin/programmes')->withStatus(302);
    }

    // ── Modules ───────────────────────────────────────────────────────────────
    public function modules(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $mm    = new ModuleModule();
        $sm    = new StaffModule();
        $res->getBody()->write($this->renderModuleList($mm->getAllModules(), $sm->getAllStaff()));
        return $res;
    }

    public function createModuleForm(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $res->getBody()->write($this->renderModuleForm([], (new StaffModule())->getAllStaff()));
        return $res;
    }

    public function storeModule(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $d  = (array)$req->getParsedBody();
        (new ModuleModule())->createModule([
            'title'           => $this->h($d['title'] ?? ''),
            'code'            => $this->h($d['code'] ?? ''),
            'description'     => $this->h($d['description'] ?? ''),
            'credits'         => (int)($d['credits'] ?? 20),
            'year_of_study'   => (int)($d['year_of_study'] ?? 1),
            'image_url'       => $this->h($d['image_url'] ?? ''),
            'module_leader_id'=> ($d['module_leader_id'] ?? '') ?: null,
        ]);
        $this->logger->info('Module created', ['title' => $this->h($d['title'] ?? ''), 'code' => $this->h($d['code'] ?? '')]);
        $_SESSION['flash_success'] = 'Module created.';
        return $res->withHeader('Location', '/admin/modules')->withStatus(302);
    }

    public function editModuleForm(Request $req, Response $res, array $args): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $m = (new ModuleModule())->getModuleById((int)$args['id']);
        if (!$m) { $_SESSION['flash_error'] = 'Not found.'; return $res->withHeader('Location', '/admin/modules')->withStatus(302); }
        $res->getBody()->write($this->renderModuleForm($m, (new StaffModule())->getAllStaff()));
        return $res;
    }

    public function updateModule(Request $req, Response $res, array $args): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $d  = (array)$req->getParsedBody();
        (new ModuleModule())->updateModule((int)$args['id'], [
            'title'           => $this->h($d['title'] ?? ''),
            'code'            => $this->h($d['code'] ?? ''),
            'description'     => $this->h($d['description'] ?? ''),
            'credits'         => (int)($d['credits'] ?? 20),
            'year_of_study'   => (int)($d['year_of_study'] ?? 1),
            'image_url'       => $this->h($d['image_url'] ?? ''),
            'module_leader_id'=> ($d['module_leader_id'] ?? '') ?: null,
        ]);
        $this->logger->info('Module updated', ['id' => (int)$args['id'], 'title' => $this->h($d['title'] ?? '')]);
        $_SESSION['flash_success'] = 'Module updated.';
        return $res->withHeader('Location', '/admin/modules')->withStatus(302);
    }

    public function deleteModule(Request $req, Response $res, array $args): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        (new ModuleModule())->deleteModule((int)$args['id']);
        $this->logger->info('Module deleted', ['id' => (int)$args['id']]);
        $_SESSION['flash_success'] = 'Module deleted.';
        return $res->withHeader('Location', '/admin/modules')->withStatus(302);
    }

    // ── Staff ─────────────────────────────────────────────────────────────────
    public function staff(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $res->getBody()->write($this->renderStaffList((new StaffModule())->getAllStaff()));
        return $res;
    }

    public function createStaffForm(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $res->getBody()->write($this->renderStaffForm([]));
        return $res;
    }

    public function storeStaff(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $d = (array)$req->getParsedBody();
        if (empty($d['password'])) { $_SESSION['flash_error'] = 'Password is required for new staff.'; return $res->withHeader('Location', '/admin/staff/create')->withStatus(302); }
        (new StaffModule())->createStaff([
            'name'       => $this->h($d['name'] ?? ''),
            'role'       => $this->h($d['role'] ?? ''),
            'department' => $this->h($d['department'] ?? ''),
            'email'      => filter_var(trim($d['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'password'   => $d['password'],
            'bio'        => $this->h($d['bio'] ?? ''),
            'phone'      => $this->h($d['phone'] ?? ''),
            'office'     => $this->h($d['office'] ?? ''),
            'photo_url'  => $this->h($d['photo_url'] ?? ''),
        ]);
        $this->logger->info('Staff created', ['name' => $this->h($d['name'] ?? ''), 'email' => trim($d['email'] ?? '')]);
        $_SESSION['flash_success'] = 'Staff member created.';
        return $res->withHeader('Location', '/admin/staff')->withStatus(302);
    }

    public function editStaffForm(Request $req, Response $res, array $args): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $s = (new StaffModule())->getStaffById((int)$args['id']);
        if (!$s) { $_SESSION['flash_error'] = 'Not found.'; return $res->withHeader('Location', '/admin/staff')->withStatus(302); }
        $res->getBody()->write($this->renderStaffForm($s));
        return $res;
    }

    public function updateStaff(Request $req, Response $res, array $args): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $id  = (int)$args['id'];
        $d   = (array)$req->getParsedBody();
        $sm  = new StaffModule();
        $sm->updateStaff($id, [
            'name'       => $this->h($d['name'] ?? ''),
            'role'       => $this->h($d['role'] ?? ''),
            'department' => $this->h($d['department'] ?? ''),
            'email'      => filter_var(trim($d['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'bio'        => $this->h($d['bio'] ?? ''),
            'phone'      => $this->h($d['phone'] ?? ''),
            'office'     => $this->h($d['office'] ?? ''),
            'photo_url'  => $this->h($d['photo_url'] ?? ''),
        ]);
        if (!empty($d['password'])) $sm->updatePassword($id, password_hash($d['password'], PASSWORD_BCRYPT));
        $this->logger->info('Staff updated', ['id' => $id, 'name' => $this->h($d['name'] ?? '')]);
        $_SESSION['flash_success'] = 'Staff updated.';
        return $res->withHeader('Location', '/admin/staff')->withStatus(302);
    }

    public function deleteStaff(Request $req, Response $res, array $args): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        (new StaffModule())->deleteStaff((int)$args['id']);
        $this->logger->info('Staff deleted', ['id' => (int)$args['id']]);
        $_SESSION['flash_success'] = 'Staff member deleted.';
        return $res->withHeader('Location', '/admin/staff')->withStatus(302);
    }

    // ── Registrations ─────────────────────────────────────────────────────────
    public function registrations(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $regs = (new InterestModel())->getAll();
        $res->getBody()->write($this->renderRegistrations($regs));
        return $res;
    }

    public function exportRegistrations(Request $req, Response $res): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        $regs = (new InterestModel())->getAll();
        $csv  = "ID,First Name,Last Name,Email,Phone,Programme,Registered At\n";
        foreach ($regs as $r2) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', [
                $r2['id'], $r2['first_name'], $r2['last_name'], $r2['email'],
                $r2['phone'] ?? '', $r2['programme_title'], $r2['created_at']
            ])) . "\n";
        }
        $response = $res->withHeader('Content-Type', 'text/csv')->withHeader('Content-Disposition', 'attachment;filename=registrations.csv');
        $response->getBody()->write($csv);
        return $response;
    }

    public function deleteRegistration(Request $req, Response $res, array $args): Response
    {
        if ($r = $this->requireAdmin($res)) return $r;
        (new InterestModel())->deleteById((int)$args['id']);
        $this->logger->info('Registration deleted by admin', ['id' => (int)$args['id']]);
        $_SESSION['flash_success'] = 'Registration deleted.';
        return $res->withHeader('Location', '/admin/registrations')->withStatus(302);
    }

    // ── Admin Views ───────────────────────────────────────────────────────────
    private function adminNav(string $active = ''): string
    {
        $user  = htmlspecialchars($_SESSION['admin_username'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
        $links = [
            '/admin/dashboard'     => ['fa-tachometer-alt', 'Dashboard'],
            '/admin/programmes'    => ['fa-graduation-cap', 'Programmes'],
            '/admin/modules'       => ['fa-cubes', 'Modules'],
            '/admin/staff'         => ['fa-users', 'Staff'],
            '/admin/registrations' => ['fa-list', 'Registrations'],
        ];
        $html  = '<aside style="width:240px;flex-shrink:0;background:var(--navy);min-height:100vh;display:flex;flex-direction:column">';
        $html .= '<div style="padding:1.5rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.08)">';
        $html .= '<div style="font-family:Merriweather,serif;color:#fff;font-size:1rem;font-weight:700"><span style="color:var(--accent)">Course</span>Hub</div>';
        $html .= '<p style="color:rgba(255,255,255,.5);font-size:.75rem;margin-top:.25rem">Admin Panel</p></div>';
        $html .= '<nav style="padding:1rem 0;flex:1">';
        foreach ($links as $href => [$icon, $label]) {
            $isActive = $active === $href;
            $style    = $isActive
                ? 'background:rgba(255,255,255,.1);color:#fff;border-right:3px solid var(--accent)'
                : 'color:rgba(255,255,255,.6)';
            $html .= '<a href="' . $href . '" style="display:flex;align-items:center;gap:.75rem;padding:.7rem 1.25rem;font-size:.875rem;font-weight:500;text-decoration:none;transition:all .15s;' . $style . '">';
            $html .= '<i class="fa ' . $icon . '" style="width:16px;text-align:center"></i>' . $label . '</a>';
        }
        $html .= '</nav>';
        $html .= '<div style="padding:1rem 1.25rem;border-top:1px solid rgba(255,255,255,.08)">';
        $html .= '<div style="color:rgba(255,255,255,.6);font-size:.8rem;margin-bottom:.5rem"><i class="fa fa-user"></i> ' . $user . '</div>';
        $html .= '<a href="/" target="_blank" style="display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.5);font-size:.78rem;margin-bottom:.3rem;text-decoration:none"><i class="fa fa-external-link-alt"></i> View Site</a>';
        $html .= '<a href="/admin/logout" style="display:flex;align-items:center;gap:.4rem;color:rgba(255,255,255,.5);font-size:.78rem;text-decoration:none"><i class="fa fa-sign-out-alt"></i> Sign Out</a>';
        $html .= '</div></aside>';
        return $html;
    }

    private function adminWrap(string $active, string $title, string $content): string
    {
        $html  = Layout::head($title . ' — Admin');
        $html .= '<div style="display:flex;min-height:100vh">';
        $html .= $this->adminNav($active);
        $html .= '<main id="main" style="flex:1;overflow-x:auto"><div style="padding:2rem">';
        $html .= Layout::flash();
        $html .= $content;
        $html .= '</div></main></div>';
        $html .= '</body></html>';
        return $html;
    }

    private function renderLoginPage(): string
    {
        $html  = Layout::head('Admin Sign In');
        $html .= Layout::nav();
        $html .= '<main id="main"><div class="container page-pad" style="max-width:420px;margin-top:3rem">';
        $html .= '<div class="card"><div class="card-header" style="background:var(--navy)"><h1 style="color:#fff;font-size:1.1rem"><i class="fa fa-lock"></i> Admin Panel</h1></div>';
        $html .= '<div class="card-body">';
        $html .= Layout::flash();
        $html .= '<form action="/admin/login" method="POST" novalidate>';
        $html .= '<div class="form-group"><label for="au">Username <span class="req">*</span></label><input type="text" id="au" name="username" class="form-control" required autofocus autocomplete="username"></div>';
        $html .= '<div class="form-group"><label for="ap">Password <span class="req">*</span></label>';
        $html .= '<div class="input-wrap"><input type="password" id="ap" name="password" class="form-control" required autocomplete="current-password">';
        $html .= '<button type="button" class="input-icon" data-toggle-pw><i class="fa fa-eye"></i></button></div></div>';
        $html .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-sign-in-alt"></i> Sign In</button>';
        $html .= '</form></div></div>';
        $html .= '</div></main>' . Layout::footer();
        return $html;
    }

    private function renderDashboard(array $stats, array $recentRegs): string
    {
        $c  = '<h1 style="font-family:Merriweather,serif;font-size:1.5rem;color:var(--navy);margin-bottom:1.5rem"><i class="fa fa-tachometer-alt" style="color:var(--accent)"></i> Dashboard</h1>';
        $c .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1.25rem;margin-bottom:2rem">';
        foreach ([
            ['fa-graduation-cap', $stats['programmes'], 'Total Programmes', 'var(--navy)'],
            ['fa-check-circle',   $stats['published'],  'Published',         'var(--success)'],
            ['fa-cubes',          $stats['modules'],    'Modules',           'var(--blue)'],
            ['fa-users',          $stats['staff'],      'Staff',             '#7c3aed'],
            ['fa-list',           $stats['registrations'],'Registrations',   'var(--accent-dark)'],
        ] as [$icon, $num, $lbl, $col]) {
            $c .= '<div class="card card-body text-center">';
            $c .= '<i class="fa ' . $icon . '" style="font-size:1.5rem;color:' . $col . ';margin-bottom:.4rem"></i>';
            $c .= '<div style="font-size:2rem;font-weight:800;color:var(--navy)">' . $num . '</div>';
            $c .= '<div class="text-xs text-muted">' . $lbl . '</div></div>';
        }
        $c .= '</div>';

        // Quick links
        $c .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem">';
        foreach ([
            ['/admin/programmes/create', 'fa-plus', 'Add Programme'],
            ['/admin/modules/create',    'fa-plus', 'Add Module'],
            ['/admin/staff/create',      'fa-plus', 'Add Staff'],
            ['/admin/registrations/export', 'fa-download', 'Export CSV'],
        ] as [$href, $icon, $lbl]) {
            $c .= '<a href="' . $href . '" class="btn btn-outline" style="justify-content:center"><i class="fa ' . $icon . '"></i> ' . $lbl . '</a>';
        }
        $c .= '</div>';

        // Recent registrations
        $c .= '<div class="card"><div class="card-header" style="display:flex;justify-content:space-between;align-items:center">';
        $c .= '<h2 style="font-size:1rem;font-weight:700;color:var(--navy)"><i class="fa fa-list"></i> Recent Registrations</h2>';
        $c .= '<a href="/admin/registrations" class="btn btn-ghost btn-sm">View All</a></div>';
        $c .= '<div class="table-wrap" style="border-radius:0;box-shadow:none;border:none">';
        $c .= '<table><thead><tr><th>Name</th><th>Email</th><th>Programme</th><th>Date</th></tr></thead><tbody>';
        foreach ($recentRegs as $r) {
            $c .= '<tr><td>' . htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) . '</td>';
            $c .= '<td class="text-muted">' . htmlspecialchars($r['email']) . '</td>';
            $c .= '<td>' . htmlspecialchars($r['programme_title'] ?? '') . '</td>';
            $c .= '<td class="text-muted text-sm">' . date('d M Y', strtotime($r['created_at'])) . '</td></tr>';
        }
        if (empty($recentRegs)) $c .= '<tr><td colspan="4" class="text-center text-muted" style="padding:2rem">No registrations yet.</td></tr>';
        $c .= '</tbody></table></div></div>';

        return $this->adminWrap('/admin/dashboard', 'Dashboard', $c);
    }

    private function renderProgrammeList(array $progs, array $staff): string
    {
        $c  = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.5rem">';
        $c .= '<h1 style="font-family:Merriweather,serif;font-size:1.5rem;color:var(--navy)"><i class="fa fa-graduation-cap" style="color:var(--accent)"></i> Programmes</h1>';
        $c .= '<a href="/admin/programmes/create" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> New Programme</a></div>';
        $c .= '<div class="table-wrap"><table><thead><tr><th>Title</th><th>Level</th><th>Leader</th><th>Modules</th><th>Regs</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
        foreach ($progs as $p) {
            $b   = $p['level'] === 'Undergraduate' ? 'badge-ug' : 'badge-pg';
            $pub = $p['published'] ? '<span class="badge badge-pub">Published</span>' : '<span class="badge badge-draft">Draft</span>';
            $c  .= '<tr>';
            $c  .= '<td><strong>' . htmlspecialchars($p['title']) . '</strong></td>';
            $c  .= '<td><span class="badge ' . $b . '">' . htmlspecialchars($p['level']) . '</span></td>';
            $c  .= '<td class="text-sm text-muted">' . htmlspecialchars($p['leader_name'] ?? '—') . '</td>';
            $c  .= '<td class="text-center">' . (int)$p['module_count'] . '</td>';
            $c  .= '<td class="text-center">' . (int)$p['reg_count'] . '</td>';
            $c  .= '<td>' . $pub . '</td>';
            $c  .= '<td><div class="flex gap-1">';
            // PREVIEW opens in new tab — admin stays on admin
            if ($p['published']) {
                $db2  = getDatabase();
                $slug = $db2->prepare('SELECT slug FROM programmes WHERE id=?');
                $slug->execute([$p['id']]);
                $s = ($slug->fetchColumn()) ?: '';
                $c .= '<a href="/programmes/' . htmlspecialchars($s) . '" target="_blank" class="btn btn-ghost btn-sm" title="Preview (opens in new tab)"><i class="fa fa-external-link-alt"></i></a>';
            }
            $c .= '<a href="/admin/programmes/' . (int)$p['id'] . '/edit" class="btn btn-outline btn-sm"><i class="fa fa-edit"></i></a>';
            $c .= '<form action="/admin/programmes/' . (int)$p['id'] . '/toggle" method="POST" style="display:inline">';
            $c .= '<button type="submit" class="btn btn-ghost btn-sm" title="' . ($p['published'] ? 'Unpublish' : 'Publish') . '">';
            $c .= '<i class="fa ' . ($p['published'] ? 'fa-eye-slash' : 'fa-eye') . '"></i></button></form>';
            $c .= '<form action="/admin/programmes/' . (int)$p['id'] . '/delete" method="POST" style="display:inline" onsubmit="return confirm(\'Delete this programme?\')">';
            $c .= '<button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button></form>';
            $c .= '</div></td></tr>';
        }
        if (empty($progs)) $c .= '<tr><td colspan="7" class="text-center text-muted" style="padding:2rem">No programmes yet.</td></tr>';
        $c .= '</tbody></table></div>';
        return $this->adminWrap('/admin/programmes', 'Programmes', $c);
    }

    private function renderProgrammeForm(array $p, array $staff, array $modules, array $currentMods): string
    {
        $isEdit = !empty($p);
        $title  = $isEdit ? 'Edit Programme' : 'Create Programme';
        $action = $isEdit ? '/admin/programmes/' . (int)$p['id'] . '/edit' : '/admin/programmes/create';

        // Build module picker HTML (inside the same form)
        $byYear = [];
        foreach ($modules as $m) $byYear[$m['year_of_study']][] = $m;
        ksort($byYear);
        $picker = '';
        foreach ($byYear as $yr => $mods) {
            $label = $yr <= 3 ? 'Year ' . $yr : 'Postgraduate';
            $picker .= '<p style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin:.75rem 0 .4rem">' . $label . '</p>';
            foreach ($mods as $m) {
                $chk     = in_array($m['id'], $currentMods) ? ' checked' : '';
                $mtitle  = htmlspecialchars($m['title']);
                $mcode   = htmlspecialchars($m['code']);
                $picker .= '<label style="display:flex;align-items:center;gap:.5rem;padding:.35rem .5rem;border-radius:6px;cursor:pointer;font-size:.85rem">';
                $picker .= '<input type="checkbox" name="modules[]" value="' . (int)$m['id'] . '"' . $chk . ' style="width:15px;height:15px">';
                $picker .= '<span>' . $mtitle . '</span>';
                $picker .= '<code style="font-size:.7rem;color:var(--muted)">' . $mcode . '</code></label>';
            }
        }

        $previewBtn = ($isEdit && !empty($p['slug']))
            ? '<a href="/programmes/' . htmlspecialchars($p['slug']) . '" target="_blank" class="btn btn-outline btn-sm" style="margin-left:auto"><i class="fa fa-external-link-alt"></i> Preview</a>'
            : '';

        $levelOpts = '';
        foreach (['Undergraduate','Postgraduate'] as $lv) {
            $sel = ($p['level'] ?? '') === $lv ? ' selected' : '';
            $levelOpts .= '<option value="' . $lv . '"' . $sel . '>' . $lv . '</option>';
        }
        $staffOpts = '<option value="">— No leader —</option>';
        foreach ($staff as $s) {
            $sel = ((int)($p['programme_leader_id'] ?? 0)) === (int)$s['id'] ? ' selected' : '';
            $staffOpts .= '<option value="' . (int)$s['id'] . '"' . $sel . '>' . htmlspecialchars($s['name']) . '</option>';
        }
        $pubChk  = !empty($p['published']) ? ' checked' : '';
        $btnTxt  = $isEdit ? 'Save Changes' : 'Create Programme';
        $dur     = (int)($p['duration_years'] ?? 3);
        $desc    = htmlspecialchars($p['description'] ?? '');
        $img     = htmlspecialchars($p['image_url'] ?? '');
        $ttl     = htmlspecialchars($p['title'] ?? '');

        $c  = '<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem">';
        $c .= '<a href="/admin/programmes" class="btn btn-ghost btn-sm"><i class="fa fa-arrow-left"></i></a>';
        $c .= '<h1 style="font-family:Merriweather,serif;font-size:1.5rem;color:var(--navy)">' . $title . '</h1>';
        $c .= $previewBtn . '</div>';

        $c .= '<form action="' . $action . '" method="POST" novalidate>';
        $c .= '<div style="display:grid;grid-template-columns:1fr 340px;gap:2rem;align-items:start">';

        // Left: details card
        $c .= '<div class="card"><div class="card-header"><h2>Programme Details</h2></div><div class="card-body">';
        $c .= '<div class="form-group"><label>Title <span class="req">*</span></label><input type="text" name="title" class="form-control" value="' . $ttl . '" required></div>';
        $c .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">';
        $c .= '<div class="form-group"><label>Level <span class="req">*</span></label><select name="level" class="form-control">' . $levelOpts . '</select></div>';
        $c .= '<div class="form-group"><label>Duration (years)</label><input type="number" name="duration_years" class="form-control" min="1" max="5" value="' . $dur . '"></div></div>';
        $c .= '<div class="form-group"><label>Programme Leader</label><select name="programme_leader_id" class="form-control">' . $staffOpts . '</select></div>';
        $c .= '<div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="6">' . $desc . '</textarea></div>';
        $c .= '<div class="form-group"><label>Image URL</label><input type="url" name="image_url" class="form-control" value="' . $img . '" placeholder="https://…"></div>';
        $c .= '<div class="form-group"><label style="display:flex;align-items:center;gap:.5rem;cursor:pointer"><input type="checkbox" name="published" value="1"' . $pubChk . '> Publish this programme</label></div>';
        $c .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-save"></i> ' . $btnTxt . '</button>';
        $c .= '</div></div>';

        // Right: module picker (inside same form)
        $c .= '<div class="card"><div class="card-header"><h2><i class="fa fa-cubes"></i> Assign Modules</h2></div>';
        $c .= '<div class="card-body" style="max-height:620px;overflow-y:auto">' . $picker . '</div></div>';

        $c .= '</div></form>';
        return $this->adminWrap($isEdit ? '/admin/programmes' : '/admin/programmes', $title, $c);
    }

    private function renderModuleList(array $modules, array $staff): string
    {
        $c  = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.5rem">';
        $c .= '<h1 style="font-family:Merriweather,serif;font-size:1.5rem;color:var(--navy)"><i class="fa fa-cubes" style="color:var(--accent)"></i> Modules</h1>';
        $c .= '<a href="/admin/modules/create" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> New Module</a></div>';
        $c .= '<div class="table-wrap"><table><thead><tr><th>Code</th><th>Title</th><th>Year</th><th>Credits</th><th>Leader</th><th>Programmes</th><th>Actions</th></tr></thead><tbody>';
        foreach ($modules as $m) {
            $c .= '<tr>';
            $c .= '<td><code style="font-family:JetBrains Mono,monospace;font-size:.8rem">' . htmlspecialchars($m['code']) . '</code></td>';
            $c .= '<td><strong>' . htmlspecialchars($m['title']) . '</strong></td>';
            $c .= '<td class="text-center">Yr ' . (int)$m['year_of_study'] . '</td>';
            $c .= '<td class="text-center">' . (int)$m['credits'] . '</td>';
            $c .= '<td class="text-sm text-muted">' . htmlspecialchars($m['leader_name'] ?? '—') . '</td>';
            $pc = (int)($m['prog_count'] ?? 0);
            $c .= '<td class="text-center">' . ($pc > 1 ? '<span class="badge badge-shared">' . $pc . '</span>' : $pc) . '</td>';
            $c .= '<td><div class="flex gap-1">';
            $c .= '<a href="/modules/' . (int)$m['id'] . '" target="_blank" class="btn btn-ghost btn-sm" title="Preview"><i class="fa fa-external-link-alt"></i></a>';
            $c .= '<a href="/admin/modules/' . (int)$m['id'] . '/edit" class="btn btn-outline btn-sm"><i class="fa fa-edit"></i></a>';
            $c .= '<form action="/admin/modules/' . (int)$m['id'] . '/delete" method="POST" style="display:inline" onsubmit="return confirm(\'Delete this module?\')">';
            $c .= '<button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button></form>';
            $c .= '</div></td></tr>';
        }
        if (empty($modules)) $c .= '<tr><td colspan="7" class="text-center text-muted" style="padding:2rem">No modules yet.</td></tr>';
        $c .= '</tbody></table></div>';
        return $this->adminWrap('/admin/modules', 'Modules', $c);
    }

    private function renderModuleForm(array $m, array $staff): string
    {
        $isEdit = !empty($m);
        $title  = $isEdit ? 'Edit Module' : 'Create Module';
        $action = $isEdit ? '/admin/modules/' . (int)$m['id'] . '/edit' : '/admin/modules/create';
        $c  = '<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem">';
        $c .= '<a href="/admin/modules" class="btn btn-ghost btn-sm"><i class="fa fa-arrow-left"></i></a>';
        $c .= '<h1 style="font-family:Merriweather,serif;font-size:1.5rem;color:var(--navy)">' . $title . '</h1>';
        if ($isEdit) $c .= '<a href="/modules/' . (int)$m['id'] . '" target="_blank" class="btn btn-outline btn-sm" style="margin-left:auto"><i class="fa fa-external-link-alt"></i> Preview</a>';
        $c .= '</div>';
        $c .= '<div class="card" style="max-width:700px"><div class="card-body">';
        $c .= '<form action="' . $action . '" method="POST" novalidate>';
        $c .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">';
        $c .= '<div class="form-group"><label>Title <span class="req">*</span></label><input type="text" name="title" class="form-control" value="' . htmlspecialchars($m['title'] ?? '') . '" required></div>';
        $c .= '<div class="form-group"><label>Code <span class="req">*</span></label><input type="text" name="code" class="form-control" value="' . htmlspecialchars($m['code'] ?? '') . '" required placeholder="CS101"></div>';
        $c .= '<div class="form-group"><label>Year of Study</label><select name="year_of_study" class="form-control">';
        for ($y = 1; $y <= 5; $y++) {
            $sel = ((int)($m['year_of_study'] ?? 1)) === $y ? ' selected' : '';
            $lbl = $y <= 3 ? 'Year ' . $y . ' (UG)' : ($y === 4 ? 'PG Year 1' : 'PG Year 2');
            $c .= '<option value="' . $y . '"' . $sel . '>' . $lbl . '</option>';
        }
        $c .= '</select></div>';
        $c .= '<div class="form-group"><label>Credits</label><input type="number" name="credits" class="form-control" min="5" max="120" value="' . (int)($m['credits'] ?? 20) . '"></div></div>';
        $c .= '<div class="form-group"><label>Module Leader</label><select name="module_leader_id" class="form-control"><option value="">— No leader —</option>';
        foreach ($staff as $s) {
            $sel = ((int)($m['module_leader_id'] ?? 0)) === (int)$s['id'] ? ' selected' : '';
            $c .= '<option value="' . (int)$s['id'] . '"' . $sel . '>' . htmlspecialchars($s['name']) . '</option>';
        }
        $c .= '</select></div>';
        $c .= '<div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="8" placeholder="Write a detailed module overview…">' . htmlspecialchars($m['description'] ?? '') . '</textarea></div>';
        $c .= '<div class="form-group"><label>Image URL</label><input type="url" name="image_url" class="form-control" value="' . htmlspecialchars($m['image_url'] ?? '') . '" placeholder="https://…"></div>';
        $c .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-save"></i> ' . ($isEdit ? 'Save Changes' : 'Create Module') . '</button>';
        $c .= '</form></div></div>';
        return $this->adminWrap($isEdit ? '/admin/modules' : '/admin/modules', $title, $c);
    }

    private function renderStaffList(array $staff): string
    {
        $c  = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.5rem">';
        $c .= '<h1 style="font-family:Merriweather,serif;font-size:1.5rem;color:var(--navy)"><i class="fa fa-users" style="color:var(--accent)"></i> Staff</h1>';
        $c .= '<a href="/admin/staff/create" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> New Staff</a></div>';
        $c .= '<div class="table-wrap"><table><thead><tr><th>Name</th><th>Role</th><th>Department</th><th>Email</th><th>Modules</th><th>Actions</th></tr></thead><tbody>';
        foreach ($staff as $s) {
            $c .= '<tr><td><strong>' . htmlspecialchars($s['name']) . '</strong></td>';
            $c .= '<td class="text-sm">' . htmlspecialchars($s['role']) . '</td>';
            $c .= '<td class="text-sm text-muted">' . htmlspecialchars($s['department']) . '</td>';
            $c .= '<td class="text-sm"><a href="mailto:' . htmlspecialchars($s['email']) . '">' . htmlspecialchars($s['email']) . '</a></td>';
            $c .= '<td class="text-center">' . (int)($s['module_count'] ?? 0) . '</td>';
            $c .= '<td><div class="flex gap-1">';
            $c .= '<a href="/staff/' . (int)$s['id'] . '" target="_blank" class="btn btn-ghost btn-sm" title="View profile"><i class="fa fa-external-link-alt"></i></a>';
            $c .= '<a href="/admin/staff/' . (int)$s['id'] . '/edit" class="btn btn-outline btn-sm"><i class="fa fa-edit"></i></a>';
            $c .= '<form action="/admin/staff/' . (int)$s['id'] . '/delete" method="POST" style="display:inline" onsubmit="return confirm(\'Delete this staff member?\')">';
            $c .= '<button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button></form>';
            $c .= '</div></td></tr>';
        }
        if (empty($staff)) $c .= '<tr><td colspan="6" class="text-center text-muted" style="padding:2rem">No staff yet.</td></tr>';
        $c .= '</tbody></table></div>';
        return $this->adminWrap('/admin/staff', 'Staff', $c);
    }

    private function renderStaffForm(array $s): string
    {
        $isEdit = !empty($s);
        $title  = $isEdit ? 'Edit Staff' : 'Create Staff';
        $action = $isEdit ? '/admin/staff/' . (int)$s['id'] . '/edit' : '/admin/staff/create';
        $c  = '<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1.5rem">';
        $c .= '<a href="/admin/staff" class="btn btn-ghost btn-sm"><i class="fa fa-arrow-left"></i></a>';
        $c .= '<h1 style="font-family:Merriweather,serif;font-size:1.5rem;color:var(--navy)">' . $title . '</h1></div>';
        $c .= '<div class="card" style="max-width:700px"><div class="card-body">';
        $c .= '<form action="' . $action . '" method="POST" novalidate>';
        $c .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">';
        foreach ([
            ['name', 'Full Name', 'text', true],
            ['email', 'Email Address', 'email', true],
            ['role', 'Job Title', 'text', true],
            ['department', 'Department', 'text', true],
            ['office', 'Office / Room', 'text', false],
            ['phone', 'Phone Extension', 'text', false],
        ] as [$n, $l, $t, $req]) {
            $v = htmlspecialchars($s[$n] ?? '', ENT_QUOTES, 'UTF-8');
            $c .= '<div class="form-group"><label>' . $l . ($req ? ' <span class="req">*</span>' : '') . '</label>';
            $c .= '<input type="' . $t . '" name="' . $n . '" class="form-control" value="' . $v . '"' . ($req ? ' required' : '') . '></div>';
        }
        $c .= '</div>';
        $c .= '<div class="form-group"><label>Profile Photo URL</label><input type="url" name="photo_url" class="form-control" value="' . htmlspecialchars($s['photo_url'] ?? '') . '" placeholder="https://…"></div>';
        $c .= '<div class="form-group"><label>Biography</label><textarea name="bio" class="form-control" rows="5">' . htmlspecialchars($s['bio'] ?? '') . '</textarea></div>';
        $c .= '<div class="form-group"><label>' . ($isEdit ? 'New Password (leave blank to keep current)' : 'Password') . ($isEdit ? '' : ' <span class="req">*</span>') . '</label>';
        $c .= '<div class="input-wrap"><input type="password" name="password" class="form-control"' . (!$isEdit ? ' required' : '') . '>';
        $c .= '<button type="button" class="input-icon" data-toggle-pw><i class="fa fa-eye"></i></button></div></div>';
        $c .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-save"></i> ' . ($isEdit ? 'Save Changes' : 'Create Staff Member') . '</button>';
        $c .= '</form></div></div>';
        return $this->adminWrap($isEdit ? '/admin/staff' : '/admin/staff', $title, $c);
    }

    private function renderRegistrations(array $regs): string
    {
        $c  = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.5rem">';
        $c .= '<h1 style="font-family:Merriweather,serif;font-size:1.5rem;color:var(--navy)"><i class="fa fa-list" style="color:var(--accent)"></i> Registrations <span style="font-size:1rem;color:var(--muted)">(' . count($regs) . ')</span></h1>';
        $c .= '<a href="/admin/registrations/export" class="btn btn-success btn-sm"><i class="fa fa-download"></i> Export CSV</a></div>';
        $c .= '<div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Programme</th><th>Date</th><th>Action</th></tr></thead><tbody>';
        foreach ($regs as $r) {
            $c .= '<tr>';
            $c .= '<td><strong>' . htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) . '</strong></td>';
            $c .= '<td><a href="mailto:' . htmlspecialchars($r['email']) . '">' . htmlspecialchars($r['email']) . '</a></td>';
            $c .= '<td class="text-muted text-sm">' . htmlspecialchars($r['phone'] ?? '—') . '</td>';
            $c .= '<td>' . htmlspecialchars($r['programme_title'] ?? '') . '</td>';
            $c .= '<td class="text-muted text-sm">' . date('d M Y, H:i', strtotime($r['created_at'])) . '</td>';
            $c .= '<td>';
            $c .= '<form action="/admin/registrations/' . (int)$r['id'] . '/delete" method="POST" style="display:inline" onsubmit="return confirm(\'Delete this registration?\')">';
            $c .= '<button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button></form>';
            $c .= '</td></tr>';
        }
        if (empty($regs)) $c .= '<tr><td colspan="6" class="text-center text-muted" style="padding:2rem">No registrations yet.</td></tr>';
        $c .= '</tbody></table></div>';
        return $this->adminWrap('/admin/registrations', 'Registrations', $c);
    }
}