<?php
declare(strict_types=1);

$__pgSidebarShow = frontend_sidebar_should_show((int)($Module_PKey ?? 0));
if ($__pgSidebarShow) {
    echo '<div class="pgLayout pgLayout--sidebar">' . "\n";
    echo '    <div class="pgLayout__main">' . "\n";
}
