<?php
declare(strict_types=1);

if (!empty($__pgSidebarShow)) {
    echo "    </div>\n";
    require __DIR__ . '/_sidebar.php';
    echo "</div>\n";
}
