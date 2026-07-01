<?php
/** 僅明確指定 layout_page_title 時輸出；勿用 Module_Name，避免與 _breadcrumbs 重複 */
$__layout_title = (string)($layout_page_title ?? '');
?>
<body <?php if (!empty($bodytxt)) {
    echo $bodytxt;
} ?>>
    <div class="appRoot">
        <?php require_once __DIR__ . '/_header.php'; ?>
        <div class="appBody">
            <?php require_once __DIR__ . '/_sidebar.php'; ?>

            <main class="mainContent">
                <div class="<?php echo e((string)($layout_container_class ?? 'container')); ?>">
<?php
$__skip_header_title = !empty($GLOBALS['manage_page_header_in_breadcrumbs'])
    || (isset($breadcrumbs) && is_array($breadcrumbs));
if ($__layout_title !== '' && !$__skip_header_title) {
?>
                    <section class="pageHeader">
                        <h1 class="pageTitle"><?php echo e($__layout_title); ?></h1>
                    </section>
<?php } ?>
