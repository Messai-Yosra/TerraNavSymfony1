document.addEventListener('DOMContentLoaded', function() {
    const navButtons = document.querySelectorAll('.nav-button');
    const contentSections = document.querySelectorAll('.content-section');

    // Handle navigation clicks (simplified)
    navButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Hide all sections
            contentSections.forEach(section => {
                if (section) {
                    section.classList.remove('active');
                }
            });
            
            // Show the clicked section
            const target = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(target);
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });
    
    // Add active class to nav links when clicked
    document.querySelectorAll('.admin-nav-link').forEach(link => {
        link.addEventListener('click', function() {
            document.querySelectorAll('.admin-nav-link').forEach(l => {
                l.classList.remove('active');
            });
            this.classList.add('active');
        });
    });
});