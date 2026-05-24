<?php
declare(strict_types=1);
/**
 * StudentAuthView — Renders student authentication pages including login,
 * registration, password reset flow, account dashboard, and profile editing.
 */
class StudentAuthView
{
    private static function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

    public function renderRegister(): string {
        $html  = Layout::head('Create Account');
        $html .= Layout::nav();
        $html .= '<main id="main"><div class="container page-pad" style="max-width:520px;margin-top:2.5rem">';
        $html .= '<a href="/login" class="btn btn-ghost btn-sm mb-2"><i class="fa fa-arrow-left"></i> Already have an account?</a>';
        $html .= '<div class="card"><div class="card-header" style="background:var(--navy)"><h1 style="color:#fff;font-size:1.2rem"><i class="fa fa-user-plus"></i> Create Student Account</h1></div>';
        $html .= '<div class="card-body">' . Layout::flash();
        $html .= '<form action="/register" method="POST" novalidate>';
        $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">';
        $html .= '<div class="form-group"><label for="fn">First Name <span class="req">*</span></label><input type="text" id="fn" name="first_name" class="form-control" required autocomplete="given-name" autofocus></div>';
        $html .= '<div class="form-group"><label for="ln">Last Name <span class="req">*</span></label><input type="text" id="ln" name="last_name" class="form-control" required autocomplete="family-name"></div>';
        $html .= '</div>';
        $html .= '<div class="form-group"><label for="em">Email Address <span class="req">*</span></label><input type="email" id="em" name="email" class="form-control" required autocomplete="email"></div>';
        $html .= '<div class="form-group"><label for="pw">Password <span class="req">*</span></label>';
        $html .= '<div class="input-wrap"><input type="password" id="pw" name="password" class="form-control" required autocomplete="new-password">';
        $html .= '<button type="button" class="input-icon" data-toggle-pw title="Show/hide"><i class="fa fa-eye"></i></button></div>';
        $html .= '<p class="form-hint">Minimum 8 characters</p></div>';
        $html .= '<div class="form-group"><label for="pw2">Confirm Password <span class="req">*</span></label>';
        $html .= '<div class="input-wrap"><input type="password" id="pw2" name="password_confirm" class="form-control" required autocomplete="new-password">';
        $html .= '<button type="button" class="input-icon" data-toggle-pw title="Show/hide"><i class="fa fa-eye"></i></button></div></div>';
        $html .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-check"></i> Create Account</button>';
        $html .= '</form>';
        $html .= '<p class="text-xs text-muted text-center mt-2">By registering you agree to receive programme communications. <a href="/interest/withdraw">Withdraw at any time</a>.</p>';
        $html .= '</div></div></div></main>' . Layout::footer();
        return $html;
    }

    public function renderLogin(): string {
        $html  = Layout::head('Student Sign In');
        $html .= Layout::nav();
        $html .= '<main id="main"><div class="container page-pad" style="max-width:440px;margin-top:2.5rem">';
        $html .= '<div class="card"><div class="card-header" style="background:var(--navy)"><h1 style="color:#fff;font-size:1.2rem"><i class="fa fa-sign-in-alt"></i> Student Sign In</h1></div>';
        $html .= '<div class="card-body">' . Layout::flash();
        $html .= '<form action="/login" method="POST" novalidate>';
        $html .= '<div class="form-group"><label for="em">Email Address <span class="req">*</span></label><input type="email" id="em" name="email" class="form-control" required autocomplete="email" autofocus></div>';
        $html .= '<div class="form-group"><label for="pw">Password <span class="req">*</span></label>';
        $html .= '<div class="input-wrap"><input type="password" id="pw" name="password" class="form-control" required autocomplete="current-password">';
        $html .= '<button type="button" class="input-icon" data-toggle-pw title="Show/hide"><i class="fa fa-eye"></i></button></div></div>';
        $html .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-sign-in-alt"></i> Sign In</button>';
        $html .= '</form>';
        $html .= '<div class="flex justify-between items-center mt-2">';
        $html .= '<a href="/forgot-password" class="text-sm text-muted">Forgot password?</a>';
        $html .= '<a href="/register" class="text-sm">Create an account →</a>';
        $html .= '</div></div></div></div></main>' . Layout::footer();
        return $html;
    }

