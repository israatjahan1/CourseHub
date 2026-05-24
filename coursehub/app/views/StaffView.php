<?php
declare(strict_types=1);
/**
 * StaffView — Renders public staff directory, individual staff profiles,
 * staff login form, portal dashboard, and profile editing interface.
 */

class StaffView
{
    private static function h(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }

    private static function initials(string $name): string
    {
        return mb_strtoupper(mb_substr(implode('', array_map(fn($w) => $w[0], array_filter(explode(' ', $name)))), 0, 2));
    }

    public function renderStaffList(array $staff): string
    {
        $html  = Layout::head('Academic Staff');
        $html .= Layout::nav('/staff');
        $html .= '<main id="main">';
        $html .= '<div class="page-banner"><div class="container">';
        $html .= '<div class="breadcrumb"><a href="/">Home</a><span class="sep">/</span><span>Staff</span></div>';
        $html .= '<h1><i class="fa fa-users" style="color:var(--accent)"></i> Academic Staff</h1>';
        $html .= '<p style="opacity:.8;margin-top:.5rem">Our ' . count($staff) . ' academics bring world-class expertise across computing, business, data science, and AI.</p>';
        $html .= '</div></div>';
        $html .= '<div class="container page-pad">';
        $html .= Layout::flash();
        $html .= '<div class="grid-3">';
        foreach ($staff as $s) {
            $html .= $this->staffCard($s);
        }
        $html .= '</div>';

        if (empty($_SESSION['staff_id'])) {
            $html .= '<div style="margin-top:3rem;background:var(--info-bg);border:1px solid #bfdbfe;border-radius:var(--radius);padding:1.5rem 2rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">';
            $html .= '<div><p style="font-weight:700;color:var(--blue)"><i class="fa fa-user-tie"></i> Are you a staff member?</p>';
            $html .= '<p class="text-muted text-sm">Sign in to view your teaching portal, manage your profile, and see all your modules.</p></div>';
            $html .= '<a href="/staff/login" class="btn btn-primary"><i class="fa fa-sign-in-alt"></i> Staff Sign In</a>';
            $html .= '</div>';
        }

        $html .= '</div></main>' . Layout::footer();
        return $html;
    }

public function renderStaffProfile(array $staff, array $modules, array $programmes): string
    {
        $name     = self::h($staff['name']);
        $role     = self::h($staff['role']);
        $dept     = self::h($staff['department']);
        $email    = self::h($staff['email']);
        $bio      = nl2br(self::h($staff['bio'] ?? ''));
        $office   = self::h($staff['office'] ?? '');
        $phone    = self::h($staff['phone'] ?? '');
        $initials = self::initials($staff['name']);

        $css = '<style>
        .profile-grid{display:grid;grid-template-columns:1fr 300px;gap:2.5rem;align-items:start}
        @media(max-width:900px){.profile-grid{grid-template-columns:1fr}}
        .sticky-side{position:sticky;top:85px}
        .mod-row{display:flex;flex-direction:column;gap:.75rem;margin-top:1rem}
        .mod-profile-card{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-sm);padding:1.1rem 1.25rem;transition:box-shadow .2s,border-color .2s}
        .mod-profile-card:hover{box-shadow:var(--shadow);border-color:var(--blue)}
        </style>';

        $html  = Layout::head($name . ' — Staff Profile', $css);
        $html .= Layout::nav('/staff');
        $html .= '<main id="main">';

        $html .= '<div style="background:linear-gradient(135deg,var(--navy),var(--blue));color:#fff;padding:3rem 0">';
        $html .= '<div class="container">';
        $html .= '<div class="breadcrumb"><a href="/">Home</a><span class="sep">/</span><a href="/staff">Staff</a><span class="sep">/</span><span>' . $name . '</span></div>';
        $html .= '<div class="flex gap-3 items-center flex-wrap mt-2">';
        if (!empty($staff['photo_url'])) {
            $html .= '<img src="' . self::h($staff['photo_url']) . '" alt="' . $name . '" style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid rgba(255,255,255,.3)">';
        } else {
            $html .= '<div style="width:90px;height:90px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:700;color:#fff;border:3px solid rgba(255,255,255,.2)">' . $initials . '</div>';
        }
        $html .= '<div>';
        $html .= '<p style="opacity:.65;font-size:.85rem;margin-bottom:.2rem"><i class="fa fa-building"></i> ' . $dept . '</p>';
        $html .= '<h1 style="font-family:Merriweather,serif;font-size:2rem">' . $name . '</h1>';
        $html .= '<p style="opacity:.8;margin-top:.3rem">' . $role . '</p>';
        $html .= '</div></div></div></div>';

