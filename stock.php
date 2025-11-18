<?php
require_once(__DIR__ . '/stock_backend.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Stock Analysis - <?php echo htmlspecialchars(string: $symbol); ?></title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0a0e17;
            color: #e0e6ed;
            line-height: 1.4;
            padding: 0;
            margin: 0;
        }
        
        .terminal-header {
            background: linear-gradient(180deg, #1a1f2e 0%, #141821 100%);
            border-bottom: 2px solid #ff7a00;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
        }
        
        .terminal-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .terminal-logo img {
            height: 32px;
            width: auto;
        }

        .terminal-logo span {
            color: #ff7a00;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        .terminal-nav {
            display: flex;
            gap: 6px;
        }
        
        .terminal-nav a {
            color: #b0b8c4;
            text-decoration: none;
            padding: 5px 10px;
            background: rgba(255,122,0,0.1);
            border: 1px solid rgba(255,122,0,0.3);
            border-radius: 3px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.2s;
        }
        
        .terminal-nav a:hover {
            background: rgba(255,122,0,0.2);
            border-color: #ff7a00;
            color: #ff7a00;
        }
        
        .container {
            max-width: 1920px;
            margin: 0 auto;
            padding: 12px;
        }
        
        .ticker-banner {
            background: linear-gradient(135deg, #1a1f2e 0%, #252b3d 100%);
            border: 1px solid #2d3548;
            border-left: 3px solid #ff7a00;
            padding: 12px 16px;
            margin-bottom: 12px;
            border-radius: 3px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .ticker-banner h1 {
            color: #ff7a00;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 0;
            letter-spacing: 1px;
        }
        
        .countdown-banner {
            background: linear-gradient(135deg, #1a2332 0%, #0f1620 100%);
            border: 1px solid #2d3548;
            border-left: 3px solid #00d4aa;
            padding: 0px 14px 0px 14px;
            margin-bottom: 12px;
            border-radius: 3px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .countdown-banner h2 {
            color: #00d4aa;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
        }
        
        .countdown-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .countdown-details {
            flex: 1;
        }
        
        .countdown-timer {
            display: flex;
            gap: 8px;
        }
        
        .time-unit {
            text-align: center;
            background: rgba(0,212,170,0.1);
            border: 1px solid rgba(0,212,170,0.3);
            padding: 8px 12px;
            border-radius: 3px;
            min-width: 60px;
        }
        
        .time-value {
            display: block;
            font-size: 24px;
            font-weight: 700;
            line-height: 1;
            color: #00d4aa;
            font-family: 'Courier New', monospace;
        }
        
        .time-label {
            display: block;
            font-size: 9px;
            margin-top: 4px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .earnings-details {
            font-size: 11px;
            color: #9ca3af;
        }
        
        .earnings-details p {
            margin: 4px 0;
            display: flex;
            align-items: center;
        }
        
        .earnings-details strong {
            color: #d1d5db;
            min-width: 55px;
            font-weight: 600;
        }
        
        .time-badge {
            display: inline-block;
            padding: 3px 8px;
            background: rgba(255,122,0,0.2);
            border: 1px solid rgba(255,122,0,0.4);
            border-radius: 2px;
            font-size: 9px;
            font-weight: 700;
            margin-left: 6px;
            color: #ff7a00;
            letter-spacing: 0.5px;
        }
        
        .info-blocks {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 12px;
        }
        
        .news-block, .grades-block {
            grid-column: 1 / -1;
        }
        
        .insider-blocks {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            grid-column: 1 / -1;
        }
        
        @media (max-width: 1600px) {
            .info-blocks {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 1200px) {
            .info-blocks {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 900px) {
            .info-blocks {
                grid-template-columns: 1fr;
            }
            .insider-blocks {
                grid-template-columns: 1fr;
            }
            .countdown-timer {
                flex-wrap: wrap;
            }
            .terminal-nav {
                flex-wrap: wrap;
            }
        }
        
        .info-block {
            background: linear-gradient(135deg, #1a1f2e 0%, #141821 100%);
            border: 1px solid #2d3548;
            border-radius: 3px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            height: 500px;
        }
        
        .info-block h2 {
            background: #0d1117;
            color: #ff7a00;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 10px 14px;
            margin: 0;
            border-bottom: 1px solid #2d3548;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .info-block h2::before {
            content: "▸";
            color: #ff7a00;
            font-size: 14px;
        }
        
        .info-block-content {
            flex: 1;
            overflow-y: auto;
            padding: 12px;
        }
        
        .earnings-count {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        table { 
            border-collapse: collapse; 
            width: 100%; 
            font-size: 10px;
        }
        
        th, td { 
            padding: 6px 6px;
            text-align: left;
            border-bottom: 1px solid #2d3548;
        }
        
        th { 
            background: #0d1117;
            color: #9ca3af;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: 0.3px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        tbody tr {
            transition: background 0.2s;
        }
        
        tbody tr:hover { 
            background: rgba(255,122,0,0.05);
        }
        
        .strong-buy { color: #00ff88; font-weight: 600; }
        .buy { color: #00d4aa; }
        .hold { color: #fbbf24; }
        .sell { color: #f87171; }
        .strong-sell { color: #ef4444; font-weight: 600; }
        
        .positive { color: #00ff88; font-weight: 600; }
        .negative { color: #ef4444; font-weight: 600; }
        
        .no-data {
            text-align: center;
            padding: 30px 16px;
            color: #6b7280;
            font-style: italic;
            font-size: 11px;
        }
        
        .earnings-date {
            font-weight: 600;
            color: #d1d5db;
            font-family: 'Courier New', monospace;
        }
        
        .hour-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 2px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        
        .hour-bmo { background: rgba(59,130,246,0.2); color: #60a5fa; border: 1px solid rgba(59,130,246,0.4); }
        .hour-amc { background: rgba(249,115,22,0.2); color: #fb923c; border: 1px solid rgba(249,115,22,0.4); }
        .hour-dmh { background: rgba(168,85,247,0.2); color: #c084fc; border: 1px solid rgba(168,85,247,0.4); }
        
        .earnings-scroll, .news-scroll, .grades-scroll {
            overflow-y: auto;
        }
        
        .earnings-scroll::-webkit-scrollbar,
        .news-scroll::-webkit-scrollbar,
        .grades-scroll::-webkit-scrollbar,
        .info-block-content::-webkit-scrollbar {
            width: 4px;
        }
        
        .earnings-scroll::-webkit-scrollbar-track,
        .news-scroll::-webkit-scrollbar-track,
        .grades-scroll::-webkit-scrollbar-track,
        .info-block-content::-webkit-scrollbar-track {
            background: #0d1117;
        }
        
        .earnings-scroll::-webkit-scrollbar-thumb,
        .news-scroll::-webkit-scrollbar-thumb,
        .grades-scroll::-webkit-scrollbar-thumb,
        .info-block-content::-webkit-scrollbar-thumb {
            background: #ff7a00;
            border-radius: 2px;
        }
        
        .transaction-buy {
            background-color: rgba(0,255,136,0.05) !important;
            border-left: 2px solid #00ff88;
        }
        
        .transaction-sell {
            background-color: rgba(239,68,68,0.05) !important;
            border-left: 2px solid #ef4444;
        }
        
        .transaction-code {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 2px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        
        .code-s { background: #ef4444; color: white; }
        .code-p { background: #00ff88; color: #0a0e17; }
        .code-m { background: #3b82f6; color: white; }
        .code-a { background: #fbbf24; color: #0a0e17; }
        
        .mspr-positive {
            background-color: rgba(0,255,136,0.05) !important;
            border-left: 2px solid #00ff88;
        }
        
        .mspr-negative {
            background-color: rgba(239,68,68,0.05) !important;
            border-left: 2px solid #ef4444;
        }
        
        .grade-badge {
            display: inline-block;
            padding: 3px 7px;
            border-radius: 2px;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        
        .grade-buy { background: #00ff88; color: #0a0e17; }
        .grade-overweight { background: #00d4aa; color: #0a0e17; }
        .grade-outperform { background: #3b82f6; color: white; }
        .grade-hold { background: #fbbf24; color: #0a0e17; }
        .grade-neutral { background: #6b7280; color: white; }
        .grade-underweight { background: #f97316; color: white; }
        .grade-underperform { background: #ef4444; color: white; }
        .grade-sell { background: #991b1b; color: white; }
        
        .action-upgrade { background-color: rgba(0,255,136,0.05) !important; border-left: 2px solid #00ff88; }
        .action-downgrade { background-color: rgba(239,68,68,0.05) !important; border-left: 2px solid #ef4444; }
        .action-init { background-color: rgba(59,130,246,0.05) !important; border-left: 2px solid #3b82f6; }
        .action-reiterated,
        .action-maintain { background-color: rgba(251,191,36,0.05) !important; border-left: 2px solid #fbbf24; }
        
        .rank-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 3px;
            font-size: 20px;
            font-weight: 700;
            margin: 8px 0;
            letter-spacing: 0.5px;
            font-family: 'Courier New', monospace;
        }
        
        .rank-1 { background: #00ff88; color: #0a0e17; }
        .rank-2 { background: #00d4aa; color: #0a0e17; }
        .rank-3 { background: #fbbf24; color: #0a0e17; }
        .rank-4 { background: #f97316; color: white; }
        .rank-5 { background: #ef4444; color: white; }
        
        .score-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 2px;
            font-weight: 700;
            margin: 4px 4px 4px 0;
            font-size: 9px;
            letter-spacing: 0.3px;
        }
        
        .score-a { background: #00ff88; color: #0a0e17; }
        .score-b { background: #84cc16; color: #0a0e17; }
        .score-c { background: #fbbf24; color: #0a0e17; }
        .score-d { background: #f97316; color: white; }
        .score-f { background: #ef4444; color: white; }
        
        .data-table {
            width: 100%;
            margin-top: 8px;
        }
        
        .data-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #2d3548;
            font-size: 10px;
        }
        
        .data-row:last-child {
            border-bottom: none;
        }
        .news-item {
            border-bottom: 1px solid #2d3548;
            padding: 12px 0;
            display: flex;
            gap: 12px;
        }
        
        .news-item:last-child {
            border-bottom: none;
        }
        
        .news-item:hover {
            background: rgba(255,122,0,0.03);
            margin: 0 -12px;
            padding: 12px;
        }
        
        .news-image {
            flex-shrink: 0;
        }
        
        .news-image img {
            width: 100px;
            height: 60px;
            object-fit: cover;
            border-radius: 2px;
            border: 1px solid #2d3548;
        }
        
        .news-content {
            flex: 1;
        }
        
        .news-headline {
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .news-headline a {
            color: #d1d5db;
            text-decoration: none;
            font-size: 11px;
            line-height: 1.3;
        }
        
        .news-headline a:hover {
            color: #ff7a00;
        }
        
        .news-meta {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 6px;
        }
        
        .news-source {
            font-weight: 700;
            color: #00d4aa;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .news-summary {
            font-size: 10px;
            color: #9ca3af;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        h3 {
            color: #ff7a00;
            font-size: 11px;
            font-weight: 600;
            margin: 12px 0 8px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #2d3548;
            padding-bottom: 6px;
        }
        
        .info-block.news-block,
        .info-block.grades-block {
            height: auto;
            max-height: 500px;
        }
    </style>
</head>
<body>
    <div class="terminal-header">
      <a href="index.php" style="text-decoration: none;">
        <div class="terminal-logo">
          <img src="logo.png" alt="Minerva Logo">
          <span>MarketRat</span>
        </div>
      </a>
        <div class="terminal-nav">
            <a href="earnings.php">Earnings</a>
            <a href="portfolio.php" class="active">Portfolio</a>
            <a href="https://www.google.com/finance/quote/<?php echo htmlspecialchars($symbol); ?>:NYSE?window=5D" target="_blank">GOOGLE</a>
            <a href="https://finance.yahoo.com/quote/<?php echo htmlspecialchars($symbol); ?>/analyst-insights/#upgrade-downgrade-table" target="_blank">YAHOO</a>
            <a href="https://www.zacks.com/stock/quote/<?php echo urlencode($symbol); ?>">ZACKS</a>
            <form method="GET" action="stock.php" style="display: inline-flex; gap: 4px; margin: 0;">
                <input type="text" name="symbol" placeholder="Search ticker..." required 
                    style="padding: 5px 10px; background: #0d1117; border: 1px solid rgba(255,122,0,0.3); 
                            border-radius: 3px; color: #e0e6ed; font-size: 11px; font-family: 'Inter', sans-serif; 
                            outline: none; text-transform: uppercase;" 
                    onfocus="this.style.borderColor='#ff7a00'" 
                    onblur="this.style.borderColor='rgba(255,122,0,0.3)'">
                <button type="submit" style="padding: 5px 10px; background: rgba(255,122,0,0.1); 
                                            border: 1px solid rgba(255,122,0,0.3); border-radius: 3px; 
                                            color: #b0b8c4; font-size: 11px; font-weight: 500; 
                                            text-transform: uppercase; letter-spacing: 0.5px; cursor: pointer; 
                                            transition: all 0.2s;" 
                        onmouseover="this.style.background='rgba(255,122,0,0.2)'; this.style.borderColor='#ff7a00'; this.style.color='#ff7a00'" 
                        onmouseout="this.style.background='rgba(255,122,0,0.1)'; this.style.borderColor='rgba(255,122,0,0.3)'; this.style.color='#b0b8c4'">
                Search
                </button>
            </form>
        </div>
    </div>
    
    <div class="container">
        
        <?php if ($nextEarnings): 
            $earningsDate = $nextEarnings['date'];
            $earningsHour = $nextEarnings['hour'] ?? 'amc';
            
            if ($earningsHour === 'bmo') {
                $earningsTime = $earningsDate . ' 09:00:00';
            } elseif ($earningsHour === 'amc') {
                $earningsTime = $earningsDate . ' 16:00:00';
            } else {
                $earningsTime = $earningsDate . ' 12:00:00';
            }
            
            $earningsDateTime = new DateTime($earningsTime, new DateTimeZone('America/New_York'));
            $earningsTimestamp = $earningsDateTime->getTimestamp();
        ?>
        <div class="countdown-banner">
            <div class="countdown-info">
                <div class="countdown-details">
                    <h2>NEXT EARNINGS ANNOUNCEMENT</h2>
                    <div class="earnings-details">
                        <p><strong>DATE:</strong> <?php echo $earningsDateTime->format('l, F j, Y'); ?></p>
                        <p><strong>TIME:</strong> 
                            <?php 
                            if ($earningsHour === 'bmo') echo 'Before Market Open (9:00 AM EST)';
                            elseif ($earningsHour === 'amc') echo 'After Market Close (4:00 PM EST)';
                            else echo 'During Market Hours (12:00 PM EST)';
                            ?>
                            <span class="time-badge"><?php echo strtoupper($earningsHour); ?></span>
                        </p>
                        <p><strong>QUARTER:</strong> Q<?php echo $nextEarnings['quarter']; ?> <?php echo $nextEarnings['year']; ?></p>
                    </div>
                </div>
                <div class="countdown-details">
                            <!-- TradingView Widget BEGIN -->
                    <div class="tradingview-widget-container" style="padding: 15px;">
                    <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-single-quote.js" async>
                    {
                    "symbol": "<?php echo htmlspecialchars($symbol); ?>",
                    "colorTheme": "dark",
                    "isTransparent": true,
                    "locale": "en",
                    "width": 350
                    }
                    </script>
                    </div>
                    <!-- TradingView Widget END -->
                </div>
                <div class="countdown-timer" id="countdown">
                    <div class="time-unit">
                        <span class="time-value" id="days">--</span>
                        <span class="time-label">Days</span>
                    </div>
                    <div class="time-unit">
                        <span class="time-value" id="hours">--</span>
                        <span class="time-label">Hours</span>
                    </div>
                    <div class="time-unit">
                        <span class="time-value" id="minutes">--</span>
                        <span class="time-label">Minutes</span>
                    </div>
                    <div class="time-unit">
                        <span class="time-value" id="seconds">--</span>
                        <span class="time-label">Seconds</span>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        const targetDate = <?php echo $earningsTimestamp * 1000; ?>;
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = targetDate - now;
            
            if (distance < 0) {
                document.getElementById('days').textContent = '00';
                document.getElementById('hours').textContent = '00';
                document.getElementById('minutes').textContent = '00';
                document.getElementById('seconds').textContent = '00';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.getElementById('days').textContent = String(days).padStart(2, '0');
            document.getElementById('hours').textContent = String(hours).padStart(2, '0');
            document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
            document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        </script>
        <?php endif; ?>
        
        <div class="info-blocks">

            <div class="info-block">
                                <!-- TradingView Widget BEGIN -->
                <div class="tradingview-widget-container" style="height:100%;width:100%">
                <div class="tradingview-widget-container__widget" style="height:calc(100% - 32px);width:100%"></div>
                <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js" async>
                {
                "allow_symbol_change": true,
                "calendar": false,
                "details": false,
                "hide_side_toolbar": true,
                "hide_top_toolbar": false,
                "hide_legend": false,
                "hide_volume": false,
                "hotlist": false,
                "interval": "D",
                "locale": "en",
                "save_image": false,
                "style": "1",
                "symbol": "<?php echo htmlspecialchars($symbol); ?>",
                "theme": "dark",
                "timezone": "Europe/Lisbon",
                "backgroundColor": "rgba(13, 17, 23, 1)",
                "gridColor": "rgba(242, 242, 242, 0.06)",
                "watchlist": [],
                "withdateranges": false,
                "compareSymbols": [
                    {
                    "symbol": "AMEX:VOO",
                    "position": "SameScale"
                    }
                ],
                "show_popup_button": true,
                "popup_height": "650",
                "popup_width": "1000",
                "studies": [],
                "autosize": true
                }
                </script>
                </div>
                <!-- TradingView Widget END -->

            </div>
            <!-- Combined Zacks Analysis Block -->
            <?php if ($zacksData['zacksRank'] || !empty($zacksData['stockActivity']) || !empty($zacksData['earningsData'])): ?>
            <div class="info-block">
                <h2>Zacks Analysis</h2>
                <div class="info-block-content">
                    <?php if ($zacksData['zacksRank']): ?>
                        <div>
                            <span class="rank-badge rank-<?php echo $zacksData['zacksRank']; ?>">
                                #<?php echo $zacksData['zacksRank']; ?> - <?php echo htmlspecialchars($zacksData['zacksRankText']); ?>
                            </span>
                            <?php if (!empty($zacksData['styleScores'])): ?>
                            <div>
                                <strong style="color: #9ca3af; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px;">Style Scores:</strong><br>
                                <?php foreach ($zacksData['styleScores'] as $style => $score): ?>
                                    <span class="score-badge score-<?php echo strtolower($score); ?>">
                                        <?php echo htmlspecialchars($style); ?>: <?php echo htmlspecialchars($score); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($zacksData['earningsData'])): ?>
                        <div style="margin-top: 12px;">
                            <h3>Earnings Data</h3>
                            <div class="data-table">
                                <?php foreach ($zacksData['earningsData'] as $label => $value): ?>
                                <div class="data-row">
                                    <strong class="data-label"><?php echo htmlspecialchars($label); ?></strong>
                                    <strong class="data-value"><?php echo htmlspecialchars($value); ?></strong>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($zacksData['stockActivity'])): ?>
                        <div style="margin-top: 12px;">
                            <h3>Stock Activity</h3>
                            <div class="data-table">
                                <?php foreach ($zacksData['stockActivity'] as $label => $value): ?>
                                <div class="data-row">
                                    <span class="data-label"><?php echo htmlspecialchars($label); ?></span>
                                    <span class="data-value"><?php echo htmlspecialchars($value); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    

                </div>
            </div>
            <?php endif; ?>
            
            <!-- Historical Grades Block -->
            <?php if (!empty($historicalGrades)): ?>
            <div class="info-block">
                <h2>Historical Analyst Grades</h2>
                <div class="info-block-content">
                    <div class="earnings-count">Showing <?php echo count($historicalGrades); ?> historical record(s)</div>
                    <div class="earnings-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th class="strong-buy">S.Buy</th>
                                    <th class="buy">Buy</th>
                                    <th class="hold">Hold</th>
                                    <th class="sell">Sell</th>
                                    <th class="strong-sell">S.Sell</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historicalGrades as $grade): 
                                    $total = ($grade['analystRatingsStrongBuy'] ?? 0) + 
                                             ($grade['analystRatingsBuy'] ?? 0) + 
                                             ($grade['analystRatingsHold'] ?? 0) + 
                                             ($grade['analystRatingsSell'] ?? 0) + 
                                             ($grade['analystRatingsStrongSell'] ?? 0);
                                ?>
                                <tr>
                                    <td class="earnings-date"><?php echo date('M Y', strtotime($grade['date'])); ?></td>
                                    <td class="strong-buy"><?php echo $grade['analystRatingsStrongBuy'] ?? 0; ?></td>
                                    <td class="buy"><?php echo $grade['analystRatingsBuy'] ?? 0; ?></td>
                                    <td class="hold"><?php echo $grade['analystRatingsHold'] ?? 0; ?></td>
                                    <td class="sell"><?php echo $grade['analystRatingsSell'] ?? 0; ?></td>
                                    <td class="strong-sell"><?php echo $grade['analystRatingsStrongSell'] ?? 0; ?></td>
                                    <td><strong><?php echo $total; ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="info-block">
                <!-- Recommendation Trends Block -->
                <?php if (!empty($recommendations)): ?>
                <div class="info-block" style="height: 350px;">
                    <h2>Analyst Recommendations</h2>
                    <div class="info-block-content">
                        <table>
                            <thead>
                                <tr>
                                    <th>Period</th>
                                    <th class="strong-buy">S.Buy</th>
                                    <th class="buy">Buy</th>
                                    <th class="hold">Hold</th>
                                    <th class="sell">Sell</th>
                                    <th class="strong-sell">S.Sell</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recommendations as $rec): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($rec['period']); ?></strong></td>
                                    <td class="strong-buy"><?php echo (int)$rec['strongBuy']; ?></td>
                                    <td class="buy"><?php echo (int)$rec['buy']; ?></td>
                                    <td class="hold"><?php echo (int)$rec['hold']; ?></td>
                                    <td class="sell"><?php echo (int)$rec['sell']; ?></td>
                                    <td class="strong-sell"><?php echo (int)$rec['strongSell']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="info-block">

                    <!-- Earnings Calendar Block -->
                    <?php if (!empty($earnings)): ?>
                    <div class="info-block">
                        <h2>Earnings History</h2>
                        <div class="info-block-content">
                            <div class="earnings-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Quarter</th>
                                            <th>EPS Act</th>
                                            <th>EPS Est</th>
                                            <th>Rev Act</th>
                                            <th>Rev Est</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($earnings as $earning): 
                                            $epsActual = $earning['epsActual'] ?? null;
                                            $epsEstimate = $earning['epsEstimate'] ?? null;
                                            $epsSurprise = ($epsActual !== null && $epsEstimate !== null) ? ($epsActual - $epsEstimate) : null;
                                            
                                            $revenueActual = $earning['revenueActual'] ?? null;
                                            $revenueEstimate = $earning['revenueEstimate'] ?? null;
                                            $revenueSurprise = ($revenueActual !== null && $revenueEstimate !== null) ? ($revenueActual - $revenueEstimate) : null;
                                            
                                            $hourClass = 'hour-dmh';
                                            $hourText = 'DMH';
                                            if ($earning['hour'] === 'bmo') {
                                                $hourClass = 'hour-bmo';
                                                $hourText = 'BMO';
                                            } elseif ($earning['hour'] === 'amc') {
                                                $hourClass = 'hour-amc';
                                                $hourText = 'AMC';
                                            }
                                        ?>
                                        <tr>
                                            <td class="earnings-date"><?php echo htmlspecialchars($earning['date']); ?></td>
                                            <td><span class="hour-badge <?php echo $hourClass; ?>"><?php echo $hourText; ?></span></td>
                                            <td>Q<?php echo $earning['quarter']; ?> <?php echo $earning['year']; ?></td>
                                            <td class="<?php echo ($epsSurprise !== null && $epsSurprise > 0) ? 'positive' : (($epsSurprise !== null && $epsSurprise < 0) ? 'negative' : ''); ?>">
                                                <?php echo $epsActual !== null ? number_format($epsActual, 2) : 'N/A'; ?>
                                            </td>
                                            <td><?php echo $epsEstimate !== null ? number_format($epsEstimate, 2) : 'N/A'; ?></td>
                                            <td class="<?php echo ($revenueSurprise !== null && $revenueSurprise > 0) ? 'positive' : (($revenueSurprise !== null && $revenueSurprise < 0) ? 'negative' : ''); ?>">
                                                <?php echo $revenueActual !== null ? '$' . number_format($revenueActual / 1000000, 0) . 'M' : 'N/A'; ?>
                                            </td>
                                            <td><?php echo $revenueEstimate !== null ? '$' . number_format($revenueEstimate / 1000000, 0) . 'M' : 'N/A'; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Earnings Surprises Block -->
                    <?php if (!empty($earningsSurprises)): ?>
                    <div class="info-block">
                        <h2>Earnings Surprises</h2>
                        <div class="info-block-content">
                            <div class="earnings-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Period</th>
                                            <th>Quarter</th>
                                            <th>Actual</th>
                                            <th>Estimate</th>
                                            <th>Surprise</th>
                                            <th>Surprise %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($earningsSurprises as $surprise): 
                                            $surpriseValue = $surprise['surprise'] ?? 0;
                                            $surprisePercent = $surprise['surprisePercent'] ?? 0;
                                        ?>
                                        <tr>
                                            <td class="earnings-date"><?php echo htmlspecialchars($surprise['period']); ?></td>
                                            <td>Q<?php echo $surprise['quarter']; ?> <?php echo $surprise['year']; ?></td>
                                            <td class="<?php echo $surpriseValue > 0 ? 'positive' : ($surpriseValue < 0 ? 'negative' : ''); ?>">
                                                <?php echo number_format($surprise['actual'], 2); ?>
                                            </td>
                                            <td><?php echo number_format($surprise['estimate'], 4); ?></td>
                                            <td class="<?php echo $surpriseValue > 0 ? 'positive' : ($surpriseValue < 0 ? 'negative' : ''); ?>">
                                                <?php echo $surpriseValue > 0 ? '+' : ''; ?><?php echo number_format($surpriseValue, 4); ?>
                                            </td>
                                            <td class="<?php echo $surprisePercent > 0 ? 'positive' : ($surprisePercent < 0 ? 'negative' : ''); ?>">
                                                <?php echo $surprisePercent > 0 ? '+' : ''; ?><?php echo number_format($surprisePercent, 2); ?>%
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
                <!--Benzinga Stock Grades Block -->
            <?php if (!empty($benzingaTables)): ?>
            <div class="info-block grades-block">
                <h2>Benzinga Analyst Ratings</h2>
                <div class="info-block-content">
                    <div class="grades-scroll">
                        <?php foreach ($benzingaTables as $index => $table): ?>
                            <table>
                                <?php if (!empty($table[0])): ?>
                                <thead>
                                    <tr>
                                        <?php foreach ($table[0] as $header): ?>
                                        <th><?php echo htmlspecialchars($header); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <?php endif; ?>
                                <tbody>
                                    <?php for ($i = 1; $i < count($table); $i++): ?>
                                    <tr>
                                        <?php foreach ($table[$i] as $cell): ?>
                                        <td><?php echo htmlspecialchars($cell); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <!-- Stock Grades Block (Financial Modeling Prep) -->
            <?php if (!empty($stockGrades)): ?>
            <div class="info-block grades-block">
                <h2>Analyst Grades & Ratings</h2>
                <div class="info-block-content">
                    <div class="grades-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Company</th>
                                    <th>Previous Grade</th>
                                    <th>New Grade</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                function getGradeClass($grade) {
                                    $grade = strtolower($grade);
                                    if (strpos($grade, 'buy') !== false) return 'grade-buy';
                                    if (strpos($grade, 'overweight') !== false) return 'grade-overweight';
                                    if (strpos($grade, 'outperform') !== false) return 'grade-outperform';
                                    if (strpos($grade, 'hold') !== false || strpos($grade, 'equal') !== false) return 'grade-hold';
                                    if (strpos($grade, 'neutral') !== false || strpos($grade, 'perform') !== false) return 'grade-neutral';
                                    if (strpos($grade, 'underweight') !== false) return 'grade-underweight';
                                    if (strpos($grade, 'underperform') !== false) return 'grade-underperform';
                                    if (strpos($grade, 'sell') !== false) return 'grade-sell';
                                    return 'grade-neutral';
                                }
                                
                                function getActionClass($action) {
                                    $action = strtolower($action);
                                    if (strpos($action, 'upgrade') !== false || strpos($action, 'up') !== false) return 'action-upgrade';
                                    if (strpos($action, 'downgrade') !== false || strpos($action, 'down') !== false) return 'action-downgrade';
                                    if (strpos($action, 'init') !== false || strpos($action, 'new') !== false) return 'action-init';
                                    return 'action-maintain';
                                }
                                
                                foreach ($stockGrades as $grade): 
                                    $action = $grade['action'] ?? 'maintain';
                                    $rowClass = getActionClass($action);
                                    $previousGrade = $grade['previousGrade'] ?? 'N/A';
                                    $newGrade = $grade['newGrade'] ?? 'N/A';
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td class="earnings-date"><?php echo htmlspecialchars($grade['date']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($grade['gradingCompany']); ?></strong></td>
                                    <td>
                                        <?php if ($previousGrade !== 'N/A'): ?>
                                        <span class="grade-badge <?php echo getGradeClass($previousGrade); ?>">
                                            <?php echo htmlspecialchars($previousGrade); ?>
                                        </span>
                                        <?php else: ?>
                                        <span style="color: #6b7280;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="grade-badge <?php echo getGradeClass($newGrade); ?>">
                                            <?php echo htmlspecialchars($newGrade); ?>
                                        </span>
                                    </td>
                                    <td><strong><?php echo ucfirst(htmlspecialchars($action)); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            

            
            <!-- Company News Block -->
            <?php if (!empty($companyNews)): ?>
            <div class="info-block news-block">
                <h2>Company News</h2>
                <div class="info-block-content">
                    <div class="earnings-count">Showing <?php echo count($companyNews); ?> news article(s)</div>
                    <div class="news-scroll">
                        <?php foreach ($companyNews as $news): ?>
                        <div class="news-item">
                            <?php if (!empty($news['image'])): ?>
                            <div class="news-image">
                                <img src="<?php echo htmlspecialchars($news['image']); ?>" alt="News thumbnail" onerror="this.style.display='none'">
                            </div>
                            <?php endif; ?>
                            <div class="news-content">
                                <div class="news-headline">
                                    <a href="<?php echo htmlspecialchars($news['url']); ?>" target="_blank">
                                        <?php echo htmlspecialchars($news['headline']); ?>
                                    </a>
                                </div>
                                <div class="news-meta">
                                    <span class="news-source"><?php echo htmlspecialchars($news['source']); ?></span>
                                    • <?php echo date('M d, Y H:i', $news['datetime']); ?>
                                    <?php if (!empty($news['category'])): ?>
                                    • <span style="color: #6b7280;"><?php echo htmlspecialchars($news['category']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="news-summary">
                                    <?php echo htmlspecialchars($news['summary']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>