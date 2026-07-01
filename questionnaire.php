<?php
declare(strict_types=1);

$pageName = 'p11';
$subPageName = 'p11_1';
require('_inc.php');
$WorkFile = basename(__FILE__);

$Module_PKey = frontend_module_pkey('question');
$Module_Name = $Array_MU_Name[$Module_PKey] ?? '問卷調查';
$Module_Link = $Array_MU_Link[$Module_PKey] ?? 'questionnaire.htm';
$page_link = 'questionnaire.htm';

$recaptcha_site_key = recaptcha_site_key();
$recaptcha_secret_key = recaptcha_secret_key();
$p9 = '隱私權政策';

$PKey = 0;
if (!empty($filter_array['PKey']) && is_numeric($filter_array['PKey'])) {
    $PKey = (int)$filter_array['PKey'];
}

$PDO_Cond = ' WHERE Upload = :Upload AND intLang = :intLang';
$Cond_Array = ['Upload' => 'Yes', 'intLang' => (int)$this_lang];
if ($PKey > 0) {
    $PDO_Cond .= ' AND PKey = :PKey';
    $Cond_Array['PKey'] = $PKey;
}
$sql = 'SELECT * FROM view_question' . $PDO_Cond . ' ORDER BY Sort, PKey';
if ($PKey === 0) {
    $sql .= ' LIMIT 1';
}
$rs = new recordset($sql, $Cond_Array);
if ($rs->eof) {
    $rs->close();
    $sqlFb = 'SELECT q.PKey, q.EMail, q.Upload,'
        . ' COALESCE(NULLIF(TRIM(ql.strName), \'\'), q.strName) AS strName'
        . ' FROM question AS q'
        . ' LEFT JOIN question_lang AS ql ON ql.Question_PKey = q.PKey AND ql.intLang = :intLang'
        . ' WHERE q.Upload = :Upload';
    if ($PKey > 0) {
        $sqlFb .= ' AND q.PKey = :PKey';
    }
    $sqlFb .= ' ORDER BY q.Sort, q.PKey';
    if ($PKey === 0) {
        $sqlFb .= ' LIMIT 1';
    }
    $rs = new recordset($sqlFb, $Cond_Array);
}
$SQL_Error = $rs->getErrorMessage();
if ($SQL_Error !== '') {
    $result = sql_error($sql . PHP_EOL . array_to_string($Cond_Array), $SQL_Error, $WorkFile, 'system', __FILE__, __LINE__);
    echo '<pre>';
    print_r($result);
    echo '</pre>';
    exit;
}

if ($rs->eof) {
    echo manage_inline_script(
        'alert(' . json_encode($lang_text['warn_data_not_found'][$this_lang] ?? '查無資料', JSON_UNESCAPED_UNICODE) . ');'
        . 'location.href=' . json_encode($web_root, JSON_UNESCAPED_SLASHES) . ';'
    );
    exit;
}

$Question_PKey = (int)$rs->field('PKey');
$Question_Name = (string)$rs->field('strName');
$seoTitle = frontend_lang_seo_title([
    'Title'   => (string)($rs->field('Title') ?? ''),
    'strName' => $Question_Name,
]);
$Send_Mail = (string)$rs->field('EMail');
$rs->close();

$bread_name = $bread_name ?? [];
$break_link = $break_link ?? [];
array_push($bread_name, e_attr($lang_text['home'][$this_lang] ?? '首頁'));
array_push($break_link, $web_root);
array_push($bread_name, e_attr($Module_Name));
array_push($break_link, $Module_Link);
array_push($bread_name, e_attr($Question_Name));
array_push($break_link, $page_link . '?PKey=' . $Question_PKey);

$Contents = [];
$sqlMsg = 'SELECT Sort, Contents FROM question_msg WHERE Question_PKey = :Question_PKey AND intLang = :intLang ORDER BY Sort';
$rsMsg = new recordset($sqlMsg, ['Question_PKey' => $Question_PKey, 'intLang' => (int)$this_lang]);
while (!$rsMsg->eof) {
    $i = (int)$rsMsg->field('Sort');
    $Contents[$i] = rwd_table((string)$rsMsg->field('Contents'), 1);
    $rsMsg->movenext();
}
$rsMsg->close();

