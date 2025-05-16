<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | CourseDock</title>
    <link href="src/styles.css?v=1.0" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Onest&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Overpass:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
    
        .password-container {
            position: relative;
            width: 100%;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #E3E3E3;
            font-size: 18px;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .password-toggle:hover {
            opacity: 1;
        }
        
        .eye-closed {
            display: none;
        }
        
        .account-warning {
            color: #ff5252;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="./img/COURSEDOCK.svg" class="fade-in">
        <div class="cmstitle">Courseware Monitoring System</div>
    </div>

    <div class="container">
    
    
            <form action="src/scripts/sampledashboard.php" method="POST" >
                <div class="signinbox">
                    <h3>Log In to CourseDock</h3>
                    <p class="subtext">Enter your username or email and password to log in.</p> 

                    <hr>

                    <div class="tfieldname">Username or email address</div>
                    
                    <div class="tf">
                        <input type="text" id="usernamelogin" name="username">
                        <div id="account-warning" class="account-warning"></div>
                    </div>
                    <div class="fieldrow">
                        <div class="tfieldname">Password</div>
                        <div class="forgotpass"><a href="src/resetpass.php">Forgot password?</a></div>
                    </div>
                    <div class="tf">
                        <div class="password-container">
                            <input type="password" name="password" id="passwordlogin">
                            <span class="password-toggle" id="togglePassword">
                                <svg xmlns="http://www.w3.org/2000/svg" class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                    <line x1="2" x2="22" y1="2" y2="22"></line>
                                </svg>
                            </span>
                        </div>
                    </div>

                    <div class="g-recaptcha" data-sitekey="6LdTgDQrAAAAAHokkaDM8gCwd4ZjgYAJh-H4o4Zg" data-theme="dark" style="margin: 10px 0; border-radius: 30px;"></div>
                 
                  

                    <button class="btnlogin" type="submit" style="margin-bottom: 20px;">Login</button>
                    <br>
                </div>
            </form>
            
            <br>
            <div class="signinbox">
                <div class="new-user-line">
                    <span class="newt">New to CourseDock?</span>
                    <span class="reglink"><a href="./src/register.php">Register here.</a></span>
                </div>
            </div>
            
            <div class="footer">
            
            <hr><br>
                © 2025 CourseDock. All rights reserved.
                
                <a href="./src/about.php">About CourseDock</a>
                <a href="./src/support.php">Contact our Support</a>

            </div>
    </div>

    
    <script>
const form = document.querySelector('form');
const usernameField = document.getElementById('usernamelogin');
const passwordField = document.getElementById('passwordlogin');
const togglePassword = document.getElementById('togglePassword');
const eyeOpen = document.querySelector('.eye-open');
const eyeClosed = document.querySelector('.eye-closed');
const accountWarning = document.getElementById('account-warning');

// Track login attempts - use localStorage for persistence
let loginAttempts = JSON.parse(localStorage.getItem('loginAttempts')) || {};

// Maximum allowed attempts before locking
const MAX_ATTEMPTS = 3;
const LOCKOUT_DURATION = 24 * 60 * 60 * 1000; // 24 hours in milliseconds

// Function to show toast messages
function showToast(message, type = 'error') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerText = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove(); 
    }, 3000);
}

// Function to check if account is locked
function isAccountLocked(username) {
    if (!username) return false;
    
    // Check in loginAttempts object
    if (loginAttempts[username] && loginAttempts[username].locked) {
        // Check if lockout period has expired
        const now = Date.now();
        if (loginAttempts[username].timestamp && (now - loginAttempts[username].timestamp > LOCKOUT_DURATION)) {
            // Unlock account if lockout period has passed
            loginAttempts[username].locked = false;
            loginAttempts[username].count = 0;
            localStorage.removeItem(`locked_${username}`);
            localStorage.setItem('loginAttempts', JSON.stringify(loginAttempts));
            return false;
        }
        return true;
    }
    
    // Double-check in localStorage (for persistence across sessions)
    return localStorage.getItem(`locked_${username}`) === 'true';
}

// Function to update account warning message with attempt countdown
function updateAccountWarning(username) {
    if (!username) {
        accountWarning.style.display = 'none';
        return;
    }
    
    if (isAccountLocked(username)) {
        // Calculate remaining lockout time
        const now = Date.now();
        const lockTime = loginAttempts[username].timestamp;
        const timeElapsed = now - lockTime;
        const timeRemaining = LOCKOUT_DURATION - timeElapsed;
        
        if (timeRemaining > 0) {
            const hoursRemaining = Math.floor(timeRemaining / (60 * 60 * 1000));
            const minutesRemaining = Math.floor((timeRemaining % (60 * 60 * 1000)) / (60 * 1000));
            
            accountWarning.textContent = `This account has been locked due to multiple failed login attempts. Lockout expires in ${hoursRemaining}h ${minutesRemaining}m.`;
        } else {
            accountWarning.textContent = 'This account has been locked due to multiple failed login attempts. Please contact support.';
        }
        
        accountWarning.style.display = 'block';
        usernameField.classList.add('error-border');
    } else if (loginAttempts[username] && loginAttempts[username].count > 0 && loginAttempts[username].count < MAX_ATTEMPTS) {
        const attemptsLeft = MAX_ATTEMPTS - loginAttempts[username].count;
        accountWarning.textContent = `Warning: ${attemptsLeft} login ${attemptsLeft === 1 ? 'attempt' : 'attempts'} remaining before account lockout.`;
        accountWarning.style.display = 'block';
        
        // Change warning color based on attempts left
        if (attemptsLeft === 1) {
            accountWarning.style.color = '#ff0000'; // Red for last attempt
        } else if (attemptsLeft === 2) {
            accountWarning.style.color = '#ff9900'; // Orange for second attempt
        } else {
            accountWarning.style.color = '#ff5252'; // Default color
        }
    } else {
        accountWarning.style.display = 'none';
        accountWarning.style.color = '#ff5252'; // Reset to default color
    }
}

