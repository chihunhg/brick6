<!-- SIDEBAR -->
<div class="sidebarContainer">
    <aside class="sidebar open" id="sidebar">
        <button type="button" class="sidebarToggle" data-manage-action="toggle-sidebar" aria-label="切換側欄">
            <i class="sidebar__toggleIcon bi bi-chevron-left" id="sidebarToggle-icon"></i>
        </button>
        <div class="sidebarScroll" id="sidebar-menu">
            <?php
            $Login_ID = (string)($Login_ID ?? ($_SESSION['Login_ID'] ?? ''));

            $Left_Menu_raw = explode(',', (string)($_SESSION['FunctionID'] ?? ''));
            $Left_Menu = array_values(array_unique(array_map('intval', array_filter($Left_Menu_raw, static function ($v) {
                return $v !== '' && ctype_digit($v);
            }))));

            $web_array     = [4, 5, 6];
            $home_array    = [1, 2];
            $control_array = [3];
            $menu_array    = array_merge($web_array, $home_array, $control_array);

            $in_list = static function (array $a) {
                $a = array_values(array_unique(array_map('intval', $a)));
                return implode(',', $a ?: [0]);
            };

            $sanitize_link = static function ($link) {
                return preg_replace('~[^a-zA-Z0-9/_-]~', '', (string)$link);
            };

            $manNo = isset($manNo) && ctype_digit((string)$manNo) ? (int)$manNo : (int)($_GET['manNo'] ?? $_SESSION['manNo'] ?? 0);
            $subNo = isset($subNo) && ctype_digit((string)$subNo) ? (int)$subNo : (int)($_GET['subNo'] ?? $_SESSION['subNo'] ?? 0);
            $manNo = max(0, $manNo);
            $subNo = max(0, $subNo);
            $Module_PKey = isset($Module_PKey) ? (int)$Module_PKey : 0;
            $subitem = (string)($subitem ?? '');

            $canSee = static function (int $navPKey) use ($Left_Menu, $Login_ID): bool {
                return in_array($navPKey, $Left_Menu, true) || $Login_ID === 'Admin';
            };

            $sidebarMenu = [];

            $appendModuleRow = static function (
                array &$menu,
                int $navPKey,
                string $navName,
                string $navLink,
                int $navLayer,
                ?string $urlLink,
                int $manNo,
                int $subNo,
                $sanitize_link
            ): void {
                if ($navLayer > 0) {
                    $children = [];
                    $hasActiveChild = false;
                    $rs1 = new recordset(
                        'SELECT * FROM module_d WHERE Module_PKey = :Module_PKey ORDER BY Sort',
                        ['Module_PKey' => $navPKey]
                    );
                    while (!$rs1->eof) {
                        $dPKey = (int)$rs1->field('PKey');
                        $dName = (string)$rs1->field('strName');
                        $dLink = $sanitize_link($rs1->field('strLink'));
                        $childActive = $subNo > 0 && $subNo === $dPKey;
                        if ($childActive) {
                            $hasActiveChild = true;
                        }
                        $children[] = [
                            'type'     => 'LINK',
                            'label'    => $dName,
                            'link'     => "../{$dLink}/list.php?manNo={$navPKey}&subNo={$dPKey}",
                            'isActive' => $childActive,
                        ];
                        $rs1->movenext();
                    }
                    $menuOpen = ($manNo > 0 && $manNo === $navPKey) || $hasActiveChild;
                    $menu[] = [
                        'id'       => 'mod-' . $navPKey,
                        'type'     => 'DROPDOWN',
                        'label'    => $navName,
                        'link'     => '#',
                        'isOpen'   => $menuOpen,
                        'isActive' => $menuOpen,
                        'children' => $children,
                    ];
                } else {
                    $menu[] = [
                        'type'     => 'LINK',
                        'label'    => $navName,
                        'link'     => $urlLink ?? '../' . $navLink . '/list.php?manNo=' . $navPKey,
                        'isActive' => $manNo === $navPKey,
                    ];
                }
            };

            // 網站管理
            if (!empty(array_intersect($web_array, $Left_Menu)) || $Login_ID === 'Admin') {
                $sql = 'SELECT * FROM module_p WHERE Upload = :Upload AND PKey IN (' . $in_list($web_array) . ') ORDER BY Sort, PKey';
                $rs  = new recordset($sql, ['Upload' => 'Yes']);
                if (!$rs->eof) {
                    $sidebarMenu[] = ['type' => 'HEADER', 'label' => '網站管理'];
                    while (!$rs->eof) {
                        $navPKey  = (int)$rs->field('PKey');
                        $navName  = (string)$rs->field('strName');
                        $navLink  = $sanitize_link($rs->field('strLink'));
                        $navLayer = (int)$rs->field('intLayer');
                        if ($canSee($navPKey)) {
                            $urlLink = '../' . $navLink . '/list.php?manNo=' . $navPKey;
                            if ($navPKey === 5) {
                                $urlLink = '../control/webset.php?manNo=' . $navPKey;
                            }
                            $appendModuleRow($sidebarMenu, $navPKey, $navName, $navLink, $navLayer, $urlLink, $manNo, $subNo, $sanitize_link);
                        }
                        $rs->movenext();
                    }
                }
            }

            // 首頁管理
            if (!empty(array_intersect($home_array, $Left_Menu)) || $Login_ID === 'Admin') {
                $sql = 'SELECT * FROM module_p WHERE Upload = :Upload AND PKey IN (' . $in_list($home_array) . ') ORDER BY Sort, PKey';
                $rs  = new recordset($sql, ['Upload' => 'Yes']);
                if (!$rs->eof) {
                    $sidebarMenu[] = ['type' => 'HEADER', 'label' => '首頁管理'];
                    while (!$rs->eof) {
                        $navPKey  = (int)$rs->field('PKey');
                        $navName  = (string)$rs->field('strName');
                        $navLink  = $sanitize_link($rs->field('strLink'));
                        $navLayer = (int)$rs->field('intLayer');
                        if ($canSee($navPKey)) {
                            $urlLink = '../' . $navLink . '/list.php?manNo=' . $navPKey;
                            $appendModuleRow($sidebarMenu, $navPKey, $navName, $navLink, $navLayer, $urlLink, $manNo, $subNo, $sanitize_link);
                        }
                        $rs->movenext();
                    }
                }
            }

            // 單元管理
            $sidebarMenu[] = ['type' => 'HEADER', 'label' => '單元管理'];
            $sql = 'SELECT * FROM module_p WHERE Upload = :Upload AND intType = :intType AND PKey NOT IN (' . $in_list($menu_array) . ') ORDER BY Sort, PKey';
            $rs  = new recordset($sql, ['Upload' => 'Yes', 'intType' => 1]);
            while (!$rs->eof) {
                $navPKey  = (int)$rs->field('PKey');
                $navName  = (string)$rs->field('strName');
                $navLink  = $sanitize_link($rs->field('strLink'));
                $navLayer = (int)$rs->field('intLayer');
                if ($canSee($navPKey)) {
                    $urlLink = '../' . $navLink . '/list.php?manNo=' . $navPKey;
                    $appendModuleRow($sidebarMenu, $navPKey, $navName, $navLink, $navLayer, $urlLink, $manNo, $subNo, $sanitize_link);
                }
                $rs->movenext();
            }

            // 系統管理
            $sidebarMenu[] = ['type' => 'HEADER', 'label' => '系統管理'];
            if (!empty(array_intersect($control_array, $Left_Menu)) || $Login_ID === 'Admin') {
                $sidebarMenu[] = [
                    'type'     => 'LINK',
                    'label'    => '權限管理',
                    'link'     => '../control/list.php?manNo=3',
                    'isActive' => $Module_PKey === 3,
                ];
            }
            $sidebarMenu[] = [
                'type'     => 'LINK',
                'label'    => '變更密碼',
                'link'     => '../control/chgpw.php',
                'isActive' => $subitem === 's5',
            ];

            // 其他
            $sidebarMenu[] = ['type' => 'HEADER', 'label' => '其他'];
            $sidebarMenu[] = [
                'type'   => 'LINK',
                'label'  => '後台操作教學',
                'link'   => 'https://www.youtube.com/playlist?list=PLhUDwpflj2e0gwtZBJIVxcdcRgoAyAmFG',
                'target' => '_blank',
                'rel'    => 'noopener noreferrer',
            ];
            $sidebarMenu[] = [
                'type'   => 'LINK',
                'label'  => '後台操作影音教學',
                'link'   => 'https://youtube.com/playlist?list=PLhUDwpflj2e0gwtZBJIVxcdcRgoAyAmFG',
                'target' => '_blank',
                'rel'    => 'noopener noreferrer',
            ];
            $sidebarMenu[] = [
                'type'        => 'TOOL',
                'label'       => '圖片壓縮工具',
                'externalUrl' => 'https://imagecompressor.com/zh/',
                'description' => '如圖片過大，可借此工具壓縮。',
            ];
            $sidebarMenu[] = [
                'type'        => 'TOOL',
                'label'       => '調整圖片大小',
                'externalUrl' => 'https://www.iloveimg.com/zh-tw/resize-image',
                'description' => '如圖片過大，可借此工具調整圖片大小。',
            ];
            $sidebarMenu[] = [
                'type'   => 'LINK',
                'label'  => '網頁編輯器操作手冊【編輯器圖插文elFinder】',
                'link'   => 'https://www.tsg.com.tw/education/網頁編輯器操作手冊【編輯器圖插文elFinder】.pdf',
                'target' => '_blank',
                'rel'    => 'noopener noreferrer',
            ];
            ?>
            <?php foreach ($sidebarMenu as $item):
                $isActive = !empty($item['isActive']) ? 'active' : 'inactive';
                $isOpen = !empty($item['isOpen']);
                $type = $item['type'] ?? 'LINK';
                $linkAttrs = '';
                if (!empty($item['target'])) {
                    $linkAttrs .= ' target="' . e($item['target']) . '"';
                }
                if (!empty($item['rel'])) {
                    $linkAttrs .= ' rel="' . e($item['rel']) . '"';
                }
                ?>
                <?php if ($type === 'HEADER'): ?>
                    <div class="menuHeader">
                        <?= e($item['label']) ?>
                    </div>
                <?php elseif ($type === 'DROPDOWN'): ?>
                    <?php
                    $dropdownId = (string)($item['id'] ?? '');
                    $submenuClass = 'submenu' . ($isOpen ? '' : ' is-collapsed');
                    $arrowClass = 'bi bi-chevron-right sidebar__arrow' . ($isOpen ? ' is-open' : '');
                    ?>
                    <div class="sidebar__bk">
                        <a href="#"
                            class="menuItem <?= e($isActive) ?>"
                            data-manage-action="toggle-submenu"
                            data-submenu-id="<?= e($dropdownId) ?>"
                            aria-expanded="<?= $isOpen ? 'true' : 'false' ?>"
                            aria-controls="submenu-<?= e($dropdownId) ?>">
                            <?= e($item['label']) ?>
                            <i class="<?= e($arrowClass) ?>" id="arrow-<?= e($dropdownId) ?>" aria-hidden="true"></i>
                        </a>
                        <?php if (!empty($item['children'])): ?>
                            <div class="<?= e($submenuClass) ?>" id="submenu-<?= e($dropdownId) ?>">
                                <?php foreach ($item['children'] as $child):
                                    $childActive = !empty($child['isActive']) ? 'active' : '';
                                    ?>
                                    <a href="<?= e($child['link']) ?>" class="submenuItem <?= e($childActive) ?>"><?= e($child['label']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($type === 'TOOL'): ?>
                    <div class="sidebar__tool">
                        <?php if (!empty($item['externalUrl'])): ?>
                            <button type="button" class="btnStyle btnStyle--ghost" data-manage-action="open-external" data-open-url="<?= e($item['externalUrl']) ?>"><?= e($item['label']) ?></button>
                        <?php else: ?>
                            <a href="<?= e($item['link'] ?? '#') ?>" class="btnStyle btnStyle--ghost"<?= $linkAttrs ?>><?= e($item['label']) ?></a>
                        <?php endif; ?>
                        <?php if (!empty($item['description'])): ?>
                            <div class="smTxt"><?= e($item['description']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="sidebar__bk">
                        <a href="<?= e($item['link']) ?>" class="menuItem <?= e($isActive) ?>"<?= $linkAttrs ?>>
                            <?= e($item['label']) ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </aside>
</div>
