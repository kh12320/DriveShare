<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveShare – Peer-to-Peer Car Rentals</title>
    <meta name="description" content="Rent cars directly from local owners. Find your perfect ride with DriveShare.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>

<body>

    <div class="auth-split">
        <!-- LEFT PANEL - Hero -->
        <div class="auth-hero">
            <div class="hero-content">
                <div class="brand-logo">
                    <i class="bi bi-car-front-fill"></i>
                    <span>DriveShare</span>
                </div>
                <h1>Your car. Their journey.<br>Earn while you park.</h1>
                <p>Connect with trusted local car owners. Rent unique vehicles or list your own — it's peer-to-peer,
                    simple, and secure.</p>
                <div class="hero-stats">
                    <div class="stat"><strong>500+</strong><span>Cars Listed</span></div>
                    <div class="stat"><strong>2K+</strong><span>Happy Renters</span></div>
                    <div class="stat"><strong>98%</strong><span>Satisfaction</span></div>
                </div>
            </div>
            <div class="hero-cars-bg"></div>
        </div>

        <!-- RIGHT PANEL - Auth Forms -->
        <div class="auth-panel">
            <div class="auth-card">

                <?php if (!empty($_GET['msg'])): ?>
                    <div class="alert alert-<?= htmlspecialchars($_GET['type'] ?? 'info') ?> alert-dismissible fade show"
                        role="alert">
                        <i
                            class="bi bi-<?= ($_GET['type'] ?? 'info') === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                        <?= htmlspecialchars($_GET['msg']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- ===== Tab Switcher (Sign In / Create Account only) ===== -->
                <ul class="nav auth-tabs" id="authTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#loginPane"
                            type="button">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#registerPane"
                            type="button">
                            <i class="bi bi-person-plus me-1"></i>Create Account
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="authTabContent">

                    <!-- ===== LOGIN ===== -->
                    <div class="tab-pane fade show active" id="loginPane" role="tabpanel">
                        <form id="loginForm" novalidate>
                            <div class="form-group-custom">
                                <label for="login_email"><i class="bi bi-envelope"></i> Email Address</label>
                                <input type="email" class="form-control custom-input" id="login_email" name="email"
                                    placeholder="you@example.com" required autocomplete="email">
                            </div>
                            <div class="form-group-custom">
                                <label for="login_password"><i class="bi bi-lock"></i> Password</label>
                                <div class="input-with-toggle">
                                    <input type="password" class="form-control custom-input" id="login_password"
                                        name="password" placeholder="Your password" required
                                        autocomplete="current-password">
                                    <button type="button" class="toggle-pass"
                                        onclick="togglePass('login_password', this)"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>

                            <!-- Forgot password link -->
                            <div class="text-end mb-2">
                                <a href="#" id="forgotLink" class="forgot-link" onclick="showForgot(); return false;">
                                    <i class="bi bi-question-circle me-1"></i>Forgot password?
                                </a>
                            </div>

                            <div id="loginError" class="auth-error d-none"></div>
                            <button type="submit" class="btn btn-auth-primary w-100" id="loginBtn">
                                <span class="btn-text"><i class="bi bi-box-arrow-in-right me-1"></i>Sign In</span>
                                <span class="spinner-border spinner-border-sm d-none" id="loginSpinner"></span>
                            </button>
                            <p class="auth-switch">Don't have an account?
                                <a href="#" onclick="switchTab('register-tab')">Create one free</a>
                            </p>
                        </form>
                    </div>

                    <!-- ===== REGISTER ===== -->
                    <div class="tab-pane fade" id="registerPane" role="tabpanel">
                        <form id="registerForm" novalidate>

                            <!-- Role Selector -->
                            <div class="role-selector mb-3">
                                <label class="role-label">I want to:</label>
                                <div class="role-options">
                                    <label class="role-card" for="role_customer">
                                        <input type="radio" name="role" id="role_customer" value="customer" checked>
                                        <div class="role-card-inner">
                                            <i class="bi bi-person-check-fill"></i>
                                            <strong>Rent a Car</strong>
                                            <small>Browse &amp; book cars</small>
                                        </div>
                                    </label>
                                    <label class="role-card" for="role_owner">
                                        <input type="radio" name="role" id="role_owner" value="owner">
                                        <div class="role-card-inner">
                                            <i class="bi bi-car-front-fill"></i>
                                            <strong>List My Car</strong>
                                            <small>Earn from your car</small>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div id="registerSuccess" class="auth-success d-none"></div>
                            <div class="row g-0">
                                <div class="col-12">
                                    <div class="form-group-custom">
                                        <label><i class="bi bi-person"></i> Full Name</label>
                                        <input type="text" class="form-control custom-input" name="name"
                                            placeholder="John Doe" required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group-custom">
                                        <label><i class="bi bi-envelope"></i> Email Address</label>
                                        <input type="email" class="form-control custom-input" name="email"
                                            placeholder="you@example.com" required autocomplete="email">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group-custom">
                                        <label><i class="bi bi-phone"></i> Phone Number</label>
                                        <input type="tel" class="form-control custom-input" name="phone"
                                            placeholder="+91 9876543210" required>
                                    </div>
                                </div>
                                <div class="col-6 pe-1">
                                    <div class="form-group-custom">
                                        <label><i class="bi bi-lock"></i> Password</label>
                                        <div class="input-with-toggle">
                                            <input type="password" class="form-control custom-input" id="reg_password"
                                                name="password" placeholder="Min 8 chars" required minlength="8"
                                                autocomplete="new-password">
                                            <button type="button" class="toggle-pass"
                                                onclick="togglePass('reg_password', this)"><i
                                                    class="bi bi-eye"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 ps-1">
                                    <div class="form-group-custom">
                                        <label><i class="bi bi-lock-fill"></i> Confirm</label>
                                        <input type="password" class="form-control custom-input" id="reg_confirm"
                                            name="confirm" placeholder="Repeat" required autocomplete="new-password">
                                    </div>
                                </div>
                            </div>

                            <div id="registerError" class="auth-error d-none"></div>
                            <button type="submit" class="btn btn-auth-primary w-100" id="registerBtn">
                                <span class="btn-text"><i class="bi bi-person-plus me-1"></i>Create Account</span>
                                <span class="spinner-border spinner-border-sm d-none" id="registerSpinner"></span>
                            </button>
                            <p class="auth-switch">Already have an account?
                                <a href="#" onclick="switchTab('login-tab')">Sign in</a>
                            </p>
                        </form>
                    </div>
                </div><!-- tab-content -->

                <!-- ===== FORGOT PASSWORD PANEL (hidden by default) ===== -->
                <div id="forgotPanel" style="display:none">
                    <button class="back-btn" onclick="hideForgot()">
                        <i class="bi bi-arrow-left me-1"></i>Back to Sign In
                    </button>
                    <div class="forgot-header">
                        <div class="forgot-icon"><i class="bi bi-shield-lock-fill"></i></div>
                        <h5>Reset Your Password</h5>
                        <p>Enter your email and we'll send you a link to reset your password.</p>
                    </div>

                    <form id="forgotForm" novalidate>
                        <div class="form-group-custom">
                            <label for="forgot_email"><i class="bi bi-envelope"></i> Email Address</label>
                            <input type="email" class="form-control custom-input" id="forgot_email"
                                placeholder="you@example.com" required autocomplete="email">
                        </div>
                        <div id="forgotError" class="auth-error d-none"></div>
                        <div id="forgotSuccess" class="auth-success d-none"></div>
                        <button type="submit" class="btn btn-auth-primary w-100" id="forgotBtn">
                            <span class="btn-text"><i class="bi bi-send me-1"></i>Send Reset Link</span>
                            <span class="spinner-border spinner-border-sm d-none" id="forgotSpinner"></span>
                        </button>
                    </form>
                </div>

            </div><!-- auth-card -->
        </div><!-- auth-panel -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* ── Tab switching ── */
        function switchTab(tabId) {
            document.getElementById(tabId).click();
        }

        /* ── Show / hide forgot panel ── */
        function showForgot() {
            document.getElementById('authTabs').style.display = 'none';
            document.getElementById('authTabContent').style.display = 'none';
            document.getElementById('forgotPanel').style.display = 'block';
            document.getElementById('forgot_email').focus();
        }
        function hideForgot() {
            document.getElementById('authTabs').style.display = 'flex';
            document.getElementById('authTabContent').style.display = 'block';
            document.getElementById('forgotPanel').style.display = 'none';
            document.getElementById('forgotError').classList.add('d-none');
            document.getElementById('forgotSuccess').classList.add('d-none');
            document.getElementById('forgotForm').reset();
        }

        /* ── Password toggle ── */
        function togglePass(inputId, btn) {
            const inp = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (inp.type === 'password') {
                inp.type = 'text'; icon.className = 'bi bi-eye-slash';
            } else {
                inp.type = 'password'; icon.className = 'bi bi-eye';
            }
        }

        /* ── Error helpers ── */
        function showError(elId, msg) { const el = document.getElementById(elId); el.textContent = msg; el.classList.remove('d-none'); }
        function hideError(elId) { document.getElementById(elId).classList.add('d-none'); }
        function showSuccess(elId, msg) { const el = document.getElementById(elId); el.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + msg; el.classList.remove('d-none'); }

        /* ── LOGIN ── */
        document.getElementById('loginForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            hideError('loginError');
            const btn = document.getElementById('loginBtn');
            const spin = document.getElementById('loginSpinner');
            btn.disabled = true; spin.classList.remove('d-none');

            const fd = new FormData(this);
            try {
                const res = await fetch('/api/auth.php?action=login', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    showError('loginError', data.message);
                }
            } catch (_) { showError('loginError', 'Connection error. Please try again.'); }
            finally { btn.disabled = false; spin.classList.add('d-none'); }
        });

        /* ── REGISTER ── */
        document.getElementById('registerForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            hideError('registerError');

            const pass = document.getElementById('reg_password').value;
            const confirm = document.getElementById('reg_confirm').value;
            if (pass !== confirm) { showError('registerError', 'Passwords do not match.'); return; }
            if (pass.length < 8) { showError('registerError', 'Password must be at least 8 characters.'); return; }

            const btn = document.getElementById('registerBtn');
            const spin = document.getElementById('registerSpinner');
            btn.disabled = true; spin.classList.remove('d-none');

            const fd = new FormData(this);
            try {
                const res = await fetch('/api/auth.php?action=register', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        showSuccess('registerSuccess', data.message);
                        document.getElementById('registerForm').reset();
                    }
                } else {
                    showError('registerError', data.message);
                }
            } catch (_) { showError('registerError', 'Connection error. Please try again.'); }
            finally { btn.disabled = false; spin.classList.add('d-none'); }
        });

        /* ── FORGOT PASSWORD ── */
        document.getElementById('forgotForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            hideError('forgotError');
            hideError('forgotSuccess');

            const email = document.getElementById('forgot_email').value.trim();
            if (!email) { showError('forgotError', 'Please enter your email address.'); return; }

            const btn = document.getElementById('forgotBtn');
            const spin = document.getElementById('forgotSpinner');
            btn.disabled = true; spin.classList.remove('d-none');

            const fd = new FormData();
            fd.append('email', email);
            try {
                const res = await fetch('/api/auth.php?action=forgot_password', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    showSuccess('forgotSuccess', data.message);
                    document.getElementById('forgotBtn').style.display = 'none';
                } else {
                    showError('forgotError', data.message);
                }
            } catch (_) { showError('forgotError', 'Connection error. Please try again.'); }
            finally { btn.disabled = false; spin.classList.add('d-none'); }
        });

        /* ── Role card styling ── */
        document.querySelectorAll('.role-card input').forEach(inp => {
            inp.addEventListener('change', () => {
                document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
                if (inp.checked) inp.closest('.role-card').classList.add('selected');
            });
        });
        document.querySelector('#role_customer').closest('.role-card').classList.add('selected');
    </script>
</body>

</html>