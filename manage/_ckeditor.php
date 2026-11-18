<?php

declare(strict_types=1);

/**
 * 內容編輯器資源。知識庫內文頁在版尾再引入一次；
 * 資產已由 _in_code_bottom.php 輸出時不會重複。
 */
require_once __DIR__ . '/_jodit_assets.php';
manage_echo_jodit_assets('../jodit/');
