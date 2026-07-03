<?php

declare(strict_types=1);

/**
 * 子模組列表版面 shell（由 manage_child_list_render 設定變數後引入）
 *
 * @var string $childListModuleDir
 */

?>
<?php require_once __DIR__ . '/_layout_head.php'; ?>
</head>

<?php require_once __DIR__ . '/_layout_body_open.php'; ?>
                    <?php require_once __DIR__ . '/_breadcrumbs.php'; ?>

                    <form action="" method="post" name="form1" id="form1">
                    <div id="view-list">
                        <div class="card filterWrap">
                            <div class="filterWrap__actions">
                                <a href="<?php echo e($listBackUrl); ?>" class="btnStyle btnStyle--outline">
                                    <i class="bi bi-arrow-left"></i> <?php echo e($listBackLabel); ?>
                                </a>
                            </div>
                        </div>

                        <div class="card">
                            <?php
                            ob_start();
                            require_once __DIR__ . '/_select.php';
                            $toolbarHtml = ob_get_clean();
                            echo str_replace(
                                'data-page="add.php" data-pkey=""',
                                'data-page="' . e($addUrl) . '" data-pkey=""',
                                $toolbarHtml
                            );
                            ?>
                            <?php require $childListModuleDir . '/_list.php'; ?>

                            <?php echo $listHiddenHtml; ?>
                            <?php echo hiddenNumeric('Total', (int)($Total ?? 0)) . PHP_EOL; ?>

                            <?php if (file_exists(__DIR__ . '/_page.php')) {
                                require_once __DIR__ . '/_page.php';
                            } ?>
                        </div>
                    </div>

                    <?php if (($childListNotesHtml ?? '') !== '') {
                        echo $childListNotesHtml;
                    } ?>
                    </form>

<?php require_once __DIR__ . '/_layout_body_close.php'; ?>
<?php require_once __DIR__ . '/_in_code_bottom.php'; ?>
</body>
</html>
