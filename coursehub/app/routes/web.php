<?php
declare(strict_types=1);
use Psr\Log\LoggerInterface;
use Slim\App;

function registerRoutes(App $app, LoggerInterface $logger): void
{
    $pc  = new ProgrammeController($logger);
    $sc  = new StaffController($logger);
    $mc  = new ModuleController(new ModuleModule(), new ModuleView(), $logger);
    $ac  = new AdminController($logger);
    $sac = new StudentAuthController($logger);

    // ── Homepage ──────────────────────────────────────────────────────────────
    $app->get('/', function($req,$res) use ($logger) {
        $logger->info('Homepage viewed');
        $pm  = new ProgrammeModule(); $mm = new ModuleModule(); $sm = new StaffModule();
        $stats = ['programmes'=>$pm->countPublished(),'modules'=>$mm->count(),'staff'=>$sm->count()];
        $res->getBody()->write((new HomeView())->render($stats, $pm->getAllPublishedProgrammes()));
        return $res;
    });

    // ── Student auth ──────────────────────────────────────────────────────────
    $app->get( '/register',        fn($q,$r)   => $sac->registerForm($q,$r));
    $app->post('/register',        fn($q,$r)   => $sac->register($q,$r));
    $app->get( '/login',           fn($q,$r)   => $sac->loginForm($q,$r));
    $app->post('/login',           fn($q,$r)   => $sac->login($q,$r));
    $app->get( '/logout',          fn($q,$r)   => $sac->logout($q,$r));
    $app->get( '/forgot-password',      fn($q,$r) => $sac->forgotForm($q,$r));
    $app->get( '/forgot-password/sent',  fn($q,$r) => $sac->forgotSent($q,$r));
    $app->post('/forgot-password', fn($q,$r)   => $sac->forgotSubmit($q,$r));
    $app->get( '/reset-password',  fn($q,$r)   => $sac->resetForm($q,$r));
    $app->post('/reset-password',  fn($q,$r)   => $sac->resetSubmit($q,$r));
    $app->get( '/account',         fn($q,$r)   => $sac->account($q,$r));
    $app->get( '/account/edit',    fn($q,$r)   => $sac->editProfileForm($q,$r));
    $app->post('/account/edit',    fn($q,$r)   => $sac->updateProfile($q,$r));
    $app->post('/account/password',fn($q,$r)   => $sac->updatePassword($q,$r));
    $app->post('/account/delete',  fn($q,$r)   => $sac->deleteAccount($q,$r));
    $app->post('/account/withdraw/{id}', fn($q,$r,$a) => $sac->withdrawInterest($q,$r,$a));
    $app->post('/favourite/{slug}',      fn($q,$r,$a) => $sac->toggleFavourite($q,$r,$a));

    // ── Programmes ────────────────────────────────────────────────────────────
    $app->get('/programmes',               fn($q,$r)   => $pc->index($q,$r));
    $app->get('/programmes/{slug}',        fn($q,$r,$a) => $pc->show($q,$r,$a));
    $app->post('/programmes/{slug}/register',fn($q,$r,$a) => $pc->registerInterest($q,$r,$a));
    $app->get('/programmes/{id}/modules',  fn($q,$r,$a) => $mc->listByProgramme($q,$r,$a));
    $app->get('/interest/withdraw',        fn($q,$r)   => $pc->withdrawForm($q,$r));
    $app->post('/interest/withdraw',       fn($q,$r)   => $pc->withdraw($q,$r));
    $app->get('/interest/confirmed',       fn($q,$r)   => $pc->confirmed($q,$r));

    // ── Modules (public) ──────────────────────────────────────────────────────
    $app->get('/modules',      fn($q,$r)   => $mc->listAll($q,$r));
    $app->get('/modules/{id}', fn($q,$r,$a) => $mc->show($q,$r,$a));

    // ── Staff (public + portal) ────────────────────────────────────────────────
    $app->get( '/staff',               fn($q,$r)   => $sc->index($q,$r));
    $app->get( '/staff/login',         fn($q,$r)   => $sc->loginForm($q,$r));
    $app->post('/staff/login',         fn($q,$r)   => $sc->login($q,$r));
    $app->get( '/staff/logout',        fn($q,$r)   => $sc->logout($q,$r));
    $app->get( '/staff/portal',        fn($q,$r)   => $sc->portal($q,$r));
    $app->get( '/staff/profile/edit',  fn($q,$r)   => $sc->editProfileForm($q,$r));
    $app->post('/staff/profile/edit',  fn($q,$r)   => $sc->updateProfile($q,$r));
    $app->post('/staff/profile/password',fn($q,$r) => $sc->updatePassword($q,$r));
    $app->post('/staff/profile/delete',fn($q,$r)   => $sc->deleteProfile($q,$r));
    $app->get( '/staff/{id}',          fn($q,$r,$a) => $sc->show($q,$r,$a));

    // ── Admin ─────────────────────────────────────────────────────────────────
    $app->get( '/admin/login',  fn($q,$r) => $ac->loginForm($q,$r));
    $app->post('/admin/login',  fn($q,$r) => $ac->login($q,$r));
    $app->get( '/admin/logout', fn($q,$r) => $ac->logout($q,$r));
    $app->get( '/admin',           fn($q,$r) => $ac->dashboard($q,$r));
    $app->get( '/admin/dashboard', fn($q,$r) => $ac->dashboard($q,$r));

    $app->get( '/admin/programmes',              fn($q,$r)    => $ac->programmes($q,$r));
    $app->get( '/admin/programmes/create',       fn($q,$r)    => $ac->createProgrammeForm($q,$r));
    $app->post('/admin/programmes/create',       fn($q,$r)    => $ac->storeProgramme($q,$r));
    $app->get( '/admin/programmes/{id}/edit',    fn($q,$r,$a) => $ac->editProgrammeForm($q,$r,$a));
    $app->post('/admin/programmes/{id}/edit',    fn($q,$r,$a) => $ac->updateProgramme($q,$r,$a));
    $app->post('/admin/programmes/{id}/delete',  fn($q,$r,$a) => $ac->deleteProgramme($q,$r,$a));
    $app->post('/admin/programmes/{id}/toggle',  fn($q,$r,$a) => $ac->togglePublish($q,$r,$a));

    $app->get( '/admin/modules',             fn($q,$r)    => $ac->modules($q,$r));
    $app->get( '/admin/modules/create',      fn($q,$r)    => $ac->createModuleForm($q,$r));
    $app->post('/admin/modules/create',      fn($q,$r)    => $ac->storeModule($q,$r));
    $app->get( '/admin/modules/{id}/edit',   fn($q,$r,$a) => $ac->editModuleForm($q,$r,$a));
    $app->post('/admin/modules/{id}/edit',   fn($q,$r,$a) => $ac->updateModule($q,$r,$a));
    $app->post('/admin/modules/{id}/delete', fn($q,$r,$a) => $ac->deleteModule($q,$r,$a));

    $app->get( '/admin/staff',             fn($q,$r)    => $ac->staff($q,$r));
    $app->get( '/admin/staff/create',      fn($q,$r)    => $ac->createStaffForm($q,$r));
    $app->post('/admin/staff/create',      fn($q,$r)    => $ac->storeStaff($q,$r));
    $app->get( '/admin/staff/{id}/edit',   fn($q,$r,$a) => $ac->editStaffForm($q,$r,$a));
    $app->post('/admin/staff/{id}/edit',   fn($q,$r,$a) => $ac->updateStaff($q,$r,$a));
    $app->post('/admin/staff/{id}/delete', fn($q,$r,$a) => $ac->deleteStaff($q,$r,$a));

    $app->get( '/admin/registrations',              fn($q,$r)    => $ac->registrations($q,$r));
    $app->get( '/admin/registrations/export',       fn($q,$r)    => $ac->exportRegistrations($q,$r));
    $app->post('/admin/registrations/{id}/delete',  fn($q,$r,$a) => $ac->deleteRegistration($q,$r,$a));
}