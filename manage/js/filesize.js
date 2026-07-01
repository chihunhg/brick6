/* filesize.js — upload preview / size check / delete helper (jQuery required) */

/* ---------- helpers ---------- */
/** 由 input id 取得 preview 序號（Photo1、fileInputPreview1 → 1） */
function fileFieldIndex(field) {
  var m = String(field || '').match(/(?:^Photo|^fileInputPreview)(\d+)$/i);
  return m ? m[1] : String(field || '').replace(/^Photo/i, '');
}

function format_float(num, pos) {
  var size = Math.pow(10, pos);
  return Math.round(num * size) / size;
}

// 四捨五入（len 位小數），例如 GetRound(2.999, 2) => 3.00
function GetRound(num, len) {
  return Math.round(num * Math.pow(10, len)) / Math.pow(10, len);
}

// 檔案大小提示訊息
function Message(fileKB, limitKB, type) {
  var file  = (fileKB  > 999) ? (GetRound(fileKB / 1000, 2) + ' MB') : (fileKB  + ' KB');
  var limit = (limitKB > 999) ? (GetRound(limitKB / 1000, 2) + ' MB') : (limitKB + ' KB');
  var msg   = (type === 2)
    ? ('上傳檔案錯誤，檔案大小請小於 ' + limit)
    : ('上傳圖片錯誤，圖片檔案大小請小於 ' + limit);
  return msg;
}

// 重置 <input type="file">（保留屬性；change 請用 document 委派綁定）
function resetFileInput($file){
  var $new = $file.clone(false);
  $new.val('');
  $file.after($new);
  $file.remove();
}

var __uploadPreviewGen = {};

/** 記錄編輯頁既有預覽，驗證失敗時可還原 */
function snapshotUploadSlotPreview(num) {
  num = String(num || '');
  if (!num) {
    return;
  }
  var $img = $('#preview' + num);
  if ($img.length && !$img.attr('data-original-src')) {
    var src = ($img.attr('src') || '').trim();
    if (src) {
      $img.attr('data-original-src', src);
    }
  }
  var $prefile = $('#prefile' + num);
  if ($prefile.length && !$prefile.attr('data-original-html')) {
    var html = $.trim($prefile.html());
    if (html !== '') {
      $prefile.attr('data-original-html', html);
      $prefile.attr('data-original-class', $prefile.attr('class') || '');
      $prefile.attr('data-original-style', $prefile.attr('style') || '');
    }
  }
}

function initUploadSlotPreviewSnapshots() {
  $('[id^="preview"]').each(function () {
    var m = String(this.id || '').match(/^preview(\d+)$/i);
    if (m) {
      snapshotUploadSlotPreview(m[1]);
    }
  });
  $('[id^="FileSize"]').each(function () {
    var m = String(this.id || '').match(/^FileSize(\d+)$/i);
    if (m && !this.getAttribute('data-original-value')) {
      this.setAttribute('data-original-value', this.value || '');
    }
  });
}

/** 驗證失敗時清空預覽，避免使用者誤以為已選檔成功 */
function clearUploadSlotPreview(num) {
  num = String(num || '');
  if (!num) {
    return;
  }
  __uploadPreviewGen[num] = (__uploadPreviewGen[num] || 0) + 1;

  var $img = $('#preview' + num);
  if ($img.length) {
    var origSrc = ($img.attr('data-original-src') || '').trim();
    if (origSrc) {
      $img.attr('src', origSrc).show().css({ display: 'block', visibility: 'visible' });
    } else {
      $img.attr('src', '').hide().css({ display: 'none', visibility: 'hidden' });
    }
  }

  var $prefile = $('#prefile' + num);
  if ($prefile.length) {
    var origHtml = $prefile.attr('data-original-html');
    if (origHtml != null && origHtml !== '') {
      $prefile.attr('class', $prefile.attr('data-original-class') || '');
      $prefile.attr('style', $prefile.attr('data-original-style') || '');
      $prefile.html(origHtml).show();
    } else {
      $prefile
        .removeClass('fas fa-file-pdf fa-file-word fa-file-excel fa-file-powerpoint fa-file-archive fa-file-alt')
        .text('')
        .hide();
    }
  }

  var sizeEl = document.getElementById('size' + num);
  if (sizeEl) {
    sizeEl.textContent = '';
  }

  var fileSizeInput = document.getElementById('FileSize' + num);
  if (fileSizeInput) {
    var origSize = fileSizeInput.getAttribute('data-original-value');
    fileSizeInput.value = origSize != null ? origSize : '';
  }

  var hintEl = document.getElementById('Photo' + num + '_txt');
  if (hintEl) {
    hintEl.textContent = '';
  }
}

