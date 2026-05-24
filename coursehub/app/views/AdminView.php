<?php
declare(strict_types=1);

class AdminView
{
    private static function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

    private static function shell(string $title, string $body, string $active = ''): string
    {
        $user = self::h($_SESSION['admin_username'] ?? 'Admin');
        $nav  = [
            '/admin/dashboard'    => ['fa-th-large',   'Dashboard'],
            '/admin/programmes'   => ['fa-graduation-cap','Programmes'],
            '/admin/modules'      => ['fa-cubes',      'Modules'],
            '/admin/staff'        => ['fa-users',      'Staff'],
            '/admin/registrations'=> ['fa-envelope',   'Registrations'],
        ];
        $html  = Layout::head($title . ' — Admin', '<style>
        body{background:#f0f4f9}
        .admin-layout{display:grid;grid-template-columns:240px 1fr;min-height:calc(100vh - 60px)}
        .sidebar{background:var(--navy);padding:1.5rem 0;position:sticky;top:60px;height:calc(100vh - 60px);overflow-y:auto}
        .sidebar-title{color:rgba(255,255,255,.4);font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;padding:.5rem 1.5rem;margin-top:1rem}
        .sidebar a{display:flex;align-items:center;gap:.75rem;color:rgba(255,255,255,.7);padding:.7rem 1.5rem;font-size:.875rem;font-weight:500;transition:all .15s;text-decoration:none}
        .sidebar a:hover{color:#fff;background:rgba(255,255,255,.08)}
        .sidebar a.active{color:var(--accent);background:rgba(232,160,32,.12);border-right:3px solid var(--accent)}
        .sidebar a i{width:18px;text-align:center}
        .admin-content{padding:2rem;max-width:1100px}
        .admin-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem}
        .admin-header h1{font-family:Merriweather,serif;font-size:1.6rem;color:var(--navy)}
        .stat-card{background:var(--white);border-radius:var(--radius);padding:1.25rem 1.5rem;box-shadow:var(--shadow);border:1px solid var(--border-light);display:flex;align-items:center;gap:1.25rem}
        .stat-card .stat-icon{width:52px;height:52px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
        .stat-card .stat-num{font-size:1.8rem;font-weight:800;color:var(--navy);line-height:1}
        .stat-card .stat-lbl{font-size:.78rem;color:var(--muted);margin-top:.2rem}
        @media(max-width:900px){.admin-layout{grid-template-columns:1fr}.sidebar{display:none}}
        </style>');
        $html .= Layout::nav();
        $html .= '<div class="admin-layout">';
        $html .= '<aside class="sidebar">';
        $html .= '<div style="padding:.5rem 1.5rem 1rem;border-bottom:1px solid rgba(255,255,255,.07)">';
        $html .= '<p style="color:rgba(255,255,255,.45);font-size:.72rem;text-transform:uppercase;letter-spacing:.08em">Signed in as</p>';
        $html .= '<p style="color:#fff;font-weight:600;font-size:.9rem">' . $user . '</p></div>';
        $html .= '<p class="sidebar-title">Main</p>';
        foreach ($nav as $href => [$icon, $label]) {
            $cls = ($active === $href || str_starts_with($active, $href)) ? ' active' : '';
            $html .= '<a href="' . $href . '" class="' . ltrim($cls) . '"><i class="fa ' . $icon . '"></i>' . $label . '</a>';
        }
        $html .= '<p class="sidebar-title">Links</p>';
        $html .= '<a href="/" target="_blank"><i class="fa fa-external-link-alt"></i> View Site</a>';
        $html .= '<a href="/admin/logout"><i class="fa fa-sign-out-alt" style="color:rgba(255,100,100,.7)"></i><span style="color:rgba(255,100,100,.7)">Logout</span></a>';
        $html .= '</aside>';
        $html .= '<main class="admin-content" id="main">' . Layout::flash() . $body . '</main>';
        $html .= '</div>';
        $html .= Layout::footer();
        return $html;
    }

    public static function loginForm(): string {
        $html  = Layout::head('Admin Login');
        $html .= Layout::nav();
        $html .= '<main id="main"><div class="container page-pad" style="max-width:420px;margin-top:3rem">';
        $html .= '<div class="card"><div class="card-header" style="background:var(--navy)"><h1 style="color:#fff;font-size:1.1rem"><i class="fa fa-shield-alt"></i> Admin Panel</h1></div>';
        $html .= '<div class="card-body">' . Layout::flash();
        $html .= '<form action="/admin/login" method="POST" novalidate>';
        $html .= '<div class="form-group"><label for="au">Username <span class="req">*</span></label><input type="text" id="au" name="username" class="form-control" required autofocus autocomplete="username"></div>';
        $html .= '<div class="form-group"><label for="ap">Password <span class="req">*</span></label>';
        $html .= '<div class="input-wrap"><input type="password" id="ap" name="password" class="form-control" required autocomplete="current-password">';
        $html .= '<button type="button" class="input-icon" data-toggle-pw><i class="fa fa-eye"></i></button></div></div>';
        $html .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-sign-in-alt"></i> Sign In</button>';
        $html .= '</form><p class="text-xs text-muted text-center mt-2">Default: <code>admin</code> / <code>password</code></p>';
        $html .= '</div></div></div></main>' . Layout::footer();
        return $html;
    }

    public static function dashboard(array $stats, array $recent, array $progs): string {
        $b  = '<div class="admin-header"><h1><i class="fa fa-th-large" style="color:var(--accent)"></i> Dashboard</h1>';
        $b .= '<a href="/" target="_blank" class="btn btn-outline btn-sm"><i class="fa fa-eye"></i> View Site</a></div>';

        // Stats
        $b .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1.25rem;margin-bottom:2rem">';
        foreach ([
            ['fa-graduation-cap','#dbeafe','#1e40af', $stats['published'],'Published Progs'],
            ['fa-cubes','#dcfce7','#15803d',$stats['modules'],'Modules'],
            ['fa-users','#ede9fe','#5b21b6',$stats['staff'],'Staff Members'],
            ['fa-envelope','#fef9c3','#854d0e',$stats['interests'],'Registrations'],
        ] as [$icon,$bg,$fg,$num,$lbl]) {
            $b .= '<div class="stat-card"><div class="stat-icon" style="background:' . $bg . ';color:' . $fg . '"><i class="fa ' . $icon . '"></i></div>';
            $b .= '<div><div class="stat-num">' . $num . '</div><div class="stat-lbl">' . $lbl . '</div></div></div>';
        }
        $b .= '</div>';

        // Quick links
        $b .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:2rem">';
        foreach ([
            ['/admin/programmes/create','fa-plus','Create Programme','btn-primary'],
            ['/admin/modules/create','fa-plus','Create Module','btn-outline'],
            ['/admin/staff/create','fa-user-plus','Add Staff','btn-outline'],
            ['/admin/registrations','fa-download','View Registrations','btn-outline'],
        ] as [$href,$icon,$label,$cls]) {
            $b .= '<a href="' . $href . '" class="btn ' . $cls . '"><i class="fa ' . $icon . '"></i> ' . $label . '</a>';
        }
        $b .= '</div>';

        // Programmes table
        $b .= '<div class="section-head"><h2>Programmes Overview</h2></div>';
        $b .= '<div class="table-wrap"><table><thead><tr><th>Title</th><th>Level</th><th>Modules</th><th>Registrations</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
        foreach (array_slice($progs, 0, 10) as $p) {
            $badge = $p['published'] ? '<span class="badge badge-pub">Published</span>' : '<span class="badge badge-draft">Draft</span>';
            $b .= '<tr>';
            $b .= '<td><a href="/programmes/' . self::h($p['slug']) . '" target="_blank" class="font-semibold">' . self::h($p['title']) . '</a></td>';
            $b .= '<td><span class="badge ' . ($p['level']==='Undergraduate'?'badge-ug':'badge-pg') . '">' . self::h($p['level']) . '</span></td>';
            $b .= '<td>' . (int)($p['module_count']??0) . '</td>';
            $b .= '<td>' . (int)($p['reg_count']??0) . '</td>';
            $b .= '<td>' . $badge . '</td>';
            $b .= '<td class="flex gap-1"><a href="/admin/programmes/' . (int)$p['id'] . '/edit" class="btn btn-outline btn-sm"><i class="fa fa-edit"></i></a>';
            $b .= '<a href="/programmes/' . self::h($p['slug']) . '" target="_blank" class="btn btn-ghost btn-sm" title="Preview"><i class="fa fa-eye"></i></a>';
            $b .= '<form action="/admin/programmes/' . (int)$p['id'] . '/toggle" method="POST" style="display:inline"><button class="btn btn-ghost btn-sm" title="Toggle publish"><i class="fa fa-toggle-on"></i></button></form>';
            $b .= '</td></tr>';
        }
        $b .= '</tbody></table></div>';

        // Recent registrations
        if ($recent) {
            $b .= '<div class="section-head mt-4"><h2>Recent Registrations</h2></div>';
            $b .= '<div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Programme</th><th>Date</th></tr></thead><tbody>';
            foreach ($recent as $r) {
                $b .= '<tr><td>' . self::h($r['first_name'].' '.$r['last_name']) . '</td><td><a href="mailto:' . self::h($r['email']) . '">' . self::h($r['email']) . '</a></td>';
                $b .= '<td>' . self::h($r['programme_title']) . '</td>';
                $b .= '<td class="text-muted text-sm">' . date('d M Y', strtotime($r['created_at'])) . '</td></tr>';
            }
            $b .= '</tbody></table></div>';
        }
        return self::shell('Dashboard', $b, '/admin/dashboard');
    }

    public static function programmeList(array $progs): string {
        $b  = '<div class="admin-header"><h1><i class="fa fa-graduation-cap" style="color:var(--accent)"></i> Programmes</h1>';
        $b .= '<a href="/admin/programmes/create" class="btn btn-primary"><i class="fa fa-plus"></i> New Programme</a></div>';
        $b .= '<div class="table-wrap"><table><thead><tr><th>Title</th><th>Level</th><th>Modules</th><th>Regs</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
        foreach ($progs as $p) {
            $b .= '<tr>';
            $b .= '<td class="font-semibold">' . self::h($p['title']) . '</td>';
            $b .= '<td><span class="badge ' . ($p['level']==='Undergraduate'?'badge-ug':'badge-pg') . '">' . self::h($p['level']) . '</span></td>';
            $b .= '<td>' . (int)($p['module_count']??0) . '</td>';
            $b .= '<td>' . (int)($p['reg_count']??0) . '</td>';
            $b .= '<td>' . ($p['published']?'<span class="badge badge-pub">Published</span>':'<span class="badge badge-draft">Draft</span>') . '</td>';
            $b .= '<td><div class="flex gap-1">';
            $b .= '<a href="/admin/programmes/' . (int)$p['id'] . '/edit" class="btn btn-outline btn-sm"><i class="fa fa-edit"></i> Edit</a>';
            $b .= '<a href="/programmes/' . self::h($p['slug']) . '" target="_blank" class="btn btn-ghost btn-sm"><i class="fa fa-eye"></i> View</a>';
            $b .= '<form action="/admin/programmes/' . (int)$p['id'] . '/toggle" method="POST" style="display:inline"><button class="btn btn-ghost btn-sm" title="Toggle publish"><i class="fa fa-toggle-' . ($p['published']?'on':'off') . '"></i></button></form>';
            $b .= '<form action="/admin/programmes/' . (int)$p['id'] . '/delete" method="POST" style="display:inline" onsubmit="return confirm(\'Delete this programme?\')"><button class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button></form>';
            $b .= '</div></td></tr>';
        }
        $b .= '</tbody></table></div>';
        return self::shell('Programmes', $b, '/admin/programmes');
    }

    public static function programmeForm(?array $prog, array $staff, array $allModules, array $linkedIds): string {
        $edit   = $prog !== null;
        $title  = $edit ? 'Edit Programme' : 'New Programme';
        $action = $edit ? '/admin/programmes/' . (int)$prog['id'] . '/edit' : '/admin/programmes/create';

        $b  = '<div class="admin-header"><h1><i class="fa fa-graduation-cap" style="color:var(--accent)"></i> ' . $title . '</h1>';
        $b .= '<a href="/admin/programmes" class="btn btn-ghost btn-sm"><i class="fa fa-arrow-left"></i> Back</a></div>';
        $b .= '<div style="display:grid;grid-template-columns:1fr 340px;gap:2rem;align-items:start">';
        $b .= '<div class="card"><div class="card-header"><h2><i class="fa fa-edit"></i> Programme Details</h2></div><div class="card-body">';
        $b .= '<form action="' . $action . '" method="POST" novalidate>';
        $b .= '<div class="form-group"><label>Title <span class="req">*</span></label><input type="text" name="title" class="form-control" value="' . self::h($prog['title']??'') . '" required></div>';
        $b .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">';
        $b .= '<div class="form-group"><label>Level <span class="req">*</span></label><select name="level" class="form-control">';
        foreach (['Undergraduate','Postgraduate'] as $lv) $b .= '<option value="' . $lv . '"' . (($prog['level']??'')===$lv?' selected':'') . '>' . $lv . '</option>';
        $b .= '</select></div>';
        $b .= '<div class="form-group"><label>Duration (years)</label><input type="number" name="duration_years" class="form-control" value="' . (int)($prog['duration_years']??3) . '" min="1" max="5"></div>';
        $b .= '</div>';
        $b .= '<div class="form-group"><label>Programme Leader</label><select name="programme_leader_id" class="form-control"><option value="">— None —</option>';
        foreach ($staff as $s) $b .= '<option value="' . (int)$s['id'] . '"' . (($prog['programme_leader_id']??0)==$s['id']?' selected':'') . '>' . self::h($s['name']) . '</option>';
        $b .= '</select></div>';
        $b .= '<div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="6">' . self::h($prog['description']??'') . '</textarea></div>';
        $b .= '<div class="form-group"><label><input type="checkbox" name="published" value="1"' . (!empty($prog['published'])?' checked':'') . '> Published (visible to students)</label></div>';
        $b .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-save"></i> ' . ($edit?'Update':'Create') . ' Programme</button>';
        $b .= '</form></div></div>';

        // Modules selector
        $byYear = [];
        foreach ($allModules as $m) $byYear[(int)$m['year_of_study']][] = $m;
        ksort($byYear);
        $b .= '<div class="card" style="position:sticky;top:80px"><div class="card-header"><h2><i class="fa fa-cubes"></i> Assign Modules</h2></div><div class="card-body" style="max-height:600px;overflow-y:auto">';
        $b .= '<form action="' . $action . '" method="POST" id="modForm">';
        $b .= '<input type="hidden" name="title" value="' . self::h($prog['title']??'') . '">';
        $b .= '<p class="text-xs text-muted mb-2">Check modules to include in this programme.</p>';
        foreach ($byYear as $yr => $mods) {
            $label = $yr <= 3 ? 'Year ' . $yr : 'Postgraduate';
            $b .= '<p class="text-xs font-bold text-muted mt-2 mb-1" style="text-transform:uppercase;letter-spacing:.06em">' . $label . '</p>';
            foreach ($mods as $m) {
                $checked = in_array($m['id'], $linkedIds) ? ' checked' : '';
                $b .= '<label style="display:flex;align-items:flex-start;gap:.5rem;margin-bottom:.5rem;font-size:.85rem;cursor:pointer">';
                $b .= '<input type="checkbox" name="module_ids[]" value="' . (int)$m['id'] . '"' . $checked . ' style="margin-top:.2rem">';
                $b .= '<span><span class="module-code" style="font-size:.7rem">' . self::h($m['code']) . '</span> ' . self::h($m['title']) . '</span></label>';
            }
        }
        $b .= '<button type="submit" class="btn btn-accent w-full mt-2"><i class="fa fa-link"></i> Save with Modules</button>';
        $b .= '</form></div></div>';
        $b .= '</div>';

        return self::shell($title, $b, '/admin/programmes');
    }

    public static function moduleList(array $modules): string {
        $b  = '<div class="admin-header"><h1><i class="fa fa-cubes" style="color:var(--accent)"></i> Modules</h1>';
        $b .= '<a href="/admin/modules/create" class="btn btn-primary"><i class="fa fa-plus"></i> New Module</a></div>';
        $b .= '<div class="table-wrap"><table><thead><tr><th>Code</th><th>Title</th><th>Year</th><th>Credits</th><th>Leader</th><th>Programmes</th><th>Actions</th></tr></thead><tbody>';
        foreach ($modules as $m) {
            $b .= '<tr>';
            $b .= '<td><code style="font-family:JetBrains Mono,monospace;font-size:.8rem">' . self::h($m['code']) . '</code></td>';
            $b .= '<td class="font-semibold">' . self::h($m['title']) . '</td>';
            $b .= '<td>' . (int)$m['year_of_study'] . '</td>';
            $b .= '<td>' . (int)$m['credits'] . '</td>';
            $b .= '<td class="text-muted text-sm">' . self::h($m['leader_name']??'—') . '</td>';
            $pc = (int)($m['prog_count']??0);
            $b .= '<td>' . ($pc > 1 ? '<span class="badge badge-shared"><i class="fa fa-share-alt"></i> ' . $pc . '</span>' : $pc) . '</td>';
            $b .= '<td><div class="flex gap-1">';
            $b .= '<a href="/admin/modules/' . (int)$m['id'] . '/edit" class="btn btn-outline btn-sm"><i class="fa fa-edit"></i> Edit</a>';
            $b .= '<a href="/modules/' . (int)$m['id'] . '" target="_blank" class="btn btn-ghost btn-sm"><i class="fa fa-eye"></i></a>';
            $b .= '<form action="/admin/modules/' . (int)$m['id'] . '/delete" method="POST" style="display:inline" onsubmit="return confirm(\'Delete this module?\')"><button class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button></form>';
            $b .= '</div></td></tr>';
        }
        $b .= '</tbody></table></div>';
        return self::shell('Modules', $b, '/admin/modules');
    }

    public static function moduleForm(?array $module, array $staff): string {
        $edit   = $module !== null;
        $title  = $edit ? 'Edit Module' : 'New Module';
        $action = $edit ? '/admin/modules/' . (int)$module['id'] . '/edit' : '/admin/modules/create';
        $b  = '<div class="admin-header"><h1><i class="fa fa-cubes" style="color:var(--accent)"></i> ' . $title . '</h1>';
        $b .= '<a href="/admin/modules" class="btn btn-ghost btn-sm"><i class="fa fa-arrow-left"></i> Back</a></div>';
        $b .= '<div class="card" style="max-width:680px"><div class="card-body">';
        $b .= '<form action="' . $action . '" method="POST" novalidate>';
        $b .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">';
        $b .= '<div class="form-group"><label>Module Title <span class="req">*</span></label><input type="text" name="title" class="form-control" value="' . self::h($module['title']??'') . '" required></div>';
        $b .= '<div class="form-group"><label>Module Code <span class="req">*</span></label><input type="text" name="code" class="form-control" value="' . self::h($module['code']??'') . '" required placeholder="e.g. CS101"></div>';
        $b .= '</div><div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem">';
        $b .= '<div class="form-group"><label>Year of Study <span class="req">*</span></label><select name="year_of_study" class="form-control">';
        for($y=1;$y<=5;$y++) $b .= '<option value="'.$y.'"'.(($module['year_of_study']??1)==$y?' selected':'').'>' . ($y<=3?'Year '.$y:'PG Year '.($y-3)) . '</option>';
        $b .= '</select></div>';
        $b .= '<div class="form-group"><label>Credits</label><input type="number" name="credits" class="form-control" value="' . (int)($module['credits']??20) . '" min="10" max="120"></div>';
        $b .= '<div class="form-group"><label>Module Leader</label><select name="module_leader_id" class="form-control"><option value="">— None —</option>';
        foreach ($staff as $s) $b .= '<option value="' . (int)$s['id'] . '"' . (($module['module_leader_id']??0)==$s['id']?' selected':'') . '>' . self::h($s['name']) . '</option>';
        $b .= '</select></div></div>';
        $b .= '<div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="8">' . self::h($module['description']??'') . '</textarea></div>';
        $b .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-save"></i> ' . ($edit?'Update':'Create') . ' Module</button>';
        $b .= '</form></div></div>';
        return self::shell($title, $b, '/admin/modules');
    }

    public static function staffList(array $staff): string {
        $b  = '<div class="admin-header"><h1><i class="fa fa-users" style="color:var(--accent)"></i> Staff</h1>';
        $b .= '<a href="/admin/staff/create" class="btn btn-primary"><i class="fa fa-user-plus"></i> Add Staff</a></div>';
        $b .= '<div class="card card-body mb-2" style="background:var(--info-bg);border:1px solid #bfdbfe;padding:.85rem 1.25rem">';
        $b .= '<p class="text-sm" style="color:#1e3a5f"><i class="fa fa-info-circle"></i> <strong>Staff Passwords</strong> &mdash; click the eye icon to reveal a staff member\'s current login password. Keep this page secure.</p></div>';
        $b .= '<div class="table-wrap"><table><thead><tr><th>Name</th><th>Role</th><th>Email</th><th>Password</th><th>Modules</th><th>Actions</th></tr></thead><tbody>';
        foreach ($staff as $s) {
            $pid   = 'pw-' . (int)$s['id'];
            $plain = $s['password_plain'] ?? '';
            $b .= '<tr>';
            $b .= '<td><p class="font-semibold">' . self::h($s['name']) . '</p><p class="text-xs text-muted">' . self::h($s['department']) . '</p></td>';
            $b .= '<td class="text-sm">' . self::h($s['role']) . '</td>';
            $b .= '<td><a href="mailto:' . self::h($s['email']) . '" class="text-sm">' . self::h($s['email']) . '</a></td>';
            $b .= '<td style="white-space:nowrap">';
            if ($plain) {
                $safePlain = htmlspecialchars(addslashes($plain), ENT_QUOTES, 'UTF-8');
                $b .= '<div style="display:flex;align-items:center;gap:.4rem">';
                $b .= '<code id="' . $pid . '" style="font-family:JetBrains Mono,monospace;font-size:.8rem;background:var(--light);padding:.2rem .5rem;border-radius:4px">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</code>';
                $b .= '<button type="button" onclick="togglePw(' . json_encode($pid) . ',' . json_encode($plain) . ',this)" class="btn btn-ghost btn-sm" style="padding:.25rem .5rem" title="Show/hide"><i class="fa fa-eye"></i></button>';
                $b .= '</div>';
            } else {
                $b .= '<span class="text-xs text-muted"><em>Not stored &mdash; reset via Edit</em></span>';
            }
            $b .= '</td>';
            $b .= '<td>' . (int)($s['module_count'] ?? 0) . '</td>';
            $b .= '<td><div class="flex gap-1">';
            $b .= '<a href="/admin/staff/' . (int)$s['id'] . '/edit" class="btn btn-outline btn-sm"><i class="fa fa-edit"></i> Edit</a>';
            $b .= '<a href="/staff/' . (int)$s['id'] . '" target="_blank" class="btn btn-ghost btn-sm" title="View profile"><i class="fa fa-eye"></i></a>';
            $b .= '<form action="/admin/staff/' . (int)$s['id'] . '/delete" method="POST" style="display:inline" onsubmit="return confirm(\'Delete this staff member?\')">';
            $b .= '<button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button></form>';
            $b .= '</div></td></tr>';
        }
        $b .= '</tbody></table></div>';
        $b .= <<<'SEOF'
<script>
function togglePw(id, pw, btn) {
    var el = document.getElementById(id);
    var hidden = (el.textContent.indexOf('\u2022') !== -1 || el.innerHTML.indexOf('&bull;') !== -1);
    el.textContent = hidden ? pw : '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022';
    btn.innerHTML  = hidden ? '<i class="fa fa-eye-slash"></i>' : '<i class="fa fa-eye"></i>';
}
</script>
SEOF;
        return self::shell('Staff', $b, '/admin/staff');
    }

        public static function staffForm(?array $staff): string {
        $edit   = $staff !== null;
        $title  = $edit ? 'Edit Staff' : 'Add Staff Member';
        $action = $edit ? '/admin/staff/' . (int)$staff['id'] . '/edit' : '/admin/staff/create';
        $b  = '<div class="admin-header"><h1><i class="fa fa-user-tie" style="color:var(--accent)"></i> ' . $title . '</h1>';
        $b .= '<a href="/admin/staff" class="btn btn-ghost btn-sm"><i class="fa fa-arrow-left"></i> Back</a></div>';
        $b .= '<div class="card" style="max-width:700px"><div class="card-body">';
        $b .= '<form action="' . $action . '" method="POST" novalidate>';
        $b .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">';
        foreach ([['name','Full Name',true,'text'],['role','Job Title',true,'text'],['department','Department',true,'text'],['email','Email',true,'email'],['phone','Phone',false,'text'],['office','Office / Room',false,'text'],['photo_url','Photo URL',false,'url']] as [$n,$l,$req,$t]) {
            $b .= '<div class="form-group"><label>' . $l . ($req?' <span class="req">*</span>':'') . '</label><input type="' . $t . '" name="' . $n . '" class="form-control" value="' . self::h($staff[$n]??'') . '"' . ($req?' required':'') . '></div>';
        }
        $b .= '</div>';
        $b .= '<div class="form-group"><label>Biography</label><textarea name="bio" class="form-control" rows="5">' . self::h($staff['bio']??'') . '</textarea></div>';
        if ($edit) {
            $b .= '<div class="form-group"><label>New Password <small class="text-muted">(leave blank to keep current)</small></label>';
            $b .= '<div class="input-wrap"><input type="password" name="new_password" class="form-control" autocomplete="new-password">';
            $b .= '<button type="button" class="input-icon" data-toggle-pw><i class="fa fa-eye"></i></button></div></div>';
        } else {
            $b .= '<div class="form-group"><label>Password <span class="req">*</span></label>';
            $b .= '<div class="input-wrap"><input type="password" name="password" class="form-control" required autocomplete="new-password" placeholder="Min. 8 characters">';
            $b .= '<button type="button" class="input-icon" data-toggle-pw><i class="fa fa-eye"></i></button></div>';
            $b .= '<p class="form-hint">Staff use their email + this password to sign in to the Staff Portal.</p></div>';
        }
        $b .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-save"></i> ' . ($edit?'Update':'Create') . ' Staff Member</button>';
        $b .= '</form></div></div>';
        return self::shell($title, $b, '/admin/staff');
    }

    public static function registrations(array $rows): string {
        $b  = '<div class="admin-header"><h1><i class="fa fa-envelope" style="color:var(--accent)"></i> Interest Registrations</h1>';
        $b .= '<a href="/admin/registrations/export" class="btn btn-outline btn-sm"><i class="fa fa-file-csv"></i> Export CSV</a></div>';
        $b .= '<p class="text-muted text-sm mb-2">' . count($rows) . ' total registration' . (count($rows)!==1?'s':'') . '</p>';
        $b .= '<div class="table-wrap"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Programme</th><th>Date</th><th>Action</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $b .= '<tr>';
            $b .= '<td class="font-semibold">' . self::h($r['first_name'].' '.$r['last_name']) . '</td>';
            $b .= '<td><a href="mailto:' . self::h($r['email']) . '">' . self::h($r['email']) . '</a></td>';
            $b .= '<td>' . self::h($r['phone']??'—') . '</td>';
            $b .= '<td>' . self::h($r['programme_title']) . '</td>';
            $b .= '<td class="text-muted text-sm">' . date('d M Y', strtotime($r['created_at'])) . '</td>';
            $b .= '<td><form action="/admin/registrations/' . (int)$r['id'] . '/delete" method="POST" onsubmit="return confirm(\'Delete this registration?\')"><button class="btn btn-danger btn-sm"><i class="fa fa-trash"></i></button></form></td>';
            $b .= '</tr>';
        }
        $b .= '</tbody></table></div>';
        return self::shell('Registrations', $b, '/admin/registrations');
    }
}