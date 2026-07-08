<?php
declare(strict_types=1);

/**
 * 開啟含側欄版面容器（左側 _sidebar + 右側主內容區開始標記）
 */

$__pgSidebarShow = frontend_sidebar_should_show((int)($Module_PKey ?? 0));
if ($__pgSidebarShow) {
    echo '<div class="pgLayout pgLayout--sidebar">' . "\n";
    echo '    <div class="pgLayout__main">' . "\n";
}
