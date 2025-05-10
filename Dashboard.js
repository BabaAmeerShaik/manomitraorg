/**
 * ManoMitra Dashboard - JavaScript
 * Handles dashboard functionality
 */

const ManoMitraDashboard = {
    // User data
    userData: null,
    
    // Initialize dashboard
    init: function() {
        console.log('Initializing ManoMitra dashboard...');
        
        // Load user data
        this.loadUserData();
        
        // Set up event handlers
        this.setupEventHandlers();
    },
    
    // Load user data from the server
    loadUserData: function() {
        ManoMitra.getUserStats((data) => {
            this.userData = data;
            this.updateDashboardDisplay();
        });
    },
    
    // Set up event handlers
    setupEventHandlers: function() {
        // Game card clicks
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('click', () => {
                const gameId = card.dataset.gameId;
                if (gameId) {
                    window.location.href = `games.html#${gameId}`;
                }
            });
        });
        
        // Chatbot toggle
        const chatbotToggle = document.getElementById('chatbot-toggle');
        if (chatbotToggle) {
            chatbotToggle.addEventListener('click', () => {
                const container = document.getElementById('chatbot-container');
                container.classList.toggle('active');
            });
        }
    },
    
    // Update dashboard display with user data
    updateDashboardDisplay: function() {
        if (!this.userData) return;
        
        const userInfo = this.userData.user_info;
        const stats = userInfo.stats;
        const gameStats = this.userData.game_stats;
        
        // Update username
        document.getElementById('username').textContent = userInfo.username;
        
        // Update stats
        document.getElementById('streak-count').textContent = stats.streak || 0;
        document.getElementById('games-count').textContent = stats.games_played || 0;
        
        // Update progress bar
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            // Calculate overall progress (average of all skills)
            const overall = (stats.memory_skill + stats.focus_skill + stats.problem_skill + stats.reaction_skill) / 4;
            const percentage = Math.round(overall);
            
            progressBar.style.width = `${percentage}%`;
            document.querySelector('.progress-container + div span').textContent = `${percentage}%`;
        }
        
        // Update game cards progress
        if (gameStats) {
            Object.keys(gameStats).forEach(gameId => {
                const game = gameStats[gameId];
                const card = document.querySelector(`.card[data-game-id="${gameId}"] .progress-bar`);
                
                if (card) {
                    let progressPercent = 0;
                    
                    // Calculate progress percentage based on game type
                    switch (gameId) {
                        case 'memory-match':
                            progressPercent = Math.min(game.high_score, 100);
                            break;
                        case 'sequence-recall':
                            progressPercent = Math.min(game.high_score * 10, 100);
                            break;
                        case 'reaction-time':
                            // Lower is better for reaction time
                            progressPercent = Math.max(0, 100 - (game.high_score / 10));
                            break;
                        case 'math-game':
                            progressPercent = Math.min(game.high_score * 5, 100);
                            break;
                        case 'word-association':
                            progressPercent = Math.min(game.high_score * 4, 100);
                            break;
                        case 'pattern-recognition':
                            progressPercent = Math.min(game.high_score * 15, 100);
                            break;
                        default:
                            progressPercent = Math.min(game.high_score, 100);
                    }
                    
                    card.style.width = `${progressPercent}%`;
                }
            });
        }
        
        // Update recent activity table
        this.updateActivityTable();
    },
    
    // Update recent activity table
    updateActivityTable: function() {
        if (!this.userData) return;
        
        const recentActivity = this.userData.recent_activity;
        const activityTable = document.getElementById('recent-activity-table');
        
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
                    <td class="py-3 px-3">${activity.game_name}</td>
                    <td class="py-3 px-3">${date}</td>
                    <td class="py-3 px-3">${scoreDisplay}</td>
                    <td class="py-3 px-3 text-emerald-600">+2%</td>
                `;
                
                activityTable.appendChild(row);
            });
        } else {
            // No activity data
            const row = document.createElement('tr');
            row.className = 'border-t border-gray-200';
            
            const cell = document.createElement('td');
            cell.className = 'py-4 px-3 text-center text-gray-500';
            cell.colSpan = 4;
            cell.textContent = 'No activity data available yet. Play some games to see your history.';
            
            row.appendChild(cell);
            activityTable.appendChild(row);
        }
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    ManoMitraDashboard.init();
});