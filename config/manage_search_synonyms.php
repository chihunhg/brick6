<?php
declare(strict_types=1);

/**
 * 後台智慧語意搜尋 — 內建同義詞／中英對照
 *
 * 維護方式：
 * - key：搜尋詞（建議小寫、無空白，程式會自動正規化）
 * - value：可一併比對的同義詞陣列（不含原詞亦可，合併時會自動加入使用者輸入）
 *
 * 修改後若搜尋仍沿用舊結果，請將 include/manage_semantic_search_helpers.php
 * 內 manage_semantic_expand_terms() 的快取 key 前綴加 1（例如 terms7_ → terms8_），
 * 或請使用者清除 session／重新登入後台。
 *
 * @return array<string, list<string>>
 */
return [
    'table'     => ['表格', '資料表'],
    'tables'    => ['表格', '資料表'],
    'grid'      => ['表格', '格線'],
    'datatable' => ['表格', '資料表'],
    '表格'      => ['table', 'tables', '資料表'],
    '資料表'    => ['表格', 'table', 'datatable'],
    '数据表'    => ['表格', '資料表', 'table'],
    '表單'      => ['form', '表格'],
    'form'      => ['表單', '表格'],
    'editor'    => ['編輯器'],
    '編輯器'    => ['editor'],
    '管理'      => ['manage'],
    'manage'    => ['管理'],
    '操作'      => ['作業', 'option'],
    '作業'      => ['操作'],
    'option'    => ['操作'],
    '其他'      => ['other', '其它'],
    '其它'      => ['other', '其他'],
    'other'     => ['其他', '其它'],
];