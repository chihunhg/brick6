/**
 * album_d 新增頁：多檔拖拉／選擇、AJAX 背景上傳、進度條、縮圖預覽
 */
$(function () {
  var $root = $('#albumDMultiUpload');
  if (!$root.length) {
    return;
  }

  var maxSlots = parseInt($root.data('max-slots'), 10) || 10;
  var uploadUrl = String($root.data('upload-url') || '_ajax_upload.php');
  var removeUrl = String($root.data('remove-url') || '_ajax_upload_remove.php');
  var clearUrl = String($root.data('clear-url') || '_ajax_upload_clear.php');
  var albumPKey = String($root.data('album-pkey') || '');
  var csrf = ($('input[name="csrf_token"]').val() || '').trim();

  var $dropzone = $('#albumDDropzone');
  var $fileInput = $('#albumDFileInput');
  var $grid = $('#albumDPreviewGrid');
  var $clearAllBtn = $('#albumDClearAllBtn');
  var uploadQueue = [];
  var uploading = false;
  var isClearing = false;
  var currentXhr = null;

  function activeCount() {
    return $grid.find('.album-d-upload-item').length;
  }

  function hasUploadActivity() {
    return activeCount() > 0 || uploadQueue.length > 0 || uploading;
  }

  function canAddMore(extra) {
    return activeCount() + (extra || 0) <= maxSlots;
  }

  function escapeHtml(text) {
    return String(text || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function buildItem(localId, fileName) {
    var html = ''
      + '<div class="album-d-upload-item" data-local-id="' + escapeHtml(localId) + '">'
      + '  <div class="album-d-upload-item__progress"><div class="album-d-upload-item__bar"></div></div>'
      + '  <div class="album-d-upload-item__thumbWrap">'
      + '    <img class="album-d-upload-item__thumb" alt="" src="">'
      + '  </div>'
      + '  <p class="album-d-upload-item__name">' + escapeHtml(fileName) + '</p>'
      + '  <label class="inputLabel album-d-upload-item__captionLabel">圖說</label>'
      + '  <input type="text" name="staged_photo_m[]" class="formInput album-d-upload-item__caption" maxlength="50" value="">'
      + '  <button type="button" class="btn btn-outline-danger btn-sm album-d-upload-item__remove">移除</button>'
      + '</div>';
    return $(html);
  }

  function markItemDone($item, resp) {
    $item.addClass('is-done').attr('data-upload-id', resp.upload_id || '');
    $item.find('.album-d-upload-item__progress').addClass('is-hidden');
    if (resp.preview_url) {
      $item.find('.album-d-upload-item__thumb').attr('src', resp.preview_url);
    }
    $item.append(
      $('<input>', {
        type: 'hidden',
        name: 'staged_upload_id[]',
        value: resp.upload_id || ''
      })
    );
  }

  function showUploadError($item, msg) {
    $item.addClass('is-error');
    $item.find('.album-d-upload-item__progress').addClass('is-hidden');
    $item.find('.album-d-upload-item__name').text(msg || '上傳失敗');
  }

  function localPreview(file, $item) {
    if (!window.FileReader || !file || !file.type || file.type.indexOf('image/') !== 0) {
      return;
    }
    var reader = new FileReader();
    reader.onload = function (evt) {
      $item.find('.album-d-upload-item__thumb').attr('src', evt.target.result || '');
    };
    reader.readAsDataURL(file);
  }

  function restoreStagedItem(item) {
    if (!item || !item.upload_id) {
      return;
    }
    var localId = 'restored-' + item.upload_id;
    var $item = buildItem(localId, item.original_name || 'image');
    markItemDone($item, item);
    $grid.append($item);
  }

  function parseStagedData() {
    var raw = String($root.attr('data-staged') || '[]');
    try {
      var parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (ignore) {
      return [];
    }
  }

  function clearUploadUi() {
    uploadQueue = [];
    uploading = false;
    isClearing = false;
    currentXhr = null;
    $grid.empty();
    $fileInput.val('');
    $root.attr('data-staged', '[]');
    $clearAllBtn.prop('disabled', false);
  }

  function abortCurrentUpload() {
    uploadQueue = [];
    if (currentXhr && currentXhr.readyState !== 4) {
      currentXhr.abort();
    }
    currentXhr = null;
    uploading = false;
  }

  function navigateReturnList() {
    var form = document.getElementById('form1');
    if (!form) {
      window.location.href = 'list.php';
      return;
    }

    var listEl = form.querySelector('[name="list"]');
    var listPath = listEl && listEl.value ? String(listEl.value).trim() : 'list.php';
    var slashIdx = listPath.lastIndexOf('/');
    if (slashIdx >= 0) {
      listPath = listPath.slice(slashIdx + 1);
    }
    if (!listPath) {
      listPath = 'list.php';
    }

    var url = listPath;
    ['manNo', 'subNo', 'Album_PKey'].forEach(function (name) {
      var field = form.querySelector('[name="' + name + '"]');
      if (field && field.value) {
        url += (url.indexOf('?') >= 0 ? '&' : '?')
          + name + '=' + encodeURIComponent(field.value);
      }
    });
    window.location.href = url;
  }

  function requestClearAll(callback) {
    isClearing = true;
    abortCurrentUpload();
    $clearAllBtn.prop('disabled', true);

    $.ajax({
      url: clearUrl,
      type: 'POST',
      data: {
        Album_PKey: albumPKey,
        csrf_token: csrf
      },
      dataType: 'json',
      headers: csrf ? { 'X-CSRF-Token': csrf } : {}
    }).always(function () {
      clearUploadUi();
      if (typeof callback === 'function') {
        callback();
      }
    });
  }

  parseStagedData().forEach(restoreStagedItem);

  function processUploadQueue() {
    if (isClearing || uploading || uploadQueue.length === 0) {
      return;
    }

    uploading = true;
    var task = uploadQueue.shift();
    var $item = task.$item;
    var file = task.file;

    var formData = new FormData();
    formData.append('photo', file);
    formData.append('Album_PKey', albumPKey);
    formData.append('csrf_token', csrf);

    $.ajax({
      url: uploadUrl,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      headers: csrf ? { 'X-CSRF-Token': csrf } : {},
      xhr: function () {
        var xhr = $.ajaxSettings.xhr();
        currentXhr = xhr;
        if (xhr.upload) {
          xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable && !isClearing) {
              var pct = Math.round((e.loaded / e.total) * 100);
              $item.find('.album-d-upload-item__bar').css('width', pct + '%');
            }
          }, false);
        }
        return xhr;
      }
    }).done(function (resp) {
      if (isClearing) {
        return;
      }
      if (resp && Number(resp.ok) === 1) {
        markItemDone($item, resp);
      } else {
        showUploadError($item, (resp && resp.msg) ? resp.msg : '上傳失敗');
      }
    }).fail(function (xhr, textStatus) {
      if (isClearing || textStatus === 'abort') {
        return;
      }
      var msg = '上傳失敗';
      try {
        var resp = JSON.parse(xhr.responseText || '');
        if (resp && resp.msg) {
          msg = resp.msg;
        }
      } catch (ignore) {}
      showUploadError($item, msg);
    }).always(function () {
      currentXhr = null;
      uploading = false;
      if (!isClearing) {
        processUploadQueue();
      }
    });
  }

  function enqueueUpload(file, $item) {
    uploadQueue.push({ file: file, $item: $item });
    processUploadQueue();
  }

  function uploadFile(file) {
    if (!file || isClearing) {
      return;
    }
    if (!canAddMore(1)) {
      alert('最多只能上傳 ' + maxSlots + ' 張圖片');
      return;
    }

    var localId = 'local-' + Date.now() + '-' + Math.random().toString(36).slice(2);
    var $item = buildItem(localId, file.name || 'image');
    $grid.append($item);
    localPreview(file, $item);
    enqueueUpload(file, $item);
  }

  function handleFiles(fileList) {
    if (!fileList || !fileList.length || isClearing) {
      return;
    }
    var files = [];
    var i;
    for (i = 0; i < fileList.length; i++) {
      files.push(fileList[i]);
    }
    if (!canAddMore(files.length)) {
      alert('最多只能上傳 ' + maxSlots + ' 張圖片');
      files = files.slice(0, Math.max(0, maxSlots - activeCount()));
    }
    files.forEach(function (file) {
      uploadFile(file);
    });
  }

  $('#albumDChooseBtn').on('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $fileInput.trigger('click');
  });

  $dropzone.on('click', function (e) {
    if ($(e.target).closest('#albumDChooseBtn, .album-d-upload-item').length) {
      return;
    }
    $fileInput.trigger('click');
  });

  $fileInput.on('change', function () {
    handleFiles(this.files);
    $(this).val('');
  });

  $dropzone.on('dragenter dragover', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $dropzone.addClass('is-dragover');
  });

  $dropzone.on('dragleave drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    $dropzone.removeClass('is-dragover');
  });

  $dropzone.on('drop', function (e) {
    var dt = e.originalEvent && e.originalEvent.dataTransfer;
    if (dt && dt.files) {
      handleFiles(dt.files);
    }
  });

  $clearAllBtn.on('click', function () {
    if (!hasUploadActivity()) {
      requestClearAll();
      return;
    }
    if (!window.confirm('確定要清除所有已選擇的圖片嗎？')) {
      return;
    }
    requestClearAll();
  });

  $grid.on('click', '.album-d-upload-item__remove', function () {
    var $item = $(this).closest('.album-d-upload-item');
    var uploadId = String($item.attr('data-upload-id') || '').trim();

    if (!uploadId) {
      $item.remove();
      return;
    }

    $(this).prop('disabled', true);

    $.ajax({
      url: removeUrl,
      type: 'POST',
      data: {
        Album_PKey: albumPKey,
        upload_id: uploadId,
        csrf_token: csrf
      },
      dataType: 'json',
      headers: csrf ? { 'X-CSRF-Token': csrf } : {}
    }).done(function (resp) {
      if (resp && Number(resp.ok) === 1) {
        $item.remove();
      } else {
        alert((resp && resp.msg) ? resp.msg : '刪除失敗');
        $item.find('.album-d-upload-item__remove').prop('disabled', false);
      }
    }).fail(function () {
      alert('刪除失敗');
      $item.find('.album-d-upload-item__remove').prop('disabled', false);
    });
  });

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-manage-action="return-list"]');
    if (!btn) {
      return;
    }

    e.preventDefault();
    e.stopImmediatePropagation();

    requestClearAll(function () {
      navigateReturnList();
    });
  }, true);

  window.albumDMultiUploadDoneCount = function () {
    return $grid.find('.album-d-upload-item.is-done').length;
  };

  window.albumDMultiUploadPendingCount = function () {
    return $grid.find('.album-d-upload-item:not(.is-done):not(.is-error)').length
      + uploadQueue.length
      + (uploading ? 1 : 0);
  };

  window.albumDMultiUploadClearAll = requestClearAll;
});