    public function renderForgotPassword(): string {
        $html  = Layout::head('Forgot Password');
        $html .= Layout::nav();
        $html .= '<main id="main"><div class="container page-pad" style="max-width:440px;margin-top:2.5rem">';
        $html .= '<a href="/login" class="btn btn-ghost btn-sm mb-2"><i class="fa fa-arrow-left"></i> Back to Sign In</a>';
        $html .= '<div class="card"><div class="card-header"><h2><i class="fa fa-key"></i> Forgot Password</h2></div>';
        $html .= '<div class="card-body">' . Layout::flash();
        $html .= '<p class="text-muted text-sm mb-2">Enter your registered email address. A password reset link will be sent to you (in development, check your server error log).</p>';
        $html .= '<form action="/forgot-password" method="POST" novalidate>';
        $html .= '<div class="form-group"><label for="em">Email Address <span class="req">*</span></label><input type="email" id="em" name="email" class="form-control" required autofocus></div>';
        $html .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-paper-plane"></i> Send Reset Link</button>';
        $html .= '</form></div></div></div></main>' . Layout::footer();
        return $html;
    }

    public function renderResetPassword(string $token): string {
        $html  = Layout::head('Reset Password');
        $html .= Layout::nav();
        $html .= '<main id="main"><div class="container page-pad" style="max-width:440px;margin-top:2.5rem">';
        $html .= '<div class="card"><div class="card-header"><h2><i class="fa fa-lock"></i> Set New Password</h2></div>';
        $html .= '<div class="card-body">' . Layout::flash();
        $html .= '<form action="/reset-password" method="POST" novalidate>';
        $html .= '<input type="hidden" name="token" value="' . self::h($token) . '">';
        foreach ([['password','New Password'],['password_confirm','Confirm Password']] as [$n,$l]) {
            $html .= '<div class="form-group"><label>' . $l . ' <span class="req">*</span></label>';
            $html .= '<div class="input-wrap"><input type="password" name="' . $n . '" class="form-control" required>';
            $html .= '<button type="button" class="input-icon" data-toggle-pw><i class="fa fa-eye"></i></button></div></div>';
        }
        $html .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-check"></i> Reset Password</button>';
        $html .= '</form></div></div></div></main>' . Layout::footer();
        return $html;
    }

