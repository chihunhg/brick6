<?php
declare(strict_types=1);

/**
 * 向量搜尋：Pinecone metadata.type 對應 MySQL 資料表
 *
 * sync=true 的類型會在後台新增／修改／刪除時自動同步向量。
 *
 * @return array<string, array{
 *     table: string,
 *     pk: string,
 *     columns: list<string>,
 *     sync?: bool,
 *     text_columns?: list<string>,
 *     msg_table?: string,
 *     msg_fk?: string,
 *     require_upload_yes?: bool
 * }>
 */
return [
    'product' => [
        'table'              => 'product',
        'pk'                 => 'PKey',
        'columns'            => ['PKey', 'strName', 'strNo', 'Upload', 'dtUDate'],
        'sync'               => true,
        'text_columns'       => ['strName', 'strNo'],
        'require_upload_yes' => true,
    ],
    'news' => [
        'table'              => 'news',
        'pk'                 => 'PKey',
        'columns'            => ['PKey', 'strName', 'OpenDate', 'EndDate', 'dtUDate'],
        'sync'               => true,
        'text_columns'       => ['strName'],
        'msg_table'          => 'news_msg',
        'msg_fk'             => 'News_PKey',
        'require_upload_yes' => true,
        'publish_window'     => true,
    ],
    'article' => [
        'table'              => 'news',
        'pk'                 => 'PKey',
        'columns'            => ['PKey', 'strName', 'Upload', 'dtUDate'],
        'sync'               => false,
    ],
    'knowledge' => [
        'table'              => 'knowledge',
        'pk'                 => 'PKey',
        'columns'            => ['PKey', 'strName', 'Upload', 'dtUDate'],
        'sync'               => true,
        'text_columns'       => ['strName'],
        'msg_table'          => 'knowledge_msg',
        'msg_fk'             => 'Knowledge_PKey',
        'require_upload_yes' => true,
    ],
    'faq' => [
        'table'              => 'faq',
        'pk'                 => 'PKey',
        'columns'            => ['PKey', 'strName', 'Upload', 'dtUDate'],
        'sync'               => true,
        'text_columns'       => ['strName'],
        'msg_table'          => 'faq_msg',
        'msg_fk'             => 'Faq_PKey',
        'require_upload_yes' => true,
    ],
    'paper' => [
        'table'              => 'paper',
        'pk'                 => 'PKey',
        'columns'            => ['PKey', 'strName', 'Upload', 'dtUDate'],
        'sync'               => true,
        'text_columns'       => ['strName', 'Description', 'Keywords'],
        'msg_table'          => 'paper_msg',
        'msg_fk'             => 'Paper_PKey',
        'require_upload_yes' => true,
    ],
    'video' => [
        'table'              => 'video',
        'pk'                 => 'PKey',
        'columns'            => ['PKey', 'strName', 'Upload', 'dtUDate'],
        'sync'               => true,
        'text_columns'       => ['strName', 'Description', 'Keywords', 'Interview'],
        'require_upload_yes' => true,
    ],
];
