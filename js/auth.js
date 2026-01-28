/**
 * Auth.js - AJAX Authentication Flow
 * De Gouden Schoen - Email Verificatie Systeem
 *
 * Functies:
 * - Registratie zonder page reload
 * - Verificatiecode invoer
 * - Login met AJAX
 * - Spinner feedback
 */

document.addEventListener('DOMContentLoaded', function() {
    // =====================================================
    // ELEMENT REFERENCES
    // =====================================================
    const registerForm = document.getElementById('register-form');
    const loginForm = document.getElementById('login-form');
    const verificationForm = document.getElementById('verification-form');
    const alertContainer = document.getElementById('alert-message');
    const formTitle = document.getElementById('form-title');

    // Buttons
    const registerBtn = document.getElementById('register-btn');
    const loginBtn = document.getElementById('login-btn');
    const verifyBtn = document.getElementById('verify-btn');

    // Links
    const switchLinks = document.querySelectorAll('[data-switch]');
    const backToLoginLink = document.getElementById('back-to-login');
    const resendCodeLink = document.getElementById('resend-code');

    // State
    let currentUsername = '';

    // =====================================================
    // UTILITY FUNCTIONS
    // =====================================================

    /**
     * Toon alert bericht
     */
    function showAlert(message, type = 'error') {
        alertContainer.textContent = message;
        alertContainer.className = `alert alert-${type} show`;

        // Auto-hide na 5 seconden voor success berichten
        if (type === 'success') {
            setTimeout(() => {
                alertContainer.classList.remove('show');
            }, 5000);
        }
    }

    /**
     * Verberg alert
     */
    function hideAlert() {
        alertContainer.classList.remove('show');
    }

    /**
     * Toggle loading state op button
     */
    function setLoading(button, isLoading) {
        if (isLoading) {
            button.classList.add('btn-loading');
            button.disabled = true;
        } else {
            button.classList.remove('btn-loading');
            button.disabled = false;
        }
    }

    /**
     * Wissel tussen formulieren
     */
    function switchForm(targetForm) {
        // Verberg alle formulieren
        registerForm.style.display = 'none';
        loginForm.style.display = 'none';
        verificationForm.classList.remove('show');

        hideAlert();

        // Toon target formulier
        if (targetForm === 'register') {
            registerForm.style.display = 'block';
            formTitle.textContent = 'Maak een account aan';
            // Update URL zonder reload
            history.pushState({}, '', 'index.php?mode=register');
        } else if (targetForm === 'login') {
            loginForm.style.display = 'block';
            formTitle.textContent = 'Login om je taken te bekijken';
            history.pushState({}, '', 'index.php?mode=login');
        } else if (targetForm === 'verification') {
            verificationForm.classList.add('show');
            formTitle.textContent = 'Verifieer je account';
            // Focus op code input
            document.getElementById('verification-code').focus();
        }
    }

    /**
     * AJAX POST request
     */
    async function postData(data) {
        try {
            const response = await fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            return await response.json();
        } catch (error) {
            console.error('Fetch error:', error);
            return { success: false, message: 'Verbindingsfout. Probeer het opnieuw.' };
        }
    }

    // =====================================================
    // REGISTRATIE HANDLER
    // =====================================================

    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideAlert();

            // Verzamel form data
            const username = document.getElementById('reg-username').value.trim();
            const email = document.getElementById('reg-email').value.trim();
            const password = document.getElementById('reg-password').value;
            const passwordConfirm = document.getElementById('reg-password-confirm').value;

            // Client-side validatie
            if (!username || !email || !password || !passwordConfirm) {
                showAlert('Alle velden zijn verplicht', 'error');
                return;
            }

            if (password !== passwordConfirm) {
                showAlert('Wachtwoorden komen niet overeen', 'error');
                return;
            }

            if (password.length < 6) {
                showAlert('Wachtwoord moet minimaal 6 karakters bevatten', 'error');
                return;
            }

            // Start loading
            setLoading(registerBtn, true);

            // Verstuur AJAX request
            const result = await postData({
                action: 'register',
                username: username,
                email: email,
                password: password,
                password_confirm: passwordConfirm
            });

            // Stop loading
            setLoading(registerBtn, false);

            if (result.success) {
                // Sla username op voor verificatie
                currentUsername = username;
                document.getElementById('verify-username').value = username;

                // Toon success bericht
                showAlert(result.message, 'success');

                // Als SMTP uit staat, toon dev code
                if (result.dev_code) {
                    const devContainer = document.getElementById('dev-code-container');
                    devContainer.innerHTML = `
                        <div class="dev-code-box">
                            <strong>Development Mode</strong><br>
                            Code: <span style="font-size: 24px; color: #4CAF50;">${result.dev_code}</span>
                        </div>
                    `;
                }

                // Wissel naar verificatie formulier
                if (result.show_verification) {
                    setTimeout(() => {
                        switchForm('verification');
                    }, 500);
                }
            } else {
                showAlert(result.message, 'error');
            }
        });
    }

    // =====================================================
    // LOGIN HANDLER
    // =====================================================

    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideAlert();

            const username = document.getElementById('login-username').value.trim();
            const password = document.getElementById('login-password').value;

            if (!username || !password) {
                showAlert('Gebruikersnaam en wachtwoord zijn verplicht', 'error');
                return;
            }

            setLoading(loginBtn, true);

            const result = await postData({
                action: 'login',
                username: username,
                password: password
            });

            setLoading(loginBtn, false);

            if (result.success) {
                showAlert(result.message, 'success');

                // Redirect naar dashboard
                if (result.redirect) {
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 500);
                }
            } else {
                // Check of verificatie nodig is
                if (result.needs_verification) {
                    currentUsername = result.username;
                    document.getElementById('verify-username').value = result.username;
                    showAlert('Je account is nog niet geverifieerd. Voer je verificatiecode in.', 'info');

                    setTimeout(() => {
                        switchForm('verification');
                    }, 1000);
                } else {
                    showAlert(result.message, 'error');
                }
            }
        });
    }

    // =====================================================
    // VERIFICATIE CODE HANDLER
    // =====================================================

    if (verificationForm) {
        verificationForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            hideAlert();

            const username = document.getElementById('verify-username').value || currentUsername;
            const code = document.getElementById('verification-code').value.trim();

            if (!code) {
                showAlert('Voer je verificatiecode in', 'error');
                return;
            }

            if (code.length !== 6 || !/^\d{6}$/.test(code)) {
                showAlert('De code moet 6 cijfers bevatten', 'error');
                return;
            }

            setLoading(verifyBtn, true);

            const result = await postData({
                action: 'verify_code',
                username: username,
                code: code
            });

            setLoading(verifyBtn, false);

            if (result.success) {
                showAlert(result.message, 'success');

                // Redirect naar dashboard
                if (result.redirect) {
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                }
            } else {
                showAlert(result.message, 'error');
                // Clear code input voor retry
                document.getElementById('verification-code').value = '';
                document.getElementById('verification-code').focus();
            }
        });
    }

    // =====================================================
    // CODE OPNIEUW VERSTUREN
    // =====================================================

    if (resendCodeLink) {
        resendCodeLink.addEventListener('click', async function(e) {
            e.preventDefault();
            hideAlert();

            const username = document.getElementById('verify-username').value || currentUsername;

            if (!username) {
                showAlert('Geen gebruiker bekend. Ga terug naar registratie.', 'error');
                return;
            }

            // Disable link tijdelijk
            resendCodeLink.style.pointerEvents = 'none';
            resendCodeLink.textContent = 'Versturen...';

            const result = await postData({
                action: 'resend_code',
                username: username
            });

            // Re-enable link
            resendCodeLink.style.pointerEvents = 'auto';
            resendCodeLink.textContent = 'Opnieuw versturen';

            if (result.success) {
                showAlert(result.message, 'success');

                // Update dev code als aanwezig
                if (result.dev_code) {
                    const devContainer = document.getElementById('dev-code-container');
                    devContainer.innerHTML = `
                        <div class="dev-code-box">
                            <strong>Development Mode</strong><br>
                            Nieuwe code: <span style="font-size: 24px; color: #4CAF50;">${result.dev_code}</span>
                        </div>
                    `;
                }
            } else {
                showAlert(result.message, 'error');
            }
        });
    }

    // =====================================================
    // FORM SWITCH HANDLERS
    // =====================================================

    // Switch tussen login/register
    switchLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.dataset.switch;
            switchForm(target);
        });
    });

    // Terug naar login vanuit verificatie
    if (backToLoginLink) {
        backToLoginLink.addEventListener('click', function(e) {
            e.preventDefault();
            // Clear dev code container
            const devContainer = document.getElementById('dev-code-container');
            if (devContainer) devContainer.innerHTML = '';

            // Clear verification code
            document.getElementById('verification-code').value = '';

            switchForm('login');
        });
    }

    // =====================================================
    // AUTO-FORMAT VERIFICATIE CODE INPUT
    // =====================================================

    const codeInput = document.getElementById('verification-code');
    if (codeInput) {
        codeInput.addEventListener('input', function(e) {
            // Alleen cijfers toestaan
            this.value = this.value.replace(/[^0-9]/g, '');

            // Auto-submit wanneer 6 cijfers zijn ingevoerd
            if (this.value.length === 6) {
                // Kleine delay voor betere UX
                setTimeout(() => {
                    verificationForm.dispatchEvent(new Event('submit'));
                }, 300);
            }
        });

        // Paste handler
        codeInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const numbers = pastedText.replace(/[^0-9]/g, '').substring(0, 6);
            this.value = numbers;

            if (numbers.length === 6) {
                setTimeout(() => {
                    verificationForm.dispatchEvent(new Event('submit'));
                }, 300);
            }
        });
    }

    // =====================================================
    // KEYBOARD SHORTCUTS
    // =====================================================

    document.addEventListener('keydown', function(e) {
        // Enter in verificatie veld
        if (e.key === 'Enter' && document.activeElement === codeInput) {
            e.preventDefault();
            if (codeInput.value.length === 6) {
                verificationForm.dispatchEvent(new Event('submit'));
            }
        }

        // Escape om terug te gaan
        if (e.key === 'Escape' && verificationForm.classList.contains('show')) {
            switchForm('login');
        }
    });

    console.log('Auth.js geladen - AJAX verificatie actief');
});