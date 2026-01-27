// Authentication Handler
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    // Handle Login
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            // Check if admin (for demo: username 'admin' is admin)
            const isAdmin = username.toLowerCase() === 'admin';
            
            // Temporary: Store user session (later will be replaced with PHP/Database)
            const user = {
                username: username,
                loggedIn: true,
                loginTime: new Date().toISOString(),
                role: isAdmin ? 'admin' : 'user',
                isAdmin: isAdmin
            };
            
            // Store in localStorage (temporary solution)
            localStorage.setItem('currentUser', JSON.stringify(user));
            
            // Redirect to dag view
            window.location.href = 'dag.php';
        });
    }
    
    // Handle Registration
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('reg-username').value;
            const password = document.getElementById('reg-password').value;
            const passwordConfirm = document.getElementById('reg-password-confirm').value;
            
            // Validate passwords match
            if (password !== passwordConfirm) {
                alert('Wachtwoorden komen niet overeen!');
                return;
            }
            
            // Temporary: Create user account (later will be replaced with PHP/Database)
            const newUser = {
                username: username,
                password: password, // In real app, this would be hashed
                createdAt: new Date().toISOString(),
                role: 'user'
            };
            
            // Store users in localStorage (temporary solution)
            let users = JSON.parse(localStorage.getItem('users') || '[]');
            
            // Check if username already exists
            if (users.some(u => u.username === username)) {
                alert('Gebruikersnaam bestaat al!');
                return;
            }
            
            users.push(newUser);
            localStorage.setItem('users', JSON.stringify(users));
            
            alert('Account succesvol aangemaakt! Je kunt nu inloggen.');
            window.location.href = 'index.php';
        });
    }
});

// Check if user is logged in (for protected pages)
function checkAuth() {
    const currentUser = localStorage.getItem('currentUser');
    
    if (!currentUser && !window.location.pathname.includes('index.php') && !window.location.pathname.includes('register.php')) {
        window.location.href = 'index.php';
    }
    
    return currentUser ? JSON.parse(currentUser) : null;
}

// Logout function
function logout() {
    localStorage.removeItem('currentUser');
    window.location.href = 'index.php';
}
