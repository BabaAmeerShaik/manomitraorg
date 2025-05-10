/**
 * ManoMitra Games - JavaScript
 * Handles game functionality and score calculations
 */

// Global games namespace
const ManoMitraGames = {
    currentGame: null,
    gameData: {}, // Will store game-specific data
    
    // Initialize games functionality
    init: function() {
        console.log('Initializing ManoMitra games...');
        
        // Set up game category filters
        this.setupCategoryFilters();
        
        // Set up game launch buttons
        this.setupGameLaunchers();
        
        // Set up game-specific handlers
        this.initializeMemoryGame();
        this.initializeSequenceGame();
        this.initializeReactionGame();
        this.initializeMathGame();
        this.initializeWordGame();
        this.initializePatternGame();
        this.initializeBreathingExercise();
    },
    
    // Set up category filter buttons
    setupCategoryFilters: function() {
        document.querySelectorAll('.category-btn').forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.category-btn').forEach(btn => {
                    btn.classList.remove('bg-emerald-500', 'text-white');
                    btn.classList.add('bg-white', 'hover:bg-emerald-100', 'text-emerald-700');
                });
                
                // Add active class to clicked button
                this.classList.remove('bg-white', 'hover:bg-emerald-100', 'text-emerald-700');
                this.classList.add('bg-emerald-500', 'text-white');
                
                // Get category
                const category = this.textContent.trim().toLowerCase();
                
                // Filter game cards
                if (category === 'all games') {
                    // Show all games
                    document.querySelectorAll('.game-card').forEach(card => {
                        card.style.display = 'block';
                    });
                } else {
                    // Filter by category
                    document.querySelectorAll('.game-card').forEach(card => {
                        const gameCategory = card.dataset.category;
                        if (gameCategory && gameCategory.toLowerCase() === category) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                }
            });
        });
    },
    
    // Set up game launcher buttons
    setupGameLaunchers: function() {
        document.querySelectorAll('.game-card').forEach(card => {
            card.addEventListener('click', () => {
                const gameId = card.dataset.gameId;
                if (gameId) {
                    this.startGame(gameId);
                }
            });
        });
        
        // Back buttons to return to gallery
        document.querySelectorAll('.back-button').forEach(button => {
            button.addEventListener('click', this.exitGame.bind(this));
        });
    },
    
    // Start a game
    startGame: function(gameId) {
        this.currentGame = gameId;
        
        // Hide games gallery
        const gamesGallery = document.getElementById('games-gallery');
        if (gamesGallery) {
            gamesGallery.style.display = 'none';
        }
        
        // Show selected game screen
        const gameScreen = document.getElementById(`${gameId}-game`);
        if (gameScreen) {
            gameScreen.style.display = 'block';
            
            // Initialize game-specific functionality
            switch (gameId) {
                case 'memory-match':
                    this.startMemoryGame();
                    break;
                case 'sequence-recall':
                    // Just show the screen, player needs to click start
                    break;
                case 'reaction-time':
                    // Just show the screen, player needs to click start
                    break;
                case 'math-game':
                    this.startMathGame();
                    break;
                case 'word-association':
                    this.startWordGame();
                    break;
                case 'pattern-recognition':
                    this.initializePatternGame();
                    break;
                case 'breathing-exercise':
                    // Just show the screen, player needs to click start
                    break;
                case 'find-difference':
                    this.initializeDifferenceGame();
                    break;
            }
        }
    },
    
    // Exit current game
    exitGame: function() {
        // Reset current game
        this.currentGame = null;
        
        // Hide all game screens
        document.querySelectorAll('.game-screen').forEach(screen => {
            screen.style.display = 'none';
        });
        
        // Show games gallery
        const gamesGallery = document.getElementById('games-gallery');
        if (gamesGallery) {
            gamesGallery.style.display = 'block';
        }
    },
    
    // Save game result
    saveGameResult: function(gameId, score, duration = 0, level = 0, completed = true) {
        // Create game result object
        const gameResult = {
            gameId: gameId,
            score: score,
            duration: duration,
            level: level,
            completed: completed,
            timestamp: new Date().toISOString()
        };
        
        // Dispatch game completed event (will be caught by main app to save to backend)
        const event = new CustomEvent('gameCompleted', { detail: gameResult });
        document.dispatchEvent(event);
        
        return gameResult;
    },
    
    // ======= MEMORY MATCH GAME =======
    initializeMemoryGame: function() {
        // Initialize memory game variables
        this.gameData.memory = {
            cards: [],
            flippedCards: [],
            matchedPairs: 0,
            lockBoard: false,
            timer: null,
            time: 0,
            totalPairs: 8
        };
        
        // Set up memory game restart button
        const restartBtn = document.querySelector('#memory-match-game button[onclick="startMemoryGame()"]');
        if (restartBtn) {
            restartBtn.onclick = this.startMemoryGame.bind(this);
        }
        
        // Set up card click handlers (will be added to cards when game starts)
    },
    
    startMemoryGame: function() {
        const memory = this.gameData.memory;
        
        // Reset game
        const memoryBoard = document.getElementById('memory-board');
        memoryBoard.innerHTML = '';
        memory.flippedCards = [];
        memory.matchedPairs = 0;
        memory.lockBoard = false;
        memory.time = 0;
        
        document.getElementById('memory-pairs').textContent = '0';
        document.getElementById('memory-time').textContent = '0s';
        
        // Clear timer if it exists
        if (memory.timer) clearInterval(memory.timer);
        
        // Start timer
        memory.timer = setInterval(() => {
            memory.time++;
            document.getElementById('memory-time').textContent = `${memory.time}s`;
        }, 1000);
        
        // Generate cards
        const symbols = ['🍎', '🍌', '🍒', '🍓', '🍇', '🍉', '🍊', '🍍', '🥭', '🥝', '🍐', '🥥'];
        const selectedSymbols = symbols.slice(0, 8); // Select 8 symbols for 16 cards (8 pairs)
        memory.cards = [...selectedSymbols, ...selectedSymbols]; // Duplicate for pairs
        memory.totalPairs = selectedSymbols.length;
        
        // Shuffle cards
        memory.cards.sort(() => Math.random() - 0.5);
        
        // Create card elements
        memory.cards.forEach((symbol, index) => {
            const card = document.createElement('div');
            card.classList.add('memory-card');
            card.dataset.index = index;
            card.dataset.symbol = symbol;
            card.innerHTML = `
                <div class="card-front">${symbol}</div>
                <div class="card-back"></div>
            `;
            card.addEventListener('click', this.flipMemoryCard.bind(this));
            memoryBoard.appendChild(card);
        });
        
        document.getElementById('memory-total').textContent = memory.totalPairs;
    },
    
    flipMemoryCard: function(e) {
        const memory = this.gameData.memory;
        const card = e.currentTarget;
        
        if (memory.lockBoard) return;
        if (card.classList.contains('flipped')) return;
        
        card.classList.add('flipped');
        memory.flippedCards.push(card);
        
        if (memory.flippedCards.length === 2) {
            memory.lockBoard = true;
            this.checkForMemoryMatch();
        }
    },
    
    checkForMemoryMatch: function() {
        const memory = this.gameData.memory;
        const [card1, card2] = memory.flippedCards;
        const isMatch = card1.dataset.symbol === card2.dataset.symbol;
        
        if (isMatch) {
            this.disableMemoryCards();
            memory.matchedPairs++;
            document.getElementById('memory-pairs').textContent = memory.matchedPairs;
            
            // Check for game completion
            if (memory.matchedPairs === memory.totalPairs) {
                clearInterval(memory.timer);
                
                // Calculate score (based on time and pairs found)
                // Score formula: 100 - (seconds taken / total pairs) * 5, min score 50
                const score = Math.max(50, Math.min(100, Math.round(100 - (memory.time / memory.totalPairs) * 5)));
                
                // Save result
                const result = this.saveGameResult('memory-match', score, memory.time);
                
                setTimeout(() => {
                    alert(`Congratulations! You completed the game in ${memory.time} seconds. Score: ${score}`);
                }, 500);
            }
        } else {
            this.unflipMemoryCards();
        }
    },
    
    disableMemoryCards: function() {
        const memory = this.gameData.memory;
        
        memory.flippedCards.forEach(card => {
            card.classList.add('matched');
            card.removeEventListener('click', this.flipMemoryCard);
        });
        
        this.resetMemoryBoard();
    },
    
    unflipMemoryCards: function() {
        const memory = this.gameData.memory;
        
        setTimeout(() => {
            memory.flippedCards.forEach(card => {
                card.classList.remove('flipped');
            });
            
            this.resetMemoryBoard();
        }, 1000);
    },
    
    resetMemoryBoard: function() {
        const memory = this.gameData.memory;
        memory.flippedCards = [];
        memory.lockBoard = false;
    },
    
    // ======= SEQUENCE RECALL GAME =======
    initializeSequenceGame: function() {
        // Initialize sequence game variables
        this.gameData.sequence = {
            pattern: [],
            playerPattern: [],
            level: 1,
            isShowing: false
        };
        
        // Set up sequence game start button
        const startBtn = document.getElementById('sequence-start-btn');
        if (startBtn) {
            startBtn.onclick = this.startSequenceGame.bind(this);
        }
        
        // Set up sequence buttons
        document.querySelectorAll('.sequence-btn').forEach(btn => {
            btn.addEventListener('click', this.handleSequenceButtonClick.bind(this));
        });
    },
    
    startSequenceGame: function() {
        const sequence = this.gameData.sequence;
        
        document.getElementById('sequence-start-btn').disabled = true;
        sequence.pattern = [];
        sequence.playerPattern = [];
        sequence.level = 1;
        document.getElementById('sequence-level').textContent = sequence.level;
        document.getElementById('sequence-status').textContent = 'Watch...';
        
        // Generate new pattern
        this.addToSequence();
    },
    
    addToSequence: function() {
        const sequence = this.gameData.sequence;
        
        // Add random color to sequence
        const colors = ['red', 'blue', 'green', 'yellow'];
        const randomColor = colors[Math.floor(Math.random() * colors.length)];
        sequence.pattern.push(randomColor);
        
        // Display sequence to player
        this.showSequence();
    },
    
    showSequence: function() {
        const sequence = this.gameData.sequence;
        sequence.isShowing = true;
        const buttons = document.querySelectorAll('.sequence-btn');
        let i = 0;
        
        // Disable buttons during sequence display
        buttons.forEach(btn => {
            btn.disabled = true;
        });
        
        // Show pattern with delays
        const interval = setInterval(() => {
            if (i >= sequence.pattern.length) {
                clearInterval(interval);
                buttons.forEach(btn => {
                    btn.disabled = false;
                });
                sequence.isShowing = false;
                document.getElementById('sequence-status').textContent = 'Your turn!';
                return;
            }
            
            const currentColor = sequence.pattern[i];
            const button = document.querySelector(`.sequence-btn[data-color="${currentColor}"]`);
            
            // Light up button
            button.classList.add('lit');
            
            // Turn off after delay
            setTimeout(() => {
                button.classList.remove('lit');
            }, 300);
            
            i++;
        }, 600);
    },
    
    handleSequenceButtonClick: function(e) {
        const sequence = this.gameData.sequence;
        if (sequence.isShowing) return;
        
        const btn = e.currentTarget;
        const clickedColor = btn.dataset.color;
        
        // Light up button
        btn.classList.add('lit');
        
        // Turn off after delay
        setTimeout(() => {
            btn.classList.remove('lit');
        }, 300);
        
        // Add to player's pattern
        sequence.playerPattern.push(clickedColor);
        
        // Check if correct
        const index = sequence.playerPattern.length - 1;
        if (sequence.playerPattern[index] !== sequence.pattern[index]) {
            // Wrong pattern
            document.getElementById('sequence-status').textContent = 'Wrong! Game over.';
            
            // Save result
            const result = this.saveGameResult('sequence-recall', sequence.level - 1, 0, sequence.level - 1);
            
            setTimeout(() => {
                alert(`Game over! You reached level ${sequence.level}.`);
                document.getElementById('sequence-start-btn').disabled = false;
            }, 500);
            return;
        }
        
        // Check if complete pattern
        if (sequence.playerPattern.length === sequence.pattern.length) {
            // Correct pattern
            sequence.level++;
            document.getElementById('sequence-level').textContent = sequence.level;
            document.getElementById('sequence-status').textContent = 'Correct! Next level...';
            sequence.playerPattern = [];
            
            // Next level after delay
            setTimeout(() => {
                this.addToSequence();
            }, 1000);
        }
    },
    
    // ======= REACTION TIME GAME =======
    initializeReactionGame: function() {
        // Initialize reaction game variables
        this.gameData.reaction = {
            startTime: null,
            timeouts: [],
            bestTime: Infinity,
            isWaiting: false
        };
        
        // Set up reaction game start button
        const startBtn = document.querySelector('#reaction-time-game button');
        if (startBtn) {
            startBtn.onclick = this.startReactionGame.bind(this);
        }
        
        // Set up reaction target
        const target = document.getElementById('reaction-target');
        if (target) {
            target.onclick = this.handleReactionClick.bind(this);
        }
    },
    
    startReactionGame: function() {
        const reaction = this.gameData.reaction;
        
        // Reset UI
        const target = document.getElementById('reaction-target');
        target.textContent = 'Wait...';
        target.classList.add('waiting');
        target.classList.remove('go');
        
        // Clear any existing timeouts
        reaction.timeouts.forEach(timeout => clearTimeout(timeout));
        reaction.timeouts = [];
        
        // Set waiting state
        reaction.isWaiting = true;
        
        // Random delay between 2-5 seconds
        const delay = Math.floor(Math.random() * 3000) + 2000;
        
        // Set timeout to change to green
        reaction.timeouts.push(setTimeout(() => {
            if (reaction.isWaiting) {
                target.textContent = 'CLICK NOW!';
                target.classList.remove('waiting');
                target.classList.add('go');
                reaction.startTime = Date.now();
            }
        }, delay));
    },
    
    handleReactionClick: function(e) {
        const reaction = this.gameData.reaction;
        const target = e.currentTarget;
        
        if (target.classList.contains('waiting')) {
            // Clicked too early
            clearTimeout(reaction.timeouts[0]);
            target.textContent = 'Too early! Try again.';
            reaction.isWaiting = false;
            setTimeout(() => {
                this.startReactionGame();
            }, 1500);
        } else if (target.classList.contains('go')) {
            // Clicked at right time
            const endTime = Date.now();
            const reactionTime = endTime - reaction.startTime;
            
            // Update UI
            target.textContent = `${reactionTime} ms`;
            target.classList.remove('go');
            document.getElementById('reaction-last').textContent = reactionTime;
            
            // Update best time
            if (reactionTime < reaction.bestTime) {
                reaction.bestTime = reactionTime;
                document.getElementById('reaction-best').textContent = reactionTime;
            }
            
            // Save result
            this.saveGameResult('reaction-time', reactionTime, 0, 0);
            
            // Reset after delay
            setTimeout(() => {
                this.startReactionGame();
            }, 2000);
        }
    },
    
    // ======= MATH GAME =======
    initializeMathGame: function() {
        // Initialize math game variables
        this.gameData.math = {
            score: 0,
            timeLeft: 60,
            timer: null,
            problem: {},
            totalProblems: 0
        };
        
        // Set up math game start button
        const startBtn = document.querySelector('#math-game-game button');
        if (startBtn) {
            startBtn.onclick = this.startMathGame.bind(this);
        }
    },
    
    startMathGame: function() {
        const math = this.gameData.math;
        
        // Reset game
        math.score = 0;
        math.timeLeft = 60;
        math.totalProblems = 0;
        document.getElementById('math-score').textContent = math.score;
        document.getElementById('math-time').textContent = `${math.timeLeft}s`;
        
        // Clear timer if it exists
        if (math.timer) clearInterval(math.timer);
        
        // Start timer
        math.timer = setInterval(() => {
            math.timeLeft--;
            document.getElementById('math-time').textContent = `${math.timeLeft}s`;
            
            if (math.timeLeft <= 0) {
                clearInterval(math.timer);
                
                // Save result
                this.saveGameResult('math-game', math.score, 60, 0);
                
                alert(`Game over! Your score: ${math.score}`);
            }
        }, 1000);
        
        // Generate first problem
        this.generateMathProblem();
    },
    
    generateMathProblem: function() {
        const math = this.gameData.math;
        const operators = ['+', '-', '*'];
        const operator = operators[Math.floor(Math.random() * operators.length)];
        let num1, num2, answer, options;
        
        // Generate numbers based on operator
        switch (operator) {
            case '+':
                num1 = Math.floor(Math.random() * 50) + 1;
                num2 = Math.floor(Math.random() * 50) + 1;
                answer = num1 + num2;
                break;
            case '-':
                num1 = Math.floor(Math.random() * 50) + 10;
                num2 = Math.floor(Math.random() * num1);
                answer = num1 - num2;
                break;
            case '*':
                num1 = Math.floor(Math.random() * 12) + 1;
                num2 = Math.floor(Math.random() * 12) + 1;
                answer = num1 * num2;
                break;
        }
        
        // Generate wrong options
        options = [answer];
        while (options.length < 4) {
            const wrongOption = answer + (Math.floor(Math.random() * 10) - 5);
            if (wrongOption !== answer && !options.includes(wrongOption) && wrongOption > 0) {
                options.push(wrongOption);
            }
        }
        
        // Shuffle options
        options.sort(() => Math.random() - 0.5);
        
        // Store problem data
        math.problem = {
            problem: `${num1} ${operator} ${num2} = ?`,
            answer,
            options
        };
        
        // Update UI
        document.getElementById('math-problem').textContent = math.problem.problem;
        const optionsContainer = document.getElementById('math-options');
        optionsContainer.innerHTML = '';
        
        math.problem.options.forEach(option => {
            const button = document.createElement('button');
            button.classList.add('math-option', 'bg-white', 'hover:bg-emerald-50', 'border-2', 'border-emerald-200', 'rounded-lg', 'px-6', 'py-3', 'text-lg', 'font-medium', 'text-emerald-800');
            button.textContent = option;
            button.onclick = () => {
                this.checkMathAnswer(option);
            };
            optionsContainer.appendChild(button);
        });
        
        // Increment total problems
        math.totalProblems++;
    },
    
    checkMathAnswer: function(selectedOption) {
        const math = this.gameData.math;
        
        if (selectedOption === math.problem.answer) {
            // Correct answer
            math.score++;
            document.getElementById('math-score').textContent = math.score;
        }
        
        // Generate new problem
        this.generateMathProblem();
    },
    
    // ======= WORD ASSOCIATION GAME =======
    initializeWordGame: function() {
        // Initialize word game variables
        this.gameData.word = {
            score: 0,
            timeLeft: 60,
            timer: null,
            currentWord: {},
            totalWords: 0
        };
        
        // Set up word game start button
        const startBtn = document.querySelector('#word-association-game button');
        if (startBtn) {
            startBtn.onclick = this.startWordGame.bind(this);
        }
    },
    
    startWordGame: function() {
        const word = this.gameData.word;
        
        // Reset game
        word.score = 0;
        word.timeLeft = 60;
        word.totalWords = 0;
        document.getElementById('word-score').textContent = word.score;
        document.getElementById('word-time').textContent = `${word.timeLeft}s`;
        
        // Clear timer if it exists
        if (word.timer) clearInterval(word.timer);
        
        // Start timer
        word.timer = setInterval(() => {
            word.timeLeft--;
            document.getElementById('word-time').textContent = `${word.timeLeft}s`;
            
            if (word.timeLeft <= 0) {
                clearInterval(word.timer);
                
                // Save result
                this.saveGameResult('word-association', word.score, 60, 0);
                
                alert(`Game over! Your score: ${word.score}`);
            }
        }, 1000);
        
        // Generate first word
        this.generateWordProblem();
    },
    
    generateWordProblem: function() {
        const word = this.gameData.word;
        
        // Word association pairs (would be much larger in a real app)
        const wordPairs = [
            { target: 'Ocean', correct: 'Wave', options: ['Wave', 'Mountain', 'Blue', 'Tree'] },
            { target: 'Fire', correct: 'Hot', options: ['Hot', 'Cold', 'Wood', 'Light'] },
            { target: 'School', correct: 'Teacher', options: ['Teacher', 'Hospital', 'Book', 'Park'] },
            { target: 'Night', correct: 'Stars', options: ['Stars', 'Sun', 'Day', 'Moon'] },
            { target: 'Dog', correct: 'Bark', options: ['Bark', 'Meow', 'Fur', 'Bird'] },
            { target: 'Apple', correct: 'Fruit', options: ['Fruit', 'Vegetable', 'Red', 'Tree'] },
            { target: 'Chair', correct: 'Sit', options: ['Sit', 'Stand', 'Table', 'Wood'] },
            { target: 'Book', correct: 'Read', options: ['Read', 'Write', 'Paper', 'Library'] },
            { target: 'Winter', correct: 'Snow', options: ['Snow', 'Hot', 'Summer', 'Rain'] },
            { target: 'Car', correct: 'Drive', options: ['Drive', 'Walk', 'Road', 'Bike'] },
            { target: 'Sky', correct: 'Blue', options: ['Blue', 'Ground', 'Cloud', 'Bird'] },
            { target: 'Sleep', correct: 'Dream', options: ['Dream', 'Awake', 'Bed', 'Night'] },
            { target: 'Doctor', correct: 'Hospital', options: ['Hospital', 'Teacher', 'Nurse', 'Medicine'] },
            { target: 'River', correct: 'Flow', options: ['Flow', 'Mountain', 'Fish', 'Bridge'] },
            { target: 'Pizza', correct: 'Cheese', options: ['Cheese', 'Burger', 'Pasta', 'Bread'] }
        ];
        
        // Select random word pair
        const randomIndex = Math.floor(Math.random() * wordPairs.length);
        word.currentWord = wordPairs[randomIndex];
        
        // Shuffle options
        const shuffledOptions = [...word.currentWord.options];
        shuffledOptions.sort(() => Math.random() - 0.5);
        
        // Update UI
        document.getElementById('word-target').textContent = word.currentWord.target;
        const optionsContainer = document.getElementById('word-options');
        optionsContainer.innerHTML = '';
        
        shuffledOptions.forEach(option => {
            const button = document.createElement('button');
            button.classList.add('word-option', 'bg-white', 'hover:bg-emerald-50', 'border-2', 'border-emerald-200', 'rounded-lg', 'px-6', 'py-3', 'text-lg', 'font-medium', 'text-emerald-800');
            button.textContent = option;
            button.onclick = () => {
                this.checkWordAnswer(option);
            };
            optionsContainer.appendChild(button);
        });
        
        // Increment total words
        word.totalWords++;
    },
    
    checkWordAnswer: function(selectedOption) {
        const word = this.gameData.word;
        
        if (selectedOption === word.currentWord.correct) {
            // Correct answer
            word.score++;
            document.getElementById('word-score').textContent = word.score;
        }
        
        // Generate new word
        this.generateWordProblem();
    },
    
    // ======= PATTERN RECOGNITION GAME =======
    initializePatternGame: function() {
        // Initialize pattern game variables
        this.gameData.pattern = {
            grid: [],
            level: 1,
            showMode: true,
            userSelections: []
        };
        
        // Create grid
        const board = document.getElementById('pattern-board');
        if (board) {
            board.innerHTML = '';
            
            for (let i = 0; i < 16; i++) {
                const cell = document.createElement('div');
                cell.classList.add('pattern-cell');
                cell.dataset.index = i;
                board.appendChild(cell);
            }
            
            // Set up pattern game start button
            const button = document.getElementById('pattern-button');
            if (button) {
                button.textContent = 'Start Game';
                button.onclick = this.startPatternGame.bind(this);
            }
            
            document.getElementById('pattern-level').textContent = '1';
            document.getElementById('pattern-status').textContent = 'Press Start';
        }
    },
    
    startPatternGame: function() {
        const pattern = this.gameData.pattern;
        
        pattern.level = 1;
        document.getElementById('pattern-level').textContent = pattern.level;
        document.getElementById('pattern-status').textContent = 'Memorize';
        document.getElementById('pattern-button').textContent = 'Memorizing...';
        document.getElementById('pattern-button').disabled = true;
        
        this.generatePattern();
    },
    
    generatePattern: function() {
        const pattern = this.gameData.pattern;
        
        // Generate pattern based on level
        pattern.grid = [];
        const cellCount = 3 + Math.min(pattern.level, 8); // Start with 4, max at 11
        
        // Reset all cells
        const cells = document.querySelectorAll('.pattern-cell');
        cells.forEach(cell => {
            cell.classList.remove('highlighted', 'selected');
            cell.onclick = null;
        });
        
        // Generate random pattern
        while (pattern.grid.length < cellCount) {
            const index = Math.floor(Math.random() * 16);
            if (!pattern.grid.includes(index)) {
                pattern.grid.push(index);
            }
        }
        
        // Show pattern
        this.showPattern();
    },
    
    showPattern: function() {
        const pattern = this.gameData.pattern;
        pattern.showMode = true;
        let i = 0;
        
        const interval = setInterval(() => {
            if (i >= pattern.grid.length) {
                clearInterval(interval);
                setTimeout(() => {
                    // Hide pattern and allow user to select
                    const cells = document.querySelectorAll('.pattern-cell');
                    cells.forEach(cell => {
                        cell.classList.remove('highlighted');
                    });
                    
                    document.getElementById('pattern-status').textContent = 'Recreate Pattern';
                    pattern.showMode = false;
                    pattern.userSelections = [];
                    
                    // Add click event to cells
                    cells.forEach(cell => {
                        cell.onclick = (e) => {
                            this.handlePatternCellClick(e);
                        };
                    });
                    
                    document.getElementById('pattern-button').textContent = 'Submit Pattern';
                    document.getElementById('pattern-button').disabled = false;
                    document.getElementById('pattern-button').onclick = this.checkPattern.bind(this);
                }, 500);
                return;
            }
            
            // Highlight current cell
            const cellIndex = pattern.grid[i];
            const cell = document.querySelector(`.pattern-cell[data-index="${cellIndex}"]`);
            cell.classList.add('highlighted');
            
            i++;
        }, 600);
    },
    
    handlePatternCellClick: function(e) {
        const pattern = this.gameData.pattern;
        if (pattern.showMode) return;
        
        const cell = e.currentTarget;
        const index = parseInt(cell.dataset.index);
        
        // Toggle selection
        if (cell.classList.contains('selected')) {
            cell.classList.remove('selected');
            pattern.userSelections = pattern.userSelections.filter(i => i !== index);
        } else {
            cell.classList.add('selected');
            pattern.userSelections.push(index);
        }
    },
    
    checkPattern: function() {
        const pattern = this.gameData.pattern;
        
        // Check if patterns match
        let correct = true;
        
        if (pattern.userSelections.length !== pattern.grid.length) {
            correct = false;
        } else {
            // Check each cell
            for (let i = 0; i < pattern.grid.length; i++) {
                if (!pattern.userSelections.includes(pattern.grid[i])) {
                    correct = false;
                    break;
                }
            }
        }
        
        if (correct) {
            // Level complete
            document.getElementById('pattern-status').textContent = 'Correct! Next Level';
            pattern.level++;
            document.getElementById('pattern-level').textContent = pattern.level;
            
            // Show next pattern after delay
            setTimeout(() => {
                this.generatePattern();
            }, 1500);
        } else {
            // Game over
            document.getElementById('pattern-status').textContent = 'Incorrect! Game Over';
            document.getElementById('pattern-button').textContent = 'Try Again';
            document.getElementById('pattern-button').disabled = false;
            document.getElementById('pattern-button').onclick = this.startPatternGame.bind(this);
            
            // Show correct pattern
            pattern.grid.forEach(index => {
                const cell = document.querySelector(`.pattern-cell[data-index="${index}"]`);
                cell.classList.add('highlighted');
            });
            
            // Save result
            this.saveGameResult('pattern-recognition', pattern.level - 1, 0, pattern.level - 1);
        }
    },
    
    // ======= BREATHING EXERCISE =======
    initializeBreathingExercise: function() {
        // Initialize breathing exercise variables
        this.gameData.breathing = {
            state: 'idle', // 'idle', 'inhale', 'exhale'
            cycle: 0,
            interval: null,
            totalCycles: 5
        };
        
        // Set up breathing exercise start button
        const startBtn = document.getElementById('breathing-button');
        if (startBtn) {
            startBtn.onclick = this.startBreathingExercise.bind(this);
        }
    },
    
    startBreathingExercise: function() {
        const breathing = this.gameData.breathing;
        
        // Reset state
        breathing.state = 'idle';
        breathing.cycle = 0;
        
        // Update UI
        const circle = document.querySelector('.breathing-circle');
        const instruction = document.getElementById('breathing-instruction');
        const count = document.getElementById('breathing-count');
        const button = document.getElementById('breathing-button');
        
        button.disabled = true;
        button.textContent = 'In Progress...';
        
        // Start breathing cycle
        breathing.state = 'inhale';
        instruction.textContent = 'Inhale slowly...';
        circle.classList.add('inhale');
        
        // Start countdown
        let countDown = 4;
        count.textContent = countDown;
        
        breathing.interval = setInterval(() => {
            if (breathing.state === 'inhale') {
                countDown--;
                count.textContent = countDown;
                
                if (countDown === 0) {
                    // Switch to exhale
                    breathing.state = 'exhale';
                    instruction.textContent = 'Exhale slowly...';
                    circle.classList.remove('inhale');
                    circle.classList.add('exhale');
                    countDown = 4;
                    count.textContent = countDown;
                }
            } else if (breathing.state === 'exhale') {
                countDown--;
                count.textContent = countDown;
                
                if (countDown === 0) {
                    // Complete one cycle
                    breathing.cycle++;
                    
                    if (breathing.cycle >= breathing.totalCycles) {
                        // Exercise complete
                        clearInterval(breathing.interval);
                        breathing.state = 'idle';
                        instruction.textContent = 'Exercise Complete';
                        button.disabled = false;
                        button.textContent = 'Start Again';
                        circle.classList.remove('exhale');
                        count.textContent = '😌';
                        
                        // Calculate score (5 points per cycle)
                        const score = breathing.cycle * 5;
                        
                        // Save result
                        this.saveGameResult('breathing-exercise', score, breathing.cycle * 8, breathing.cycle);
                    } else {
                        // Start next inhale
                        breathing.state = 'inhale';
                        instruction.textContent = 'Inhale slowly...';
                        circle.classList.remove('exhale');
                        circle.classList.add('inhale');
                        countDown = 4;
                        count.textContent = countDown;
                    }
                }
            }
        }, 1000);
    },
    
    // ======= FIND THE DIFFERENCE GAME =======
    initializeDifferenceGame: function() {
        // This would be implemented in a real app
        // For this demo, we'll just show an alert
        alert('Find the Difference game is not implemented in this demo.');
        this.exitGame();
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    ManoMitraGames.init();
});