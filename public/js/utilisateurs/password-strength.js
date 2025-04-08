document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password-field');
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text').querySelector('span');
    
    // Critères de validation
    const requirements = {
        length: { element: document.getElementById('length'), regex: /.{8,}/ },
        lowercase: { element: document.getElementById('lowercase'), regex: /[a-z]/ },
        uppercase: { element: document.getElementById('uppercase'), regex: /[A-Z]/ },
        number: { element: document.getElementById('number'), regex: /[0-9]/ },
        special: { element: document.getElementById('special'), regex: /[^A-Za-z0-9]/ }
    };
    
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Vérifier chaque critère
            for (const key in requirements) {
                const requirement = requirements[key];
                const isValid = requirement.regex.test(password);
                
                // Mettre à jour l'icône
                if (isValid) {
                    requirement.element.innerHTML = '<i class="fas fa-check-circle text-success"></i> ' + requirement.element.innerHTML.split('</i> ')[1];
                    strength += 20; // Chaque critère vaut 20% (5 critères = 100%)
                } else {
                    requirement.element.innerHTML = '<i class="far fa-circle"></i> ' + requirement.element.innerHTML.split('</i> ')[1];
                }
            }
            
            // Mettre à jour la barre de progression
            strengthBar.style.width = strength + '%';
            strengthBar.setAttribute('aria-valuenow', strength);
            
            // Définir la couleur et le texte en fonction de la force
            if (strength <= 20) {
                strengthBar.className = 'progress-bar bg-danger';
                strengthText.textContent = 'Très faible';
            } else if (strength <= 40) {
                strengthBar.className = 'progress-bar bg-warning';
                strengthText.textContent = 'Faible';
            } else if (strength <= 60) {
                strengthBar.className = 'progress-bar bg-info';
                strengthText.textContent = 'Moyen';
            } else if (strength <= 80) {
                strengthBar.className = 'progress-bar bg-primary';
                strengthText.textContent = 'Fort';
            } else {
                strengthBar.className = 'progress-bar bg-success';
                strengthText.textContent = 'Excellent';
            }
        });
    }
});