document.addEventListener('DOMContentLoaded', function() {
    const navButtons = document.querySelectorAll('.nav-button');
    const contentSections = document.querySelectorAll('.content-section');

    // Function to show section based on hash
    function showSectionFromHash() {
        const hash = window.location.hash.substring(1);
        if (!hash) {
            // Default to dashboard if no hash
            document.querySelector('.nav-button').classList.add('active');
            document.getElementById('dashboard-content').classList.add('active');
            return;
        }

        // Remove all active classes
        navButtons.forEach(btn => btn.classList.remove('active'));
        contentSections.forEach(section => section.classList.remove('active'));

        // Find and activate the matching section
        const sectionId = hash + '-content';
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.add('active');
            // Find and activate the corresponding button
            document.querySelector(`.nav-button[href="#${hash}"]`).classList.add('active');

            // Move highlight to active button
            const highlight = document.getElementById('nav-content-highlight');
            const activeButton = document.querySelector('.nav-button.active');
            const index = Array.from(navButtons).indexOf(activeButton);
            highlight.style.top = `${16 + (index * 54)}px`;
        }
    }

    // Initial load
    showSectionFromHash();

    // Handle navigation clicks
    navButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('href');
            window.location.hash = target.substring(1);
            showSectionFromHash();
        });
    });

    // Handle back/forward button navigation
    window.addEventListener('hashchange', showSectionFromHash);
});