<?php
// Helper functions for templates
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
?>

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
                    <strong style="color: #666; font-size: 13px;">Style Scores:</strong><br>
                    <?php foreach ($zacksData['styleScores'] as $style => $score): ?>
                        <span class="score-badge score-<?php echo strtolower($score); ?>">
                            <?php echo htmlspecialchars($style); ?>: <?php echo htmlspecialchars($score); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($zacksData['stockActivity'])): ?>
            <div style="margin-bottom: 20px;">
                <h3 style="color: #666; font-size: 16px; margin-bottom: 10px; border-bottom: 1px solid #e0e0e0; padding-bottom: 5px;">Stock Activity</h3>
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
        
        <?php if (!empty($zacksData['earningsData'])): ?>
            <div style="margin-bottom: 20px;">
                <h3 style="color: #666; font-size: 16px; margin-bottom: 10px; border-bottom: 1px solid #e0e0e0; padding-bottom: 5px;">Earnings Data</h3>
                <div class="data-table">
                    <?php foreach ($zacksData['earningsData'] as $label => $value): ?>
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

<!-- Remaining template sections... (recommendations, earnings, news, etc.) -->
<!-- I'll include a few key sections, but you get the pattern -->

<?php if (!empty($recommendations)): ?>
<div class="info-block">
    <h2>Analyst Recommendations</h2>
    <div class="info-block-content">
        <table>
            <thead>
                <tr>
                    <th>Period</th>
                    <th class="strong-buy">Strong Buy</th>
                    <th class="buy">Buy</th>
                    <th class="hold">Hold</th>
                    <th class="sell">Sell</th>
                    <th class="strong-sell">Strong Sell</th>
                    <th>Total</th>
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
                    <td><?php echo (int)$rec['strongBuy'] + (int)$rec['buy'] + (int)$rec['hold'] + (int)$rec['sell'] + (int)$rec['strongSell']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Add remaining sections here following the same pattern -->