document.addEventListener('DOMContentLoaded', function() {
    var loginDiv = document.getElementById('login');
    if (!loginDiv) return;

    var nav = document.getElementById('nav');
    var backtoblog = document.getElementById('backtoblog');
    var loginError = document.getElementById('login_error');
    var loginMessage = document.querySelector('.message');

    var loginForm = document.getElementById('loginform');
    var lostPasswordForm = document.getElementById('lostpasswordform');
    var resetPassForm = document.getElementById('resetpassform');
    var registerForm = document.getElementById('registerform');
    
    var isLostPassword = !!lostPasswordForm || window.location.search.indexOf('action=lostpassword') !== -1 || window.location.search.indexOf('action=retrievepassword') !== -1;
    var isResetPass = !!resetPassForm || window.location.search.indexOf('action=rp') !== -1 || window.location.search.indexOf('action=resetpass') !== -1;
    var isCheckEmail = window.location.search.indexOf('checkemail=confirm') !== -1;
    var isRegister = !!registerForm || window.location.search.indexOf('action=register') !== -1;

    var activeForm = loginForm || lostPasswordForm || resetPassForm || registerForm || loginDiv.querySelector('form');

    // Create left banner
    var leftBanner = document.createElement('div');
    leftBanner.className = 'login-left-banner';
    leftBanner.innerHTML = `
        <div class="login-logo-card">
            <span class="logo-title-stock">Stock</span>
            <span class="logo-title-supply"><span class="logo-sup">Sup</span><span class="logo-ply">ply</span></span>
        </div>
        <div class="banner-description">
            Comprehensive Inventory Management System. Manage stock and track item status quickly and accurately.
        </div>
    `;

    // Create right container
    var rightContainer = document.createElement('div');
    rightContainer.className = 'login-right-container';

    var formTitle = document.createElement('h2');
    formTitle.className = 'login-form-title';
    
    var formSubtitle = document.createElement('p');
    formSubtitle.className = 'login-form-subtitle';

    if (isLostPassword) {
        formTitle.textContent = 'Forgot Password';
        formSubtitle.textContent = 'Enter your username or email address to receive a password reset link via email.';
    } else if (isResetPass) {
        formTitle.textContent = 'Reset Password';
        formSubtitle.textContent = 'Enter the new password you want to use for this account.';
    } else if (isCheckEmail) {
        formTitle.textContent = 'Check Your Email';
        formSubtitle.textContent = 'We have sent password reset instructions to your email address.';
    } else if (isRegister) {
        formTitle.textContent = 'Register Account';
        formSubtitle.textContent = 'Enter your details to register for the stock management system.';
    } else {
        formTitle.textContent = 'Sign In';
        formSubtitle.textContent = 'Enter your account credentials to sign in to the stock management system.';
    }

    rightContainer.appendChild(formTitle);
    rightContainer.appendChild(formSubtitle);

    if (loginError) rightContainer.appendChild(loginError);
    if (loginMessage) rightContainer.appendChild(loginMessage);

    if (activeForm) {
        rightContainer.appendChild(activeForm);
    }

    if (nav) rightContainer.appendChild(nav);
    if (backtoblog) rightContainer.appendChild(backtoblog);

    // Reconstruct loginDiv
    loginDiv.innerHTML = '';
    loginDiv.appendChild(leftBanner);
    loginDiv.appendChild(rightContainer);

    // 1. Username / Email Input formatting
    var usernameInput = document.getElementById('user_login');
    if (usernameInput) {
        usernameInput.placeholder = 'Username or Email';
        var pUser = usernameInput.closest('p') || usernameInput.parentElement;
        var userWrapper = document.createElement('div');
        userWrapper.className = 'input-icon-wrapper';
        userWrapper.innerHTML = `<svg class="input-field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>`;
        if (pUser) {
            pUser.parentNode.insertBefore(userWrapper, pUser);
            userWrapper.appendChild(usernameInput);
            pUser.remove();
        }
    }

    // 2. Password Input formatting & Show/Hide Toggle
    var passInput = document.getElementById('user_pass') || document.getElementById('pass1');
    if (passInput) {
        passInput.placeholder = isResetPass ? 'New Password' : 'Password';
        var pPass = passInput.closest('.user-pass-wrap') || passInput.closest('p') || passInput.parentElement;
        var passWrapper = document.createElement('div');
        passWrapper.className = 'input-icon-wrapper delay-1';
        
        var eyeSvg = `<svg class="pw-icon-eye" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:20px;height:20px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
        var eyeOffSvg = `<svg class="pw-icon-eye-off" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:20px;height:20px;display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 014.122-.976c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>`;

        passWrapper.innerHTML = `<svg class="input-field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>`;
        
        var toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'wp-hide-pw';
        toggleBtn.setAttribute('aria-label', 'Toggle Password Visibility');
        toggleBtn.innerHTML = eyeSvg + eyeOffSvg;
        
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var isPassword = passInput.type === 'password';
            passInput.type = isPassword ? 'text' : 'password';
            var eye = toggleBtn.querySelector('.pw-icon-eye');
            var eyeOff = toggleBtn.querySelector('.pw-icon-eye-off');
            if (eye && eyeOff) {
                eye.style.display = isPassword ? 'none' : 'block';
                eyeOff.style.display = isPassword ? 'block' : 'none';
            }
        });

        if (pPass) {
            pPass.parentNode.insertBefore(passWrapper, pPass);
            passWrapper.appendChild(passInput);
            passWrapper.appendChild(toggleBtn);
            pPass.remove();
        }
    }

    // 3. Second Password Input formatting (if resetpassform pass2 exists)
    var pass2Input = document.getElementById('pass2');
    if (pass2Input) {
        pass2Input.placeholder = 'Confirm New Password';
        var pPass2 = pass2Input.closest('.user-pass-wrap') || pass2Input.closest('p') || pass2Input.parentElement;
        var pass2Wrapper = document.createElement('div');
        pass2Wrapper.className = 'input-icon-wrapper delay-2';
        pass2Wrapper.innerHTML = `<svg class="input-field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>`;
        if (pPass2) {
            pPass2.parentNode.insertBefore(pass2Wrapper, pPass2);
            pass2Wrapper.appendChild(pass2Input);
            pPass2.remove();
        }
    }

    // 4. Submit Button formatting
    var submitBtn = document.getElementById('wp-submit');
    if (submitBtn) {
        if (isLostPassword) {
            submitBtn.value = 'Send Reset Link';
        } else if (isResetPass) {
            submitBtn.value = 'Save New Password';
        } else if (isRegister) {
            submitBtn.value = 'Register';
        } else {
            submitBtn.value = 'Sign In';
        }
        var pSubmit = submitBtn.closest('p');
        if (pSubmit) pSubmit.classList.add('input-icon-wrapper', 'delay-2');
    }

    // 5. Cleanup WP capslock elements
    var nukeCapslock = function() {
        var caps = document.querySelectorAll('.capslock, [class*="capslock"], [id*="capslock"], .dashicons-arrow-up-alt');
        caps.forEach(el => el.remove());
    };
    nukeCapslock();
    setInterval(nukeCapslock, 100);
});
