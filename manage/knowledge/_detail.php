<?php
declare(strict_types=1);
/**
 * knowledge 新增／編輯表單（由 add.php、update.php 引入）
 */

knowledge_detail_export_vars();

$isAdd       = (int)($Update_PKey ?? 0) <= 0;
$classLabel  = (string)($Class_Name[1] ?? '分類');
$layer       = (int)($Layer ?? 1);
$extLower    = strtolower((string)($Ext[1] ?? ''));
$layout_page_title = (string)($layout_page_title ?? ($isAdd ? '新增' : '編輯'));
$managePhotoSlotMax = 1;
?>
<?php require_once '../_layout_head.php'; ?>
<?php echo script_open(); ?>
$(function() {
  function toggleInputs() {
    const isLink = $('#intLink1').prop('checked');
    if (isLink) {
      $('#strLink').prop('disabled', false);
      $('#Photo1').prop('disabled', true);
    } else {
      $('#strLink').prop('disabled', true);
      $('#Photo1').prop('disabled', false);
    }
  }
  $('#intLink1, #intLink2').on('change', toggleInputs);
  toggleInputs();
});

function fieldCheck0(theForm) {
  if (typeof loading === 'function') {
    loading(1);
  }
  const errors = [];
  const fields = [];

  const sortVal = $.trim($('#Sort').val());
  if (sortVal === '' || !/^\d+$/.test(sortVal)) {
    errors.push('順序不是數字');
    fields.push('Sort');
  }

  <?php if ($layer > 1): ?>
  if ($('#Class1').val() === '') {
    errors.push('<?php echo e($classLabel); ?>名稱請選擇');
    fields.push('Class1');
  }
  <?php endif; ?>

  if ($.trim($('#strName').val()) === '') {
    errors.push('標題空白');
    fields.push('strName');
  }

  const isLink = $('#intLink1').prop('checked');
  if (isLink) {
    if ($.trim($('#strLink').val()) === '') {
      errors.push('連結路徑空白');
      fields.push('strLink');
    }
  } else {
    const previewSrc = ($('#preview1').attr('src') || '').trim();
    const hasPreview = previewSrc !== '';
    const hasPrefile = $.trim($('#prefile1').text()) !== '';
    const hasFile = ($('#Photo1').val() || '').length > 0;
    if (!hasPreview && !hasPrefile && !hasFile) {
      errors.push('請選擇上傳檔案');
      fields.push('Photo1');
    }
  }

  if (errors.length > 0) {
    return window.manageFormValidationFail(errors, {
      focusField: fields[0],
      form: theForm
    });
  }
  return window.manageFormValidationOk(theForm);
}

$(function () {
  $('#delete1').on('click', function (e) {
    e.preventDefault();
    if (!confirm('確定刪除嗎？')) {
      return;
    }
    if (typeof del_file === 'function') {
      del_file(<?php echo (int)($PhotoS[1] ?? 0); ?>, 1, <?php echo js_str($extLower); ?>);
    }
  });
  $('#Photo1').on('change', function () {
    if (typeof checkFile === 'function') {
      checkFile('Photo1', 6000, 'file');
    }
  });
});
<?php echo script_close(); ?>
</head>

