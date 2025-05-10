/**
 * ManoMitra Application - Main JavaScript
 * Handles UI interactions and API calls
 */

// Global app namespace
const ManoMitra = {
    currentUser: null,
    isLoggedIn: false,
    apiUrl: './', // Base URL for API calls (same directory as frontend)
    
    // Initialize application
    init: function() {
        console.log('Initializing ManoMitra application...');
        
        // Check if user is logged in (via session)
        this.checkLoginStatus();
        
        // Set up global event listeners
        this.setupEventListeners();
    },
    
    // Check if user is already logged in
    checkLoginStatus: function() {
        fetch(this.apiUrl + 'check_session.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success' && data.data && data.data.logged_in) {
                    this.isLoggedIn = true;
                    this.currentUser = {
                        username: data.data.username,
                        userId: data.data.user_id
                    };
                    
                    // Dispatch login event
                    const event = new CustomEvent('userLoggedIn', { 
                        detail: this.currentUser
                    });
                    document.dispatchEvent(event);
                    
                    // Redirect to dashboard if on login/signup page
                    const currentPath = window.location.pathname;
                    if (currentPath.includes('login.html') || 
                        currentPath.includes('signup.html') ||
                        currentPath === '/' || 
                        currentPath.includes('index.html') ||
                        currentPath.includes('landing_page.html')) {
                        window.location.href = 'dashboard.html';
                    }
                } else {
                    // Not logged in, redirect to login if on protected page
                    const currentPath = window.location.pathname;
                    if (currentPath.includes('dashboard.html') || 
                        currentPath.includes('games.html') || 
                        currentPath.includes('profile.html')) {
                        window.location.href = 'login.html';
                    }
                }
            })
            .catch(error => {
                console.error('Error checking login status:', error);
            });
    },
    
    // Setup global event listeners
    setupEventListeners: function() {
        // Listen for login form submission
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', this.handleLogin.bind(this));
        }
        
        // Listen for signup form submission
        const signupForm = document.getElementById('signup-form');
        if (signupForm) {
            signupForm.addEventListener('submit', this.handleSignup.bind(this));
        }
        
        // Listen for logout button clicks
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'logout-btn') {
                ManoMitra.handleLogout();
            }
        });
        
        // Listen for game completion
        document.addEventListener('gameCompleted', this.saveGameResult.bind(this));
    },
    
    // Handle login form submission
    handleLogin: function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const calmImage = this.getSelectedImage('calm');
        const strongImage = this.getSelectedImage('strong');
        const focusedImage = this.getSelectedImage('focused');
        
        // Validate inputs
        if (!username) {
            this.showNotification("Please enter your username", "error");
            return;
        }
        
        if (!calmImage || !strongImage || !focusedImage) {
            this.showNotification("Please select all three emotional anchors", "warning");
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('username', username);
        formData.append('calm_image', calmImage);
        formData.append('strong_image', strongImage);
        formData.append('focused_image', focusedImage);
        
        // Send login request
        fetch(this.apiUrl + 'login_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.showNotification(data.message, "success");
                
                // Set current user
                this.isLoggedIn = true;
                this.currentUser = {
                    username: data.data.username
                };
                
                // Redirect to dashboard after delay
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 1500);
            } else {
                this.showNotification(data.message, "error");
            }
        })
        .catch(error => {
            console.error('Login error:', error);
            this.showNotification("An error occurred during login. Please try again.", "error");
        });
    },
    
    // Handle signup form submission
    handleSignup: function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email') ? document.getElementById('email').value.trim() : '';
        const calmImage = this.getSelectedImage('calm');
        const strongImage = this.getSelectedImage('strong');
        const focusedImage = this.getSelectedImage('focused');
        
        // Validate inputs
        if (!username) {
            this.showNotification("Please enter a username", "error");
            return;
        }
        
        if (!calmImage || !strongImage || !focusedImage) {
            this.showNotification("Please select all three emotional anchors", "warning");
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('username', username);
        if (email) formData.append('email', email);
        formData.append('calm_image', calmImage);
        formData.append('strong_image', strongImage);
        formData.append('focused_image', focusedImage);
        
        // Send signup request
        fetch(this.apiUrl + 'signup_process.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                this.showNotification(data.message, "success");
                
                // Redirect to login page after delay
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 1500);
            } else {
                this.showNotification(data.message, "error");
            }
        })
        .catch(error => {
            console.error('Signup error:', error);
            this.showNotification("An error occurred during signup. Please try again.", "error");
        });
    },
    
    // Handle logout
    handleLogout: function() {
        fetch(this.apiUrl + 'logout.php')
            .then(response => response.json())
            .then(data => {
                this.isLoggedIn = false;
                this.currentUser = null;
                
                // Redirect to login page
                window.location.href = 'login.html';
            })
            .catch(error => {
                console.error('Logout error:', error);
            });
    },
    
    // Save game result to backend
    saveGameResult: function(e) {
        const gameData = e.detail;
        
        // Check if user is logged in
        if (!this.isLoggedIn) {
            console.error('Cannot save game result: User not logged in');
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('game_code', gameData.gameId);
        formData.append('score', gameData.score);
        if (gameData.duration) formData.append('duration', gameData.duration);
        if (gameData.level) formData.append('level', gameData.level);
        formData.append('completed', gameData.completed !== false);
        
        // Send save request
        fetch(this.apiUrl + 'save_game.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                console.log('Game result saved:', data.data);
                
                // Dispatch event for UI updates
                const event = new CustomEvent('gameResultSaved', { 
                    detail: data.data
                });
                document.dispatchEvent(event);
            } else {
                console.error('Error saving game result:', data.message);
            }
        })
        .catch(error => {
            console.error('Save game error:', error);
        });
    },
    
    // Get user stats for profile or dashboard
    getUserStats: function(callback) {
        fetch(this.apiUrl + 'get_user_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (callback && typeof callback === 'function') {
                        callback(data.data);
                    }
                } else {
                    console.error('Error getting user stats:', data.message);
                }
            })
            .catch(error => {
                console.error('Get stats error:', error);
            });
    },
    
    // Helper function to get selected image for an emotion
    getSelectedImage: function(emotion) {
        const selectedImg = document.querySelector(`[id^=${emotion}-] img.selected`);
        if (selectedImg) {
            const buttonId = selectedImg.parentElement.id;
            return buttonId.split('-')[1]; // Extract image name from id
        }
        return '';
    },
    
    // Show notification with type: success, error, or warning
    showNotification: function(message, type) {
        const notification = document.getElementById('notification');
        if (notification) {
            notification.textContent = message;
            notification.className = `mb-4 p-4 rounded-xl text-center font-medium alert ${type} show`;
            
            // Hide after 5 seconds unless it's an error
            if (type !== 'error') {
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 5000);
            }
        } else {
            // Fallback to alert if notification element doesn't exist
            alert(message);
        }
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    ManoMitra.init();
});