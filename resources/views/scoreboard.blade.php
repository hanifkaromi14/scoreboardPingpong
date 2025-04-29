<!DOCTYPE html>
<html>
<head>
    <title>Scoreboard Online</title>
    <style>
        /* Gaya seperti sebelumnya, tidak ada perubahan */
        body {
            font-family: sans-serif;
            background: #f2f2f2;
            text-align: center;
            padding: 2rem;
        }

        .team-wrapper {
            display: flex;
            justify-content: center;
            gap: 4rem;
            padding-top: 2rem;
        }

        .team-container {
            border: 3px solid #ccc;
            border-radius: 10px;
            padding: 1rem;
            width: 280px;
            background: white;
        }

        .team-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1rem;
            border: none;
            background: transparent;
            text-align: center;
            width: 100%;
        }

        .score-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            position: relative;
            margin-bottom: 1rem;
        }

        .game {
            width: 100px;
            height: 100px;
            border-radius: 15px;
            font-size: 2.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .red .game {
            background-color: #ffcccc;
        }

        .blue .game {
            background-color: #b3d9ff;
        }

        .set {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .red .set {
            background-color: #ffe6e6;
        }

        .blue .set {
            background-color: #e6f2ff;
        }

        .controls {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        button {
            padding: 0.4rem 0.8rem;
            font-size: 1.2rem;
            cursor: pointer;
            border-radius: 5px;
            border: none;
        }

        .increment {
            background-color: #4caf50;
            color: white;
        }

        .decrement {
            background-color: #f44336;
            color: white;
        }

        .reset {
            background-color: #333;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <h1>Scoreboard Online</h1>

    <div style="margin-bottom: 1rem;">
        <label><input type="radio" name="bestOf" value="3" checked onchange="setBestOf(3)"> Best of 3</label>
        <label style="margin-left: 1rem;"><input type="radio" name="bestOf" value="5" onchange="setBestOf(5)"> Best of 5</label>
    </div>

    <div class="team-wrapper">
        <!-- Red Team -->
        <div class="team-container red">
            <input type="text" id="team-name-red" value="{{ $data['team_name']['red'] }}" onchange="saveTeamName()" class="team-name">
            <div class="score-box">
                <div class="controls">
                    <button class="increment" type="button" onclick="updateScore('red_add')">+</button>
                    <button class="decrement" type="button" onclick="updateScore('red_sub')">-</button>
                </div>
                <div class="game" id="red-game">{{ $data['red']['game'] }}</div>
                <div class="set" id="red-set">{{ $data['red']['set'] }}</div>
            </div>
            <label style="display: block; margin-top: 0.5rem;">
                <input type="checkbox" id="serve-red" onchange="toggleServe('red')">
                Serve
            </label>
        </div>

        <!-- Blue Team -->
        <div class="team-container blue">
            <input type="text" id="team-name-blue" value="{{ $data['team_name']['blue'] }}" onchange="saveTeamName()" class="team-name">
            <div class="score-box">
                <div class="set" id="blue-set">{{ $data['blue']['set'] }}</div>
                <div class="game" id="blue-game">{{ $data['blue']['game'] }}</div>
                <div class="controls">
                    <button class="increment" type="button" onclick="updateScore('blue_add')">+</button>
                    <button class="decrement" type="button" onclick="updateScore('blue_sub')">-</button>
                </div>
            </div>
            <label style="display: block; margin-top: 0.5rem;">
                <input type="checkbox" id="serve-blue" onchange="toggleServe('blue')">
                Serve
            </label>
        </div>
    </div>

    <form method="post" action="{{ route('score.ajax.reset') }}">
        @csrf
        <button class="reset" type="button" onclick="resetScore()">Reset</button>
        <button class="reset" type="button" onclick="viewHistory()">History Match</button>
    </form>

    <script>
        let redSetScores = [];
        let blueSetScores = [];

        let matchSetScores = [];

        let prevRedGame = 0;
        let prevBlueGame = 0;

        let bestOf = 3;
        let history = [];

        let lastSetCount = 0;

        function updateScore(action) {
            const team_name = {
                red: document.getElementById('team-name-red').value,
                blue: document.getElementById('team-name-blue').value
            };

            // Simpan skor sebelum update
            prevRedGame = parseInt(document.getElementById('red-game').innerText);
            prevBlueGame = parseInt(document.getElementById('blue-game').innerText);

            fetch("{{ route('score.ajax.update') }}", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({ action, team_name })
            })
            .then(res => res.json())
            .then(data => {
                const redGame = data.red.game;
                const blueGame = data.blue.game;
                const redSet = data.red.set;
                const blueSet = data.blue.set;

                document.getElementById('red-game').innerText = redGame;
                document.getElementById('blue-game').innerText = blueGame;
                document.getElementById('red-set').innerText = redSet;
                document.getElementById('blue-set').innerText = blueSet;

                // Jika baru reset dan set sudah naik, simpan skor sebelumnya
                if (data.last_set) {
                    const currentTotalSet = data.red.set + data.blue.set;

                    if (currentTotalSet > lastSetCount) {
                        matchSetScores.push({
                            red: data.last_set.red,
                            blue: data.last_set.blue
                        });
                        lastSetCount = currentTotalSet;
                    }
                }

                checkWinner(data);
            });
        }

        function resetScore() {
            fetch("{{ route('score.ajax.reset') }}", {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                }
            })
            .then(res => res.json())
            .then(() => {
                redSetScores = [];
                blueSetScores = [];
                location.reload();
            });

            matchSetScores = [];
        }

        function saveTeamName() {
            updateScore(null); // Kirim nama tim
        }

        function setBestOf(val) {
            bestOf = parseInt(val);
        }

        function checkWinner(data) {
            const redSet = data.red.set;
            const blueSet = data.blue.set;
            const redName = document.getElementById('team-name-red').value;
            const blueName = document.getElementById('team-name-blue').value;

            const winTarget = Math.ceil(bestOf / 2);
            let winner = null;

            if (redSet === winTarget) {
                winner = redName;
            } else if (blueSet === winTarget) {
                winner = blueName;
            }

            if (winner) {
                alert(`🏆 ${winner} wins the match!`);

                // Ambil history lama
                let history = JSON.parse(localStorage.getItem("matchHistory") || "[]");

                history.unshift({
                    red: redName,
                    blue: blueName,
                    winner: winner,
                    setScores: matchSetScores.map((set, i) => `Set ${i + 1}: ${set.red} - ${set.blue}`),
                    timestamp: new Date().toLocaleString()
                });

                if (history.length > 50) history.pop();

                localStorage.setItem("matchHistory", JSON.stringify(history));

                resetScore();
            }
        }

        function viewHistory() {
            const history = JSON.parse(localStorage.getItem('matchHistory') || '[]');
            let html = `
                <h2>History Match</h2>
                <ul style="list-style: none; padding: 0;">
            `;

            if (history.length === 0) {
                html += `<li>Belum ada match yang dimainkan.</li>`;
            } else {
                history.forEach((match, index) => {
                    html += `
                        <li>
                            ${index + 1}. ${match.red} vs ${match.blue} - <strong>Pemenang:</strong> ${match.winner}<br>
                            <strong>Skor Set:</strong><br>
                            ${match.setScores.join('<br>')}
                        </li>
                        <hr>
                    `;
                });
            }

            html += `
                </ul>
                <button onclick="clearHistory()" style="margin-top: 1rem; background: #f44336; color: white; padding: 0.5rem 1rem; border: none; border-radius: 5px;">Hapus History</button>
                <button onclick="location.reload()" style="margin-left: 1rem; background: #4caf50; color: white; padding: 0.5rem 1rem; border: none; border-radius: 5px;">Kembali ke Scoreboard</button>
            `;

            document.body.innerHTML = html;
        }

        function clearHistory() {
            if (confirm('Yakin ingin menghapus semua history match?')) {
                localStorage.removeItem('matchHistory');
                viewHistory();
            }
        }

        function toggleServe(team) {
            const redToggle = document.getElementById('serve-red');
            const blueToggle = document.getElementById('serve-blue');

            const redContainer = document.querySelector('.team-container.red');
            const blueContainer = document.querySelector('.team-container.blue');

            if (team === 'red' && redToggle.checked) {
                blueToggle.checked = false;
                redContainer.style.backgroundColor = '#ccff90'; // hijau stabilo
                blueContainer.style.backgroundColor = 'white';
            } else if (team === 'blue' && blueToggle.checked) {
                redToggle.checked = false;
                blueContainer.style.backgroundColor = '#ccff90';
                redContainer.style.backgroundColor = 'white';
            } else {
                redContainer.style.backgroundColor = 'white';
                blueContainer.style.backgroundColor = 'white';
            }
        }
    </script>
</body>
</html>