/* ---------- preview ---------- */
function preview(input, num) {
  if (!(input && input.files && input.files[0])) return;

  num = String(num || '');
  var gen = (__uploadPreviewGen[num] = (__uploadPreviewGen[num] || 0) + 1);

  var reader  = new FileReader();
  var file    = input.files[0];
  var imgtype = file.type;
  var name    = file.name;

  reader.onload = function (e) {
    if (__uploadPreviewGen[num] !== gen) {
      return;
    }
    var $img  = $('#preview' + num);
    var $file = $('#prefile' + num);

    if (!$img.length) {
      return;
    }

    // 先清乾淨 icon 與文字
    $file.removeClass('fas fa-file-pdf fa-file-word fa-file-excel fa-file-archive fa-file-alt').text('');

    switch (imgtype) {
      case 'image/jpeg':
      case 'image/gif':
      case 'image/png':
        $img.attr('src', e.target.result)
          .css({ display: 'block', visibility: 'visible', maxWidth: '150px', maxHeight: '150px' })
          .show();
        $file.hide();
        break;
      case 'application/pdf':
        $file.addClass('fas fa-file-pdf').text(name).show();
        $img.hide();
        break;
      case 'application/msword':
      case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
        $file.addClass('fas fa-file-word').text(name).show();
        $img.hide();
        break;
      case 'application/vnd.ms-excel':
      case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
        $file.addClass('fas fa-file-excel').text(name).show();
        $img.hide();
        break;
      case 'application/octet-stream':
      case 'application/zip':
        $file.addClass('fas fa-file-archive').text(name).show();
        $img.hide();
        break;
      default:
        $file.addClass('fas fa-file-alt').text(name).show();
        $img.hide();
        break;
    }

    // 可視需要顯示大小
    // var KB = format_float(file.size / 1024, 2);
    // $('#size' + num).text('檔案大小：' + KB + ' KB');

    // 依專案需求自動勾選
    if ($("#img-upl-7").length)   $("#img-upl-7").prop("checked", true);
    if ($("#isShow2").length)     $("#isShow2").prop("checked", true);
    if ($("#img-upl-bka3").length)$("#img-upl-bka3").prop("checked", true);
    if ($("#intLink2").length)    $("#intLink2").prop("checked", true);
  };

  reader.readAsDataURL(file);
}

/* ---------- size & type checks ---------- */
function chkSize(field, sizeKB, type) {
  var f = $('#' + field).get(0);
  if (f && f.files && f.files[0]) {
    var fileSize = f.files[0].size;        // bytes
    var limit    = 1024 * sizeKB;          // KB -> bytes
    if (fileSize > limit) {
      return Message(parseInt(fileSize / 1024, 10), parseInt(limit / 1024, 10), type);
    }
  }
  return '';
}

// filetype: 'img' | 'file' | 'pdf' | 'csv' | 'excel'
function checkFile(field, sizeKB, filetype) {
  var ImageTypeLimit = 'JPG,JPEG,GIF,PNG';
  var FileTypeLimit  = '.JPG.GIF.PNG.PDF.DOC.DOCX.PPT.XLS.XLSX.TXT.ZIP.RAR';
  var PdfTypeLimit   = '.PDF';
  var CsvLimit       = '.CSV.TXT.XLS.XLSX';
  var ExcelLimit     = 'XLS.XLSX';

  var filePath = $('#' + field).val() || '';
  var parts    = filePath.split('.');
  var ext      = parts.length > 1 ? parts.pop() : '';
  var upperExt = ext.toUpperCase();

  var errTxt = '';
  switch (filetype) {
    case 'img':
      if (ImageTypeLimit.toUpperCase().indexOf(upperExt) < 0) {
        errTxt = '※ 圖檔格式只接受 ' + ImageTypeLimit + '，請重新選擇檔案。';
      } else {
        errTxt = chkSize(field, sizeKB, 1);
      }
      break;

    case 'file':
      if (FileTypeLimit.toUpperCase().indexOf('.' + upperExt) < 0) {
        errTxt = '※ 檔案上傳限制 ' + FileTypeLimit + '，請重新選擇檔案。';
      } else {
        errTxt = chkSize(field, sizeKB, 2);
      }
      break;

    case 'pdf':
      if (PdfTypeLimit.toUpperCase().indexOf('.' + upperExt) < 0) {
        errTxt = '※ 檔案上傳限制 ' + PdfTypeLimit + '，請重新選擇檔案。';
      } else {
        errTxt = chkSize(field, sizeKB, 2);
      }
      break;

    case 'csv':
      if (CsvLimit.toUpperCase().indexOf('.' + upperExt) < 0) {
        errTxt = '※ 檔案上傳限制 ' + CsvLimit + '，請重新選擇檔案。';
      } else {
        errTxt = chkSize(field, sizeKB, 2);
      }
      break;

    case 'excel':
      if (ExcelLimit.toUpperCase().indexOf(upperExt) < 0) {
        errTxt = '匯入檔案格式請使用 .xls 或 .xlsx 格式';
      } else {
        errTxt = chkSize(field, sizeKB, 2);
      }
      break;
  }

  if (errTxt) {
    clearUploadSlotPreview(fileFieldIndex(field));
    if (typeof window.manageFormValidationFail === 'function') {
      window.manageFormValidationFail([errTxt], {
        focusField: field,
        stopLoading: false
      });
    }
    resetFileInput($('#' + field));
    $('#' + field).focus();
    return false;
  } else {
    var hintEl = document.getElementById(field + '_txt');
    if (hintEl) {
      hintEl.textContent = '';
    }
    var input = document.getElementById(field);
    var num   = fileFieldIndex(field);
    preview(input, num);
    return true;
  }
}