$Photo1 = '';
$sqlImg = 'SELECT Forder, Photo1 FROM question_img'
    . ' WHERE Question_PKey = :Question_PKey AND Question_I_PKey = 0 AND Photo1 <> \'\' ORDER BY Sort LIMIT 1';
$rsImg = new recordset($sqlImg, ['Question_PKey' => $Question_PKey]);
if (!$rsImg->eof) {
    $photoPath = $upload_forder . $rsImg->field('Forder') . '/' . $rsImg->field('Photo1');
    if (is_file($photoPath)) {
        $Photo1 = $photoPath;
        $ext = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
        $webpPhoto = preg_replace('/\.[^.]+$/', '.webp', $photoPath);
        if ($ext !== 'gif' && is_file($webpPhoto)) {
            $Photo1 = $webpPhoto;
        }
    }
}
$rsImg->close();

if (!function_exists('questionnaire_resolve_class_name')) {
    function questionnaire_resolve_class_name(int $classPKey, int $lang, string $masterName, int $sort): string
    {
        $name = trim($masterName);
        if ($name !== '') {
            return $name;
        }

        $rsName = new recordset(
            'SELECT strName FROM question_class_lang'
            . ' WHERE Question_Class_PKey = :PKey AND intLang = :intLang LIMIT 1',
            ['PKey' => $classPKey, 'intLang' => $lang]
        );
        if (!$rsName->eof) {
            $name = trim((string)$rsName->field('strName'));
        }
        $rsName->close();

        if ($name === '') {
            $rsName = new recordset(
                'SELECT strName FROM view_question_class'
                . ' WHERE PKey = :PKey AND intLang = :intLang LIMIT 1',
                ['PKey' => $classPKey, 'intLang' => $lang]
            );
            if (!$rsName->eof) {
                $name = trim((string)$rsName->field('strName'));
            }
            $rsName->close();
        }

        return $name !== '' ? $name : ('區塊 ' . $sort);
    }
}

if (!function_exists('questionnaire_resolve_item_name')) {
    function questionnaire_resolve_item_name(int $itemPKey, int $lang, string $masterName, int $sort): string
    {
        $name = trim($masterName);
        if ($name !== '') {
            return $name;
        }

        $rsName = new recordset(
            'SELECT strName FROM question_itme_lang'
            . ' WHERE Question_Item_PKey = :PKey AND intLang = :intLang LIMIT 1',
            ['PKey' => $itemPKey, 'intLang' => $lang]
        );
        if (!$rsName->eof) {
            $name = trim((string)$rsName->field('strName'));
        }
        $rsName->close();

        return $name !== '' ? $name : ('題目 ' . $sort);
    }
}

if (!function_exists('questionnaire_class_rows')) {
    function questionnaire_class_rows(int $questionPKey, int $lang): array
    {
        $rows = [];
        if ($questionPKey <= 0) {
            return $rows;
        }

        $rs = new recordset(
            'SELECT PKey, Sort, strName FROM question_class'
            . ' WHERE Question_PKey = :Question_PKey ORDER BY Sort',
            ['Question_PKey' => $questionPKey]
        );
        while (!$rs->eof) {
            $classPKey = (int)$rs->field('PKey');
            $sort = (int)$rs->field('Sort');
            $rows[] = [
                'PKey'    => $classPKey,
                'Sort'    => $sort,
                'strName' => questionnaire_resolve_class_name(
                    $classPKey,
                    $lang,
                    (string)$rs->field('strName'),
                    $sort
                ),
            ];
            $rs->movenext();
        }
        $rs->close();

        if ($rows === []) {
            $rs = new recordset(
                'SELECT PKey, Sort, strName FROM view_question_class'
                . ' WHERE Question_PKey = :Question_PKey AND intLang = :intLang ORDER BY Sort',
                ['Question_PKey' => $questionPKey, 'intLang' => $lang]
            );
            while (!$rs->eof) {
                $rows[] = [
                    'PKey'    => (int)$rs->field('PKey'),
                    'Sort'    => (int)$rs->field('Sort'),
                    'strName' => trim((string)$rs->field('strName')),
                ];
                $rs->movenext();
            }
            $rs->close();
        }

        return $rows;
    }
}