    public function renderAccount(array $student, array $interests, array $favourites): string {
        $name   = self::h($student['first_name'] . ' ' . $student['last_name']);
        $email  = self::h($student['email']);
        $initials = mb_strtoupper(mb_substr($student['first_name'],0,1) . mb_substr($student['last_name'],0,1));

        $html  = Layout::head('My Account — ' . $name);
        $html .= Layout::nav('/account');
        $html .= '<main id="main">';

        // Banner
        $html .= '<div style="background:linear-gradient(135deg,var(--navy),var(--blue));color:#fff;padding:2.5rem 0">';
        $html .= '<div class="container flex gap-3 items-center flex-wrap justify-between">';
        $html .= '<div class="flex gap-3 items-center">';
        $html .= '<div style="width:70px;height:70px;border-radius:50%;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.7rem;font-weight:700;border:2px solid rgba(255,255,255,.3)">' . $initials . '</div>';
        $html .= '<div><h1 style="font-family:Merriweather,serif;font-size:1.6rem">' . $name . '</h1><p style="opacity:.75">' . $email . '</p></div></div>';
        $html .= '<div class="flex gap-1 flex-wrap">';
        $html .= '<a href="/account/edit" class="btn" style="background:#fff;color:var(--navy);border:2px solid #fff;font-weight:700"><i class="fa fa-edit"></i> Edit Profile</a>';
        $html .= '<a href="/logout" class="btn btn-ghost" style="color:rgba(255,255,255,.7)"><i class="fa fa-sign-out-alt"></i> Logout</a>';
        $html .= '</div></div></div>';

        $html .= '<div class="container page-pad">';
        $html .= Layout::flash();

        // Stats
        $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1.25rem;margin-bottom:2.5rem">';
        foreach ([
            ['fa-envelope-open-text', count($interests), 'Registrations', 'var(--blue)'],
            ['fa-heart', count($favourites), 'Favourites', 'var(--error)'],
        ] as [$icon, $num, $lbl, $col]) {
            $html .= '<div class="card card-body text-center"><i class="fa ' . $icon . '" style="font-size:1.4rem;color:' . $col . ';margin-bottom:.4rem"></i>';
            $html .= '<div style="font-size:1.8rem;font-weight:800;color:var(--navy)">' . $num . '</div><div class="text-xs text-muted">' . $lbl . '</div></div>';
        }
        $html .= '</div>';

        // Registered interests
        $html .= '<div class="section-head"><h2>My Programme Interests</h2><p>Programmes you have registered interest in</p></div>';
        if (empty($interests)) {
            $html .= '<div class="card card-body text-center" style="padding:2.5rem">';
            $html .= '<i class="fa fa-inbox" style="font-size:2.5rem;color:var(--border);margin-bottom:1rem"></i>';
            $html .= '<p class="text-muted">No registrations yet.</p><a href="/programmes" class="btn btn-primary btn-sm mt-2"><i class="fa fa-graduation-cap"></i> Browse Programmes</a></div>';
        } else {
            $html .= '<div class="table-wrap"><table><thead><tr><th>Programme</th><th>Level</th><th>Registered</th><th>Action</th></tr></thead><tbody>';
            foreach ($interests as $i) {
                $badge = $i['level']==='Undergraduate'?'badge-ug':'badge-pg';
                $html .= '<tr>';
                $html .= '<td><a href="/programmes/' . self::h($i['programme_slug']) . '">' . self::h($i['programme_title']) . '</a></td>';
                $html .= '<td><span class="badge ' . $badge . '">' . self::h($i['level']) . '</span></td>';
                $html .= '<td class="text-muted text-sm">' . date('d M Y', strtotime($i['created_at'])) . '</td>';
                $html .= '<td><form action="/account/withdraw/' . (int)$i['id'] . '" method="POST" onsubmit="return confirm(\'Remove this interest registration?\')">';
                $html .= '<button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-times"></i> Withdraw</button></form></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
        }

        // Favourites
        $html .= '<div class="section-head mt-4"><h2>My Favourites</h2><p>Programmes you have saved for later</p></div>';
        if (empty($favourites)) {
            $html .= '<div class="card card-body text-center" style="padding:2.5rem">';
            $html .= '<i class="fa fa-heart" style="font-size:2.5rem;color:var(--border);margin-bottom:1rem"></i>';
            $html .= '<p class="text-muted">No favourites saved yet.</p>';
            $html .= '<p class="text-sm text-muted mt-1">Click the <strong>♡ heart</strong> on any programme to save it here.</p>';
            $html .= '</div>';
        } else {
            $html .= '<div class="grid-3">';
            foreach ($favourites as $p) {
                $badge = $p['level']==='Undergraduate'?'badge-ug':'badge-pg';
                $slug  = self::h($p['slug']);
                $html .= '<article class="prog-card">';
                $html .= '<div class="prog-card-img"><span style="font-size:3.5rem">🎓</span><div class="overlay"></div></div>';
                $html .= '<div class="prog-card-body">';
                $html .= '<span class="badge ' . $badge . '">' . self::h($p['level']) . '</span>';
                $html .= '<h3 class="font-bold mt-1" style="font-size:1rem"><a href="/programmes/' . $slug . '" style="color:var(--navy)">' . self::h($p['title']) . '</a></h3>';
                if (!empty($p['leader_name'])) $html .= '<p class="text-xs text-muted"><i class="fa fa-user-tie"></i> ' . self::h($p['leader_name']) . '</p>';
                $html .= '</div><div class="prog-card-footer">';
                $html .= '<a href="/programmes/' . $slug . '" class="btn btn-primary btn-sm"><i class="fa fa-eye"></i> View</a>';
                $html .= '<form action="/favourite/' . $slug . '" method="POST" style="display:inline"><button type="submit" class="btn btn-danger btn-sm"><i class="fa fa-heart-broken"></i> Remove</button></form>';
                $html .= '</div></article>';
            }
            $html .= '</div>';
        }
        $html .= '</div></main>' . Layout::footer();
        return $html;
    }

    public function renderEditProfile(array $student): string {
        $html  = Layout::head('Edit Profile');
        $html .= Layout::nav('/account');
        $html .= '<main id="main"><div class="container page-pad" style="max-width:900px">';
        $html .= '<a href="/account" class="btn btn-ghost btn-sm mb-2"><i class="fa fa-arrow-left"></i> Back to Account</a>';
        $html .= '<div class="page-banner" style="border-radius:var(--radius);margin-bottom:2rem"><h1><i class="fa fa-edit"></i> Edit Profile</h1></div>';
        $html .= Layout::flash();
        $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;align-items:start">';

        // Profile info form
        $html .= '<div class="card"><div class="card-header"><h2><i class="fa fa-user"></i> Personal Information</h2></div><div class="card-body">';
        $html .= '<form action="/account/edit" method="POST" novalidate>';
        $html .= '<div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">';
        $html .= '<div class="form-group"><label>First Name <span class="req">*</span></label><input type="text" name="first_name" class="form-control" value="' . self::h($student['first_name']) . '" required></div>';
        $html .= '<div class="form-group"><label>Last Name <span class="req">*</span></label><input type="text" name="last_name" class="form-control" value="' . self::h($student['last_name']) . '" required></div>';
        $html .= '</div>';
        $html .= '<div class="form-group"><label>Email</label><input type="email" class="form-control" value="' . self::h($student['email']) . '" disabled style="opacity:.6;cursor:not-allowed"><p class="form-hint">Email cannot be changed. Contact admin to update.</p></div>';
        $html .= '<div class="form-group"><label>Phone (optional)</label><input type="tel" name="phone" class="form-control" value="' . self::h($student['phone']??'') . '"></div>';
        $html .= '<div class="form-group"><label>About Me (optional)</label><textarea name="bio" class="form-control" rows="4" placeholder="Tell us about your academic interests…">' . self::h($student['bio']??'') . '</textarea></div>';
        $html .= '<button type="submit" class="btn btn-primary w-full"><i class="fa fa-save"></i> Save Changes</button>';
        $html .= '</form></div></div>';

        // Password + danger
        $html .= '<div>';
        $html .= '<div class="card mb-2"><div class="card-header"><h2><i class="fa fa-lock"></i> Change Password</h2></div><div class="card-body">';
        $html .= '<form action="/account/password" method="POST" novalidate>';
        foreach ([['current_password','Current Password'],['new_password','New Password'],['confirm_password','Confirm Password']] as [$n,$l]) {
            $html .= '<div class="form-group"><label>' . $l . ' <span class="req">*</span></label>';
            $html .= '<div class="input-wrap"><input type="password" name="' . $n . '" class="form-control" required>';
            $html .= '<button type="button" class="input-icon" data-toggle-pw><i class="fa fa-eye"></i></button></div></div>';
        }
        $html .= '<button type="submit" class="btn btn-outline w-full"><i class="fa fa-key"></i> Update Password</button>';
        $html .= '</form></div></div>';

        $html .= '<div class="card" style="border-color:var(--error)"><div class="card-header" style="background:var(--error-bg)"><h2 style="color:var(--error)"><i class="fa fa-exclamation-triangle"></i> Danger Zone</h2></div><div class="card-body">';
        $html .= '<p class="text-muted text-sm mb-2">Permanently delete your student account, all interest registrations, and saved favourites. This cannot be undone.</p>';
        $html .= '<form action="/account/delete" method="POST" onsubmit="return confirm(\'Delete your account permanently? All your data will be removed and cannot be recovered.\')">';
        $html .= '<button type="submit" class="btn btn-danger w-full"><i class="fa fa-trash"></i> Delete My Account</button></form>';
        $html .= '</div></div></div></div></div></main>' . Layout::footer();
        return $html;
    }
    public function renderForgotSent(string $email, ?string $token): string {
        $html  = Layout::head('Password Reset');
        $html .= Layout::nav();
        $html .= '<main id="main"><div class="container page-pad" style="max-width:540px;margin-top:2.5rem">';

        if ($token) {
            // Token found — show the reset link on screen (dev mode)
            $link = '/reset-password?token=' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8');
            $html .= '<div class="card">';
            $html .= '<div class="card-header" style="background:var(--success-bg)"><h2 style="color:var(--success)"><i class="fa fa-check-circle"></i> Reset Link Generated</h2></div>';
            $html .= '<div class="card-body">';
            $html .= '<p class="text-sm text-muted mb-2">A password reset has been requested for <strong>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</strong>.</p>';
            $html .= '<div style="background:var(--light);border:1px solid var(--border);border-radius:var(--radius-sm);padding:1rem;margin:1rem 0">';
            $html .= '<p class="text-xs text-muted mb-1"><i class="fa fa-info-circle" style="color:var(--blue)"></i> <strong>Development mode</strong> — in production this link would be emailed. Click below to reset your password:</p>';
            $html .= '<a href="' . $link . '" class="btn btn-primary w-full mt-1"><i class="fa fa-key"></i> Click Here to Reset Password</a>';
            $html .= '</div>';
            $html .= '<p class="text-xs text-muted">This link expires in <strong>1 hour</strong>. If you did not request a reset, you can safely ignore this.</p>';
            $html .= '</div></div>';
        } else {
            // Email not found — generic message (don't reveal whether it exists)
            $html .= '<div class="card">';
            $html .= '<div class="card-header"><h2><i class="fa fa-envelope"></i> Check Your Email</h2></div>';
            $html .= '<div class="card-body">';
            $html .= '<p class="text-muted">If <strong>' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</strong> is registered with us, you will receive a password reset link shortly.</p>';
            $html .= '<p class="text-sm text-muted mt-2">Did not receive it? Check your spam folder or <a href="/forgot-password">try again</a>.</p>';
            $html .= '</div></div>';
        }

        $html .= '<div class="text-center mt-3"><a href="/login" class="btn btn-ghost btn-sm"><i class="fa fa-arrow-left"></i> Back to Sign In</a></div>';
        $html .= '</div></main>' . Layout::footer();
        return $html;
    }

}