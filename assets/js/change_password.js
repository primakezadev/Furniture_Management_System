document.addEventListener('DOMContentLoaded', () => {
    const currentInput = document.getElementById('current_password');
    const newInput = document.getElementById('new_password');
    const confirmInput = document.getElementById('confirm_password');
    const bars = Array.from(document.querySelectorAll('.strength-bar'));
    const strengthText = document.querySelector('#strengthText span');
    const confirmHelp = document.getElementById('confirm-help');


    document.querySelectorAll('.visibility-toggle').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.toggle);
            if (!input) return;

            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', `${show ? 'Hide' : 'Show'} ${button.dataset.toggle.replaceAll('_', ' ')}`);
        });
    });

    function updateStrength() {
        const value = newInput.value;
        const rules = [
            value.length >= 8,
            /[0-9]/.test(value),
            /[^a-zA-Z0-9]/.test(value),
            /[a-z]/.test(value) && /[A-Z]/.test(value)
        ];
        const score = rules.filter(Boolean).length;
        bars.forEach((bar, index) => bar.classList.toggle('active', index < score));

        if (!value) {
            strengthText.textContent = 'Use 8+ characters, including a number and a symbol.';
        } else if (score === 4 && value !== currentInput.value) {
            strengthText.textContent = 'Strong password · Different from your current password';
        } else if (value === currentInput.value && currentInput.value) {
            strengthText.textContent = 'Choose a password different from your current password.';
        } else if (score >= 3) {
            strengthText.textContent = 'Good password · Add uppercase and lowercase letters for extra strength.';
        } else {
            strengthText.textContent = 'Add 8+ characters, a number and a symbol.';
        }
    }

    function updateConfirmation() {
        const confirmation = confirmInput.value;
        confirmInput.classList.remove('invalid');
        confirmHelp.classList.remove('match', 'no-match');

        if (!confirmation) {
            confirmHelp.textContent = '';
            return;
        }
        if (confirmation === newInput.value) {
            confirmHelp.textContent = 'Passwords match.';
            confirmHelp.classList.add('match');
        } else {
            confirmHelp.textContent = 'Passwords do not match.';
            confirmHelp.classList.add('no-match');
            confirmInput.classList.add('invalid');
        }
    }

    [currentInput, newInput].forEach((input) => input.addEventListener('input', () => {
        updateStrength();
        updateConfirmation();
    }));
    confirmInput.addEventListener('input', updateConfirmation);

    document.getElementById('changePasswordForm').addEventListener('submit', (event) => {
        const newValue = newInput.value;
        const validNewPassword = newValue.length >= 8 && /[0-9]/.test(newValue) && /[^a-zA-Z0-9]/.test(newValue);
        const passwordsMatch = newValue === confirmInput.value;
        const isDifferent = newValue !== currentInput.value;

        if (!currentInput.value || !newValue || !confirmInput.value || !validNewPassword || !passwordsMatch || !isDifferent) {
            event.preventDefault();
            updateStrength();
            updateConfirmation();
            if (!validNewPassword) newInput.focus();
            else if (!passwordsMatch) confirmInput.focus();
            else if (!isDifferent) newInput.focus();
        }
    });

    updateStrength();
    updateConfirmation();
});