/* ---------- delete (AJAX) ---------- */
// 讓 inline onclick 也能呼叫：<a onclick="del_file(123, 1, 'img')">
window.del_file = del_file;

/**
 * 刪除檔案
 * @param {string|number} pkey 後端圖片資料 PKey
 * @param {number} num 第幾個欄位（對應 preview/prefile/delete 的序號）
 * @param {string} type 'img'|'pdf'|'doc'|'docx'|'xls'|'xlsx'|'rar'|'zip'|...
 */
function del_file(pkey, num, type) {
  pkey = String(pkey || '').trim();
  if (!/^\d+$/.test(pkey)) {
    alert('參數錯誤：PKey 無效');
    return;
  }
  var csrfEl = document.querySelector('input[name="csrf"]')
    || document.querySelector('input[name="csrf_token"]');
  var csrf = csrfEl ? csrfEl.value : '';
  var $btn = $('#delete' + num);
  $btn.prop('disabled', true).addClass('disabled');

  $.ajax({
    type: 'POST',
    url: '_del_img.php',
    data: { PKey: pkey },
    headers: csrf ? { 'X-CSRF-Token': csrf } : {},
    dataType: 'json',
    timeout: 15000,
    success: function (resp, _st, xhr) {
      if (xhr.status === 204 || resp == null || resp === '') resp = { ok: 1 };
      if (typeof resp === 'string') resp = (resp.trim() === '1|OK') ? { ok: 1 } : { ok: 0, msg: resp };

      if (resp && Number(resp.ok) === 1) {
        $btn.hide();

        var $preview = $('#preview' + num);
        var $prefile = $('#prefile' + num);

        // 清 icon
        $prefile.removeClass('fas fa-file-pdf fa-file-word fa-file-excel fa-file-archive fa-file-alt').text('');

        if (type === 'img') {
          $preview.attr('src', '');
          $prefile.hide();
        } else {
          var iconMap = {
            pdf:'fa-file-pdf', doc:'fa-file-word', docx:'fa-file-word',
            xls:'fa-file-excel', xlsx:'fa-file-excel',
            rar:'fa-file-archive', zip:'fa-file-archive', default:'fa-file-alt'
          };
          var icon = iconMap[type] || iconMap.default;
          $prefile.addClass('fas ' + icon).show().text('');
          $preview.hide();
        }

        var $fileInput = $('#Photo' + num);
        if ($fileInput.length) $fileInput.val('');

      } else {
        alert((resp && resp.msg) ? resp.msg : '刪除失敗');
        $btn.prop('disabled', false).removeClass('disabled');
      }
    },
    error: function (xhr) {
      if (xhr && (xhr.status === 403 || xhr.status === 419)) {
        alert('驗證逾時，請重新整理頁面後再試');
      } else {
        alert('刪除失敗，請稍後再試');
      }
      $btn.prop('disabled', false).removeClass('disabled');
    }
  });
}

/* ---------- exports for other scripts (optional) ---------- */
window.checkFile = checkFile;
window.preview   = preview;
window.clearUploadSlotPreview = clearUploadSlotPreview;

if (typeof jQuery !== 'undefined') {
  jQuery(function () {
    initUploadSlotPreviewSnapshots();
  });
}