if (!function_exists('questionnaire_item_recordset')) {
    function questionnaire_item_recordset(int $questionPKey, int $classPKey, int $lang, int $classSort = 0): recordset
    {
        unset($questionPKey, $lang);

        $rs = new recordset(
            'SELECT * FROM question_item WHERE Question_D_PKey = :Class_PKey ORDER BY Sort',
            ['Class_PKey' => $classPKey]
        );

        if ($rs->record_count === 0 && $classSort > 0 && $classSort !== $classPKey) {
            $rs->close();
            $rs = new recordset(
                'SELECT * FROM question_item WHERE Question_D_PKey = :Class_Sort ORDER BY Sort',
                ['Class_Sort' => $classSort]
            );
        }

        return $rs;
    }
}

if (!function_exists('questionnaire_item_required_row')) {
    /** @param array<string,mixed> $item */
    function questionnaire_item_required_row(array $item): bool
    {
        return ((string)($item['Must'] ?? '')) === 'Yes';
    }
}

if (!function_exists('questionnaire_load_sections')) {
    /** @return list<array{PKey:int,Sort:int,strName:string,items:list<array<string,mixed>>}> */
    function questionnaire_load_sections(int $questionPKey, int $lang): array
    {
        $sections = [];
        if ($questionPKey <= 0) {
            return [];
        }

        $rsClass = new recordset(
            'SELECT PKey, Sort, strName FROM question_class'
            . ' WHERE Question_PKey = :Question_PKey ORDER BY Sort',
            ['Question_PKey' => $questionPKey]
        );
        while (!$rsClass->eof) {
            $classPKey = (int)$rsClass->field('PKey');
            $classSort = (int)$rsClass->field('Sort');
            $section = [
                'PKey'    => $classPKey,
                'Sort'    => $classSort,
                'strName' => questionnaire_resolve_class_name(
                    $classPKey,
                    $lang,
                    (string)$rsClass->field('strName'),
                    $classSort
                ),
                'items'   => [],
            ];

            $rsItem = questionnaire_item_recordset($questionPKey, $classPKey, $lang, $classSort);
            while (!$rsItem->eof) {
                $itemPKey = (int)$rsItem->field('PKey');
                $itemSort = (int)$rsItem->field('Sort');
                $section['items'][] = [
                    'PKey'    => $itemPKey,
                    'Sort'    => $itemSort,
                    'Qtype'   => (int)$rsItem->field('Qtype'),
                    'Other'   => (string)$rsItem->field('Other'),
                    'Must'    => (string)$rsItem->field('Must'),
                    'strName' => questionnaire_resolve_item_name(
                        $itemPKey,
                        $lang,
                        (string)$rsItem->field('strName'),
                        $itemSort
                    ),
                ];
                $rsItem->movenext();
            }
            $rsItem->close();

            $sections[] = $section;
            $rsClass->movenext();
        }
        $rsClass->close();

        return $sections;
    }
}

if (!function_exists('questionnaire_item_required')) {
    function questionnaire_item_required(recordset $rs): bool
    {
        $must = (string)$rs->field('Must');
        if ($must === 'Yes') {
            return true;
        }

        return (string)$rs->field('Required') === 'Yes';
    }
}

$questionnaire_sections = questionnaire_load_sections($Question_PKey, (int)$this_lang);

