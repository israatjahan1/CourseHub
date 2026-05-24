<?php
declare(strict_types=1);
/**
 * ModuleView — Renders module listing with search and filters,
 * programme-specific module lists, and individual module detail pages.
 */

class ModuleView
{
    private static function h(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    }

    public function renderAllModules(array $modules): string
    {
        $totalModules = count($modules);
        $sharedCount  = count(array_filter($modules, fn($m) => (int)($m['prog_count'] ?? 0) > 1));
        $ugCount      = count(array_filter($modules, fn($m) => str_contains($m['programme_levels'] ?? '', 'Undergraduate')));
        $pgCount      = count(array_filter($modules, fn($m) => str_contains($m['programme_levels'] ?? '', 'Postgraduate')));

        // Collect all unique years for filter buttons
        $years = array_unique(array_column($modules, 'year_of_study'));
        sort($years);

        $css = '<style>
        .filter-bar{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius);padding:1.25rem 1.5rem;margin-bottom:2rem;box-shadow:var(--shadow)}
        .filter-row{display:flex;flex-wrap:wrap;gap:.75rem;align-items:center}
        .filter-label{font-size:.78rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.05em;margin-right:.25rem}
        .filter-chip{padding:.35rem .9rem;border-radius:999px;font-size:.8rem;font-weight:600;border:1.5px solid var(--border);background:var(--white);color:var(--muted);cursor:pointer;transition:all .18s;font-family:inherit}
        .filter-chip:hover{border-color:var(--blue);color:var(--blue)}
        .filter-chip.active{background:var(--blue);color:#fff;border-color:var(--blue)}
        .filter-chip.active-shared{background:var(--accent);color:var(--navy);border-color:var(--accent)}
        .filter-chip.active-ug{background:#1e40af;color:#fff;border-color:#1e40af}
        .filter-chip.active-pg{background:#5b21b6;color:#fff;border-color:#5b21b6}
        .filter-divider{width:1px;height:24px;background:var(--border-light);margin:0 .25rem}
        .mod-item{display:flex;flex-direction:column}
        #no-results{display:none;text-align:center;padding:3rem;color:var(--muted)}
        #results-count{font-size:.85rem;color:var(--muted);margin-bottom:1.25rem}
        </style>';

        $html  = Layout::head('All Modules', $css);
        $html .= Layout::nav('/modules');
        $html .= '<main id="main">';

        // Banner
        $html .= '<div class="page-banner"><div class="container">';
        $html .= '<div class="breadcrumb"><a href="/">Home</a><span class="sep">/</span><span>All Modules</span></div>';
        $html .= '<h1><i class="fa fa-cubes" style="color:var(--accent)"></i> All Modules</h1>';
        $html .= '<p style="opacity:.8;margin-top:.4rem">Browse all ' . $totalModules . ' modules across every programme. Use the filters below to narrow by level, year, or shared status.</p>';
        $html .= '</div></div>';

        $html .= '<div class="container page-pad">';
        $html .= Layout::flash();

        // Stats strip
        $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.75rem">';
        foreach ([
            ['fa-cubes',     $totalModules, 'Total Modules',      'var(--blue)'],
            ['fa-share-alt', $sharedCount,  'Shared Modules',     'var(--accent-dark)'],
            ['fa-book',      $ugCount,      'In UG Programmes',   '#1e40af'],
            ['fa-flask',     $pgCount,      'In PG Programmes',   '#5b21b6'],
        ] as [$icon, $num, $lbl, $col]) {
            $html .= '<div class="card card-body text-center">';
            $html .= '<i class="fa ' . $icon . '" style="color:' . $col . ';font-size:1.3rem;margin-bottom:.3rem"></i>';
            $html .= '<div style="font-size:1.6rem;font-weight:800;color:var(--navy)">' . $num . '</div>';
            $html .= '<div class="text-xs text-muted">' . $lbl . '</div></div>';
        }
        $html .= '</div>';

        // ── Filter bar ────────────────────────────────────────────────────────
        $html .= '<div class="filter-bar">';

        // Row 1 — level + shared
        $html .= '<div class="filter-row">';
        $html .= '<span class="filter-label"><i class="fa fa-filter"></i> Level</span>';
        $html .= '<button class="filter-chip active" data-filter="level" data-val="all" onclick="applyFilter(\'level\',\'all\',this)">All</button>';
        $html .= '<button class="filter-chip" data-filter="level" data-val="ug" onclick="applyFilter(\'level\',\'ug\',this)"><i class="fa fa-graduation-cap"></i> Undergraduate</button>';
        $html .= '<button class="filter-chip" data-filter="level" data-val="pg" onclick="applyFilter(\'level\',\'pg\',this)"><i class="fa fa-flask"></i> Postgraduate</button>';
        $html .= '<div class="filter-divider"></div>';
        $html .= '<span class="filter-label"><i class="fa fa-share-alt"></i> Type</span>';
        $html .= '<button class="filter-chip" data-filter="shared" data-val="shared" onclick="applyFilter(\'shared\',\'shared\',this)"><i class="fa fa-share-alt"></i> Shared only</button>';
        $html .= '</div>';

        // Row 2 — year
        $html .= '<div class="filter-row" style="margin-top:.75rem">';
        $html .= '<span class="filter-label"><i class="fa fa-layer-group"></i> Year</span>';
        $html .= '<button class="filter-chip active" data-filter="year" data-val="all" onclick="applyFilter(\'year\',\'all\',this)">All Years</button>';
        foreach ($years as $yr) {
            $lbl = $yr <= 3 ? 'Year ' . $yr : 'PG Year ' . ($yr - 3);
            $html .= '<button class="filter-chip" data-filter="year" data-val="' . $yr . '" onclick="applyFilter(\'year\',\'' . $yr . '\',this)">' . $lbl . '</button>';
        }
        $html .= '</div>';

        // Reset
        $html .= '<div class="filter-row" style="margin-top:.75rem;border-top:1px solid var(--border-light);padding-top:.75rem">';
        $html .= '<button class="btn btn-ghost btn-sm" onclick="resetFilters()"><i class="fa fa-times"></i> Clear all filters</button>';
        $html .= '<span id="results-count" style="margin-left:auto">' . $totalModules . ' modules shown</span>';
        $html .= '</div>';
        $html .= '</div>'; // end filter-bar

        // ── Module grid ───────────────────────────────────────────────────────
        $html .= '<div class="grid-3" id="module-grid">';
        foreach ($modules as $m) {
            $shared  = (int)($m['prog_count'] ?? 0);
            $levels  = $m['programme_levels'] ?? '';
            $isUg    = str_contains($levels, 'Undergraduate') ? 'true' : 'false';
            $isPg    = str_contains($levels, 'Postgraduate')  ? 'true' : 'false';
            $isShared = ($shared > 1) ? 'true' : 'false';
            $yr      = (int)$m['year_of_study'];

            $t = self::h($m['title']);
            $c = self::h($m['code']);
            $l = self::h($m['leader_name'] ?? '');
            $d = self::h(mb_substr($m['description'] ?? '', 0, 110));

            $html .= '<article class="module-card mod-item"';
            $html .= ' data-year="' . $yr . '"';
            $html .= ' data-ug="' . $isUg . '"';
            $html .= ' data-pg="' . $isPg . '"';
            $html .= ' data-shared="' . $isShared . '">';

            // Shared banner
            if ($shared > 1) {
                $html .= '<div style="background:linear-gradient(to right,#fef3c7,#fffbf0);border-left:3px solid var(--accent);padding:.25rem .75rem;margin:-1.2rem -1.2rem .75rem;font-size:.72rem;font-weight:700;color:var(--accent-dark)">';
                $html .= '<i class="fa fa-share-alt"></i> Shared across ' . $shared . ' programmes</div>';
            }

            $html .= '<div class="flex justify-between items-start">';
            $html .= '<span class="module-code">' . $c . '</span>';
            $html .= '<div class="flex gap-1 flex-wrap">';
            $html .= '<span class="tag"><i class="fa fa-star-half-alt"></i> ' . (int)$m['credits'] . ' cr</span>';

            // Level badges
            if (str_contains($levels, 'Undergraduate')) $html .= '<span class="badge badge-ug" style="font-size:.65rem">UG</span>';
            if (str_contains($levels, 'Postgraduate'))  $html .= '<span class="badge badge-pg" style="font-size:.65rem">PG</span>';
            $html .= '</div></div>';

            $html .= '<p class="font-semibold mt-1" style="font-size:.95rem"><a href="/modules/' . (int)$m['id'] . '" style="color:var(--navy)">' . $t . '</a></p>';
            $html .= '<p class="text-xs text-muted mt-1"><i class="fa fa-layer-group"></i> ' . ($yr <= 3 ? 'Year ' . $yr : 'PG Year ' . ($yr - 3)) . '</p>';
            if ($l) $html .= '<p class="text-xs text-muted"><i class="fa fa-user"></i> ' . $l . '</p>';
            if ($d) $html .= '<p class="text-sm text-muted mt-1" style="flex:1">' . $d . (strlen($m['description'] ?? '') > 110 ? '…' : '') . '</p>';
            $html .= '<a href="/modules/' . (int)$m['id'] . '" class="btn btn-ghost btn-sm mt-2" style="align-self:flex-start;font-size:.8rem"><i class="fa fa-eye"></i> View Details</a>';
            $html .= '</article>';
        }
        $html .= '</div>';

        // No results message
        $html .= '<div id="no-results"><i class="fa fa-search" style="font-size:2.5rem;color:var(--border);margin-bottom:1rem;display:block"></i>';
        $html .= '<p class="font-semibold">No modules match your filters.</p>';
        $html .= '<button class="btn btn-primary btn-sm mt-2" onclick="resetFilters()">Clear filters</button></div>';

        $html .= '</div></main>';
        $html .= Layout::footer();

        // ── Filter JS ─────────────────────────────────────────────────────────
        $html .= '<script>
const state = { level: "all", year: "all", shared: false };

function applyFilter(type, val, btn) {
    // Toggle shared off if clicking again
    if (type === "shared") {
        state.shared = !state.shared;
        btn.classList.toggle("active-shared", state.shared);
        btn.classList.toggle("active", false);
    } else {
        state[type] = val;
        // Update chip active states for this filter group
        document.querySelectorAll("[data-filter=\'" + type + "\']").forEach(c => {
            c.classList.remove("active","active-ug","active-pg");
        });
        if (val === "ug") btn.classList.add("active-ug");
        else if (val === "pg") btn.classList.add("active-pg");
        else btn.classList.add("active");
    }
    runFilter();
}

function runFilter() {
    const cards = document.querySelectorAll(".mod-item");
    let visible = 0;
    cards.forEach(card => {
        const yr     = card.dataset.year;
        const isUg   = card.dataset.ug === "true";
        const isPg   = card.dataset.pg === "true";
        const isShared = card.dataset.shared === "true";

        let show = true;

        // Level filter
        if (state.level === "ug" && !isUg) show = false;
        if (state.level === "pg" && !isPg) show = false;

        // Year filter
        if (state.year !== "all" && yr !== state.year) show = false;

        // Shared filter
        if (state.shared && !isShared) show = false;

        card.style.display = show ? "" : "none";
        if (show) visible++;
    });

    document.getElementById("results-count").textContent = visible + " module" + (visible !== 1 ? "s" : "") + " shown";
    document.getElementById("no-results").style.display = visible === 0 ? "block" : "none";
    document.getElementById("module-grid").style.display = visible === 0 ? "none" : "";
}

function resetFilters() {
    state.level = "all"; state.year = "all"; state.shared = false;
    document.querySelectorAll(".filter-chip").forEach(c => {
        c.classList.remove("active","active-ug","active-pg","active-shared");
    });
    // Re-activate the "all" defaults
    document.querySelectorAll("[data-val=\'all\']").forEach(c => c.classList.add("active"));
    runFilter();
}
</script>';
        return $html;
    }

    public function render(array $modules, int $programmeId): string
    {
        $html  = Layout::head('Programme Modules');
        $html .= Layout::nav('/programmes');
        $html .= '<main id="main"><div class="container page-pad">';
        $html .= Layout::flash();
        $html .= '<a href="/programmes" class="btn btn-ghost btn-sm mb-2"><i class="fa fa-arrow-left"></i> All Programmes</a>';
        $html .= '<div class="section-head"><h2>Modules for Programme #' . $programmeId . '</h2></div>';
        if (empty($modules)) {
            $html .= '<div class="card card-body text-center" style="padding:3rem"><i class="fa fa-book" style="font-size:2.5rem;color:var(--border);margin-bottom:1rem"></i><p>No modules found.</p></div>';
        } else {
            $html .= '<div class="grid-3">';
            foreach ($modules as $m) $html .= $this->moduleCard($m);
            $html .= '</div>';
        }
        $html .= '</div></main>' . Layout::footer();
        return $html;
    }

    public function renderModuleDetail(array $module, array $programmes): string
    {
        $title  = self::h($module['title']);
        $code   = self::h($module['code']);
        $leader = self::h($module['leader_name'] ?? '');
        $lemail = self::h($module['leader_email'] ?? '');
        $desc   = self::h($module['description'] ?? '');
        $shared = (int)(count($programmes));

        $css = '<style>
        .md-grid{display:grid;grid-template-columns:1fr 340px;gap:2.5rem;align-items:start}
        @media(max-width:900px){.md-grid{grid-template-columns:1fr}}
        .sticky-side{position:sticky;top:85px}
        </style>';

        $html  = Layout::head($title . ' — Module', $css);
        $html .= Layout::nav('/modules');
        $html .= '<main id="main">';

        // Header
        $html .= '<div style="background:linear-gradient(135deg,var(--navy),var(--blue));color:#fff;padding:2.5rem 0">';
        $html .= '<div class="container">';
        $html .= '<div class="breadcrumb"><a href="/">Home</a><span class="sep">/</span><a href="/modules">Modules</a><span class="sep">/</span><span>' . $title . '</span></div>';
        $html .= '<div class="flex gap-2 items-center flex-wrap mt-2">';
        $html .= '<code style="background:rgba(255,255,255,.15);color:#fff;padding:.2rem .7rem;border-radius:6px;font-family:JetBrains Mono,monospace;font-size:.85rem">' . $code . '</code>';
        $html .= '<span class="badge" style="background:rgba(255,255,255,.15);color:#fff">Year ' . (int)$module['year_of_study'] . '</span>';
        $html .= '<span class="badge" style="background:rgba(255,255,255,.15);color:#fff">' . (int)$module['credits'] . ' credits</span>';
        if ($shared > 1) $html .= '<span class="badge badge-shared"><i class="fa fa-share-alt"></i> Shared across ' . $shared . ' programmes</span>';
        $html .= '</div>';
        $html .= '<h1 style="font-family:Merriweather,serif;font-size:clamp(1.5rem,3vw,2rem);margin-top:.6rem">' . $title . '</h1>';
        if ($leader) $html .= '<p style="opacity:.75;margin-top:.4rem"><i class="fa fa-user-tie"></i> Module Leader: ' . $leader . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="container page-pad"><div class="md-grid">';

        // Left column
        $html .= '<div>';
        $html .= Layout::flash();

        if ($desc) {
            $html .= '<div class="section-head"><h2>Module Overview</h2></div>';
            $html .= '<p style="line-height:1.85;color:var(--text)">' . $desc . '</p>';
        }

        // Shared across programmes
        if (!empty($programmes)) {
            $html .= '<div class="section-head"><h2>Offered In</h2><p>This module is taught as part of ' . count($programmes) . ' programme' . (count($programmes) !== 1 ? 's' : '') . '</p></div>';

            if (count($programmes) > 1) {
                $html .= '<div class="card card-body" style="background:var(--warn-bg);border:1px solid #fde68a;margin-bottom:1.5rem">';
                $html .= '<p class="text-sm" style="color:var(--warn)"><i class="fa fa-share-alt"></i> <strong>Shared Module</strong> — this module is taught across multiple programmes, meaning students from different degree paths study it together.</p>';
                $html .= '</div>';
            }

            $html .= '<div class="flex flex-wrap gap-1">';
            foreach ($programmes as $p) {
                $b = $p['level'] === 'Undergraduate' ? 'badge-ug' : 'badge-pg';
                $html .= '<a href="/programmes/' . self::h($p['slug']) . '" class="badge ' . $b . '" style="font-size:.82rem;padding:.35rem 1rem;text-decoration:none">' . self::h($p['title']) . '</a>';
            }
            $html .= '</div>';
        }
        $html .= '</div>';

        // Sidebar
        $html .= '<aside class="sticky-side">';

        if ($leader) {
            $initials = mb_strtoupper(mb_substr(implode('', array_map(fn($w) => $w[0], array_filter(explode(' ', $module['leader_name'] ?? 'S')))), 0, 2));
            $html .= '<div class="card mb-2"><div class="card-header"><h3><i class="fa fa-user-tie"></i> Module Leader</h3></div><div class="card-body">';
            $html .= '<div class="flex gap-2 items-center">';
            if (!empty($module['leader_photo'])) {
                $html .= '<img src="' . self::h($module['leader_photo']) . '" alt="' . $leader . '" style="width:50px;height:50px;border-radius:50%;object-fit:cover">';
            } else {
                $html .= '<div style="width:50px;height:50px;border-radius:50%;background:var(--light);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--blue)">' . $initials . '</div>';
            }
            $html .= '<div><p class="font-semibold">' . $leader . '</p>';
            if ($lemail) $html .= '<a href="mailto:' . $lemail . '" class="text-sm">' . $lemail . '</a>';
            if (!empty($module['leader_phone'])) $html .= '<p class="text-xs text-muted"><i class="fa fa-phone"></i> ' . self::h($module['leader_phone']) . '</p>';
            if (!empty($module['leader_office'])) $html .= '<p class="text-xs text-muted"><i class="fa fa-map-marker-alt"></i> ' . self::h($module['leader_office']) . '</p>';
            $html .= '</div></div>';
            if (!empty($module['module_leader_id'])) {
                $html .= '<a href="/staff/' . (int)$module['module_leader_id'] . '" class="btn btn-outline btn-sm w-full mt-2"><i class="fa fa-user"></i> View Full Profile</a>';
            }
            $html .= '</div></div>';
        }

        // Quick info card
        $html .= '<div class="card"><div class="card-header"><h3><i class="fa fa-info-circle"></i> Quick Info</h3></div><div class="card-body">';
        $rows = [
            ['Module Code', '<code style="font-family:JetBrains Mono,monospace;font-size:.85rem">' . $code . '</code>'],
            ['Year of Study', 'Year ' . (int)$module['year_of_study']],
            ['Credits', (int)$module['credits'] . ' credits'],
            ['Programmes', count($programmes) . ' programme' . (count($programmes) !== 1 ? 's' : '')],
        ];
        foreach ($rows as [$label, $value]) {
            $html .= '<div style="display:flex;justify-content:space-between;align-items:center;padding:.55rem 0;border-bottom:1px solid var(--border-light)">';
            $html .= '<span class="text-sm text-muted">' . $label . '</span>';
            $html .= '<span class="text-sm font-semibold">' . $value . '</span></div>';
        }
        $html .= '</div></div>';
        $html .= '</aside>';

        $html .= '</div></div></main>' . Layout::footer();
        return $html;
    }

    private function moduleCard(array $m): string
    {
        $t      = self::h($m['title']);
        $c      = self::h($m['code']);
        $l      = self::h($m['leader_name'] ?? '');
        $d      = self::h(mb_substr($m['description'] ?? '', 0, 100));
        $shared = (int)($m['prog_count'] ?? 0);

        $html  = '<article class="module-card" style="display:flex;flex-direction:column">';
        $html .= '<div class="flex justify-between items-start">';
        $html .= '<span class="module-code">' . $c . '</span>';
        $html .= '<div class="flex gap-1">';
        $html .= '<span class="tag"><i class="fa fa-star-half-alt"></i> ' . (int)$m['credits'] . ' cr</span>';
        if ($shared > 1) $html .= '<span class="badge badge-shared" title="Shared across ' . $shared . ' programmes"><i class="fa fa-share-alt"></i> ×' . $shared . '</span>';
        $html .= '</div></div>';
        $html .= '<p class="font-semibold mt-1" style="font-size:.95rem"><a href="/modules/' . (int)$m['id'] . '" style="color:var(--navy)">' . $t . '</a></p>';
        $html .= '<p class="text-xs text-muted mt-1"><i class="fa fa-layer-group"></i> Year ' . (int)$m['year_of_study'] . '</p>';
        if ($l) $html .= '<p class="text-xs text-muted"><i class="fa fa-user"></i> ' . $l . '</p>';
        if ($d) $html .= '<p class="text-sm text-muted mt-1" style="flex:1">' . $d . (strlen($m['description'] ?? '') > 100 ? '…' : '') . '</p>';
        $html .= '<a href="/modules/' . (int)$m['id'] . '" class="btn btn-ghost btn-sm mt-2" style="align-self:flex-start;font-size:.8rem"><i class="fa fa-eye"></i> View Details</a>';
        $html .= '</article>';
        return $html;
    }
}