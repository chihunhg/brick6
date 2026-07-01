<?php
declare(strict_types=1);
/**
 * 各模組複製為 _config.php 後修改（範例：news、paper、knowledge）
 *
 * 檔案位置：manage/{模組名}/_config.php
 * 於 add.php、update.php、addin.php 開頭：
 *   $detailConfig = require __DIR__ . '/_config.php';
 *   manage_detail_set_config($detailConfig);
 */
return [
  // 主檔資料表
    'master' => 'news',
  // 子表（沒有該表可設 ''）
    'img'    => 'news_img',
    'lang'   => 'news_lang',
    'msg'    => 'news_msg',
    'link'   => 'news_link',
  // 子表外鍵欄位（對應主檔 PKey）
    'fk'     => 'News_PKey',
  // 表單送出 CSRF（addin.php）
    'csrf'   => 'news_addin',
];

/**
 * addin.php 範本（見 manage/class1/addin.php）：
 *
 * require _config + manage_detail_set_config + crud_csrf_verify
 * 驗證：crud_validate_lang_show_strname、crud_decode_b64_content_multilang（多語系內文時）
 * 上傳：crud_upload_dir + crud_upload_file_slots + crud_save_img_slots
 * 主檔：crud_upsert_master；子表：crud_save_lang_slots、crud_save_msg_blocks_multilang
 * 失敗導回：crud_form_error_redirect($MSG, crud_addin_return_url($formPKey))
 */