if (!empty($filter_array['Send']) && (string)$filter_array['Send'] === 'OK') {
    $csrfPost = (string)($filter_array['csrf'] ?? '');
    if ($csrfPost === '' || !hash_equals($csrf_token, $csrfPost)) {
        location_href('./');
    }

    $recaptchaOk = ($recaptcha_secret_key === '');
    if ($recaptcha_secret_key !== '') {
        $resToken = trim((string)($filter_array['g-recaptcha-response'] ?? ''));
        if ($resToken !== '') {
            $remoteIP = (string)($_SERVER['REMOTE_ADDR'] ?? '');
            $verify = recaptcha_siteverify($recaptcha_secret_key, $resToken, $remoteIP);
            if (!$verify['success'] && $remoteIP !== '') {
                $verify = recaptcha_siteverify($recaptcha_secret_key, $resToken, '');
            }
            $recaptchaOk = !empty($verify['success']);
        }
    }

    $MSG = '';
    if (empty($filter_array['strName']) || !is_string($filter_array['strName'])) {
        $MSG .= "【姓名】空白\\n";
    }
    if (empty($filter_array['Mobile']) || !is_string($filter_array['Mobile'])) {
        $MSG .= "【電話】空白\\n";
    }
    if (!CheckMail((string)($filter_array['EMail'] ?? '')) || !is_string($filter_array['EMail'] ?? null)) {
        $MSG .= "【電子信箱】空白或格式錯誤\\n";
    }
    if ($recaptcha_secret_key !== '' && !$recaptchaOk) {
        $MSG .= "【我不是機器人】請點選\\n";
    }

    if ($MSG === '') {
        $db = sql_conn();
        if ($db === null) {
            echo '資料庫連線失敗。';
            exit;
        }
        $pdo = new dbPDO($db);

        $data_array = [
            'Question_PKey' => SqlFilter($Question_PKey, 'int'),
            'Question_Name' => SqlFilter($Question_Name, 'tab'),
            'strName' => SqlFilter((string)$filter_array['strName'], 'tab'),
            'Mobile' => SqlFilter((string)$filter_array['Mobile'], 'tab'),
            'EMail' => SqlFilter((string)$filter_array['EMail'], 'tab'),
            'Birthday' => SqlFilter((string)($filter_array['Birthday'] ?? ''), 'tab'),
            'UserIP' => UserIP(),
            'dtDate' => date('Y-m-d H:i:s'),
        ];
        $pdo->insert('question_report_p', $data_array);
        $Report_PKey = (int)$pdo->getLastId();
        $SQL_U = $pdo->getLastSql() . "\n" . array_to_string($data_array) . 'PKey=' . $Report_PKey;
        $SQL_Error = $pdo->getErrorMessage();
        if ($SQL_Error !== '') {
            sql_error($SQL_U, $SQL_Error, $WorkFile, 'system', __FILE__, __LINE__);
            echo e($SQL_Error);
            exit;
        }
        manage_history($Module_PKey, $Module_Name, $SQL_U, $WorkFile, 'system', '新增問卷主檔');

        $sqlClass = 'SELECT PKey, Sort FROM question_class WHERE Question_PKey = :Question_PKey ORDER BY Sort';
        $rsClass = new recordset($sqlClass, ['Question_PKey' => $Question_PKey]);
        $q = 0;
        while (!$rsClass->eof) {
            $q++;
            $classPKey = (int)$rsClass->field('PKey');
            $classSort = (int)$rsClass->field('Sort');
            $sqlItem = 'SELECT * FROM question_item WHERE Question_D_PKey = :Question_D_PKey ORDER BY Sort';
            $rsItem = new recordset($sqlItem, ['Question_D_PKey' => $classPKey]);
            if ($rsItem->record_count === 0 && $classSort > 0 && $classSort !== $classPKey) {
                $rsItem->close();
                $sqlItem = 'SELECT * FROM question_item WHERE Question_D_PKey = :Class_Sort ORDER BY Sort';
                $rsItem = new recordset($sqlItem, ['Class_Sort' => $classSort]);
            }
            while (!$rsItem->eof) {
                $a = (int)$rsItem->field('Sort');
                $answer = '';
                switch ((int)$rsItem->field('Qtype')) {
                    case 1:
                        $answer = (string)($filter_array['Q_' . $q . '_' . $a] ?? '');
                        if (!empty($filter_array['Q_' . $q . '_' . $a . '_Memo'])) {
                            $answer .= '：' . (string)$filter_array['Q_' . $q . '_' . $a . '_Memo'];
                        }
                        break;
                    case 2:
                        $aSort = [];
                        $names = [];
                        if (!empty($filter_array['Q' . $q . '_' . $a . '_Sort'])) {
                            $aSort = explode(',', (string)$filter_array['Q' . $q . '_' . $a . '_Sort']);
                        }
                        foreach ($aSort as $sortIdx) {
                            $sortIdx = trim($sortIdx);
                            if ($sortIdx === '') {
                                continue;
                            }
                            $key = 'Q_' . $q . '_' . $a . '_' . $sortIdx;
                            if (!empty($filter_array[$key])) {
                                $ans = (string)$filter_array[$key];
                                $memoKey = $key . '_Memo';
                                if (!empty($filter_array[$memoKey])) {
                                    $ans .= '：' . (string)$filter_array[$memoKey];
                                }
                                $names[] = $ans;
                            }
                        }
                        $answer = implode(',', $names);
                        break;
                    default:
                        if (!empty($filter_array['Q_' . $q . '_' . $a])) {
                            $answer = (string)$filter_array['Q_' . $q . '_' . $a];
                        }
                        break;
                }

                $detail = [
                    'Report_PKey' => SqlFilter($Report_PKey, 'int'),
                    'Question_D_PKey' => $rsItem->field('Question_D_PKey'),
                    'Question_I_PKey' => $rsItem->field('PKey'),
                    'Step' => $q,
                    'Sort' => $rsItem->field('Sort'),
                    'Qtype' => $rsItem->field('Qtype'),
                    'strName' => SqlFilter((string)$rsItem->field('strName'), 'tab'),
                    'Contents' => SqlFilter($answer, 'tab'),
                    'dtDate' => date('Y-m-d H:i:s'),
                ];
                $pdo->insert('question_report_d', $detail);
                $SQL_Error = $pdo->getErrorMessage();
                if ($SQL_Error !== '') {
                    sql_error($pdo->getLastSql(), $SQL_Error, $WorkFile, 'system', __FILE__, __LINE__);
                    echo e($SQL_Error);
                    exit;
                }
                $rsItem->movenext();
            }
            $rsItem->close();
            $rsClass->movenext();
        }
        $rsClass->close();

        if ($Report_PKey > 0 && $Send_Mail !== '') {
            $mailSubject = (string)$filter_array['strName'] . '-問卷/滿意度調查';
            $BODY = '<p>管理者您好，</p>'
                . '<p>「' . e((string)$filter_array['strName']) . '」於「' . e(date('Y-m-d H:i:s')) . '」完成填寫【'
                . e($Question_Name) . '】問卷，請至後端管理系統查看，謝謝！</p>';
            SendMail($m_title, $Send_Mail, $m_title, $m_from_mail, $mailSubject, $BODY);
        }

        echo manage_inline_script(
            'alert(' . json_encode('感謝您的填寫。', JSON_UNESCAPED_UNICODE) . ');'
            . 'location.href=' . json_encode($web_root, JSON_UNESCAPED_SLASHES) . ';'
        );
        exit;
    }

    echo manage_inline_script(
        'alert(' . json_encode(($lang_text['warn_msg_error'][$this_lang] ?? '發生錯誤，請填寫下列欄位') . "\n" . $MSG, JSON_UNESCAPED_UNICODE) . ');'
        . 'history.back();'
    );
    exit;
}
?>
<!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>
<head>
<?php require('_in_code_head.php'); ?>
<?php require('_in_javascript.php'); ?>
<?php echo recaptcha_script_tag(); ?>
<?php echo script_open(); ?>
$(function () {
  $('#btnSubmitQuestionnaire').on('click', function () {
    if (typeof loading === 'function') {
      loading(1);
    }
    var errors = [];
    var fields = [];
    var formEl = document.getElementById('form1');

    function isMobile(mobile) {
      return /^09\d{8}$/.test(String(mobile || '').trim());
    }
    function isValidDate(v) {
      return String(v || '').trim() !== '';
    }

    if ($('#strName').val().trim() === '') {
      errors.push(<?php echo js_str($lang_text['chk_name'][$this_lang] ?? ''); ?>);
      fields.push('strName');
    }
    var mobileVal = $('#Mobile').val().trim();
    if (mobileVal === '') {
      errors.push(<?php echo js_str('請輸入【手機】'); ?>);
      fields.push('Mobile');
    } else if (!isMobile(mobileVal)) {
      errors.push(<?php echo js_str('請輸入正確的【手機】'); ?>);
      fields.push('Mobile');
    }
    var emailVal = $('#EMail').val().trim();
    if (emailVal === '') {
      errors.push(<?php echo js_str($lang_text['chk_email'][$this_lang] ?? ''); ?>);
      fields.push('EMail');
    } else if (typeof isEmail === 'function' && !isEmail(emailVal)) {
      errors.push(<?php echo js_str($lang_text['chk_email_rule'][$this_lang] ?? ''); ?>);
      fields.push('EMail');
    }

<?php
$qJs = 0;
foreach ($questionnaire_sections as $classRowJs) {
    $qJs++;
    foreach ($classRowJs['items'] as $itemRowJs) {
        if (!questionnaire_item_required_row($itemRowJs)) {
            continue;
        }
        $aJs = (int)$itemRowJs['Sort'];
        $qtypeJs = (int)$itemRowJs['Qtype'];
        $itemTitleJs = (string)$itemRowJs['strName'];
        if ($itemTitleJs === '') {
            $itemTitleJs = '題目 ' . $aJs;
        }
        $chkSelect = '請勾選【' . $itemTitleJs . '】';
        $chkInput = '請輸入【' . $itemTitleJs . '】';
        if ($qtypeJs === 1 || $qtypeJs === 2) {
            echo "    if ($('#Q{$qJs}_{$aJs}_Sort').val() === '') {\n";
            echo '      errors.push(' . js_str($chkSelect) . ");\n";
            echo "      fields.push('Q_{$qJs}_{$aJs}_1');\n";
            echo "    }\n";
        } elseif ($qtypeJs === 5) {
            echo "    if (!isValidDate($('#Q_{$qJs}_{$aJs}').val())) {\n";
            echo '      errors.push(' . js_str($chkInput) . ");\n";
            echo "      fields.push('Q_{$qJs}_{$aJs}');\n";
            echo "    }\n";
        } else {
            echo "    if ($('#Q_{$qJs}_{$aJs}').val().trim() === '') {\n";
            echo '      errors.push(' . js_str($chkInput) . ");\n";
            echo "      fields.push('Q_{$qJs}_{$aJs}');\n";
            echo "    }\n";
        }
    }
}
?>

    if (!$('#Agree').prop('checked')) {
      errors.push(<?php echo js_str('請勾選同意隱私權條款'); ?>);
      fields.push('Agree');
    }

<?php if ($recaptcha_site_key !== '') { ?>
    if (typeof grecaptcha !== 'undefined' && typeof grecaptcha.getResponse === 'function') {
      if (grecaptcha.getResponse().length === 0) {
        errors.push(<?php echo js_str($lang_text['chk_google_code'][$this_lang] ?? ''); ?>);
      }
    }
<?php } ?>

    if (errors.length) {
      return window.manageFormValidationFail(errors, {
        focusField: fields[0] || '',
        errorFields: fields,
        form: formEl
      });
    }

    window.manageFormValidationOk(formEl);
    $('#Send').val('OK');
    $('#form1').submit();
    if (typeof loading === 'function') {
      loading(1);
    }
  });
});
<?php echo script_close(); ?>
</head>
<body <?php echo $bodytxt ?? ''; ?>>
<?php require('_header.php'); ?>
<?php require('_banner.php'); ?>