        $html .= '<div class="container page-pad"><div class="profile-grid">';

        // Main content LEFT
        $html .= '<div>';
        $html .= Layout::flash();

        $html .= '<div class="section-head"><h2>Biography</h2></div>';
        if ($bio) {
            $html .= '<p style="line-height:1.9;color:var(--text);font-size:.975rem">' . $bio . '</p>';
        } else {
            $html .= '<p class="text-muted" style="font-style:italic">No biography available yet.</p>';
        }

        $html .= '<div class="section-head" style="margin-top:2.5rem"><h2>Teaching Modules</h2>';
        $html .= '<p>' . (count($modules) > 0 ? count($modules) . ' module' . (count($modules) !== 1 ? 's' : '') . ' led by ' . $name : 'No modules assigned yet') . '</p></div>';

        if (!empty($modules)) {
            $html .= '<div class="mod-row">';
            foreach ($modules as $m) {
                $mc      = self::h($m['code']);
                $mt      = self::h($m['title']);
                $desc    = self::h($m['description'] ?? '');
                $yr      = (int)$m['year_of_study'];
                $yrLabel = $yr <= 3 ? 'Year ' . $yr : 'PG Year ' . ($yr - 3);
                $pc      = (int)($m['prog_count'] ?? 0);

                $html .= '<div class="mod-profile-card">';
                $html .= '<div class="flex justify-between items-center flex-wrap gap-1">';
                $html .= '<div class="flex gap-1 items-center flex-wrap">';
                $html .= '<span class="module-code">' . $mc . '</span>';
                $html .= '<span class="tag"><i class="fa fa-layer-group"></i> ' . $yrLabel . '</span>';
                $html .= '<span class="tag"><i class="fa fa-star-half-alt"></i> ' . (int)$m['credits'] . ' credits</span>';
                if ($pc > 1) $html .= '<span class="badge badge-shared"><i class="fa fa-share-alt"></i> Shared across ' . $pc . ' programmes</span>';
                $html .= '</div>';
                $html .= '<a href="/modules/' . (int)$m['id'] . '" class="btn btn-ghost btn-sm" style="font-size:.8rem"><i class="fa fa-eye"></i> View</a>';
                $html .= '</div>';
                $html .= '<p class="font-semibold mt-1" style="font-size:1rem;color:var(--navy)">' . $mt . '</p>';
                if ($desc) $html .= '<p class="text-sm text-muted mt-1" style="line-height:1.7">' . mb_substr($desc, 0, 200) . (mb_strlen($desc) > 200 ? '&hellip;' : '') . '</p>';
                $html .= '</div>';
            }
            $html .= '</div>';
        } else {
            $html .= '<div class="card card-body text-center" style="padding:2rem">';
            $html .= '<i class="fa fa-book" style="font-size:2rem;color:var(--border);margin-bottom:.75rem"></i>';
            $html .= '<p class="text-muted">No modules currently assigned.</p></div>';
        }
        $html .= '</div>';

        // Sidebar RIGHT
        $html .= '<aside class="sticky-side">';

        $html .= '<div class="card"><div class="card-header"><h3><i class="fa fa-address-card"></i> Contact</h3></div><div class="card-body">';
        $rows = [['fa-envelope','Email','<a href="mailto:' . $email . '">' . $email . '</a>']];
        if ($phone)  $rows[] = ['fa-phone','Phone',$phone];
        if ($office) $rows[] = ['fa-map-marker-alt','Office',$office];
        foreach ($rows as [$icon,$label,$val]) {
            $html .= '<div style="display:flex;gap:.6rem;align-items:flex-start;margin-bottom:.85rem;font-size:.875rem">';
            $html .= '<i class="fa ' . $icon . '" style="color:var(--blue);margin-top:.25rem;width:16px;flex-shrink:0"></i>';
            $html .= '<div><div style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:700">' . $label . '</div>';
            $html .= '<div style="margin-top:.15rem;word-break:break-word">' . $val . '</div></div></div>';
        }
        $html .= '</div></div>';

