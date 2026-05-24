<?php
declare(strict_types=1);
class ProgrammeView {
    public function renderProgrammeList(array $programmes): string {
        $search = htmlspecialchars($_GET['search']??'',ENT_QUOTES,'UTF-8');
        $level  = htmlspecialchars($_GET['level']??'',ENT_QUOTES,'UTF-8');
        $html   = Layout::head('Degree Programmes');
        $html  .= Layout::nav('/programmes');
        $html  .= '<main id="main">';
        $html  .= '<div class="page-banner"><div class="container">';
        $html  .= '<div class="breadcrumb"><a href="/">Home</a><span class="sep">/</span><span>Programmes</span></div>';
        $html  .= '<h1><i class="fa fa-graduation-cap" style="color:var(--accent);margin-right:.5rem"></i>Degree Programmes</h1>';
        $html  .= '<p style="opacity:.8;margin-top:.4rem">Discover undergraduate and postgraduate degrees across every discipline</p>';
        $html  .= '</div></div>';
        $html  .= '<div class="container page-pad">';
        $html  .= Layout::flash();

        // Filter bar
        $html .= '<form action="/programmes" method="GET" role="search" style="background:var(--white);padding:1.25rem 1.5rem;border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border-light);margin:2rem 0" class="flex gap-2 flex-wrap items-center">';
        $html .= '<input type="search" name="search" class="form-control" value="' . $search . '" placeholder="Search by title or keyword…" style="max-width:300px">';
        $html .= '<select name="level" class="form-control" style="max-width:200px">';
        $html .= '<option value="">All Levels</option>';
        $html .= '<option value="Undergraduate"' . ($level==='Undergraduate'?' selected':'') . '>Undergraduate</option>';
        $html .= '<option value="Postgraduate"' . ($level==='Postgraduate'?' selected':'') . '>Postgraduate</option>';
        $html .= '</select>';
        $html .= '<button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>';
        if ($search||$level) $html .= '<a href="/programmes" class="btn btn-outline"><i class="fa fa-times"></i> Clear</a>';
        $html .= '<span class="text-muted text-sm" style="margin-left:auto">' . count($programmes) . ' programme' . (count($programmes)!==1?'s':'') . ' found</span>';
        $html .= '</form>';

        if (empty($programmes)) {
            $html .= '<div class="card card-body text-center" style="padding:4rem"><i class="fa fa-search" style="font-size:3rem;color:var(--border);margin-bottom:1rem"></i><p>No programmes match your search.</p><a href="/programmes" class="btn btn-primary mt-2">Clear filters</a></div>';
        } else {
            // Group by level
            $ug = array_filter($programmes,fn($p)=>$p['level']==='Undergraduate');
            $pg = array_filter($programmes,fn($p)=>$p['level']==='Postgraduate');
            foreach ([['Undergraduate',$ug],['Postgraduate',$pg]] as [$lv,$progs]) {
                if (!$progs) continue;
                $html .= '<h2 style="font-family:Merriweather,serif;font-size:1.4rem;color:var(--navy);margin:2rem 0 1rem;display:flex;align-items:center;gap:.5rem"><i class="fa ' . ($lv==='Undergraduate'?'fa-book':'fa-flask') . '" style="color:var(--accent)"></i>' . $lv . ' Programmes</h2>';
                $html .= '<div class="grid-3">';
                foreach ($progs as $p) $html .= HomeView::progCard($p, true);
                $html .= '</div>';
            }
        }
        $html .= '</div></main>' . Layout::footer();
        return $html;
    }

