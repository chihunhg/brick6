<?php

declare(strict_types=1);

/**
 * 前台共用單元 Module_PKey 對照表（slug → PKey）
 *
 * 各前台 *.php 頁面請優先使用 frontend_module_pkey_for_page('xxx.htm') 依選單 PageLink 反查；
 * 本檔僅供首頁區塊、程式內比對等仍須固定 slug 的場景。
 *
 * @return array<string, int>
 */
return [
    'knowledge' => 7,   // 專欄主題
    'news'      => 8,   // 最新消息
    'product'   => 10,  // 工業產品
    'company'   => 12,  // 關於我們
    'faq'       => 13,  // 相關問題
    'filedown'  => 14,  // 檔案下載
    'investor'  => 15,  // 投資人專區
    'weblink'   => 17,  // 相關網站
    'video'     => 18,  // 影音專區
    'question'  => 19,  // 問卷調查
    'album'     => 20,  // 相簿
    'contact'   => 21,  // 聯絡我們
];
