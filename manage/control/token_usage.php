<?php
declare(strict_types=1);

/**
 * Gemini token 用量：依日期與單元彙總，並列出單筆明細。
 */

require_once '../_inc.php';
require_once '../_module.php';
require_once dirname(__DIR__, 2) . '/include/gemini_token_usage.php';

$subitem = 's1';
$Module_Name = 'Token 用量明細';

$today = date('Y-m-d');
$requestedFrom = trim((string)($filter_array['date_from'] ?? ''));
$requestedTo = trim((string)($filter_array['date_to'] ?? ''));
$requestedModule = gemini_token_usage_normalize_module((string)($filter_array['module'] ?? ''));
[$dateFrom, $dateTo] = gemini_token_usage_date_range(
    $requestedFrom !== '' ? $requestedFrom : date('Y-m-d', strtotime('-29 days')),
    $requestedTo !== '' ? $requestedTo : $today
);

$summaryRows = gemini_token_usage_summary($dateFrom, $dateTo, $requestedModule);
$detailRows = gemini_token_usage_details($dateFrom, $dateTo, $requestedModule, 200);
$moduleOptions = gemini_token_usage_module_options();

$sumCalls = 0;
$sumPrompt = 0;
$sumOutput = 0;
$sumThought = 0;
$sumTotal = 0;
$daily = [];
foreach ($summaryRows as $summaryRow) {
    $sumCalls += (int)($summaryRow['call_count'] ?? 0);
    $sumPrompt += (int)($summaryRow['prompt_tokens'] ?? 0);
    $sumOutput += (int)($summaryRow['output_tokens'] ?? 0);
    $sumThought += (int)($summaryRow['thought_tokens'] ?? 0);
    $sumTotal += (int)($summaryRow['total_tokens'] ?? 0);

    $useDate = (string)($summaryRow['use_date'] ?? '');
    if ($useDate === '') {
        continue;
    }
    if (!isset($daily[$useDate])) {
        $daily[$useDate] = ['calls' => 0, 'prompt' => 0, 'output' => 0, 'thought' => 0];
    }
    $daily[$useDate]['calls'] += (int)($summaryRow['call_count'] ?? 0);
    $daily[$useDate]['prompt'] += (int)($summaryRow['prompt_tokens'] ?? 0);
    $daily[$useDate]['output'] += (int)($summaryRow['output_tokens'] ?? 0);
    $daily[$useDate]['thought'] += (int)($summaryRow['thought_tokens'] ?? 0);
}

$chartLabels = [];
$chartCalls = [];
$chartPrompt = [];
$chartOutput = [];
$chartThought = [];
$rangeStart = DateTimeImmutable::createFromFormat('!Y-m-d', $dateFrom);
$rangeEnd = DateTimeImmutable::createFromFormat('!Y-m-d', $dateTo);
$spanDays = ($rangeStart instanceof DateTimeImmutable && $rangeEnd instanceof DateTimeImmutable)
    ? (int)$rangeStart->diff($rangeEnd)->days
    : 999;
if ($rangeStart instanceof DateTimeImmutable && $rangeEnd instanceof DateTimeImmutable && $spanDays <= 92) {
    $day = $rangeStart;
    $guard = 0;
    while ($day <= $rangeEnd && $guard < 120) {
        $key = $day->format('Y-m-d');
        $bucket = $daily[$key] ?? ['calls' => 0, 'prompt' => 0, 'output' => 0, 'thought' => 0];
        $chartLabels[] = $key;
        $chartCalls[] = (int)$bucket['calls'];
        $chartPrompt[] = (int)$bucket['prompt'];
        $chartOutput[] = (int)$bucket['output'];
        $chartThought[] = (int)$bucket['thought'];
        $next = $day->modify('+1 day');
        if (!$next instanceof DateTimeImmutable) {
            break;
        }
        $day = $next;
        $guard++;
    }
} else {
    ksort($daily);
    foreach ($daily as $key => $bucket) {
        $chartLabels[] = (string)$key;
        $chartCalls[] = (int)$bucket['calls'];
        $chartPrompt[] = (int)$bucket['prompt'];
        $chartOutput[] = (int)$bucket['output'];
        $chartThought[] = (int)$bucket['thought'];
    }
}
$chartPayload = [
    'labels' => $chartLabels,
    'calls' => $chartCalls,
    'prompt' => $chartPrompt,
    'output' => $chartOutput,
    'thought' => $chartThought,
];
$chartJsPath = dirname(__DIR__) . '/js/chart.umd.min.js';
$chartJsVer = is_file($chartJsPath) ? (string)filemtime($chartJsPath) : '1';