togglePassword.addEventListener('click', function () {
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    
    eyeOpen.style.display = type === 'password' ? 'block' : 'none';
    eyeClosed.style.display = type === 'password' ? 'none' : 'block';
});

form.addEventListener('submit', function(e) {
    const username = usernameField.value.trim();
    const password = passwordField.value.trim();
    const captchaResponse = grecaptcha.getResponse();

    // Check if account is locked
    if (isAccountLocked(username)) {
        e.preventDefault();
        showToast('This account has been locked due to multiple failed login attempts. Please try again later or contact support.', 'error');
        return;
    }

    if (username === '' || password === '' || captchaResponse === '') {
        e.preventDefault();

        if (username === '') usernameField.classList.add('error-border');
        if (password === '') passwordField.classList.add('error-border');
        if (captchaResponse === '') {
            showToast('Please complete the CAPTCHA.');
            return;
        }

        showToast('Please fill in all fields.');
        return;
    }
    
    // Add username to form data for tracking failed attempts
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'attempted_username';
    hiddenInput.value = username;
    form.appendChild(hiddenInput);
});

// Process URL parameters for login errors
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('error') === 'invalid') {
    const username = urlParams.get('username') || '';
    
    // Initialize login attempts for this username if not exists
    if (!loginAttempts[username]) {
        loginAttempts[username] = {
            count: 0,
            locked: false,
            timestamp: Date.now()
        };
    }
    
    // Increment failed attempts
    loginAttempts[username].count++;
    loginAttempts[username].timestamp = Date.now();
    
    // Update localStorage
    localStorage.setItem('loginAttempts', JSON.stringify(loginAttempts));
    
    // Check if account should be locked (3 failed attempts)
    if (loginAttempts[username].count >= MAX_ATTEMPTS) {
        loginAttempts[username].locked = true;
        localStorage.setItem(`locked_${username}`, 'true');
        localStorage.setItem('loginAttempts', JSON.stringify(loginAttempts));
        
        showToast(`Your account has been locked due to ${MAX_ATTEMPTS} failed login attempts. Please try again after 24 hours or contact support.`, 'error');
        
        // Pre-fill the username field
        usernameField.value = username;
        updateAccountWarning(username);
    } else {
        const attemptsLeft = MAX_ATTEMPTS - loginAttempts[username].count;
        showToast(`Invalid username or password. ${attemptsLeft} ${attemptsLeft === 1 ? 'attempt' : 'attempts'} remaining before account lockout.`, 'warning');
        
        // Pre-fill the username field
        usernameField.value = username;
        updateAccountWarning(username);
    }
} else if (urlParams.get('locked') === 'true') {
    const username = urlParams.get('username') || '';
    showToast('This account has been locked due to multiple failed login attempts. Please try again after 24 hours or contact support.', 'error');
    
    if (username) {
        usernameField.value = username;
        updateAccountWarning(username);
    }
}

// Check localStorage for locked accounts on page load
document.addEventListener('DOMContentLoaded', function() {
    // Clean up old login attempts (older than 24 hours)
    const now = Date.now();
    
    Object.keys(loginAttempts).forEach(username => {
        if (loginAttempts[username].timestamp && (now - loginAttempts[username].timestamp > LOCKOUT_DURATION)) {
            // Reset if lockout period has passed
            if (loginAttempts[username].locked) {
                loginAttempts[username].locked = false;
                loginAttempts[username].count = 0;
                localStorage.removeItem(`locked_${username}`);
            } else if (loginAttempts[username].count > 0) {
                // Reset attempt count if not locked and old
                delete loginAttempts[username];
            }
        }
    });
    
    localStorage.setItem('loginAttempts', JSON.stringify(loginAttempts));
    
    // Check if current username is locked
    const username = usernameField.value.trim();
    if (username) {
        updateAccountWarning(username);
    }
});

usernameField.addEventListener('input', () => {
    const username = usernameField.value.trim();
    
    if (username !== '') {
        usernameField.classList.remove('error-border');
    }
    
    // Update warning based on username
    updateAccountWarning(username);
});

usernameField.addEventListener('blur', () => {
    const username = usernameField.value.trim();
    updateAccountWarning(username);
});

passwordField.addEventListener('input', () => {
    if (passwordField.value.trim() !== '') {
        passwordField.classList.remove('error-border');
    }
});

// Function to check remaining lockout time (for debugging)
function checkLockoutTime(username) {
    if (!loginAttempts[username] || !loginAttempts[username].locked) {
        console.log(`User ${username} is not locked.`);
        return;
    }
    
    const now = Date.now();
    const lockTime = loginAttempts[username].timestamp;
    const timeElapsed = now - lockTime;
    const timeRemaining = LOCKOUT_DURATION - timeElapsed;
    
    if (timeRemaining > 0) {
        const hoursRemaining = Math.floor(timeRemaining / (60 * 60 * 1000));
        const minutesRemaining = Math.floor((timeRemaining % (60 * 60 * 1000)) / (60 * 1000));
        console.log(`User ${username} lockout expires in ${hoursRemaining}h ${minutesRemaining}m.`);
    } else {
        console.log(`User ${username} lockout has expired but hasn't been reset yet.`);
    }
}
</script>
</body>
</html>