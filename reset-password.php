<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveShare – Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css">
</head>

<body>

    <div class="auth-split" style="grid-template-columns: 1fr;"> <!-- Full width for reset -->
        <div class="auth-panel" style="border: none;">
            <div class="auth-card" style="margin: auto; max-width: 450px;">

                <div class="text-center mb-4">
                    <div class="brand-logo justify-content-center mb-3">
                        <i class="bi bi-car-front-fill"></i>
                        <span>DriveShare</span>
                    </div>
                </div>

                <div id="loadingPanel" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3 text-muted">Verifying secure link...</p>
                </div>

                <div id="errorPanel" style="display:none;" class="text-center">
                    <div class="forgot-icon mx-auto m-b-3"
                        style="color: var(--accent); background: rgba(255,107,107,0.1); width: 60px; height: 60px; line-height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 20px;">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <h5 class="text-white">Invalid or Expired Link</h5>
                    <p class="text-muted" id="errorText">This password reset link is invalid or has expired.</p>
                    <a href="/index.php" class="btn btn-auth-primary w-100 mt-3">Back to Sign In</a>
                </div>

                <div id="resetPanel" style="display:none">
                    <a href="/index.php" class="back-btn" style="text-decoration: none;">
                        <i class="bi bi-arrow-left me-1"></i>Back to Sign In
                    </a>
                    <div class="forgot-header text-center mb-4">
                        <div class="forgot-icon mx-auto"
                            style="width: 60px; height: 60px; background: rgba(108,99,255,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; border-radius: 50%; margin-bottom: 15px;">
                            <i class="bi bi-key-fill"></i>
                        </div>
                        <h5 class="text-white">Set New Password</h5>
                        <p class="text-muted">Enter your new secure password below.</p>
                    </div>

                    <form id="resetForm" novalidate>
                        <div class="form-group-custom">
                            <label><i class="bi bi-lock"></i> New Password</label>
                            <div class="input-with-toggle">
                                <input type="password" class="form-control custom-input" id="new_password"
                                    name="password" placeholder="Min 8 characters" required minlength="8">
                                <button type="button" class="toggle-pass" onclick="togglePass('new_password', this)"><i
                                        class="bi bi-eye"></i></button>
                            </div>
                        </div>
                        <div class="form-group-custom">
                            <label><i class="bi bi-lock-fill"></i> Confirm Password</label>
                            <input type="password" class="form-control custom-input" id="confirm_password"
                                name="confirm" placeholder="Repeat password" required>
                        </div>

                        <div id="resetError" class="auth-error d-none mt-3"></div>
                        <div id="resetSuccess" class="auth-success d-none mt-3"></div>

                        <button type="submit" class="btn btn-auth-primary w-100 mt-3" id="resetBtn">
                            <span class="btn-text"><i class="bi bi-check2-circle me-1"></i>Update Password</span>
                            <span class="spinner-border spinner-border-sm d-none" id="resetSpinner"></span>
                        </button>
                    </form>
                    <div class="text-center mt-4" id="backToLoginContainer" style="display: none;">
                        <a href="/index.php" class="btn btn-auth-primary w-100"><i
                                class="bi bi-box-arrow-in-right me-1"></i> Go to Sign In</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let accessToken = null;

        function togglePass(inputId, btn) {
            const inp = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (inp.type === 'password') {
                inp.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                inp.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        function getHashParams() {
            var hashParams = {};
            var e,
                a = /\+/g,  // Regex for replacing addition symbol with a space
                r = /([^&;=]+)=?([^&;]*)/g,
                d = function (s) { return decodeURIComponent(s.replace(a, " ")); },
                q = window.location.hash.substring(1);

            while (e = r.exec(q))
                hashParams[d(e[1])] = d(e[2]);
            return hashParams;
        }

        window.onload = function () {
            const params = getHashParams();

            // Supabase puts the access_token in the URL hash
            if (params.access_token && params.type === 'recovery') {
                accessToken = params.access_token;
                document.getElementById('loadingPanel').style.display = 'none';
                document.getElementById('resetPanel').style.display = 'block';
            } else {
                // Not a valid recovery link
                document.getElementById('loadingPanel').style.display = 'none';
                document.getElementById('errorPanel').style.display = 'block';
            }
        };

        document.getElementById('resetForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            const errEl = document.getElementById('resetError');
            const sucEl = document.getElementById('resetSuccess');
            errEl.classList.add('d-none');
            sucEl.classList.add('d-none');

            const pass = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;

            if (pass !== confirm) {
                errEl.textContent = 'Passwords do not match.';
                errEl.classList.remove('d-none');
                return;
            }
            if (pass.length < 8) {
                errEl.textContent = 'Password must be at least 8 characters long.';
                errEl.classList.remove('d-none');
                return;
            }

            const btn = document.getElementById('resetBtn');
            const spin = document.getElementById('resetSpinner');
            btn.disabled = true;
            spin.classList.remove('d-none');

            const fd = new URLSearchParams();
            fd.append('access_token', accessToken);
            fd.append('password', pass);
            fd.append('confirm', confirm);

            try {
                const res = await fetch('/api/auth.php?action=reset_password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: fd.toString()
                });
                const data = await res.json();

                if (data.success) {
                    sucEl.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>' + data.message;
                    sucEl.classList.remove('d-none');
                    document.getElementById('resetForm').style.display = 'none';
                    document.getElementById('backToLoginContainer').style.display = 'block';
                } else {
                    errEl.textContent = data.message || 'An error occurred updating your password.';
                    errEl.classList.remove('d-none');
                }
            } catch (error) {
                errEl.textContent = 'A network error occurred. Please try again.';
                errEl.classList.remove('d-none');
            } finally {
                btn.disabled = false;
                spin.classList.add('d-none');
            }
        });
    </script>
</body>

</html>