$backManNo = (int)($manNo ?? 0);
$backSubNo = (int)($subNo ?? 0);
$clearUrl = 'token_usage.php?manNo=' . $backManNo . '&subNo=' . $backSubNo;
$layout_page_title = 'Token 用量';
$breadcrumbs = [
    ['label' => '網站 LLM 設定', 'href' => 'llm.php?manNo=' . $backManNo . '&subNo=' . $backSubNo],
    ['label' => 'Token 用量'],
];
?>
<?php require_once '../_layout_head.php'; ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
                    <?php require_once '../_breadcrumbs.php'; ?>

                    <form action="token_usage.php" method="get" name="form1" id="form1">
                    <div id="view-list">
                        <div class="card filterWrap">
                            <div class="filterWrap__content">
                                <div class="filterWrap__grid">
                                    <div class="inputGroup">
                                        <label class="inputLabel" for="date_from">開始日期</label>
                                        <div class="inputWrapper">
                                            <input type="date" name="date_from" id="date_from"
                                                value="<?php echo e($dateFrom); ?>"
                                                class="formInput editView__dateInput">
                                        </div>
                                    </div>
                                    <div class="inputGroup">
                                        <label class="inputLabel" for="date_to">結束日期</label>
                                        <div class="inputWrapper">
                                            <input type="date" name="date_to" id="date_to"
                                                value="<?php echo e($dateTo); ?>"
                                                class="formInput editView__dateInput">
                                        </div>
                                    </div>
                                    <div class="inputGroup">
                                        <label class="inputLabel" for="module">單元</label>
                                        <div class="inputWrapper">
                                            <select class="formSelect" id="module" name="module">
                                                <option value="">全部單元</option>
                                                <?php foreach ($moduleOptions as $moduleCode) { ?>
                                                <option value="<?php echo e($moduleCode); ?>"<?php echo $requestedModule === $moduleCode ? ' selected' : ''; ?>><?php echo e(gemini_token_usage_module_label($moduleCode)); ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="filterWrap__actions">
                                    <a href="<?php echo e($clearUrl); ?>" class="btnStyle btnStyle--outline">
                                        <i class="bi bi-arrow-counterclockwise"></i> 清除
                                    </a>
                                    <button type="submit" class="btnStyle --isAnim">
                                        <i class="bi bi-search"></i> 查詢
                                    </button>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="manNo" value="<?php echo $backManNo; ?>">
                        <input type="hidden" name="subNo" value="<?php echo $backSubNo; ?>">

                        <div class="card tokenUsageChart">
                            <div class="tokenUsageChart__head">
                                <h3 class="tokenUsageChart__title">每日統計</h3>
                                <p class="tokenUsageChart__hint">柱狀為輸入、輸出、思考 token；曲線為呼叫次數（右軸）</p>
                            </div>
                            <?php if ($summaryRows === []) { ?>
                            <p class="listEmpty">這段期間沒有 token 紀錄</p>
                            <?php } else { ?>
                            <div class="tokenUsageChart__canvas">
                                <canvas id="tokenUsageChart" aria-label="每日 token 與呼叫次數"></canvas>
                            </div>
                            <?php } ?>
                        </div>

                        <div class="card tokenUsageBlock">
                            <div class="tokenUsageScroll">
                                <div class="tableHeader tableGrid--token-summary">
                                    <div>日期</div>
                                    <div>單元</div>
                                    <div>功能</div>
                                    <div class="textCenter">次數</div>
                                    <div class="textCenter">輸入</div>
                                    <div class="textCenter">輸出</div>
                                    <div class="textCenter">思考</div>
                                    <div class="textCenter">合計</div>
                                </div>
                                <div class="tableRow">
                                    <?php if ($summaryRows === []) { ?>
                                    <p class="listEmpty">這段期間沒有 token 紀錄</p>
                                    <?php } else { ?>
                                    <?php foreach ($summaryRows as $summaryRow) {
                                        $moduleCode = (string)($summaryRow['strModule'] ?? '');
                                        $featureCode = (string)($summaryRow['strFeature'] ?? '');
                                    ?>
                                    <div class="tableRow__item">
                                        <div class="tableRow__data tableGrid--token-summary">
                                            <div><span class="dateSpan"><?php echo e((string)($summaryRow['use_date'] ?? '')); ?></span></div>
                                            <div><?php echo e(gemini_token_usage_module_label($moduleCode)); ?></div>
                                            <div><?php echo e(gemini_token_usage_feature_label($featureCode)); ?></div>
                                            <div class="textCenter"><?php echo number_format((int)($summaryRow['call_count'] ?? 0)); ?></div>
                                            <div class="textCenter"><?php echo number_format((int)($summaryRow['prompt_tokens'] ?? 0)); ?></div>
                                            <div class="textCenter"><?php echo number_format((int)($summaryRow['output_tokens'] ?? 0)); ?></div>
                                            <div class="textCenter"><?php echo number_format((int)($summaryRow['thought_tokens'] ?? 0)); ?></div>
                                            <div class="textCenter"><?php echo number_format((int)($summaryRow['total_tokens'] ?? 0)); ?></div>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <div class="tableRow__item tokenUsage__sum">
                                        <div class="tableRow__data tableGrid--token-summary">
                                            <div>合計</div>
                                            <div></div>
                                            <div></div>
                                            <div class="textCenter"><?php echo number_format($sumCalls); ?></div>
                                            <div class="textCenter"><?php echo number_format($sumPrompt); ?></div>
                                            <div class="textCenter"><?php echo number_format($sumOutput); ?></div>
                                            <div class="textCenter"><?php echo number_format($sumThought); ?></div>
                                            <div class="textCenter"><?php echo number_format($sumTotal); ?></div>
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="tokenUsageScroll">
                                <div class="tableHeader tableGrid--token-detail">
                                    <div>時間</div>
                                    <div>單元</div>
                                    <div>功能</div>
                                    <div>模型</div>
                                    <div class="textCenter">輸入</div>
                                    <div class="textCenter">輸出</div>
                                    <div class="textCenter">思考</div>
                                    <div class="textCenter">合計</div>
                                    <div>帳號</div>
                                    <div class="textCenter">結果</div>
                                </div>
                                <div class="tableRow">
                                    <?php if ($detailRows === []) { ?>
                                    <p class="listEmpty">沒有明細</p>
                                    <?php } else { ?>
                                    <?php foreach ($detailRows as $detailRow) {
                                        $moduleCode = (string)($detailRow['strModule'] ?? '');
                                        $featureCode = (string)($detailRow['strFeature'] ?? '');
                                        $ok = (string)($detailRow['strSuccess'] ?? '') === 'Y';
                                    ?>
                                    <div class="tableRow__item">
                                        <div class="tableRow__data tableGrid--token-detail">
                                            <div><span class="dateSpan"><?php echo e((string)($detailRow['dtDate'] ?? '')); ?></span></div>
                                            <div><?php echo e(gemini_token_usage_module_label($moduleCode)); ?></div>
                                            <div><?php echo e(gemini_token_usage_feature_label($featureCode)); ?></div>
                                            <div><?php echo e((string)($detailRow['strModel'] ?? '')); ?></div>
                                            <div class="textCenter"><?php echo number_format((int)($detailRow['intPromptTokens'] ?? 0)); ?></div>
                                            <div class="textCenter"><?php echo number_format((int)($detailRow['intCandidatesTokens'] ?? 0)); ?></div>
                                            <div class="textCenter"><?php echo number_format((int)($detailRow['intThoughtsTokens'] ?? 0)); ?></div>
                                            <div class="textCenter"><?php echo number_format((int)($detailRow['intTotalTokens'] ?? 0)); ?></div>
                                            <div><?php echo e((string)($detailRow['strLogin'] ?? '')); ?></div>
                                            <div class="textCenter"><?php echo $ok ? '<span class="badge notes__badge">成功</span>' : '<span class="tokenUsage__fail">失敗</span>'; ?></div>
                                        </div>
                                    </div>
                                    <?php } ?>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="notes notes--lg">
                        <div class="notes__header">
                            <i class="bi bi-info-circle notes__icon"></i> 系統備註
                        </div>
                        <ul class="notes__list">
                            <li>圖表依日期加總：堆疊柱是輸入、輸出、思考 token，曲線是當天呼叫次數。</li>
                            <li>表格上方為日期、單元、功能的加總；下方為單筆明細，最多 200 筆，新的在上。</li>
                            <li>只統計已完成的 Gemini 產文與搜尋擴詞。向量嵌入的回應不含 token 數，不會出現在這份明細。</li>
                            <li>從這份頁面上線之後的呼叫才開始累計。</li>
                        </ul>
                    </div>
                    <div class="notes__spacer"></div>
                    </form>
<?php if ($summaryRows !== []) {
    echo script_src_tag('../js/chart.umd.min.js?ver=' . $chartJsVer);
    echo script_open();
?>
$(function () {
    var canvas = $('#tokenUsageChart').get(0);
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }
    var chartData = <?php echo json_encode($chartPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var formatNum = function (value) {
        return Number(value).toLocaleString('zh-Hant');
    };
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    type: 'bar',
                    label: '輸入',
                    data: chartData.prompt,
                    backgroundColor: '#4133eb',
                    stack: 'tokens',
                    yAxisID: 'y',
                    order: 2
                },
                {
                    type: 'bar',
                    label: '輸出',
                    data: chartData.output,
                    backgroundColor: '#008392',
                    stack: 'tokens',
                    yAxisID: 'y',
                    order: 2
                },
                {
                    type: 'bar',
                    label: '思考',
                    data: chartData.thought,
                    backgroundColor: '#f59e0b',
                    stack: 'tokens',
                    yAxisID: 'y',
                    order: 2
                },
                {
                    type: 'line',
                    label: '次數',
                    data: chartData.calls,
                    borderColor: '#d40023',
                    backgroundColor: '#d40023',
                    yAxisID: 'y1',
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: function (item) {
                            var suffix = item.dataset.yAxisID === 'y1' ? ' 次' : ' token';
                            return item.dataset.label + '：' + formatNum(item.parsed.y) + suffix;
                        }
                    }
                }
            },
            scales: {
                x: {
                    stacked: true,
                    ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 12 }
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    title: { display: true, text: 'Token' },
                    ticks: { precision: 0, callback: formatNum }
                },
                y1: {
                    position: 'right',
                    beginAtZero: true,
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: '次數' },
                    ticks: { precision: 0 }
                }
            }
        }
    });
});
<?php
    echo script_close();
} ?>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
