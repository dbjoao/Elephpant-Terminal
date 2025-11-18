<?php
<?php
session_start();
require_once 'config.php';
require_once 'includes/trading212_portfolio.php';

// Initialize portfolio
$apiKey = TRADING212_API_KEY;
$portfolio = new Trading212Portfolio($apiKey);

// Fetch all positions
$allPositions = $portfolio->getAllPositions();
$totalValue = 0;
$totalPnL = 0;

if ($allPositions !== false && is_array($allPositions)) {
    foreach ($allPositions as $position) {
        $totalValue += ($position['quantity'] ?? 0) * ($position['currentPrice'] ?? 0);
        $totalPnL += ($position['ppl'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Overview - Trading212</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }

        .header p {
            color: #666;
            font-size: 1.1em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }

        .stat-value.positive {
            color: #10b981;
        }

        .stat-value.negative {
            color: #ef4444;
        }

        .search-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
        }

        .search-box {
            display: flex;
            gap: 10px;
        }

        .search-box input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1em;
            transition: border-color 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-box button {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1em;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .search-box button:hover {
            transform: scale(1.05);
        }

        .positions-table {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow-x: auto;
        }

        .positions-table h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 1px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            color: #333;
        }

        tbody tr {
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f9fafb;
        }

        .ticker {
            font-weight: bold;
            color: #667eea;
            font-size: 1.1em;
        }

        .positive {
            color: #10b981;
            font-weight: bold;
        }

        .negative {
            color: #ef4444;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge.profit {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.loss {
            background: #fee2e2;
            color: #991b1b;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ef4444;
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: white;
            font-size: 1.2em;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #666;
            font-size: 1.1em;
        }

        .refresh-btn {
            float: right;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background 0.3s ease;
        }

        .refresh-btn:hover {
            background: #5568d3;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .search-box {
                flex-direction: column;
            }

            table {
                font-size: 0.9em;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Portfolio Overview</h1>
            <p>Real-time view of your Trading212 positions</p>
        </div>

        <?php if ($allPositions === false): ?>
            <div class="error-message">
                ❌ <strong>Error:</strong> Unable to fetch portfolio data. Please check your API key and connection.
            </div>
        <?php else: ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Positions</div>
                    <div class="stat-value"><?php echo count($allPositions); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Portfolio Value</div>
                    <div class="stat-value">$<?php echo number_format($totalValue, 2); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total P&L</div>
                    <div class="stat-value <?php echo $totalPnL >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo $totalPnL >= 0 ? '+' : ''; ?>$<?php echo number_format($totalPnL, 2); ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">P&L Percentage</div>
                    <div class="stat-value <?php echo $totalPnL >= 0 ? 'positive' : 'negative'; ?>">
                        <?php 
                        $pnlPercent = $totalValue > 0 ? ($totalPnL / ($totalValue - $totalPnL)) * 100 : 0;
                        echo $pnlPercent >= 0 ? '+' : '';
                        echo number_format($pnlPercent, 2); 
                        ?>%
                    </div>
                </div>
            </div>

            <div class="search-section">
                <form method="GET" action="" class="search-box">
                    <input type="text" name="search_ticker" placeholder="Search by ticker (e.g., AAPL, TSLA)" value="<?php echo htmlspecialchars($_GET['search_ticker'] ?? ''); ?>">
                    <button type="submit">🔍 Search</button>
                    <?php if (isset($_GET['search_ticker'])): ?>
                        <a href="portfolio_view.php" style="padding: 12px 20px; background: #6b7280; color: white; text-decoration: none; border-radius: 10px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="positions-table">
                <h2>
                    Open Positions
                    <button class="refresh-btn" onclick="location.reload()">🔄 Refresh</button>
                </h2>

                <?php
                $displayPositions = $allPositions;
                
                // Handle search
                if (isset($_GET['search_ticker']) && !empty($_GET['search_ticker'])) {
                    $searchTicker = strtoupper(trim($_GET['search_ticker']));
                    $displayPositions = array_filter($allPositions, function($pos) use ($searchTicker) {
                        return strpos(strtoupper($pos['ticker'] ?? ''), $searchTicker) !== false;
                    });
                }

                if (empty($displayPositions)): ?>
                    <div class="no-data">
                        📭 No positions found. <?php echo isset($_GET['search_ticker']) ? 'Try a different search term.' : 'Start investing to see your portfolio here!'; ?>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Ticker</th>
                                <th>Quantity</th>
                                <th>Avg Price</th>
                                <th>Current Price</th>
                                <th>Market Value</th>
                                <th>P&L</th>
                                <th>P&L %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($displayPositions as $position): 
                                $formatted = $portfolio->formatPosition($position);
                                $marketValue = $formatted['quantity'] * $formatted['currentPrice'];
                                $pnlPercent = $formatted['averagePrice'] > 0 ? 
                                    (($formatted['currentPrice'] - $formatted['averagePrice']) / $formatted['averagePrice']) * 100 : 0;
                            ?>
                                <tr>
                                    <td class="ticker"><?php echo htmlspecialchars($formatted['ticker']); ?></td>
                                    <td><?php echo number_format($formatted['quantity'], 4); ?></td>
                                    <td>$<?php echo number_format($formatted['averagePrice'], 2); ?></td>
                                    <td>$<?php echo number_format($formatted['currentPrice'], 2); ?></td>
                                    <td><strong>$<?php echo number_format($marketValue, 2); ?></strong></td>
                                    <td class="<?php echo $formatted['ppl'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo $formatted['ppl'] >= 0 ? '+' : ''; ?>$<?php echo number_format($formatted['ppl'], 2); ?>
                                    </td>
                                    <td class="<?php echo $pnlPercent >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo $pnlPercent >= 0 ? '+' : ''; ?><?php echo number_format($pnlPercent, 2); ?>%
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $formatted['ppl'] >= 0 ? 'profit' : 'loss'; ?>">
                                            <?php echo $formatted['ppl'] >= 0 ? '📈 Profit' : '📉 Loss'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>