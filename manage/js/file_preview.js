$(function () {

    var FA_FILE_CLASSES = 'fa-file-pdf fa-file-word fa-file-excel fa-file-powerpoint fa-file-archive fa-file-alt';

    function extFromName(name) {
        var parts = String(name || '').split('.');
        if (parts.length < 2) {
            return '';
        }
        return parts.pop().toLowerCase();
    }

    function faIconClass(ext) {
        switch (ext) {
            case 'pdf':
                return 'fa-file-pdf';
            case 'doc':
            case 'docx':
                return 'fa-file-word';
            case 'xls':
            case 'xlsx':
                return 'fa-file-excel';
            case 'ppt':
            case 'pptx':
                return 'fa-file-powerpoint';
            case 'zip':
            case 'rar':
            case '7z':
                return 'fa-file-archive';
            case 'txt':
                return 'fa-file-alt';
            default:
                return 'fa-file-alt';
        }
    }

    function applyFileIcon($prefile, ext, name) {
        if (!$prefile.length) {
            return;
        }
        $prefile
            .removeClass('fas ' + FA_FILE_CLASSES)
            .addClass('fas ' + faIconClass(ext))
            .text(name || '')
            .css('display', '');
    }

    // 預覽改由 checkFile() 驗證通過後觸發（filesize.js），避免不合格檔案仍顯示預覽

});