<?php require_once '../_layout_body_open.php'; ?>
                    <?php
                    if (!empty($breadcrumbs) && is_array($breadcrumbs)) {
                        require_once '../_breadcrumbs.php';
                    }
                    ?>

          <form action="addin.php" method="post" enctype="multipart/form-data" name="form1" id="form1" novalidate data-manage-validate="fieldCheck0">
            <div class="errorArea is-hidden" id="formErrorArea" aria-live="polite">
              <div class="errorArea__header">錯誤訊息</div>
              <div class="errorArea__body">
                <ul id="formErrorList"></ul>
              </div>
            </div>
            <div class="table-container">
              <table cellspacing="0" cellpadding="0" width="100%" border="0" class="detail">
                <tr>
                  <td>順序 <span class="inputLabel__required">*</span></td>
                  <td>
                    <input name="Sort" type="text" class="formInput" id="Sort" style="width:50px; text-align:center;" value="<?php echo e((string)($Sort ?? '')); ?>" maxlength="4" />
                    &nbsp;限輸入數字
                  </td>
                </tr>

                <?php if ($layer > 1) { ?>
                <tr>
                  <td><?php echo e($classLabel); ?>名稱 <span class="inputLabel__required">*</span></td>
                  <td>
                    <select name="Class1" id="Class1" class="m-select">
                      <option value="">請選擇</option>
                      <?php
                        $sql = 'SELECT PKey, strName FROM dbclass1 WHERE Module_PKey = :Module_PKey ORDER BY Sort';
                        $classRows = crud_fetch_all($sql, ['Module_PKey' => (int)($Module_PKey ?? 0)]);
                        foreach ($classRows as $cRow) {
                            $p = (int)($cRow['PKey'] ?? 0);
                            $n = (string)($cRow['strName'] ?? '');
                            $sel = ((int)$Class1 === $p) ? ' selected="selected"' : '';
                            echo '<option value="' . e((string)$p) . '"' . $sel . '>' . e($n) . '</option>';
                        }
                      ?>
                    </select>
                  </td>
                </tr>
                <?php } ?>

                <tr>
                  <td>標題 <span class="inputLabel__required">*</span></td>
                  <td>
                    <input type="text" name="strName" id="strName" value="<?php echo e($strName); ?>" class="formInput" maxlength="50" />
                  </td>
                </tr>

                <tr>
                  <td>內容</td>
                  <td>
                    <?php
                    $editorAiFieldId = 'Contents1';
                    require dirname(__DIR__) . '/_detail_ckeditor_ai_button.php';
                    ?>
                    <textarea name="Contents1" id="Contents1" class="ckeditor formInput"><?php echo e_editor_html($Contents1 ?? ''); ?></textarea>
                  </td>
                </tr>

                <tr>
                  <td>上傳檔案<span class="inputLabel__required">*</span></td>
                  <td>
                    <div class="photo-upload">
                      <div class="radio_box">
                        <input name="intLink" id="intLink1" type="radio" class="formInput" value="1"<?php echo ((int)$intLink === 1) ? ' checked="checked"' : ''; ?> />
                        <label for="intLink1"><span></span>連結路徑：</label>
                      </div>
                      <input name="strLink" type="text" id="strLink" class="formInput" maxlength="200" style="width:60%;" value="<?php echo e($strLink); ?>" />
                    </div>

                    <div class="photo-upload" style="margin-top:8px;">
                      <div class="radio_box">
                        <input name="intLink" id="intLink2" type="radio" class="formInput" value="2"<?php echo ((int)$intLink === 1) ? '' : ' checked="checked"'; ?> />
                        <label for="intLink2"><span></span>上傳檔案：</label>
                      </div>

                      <div class="upload-box" style="margin-bottom:0">
                        <div class="photo file">
                          <?php if (!empty($Photo[1])) { ?>
                          <?php
                            switch ($extLower) {
                              case 'jpg':
                              case 'jpeg':
                              case 'png':
                              case 'gif':
                                echo '<img id="preview1" style="max-width:150px; max-height:150px;" src="../../Upload/' . e($Photo[1]) . '">';
                                break;
                              case 'pdf':
                                echo '<span id="prefile1" class="fas fa-file-pdf">' . e($Photo[1]) . '</span>';
                                break;
                              case 'doc':
                              case 'docx':
                                echo '<span id="prefile1" class="fas fa-file-word">' . e($Photo[1]) . '</span>';
                                break;
                              case 'xls':
                              case 'xlsx':
                                echo '<span id="prefile1" class="fas fa-file-excel">' . e($Photo[1]) . '</span>';
                                break;
                              case 'ppt':
                              case 'pptx':
                                echo '<span id="prefile1" class="fas fa-file-powerpoint">' . e($Photo[1]) . '</span>';
                                break;
                              case 'rar':
                              case 'zip':
                                echo '<span id="prefile1" class="fas fa-file-archive">' . e($Photo[1]) . '</span>';
                                break;
                              default:
                                echo '<span id="prefile1" class="fas fa-file-alt">' . e($Photo[1]) . '</span>';
                                break;
                            }
                          ?>
                          <a href="#" id="delete1" class="uploadBox__delBtn">刪除檔案</a>
                          <?php } else { ?>
                          <span id="prefile1"></span>
                          <img id="preview1" style="max-width:150px; max-height:150px;" alt="">
                          <div id="size1"></div>
                          <?php } ?>
                        </div>

                        <div class="file-upload">
                          <label for="Photo1">
                            <input
                              name="Photo1"
                              type="file"
                              id="Photo1"
                              size="30"
                              accept="image/jpeg,image/png,image/gif,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation,application/zip,application/x-rar-compressed,text/plain"
                            />
                            選擇檔案
                          </label>
                          <input name="intType1" type="hidden" id="intType1" value="2" />
                        </div>
                      </div>
                    </div>

                    <ul class="set-tips">
                      <?php echo $remark_file1 . $remark_save2; ?>
                    </ul>
                  </td>
                </tr>

                <?php if (!$isAdd) { ?>
                <tr>
                  <td>修改日期</td>
                  <td><?php require_once '../_modify.php'; ?></td>
                </tr>
                <?php } ?>
              </table>
            </div>

            <?php
            if (empty($intSource)) {
                require_once '../_submit.php';
            }
            ?>
          </form>
<?php
require_once '../_layout_body_close.php';
require_once '../_in_code_bottom.php';
require_once '../_ckeditor.php';
?>
</body>
</html>
