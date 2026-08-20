<?php
declare(strict_types=1);

require_once '../_inc.php';

$subitem     = 's6';
$Module_Name = '雙因素驗證';

$loginId = (string)($_SESSION['Login_ID'] ?? '');
if ($loginId === '') {
    manage_alert_script('登入逾時，請重新登入', $web_root . 'manage/login/index.php');
    exit;
}

$__csrf_key = $__csrf_key ?? 'manage_form';
$csrf_token = crud_csrf_ensure_page($__csrf_key);

$userRow = manage_mfa_fetch_user_by_login($loginId);
$userPKey = (int)($userRow['PKey'] ?? 0);
$mfaEnabled = $userRow !== null && manage_mfa_is_enabled_for_user($userRow);
$schemaReady = manage_mfa_schema_ready();
$mfaRequired = manage_mfa_required_globally();
$mfaMustSetup = manage_mfa_user_must_setup($userRow);
$setupPending = manage_mfa_onboard_schema_ready() && (int)($userRow['mfa_setup_pending'] ?? 0) === 1;

$setupSecret = '';
$setupUri = '';
$setupQrUrl = '';
$plainBackupCodes = [];
$showBackupCodes = false;
$flashError = '';
$setupMode = (string)($_SESSION['MFA_Setup_Mode'] ?? '');

if (!$schemaReady) {
    $flashError = '資料庫尚未建立 MFA 欄位，請先執行 sql/webcontrol_totp_mfa.sql';
}

$manageMfaVerifyPassword = static function (string $password, int $pkey): bool {
    if ($pkey <= 0 || $password === '') {
        return false;
    }
    $pwRow = crud_fetch_one(
        'SELECT strPW FROM webcontrol WHERE PKey = :pk LIMIT 1',
        ['pk' => $pkey]
    );
    $hashed = (string)($pwRow['strPW'] ?? '');
    return $hashed !== '' && secure_verify_and_migrate($password, $hashed, $pkey);
};

if ($schemaReady && isset($filter_array['Submit'])) {
    crud_csrf_verify($__csrf_key);
    $action = (string)($filter_array['Submit'] ?? '');

    if ($action === '取消重新綁定') {
        unset($_SESSION['MFA_Setup_Secret'], $_SESSION['MFA_Setup_PKey'], $_SESSION['MFA_Setup_Mode']);
        $setupMode = '';
    } elseif ($action === '開始設定') {
        $setupSecret = manage_mfa_generate_secret();
        $_SESSION['MFA_Setup_Secret'] = $setupSecret;
        $_SESSION['MFA_Setup_PKey'] = $userPKey;
        $_SESSION['MFA_Setup_Mode'] = 'enable';
        $setupMode = 'enable';
    } elseif ($action === '開始重新綁定') {
        if (!$mfaEnabled) {
            $flashError = '尚未啟用雙因素驗證，請使用「開始設定 TOTP」';
        } else {
            $currentPw = trim((string)($filter_array['rebind_pw'] ?? ''));
            $code = trim((string)($filter_array['rebind_mfa_code'] ?? ''));
            if (!$manageMfaVerifyPassword($currentPw, $userPKey)) {
                $flashError = '目前密碼錯誤';
            } elseif (!manage_mfa_verify_for_user($userPKey, $code)) {
                $flashError = '驗證碼錯誤';
            } else {
                $setupSecret = manage_mfa_generate_secret();
                $_SESSION['MFA_Setup_Secret'] = $setupSecret;
                $_SESSION['MFA_Setup_PKey'] = $userPKey;
                $_SESSION['MFA_Setup_Mode'] = 'rebind';
                $setupMode = 'rebind';
                manage_history(3, $Module_Name, '開始重新綁定 TOTP', $WorkFile ?? 'mfa_setup.php', $loginId, '驗證通過');
            }
        }
    } elseif ($action === '確認啟用' || $action === '確認重新綁定') {
        $setupSecret = (string)($_SESSION['MFA_Setup_Secret'] ?? '');
        $setupPKey = (int)($_SESSION['MFA_Setup_PKey'] ?? 0);
        $setupMode = (string)($_SESSION['MFA_Setup_Mode'] ?? 'enable');
        $code = trim((string)($filter_array['mfa_code'] ?? ''));
        if ($setupSecret === '' || $setupPKey !== $userPKey) {
            $flashError = '設定逾時，請重新開始';
            unset($_SESSION['MFA_Setup_Secret'], $_SESSION['MFA_Setup_PKey'], $_SESSION['MFA_Setup_Mode']);
            $setupMode = '';
        } elseif ($action === '確認啟用' && $setupMode !== 'enable') {
            $flashError = '操作無效，請重新開始';
        } elseif ($action === '確認重新綁定' && $setupMode !== 'rebind') {
            $flashError = '操作無效，請重新開始';
        } elseif (!manage_mfa_verify_totp($setupSecret, $code)) {
            $flashError = '驗證碼錯誤，請確認 App 時間同步後重試';
        } else {
            $plainBackupCodes = manage_mfa_generate_backup_codes();
            try {
                manage_mfa_save_enabled($userPKey, $setupSecret, $plainBackupCodes);
                unset($_SESSION['MFA_Setup_Secret'], $_SESSION['MFA_Setup_PKey'], $_SESSION['MFA_Setup_Mode']);
                $setupMode = '';
                $showBackupCodes = true;
                $userRow = manage_mfa_fetch_user_by_login($loginId);
                $mfaEnabled = true;
                $historyAction = $action === '確認重新綁定' ? '重新綁定 TOTP' : '啟用 TOTP';
                manage_history(3, $Module_Name, $historyAction, $WorkFile ?? 'mfa_setup.php', $loginId, '成功');
            } catch (Throwable $e) {
                $flashError = $e->getMessage();
            }
        }
    } elseif ($action === '停用') {
        $currentPw = trim((string)($filter_array['current_pw'] ?? ''));
        $code = trim((string)($filter_array['mfa_code'] ?? ''));
        if ($userRow === null) {
            $flashError = '找不到帳號資料';
        } elseif (!$manageMfaVerifyPassword($currentPw, $userPKey)) {
            $flashError = '目前密碼錯誤';
        } elseif (!manage_mfa_verify_for_user($userPKey, $code)) {
            $flashError = '驗證碼錯誤';
        } else {
            manage_mfa_disable($userPKey);
            unset($_SESSION['MFA_Setup_Secret'], $_SESSION['MFA_Setup_PKey'], $_SESSION['MFA_Setup_Mode']);
            $mfaEnabled = false;
            $setupMode = '';
            $userRow = manage_mfa_fetch_user_by_login($loginId);
            manage_history(3, $Module_Name, '停用 TOTP', $WorkFile ?? 'mfa_setup.php', $loginId, '停用成功');
            manage_alert_script('已停用雙因素驗證', $WorkFile ?? 'mfa_setup.php');
            exit;
        }
    }
}

