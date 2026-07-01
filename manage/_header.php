<?php
declare(strict_types=1);

// 自函式內 require 時（如 manage_child_list_render）需從 $GLOBALS 取路徑變數
$web_root = (string)($web_root ?? $GLOBALS['web_root'] ?? (defined('APP_WEB_ROOT') ? APP_WEB_ROOT : '/'));
$Web_Name = (string)($Web_Name ?? $GLOBALS['Web_Name'] ?? $GLOBALS['WebName'] ?? '');

$__header_site_url = rtrim($web_root, '/');
$__header_logout_url = $web_root . 'manage/index.php?Action=logout';
?>
    <!-- HEADER -->
    <header class="header">
        <div class="headerLeft">
            <div class="logoBox header__logoBox">
                <img src="<?php echo e($web_root); ?>images/all/logo.png" alt="<?php echo e($Web_Name); ?>">
            </div>
            <div class="logoText">
                <h1><?php echo e($Web_Name); ?></h1>
                <span>後端管理系統</span>
            </div>
        </div>

        <div class="headerRight">
            <button type="button" class="btnStyle btnStyle--ghost"
                data-manage-action="open-external"
                data-open-url="<?php echo e($__header_site_url); ?>"
                title="網站前台">
                <i class="bi bi-box-arrow-up-right"></i>
                <span>瀏覽網站</span>
            </button>
            <button type="button" class="btnStyle btnStyle--dangerGhost"
                data-manage-action="manage-logout"
                data-logout-url="<?php echo e($__header_logout_url); ?>"
                title="登出">
                <i class="bi bi-box-arrow-right"></i>
                <span>登出</span>
            </button>
        </div>
    </header>