        $html .= '<div class="card mt-2"><div class="card-header"><h3><i class="fa fa-chart-bar"></i> Overview</h3></div><div class="card-body">';
        foreach ([['Modules led',count($modules)],['Programmes led',count($programmes)],['Department',$dept]] as [$lbl,$val]) {
            $html .= '<div style="display:flex;justify-content:space-between;align-items:center;padding:.55rem 0;border-bottom:1px solid var(--border-light)">';
            $html .= '<span class="text-sm text-muted">' . $lbl . '</span><strong class="text-sm">' . $val . '</strong></div>';
        }
        $html .= '</div></div>';

        if (!empty($programmes)) {
            $html .= '<div class="card mt-2"><div class="card-header"><h3><i class="fa fa-graduation-cap"></i> Programme Leader</h3></div><div class="card-body">';
            foreach ($programmes as $p) {
                $b = $p['level'] === 'Undergraduate' ? 'badge-ug' : 'badge-pg';
                $html .= '<a href="/programmes/' . self::h($p['slug']) . '" style="display:flex;align-items:center;gap:.5rem;padding:.5rem 0;border-bottom:1px solid var(--border-light);text-decoration:none;color:var(--text)">';
                $html .= '<span class="badge ' . $b . '" style="font-size:.7rem;flex-shrink:0">' . ($p['level']==='Undergraduate'?'UG':'PG') . '</span>';
                $html .= '<span class="text-sm font-semibold">' . self::h($p['title']) . '</span></a>';
            }
            $html .= '</div></div>';
        }

