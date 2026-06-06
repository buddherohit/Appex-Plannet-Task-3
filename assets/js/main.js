/**
 * Smart User Management System - Core Client JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // 1. Dark Mode / Theme Setup
    const themeToggleBtn = document.getElementById('theme-toggle');
    const htmlElement = document.documentElement;
    
    // Load saved theme or check system preference
    const savedTheme = localStorage.getItem('theme');
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme) {
        htmlElement.setAttribute('data-theme', savedTheme);
        updateThemeToggleIcon(savedTheme);
    } else if (systemPrefersDark) {
        htmlElement.setAttribute('data-theme', 'dark');
        updateThemeToggleIcon('dark');
    } else {
        htmlElement.setAttribute('data-theme', 'light');
        updateThemeToggleIcon('light');
    }
    
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', function() {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeToggleIcon(newTheme);
        });
    }
    
    function updateThemeToggleIcon(theme) {
        if (!themeToggleBtn) return;
        const icon = themeToggleBtn.querySelector('i');
        if (icon) {
            if (theme === 'dark') {
                icon.className = 'bi bi-sun-fill';
            } else {
                icon.className = 'bi bi-moon-fill';
            }
        }
    }

    // 2. Sidebar Toggle Logic
    const sidebarToggler = document.getElementById('sidebar-toggle');
    const appSidebar = document.getElementById('app-sidebar');
    const bodyElement = document.body;
    
    // Create and append sidebar backdrop if not present
    let backdrop = document.getElementById('sidebar-backdrop');
    if (!backdrop && appSidebar) {
        backdrop = document.createElement('div');
        backdrop.id = 'sidebar-backdrop';
        backdrop.className = 'app-sidebar-backdrop';
        document.body.appendChild(backdrop);
    }
    
    if (sidebarToggler && appSidebar) {
        sidebarToggler.addEventListener('click', function() {
            appSidebar.classList.toggle('show');
            if (backdrop) backdrop.classList.toggle('show');
        });
    }
    
    if (backdrop) {
        backdrop.addEventListener('click', function() {
            if (appSidebar) appSidebar.classList.remove('show');
            backdrop.classList.remove('show');
        });
    }

    // 3. Password Visibility Toggle
    const togglePasswordButtons = document.querySelectorAll('.toggle-password-btn');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    if (icon) icon.className = 'bi bi-eye-slash-fill';
                } else {
                    passwordInput.type = 'password';
                    if (icon) icon.className = 'bi bi-eye-fill';
                }
            }
        });
    });

    // 4. Form Client-Side Validation Helpers
    const validatedForms = document.querySelectorAll('.needs-validation-custom');
    validatedForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            // Password confirmation check
            const password = form.querySelector('.validate-password');
            const confirmPassword = form.querySelector('.validate-confirm-password');
            
            if (password && confirmPassword) {
                if (password.value !== confirmPassword.value) {
                    isValid = false;
                    confirmPassword.classList.add('is-invalid');
                    const feedback = confirmPassword.nextElementSibling;
                    if (feedback && feedback.classList.contains('invalid-feedback')) {
                        feedback.textContent = 'Passwords do not match.';
                    }
                } else {
                    confirmPassword.classList.remove('is-invalid');
                }
            }
            
            // Mobile number format check (basic check)
            const mobileInput = form.querySelector('.validate-mobile');
            if (mobileInput) {
                const mobileRegex = /^[+]?[0-9]{10,15}$/;
                if (!mobileRegex.test(mobileInput.value.replace(/[\s-]/g, ''))) {
                    isValid = false;
                    mobileInput.classList.add('is-invalid');
                } else {
                    mobileInput.classList.remove('is-invalid');
                }
            }
            
            // File Upload validation
            const fileInput = form.querySelector('.validate-file-upload');
            if (fileInput && fileInput.files.length > 0) {
                const file = fileInput.files[0];
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                const maxSize = 2 * 1024 * 1024; // 2MB
                
                if (!validTypes.includes(file.type)) {
                    isValid = false;
                    fileInput.classList.add('is-invalid');
                    alert('Invalid file format. Please upload JPG, JPEG, or PNG.');
                } else if (file.size > maxSize) {
                    isValid = false;
                    fileInput.classList.add('is-invalid');
                    alert('File is too large. Maximum size allowed is 2MB.');
                } else {
                    fileInput.classList.remove('is-invalid');
                }
            }
            
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    });

    // 5. Autohide alerts after 5 seconds
    const autoDismissAlerts = document.querySelectorAll('.alert-dismissible-auto');
    autoDismissAlerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // 6. Delete Confirmation Modal Hooks
    const deleteModal = document.getElementById('deleteConfirmModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const userId = button.getAttribute('data-userid');
            const userName = button.getAttribute('data-username');
            
            const modalTitle = deleteModal.querySelector('.modal-title');
            const modalBodySpan = deleteModal.querySelector('#delete-username-span');
            const deleteInputId = deleteModal.querySelector('#delete-userid-input');
            
            if (modalTitle) modalTitle.textContent = 'Delete User Confirmation';
            if (modalBodySpan) modalBodySpan.textContent = userName;
            if (deleteInputId) deleteInputId.value = userId;
        });
    }
});