if ($setupSecret === '') {
    $setupSecret = (string)($_SESSION['MFA_Setup_Secret'] ?? '');
}
if ($setupMode === '') {
    $setupMode = (string)($_SESSION['MFA_Setup_Mode'] ?? '');
}
$isRebindSetup = $setupMode === 'rebind' && $setupSecret !== '';
if ($setupSecret !== '' && $userPKey > 0) {
    $issuer = trim((string)$WebName) !== '' ? (string)$WebName : 'Brick6';
    $setupUri = manage_mfa_otpauth_uri($setupSecret, $loginId, $issuer);
    $setupQrUrl = manage_mfa_qr_image_url($setupUri);
}

$backupRemaining = $userRow !== null ? manage_mfa_backup_codes_remaining($userRow) : 0;

$breadcrumbs = [
    ['label' => '單元管理'],
    ['label' => '帳號管理'],
    ['label' => '雙因素驗證'],
];
$layout_page_title = '雙因素驗證';

require_once '../_layout_head.php';
?>
<?php echo script_open(); ?>
function fieldCheckEnable(theForm) {
    var $active = $(document.activeElement);
    if ($active.attr('name') === 'Submit' && $active.val() === '取消重新綁定') {
        if (typeof loading === 'function') loading(1);
        return true;
    }
    if (typeof loading === 'function') loading(1);
    var code = $.trim($('#mfa_code').val());
    if (code === '') {
        return window.manageFormValidationFail(['請輸入 App 驗證碼'], { focusField: 'mfa_code', form: theForm });
    }
    return window.manageFormValidationOk(theForm);
}
function fieldCheckDisable(theForm) {
    if (typeof loading === 'function') loading(1);
    var errors = [], fields = [];
    if ($.trim($('#current_pw').val()) === '') { errors.push('請輸入目前密碼'); fields.push('current_pw'); }
    if ($.trim($('#disable_mfa_code').val()) === '') { errors.push('請輸入驗證碼'); fields.push('disable_mfa_code'); }
    if (errors.length) {
        return window.manageFormValidationFail(errors, { focusField: fields[0], form: theForm });
    }
    return window.manageFormValidationOk(theForm);
}
function fieldCheckRebind(theForm) {
    if (typeof loading === 'function') loading(1);
    var errors = [], fields = [];
    if ($.trim($('#rebind_pw').val()) === '') { errors.push('請輸入目前密碼'); fields.push('rebind_pw'); }
    if ($.trim($('#rebind_mfa_code').val()) === '') { errors.push('請輸入驗證碼'); fields.push('rebind_mfa_code'); }
    if (errors.length) {
        return window.manageFormValidationFail(errors, { focusField: fields[0], form: theForm });
    }
    return window.manageFormValidationOk(theForm);
}
<?php echo script_close(); ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
                    <?php require_once '../_breadcrumbs.php'; ?>

                    <?php if ($flashError !== ''): ?>
                    <div class="errorArea" aria-live="polite">
                        <div class="errorArea__header">錯誤訊息</div>
                        <div class="errorArea__body"><ul><li><?= e($flashError) ?></li></ul></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($mfaMustSetup && $schemaReady): ?>
                    <article class="card mb-3">
                        <p class="text-danger mb-0"><strong><?php
                        if ($setupPending) {
                            echo '此為新帳號首次登入，請完成雙因素驗證綁定後才能使用其他後台功能。';
                        } elseif ($mfaRequired) {
                            echo '此站台已啟用 MANAGE_MFA_REQUIRED，請完成雙因素驗證設定。';
                        } else {
                            echo '請完成雙因素驗證設定。';
                        }
                        ?></strong></p>
                    </article>
                    <?php endif; ?>

                    <article class="card">
                        <h3 class="h5">目前狀態</h3>
                        <p>帳號：<strong><?= e($loginId) ?></strong></p>
                        <p>雙因素驗證：
                            <?php if (!$schemaReady): ?>
                                <span class="text-muted">尚未就緒（需執行 SQL migration）</span>
                            <?php elseif ($mfaEnabled): ?>
                                <span class="text-success">已啟用</span>（備用碼剩餘 <?= (int)$backupRemaining ?> 組）
                            <?php else: ?>
                                <span class="text-warning">未啟用</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-muted small mb-0">可使用 Google Authenticator、Microsoft Authenticator 或其他 TOTP App，掃描 QR Code 或手動輸入金鑰即可。</p>
                    </article>

                    <?php if ($showBackupCodes && $plainBackupCodes !== []): ?>
                    <article class="card mt-3">
                        <h3 class="h5">備用碼（請立即保存，僅顯示一次）</h3>
                        <p class="text-danger">每組備用碼僅能使用一次。遺失 App 時可用備用碼登入。</p>
                        <ul class="list-unstyled font-monospace">
                            <?php foreach ($plainBackupCodes as $bc): ?>
                            <li><?= e($bc) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                    <?php endif; ?>

                    <?php if ($schemaReady && !$mfaEnabled && $setupSecret === ''): ?>
                    <section class="editView mt-3">
                        <form action="" method="post">
                            <input type="hidden" name="csrf_token" value="<?= e((string)$csrf_token) ?>">
                            <button type="submit" name="Submit" value="開始設定" class="btnStyle"><i class="bi bi-shield-lock"></i> 開始設定 TOTP</button>
                        </form>
                    </section>
                    <?php endif; ?>

                    <?php if ($schemaReady && !$mfaEnabled && $setupSecret !== '' && $setupMode === 'enable'): ?>
                    <section class="editView mt-3">
                        <h4 class="editView__sectionTitle">步驟 2：綁定 Authenticator App</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <?php if ($setupQrUrl !== ''): ?>
                                <img src="<?= e_attr($setupQrUrl) ?>" width="220" height="220" alt="TOTP QR Code" class="img-fluid border">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <p>手動金鑰（無法掃描時）：</p>
                                <p class="font-monospace user-select-all"><?= e($setupSecret) ?></p>
                            </div>
                        </div>
                        <form action="" method="post" class="mt-3" data-manage-validate="fieldCheckEnable">
                            <div class="formGrid">
                                <label class="col--2 inputLabel" for="mfa_code">App 驗證碼</label>
                                <div class="col--10">
                                    <input type="text" name="mfa_code" id="mfa_code" class="formInput" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="6 位數">
                                </div>
                            </div>
                            <div class="editView__footer">
                                <input type="hidden" name="csrf_token" value="<?= e((string)$csrf_token) ?>">
                                <button type="submit" name="Submit" value="確認啟用" class="btnStyle"><i class="bi bi-check-lg"></i> 確認啟用</button>
                            </div>
                        </form>
                    </section>
                    <?php endif; ?>

                    <?php if ($schemaReady && $isRebindSetup): ?>
                    <section class="editView mt-3">
                        <h4 class="editView__sectionTitle">重新綁定 Authenticator App</h4>
                        <p class="text-muted">請用 App 掃描下方<strong>新的</strong> QR Code（舊 App 項目可於完成後刪除）。完成後將產生新的備用碼，舊備用碼會失效。</p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <?php if ($setupQrUrl !== ''): ?>
                                <img src="<?= e_attr($setupQrUrl) ?>" width="220" height="220" alt="TOTP QR Code" class="img-fluid border">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <p>手動金鑰（無法掃描時）：</p>
                                <p class="font-monospace user-select-all"><?= e($setupSecret) ?></p>
                            </div>
                        </div>
                        <form action="" method="post" class="mt-3" data-manage-validate="fieldCheckEnable">
                            <div class="formGrid">
                                <label class="col--2 inputLabel" for="rebind_confirm_code">新 App 驗證碼</label>
                                <div class="col--10">
                                    <input type="text" name="mfa_code" id="rebind_confirm_code" class="formInput" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="6 位數">
                                </div>
                            </div>
                            <div class="editView__footer">
                                <input type="hidden" name="csrf_token" value="<?= e((string)$csrf_token) ?>">
                                <button type="submit" name="Submit" value="確認重新綁定" class="btnStyle"><i class="bi bi-check-lg"></i> 確認重新綁定</button>
                                <button type="submit" name="Submit" value="取消重新綁定" class="btnStyle btnStyle--outline"><i class="bi bi-x-lg"></i> 取消</button>
                            </div>
                        </form>
                    </section>
                    <?php endif; ?>

                    <?php if ($schemaReady && $mfaEnabled && !$isRebindSetup && $setupSecret === ''): ?>
                    <section class="editView mt-3">
                        <h4 class="editView__sectionTitle">重新綁定（換手機／換 App）</h4>
                        <p class="text-muted">驗證身份後可掃描新的 QR Code，無須先停用雙因素驗證。</p>
                        <form action="" method="post" data-manage-validate="fieldCheckRebind">
                            <div class="formGrid">
                                <label class="col--2 inputLabel" for="rebind_pw">目前密碼</label>
                                <div class="col--10">
                                    <input type="password" name="rebind_pw" id="rebind_pw" class="formInput" autocomplete="current-password">
                                </div>
                            </div>
                            <div class="formGrid">
                                <label class="col--2 inputLabel" for="rebind_mfa_code">目前驗證碼</label>
                                <div class="col--10">
                                    <input type="text" name="rebind_mfa_code" id="rebind_mfa_code" class="formInput" maxlength="16" autocomplete="one-time-code" placeholder="App 6 位數或備用碼">
                                </div>
                            </div>
                            <div class="editView__footer">
                                <input type="hidden" name="csrf_token" value="<?= e((string)$csrf_token) ?>">
                                <button type="submit" name="Submit" value="開始重新綁定" class="btnStyle"><i class="bi bi-arrow-repeat"></i> 開始重新綁定</button>
                            </div>
                        </form>
                    </section>

                    <section class="editView mt-3">
                        <h4 class="editView__sectionTitle">停用雙因素驗證</h4>
                        <p class="text-muted">需輸入目前密碼與 App 驗證碼（或備用碼）方可停用。</p>
                        <form action="" method="post" data-manage-validate="fieldCheckDisable">
                            <div class="formGrid">
                                <label class="col--2 inputLabel" for="current_pw">目前密碼</label>
                                <div class="col--10">
                                    <input type="password" name="current_pw" id="current_pw" class="formInput" autocomplete="current-password">
                                </div>
                            </div>
                            <div class="formGrid">
                                <label class="col--2 inputLabel" for="disable_mfa_code">驗證碼</label>
                                <div class="col--10">
                                    <input type="text" name="mfa_code" id="disable_mfa_code" class="formInput" maxlength="16" autocomplete="one-time-code">
                                </div>
                            </div>
                            <div class="editView__footer">
                                <input type="hidden" name="csrf_token" value="<?= e((string)$csrf_token) ?>">
                                <button type="submit" name="Submit" value="停用" class="btnStyle btnStyle--outline"><i class="bi bi-shield-x"></i> 停用</button>
                            </div>
                        </form>
                    </section>
                    <?php endif; ?>

                    <div class="notes notes--lg mt-3">
                        <div class="notes__header"><i class="bi bi-info-circle notes__icon"></i> 說明</div>
                        <ul class="notes__list">
                            <li>TOTP 為時間型一次性密碼，每 30 秒更新，無須付費第三方 API。</li>
                            <li>Google Authenticator 與 Microsoft Authenticator 可掃描同一 QR Code，擇一使用即可。</li>
                            <li>啟用後，登入時須在密碼通過後輸入 App 驗證碼或備用碼。</li>
                            <li>換手機時可使用「重新綁定」，完成後請保存新的備用碼。</li>
                        </ul>
                    </div>
                    <div class="notes__spacer"></div>
<?php require_once '../_layout_body_close.php'; ?>
<?php require_once '../_in_code_bottom.php'; ?>
</body>
</html>
