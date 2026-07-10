/**
 * Toggle password visibility
 * Usage: onclick="togglePassword('input-id', this)"
 */
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    // Toggle icon
    button.innerHTML = isPassword
        ? `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
               <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
           </svg>`
        : `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
               <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
               <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
           </svg>`;
}

/**
 * Real-time password confirmation check
 * Usage: oninput="checkPasswordMatch('password', 'password_confirmation', 'status-id')"
 */
function checkPasswordMatch(passwordId, confirmId, statusId) {
    const password = document.getElementById(passwordId);
    const confirm = document.getElementById(confirmId);
    const status = document.getElementById(statusId);

    if (!password || !confirm || !status) return;

    const value = confirm.value;

    if (value.length === 0) {
        status.textContent = 'Ketik ulang password untuk konfirmasi';
        status.className = 'password-hint';
        confirm.setCustomValidity('');
        return;
    }

    if (value === password.value) {
        status.textContent = '✓ Password cocok';
        status.className = 'password-match match';
        confirm.setCustomValidity('');
    } else {
        status.textContent = '✗ Password tidak cocok';
        status.className = 'password-match mismatch';
        confirm.setCustomValidity('Password tidak cocok');
    }
}

// Auto-bind on page load
document.addEventListener('DOMContentLoaded', function () {
    // User Register
    const userPassword = document.getElementById('password');
    const userConfirm = document.getElementById('password_confirmation');
    const userStatus = document.getElementById('password-match-status');

    if (userPassword && userConfirm && userStatus) {
        userConfirm.addEventListener('input', () =>
            checkPasswordMatch('password', 'password_confirmation', 'password-match-status')
        );
    }

    // EO Register
    const eoPassword = document.getElementById('eo-password');
    const eoConfirm = document.getElementById('eo-password_confirmation');
    const eoStatus = document.getElementById('eo-password-match-status');

    if (eoPassword && eoConfirm && eoStatus) {
        eoConfirm.addEventListener('input', () =>
            checkPasswordMatch('eo-password', 'eo-password_confirmation', 'eo-password-match-status')
        );
    }
});