    public function renderProgrammeDetail(array $p, array $mByYear, array $shared): string {
        $title  = htmlspecialchars($p['title'],ENT_QUOTES,'UTF-8');
        $level  = htmlspecialchars($p['level'],ENT_QUOTES,'UTF-8');
        $slug   = htmlspecialchars($p['slug'],ENT_QUOTES,'UTF-8');
        $badge  = $p['level']==='Undergraduate'?'badge-ug':'badge-pg';
        $leader = htmlspecialchars($p['leader_name']??'',ENT_QUOTES,'UTF-8');
        $dur    = (int)($p['duration_years']??3);
        $sid    = $_SESSION['student_id']??null;

        $css = '<style>
        .detail-grid{display:grid;grid-template-columns:1fr 380px;gap:2.5rem;align-items:start;margin-top:2rem}
        @media(max-width:900px){.detail-grid{grid-template-columns:1fr}}
        .sticky-sidebar{position:sticky;top:80px}
        </style>';

        $html  = Layout::head($title, $css);
        $html .= Layout::nav('/programmes');
        $html .= '<main id="main">';

        $html .= '<div class="page-banner"><div class="container">';
        $html .= '<div class="breadcrumb"><a href="/">Home</a><span class="sep">/</span><a href="/programmes">Programmes</a><span class="sep">/</span><span>' . $title . '</span></div>';
        $html .= '<div class="flex gap-2 flex-wrap items-center mt-1"><span class="badge ' . $badge . '">' . $level . '</span>';
        $html .= '<span class="text-xs" style="opacity:.7"><i class="fa fa-clock"></i> ' . $dur . ' year' . ($dur>1?'s':'') . '</span></div>';
        $html .= '<h1 style="margin-top:.5rem">' . $title . '</h1>';
        if ($leader) $html .= '<p style="opacity:.8;margin-top:.4rem"><i class="fa fa-user-tie"></i> Programme Leader: ' . $leader . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="container page-pad"><div class="detail-grid">';
        // Left column
        $html .= '<div>';
        $html .= Layout::flash();
        if ($p['description']) {
            $html .= '<div class="section-head"><h2>About This Programme</h2></div>';
            $html .= '<p>' . nl2br(htmlspecialchars($p['description'],ENT_QUOTES,'UTF-8')) . '</p>';
        }

        if (!empty($mByYear)) {
            $html .= '<div class="section-head"><h2>Modules by Year</h2></div>';
            foreach ($mByYear as $yr => $mods) {
                $html .= '<h3 style="color:var(--navy);font-weight:700;margin:1.5rem 0 .75rem;font-size:1rem"><i class="fa fa-layer-group" style="color:var(--accent)"></i> Year ' . $yr . '</h3>';
                foreach ($mods as $m) {
                    $mt  = htmlspecialchars($m['title'],ENT_QUOTES,'UTF-8');
                    $mc  = htmlspecialchars($m['code'],ENT_QUOTES,'UTF-8');
                    $ml  = htmlspecialchars($m['leader_name']??'',ENT_QUOTES,'UTF-8');
                    $mds = htmlspecialchars(mb_substr($m['description']??'',0,140),ENT_QUOTES,'UTF-8');
                    $pcount = (int)($m['prog_count']??0);
                    $html .= '<div class="module-card mb-1">';
                    $html .= '<div class="flex justify-between items-center">';
                    $html .= '<span class="module-code">' . $mc . '</span>';
                    $html .= '<div class="flex gap-1 items-center">';
                    $html .= '<span class="text-xs text-muted"><i class="fa fa-star-half-alt"></i> ' . (int)$m['credits'] . ' credits</span>';
                    if ($pcount > 1) $html .= '<span class="badge badge-shared"><i class="fa fa-share-alt"></i> Shared ×' . $pcount . '</span>';
                    $html .= '</div></div>';
                    $html .= '<p class="font-semibold mt-1" style="font-size:.95rem"><a href="/modules/' . $m['id'] . '" style="color:var(--navy)">' . $mt . '</a></p>';
                    if ($ml) $html .= '<p class="text-xs text-muted"><i class="fa fa-user"></i> ' . $ml . '</p>';
                    if ($mds) $html .= '<p class="text-sm text-muted mt-1">' . $mds . (strlen($m['description']??'')>140?'…':'') . '</p>';
                    $html .= '</div>';
                }
            }
        }

        if (!empty($shared)) {
            $html .= '<div class="section-head"><h2>Related Programmes</h2><p>Programmes that share modules with this one</p></div>';
            $html .= '<div class="flex flex-wrap gap-1">';
            foreach ($shared as $sp) {
                $sb = $sp['level']==='Undergraduate'?'badge-ug':'badge-pg';
                $html .= '<a href="/programmes/' . htmlspecialchars($sp['slug']) . '" class="badge ' . $sb . '" style="font-size:.82rem;padding:.3rem .9rem;text-decoration:none">' . htmlspecialchars($sp['title']) . '</a>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        // Sidebar
        $html .= '<aside class="sticky-sidebar">';
        $sm = new StudentModel();
        if ($sid && $sm->findById((int)$sid)) {
            $isFav = $sm->isFavourite((int)$sid,(int)$p['id']);
            $fLbl  = $isFav?'<i class="fa fa-heart"></i> Saved to Favourites':'<i class="fa fa-heart"></i> Save to Favourites';
            $fStyle= $isFav?'background:#fee2e2;color:var(--error);border-color:#fca5a5':'';
            $html .= '<form action="/favourite/' . $slug . '" method="POST" style="margin-bottom:1rem">';
            $html .= '<button type="submit" class="btn btn-outline w-full" style="' . $fStyle . '">' . $fLbl . '</button>';
            $html .= '</form>';
        }

        // Interest form
        $html .= '<div class="card"><div class="card-header"><h3><i class="fa fa-envelope"></i> Register Your Interest</h3></div><div class="card-body">';
        $sm = new StudentModel();
        $st = $sid ? $sm->findById((int)$sid) : false;
        if ($sid && $st) {
            $fn  = htmlspecialchars($st['first_name'],ENT_QUOTES,'UTF-8');
            $ln  = htmlspecialchars($st['last_name'],ENT_QUOTES,'UTF-8');
            $em  = htmlspecialchars($st['email'],ENT_QUOTES,'UTF-8');
            $html .= '<p class="text-sm text-muted mb-2">Registering as <strong>' . $fn . ' ' . $ln . '</strong> (' . $em . ')</p>';
            $html .= '<form action="/programmes/' . $slug . '/register" method="POST" novalidate>';
            $html .= '<div class="form-group"><label for="ph">Phone (optional)</label><input type="tel" id="ph" name="phone" class="form-control"></div>';
            $html .= '<div class="form-group"><label for="msg">Message (optional)</label><textarea id="msg" name="message" class="form-control" rows="3"></textarea></div>';
            $html .= '<button type="submit" class="btn btn-accent w-full"><i class="fa fa-check"></i> Register Interest</button></form>';
        } else {
            $html .= '<p class="text-sm text-muted mb-2"><a href="/login">Sign in</a> for faster registration, or fill in below:</p>';
            $html .= '<form action="/programmes/' . $slug . '/register" method="POST" novalidate>';
            $html .= '<div class="form-group"><label>First Name <span class="req">*</span></label><input type="text" name="first_name" class="form-control" required></div>';
            $html .= '<div class="form-group"><label>Last Name <span class="req">*</span></label><input type="text" name="last_name" class="form-control" required></div>';
            $html .= '<div class="form-group"><label>Email <span class="req">*</span></label><input type="email" name="email" class="form-control" required></div>';
            $html .= '<div class="form-group"><label>Phone (optional)</label><input type="tel" name="phone" class="form-control"></div>';
            $html .= '<div class="form-group"><label>Message (optional)</label><textarea name="message" class="form-control" rows="3"></textarea></div>';
            $html .= '<button type="submit" class="btn btn-accent w-full"><i class="fa fa-check"></i> Register Interest</button></form>';
        }
        $html .= '<p class="text-xs text-muted mt-2">Your details are only used to send programme updates. <a href="/interest/withdraw">Withdraw interest</a>.</p>';
        $html .= '</div></div>';

        // Leader info
        if ($leader) {
            $html .= '<div class="card mt-2"><div class="card-header"><h3><i class="fa fa-user-tie"></i> Programme Leader</h3></div><div class="card-body">';
            $html .= '<p class="font-semibold">' . $leader . '</p>';
            if (!empty($p['leader_email'])) $html .= '<p class="text-sm"><a href="mailto:' . htmlspecialchars($p['leader_email']) . '"><i class="fa fa-envelope"></i> ' . htmlspecialchars($p['leader_email']) . '</a></p>';
            if (!empty($p['leader_phone'])) $html .= '<p class="text-sm text-muted"><i class="fa fa-phone"></i> ' . htmlspecialchars($p['leader_phone']) . '</p>';
            if (!empty($p['leader_office'])) $html .= '<p class="text-sm text-muted"><i class="fa fa-map-marker-alt"></i> ' . htmlspecialchars($p['leader_office']) . '</p>';
            if (!empty($p['leader_bio'])) $html .= '<p class="text-sm text-muted mt-1">' . htmlspecialchars(mb_substr($p['leader_bio'],0,180)) . '…</p>';
            $html .= '</div></div>';
        }

        $html .= '</aside></div></div></main>' . Layout::footer();
        return $html;
    }

    public function renderConfirmed(string $prog): string {
        $html  = Layout::head('Interest Registered') . Layout::nav();
        $html .= '<main id="main"><div class="container page-pad text-center" style="max-width:600px;margin-top:4rem">';
        $html .= '<div style="font-size:4rem;color:var(--success);margin-bottom:1rem"><i class="fa fa-check-circle"></i></div>';
        $html .= '<h1 style="font-family:Merriweather,serif;color:var(--navy)">You\'re Registered!</h1>';
        if ($prog) $html .= '<p class="mt-2">Thank you for your interest in <strong>' . htmlspecialchars($prog) . '</strong>. We\'ll keep you updated on open days and deadlines.</p>';
        $html .= '<div class="flex gap-2 justify-center mt-3 flex-wrap">';
        $html .= '<a href="/programmes" class="btn btn-primary">Browse More Programmes</a>';
        $html .= '<a href="/account" class="btn btn-outline">My Dashboard</a>';
        $html .= '</div></div></main>' . Layout::footer();
        return $html;
    }

    public function renderWithdrawForm(): string {
        $html  = Layout::head('Withdraw Interest') . Layout::nav();
        $html .= '<main id="main"><div class="container page-pad" style="max-width:500px;margin-top:3rem">';
        $html .= '<a href="/programmes" class="btn btn-ghost btn-sm mb-2"><i class="fa fa-arrow-left"></i> Back to Programmes</a>';
        $html .= '<div class="card"><div class="card-header"><h2><i class="fa fa-times-circle"></i> Withdraw Interest</h2></div><div class="card-body">';
        $html .= '<p class="text-muted mb-2">Enter your email address to remove all interest registrations and stop receiving communications.</p>';
        $html .= Layout::flash();
        $html .= '<form action="/interest/withdraw" method="POST" novalidate>';
        $html .= '<div class="form-group"><label for="we">Email Address <span class="req">*</span></label><input type="email" id="we" name="email" class="form-control" required></div>';
        $html .= '<button type="submit" class="btn btn-danger w-full"><i class="fa fa-trash"></i> Withdraw All Interest</button>';
        $html .= '</form></div></div></div></main>' . Layout::footer();
        return $html;
    }
}