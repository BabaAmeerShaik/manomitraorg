/**
 * ManoMitra Profile - JavaScript
 * Handles profile page functionality
 */

const ManoMitraProfile = {
    // User data
    userData: null,
    
    // Initialize profile
    init: function() {
        console.log('Initializing ManoMitra profile...');
        
        // Load user data
        this.loadUserData();
        
        // Set up tab navigation
        this.setupTabs();
        
        // Set up profile form submission
        this.setupProfileForm();
        
        // Set up other handlers
        this.setupEventHandlers();
    },
    
    // Load user data from the server
    loadUserData: function() {
        ManoMitra.getUserStats((data) => {
            this.userData = data;
            this.updateProfileDisplay();
        });
    },
    
    // Set up tab navigation
    setupTabs: function() {
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.id.replace('tab-', '');
                this.changeTab(tabId);
            });
        });
    },
    
    // Change active tab
    changeTab: function(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.add('hidden');
        });
        
        // Show selected tab content
        document.getElementById(`content-${tabId}`).classList.remove('hidden');
        
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
            button.classList.remove('text-emerald-800');
            button.classList.add('text-emerald-600');
        });
        
        document.getElementById(`tab-${tabId}`).classList.add('active');
        document.getElementById(`tab-${tabId}`).classList.add('text-emerald-800');
        document.getElementById(`tab-${tabId}`).classList.remove('text-emerald-600');
    },
    
    // Set up profile form submission
    setupProfileForm: function() {
        const form = document.getElementById('profile-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.updateProfile();
            });
        }
    },
    
    // Set up other event handlers
    setupEventHandlers: function() {
        // Update anchors button
        const updateAnchorsBtn = document.getElementById('update-anchors-btn');
        if (updateAnchorsBtn) {
            updateAnchorsBtn.addEventListener('click', this.redirectToUpdateAnchors);
        }
        
        // Export data button
        const exportBtn = document.getElementById('export-data-btn');
        if (exportBtn) {
            exportBtn.addEventListener('click', this.exportData);
        }
        
        // Clear data button
        const clearBtn = document.getElementById('clear-data-btn');
        if (clearBtn) {
            clearBtn.addEventListener('click', this.clearData);
        }
        
        // Delete account button
        const deleteBtn = document.getElementById('delete-account-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', this.confirmDeleteAccount);
        }
    },
    
    // Update profile display with user data
    updateProfileDisplay: function() {
        if (!this.userData) return;
        
        const userInfo = this.userData.user_info;
        const stats = userInfo.stats;
        
        // Update basic profile info
        document.getElementById('profile-name').textContent = userInfo.username;
        document.getElementById('profile-avatar').textContent = userInfo.username.charAt(0).toUpperCase();
        document.getElementById('profile-date').textContent = new Date(userInfo.member_since).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long'
        });
        
        // Update form fields
        document.getElementById('edit-username').value = userInfo.username;
        if (userInfo.email) {
            document.getElementById('edit-email').value = userInfo.email;
        }
        
        // Update stats summary
        document.getElementById('stats-games').textContent = stats.games_played || 0;
        document.getElementById('stats-reaction').textContent = stats.best_reaction ? `${stats.best_reaction}ms` : '--';
        document.getElementById('stats-memory').textContent = `${Math.round(stats.memory_skill)}%`;
        document.getElementById('stats-streak').textContent = stats.streak || 0;
        
        // Update skill bars
        document.getElementById('skill-memory').style.width = `${stats.memory_skill}%`;
        document.getElementById('skill-focus').style.width = `${stats.focus_skill}%`;
        document.getElementById('skill-problem').style.width = `${stats.problem_skill}%`;
        document.getElementById('skill-reaction').style.width = `${stats.reaction_skill}%`;
        
        // Update performance chart
        this.initializePerformanceChart();
        
        // Update activity history
        this.updateActivityHistory();
        
        // Update recommendations
        this.updateRecommendations();
    },
    
    // Initialize performance overview chart
    initializePerformanceChart: function() {
        if (!this.userData) return;
        
        const stats = this.userData.user_info.stats;
        const ctx = document.getElementById('performanceChart').getContext('2d');
        
        const performanceChart = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Memory', 'Focus', 'Problem Solving', 'Reaction Speed', 'Attention', 'Mindfulness'],
                datasets: [{
                    label: 'Your Skills',
                    data: [
                        stats.memory_skill,
                        stats.focus_skill,
                        stats.problem_skill,
                        stats.reaction_skill,
                        stats.focus_skill * 0.8, // Derived from focus
                        stats.focus_skill * 0.7  // Derived from focus
                    ],
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: 'rgb(16, 185, 129)',
                    pointBackgroundColor: 'rgb(16, 185, 129)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgb(16, 185, 129)'
                }]
            },
            options: {
                scales: {
                    r: {
                        angleLines: {
                            display: true
                        },
                        suggestedMin: 0,
                        suggestedMax: 100
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                maintainAspectRatio: false
            }
        });
        
        // Initialize monthly progress chart
        this.initializeMonthlyChart();
    },
    
    // Initialize monthly progress chart
    initializeMonthlyChart: function() {
        if (!this.userData) return;
        
        const monthlyData = this.userData.monthly_data;
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        
        // Prepare data for chart
        const labels = [];
        const data = [];
        
        // If we have monthly data, use it
        if (monthlyData && monthlyData.length > 0) {
            monthlyData.forEach(item => {
                // Format month (YYYY-MM to Month name)
                const dateParts = item.month.split('-');
                const month = new Date(dateParts[0], dateParts[1] - 1, 1)
                    .toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                
                labels.push(month);
                data.push(item.games_played);
            });
        } else {
            // Fallback to sample data
            labels.push('Week 1', 'Week 2', 'Week 3', 'Week 4');
            data.push(5, 12, 8, 17);
        }
        
        const monthlyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Games Played',
                    data: data,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: 'rgb(16, 185, 129)',
                    tension: 0.3
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                maintainAspectRatio: false
            }
        });
    },
    
    // Update activity history table
    updateActivityHistory: function() {
        if (!this.userData) return;
        
        const recentActivity = this.userData.recent_activity;
        const activityTable = document.getElementById('activity-table');
        
        if (!activityTable) return;
        
        // Clear existing rows
        activityTable.innerHTML = '';
        
        if (recentActivity && recentActivity.length > 0) {
            recentActivity.forEach(activity => {
                // Format date
                const date = new Date(activity.played_at).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
                
                // Format score based on game type
                let scoreDisplay = activity.score;
                if (activity.game_code === 'sequence-recall' || activity.game_code === 'pattern-recognition') {
                    scoreDisplay = `Level ${activity.level_reached || activity.score}`;
                } else if (activity.game_code === 'reaction-time') {
                    scoreDisplay = `${activity.score}ms`;
                }
                
                // Create row
                const row = document.createElement('tr');
                row.className = 'border-t border-gray-200';
                
                row.innerHTML = `
                    <td class="py-3 px-4">${activity.game_name}</td>
                    <td class="py-3 px-4">${date}</td>
                    <td class="py-3 px-4">${scoreDisplay}</td>
                    <td class="py-3 px-4 text-emerald-600">+3%</td>
                `;
                
                activityTable.appendChild(row);
            });
        } else {
            // No activity data
            const row = document.createElement('tr');
            row.className = 'border-t border-gray-200';
            
            const cell = document.createElement('td');
            cell.className = 'py-3 px-4 text-gray-500';
            cell.colSpan = 4;
            cell.textContent = 'No activity data available yet. Play some games to see your history.';
            
            row.appendChild(cell);
            activityTable.appendChild(row);
        }
    },
    
    // Update recommendations
    updateRecommendations: function() {
        if (!this.userData) return;
        
        const recommendations = this.userData.recommendations;
        const recContainer = document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.gap-4');
        
        if (!recContainer) return;
        
        // Clear existing recommendations
        recContainer.innerHTML = '';
        
        if (recommendations && recommendations.length > 0) {
            recommendations.forEach(rec => {
                const div = document.createElement('div');
                div.className = 'border border-emerald-200 rounded-lg p-4';
                
                let gamesList = '';
                if (rec.games && rec.games.length > 0) {
                    gamesList = '<ul class="mt-1 text-sm text-gray-600">';
                    rec.games.forEach(game => {
                        gamesList += `<li>• ${game.game_name}</li>`;
                    });
                    gamesList += '</ul>';
                }
                
                div.innerHTML = `
                    <h3 class="font-medium text-emerald-700 mb-2">Focus on ${rec.skill}</h3>
                    <p class="text-gray-600 text-sm">${rec.message}</p>
                    ${gamesList}
                `;
                
                recContainer.appendChild(div);
            });
        } else {
            // No recommendations
            const div = document.createElement('div');
            div.className = 'border border-emerald-200 rounded-lg p-4';
            div.innerHTML = `
                <h3 class="font-medium text-emerald-700 mb-2">Keep Up the Good Work!</h3>
                <p class="text-gray-600 text-sm">Continue playing a variety of games to maintain and improve your cognitive skills.</p>
            `;
            
            recContainer.appendChild(div);
        }
    },
    
    // Update profile information
    updateProfile: function() {
        const newUsername = document.getElementById('edit-username').value.trim();
        const newEmail = document.getElementById('edit-email').value.trim();
        
        if (!newUsername) {
            ManoMitra.showNotification('Username cannot be empty', 'error');
            return;
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('username', newUsername);
        formData.append('email', newEmail);
        
        // Send update request
        fetch(ManoMitra.apiUrl + 'update_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                ManoMitra.showNotification(data.message, 'success');
                
                // Update display
                document.getElementById('profile-name').textContent = newUsername;
                document.getElementById('profile-avatar').textContent = newUsername.charAt(0).toUpperCase();
                
                // Reload user data
                this.loadUserData();
            } else {
                ManoMitra.showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Update profile error:', error);
            ManoMitra.showNotification('An error occurred while updating your profile', 'error');
        });
    },
    
    // Redirect to update anchors page
    redirectToUpdateAnchors: function() {
        window.location.href = 'update-anchors.html';
    },
    
    // Export user data
    exportData: function() {
        fetch(ManoMitra.apiUrl + 'export_data.php')
            .then(response => response.blob())
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'manomitra_data.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                console.error('Export data error:', error);
                ManoMitra.showNotification('An error occurred while exporting your data', 'error');
            });
    },
    
    // Clear user activity data
    clearData: function() {
        if (confirm('Are you sure you want to clear all your activity data? This cannot be undone.')) {
            fetch(ManoMitra.apiUrl + 'clear_data.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    ManoMitra.showNotification(data.message, 'success');
                    
                    // Reload user data
                    ManoMitraProfile.loadUserData();
                } else {
                    ManoMitra.showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Clear data error:', error);
                ManoMitra.showNotification('An error occurred while clearing your data', 'error');
            });
        }
    },
    
    // Delete account with confirmation
    confirmDeleteAccount: function() {
        if (confirm('Are you sure you want to delete your account? All data will be lost and this cannot be undone.')) {
            fetch(ManoMitra.apiUrl + 'delete_account.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    ManoMitra.showNotification(data.message, 'success');
                    
                    // Redirect to login page after delay
                    setTimeout(() => {
                        window.location.href = 'login.html';
                    }, 2000);
                } else {
                    ManoMitra.showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Delete account error:', error);
                ManoMitra.showNotification('An error occurred while deleting your account', 'error');
            });
        }
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    ManoMitraProfile.init();
});