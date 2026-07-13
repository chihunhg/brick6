<?php
declare(strict_types=1);

order_detail_export_vars();

$layout_page_title = (string)($layout_page_title ?? '訂單詳情');
$orderItems = is_array($orderItems ?? null) ? $orderItems : [];
$itemsSubTotal = (int)($itemsSubTotal ?? 0);
$Charge = (int)($Charge ?? 0);
$TotalPrice = (int)($TotalPrice ?? 0);
$intStateVal = (int)($intState ?? 1);
$intPayVal = (int)($intPay ?? 0);
$listBackUrl = manage_breadcrumbs_list_href('list.php');
?>
<?php require_once '../_layout_head.php'; ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
                    <?php require_once '../_breadcrumbs.php'; ?>

                    <section class="editView">
                        <form action="" method="post" name="form1" id="form1">
                        <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
                            <div class="errorArea__header">錯誤訊息</div>
                            <div class="errorArea__body">
                                <ul id="formErrorList"></ul>
                            </div>
                        </div>

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">訂單摘要</h4>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">訂單編號</label>
                                    <div class="col--4"><?php echo e((string)($OrderNo ?? '')); ?></div>
                                    <label class="col--2 inputLabel editView__formLabel">訂單日期</label>
                                    <div class="col--4"><?php echo e((string)($dtDate ?? '')); ?></div>
                                </div>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">付款方式</label>
                                    <div class="col--4"><?php echo e(PayType($intPayVal)); ?></div>
                                    <label class="col--2 inputLabel editView__formLabel" for="intState">處理狀況</label>
                                    <div class="col--4">
                                        <select name="intState" id="intState" class="formSelect">
                                            <?php for ($st = 1; $st <= 4; $st++) { ?>
                                            <option value="<?php echo $st; ?>"<?php
                                                if ($intStateVal === $st) {
                                                    echo ' selected="selected"';
                                                }
                                            ?>><?php echo e(FlowState($st)); ?></option>
                                            <?php } ?>
                                        </select>
                                        <input type="hidden" name="oldState" id="oldState" value="<?php echo $intStateVal; ?>">
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">訂購明細</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="text-center">品號</th>
                                                <th class="text-center">商品編號</th>
                                                <th>商品名稱</th>
                                                <th class="text-center">顏色</th>
                                                <th class="text-end">價錢</th>
                                                <th class="text-center">數量</th>
                                                <th class="text-end">小計</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if ($orderItems === []) { ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">暫無明細</td>
                                            </tr>
                                        <?php } ?>
                                        <?php foreach ($orderItems as $item) {
                                            $lineTotal = (int)($item['LineTotal'] ?? 0);
                                            ?>
                                            <tr>
                                                <td class="text-center"><?php echo e((string)($item['strNo'] ?? '')); ?></td>
                                                <td class="text-center"><?php echo e((string)($item['ProductNo'] ?? '')); ?></td>
                                                <td>
                                                    <?php if (!empty($item['Brand'])) { ?>
                                                    <span class="text-muted"><?php echo e((string)$item['Brand']); ?></span>
                                                    <?php } ?>
                                                    <?php echo e((string)($item['strName'] ?? '')); ?>
                                                </td>
                                                <td class="text-center"><?php echo e((string)($item['ColorName'] ?? '')); ?></td>
                                                <td class="text-end">$<?php echo e(number_format((int)($item['Price'] ?? 0))); ?></td>
                                                <td class="text-center"><?php echo e((string)($item['Quantity'] ?? '')); ?></td>
                                                <td class="text-end">$<?php echo e(number_format($lineTotal)); ?></td>
                                            </tr>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3 text-end fw-bold text-danger">
                                    <div>總計金額：$<?php echo e(number_format($itemsSubTotal)); ?></div>
                                    <div>運費：$<?php echo e(number_format($Charge)); ?></div>
                                    <div>應付金額：$<?php echo e(number_format($TotalPrice)); ?></div>
                                </div>
                            </div>
                        </article>

                        <article class="editView__body">
                            <div class="editView__section">
                                <h4 class="editView__sectionTitle">收件人資訊</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle mb-0">
                                        <tbody>
                                            <tr>
                                                <th class="table-light" style="width:20%">姓名</th>
                                                <td><?php echo e((string)($strName ?? '')); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-light">Email</th>
                                                <td><?php echo e((string)($EMail ?? '')); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-light">電話</th>
                                                <td><?php echo e((string)($Tel ?? '')); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-light">行動電話</th>
                                                <td><?php echo e((string)($Mobile ?? '')); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-light">地址</th>
                                                <td><?php echo e((string)($Address ?? '')); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-light">備註</th>
                                                <td><?php echo nl2br(e((string)($Memo ?? ''))); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-light">寄送超商</th>
                                                <td><?php
                                                    echo e((string)($Flow ?? ''));
                                                    if (($Flow ?? '') === '超商取貨') {
                                                        echo ' (' . e((string)($CVSStoreName ?? ''))
                                                            . ' ' . e((string)($CVSAddress ?? '')) . ')';
                                                    }
                                                ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-light">發票類別</th>
                                                <td><?php echo e((string)($Invoice ?? '')); ?></td>
                                            </tr>
                                            <?php if (($Invoice ?? '') === '公司戶電子發票') { ?>
                                            <tr>
                                                <th class="table-light">發票統編</th>
                                                <td><?php echo e((string)($InvoiceNo ?? '')); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-light">公司/單位抬頭</th>
                                                <td><?php echo e((string)($Title ?? '')); ?></td>
                                            </tr>
                                            <?php } ?>
                                            <tr>
                                                <th class="table-light">發票狀態</th>
                                                <td><?php echo e(Invoice_Type((int)($intInvoice ?? 1))); ?></td>
                                            </tr>
                                            <?php if ((int)($intInvoice ?? 1) > 2) { ?>
                                            <tr>
                                                <th class="table-light">發票號碼</th>
                                                <td><?php echo e((string)($InvoiceNumber ?? '')); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-light">發票日期</th>
                                                <td><?php echo e((string)($invoiceDate ?? '')); ?></td>
                                            </tr>
                                            <tr>
                                                <th class="table-light">隨機碼</th>
                                                <td><?php echo e((string)($RandomNum ?? '')); ?></td>
                                            </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </article>

                        <div class="editView__footer flex flex--jtEnd gap--2">
                            <a href="<?php echo e($listBackUrl); ?>" class="btnStyle btnStyle--outline">關閉</a>
                            <button type="submit" class="btnStyle --isAnim" name="Submit" value="變更">變更</button>
                        </div>

                        <?php
                        echo hiddenText('csrf_token', e($csrf_token ?? '')) . PHP_EOL;
                        echo hiddenNumeric('PKey', $Update_PKey ?? $Order_PKey ?? 0) . PHP_EOL;
                        echo hiddenNumeric('manNo', $manNo ?? ($filter_array['manNo'] ?? '')) . PHP_EOL;
                        echo hiddenNumeric('subNo', $subNo ?? ($filter_array['subNo'] ?? '')) . PHP_EOL;
                        echo hiddenNumeric('Page', $filter_array['Page'] ?? 1) . PHP_EOL;
                        ?>
                        </form>
                    </section>

                    <div class="notes__spacer"></div>

<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
