<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🎲 كازينو الحظ 2</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"> 
    <style>
        /* Custom animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
            100% { transform: translateY(0px); }
        }
        @keyframes pulse-glow {
            0% { box-shadow: 0 0 5px rgba(255,215,0,0.5); }
            50% { box-shadow: 0 0 20px rgba(255,215,0,0.9); }
            100% { box-shadow: 0 0 5px rgba(255,215,0,0.5); }
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        body {
            background: linear-gradient(-45deg, #1a1a2e, #16213e, #0f3460, #533483);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            color: white;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @media (max-width: 768px) {
            .bet-controls {
                flex-direction: column;
                align-items: center;
            }
            .bet-amount-display {
                margin: 10px 0;
            }
            .game-tab {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
            #wheel-canvas {
                width: 250px;
                height: 250px;
            }
        }
        @media (max-width: 480px) {
            .spin-button {
                padding: 12px 24px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center py-4 px-2 sm:py-8 sm:px-4 font-sans">
    <!-- Main Game Container -->
    <div class="glass-card rounded-2xl p-4 sm:p-6 w-full max-w-4xl shadow-2xl">
        <!-- Header Section -->
        <header class="text-center mb-6 sm:mb-8">
            <h1 class="text-3xl sm:text-5xl font-bold mb-2 bg-clip-text text-transparent bg-gradient-to-r from-yellow-400 to-yellow-600 animate__animated animate__fadeInDown">
                🎲 كازينو الحظ 2
            </h1>
        </header>
        <!-- Player Stats -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 glass-card p-4 rounded-lg animate__animated animate__fadeIn animate__delay-1s">
            <div class="text-center px-4 mb-4 sm:mb-0">
                <div class="text-sm text-yellow-300">رصيدك</div>
                <div id="balance" class="text-2xl font-bold">50,000</div>
                <div class="text-xs">نقطة</div>
            </div>
            <!-- Bet Controls -->
            <div class="text-center px-4 mb-4 sm:mb-0">
                <div class="text-sm text-yellow-300 font-bold">الرهان</div>
                <div class="flex items-center justify-center bet-controls">
                    <button id="decrease-bet" class="px-3 py-1 bg-red-600 text-white rounded-lg text-xl font-bold hover:bg-red-700 transition">
                        -
                    </button>
                    <div id="bet-amount" class="text-2xl font-bold mx-4 bet-amount-display bg-yellow-600 px-4 py-2 rounded-lg">
                        1,000
                    </div>
                    <button id="increase-bet" class="px-3 py-1 bg-green-600 text-white rounded-lg text-xl font-bold hover:bg-green-700 transition">
                        +
                    </button>
                </div>
                <div class="text-xs mt-1">غيّر الرهان (كل كبسة ٥٠٠)</div>
            </div>
            <div class="text-center px-4">
                <div class="text-sm text-yellow-300">آخر ربح</div>
                <div id="last-win" class="text-2xl font-bold">0</div>
                <div class="text-xs">نقطة</div>
            </div>
        </div>
        <!-- Game Selection Tabs -->
        <div class="flex justify-center mb-6 animate__animated animate__fadeIn animate__delay-1s">
            <button id="dice-tab" class="game-tab active px-4 sm:px-6 py-2 rounded-t-lg bg-yellow-600 text-white font-bold transition-all duration-300">
                لعبة النرد
            </button>
            <button id="wheel-tab" class="game-tab px-4 sm:px-6 py-2 rounded-t-lg bg-gray-700 text-white font-bold hover:bg-gray-600 transition-all duration-300">
                عجلة الحظ
            </button>
        </div>
        <!-- Dice Game -->
        <div id="dice-game" class="game-content">
            <div class="text-center mb-6 sm:mb-8">
                <div id="dice-result" class="text-6xl sm:text-8xl my-6 sm:my-8">🎲</div>
                <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <button class="dice-option bg-blue-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-bold" data-bet="low">
                        أقل (١-٣)
                    </button>
                    <button class="dice-option bg-red-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-bold" data-bet="high">
                        أعلى (٤-٦)
                    </button>
                    <button class="dice-option bg-yellow-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-bold" data-bet="even">
                        زوجي
                    </button>
                    <button class="dice-option bg-pink-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-bold" data-bet="odd">
                        فردي
                    </button>
                    <button class="dice-option bg-green-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg font-bold" data-bet="exact">
                        رقم معيّن
                    </button>
                </div>
                <div id="exact-bet-container" class="mt-4 hidden">
                    <select id="exact-number" class="bg-gray-800 text-white p-2 rounded">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                    </select>
                    <button id="confirm-exact" class="bg-green-600 text-white px-3 py-1 sm:px-4 sm:py-2 ml-2 rounded">
                        تأكيد
                    </button>
                </div>
            </div>
            <div class="glass-card p-4 rounded-lg">
                <h3 class="text-xl font-bold text-center mb-4 text-yellow-300">جدول أرباح النرد</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                    <div class="bg-black bg-opacity-30 p-2 rounded">
                        <div>زوجي</div>
                        <div class="font-bold">1x</div>
                    </div>
                    <div class="bg-black bg-opacity-30 p-2 rounded">
                        <div>فردي</div>
                        <div class="font-bold">1x</div>
                    </div>
                    <div class="bg-black bg-opacity-30 p-2 rounded">
                        <div>أقل (١-٣)</div>
                        <div class="font-bold">1.5x</div>
                    </div>
                    <div class="bg-black bg-opacity-30 p-2 rounded">
                        <div>أعلى (٤-٦)</div>
                        <div class="font-bold">1.5x</div>
                    </div>
                    <div class="bg-black bg-opacity-30 p-2 rounded">
                        <div>رقم معيّن</div>
                        <div class="font-bold">5x</div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Wheel Game (Hidden by default) -->
        <div id="wheel-game" class="game-content hidden">
            <div class="text-center mb-6 sm:mb-8">
                <div id="wheel-container" class="relative w-64 h-64 mx-auto my-6 sm:my-8">
                    <canvas id="wheel-canvas" width="300" height="300"></canvas>
                    <div class="wheel-pointer absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-4xl">
                        ▼
                    </div>
                </div>
                <button id="spin-wheel" class="spin-button bg-gradient-to-r from-purple-500 to-purple-600 text-white px-8 sm:px-10 py-3 sm:py-4 rounded-full text-lg sm:text-xl font-bold shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                    🎡 لف العجلة 
                </button>
            </div>
            <div class="glass-card p-4 rounded-lg">
                <h3 class="text-xl font-bold text-center mb-4 text-yellow-300">جوائز العجلة</h3>
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div class="bg-black bg-opacity-30 p-2 rounded">
                        <div>JACKPOT</div>
                        <div class="font-bold">20x</div>
                    </div>
                    <div class="bg-black bg-opacity-30 p-2 rounded">
                        <div>MEGA PRIZE</div>
                        <div class="font-bold">10x</div>
                    </div>
                    <div class="bg-black bg-opacity-30 p-2 rounded">
                        <div>BIG WIN</div>
                        <div class="font-bold">5x</div>
                    </div>
                    <div class="bg-black bg-opacity-30 p-2 rounded">
                        <div>SMALL WIN</div>
                        <div class="font-bold">2x</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Win Celebration Modal -->
    <div id="win-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="absolute inset-0 bg-black bg-opacity-70"></div>
        <div class="relative glass-card p-6 sm:p-8 rounded-2xl text-center max-w-md w-11/12 animate__animated animate__zoomIn">
            <div id="win-icon" class="text-6xl sm:text-8xl mb-4">🎉</div>
            <h2 id="win-title" class="text-2xl sm:text-3xl font-bold mb-2">YOU WON!</h2>
            <div id="win-amount" class="text-4xl sm:text-5xl font-bold text-yellow-400 mb-4">0</div>
            <p id="win-message" class="text-lg sm:text-xl mb-6">Congratulations!</p>
            <button id="close-modal" class="bg-yellow-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-yellow-700 transition">
                كمّل لعب
            </button>
        </div>
    </div>

    <script>
        // Game Configuration
        let forcedSectionIndex = null;
        const config = {
            wheelSections: [
                {text: "JACKPOT", color: "#FFD700", multiplier: 20},
                {text: "TRY AGAIN", color: "#A9A9A9", multiplier: 0},
                {text: "MEGA", color: "#FF6347", multiplier: 10},
                {text: "TRY AGAIN", color: "#A9A9A9", multiplier: 0},
                {text: "BIG WIN", color: "#9370DB", multiplier: 5},
                {text: "TRY AGAIN", color: "#A9A9A9", multiplier: 0},
                {text: "SMALL WIN", color: "#4682B4", multiplier: 2},
                {text: "TRY AGAIN", color: "#A9A9A9", multiplier: 0}
            ],
            apiBaseUrl: 'http://127.0.0.1:8000'
        };

        // Game State
        let state = {
            balance: {{$balance}},
            betAmount: 1000,
            lastWin: 0,
            currentGame: 'dice',
            spinning: false,
            userId: {{$id}},
            isAllowToWinWheel: false,
            isAllowToWinDice: false
        };

        // DOM Elements
        const elements = {
            balance: document.getElementById('balance'),
            betAmount: document.getElementById('bet-amount'),
            lastWin: document.getElementById('last-win'),
            decreaseBet: document.getElementById('decrease-bet'),
            increaseBet: document.getElementById('increase-bet'),
            winModal: document.getElementById('win-modal'),
            winAmount: document.getElementById('win-amount'),
            winTitle: document.getElementById('win-title'),
            winMessage: document.getElementById('win-message'),
            winIcon: document.getElementById('win-icon'),
            closeModal: document.getElementById('close-modal'),
            diceResult: document.getElementById('dice-result'),
            exactBetContainer: document.getElementById('exact-bet-container'),
            wheelCanvas: document.getElementById('wheel-canvas')
        };

        async function init() {
            await getUserBalance();
            setupEventListeners();
            initWheel();
            updateUI();
        }

        async function getUserBalance() {
            try {
                const response = await fetch(`https://harrypotter.africaxbet.com/get_user_balance/${state.userId}`);
                const data = await response.json();
                state.balance = data.user.balance;
                state.isAllowToWinWheel = data.is_allow_to_win_for_wheel;
                state.isAllowToWinDice = data.is_allow_to_win_for_dice;
                adjustBetToBalance();
                updateUI();
            } catch (error) {
                console.error('Error fetching user balance:', error);
            }
        }

        async function updateUserBalance(betData) {
            try {
                const response = await fetch(`https://harrypotter.africaxbet.com/put_user_balance`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        id: state.userId,
                        bet_amount: betData.betAmount,
                        balance_before: betData.balanceBefore,
                        current_balance: state.balance,
                        win_amount: betData.winAmount,
                        game_name: state.currentGame
                    })
                });
                if (!response.ok) throw new Error('Failed to update balance');
                return await response.json();
            } catch (error) {
                console.error('Error updating user balance:', error);
                return null;
            }
        }

        function setupEventListeners() {
            document.getElementById('dice-tab').addEventListener('click', () => switchGame('dice'));
            document.getElementById('wheel-tab').addEventListener('click', () => switchGame('wheel'));

            elements.decreaseBet.addEventListener('click', () => adjustBet(-500));
            elements.increaseBet.addEventListener('click', () => adjustBet(500));

            document.querySelectorAll('.dice-option').forEach(btn => {
                btn.addEventListener('click', function() {
                    const betType = this.dataset.bet;
                    if (betType === 'exact') {
                        elements.exactBetContainer.classList.remove('hidden');
                    } else {
                        rollDice(betType);
                    }
                });
            });

            document.getElementById('confirm-exact').addEventListener('click', () => {
                const selectedNumber = document.getElementById('exact-number').value;
                rollDice('exact', selectedNumber);
            });

            document.getElementById('spin-wheel').addEventListener('click', spinWheel);

            elements.closeModal.addEventListener('click', () => {
                elements.winModal.classList.add('hidden');
            });
        }

        function initWheel() {
            const canvas = elements.wheelCanvas;
            const ctx = canvas.getContext('2d');
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = 140;
            const arc = (2 * Math.PI) / config.wheelSections.length;

            config.wheelSections.forEach((section, index) => {
                ctx.beginPath();
                ctx.fillStyle = section.color;
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, index * arc, (index + 1) * arc);
                ctx.lineTo(centerX, centerY);
                ctx.fill();

                ctx.save();
                ctx.translate(centerX, centerY);
                ctx.rotate(index * arc + arc / 2);
                ctx.textAlign = 'right';
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 14px Arial';
                ctx.fillText(section.text, radius - 10, 5);
                ctx.restore();
            });
        }

        function updateUI() {
            elements.balance.textContent = formatNumber(state.balance);
            elements.lastWin.textContent = formatNumber(state.lastWin);
            elements.betAmount.textContent = formatNumber(state.betAmount);
            elements.decreaseBet.disabled = state.betAmount <= 1000;
            elements.increaseBet.disabled = state.betAmount >= 10000 || state.balance < state.betAmount + 500;
        }

        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function adjustBet(amount) {
            const newBet = state.betAmount + amount;
            if (newBet >= 1000 && newBet <= 10000 && newBet <= state.balance) {
                state.betAmount = newBet;
                adjustBetToBalance();
                updateUI();
            }
        }

        function adjustBetToBalance() {
            if (state.betAmount > state.balance) {
                let maxAllowed = Math.floor(state.balance / 500) * 500;
                if (maxAllowed < 1000) maxAllowed = 1000;
                state.betAmount = maxAllowed;
            }
        }

        function switchGame(game) {
            state.currentGame = game;
            document.querySelectorAll('.game-content').forEach(el => el.classList.add('hidden'));
            document.getElementById(`${game}-game`).classList.remove('hidden');
            document.querySelectorAll('.game-tab').forEach(tab => {
                tab.classList.remove('bg-yellow-600');
                tab.classList.add('bg-gray-700');
            });
            document.getElementById(`${game}-tab`).classList.remove('bg-gray-700');
            document.getElementById(`${game}-tab`).classList.add('bg-yellow-600');
        }

        function rollDice(betType, exactNumber = null) {
            if (state.spinning || state.balance < state.betAmount) return;
            state.spinning = true;
            const balanceBefore = state.balance;
            state.balance -= state.betAmount;
            updateUI();
            elements.exactBetContainer.classList.add('hidden');

            let result;
            if (!state.isAllowToWinDice) {
                // Force a losing result
                switch (betType) {
                    case 'low':
                        result = Math.floor(Math.random() * 3) + 4; // 4,5,6
                        break;
                    case 'high':
                        result = Math.floor(Math.random() * 3) + 1; // 1,2,3
                        break;
                    case 'exact':
                        let possible = [1,2,3,4,5,6].filter(n => n != exactNumber);
                        result = possible[Math.floor(Math.random() * possible.length)];
                        break;
                    case 'even':
                        // User bets even, so roll odd (1,3,5)
                        result = [1,3,5][Math.floor(Math.random() * 3)];
                        break;
                    case 'odd':
                        // User bets odd, so roll even (2,4,6)
                        result = [2,4,6][Math.floor(Math.random() * 3)];
                        break;
                }
                // Animate dice roll visually
                let rolls = 0;
                const maxRolls = 10;
                const rollInterval = setInterval(() => {
                    const randomFace = Math.floor(Math.random() * 6) + 1;
                    elements.diceResult.textContent = getDiceEmoji(randomFace);
                    rolls++;
                    if (rolls >= maxRolls) {
                        clearInterval(rollInterval);
                        // Always show the forced losing result at the end
                        elements.diceResult.textContent = getDiceEmoji(result);
                        finishDiceRoll(betType, result, exactNumber, balanceBefore);
                    }
                }, 100);
            } else {
                // Normal random roll
                let rolls = 0;
                const maxRolls = 10;
                const rollInterval = setInterval(() => {
                    const randomFace = Math.floor(Math.random() * 6) + 1;
                    elements.diceResult.textContent = getDiceEmoji(randomFace);
                    rolls++;
                    if (rolls >= maxRolls) {
                        clearInterval(rollInterval);
                        finishDiceRoll(betType, randomFace, exactNumber, balanceBefore);
                    }
                }, 100);
            }
        }

        async function finishDiceRoll(betType, result, exactNumber, balanceBefore) {
            let winMultiplier = 0;
            let winMessage = '';
            let winAmount = 0;

            switch (betType) {
                case 'low':
                    if (result <= 3 && state.isAllowToWinDice) {
                        winMultiplier = 1.5;
                        winMessage = `LOW WIN! Rolled ${result}`;
                        winAmount = Math.floor(state.betAmount * winMultiplier);
                    } else {
                        winMessage = `LOST! Rolled ${result} (needed 1-3)`;
                    }
                    break;
                case 'high':
                    if (result >= 4 && state.isAllowToWinDice) {
                        winMultiplier = 1.5;
                        winMessage = `HIGH WIN! Rolled ${result}`;
                        winAmount = Math.floor(state.betAmount * winMultiplier);
                    } else {
                        winMessage = `LOST! Rolled ${result} (needed 4-6)`;
                    }
                    break;
                case 'exact':
                    if (result == exactNumber && state.isAllowToWinDice) {
                        winMultiplier = 5;
                        winMessage = `EXACT WIN! Rolled ${result}`;
                        winAmount = Math.floor(state.betAmount * winMultiplier);
                    } else {
                        winMessage = `LOST! Rolled ${result} (needed ${exactNumber})`;
                    }
                    break;
                case 'even':
                    if ([2,4,6].includes(result) && state.isAllowToWinDice) {
                        winMultiplier = 1;
                        winMessage = `EVEN WIN! Rolled ${result}`;
                        winAmount = Math.floor(state.betAmount * winMultiplier);
                    } else {
                        winMessage = `LOST! Rolled ${result} (needed even)`;
                    }
                    break;
                case 'odd':
                    if ([1,3,5].includes(result) && state.isAllowToWinDice) {
                        winMultiplier = 1;
                        winMessage = `ODD WIN! Rolled ${result}`;
                        winAmount = Math.floor(state.betAmount * winMultiplier);
                    } else {
                        winMessage = `LOST! Rolled ${result} (needed odd)`;
                    }
                    break;
            }

            if (winAmount > 0) {
                state.lastWin = winAmount;
                state.balance += winAmount;
                showWin('DICE WIN!', state.lastWin, '🎲');
            } else {
                state.lastWin = 0;
            }

            const updateResp = await updateUserBalance({
                betAmount: state.betAmount,
                balanceBefore: balanceBefore,
                winAmount: winAmount
            });
            if (updateResp) {
                if (typeof updateResp.is_allow_to_win_for_wheel !== 'undefined') {
                    state.isAllowToWinWheel = updateResp.is_allow_to_win_for_wheel;
                }
                if (typeof updateResp.is_allow_to_win_for_dice !== 'undefined') {
                    state.isAllowToWinDice = updateResp.is_allow_to_win_for_dice;
                }
            }

            state.spinning = false;
            updateUI();
        }

        function getDiceEmoji(number) {
            switch (number) {
                case 1: return '⚀';
                case 2: return '⚁';
                case 3: return '⚂';
                case 4: return '⚃';
                case 5: return '⚄';
                case 6: return '⚅';
                default: return '🎲';
            }
        }

        async function spinWheel() {
            if (state.spinning || state.balance < state.betAmount) return;
            state.spinning = true;
            const balanceBefore = state.balance;
            state.balance -= state.betAmount;
            updateUI();

            const canvas = elements.wheelCanvas;
            const ctx = canvas.getContext('2d');
            const sectionCount = config.wheelSections.length;
            const sectionAngle = (2 * Math.PI) / sectionCount;

            if (!state.isAllowToWinWheel) {
                // Only pick from "TRY AGAIN" sections
                const tryAgainIndices = config.wheelSections
                    .map((s, i) => s.text === "TRY AGAIN" ? i : -1)
                    .filter(i => i !== -1);
                forcedSectionIndex = tryAgainIndices[Math.floor(Math.random() * tryAgainIndices.length)];
            } else {
                // Pick any section
                forcedSectionIndex = Math.floor(Math.random() * config.wheelSections.length);
            }

            // Use forcedSectionIndex for stopAngle
            const stopAngle = (config.wheelSections.length - forcedSectionIndex + 5) * sectionAngle + sectionAngle / 2;
            const fullRotations = 6 * 2 * Math.PI; // 6 full spins
            const finalRotation = fullRotations + stopAngle;

            const duration = 5000;
            const startTime = Date.now();

            function animateWheel() {
                const currentTime = Date.now();
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const currentRotation = easeOut * finalRotation;

                ctx.save();
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.translate(canvas.width / 2, canvas.height / 2);
                ctx.rotate(currentRotation);
                ctx.translate(-canvas.width / 2, -canvas.height / 2);

                const centerX = canvas.width / 2;
                const centerY = canvas.height / 2;
                const radius = 140;

                config.wheelSections.forEach((section, index) => {
                    ctx.beginPath();
                    ctx.fillStyle = section.color;
                    ctx.moveTo(centerX, centerY);
                    ctx.arc(centerX, centerY, radius, index * sectionAngle, (index + 1) * sectionAngle);
                    ctx.lineTo(centerX, centerY);
                    ctx.fill();

                    ctx.save();
                    ctx.translate(centerX, centerY);
                    ctx.rotate(index * sectionAngle + sectionAngle / 2);
                    ctx.textAlign = 'right';
                    ctx.fillStyle = '#fff';
                    ctx.font = 'bold 14px Arial';
                    ctx.fillText(section.text, radius - 10, 5);
                    ctx.restore();
                });

                ctx.restore();

                if (progress < 1) {
                    requestAnimationFrame(animateWheel);
                } else {
                    finishWheelSpin(balanceBefore);
                }
            }

            animateWheel();
        }

        async function finishWheelSpin(balanceBefore) {
            let sectionIndex = forcedSectionIndex;
            forcedSectionIndex = null;

            let winningSection = config.wheelSections[sectionIndex];
            let winAmount = 0;

            if (winningSection.multiplier > 0) {
                // Only pay out if isAllowToWinWheel is true
                if (state.isAllowToWinWheel) {
                    winAmount = Math.floor(state.betAmount * winningSection.multiplier);
                    state.lastWin = winAmount;
                    state.balance += winAmount;
                    showWin('WHEEL WIN!', state.lastWin, '🎡');
                } else {
                    // Not allowed to win, treat as loss
                    state.lastWin = 0;
                    elements.winTitle.textContent = "TRY AGAIN!";
                    elements.winAmount.textContent = "0";
                    elements.winIcon.textContent = "😢";
                    elements.winMessage.textContent = "لسا ما زبطت معك! جرب كمان";
                    elements.winModal.classList.remove('hidden');
                }
            } else {
                // Landed on TRY AGAIN (multiplier == 0), always loss
                state.lastWin = 0;
                elements.winTitle.textContent = "TRY AGAIN!";
                elements.winAmount.textContent = "0";
                elements.winIcon.textContent = "😢";
                elements.winMessage.textContent = "لسا ما زبطت معك! جرب كمان";
                elements.winModal.classList.remove('hidden');
            }

            const updateResp = await updateUserBalance({
                betAmount: state.betAmount,
                balanceBefore: balanceBefore,
                winAmount: winAmount
            });
            if (updateResp) {
                if (typeof updateResp.is_allow_to_win_for_wheel !== 'undefined') {
                    state.isAllowToWinWheel = updateResp.is_allow_to_win_for_wheel;
                }
                if (typeof updateResp.is_allow_to_win_for_dice !== 'undefined') {
                    state.isAllowToWinDice = updateResp.is_allow_to_win_for_dice;
                }
            }

            state.spinning = false;
            updateUI();
        }

        function showWin(title, amount, icon) {
            elements.winTitle.textContent = title;
            elements.winAmount.textContent = formatNumber(amount);
            elements.winIcon.textContent = icon;

            if (amount >= state.betAmount * 10) {
                elements.winMessage.textContent = "ربح خرافي! 🎉";
            } else if (amount >= state.betAmount * 5) {
                elements.winMessage.textContent = "ربحت كتير! 🥳";
            } else if (amount >= state.betAmount * 2) {
                elements.winMessage.textContent = "ربح حلو! 😊";
            } else {
                elements.winMessage.textContent = "بداية موفقة! 👍";
            }

            elements.winModal.classList.remove('hidden');
        }

        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>