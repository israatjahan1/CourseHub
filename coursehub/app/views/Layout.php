<?php
declare(strict_types=1);

class Layout
{
    public static function head(string $title, string $extra = ''): string
    {
        $app = 'CourseHub';
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>{$title} · {$app}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Merriweather:wght@400;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#0a2342;--blue:#1d6fa4;--blue-light:#2589c7;--accent:#e8a020;--accent-dark:#c8881a;
  --light:#f0f4f9;--lighter:#f8fafc;--white:#fff;--text:#1a2535;
  --muted:#5a6a7e;--border:#dde4ed;--border-light:#eef1f6;
  --success:#15803d;--success-bg:#dcfce7;
  --error:#dc2626;--error-bg:#fee2e2;
  --warn:#ca8a04;--warn-bg:#fef9c3;
  --info:#1d6fa4;--info-bg:#dbeafe;
  --radius:12px;--radius-sm:8px;--radius-xs:6px;
  --shadow:0 1px 3px rgba(0,0,0,.08),0 4px 16px rgba(0,0,0,.06);
  --shadow-md:0 4px 12px rgba(0,0,0,.1),0 8px 32px rgba(0,0,0,.08);
  --shadow-lg:0 8px 24px rgba(0,0,0,.12),0 16px 48px rgba(0,0,0,.1);
}
html{scroll-behavior:smooth;font-size:16px}
body{font-family:'Inter',system-ui,sans-serif;color:var(--text);background:var(--lighter);line-height:1.7;min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased}
a{color:var(--blue);text-decoration:none;transition:color .15s}
a:hover{color:var(--blue-light);text-decoration:underline}
img{max-width:100%;height:auto;display:block}
code,pre{font-family:'JetBrains Mono',monospace}
h1,h2,h3,h4{line-height:1.3;letter-spacing:-.01em}

/* ── Skip link ── */
.skip{position:absolute;top:-50px;left:1rem;background:var(--navy);color:#fff;padding:.6rem 1.2rem;border-radius:0 0 var(--radius-sm) var(--radius-sm);z-index:9999;transition:top .2s;font-weight:600}
.skip:focus{top:0}

/* ── Nav ── */
.nav{background:rgba(10,35,66,.97);backdrop-filter:blur(12px);position:sticky;top:0;z-index:200;border-bottom:1px solid rgba(255,255,255,.06)}
.nav-inner{max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;padding:.85rem 2rem;gap:1rem}
.nav-logo{font-family:'Merriweather',serif;font-size:1.15rem;color:#fff;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.5rem;flex-shrink:0}
.nav-logo .accent{color:var(--accent)}
.nav-logo .dot{width:6px;height:6px;background:var(--accent);border-radius:50%;display:inline-block}
.nav-links{display:flex;gap:.25rem;list-style:none;align-items:center}
.nav-links a{color:rgba(255,255,255,.8);font-size:.875rem;font-weight:500;padding:.5rem .85rem;border-radius:var(--radius-xs);transition:all .15s;text-decoration:none}
.nav-links a:hover{color:#fff;background:rgba(255,255,255,.08);text-decoration:none}
.nav-links a.active{color:var(--accent);background:rgba(232,160,32,.1)}
.nav-links .btn-nav{background:var(--accent);color:var(--navy)!important;font-weight:700;padding:.45rem 1rem;border-radius:var(--radius-xs)}
.nav-links .btn-nav:hover{background:var(--accent-dark);text-decoration:none}
.nav-toggle{display:none;background:none;border:2px solid rgba(255,255,255,.3);border-radius:var(--radius-xs);cursor:pointer;padding:.4rem .6rem;color:#fff}

/* ── Flash messages ── */
.flash{padding:.85rem 1.25rem;border-radius:var(--radius-sm);margin:1rem 0;font-size:.9rem;display:flex;align-items:flex-start;gap:.75rem;border:1px solid transparent}
.flash i{margin-top:.1rem;flex-shrink:0}
.flash-success{background:var(--success-bg);color:#14532d;border-color:#bbf7d0}
.flash-error{background:var(--error-bg);color:#7f1d1d;border-color:#fecaca}
.flash-warning{background:var(--warn-bg);color:#713f12;border-color:#fde68a}
.flash-info{background:var(--info-bg);color:#1e3a5f;border-color:#bfdbfe}

/* ── Layout ── */
.container{max-width:1280px;margin:0 auto;padding:0 2rem}
main{flex:1}
.page-pad{padding-bottom:4rem}

/* ── Hero ── */
.hero{background:linear-gradient(135deg,var(--navy) 0%,#1a4a7a 50%,var(--blue) 100%);color:#fff;padding:6rem 2rem;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")}
.hero-content{position:relative;z-index:1;max-width:700px;margin:0 auto}
.hero h1{font-family:'Merriweather',serif;font-size:clamp(2rem,5vw,3.25rem);margin-bottom:1.25rem;line-height:1.2}
.hero p{font-size:1.15rem;opacity:.88;margin-bottom:2rem;line-height:1.7}
.hero-actions{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap}

/* ── Buttons ── */
.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.65rem 1.5rem;border-radius:var(--radius-xs);font-weight:600;font-size:.9rem;cursor:pointer;border:2px solid transparent;transition:all .2s;text-decoration:none;font-family:inherit;white-space:nowrap;line-height:1.4}
.btn:hover{text-decoration:none;transform:translateY(-1px)}
.btn:active{transform:translateY(0)}
.btn-primary{background:var(--blue);color:#fff;border-color:var(--blue)}
.btn-primary:hover{background:var(--navy);border-color:var(--navy);color:#fff}
.btn-accent{background:var(--accent);color:var(--navy);border-color:var(--accent)}
.btn-accent:hover{background:var(--accent-dark);border-color:var(--accent-dark);color:var(--navy)}
.btn-outline{border-color:var(--border);color:var(--muted);background:var(--white)}
.btn-outline:hover{border-color:var(--blue);color:var(--blue);background:var(--info-bg)}
.btn-danger{background:var(--error);color:#fff;border-color:var(--error)}
.btn-danger:hover{background:#b91c1c;border-color:#b91c1c;color:#fff}
.btn-success{background:var(--success);color:#fff}
.btn-ghost{background:transparent;color:var(--muted);border-color:transparent}
.btn-ghost:hover{background:var(--light);color:var(--text)}
.btn-white{background:#fff;color:var(--navy);border-color:rgba(255,255,255,.3)}
.btn-white:hover{background:rgba(255,255,255,.9);color:var(--navy)}
.btn-sm{padding:.4rem 1rem;font-size:.8rem}
.btn-lg{padding:.85rem 2rem;font-size:1rem}
.btn-icon{padding:.5rem;border-radius:var(--radius-xs);width:38px;height:38px;justify-content:center}

/* ── Cards ── */
.card{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;transition:transform .2s,box-shadow .2s;border:1px solid var(--border-light)}
.card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md)}
.card-body{padding:1.5rem}
.card-header{padding:1rem 1.5rem;border-bottom:1px solid var(--border-light);display:flex;align-items:center;justify-content:space-between;background:var(--lighter)}
.card-header h2,.card-header h3{font-size:1rem;font-weight:700;color:var(--navy)}
.card-img-wrap{width:100%;height:200px;overflow:hidden;background:linear-gradient(135deg,var(--navy),var(--blue));display:flex;align-items:center;justify-content:center;font-size:3.5rem}
.card-img-wrap img{width:100%;height:100%;object-fit:cover}

/* ── Badges ── */
.badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .75rem;border-radius:999px;font-size:.72rem;font-weight:700;letter-spacing:.02em;text-transform:uppercase}
.badge-ug{background:#dbeafe;color:#1e40af}
.badge-pg{background:#ede9fe;color:#5b21b6}
.badge-pub{background:var(--success-bg);color:var(--success)}
.badge-draft{background:var(--warn-bg);color:var(--warn)}
.badge-new{background:#fee2e2;color:#991b1b}
.badge-shared{background:#fef3c7;color:#92400e}

/* ── Grids ── */
.grid-4{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem}
.grid-3{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.5rem}
.grid-2{display:grid;grid-template-columns:repeat(auto-fill,minmax(380px,1fr));gap:1.5rem}

/* ── Section heading ── */
.section-head{margin:3rem 0 1.5rem}
.section-head h2{font-family:'Merriweather',serif;font-size:1.75rem;color:var(--navy)}
.section-head p{color:var(--muted);margin-top:.4rem;font-size:.95rem}
.section-head::after{content:'';display:block;width:48px;height:3px;background:var(--accent);margin-top:.6rem;border-radius:2px}

/* ── Stats strip ── */
.stats-strip{background:var(--navy);color:#fff;padding:3rem 2rem}
.stats-inner{max-width:1280px;margin:0 auto;display:flex;justify-content:center;gap:5rem;flex-wrap:wrap}
.stat-item{text-align:center}
.stat-item .num{font-size:2.75rem;font-weight:800;color:var(--accent);line-height:1}
.stat-item .lbl{font-size:.85rem;opacity:.75;margin-top:.4rem;text-transform:uppercase;letter-spacing:.05em}

/* ── Forms ── */
.form-group{margin-bottom:1.25rem}
.form-group label{display:block;font-weight:600;font-size:.85rem;margin-bottom:.4rem;color:var(--text)}
.req{color:var(--error)}
.form-hint{font-size:.78rem;color:var(--muted);margin-top:.3rem}
.form-control{width:100%;padding:.65rem 1rem;border:1.5px solid var(--border);border-radius:var(--radius-xs);font-size:.9rem;font-family:inherit;transition:border-color .2s,box-shadow .2s;background:var(--white);color:var(--text)}
.form-control:focus{outline:none;border-color:var(--blue);box-shadow:0 0 0 3px rgba(29,111,164,.15)}
.form-control:hover{border-color:var(--blue-light)}
textarea.form-control{resize:vertical;min-height:120px}
select.form-control{cursor:pointer}
.input-wrap{position:relative}
.input-wrap .form-control{padding-right:2.8rem}
.input-wrap .input-icon{position:absolute;right:.85rem;top:50%;transform:translateY(-50%);color:var(--muted);cursor:pointer;background:none;border:none;font-size:.95rem;padding:0;line-height:1}
.input-wrap .input-icon:hover{color:var(--blue)}

/* ── Tables ── */
.table-wrap{overflow-x:auto;border-radius:var(--radius);box-shadow:var(--shadow);border:1px solid var(--border-light)}
table{width:100%;border-collapse:collapse;background:var(--white);font-size:.875rem}
thead tr{background:var(--navy);color:#fff}
th{padding:.8rem 1.1rem;text-align:left;font-weight:600;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em}
td{padding:.75rem 1.1rem;border-bottom:1px solid var(--border-light);vertical-align:middle}
tbody tr:last-child td{border-bottom:none}
tbody tr:hover{background:var(--lighter)}
tbody tr:nth-child(even){background:#fafbfd}

/* ── Programme cards ── */
.prog-card{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;border:1px solid var(--border-light);transition:transform .22s,box-shadow .22s;display:flex;flex-direction:column}
.prog-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-md)}
.prog-card-img{height:180px;background:linear-gradient(135deg,var(--navy) 0%,var(--blue) 100%);display:flex;align-items:center;justify-content:center;font-size:3rem;position:relative;overflow:hidden}
.prog-card-img img{width:100%;height:100%;object-fit:cover}
.prog-card-img .overlay{position:absolute;inset:0;background:linear-gradient(to top,rgba(10,35,66,.5),transparent)}
.prog-card-body{padding:1.4rem;flex:1;display:flex;flex-direction:column}
.prog-card-footer{padding:1rem 1.4rem;border-top:1px solid var(--border-light);display:flex;gap:.5rem;align-items:center;background:var(--lighter)}

/* ── Module card ── */
.module-card{background:var(--white);border:1px solid var(--border-light);border-radius:var(--radius-sm);padding:1.1rem 1.25rem;transition:box-shadow .2s,border-color .2s}
.module-card:hover{box-shadow:var(--shadow);border-color:var(--blue)}
.module-card.mine{border-left:3px solid var(--accent);background:linear-gradient(to right,#fffbf0,var(--white))}
.module-code{font-family:'JetBrains Mono',monospace;font-size:.72rem;background:var(--light);color:var(--muted);padding:.15rem .5rem;border-radius:4px;display:inline-block}

/* ── Staff card ── */
.staff-card{background:var(--white);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;border:1px solid var(--border-light);transition:transform .2s,box-shadow .2s}
.staff-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-md)}
.staff-avatar{width:100%;height:200px;background:linear-gradient(135deg,var(--navy),var(--blue));display:flex;align-items:center;justify-content:center;font-size:5rem;color:rgba(255,255,255,.4)}
.staff-avatar img{width:100%;height:100%;object-fit:cover}
.staff-card-body{padding:1.4rem}

/* ── Utilities ── */
.flex{display:flex}.gap-1{gap:.5rem}.gap-2{gap:1rem}.gap-3{gap:1.5rem}
.items-center{align-items:center}.justify-between{justify-content:space-between}.justify-center{justify-content:center}
.flex-wrap{flex-wrap:wrap}.flex-col{flex-direction:column}
.mt-1{margin-top:.5rem}.mt-2{margin-top:1rem}.mt-3{margin-top:1.5rem}.mt-4{margin-top:2rem}
.mb-1{margin-bottom:.5rem}.mb-2{margin-bottom:1rem}
.text-muted{color:var(--muted)}.text-sm{font-size:.875rem}.text-xs{font-size:.78rem}
.text-center{text-align:center}.text-right{text-align:right}
.w-full{width:100%}.font-bold{font-weight:700}.font-semibold{font-weight:600}
.text-navy{color:var(--navy)}.text-blue{color:var(--blue)}.text-accent{color:var(--accent)}
.divider{border:none;border-top:1px solid var(--border-light);margin:2rem 0}
.tag{display:inline-flex;align-items:center;gap:.3rem;background:var(--light);color:var(--muted);padding:.2rem .6rem;border-radius:999px;font-size:.75rem;font-weight:500}

/* ── Page banner ── */
.page-banner{background:linear-gradient(135deg,var(--navy),var(--blue));color:#fff;padding:2.5rem 2rem}
.page-banner h1{font-family:'Merriweather',serif;font-size:clamp(1.5rem,3vw,2.25rem)}
.breadcrumb{display:flex;gap:.5rem;align-items:center;font-size:.8rem;opacity:.75;margin-bottom:.6rem}
.breadcrumb a{color:rgba(255,255,255,.8);text-decoration:none}
.breadcrumb a:hover{color:#fff}
.breadcrumb .sep{opacity:.5}

/* ── Footer ── */
footer{background:#071626;color:rgba(255,255,255,.55);padding:0;margin-top:auto}
.footer-main{max-width:1280px;margin:0 auto;padding:3rem 2rem;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:3rem}
.footer-brand .logo{font-family:'Merriweather',serif;font-size:1.1rem;color:#fff;font-weight:700;margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem}
.footer-brand .logo span{color:var(--accent)}
.footer-brand p{font-size:.85rem;line-height:1.7;max-width:280px}
.footer-col h4{color:rgba(255,255,255,.85);font-size:.85rem;font-weight:700;margin-bottom:.85rem;text-transform:uppercase;letter-spacing:.06em}
.footer-col ul{list-style:none}
.footer-col ul li{margin-bottom:.5rem}
.footer-col ul li a{color:rgba(255,255,255,.55);font-size:.85rem;transition:color .15s}
.footer-col ul li a:hover{color:var(--accent);text-decoration:none}
.footer-contact p{font-size:.85rem;margin-bottom:.5rem;display:flex;align-items:flex-start;gap:.5rem}
.footer-contact i{margin-top:.2rem;color:var(--accent);flex-shrink:0}
.footer-bottom{border-top:1px solid rgba(255,255,255,.07);padding:1.25rem 2rem;max-width:1280px;margin:0 auto;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;font-size:.8rem}

/* ── Responsive ── */
@media(max-width:1024px){
  .footer-main{grid-template-columns:1fr 1fr}
  .footer-brand{grid-column:1/-1}
}
@media(max-width:768px){
  .nav-inner{padding:.75rem 1.25rem}
  .nav-toggle{display:flex;align-items:center;gap:.4rem}
  .nav-links{display:none;position:fixed;top:60px;left:0;right:0;background:rgba(10,35,66,.98);flex-direction:column;padding:1.25rem;gap:.25rem;max-height:calc(100vh - 60px);overflow-y:auto;border-top:1px solid rgba(255,255,255,.1)}
  .nav-links.open{display:flex}
  .nav-links a{padding:.75rem 1rem;border-radius:var(--radius-xs)}
  .container{padding:0 1.25rem}
  .grid-4,.grid-3,.grid-2{grid-template-columns:1fr}
  .stats-inner{gap:2.5rem}
  .footer-main{grid-template-columns:1fr;gap:2rem;padding:2rem 1.25rem}
  .footer-bottom{padding:1rem 1.25rem;flex-direction:column;text-align:center}
  .hero{padding:4rem 1.25rem}
  .page-banner{padding:2rem 1.25rem}
}
{$extra}
</style>
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>
HTML;
    }

    public static function nav(string $active = ''): string
    {
        $loggedIn  = !empty($_SESSION['student_id']);
        $firstName = htmlspecialchars($_SESSION['student_first_name'] ?? '', ENT_QUOTES, 'UTF-8');
        $links = ['/' => 'Home', '/programmes' => 'Programmes', '/modules' => 'All Modules', '/staff' => 'Staff'];
        $html  = '<nav class="nav" role="navigation" aria-label="Main navigation">';
        $html .= '<div class="nav-inner">';
        $html .= '<a class="nav-logo" href="/"><span class="accent">Course</span><span class="dot"></span>Hub</a>';
        $html .= '<button class="nav-toggle" aria-controls="main-nav" aria-expanded="false" aria-label="Toggle menu"><i class="fa fa-bars"></i> Menu</button>';
        $html .= '<ul class="nav-links" id="main-nav" role="list">';
        foreach ($links as $href => $label) {
            $cls   = $active === $href ? ' class="active"' : '';
            $html .= "<li><a href=\"{$href}\"{$cls}>{$label}</a></li>";
        }
        if ($loggedIn) {
            $html .= '<li><a href="/account"' . ($active === '/account' ? ' class="active"' : '') . '><i class="fa fa-user-circle"></i> ' . $firstName . '</a></li>';
            $html .= '<li><a href="/logout"><i class="fa fa-sign-out-alt"></i> Logout</a></li>';
        } else {
            $html .= '<li><a href="/login"' . ($active === '/login' ? ' class="active"' : '') . '><i class="fa fa-sign-in-alt"></i> Sign In</a></li>';
            $html .= '<li><a href="/register" class="btn-nav"><i class="fa fa-user-plus"></i> Register</a></li>';
        }
        $html .= '</ul></div></nav>';
        return $html;
    }

    public static function flash(): string
    {
        if (session_status() === PHP_SESSION_NONE) return '';
        $icons = ['success' => 'fa-check-circle', 'error' => 'fa-times-circle', 'warning' => 'fa-exclamation-triangle', 'info' => 'fa-info-circle'];
        $html  = '';
        foreach (['success', 'error', 'warning', 'info'] as $type) {
            if (!empty($_SESSION["flash_{$type}"])) {
                $msg  = htmlspecialchars($_SESSION["flash_{$type}"], ENT_QUOTES, 'UTF-8');
                $icon = $icons[$type];
                $html .= "<div class=\"flash flash-{$type}\" role=\"alert\"><i class=\"fa {$icon}\"></i><span>{$msg}</span></div>";
                unset($_SESSION["flash_{$type}"]);
            }
        }
        return $html;
    }

    public static function footer(): string
    {
        $year = date('Y');
        return <<<HTML
<footer role="contentinfo">
  <div class="footer-main">
    <div class="footer-brand">
      <div class="logo"><span>Course</span>Hub</div>
      <p>CourseHub connects prospective students with the right degree programmes. Explore, compare, and register your interest in our undergraduate and postgraduate offerings.</p>
    </div>
    <div class="footer-col">
      <h4>Explore</h4>
      <ul>
        <li><a href="/programmes?level=Undergraduate">Undergraduate</a></li>
        <li><a href="/programmes?level=Postgraduate">Postgraduate</a></li>
        <li><a href="/modules">All Modules</a></li>
        <li><a href="/staff">Academic Staff</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Account</h4>
      <ul>
        <li><a href="/register">Create Account</a></li>
        <li><a href="/login">Sign In</a></li>
        <li><a href="/account">My Dashboard</a></li>
        <li><a href="/interest/withdraw">Withdraw Interest</a></li>
      </ul>
    </div>
    <div class="footer-col footer-contact">
      <h4>Contact</h4>
      <p><i class="fa fa-envelope"></i> <a href="mailto:admissions@coursehub.ac.uk">admissions@coursehub.ac.uk</a></p>
      <p><i class="fa fa-phone"></i> +44 (0)1234 567 000</p>
      <p><i class="fa fa-map-marker-alt"></i> CourseHub University<br>Academic House, London EC1A 1BB</p>
      <p><i class="fa fa-clock"></i> Mon–Fri, 9 am – 5 pm</p>
    </div>
  </div>
  <div class="footer-bottom">
    <span>&copy; {$year} CourseHub. All rights reserved.</span>
    <span>Built with the Slim Framework &middot; <a href="/admin/login">Staff &amp; Admin</a></span>
  </div>
</footer>
<script>
const toggle=document.querySelector('.nav-toggle'),nav=document.getElementById('main-nav');
if(toggle&&nav){toggle.addEventListener('click',()=>{const o=nav.classList.toggle('open');toggle.setAttribute('aria-expanded',o);})}
// Password show/hide
document.querySelectorAll('.input-icon[data-toggle-pw]').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const inp=btn.closest('.input-wrap').querySelector('input');
    const isText=inp.type==='text';
    inp.type=isText?'password':'text';
    btn.innerHTML=isText?'<i class="fa fa-eye"></i>':'<i class="fa fa-eye-slash"></i>';
  });
});
</script>
</body></html>
HTML;
    }
}