<main class="pgContent blockHeight--quest">
    <div class="container container--small container--formWrap">
        <section class="container__inner">
            <div class="qzTitleBox">
                <h2 class="mainTitle mainTitle--left">
                    <span class="mainTitle__mj"><?php echo e($Question_Name); ?></span>
                </h2>
                <?php if ($Photo1 !== '') { ?>
                <figure class="qzTitleBox__bn">
                    <img src="<?php echo e_attr($web_root . $Photo1); ?>" class="qzTitleBox__bn__pic img-fluid" loading="lazy" alt="<?php echo e_attr($Question_Name); ?>">
                </figure>
                <?php } ?>
                <?php if (!empty($Contents[1])) { ?>
                <article class="qzTitleBox__brief tx01"><?php echo frontend_render_html((string)$Contents[1]); ?></article>
                <?php } ?>
            </div>

            <div class="qzMainZone">
                <form action="" name="form1" id="form1" method="post" class="qzMainZone__form">
                    <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite" tabindex="-1">
                        <div class="errorArea__header"><?php echo e(($this_lang == 2) ? 'Error messages' : '錯誤訊息'); ?></div>
                        <div class="errorArea__body">
                            <ul id="formErrorList"></ul>
                        </div>
                    </div>
                    <div class="formMain">
                        <div class="qzSectionTitle">
                            <h3 class="qzSectionTitle__name">填寫人個人資料</h3>
                            <span class="qzSectionTitle__right red">星號 * 為必填欄位</span>
                        </div>
                        <div class="formMain__block formMain__block--grid">
                            <div class="inputGroup">
                                <div class="inputGroup__item"><span class="subColor">*</span>姓名</div>
                                <div class="inputGroup__box">
                                    <div class="inputGroup__box__input">
                                        <input name="strName" id="strName" type="text" class="input" placeholder="請輸入全名" value="">
                                    </div>
                                    <small id="Name_txt" class="errorTxt"></small>
                                </div>
                            </div>
                            <div class="inputGroup">
                                <div class="inputGroup__item"><span class="subColor">*</span>手機</div>
                                <div class="inputGroup__box">
                                    <div class="inputGroup__box__input">
                                        <input name="Mobile" id="Mobile" type="tel" inputmode="numeric" class="input no-spinner" placeholder="請輸入10碼的數字" value="">
                                    </div>
                                    <small id="Mobile_txt" class="errorTxt"></small>
                                </div>
                            </div>
                            <div class="inputGroup">
                                <div class="inputGroup__item"><span class="subColor">*</span>電子信箱</div>
                                <div class="inputGroup__box">
                                    <div class="inputGroup__box__input">
                                        <input name="EMail" id="EMail" type="email" class="input" placeholder="電子信箱" value="">
                                    </div>
                                    <small id="EMail_txt" class="errorTxt"></small>
                                </div>
                            </div>
                            <div class="inputGroup">
                                <div class="inputGroup__item">出生年月日</div>
                                <div class="inputGroup__box">
                                    <div class="inputGroup__box__input">
                                        <input name="Birthday" id="Birthday" type="date" class="input" value="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    $q = 0;
                    foreach ($questionnaire_sections as $classRow) {
                        $q++;
                        $className = (string)$classRow['strName'];
                    ?>
                    <div class="formMain">
                        <div class="qzSectionTitle">
                            <h3 class="qzSectionTitle__name"><?php echo e($className); ?></h3>
                        </div>
                        <div class="formMain__block">
                            <?php
                            foreach ($classRow['items'] as $itemRow) {
                                $a = (int)$itemRow['Sort'];
                                $itemPKey = (int)$itemRow['PKey'];
                                $qtype = (int)$itemRow['Qtype'];
                                $qtypeLabel = '';
                                switch ($qtype) {
                                    case 1:
                                        $qtypeLabel = '<span class="formTpye">單選</span>';
                                        break;
                                    case 2:
                                        $qtypeLabel = '<span class="formTpye">複選題</span>';
                                        break;
                                }
                                $itemName = '';
                                if (questionnaire_item_required_row($itemRow)) {
                                    $itemName .= '<span class="subColor">*</span>';
                                }
                                $itemName .= e((string)$itemRow['strName']) . $qtypeLabel;

                                $rsItemList = new recordset(
                                    'SELECT * FROM question_item WHERE PKey = :PKey LIMIT 1',
                                    ['PKey' => $itemPKey]
                                );
                                if ($rsItemList->eof) {
                                    $rsItemList->close();
                                    continue;
                                }

                                $answerCount = 0;
                                $sqlAnsCnt = 'SELECT COUNT(PKey) AS Total FROM question_answer WHERE Question_I_PKey = :Question_I_PKey';
                                $rsAnsCnt = new recordset($sqlAnsCnt, ['Question_I_PKey' => $itemPKey]);
                                if (!$rsAnsCnt->eof) {
                                    $answerCount = (int)$rsAnsCnt->field('Total');
                                }
                                $rsAnsCnt->close();

                                $itemPhoto = '';
                                $sqlItemImg = 'SELECT Forder, Photo1 FROM question_img'
                                    . ' WHERE Question_I_PKey = :Question_I_PKey AND Photo1 <> \'\' ORDER BY Sort LIMIT 1';
                                $rsItemImg = new recordset($sqlItemImg, ['Question_I_PKey' => $itemPKey]);
                                if (!$rsItemImg->eof) {
                                    $itemPhotoPath = $upload_forder . $rsItemImg->field('Forder') . '/' . $rsItemImg->field('Photo1');
                                    if (is_file($itemPhotoPath)) {
                                        $itemPhoto = $itemPhotoPath;
                                    }
                                }
                                $rsItemImg->close();
                                $answer = $answerCount;
                                $rs1 = $rsItemList;

                                $answerFile = __DIR__ . '/_answer' . $qtype . '.php';
                                if (!is_file($answerFile)) {
                                    $rsItemList->close();
                                    continue;
                                }
                            ?>
                            <div class="inputGroup">
                                <div class="inputGroup__item"><?php echo $itemName; ?></div>
                                <?php if ($itemPhoto !== '') { ?>
                                <figure>
                                    <img src="<?php echo e_attr($web_root . $itemPhoto); ?>" alt="<?php echo e_attr((string)$itemRow['strName']); ?>" class="img-fluid" loading="lazy">
                                </figure>
                                <?php } ?>
                                <?php require $answerFile; ?>
                            </div>
                            <?php
                                $rsItemList->close();
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                    }
                    ?>

                    <div class="formMain">
                        <div class="inputGroup">
                            <div class="inputGroup__box">
                                <div class="form-check">
                                    <input name="Agree" type="checkbox" class="form-check-input" id="Agree">
                                    <label for="Agree">我已詳閱「<button type="button" class="btn btn-link p-0 align-baseline" data-popup-trigger="popUp_privacy"><?php echo e($p9); ?></button>」，並同意將個人資料提供予本網站之聲明範圍內使用。</label>
                                </div>
                                <small id="Agree_txt" class="errorTxt"></small>
                            </div>
                        </div>
                        <div class="inputGroup">
                            <div class="inputGroup__item"><?php if ($recaptcha_site_key !== '') { ?><span class="subColor">*</span><?php } ?>驗証碼</div>
                            <div class="inputGroup__box formGroup__item--recaptcha">
                                <?php if ($recaptcha_site_key !== '') { ?>
                                <div class="g-recaptcha" data-sitekey="<?php echo e_attr($recaptcha_site_key); ?>"></div>
                                <small id="g-recaptcha_txt" class="errorTxt"></small>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="btnWrap">
                        <button type="button" class="btnStyle" id="btnSubmitQuestionnaire"><span class="txt">確認送出</span></button>
                    </div>
                    <input type="hidden" name="Send" id="Send" value="">
                    <input type="hidden" name="PKey" id="PKey" value="<?php echo (int)$Question_PKey; ?>">
                    <input type="hidden" name="csrf" id="csrf" value="<?php echo e_attr($csrf_token); ?>">
                </form>
            </div>

            <?php if (!empty($Contents[2])) { ?>
            <div class="qzEnd">
                <article class="tx01"><?php echo frontend_render_html((string)$Contents[2]); ?></article>
            </div>
            <?php } ?>
        </section>
    </div>
</main>

<div id="popUp_privacy" class="popUpWrap" role="dialog" aria-labelledby="privacyTitle" aria-hidden="true">
    <div class="popUpWrap__inner">
        <a href="#" class="close" data-close title="關閉"></a>
        <h3 id="privacyTitle" class="mb-3"><?php echo e($p9); ?></h3>
        <?php require '_privacy.php'; ?>
    </div>
</div>

<?php require('_footer.php'); ?>
<?php require('_in_code_bottom.php'); ?>
</body>
</html>