        $html .= '</aside>';
        $html .= '</div></div></main>' . Layout::footer();
        return $html;
    }

        public function renderLoginForm(): string
    {
        $html  = Layout::head('Staff Sign In');
        $html .= Layout::nav();
        $html .= '<main id="main"><div class="container page-pad" style="max-width:440px;margin-top:3rem">';
        $html .= '<a href="/staff" class="btn btn-ghost btn-sm mb-2"><i class="fa fa-arrow-left"></i> Back to Staff</a>';
        $html .= '<div class="card"><div class="card-header" style="background:var(--navy)"><h2 style="color:#fff"><i class="fa fa-user-tie"></i> Staff Portal</h2></div>';
        $html .= '<div class="card-body">';
        $html .= '<p class="text-muted text-sm mb-2">Sign in with your academic email and staff password.</p>';
        $html .= Layout::flash();
        $html .= '<form action="/staff/login" method="POST" novalidate>';
        $html .= '<div class="form-group"><label for="se">Email Address <span class="req">*</span></label>';
        $html .= '<input type="email" id="se" name="email" class="form-control" required autocomplete="email" autofocus></div>';
        $html .= '<div class="form-group"><label for="sp">Password <span class="req">*</span></label>';
        $html .= '<div class="input-wrap"><input type="password" id="sp" name="password" class="form-control" required autocomplete="current-password">';
        $html .= '<button type="button" class="input-icon" data-toggle-pw title="Show/hide password"><i class="fa fa-eye"></i></button></div></div>';
        $html .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-sign-in-alt"></i> Sign In to Portal</button>';
        $html .= '</form>';
        $html .= '<p class="text-xs text-muted text-center mt-2">Credentials are managed by admin. Contact <a href="mailto:admin@coursehub.ac.uk">admin@coursehub.ac.uk</a> if you have issues.</p>';
        $html .= '</div></div></div></main>' . Layout::footer();
        return $html;
    }

    public function renderPortal(array $staff, array $allModules, array $myModuleIds): string
    {
        $name     = self::h($staff['name']);
        $initials = self::initials($staff['name']);
        $myCount  = count($myModuleIds);

        $css = '<style>
        .mine-banner{background:linear-gradient(to right,#fef3c7,#fff);border-left:3px solid var(--accent);border-radius:0 var(--radius-xs) var(--radius-xs) 0;padding:.3rem .7rem;margin:-1.2rem -1.2rem .75rem;font-size:.72rem;font-weight:700;color:var(--accent-dark)}
        .module-mine{border-color:var(--accent)!important;box-shadow:0 0 0 2px rgba(232,160,32,.15)!important}
        .filter-chip{padding:.35rem .9rem;border-radius:999px;font-size:.8rem;font-weight:600;border:1.5px solid var(--border);background:var(--white);color:var(--muted);cursor:pointer;transition:all .18s;font-family:inherit}
        .filter-chip:hover{border-color:var(--blue);color:var(--blue)}
        .filter-chip.active{background:var(--navy);color:#fff;border-color:var(--navy)}
        </style>';

        $html  = Layout::head('Staff Portal — ' . $name, $css);
        $html .= Layout::nav();
        $html .= '<main id="main">';

        // Portal banner
        $html .= '<div style="background:linear-gradient(135deg,var(--navy),#1a4a7a);color:#fff;padding:2.5rem 0">';
        $html .= '<div class="container">';
        $html .= '<div class="flex gap-3 items-center flex-wrap justify-between">';
        $html .= '<div class="flex gap-3 items-center">';
        if (!empty($staff['photo_url'])) {
            $html .= '<img src="' . self::h($staff['photo_url']) . '" alt="' . $name . '" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.3)">';
        } else {
            $html .= '<div style="width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.7rem;font-weight:700;color:#fff">' . $initials . '</div>';
        }
        $html .= '<div>';
        $html .= '<p style="opacity:.6;font-size:.8rem">' . self::h($staff['department']) . '</p>';
        $html .= '<h1 style="font-family:Merriweather,serif;font-size:1.6rem">' . $name . '</h1>';
        $html .= '<p style="opacity:.8">' . self::h($staff['role']) . '</p></div></div>';
        $html .= '<div class="flex gap-1 flex-wrap">';
        $html .= '<a href="/staff/profile/edit" class="btn" style="background:#fff;color:var(--navy);border:2px solid #fff;font-weight:700"><i class="fa fa-edit"></i> Edit Profile</a>';
        $html .= '<a href="/staff/logout" class="btn btn-ghost" style="color:rgba(255,255,255,.7)"><i class="fa fa-sign-out-alt"></i> Logout</a>';
        $html .= '</div></div></div></div>';

        $html .= '<div class="container page-pad">';
        $html .= Layout::flash();

        // Stats
        $ugMine = count(array_filter($allModules, fn($m) => in_array($m['id'], $myModuleIds) && $m['year_of_study'] <= 3));
        $pgMine = count(array_filter($allModules, fn($m) => in_array($m['id'], $myModuleIds) && $m['year_of_study'] > 3));
        $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1.25rem;margin-bottom:2rem">';
        foreach ([
            ['fa-book-open', $myCount, 'My Modules', 'var(--accent)'],
            ['fa-books', count($allModules), 'Total Modules', 'var(--blue)'],
            ['fa-graduation-cap', $ugMine, 'UG I Teach', 'var(--success)'],
            ['fa-flask', $pgMine, 'PG I Teach', '#7c3aed'],
        ] as [$icon, $num, $lbl, $col]) {
            $html .= '<div class="card card-body text-center">';
            $html .= '<i class="fa ' . $icon . '" style="font-size:1.5rem;color:' . $col . ';margin-bottom:.4rem"></i>';
            $html .= '<div style="font-size:2rem;font-weight:800;color:var(--navy)">' . $num . '</div>';
            $html .= '<div class="text-xs text-muted">' . $lbl . '</div></div>';
        }
        $html .= '</div>';

        // Filter bar
        $html .= '<div style="background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius);padding:1rem 1.5rem;margin-bottom:2rem;box-shadow:var(--shadow);display:flex;flex-wrap:wrap;gap:.75rem;align-items:center">';
        $html .= '<span style="font-size:.78rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em"><i class="fa fa-filter"></i> Filter</span>';
        $html .= '<button class="filter-chip active" onclick="setFilter(\'all\',this)"><i class="fa fa-th"></i> All Modules</button>';
        $html .= '<button class="filter-chip" onclick="setFilter(\'mine\',this)"><i class="fa fa-star" style="color:var(--accent)"></i> My Modules Only</button>';
        $html .= '<div style="width:1px;height:22px;background:var(--border-light);margin:0 .2rem"></div>';
        $html .= '<span style="font-size:.78rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em"><i class="fa fa-layer-group"></i> Year</span>';

        $byYear = [];
        foreach ($allModules as $m) $byYear[$m['year_of_study']][] = $m;
        ksort($byYear);
        foreach (array_keys($byYear) as $yr) {
            $lbl = $yr <= 3 ? 'Year ' . $yr : 'PG Yr ' . ($yr-3);
            $html .= '<button class="filter-chip" onclick="setYear(' . $yr . ',this)">' . $lbl . '</button>';
        }
        $html .= '<button id="count-badge" style="margin-left:auto;background:var(--light);color:var(--muted);padding:.3rem .9rem;border-radius:999px;font-size:.8rem;font-weight:600;border:none">' . count($allModules) . ' modules</button>';
        $html .= '</div>';

        // Legend
        $html .= '<div class="flex gap-2 items-center mb-2 flex-wrap">';
        $html .= '<span class="text-sm text-muted">Legend:</span>';
        $html .= '<span class="tag" style="border-left:3px solid var(--accent)"><i class="fa fa-star" style="color:var(--accent)"></i> Your module</span>';
        $html .= '<span class="tag"><i class="fa fa-circle" style="color:var(--border)"></i> Other staff</span>';
        $html .= '<span class="badge badge-shared"><i class="fa fa-share-alt"></i> Shared</span>';
        $html .= '</div>';

        // Modules flat list with data attributes for JS filtering
        $html .= '<div class="grid-3" id="portal-grid">';
        foreach ($allModules as $m) {
            $isMine  = in_array($m['id'], $myModuleIds);
            $shCount = (int)($m['prog_count'] ?? 0);
            $yr      = (int)$m['year_of_study'];
            $html   .= '<article class="module-card' . ($isMine ? ' module-mine' : '') . ' portal-mod" data-mine="' . ($isMine?'1':'0') . '" data-yr="' . $yr . '">';
            if ($isMine) $html .= '<div class="mine-banner"><i class="fa fa-star"></i> Your Module</div>';
            $html .= '<div class="flex justify-between items-center">';
            $html .= '<span class="module-code">' . self::h($m['code']) . '</span>';
            $html .= '<div class="flex gap-1">';
            $html .= '<span class="tag">' . (int)$m['credits'] . ' cr</span>';
            if ($shCount > 1) $html .= '<span class="badge badge-shared"><i class="fa fa-share-alt"></i> ×' . $shCount . '</span>';
            $html .= '</div></div>';
            $html .= '<p class="font-semibold mt-1"><a href="/modules/' . (int)$m['id'] . '" style="color:var(--navy)">' . self::h($m['title']) . '</a></p>';
            $html .= '<p class="text-xs text-muted mt-1"><i class="fa fa-layer-group"></i> ' . ($yr<=3?'Year '.$yr:'PG Year '.($yr-3)) . '</p>';
            if (!$isMine && !empty($m['leader_name'])) $html .= '<p class="text-xs text-muted"><i class="fa fa-user"></i> ' . self::h($m['leader_name']) . '</p>';
            if (!empty($m['description'])) $html .= '<p class="text-xs text-muted mt-1">' . self::h(mb_substr($m['description'], 0, 90)) . '…</p>';
            $html .= '<a href="/modules/' . (int)$m['id'] . '" class="btn btn-ghost btn-sm mt-2" style="font-size:.78rem"><i class="fa fa-eye"></i> View module</a>';
            $html .= '</article>';
        }
        $html .= '</div>';
        $html .= '<div id="portal-empty" style="display:none;text-align:center;padding:3rem;color:var(--muted)"><i class="fa fa-search" style="font-size:2.5rem;color:var(--border);margin-bottom:1rem;display:block"></i><p>No modules match your filter.</p><button class="btn btn-primary btn-sm mt-2" onclick="resetPortalFilter()">Clear filter</button></div>';

        $html .= '</div></main>' . Layout::footer();

        $html .= <<<'JEOF'
<script>
var portalFilter="all";var portalYear="all";
function setFilter(v,btn){
  portalFilter=v;
  document.querySelectorAll(".filter-chip").forEach(function(c){
    if(c.getAttribute("onclick") && c.getAttribute("onclick").indexOf("setFilter")!==-1) c.classList.remove("active");
  });
  btn.classList.add("active");
  runPortalFilter();
}
function setYear(v,btn){
  portalYear = (portalYear == String(v)) ? "all" : String(v);
  document.querySelectorAll(".filter-chip").forEach(function(c){
    if(c.getAttribute("onclick") && c.getAttribute("onclick").indexOf("setYear")!==-1) c.classList.remove("active");
  });
  if(portalYear !== "all") btn.classList.add("active");
  runPortalFilter();
}
function resetPortalFilter(){
  portalFilter="all"; portalYear="all";
  document.querySelectorAll(".filter-chip").forEach(function(c){ c.classList.remove("active"); });
  document.querySelectorAll(".filter-chip").forEach(function(c){
    var oc = c.getAttribute("onclick") || "";
    if(oc.indexOf("setFilter") !== -1 && oc.indexOf("all") !== -1) c.classList.add("active");
  });
  runPortalFilter();
}
function runPortalFilter(){
  var cards = document.querySelectorAll(".portal-mod");
  var visible = 0;
  cards.forEach(function(card){
    var isMine = card.getAttribute("data-mine") === "1";
    var yr     = card.getAttribute("data-yr");
    var show   = true;
    if(portalFilter === "mine" && !isMine) show = false;
    if(portalYear !== "all" && yr !== portalYear) show = false;
    card.style.display = show ? "" : "none";
    if(show) visible++;
  });
  document.getElementById("count-badge").textContent = visible + " module" + (visible !== 1 ? "s" : "");
  document.getElementById("portal-empty").style.display = visible === 0 ? "block" : "none";
  document.getElementById("portal-grid").style.display  = visible === 0 ? "none"  : "";
}
</script>
JEOF;
        return $html;
    }

    public function renderEditProfile(array $staff): string
    {
        $html  = Layout::head('Edit Profile — Staff Portal');
        $html .= Layout::nav();
        $html .= '<main id="main"><div class="container page-pad" style="max-width:900px">';
        $html .= '<a href="/staff/portal" class="btn btn-ghost btn-sm mb-2"><i class="fa fa-arrow-left"></i> Back to Portal</a>';
        $html .= '<div class="page-banner" style="border-radius:var(--radius);margin-bottom:2rem">';
        $html .= '<h1><i class="fa fa-edit"></i> Edit Your Profile</h1></div>';
        $html .= Layout::flash();
        $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:start">';

        // Left: info form
        $html .= '<div class="card"><div class="card-header"><h2><i class="fa fa-user"></i> Personal Information</h2></div><div class="card-body">';
        $html .= '<form action="/staff/profile/edit" method="POST" novalidate>';
        $fields = [
            ['name', 'Full Name', 'text', true],
            ['role', 'Job Title', 'text', true],
            ['department', 'Department', 'text', true],
            ['email', 'Email Address', 'email', true],
            ['office', 'Office / Room', 'text', false],
            ['phone', 'Phone Extension', 'text', false],
            ['photo_url', 'Profile Photo URL', 'url', false],
        ];
        foreach ($fields as [$n, $l, $t, $req]) {
            $v = self::h($staff[$n] ?? '');
            $html .= '<div class="form-group"><label>' . $l . ($req ? ' <span class="req">*</span>' : '') . '</label>';
            $html .= '<input type="' . $t . '" name="' . $n . '" class="form-control" value="' . $v . '"' . ($req ? ' required' : '') . '></div>';
        }
        $html .= '<div class="form-group"><label>Biography</label>';
        $html .= '<textarea name="bio" class="form-control" rows="5" placeholder="Write a short academic biography…">' . self::h($staff['bio'] ?? '') . '</textarea></div>';
        $html .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-save"></i> Save Profile</button>';
        $html .= '</form></div></div>';

        // Right: password + danger
        $html .= '<div>';
        $html .= '<div class="card mb-2"><div class="card-header"><h2><i class="fa fa-lock"></i> Change Password</h2></div><div class="card-body">';
        $html .= '<form action="/staff/profile/password" method="POST" novalidate>';
        foreach ([
            ['current_password', 'Current Password'],
            ['new_password', 'New Password'],
            ['confirm_password', 'Confirm New Password'],
        ] as [$n, $l]) {
            $html .= '<div class="form-group"><label>' . $l . ' <span class="req">*</span></label>';
            $html .= '<div class="input-wrap"><input type="password" name="' . $n . '" class="form-control" required>';
            $html .= '<button type="button" class="input-icon" data-toggle-pw><i class="fa fa-eye"></i></button></div></div>';
        }
        $html .= '<p class="form-hint">Minimum 8 characters</p>';
        $html .= '<button type="submit" class="btn btn-outline w-full"><i class="fa fa-key"></i> Update Password</button>';
        $html .= '</form></div></div>';

        $html .= '<div class="card" style="border-color:var(--error)"><div class="card-header" style="background:var(--error-bg)"><h2 style="color:var(--error)"><i class="fa fa-exclamation-triangle"></i> Danger Zone</h2></div><div class="card-body">';
        $html .= '<p class="text-muted text-sm mb-2">Permanently delete your staff profile. Your modules will be unassigned but not deleted. This cannot be undone.</p>';
        $html .= '<form action="/staff/profile/delete" method="POST" onsubmit="return confirm(\'Permanently delete your staff profile? This cannot be undone.\')">';
        $html .= '<button type="submit" class="btn btn-danger w-full"><i class="fa fa-trash"></i> Delete My Profile</button>';
        $html .= '</form></div></div>';
        $html .= '</div></div></div></main>' . Layout::footer();
        return $html;
    }

    private function staffCard(array $s): string
    {
        $name     = self::h($s['name']);
        $role     = self::h($s['role']);
        $dept     = self::h($s['department']);
        $email    = self::h($s['email']);
        $mc       = (int)($s['module_count'] ?? 0);
        $pc       = (int)($s['prog_count'] ?? 0);
        $initials = self::initials($s['name']);

        $html  = '<article class="staff-card">';
        $html .= '<div class="staff-avatar">';
        if (!empty($s['photo_url'])) {
            $html .= '<img src="' . self::h($s['photo_url']) . '" alt="' . $name . '">';
        } else {
            $html .= '<span style="color:rgba(255,255,255,.5);font-size:3rem;font-weight:700">' . $initials . '</span>';
        }
        $html .= '</div>';
        $html .= '<div class="staff-card-body">';
        $html .= '<p class="text-xs text-muted mb-1"><i class="fa fa-building"></i> ' . $dept . '</p>';
        $html .= '<h3 class="font-bold" style="font-size:1.05rem;color:var(--navy)">' . $name . '</h3>';
        $html .= '<p class="text-sm text-muted">' . $role . '</p>';
        $html .= '<div class="flex gap-1 flex-wrap mt-2 mb-2">';
        $html .= '<span class="tag"><i class="fa fa-book"></i> ' . $mc . ' module' . ($mc !== 1 ? 's' : '') . '</span>';
        if ($pc) $html .= '<span class="badge badge-ug"><i class="fa fa-graduation-cap"></i> ' . $pc . ' prog.</span>';
        $html .= '</div>';
        $html .= '<div class="flex gap-1">';
        $html .= '<a href="/staff/' . (int)$s['id'] . '" class="btn btn-primary btn-sm"><i class="fa fa-user"></i> Profile</a>';
        $html .= '<a href="mailto:' . $email . '" class="btn btn-outline btn-sm"><i class="fa fa-envelope"></i></a>';
        $html .= '</div></div></article>';
        return $html;
    }
}