<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🎰 كازينو الحظ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .spin {
            animation: spinAnimation 0.5s ease-in-out infinite;
        }
        @keyframes spinAnimation {
            0% { transform: translateY(0); opacity: 1; }
            50% { transform: translateY(-20px); opacity: 0.7; }
            100% { transform: translateY(0); opacity: 1; }
        }
        
        .win-animation {
            animation: winPulse 0.5s ease-in-out 3;
        }
        @keyframes winPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .slot-container {
            perspective: 1000px;
        }
        .slot-reel {
            transform-style: preserve-3d;
            transition: transform 0.1s;
        }
        
        /* Animated background */
        body {
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Glassmorphism effect for UI elements */
        .glass-card {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        @media (max-width: 600px) {
            .glass-card { padding: 1rem !important; }
            h1 { font-size: 2rem !important; }
            .slot-reel { font-size: 2.5rem !important; }
            .bet-controls { flex-direction: column; gap: 0.5rem; }
        }
    </style>
</head>
<body class="flex flex-col justify-center items-center min-h-screen">
    <div class="glass-card rounded-2xl p-4 sm:p-8 shadow-2xl max-w-lg w-full mx-2">
        <h1 class="text-3xl sm:text-5xl font-bold text-center mb-4 text-white">🎰 كازينو الحظ</h1>
        <div class="flex flex-col sm:flex-row items-center justify-center mb-4 sm:mb-6 gap-2 sm:gap-4">
            <div class="p-3 bg-white rounded-lg shadow-lg bg-opacity-90 w-full sm:w-auto text-center">
                <span class="font-bold text-gray-800">رصيدك:</span> 
                <span id="credits" class="font-mono">50,000</span> 
                <span class="font-bold text-purple-600">نقطة</span>
            </div>
            <div class="p-3 bg-white rounded-lg shadow-lg bg-opacity-90 w-full sm:w-auto text-center">
                <span class="font-bold text-gray-800">آخر ربح:</span> 
                <span id="last-win" class="font-mono">0</span> 
                <span class="font-bold text-purple-600">نقطة</span>
            </div>
        </div>
        <div id="slots" class="flex justify-center space-x-1 sm:space-x-2 mb-4 sm:mb-8 p-2 sm:p-6 bg-white rounded-xl shadow-lg bg-opacity-90">
            <div class="slot-container w-12 h-16 sm:w-16 sm:h-24 flex justify-center items-center">
                <div id="slot1" class="slot-reel text-4xl sm:text-6xl">🍒</div>
            </div>
            <div class="slot-container w-12 h-16 sm:w-16 sm:h-24 flex justify-center items-center">
                <div id="slot2" class="slot-reel text-4xl sm:text-6xl">🍒</div>
            </div>
            <div class="slot-container w-12 h-16 sm:w-16 sm:h-24 flex justify-center items-center">
                <div id="slot3" class="slot-reel text-4xl sm:text-6xl">🍋</div>
            </div>
            <div class="slot-container w-12 h-16 sm:w-16 sm:h-24 flex justify-center items-center">
                <div id="slot4" class="slot-reel text-4xl sm:text-6xl">🍀</div>
            </div>
            <div class="slot-container w-12 h-16 sm:w-16 sm:h-24 flex justify-center items-center">
                <div id="slot5" class="slot-reel text-4xl sm:text-6xl">💎</div>
            </div>
        </div>
        <div class="flex flex-col items-center mb-4 bet-controls">
            <div class="flex justify-center w-full gap-2 mb-2">
                <button id="decrease-bet" class="px-4 py-2 bg-red-600 text-white rounded-full text-2xl font-bold hover:bg-red-700 transition w-14">-</button>
                <div id="bet-amount" class="text-3xl font-extrabold mx-2 bg-gradient-to-r from-yellow-400 to-yellow-600 text-gray-900 px-12 py-3 rounded-2xl shadow-lg w-40 text-center border-4 border-yellow-300">1000</div>
                <button id="increase-bet" class="px-4 py-2 bg-green-600 text-white rounded-full text-2xl font-bold hover:bg-green-700 transition w-14">+</button>
            </div>
            <div class="text-xs text-center mb-2">غيّر الرهان (كل كبسة ٥٠٠)</div>
            <button onclick="spinSlots()" id="spin-btn" class="bg-gradient-to-r from-purple-500 to-purple-700 text-white px-10 py-4 rounded-2xl hover:bg-purple-700 transition text-2xl font-extrabold shadow-lg hover:shadow-xl disabled:opacity-50 transform hover:scale-105 w-full sm:w-auto">
                لف العجلة (<span id="spin-cost">1000</span> نقطة)
            </button>
        </div>
        <div id="result" class="mt-2 text-xl font-semibold min-h-8 text-center text-white"></div>
        <div id="win-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
            <div class="absolute inset-0 bg-black bg-opacity-70"></div>
            <div class="relative glass-card p-6 sm:p-8 rounded-2xl text-center max-w-md w-11/12 animate__animated animate__zoomIn">
                <div id="win-icon" class="text-6xl sm:text-8xl mb-4">🎉</div>
                <h2 id="win-title" class="text-2xl sm:text-3xl font-bold mb-2">مبروك ربحت!</h2>
                <div id="win-amount" class="text-4xl sm:text-5xl font-bold text-yellow-400 mb-4">0</div>
                <p id="win-message" class="text-lg sm:text-xl mb-6">مبروك ربحت!</p>
                <button id="close-modal" class="bg-yellow-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-yellow-700 transition">
                    كمّل لعب
                </button>
            </div>
        </div>
        <div id="paytable" class="mt-6 sm:mt-8 bg-white p-4 sm:p-6 rounded-xl shadow-lg bg-opacity-90 max-w-2xl mx-auto">
            <h3 class="text-2xl font-bold mb-4 text-center text-purple-700">جدول الأرباح (الضرب)</h3>
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-purple-200">
                        <th class="text-left py-3 text-purple-700">الرمز</th>
                        <th class="text-center py-3 text-purple-700">٥ مرات</th>
                        <th class="text-center py-3 text-purple-700">٤ مرات</th>
                        <th class="text-center py-3 text-purple-700">٣ مرات</th>
                        <th class="text-center py-3 text-purple-700">مرتين</th>
                    </tr>
                </thead>
                <tbody id="payout-table-body">
                </tbody>
            </table>
            <div class="mt-4 text-sm text-gray-600 text-center">
                * كل ما تجمع رموز متتالية من أول خانة، بتربح حسب الجدول
            </div>
        </div>
    </div>

    <script>
        const userId = {{$id}};
        let isAllowToWinForTree = true;
        let credits = {{$balance}};
        let lastWin = 0;
        let isSpinning = false;
        let betAmount = 1000;
        const minBet = 1000;
        const maxBet = 10000;
        const betStep = 500;

        const symbols = ['🍒', '🍋', '🍇', '🍀', '💎'];
        const payoutMultipliers = {
            '💎': { match5: 10, match4: 5, match3: 3, match2: 2 },
            '🍀': { match5: 4, match4: 3, match3: 2, match2: 0.5 },
            '🍇': { match5: 4, match4: 3, match3: 2, match2: 0.5 },
            '🍒': { match5: 3, match4: 2, match3: 1, match2: 0.5 },
            '🍋': { match5: 3, match4: 2, match3: 1, match2: 0.5 },
        };
        
        async function getUserBalance() {
            try {
                const response = await fetch(`https://harrypotter.africaxbet.com/get_user_balance/${userId}`);
                const data = await response.json();
                credits = data.user.balance;
                isAllowToWinForTree = data.is_allow_to_win_for_tree;
                adjustBetToBalance();
                updateUI();
            } catch (error) {
                console.error('Error fetching user balance:', error);
            }
        }

        async function putUserBalance(winAmount, balanceBefore) {
            try {
                const response = await fetch(`https://harrypotter.africaxbet.com/put_user_balance`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: userId,
                        bet_amount: betAmount,
                        balance_before: balanceBefore,
                        current_balance: credits,
                        win_amount: winAmount,
                        game_name: 'tree'
                    })
                });
                if (!response.ok) throw new Error('Failed to update balance');
                const data = await response.json();
                if (typeof data.is_allow_to_win_for_tree !== 'undefined') {
                    isAllowToWinForTree = data.is_allow_to_win_for_tree;
                }
                return data;
            } catch (error) {
                console.error('Error updating user balance:', error);
            }
        }
        
        function formatNumber(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        
        function updateUI() {
            document.getElementById('credits').textContent = formatNumber(credits);
            document.getElementById('last-win').textContent = formatNumber(lastWin);
            document.getElementById('bet-amount').textContent = formatNumber(betAmount);
            document.getElementById('spin-cost').textContent = formatNumber(betAmount);
            document.getElementById('decrease-bet').disabled = betAmount <= minBet;
            document.getElementById('increase-bet').disabled = betAmount >= maxBet || credits < betAmount + betStep;
            document.getElementById('spin-btn').disabled = isSpinning || credits < betAmount;
        }
        
        function getRandomSymbol() {
            return symbols[Math.floor(Math.random() * symbols.length)];
        }
        
        function animateReel(reelElement, finalSymbol, spinDuration) {
            const startTime = Date.now();
            const endTime = startTime + spinDuration;
            
            function updateReel() {
                const currentTime = Date.now();
                const remainingTime = endTime - currentTime;
                
                if (remainingTime <= 0) {
                    reelElement.textContent = finalSymbol;
                    reelElement.classList.remove('spin');
                    return;
                }
                
                // Faster animation at start, slower toward the end
                const animationSpeed = Math.max(50, remainingTime / 20);
                
                reelElement.textContent = getRandomSymbol();
                setTimeout(updateReel, animationSpeed);
            }
            
            reelElement.classList.add('spin');
            updateReel();
        }
        
        function checkWins(reels) {
            let consecutiveMatches = 1;
            for (let i = 1; i < reels.length; i++) {
                if (reels[i] === reels[0]) {
                    consecutiveMatches++;
                } else {
                    break;
                }
            }
            let winAmount = 0;
            let winMessage = '';
            let winningSymbol = '';
            if (consecutiveMatches >= 2) {
                winningSymbol = reels[0];
                const payoutKey = `match${consecutiveMatches}`;
                if (payoutMultipliers[winningSymbol] && payoutMultipliers[winningSymbol][payoutKey]) {
                    winAmount = Math.floor(betAmount * payoutMultipliers[winningSymbol][payoutKey]);
                    winMessage = consecutiveMatches === 5 ? `🎉 جاكبت كبييير! ${consecutiveMatches}x ${winningSymbol}  ${formatNumber(winAmount)} نقطة` :
                                consecutiveMatches === 4 ? `🔥 ربح كبير! ${consecutiveMatches}x ${winningSymbol}  ${formatNumber(winAmount)} نقطة` :
                                consecutiveMatches === 3 ? `🥈 ربح حلو! ${consecutiveMatches}x ${winningSymbol}  ${formatNumber(winAmount)} نقطة` :
                                `🍀 ربح بسيط! ${consecutiveMatches}x ${winningSymbol}  ${formatNumber(winAmount)} نقطة`;
                }
            }
            // لا يوجد ربح إذا لم تكن الرموز المتتالية من البداية
            return { 
                amount: winAmount, 
                message: winMessage || '🙁 ما زبطت معك هالمرة، جرب كمان!',
                symbol: winningSymbol
            };
        }
        
        function renderPayoutTable() {
            const tbody = document.getElementById('payout-table-body');
            tbody.innerHTML = '';
            Object.keys(payoutMultipliers).forEach(symbol => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="py-3 text-3xl text-center">${symbol}</td>
                    <td class="text-center font-mono">${payoutMultipliers[symbol].match5}x</td>
                    <td class="text-center font-mono">${payoutMultipliers[symbol].match4}x</td>
                    <td class="text-center font-mono">${payoutMultipliers[symbol].match3}x</td>
                    <td class="text-center font-mono">${payoutMultipliers[symbol].match2}x</td>
                `;
                tbody.appendChild(row);
            });
        }
        
        function showWinModal(amount, message, type = 'normal') {
            let icon = '🎉';
            let title = 'مبروك ربحت!';
            let msgColor = 'text-white';
            if (type === 'jackpot') {
                icon = '🥳';
                title = 'جاكبت كبييير!';
                msgColor = 'text-white';
            } else if (type === 'big') {
                icon = '🎊';
                title = 'ربح كبير!';
                msgColor = 'text-white';
            } else if (type === 'small') {
                icon = '🥈';
                title = 'ربح بسيط!';
                msgColor = 'text-white';
            } else if (type === 'two') {
                icon = '🎉';
                title = 'ربحت مرتين!';
                msgColor = 'text-white';
            }
            document.getElementById('win-title').textContent = title;
            document.getElementById('win-amount').textContent = formatNumber(Math.abs(amount));
            document.getElementById('win-icon').textContent = icon;
            let cleanMessage = message;
            if (type === 'two') {
                cleanMessage = message.replace(/🍀\s*/, '');
            }
            document.getElementById('win-message').innerHTML = `<span class='${msgColor} font-bold text-lg sm:text-xl'>${cleanMessage.replace(/-\s*/, '')}</span>`;
            document.getElementById('win-modal').classList.remove('hidden');
        }
        
        function spinSlots() {
            if (isSpinning || credits < betAmount) return;
            isSpinning = true;
            credits -= betAmount;
            updateUI();
            const resultElement = document.getElementById('result');
            resultElement.textContent = 'Spinning...';
            resultElement.className = 'mt-2 text-xl font-semibold min-h-8 text-white';
            const finalSymbols = [
                getRandomSymbol(),
                getRandomSymbol(),
                getRandomSymbol(),
                getRandomSymbol(),
                getRandomSymbol()
            ];
            if (!isAllowToWinForTree) {
                let used = new Set();
                for (let i = 0; i < finalSymbols.length; i++) {
                    let symbol;
                    do {
                        symbol = getRandomSymbol();
                    } while (used.has(symbol));
                    finalSymbols[i] = symbol;
                    used.add(symbol);
                }
            }
            const reelElements = [
                document.getElementById('slot1'),
                document.getElementById('slot2'),
                document.getElementById('slot3'),
                document.getElementById('slot4'),
                document.getElementById('slot5')
            ];
            reelElements.forEach((reel, index) => {
                const spinDuration = 1000 + index * 300 + Math.random() * 500;
                setTimeout(() => {
                    animateReel(reel, finalSymbols[index], spinDuration);
                    if (index === reelElements.length - 1) {
                        setTimeout(async () => {
                            let winResult = checkWins(finalSymbols);
                            let winAmount = winResult.amount;
                            let winType = 'normal';
                            if (!isAllowToWinForTree) {
                                winAmount = 0;
                                winResult.message = '🙁 ما زبطت معك هالمرة، جرب كمان!';
                            } else if (winResult.message.includes('جاكبت كبييير')) {
                                winType = 'jackpot';
                            } else if (winResult.message.includes('ربح كبير')) {
                                winType = 'big';
                            } else if (winResult.message.includes('ربح بسيط')) {
                                winType = 'small';
                            } else if (winResult.message.includes('مرتين')) {
                                winType = 'two';
                            }
                            lastWin = winAmount;
                            credits += winAmount;
                            resultElement.textContent = winResult.message;
                            if (winAmount > 0) {
                                showWinModal(winAmount, winResult.message, winType);
                            }
                            await putUserBalance(winAmount, credits - winAmount);
                            isSpinning = false;
                            updateUI();
                        }, spinDuration + 200);
                    }
                }, index * 200);
            });
        }
        
        function adjustBetToBalance() {
            if (betAmount > credits) {
                let maxAllowed = Math.floor(credits / 500) * 500;
                if (maxAllowed < 1000) maxAllowed = 1000;
                betAmount = maxAllowed;
            }
        }
        
        document.getElementById('decrease-bet').addEventListener('click', () => {
            if (betAmount > minBet) {
                betAmount -= betStep;
                adjustBetToBalance();
                updateUI();
            }
        });
        document.getElementById('increase-bet').addEventListener('click', () => {
            if (betAmount < maxBet && credits >= betAmount + betStep) {
                betAmount += betStep;
                adjustBetToBalance();
                updateUI();
            }
        });
        document.getElementById('close-modal').addEventListener('click', () => {
            document.getElementById('win-modal').classList.add('hidden');
        });

        document.addEventListener('DOMContentLoaded', () => {
            getUserBalance();
            renderPayoutTable();
            updateUI();
        });
    </script>
</body>
</html>