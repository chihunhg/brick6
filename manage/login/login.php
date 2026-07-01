<?php
require_once('../_inc.php');

$pageTitle = htmlspecialchars((string)$WebName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '｜後端管理系統';
$userName = (string)($_SESSION['UserName'] ?? '');
?>
<!DOCTYPE html>
<html <?php echo $lang_text['lang'][$this_lang]; ?>>

<head>
    <?php require_once('../_in_code_head.php'); ?>
    <?php require_once('../_in_javascript.php'); ?>
</head>

<body <?php if (!empty($bodytxt)) {
    echo $bodytxt;
} ?>>
    <div class="appRoot">
        <?php require_once('../_header.php'); ?>
        <div class="appBody">
            <?php require_once('../_sidebar.php'); ?>

            <!-- MAIN CONTENT -->
            <main class="mainContent">
                <div class="container">
                    <section>
                        <div class="breadcrumb">
                            <i class="bi bi-house"></i>
                            <div class="breadcrumb__item">首頁</div>
                        </div>
                        <h2 class="pageTitle">Hello, <?= e($userName) ?></h2>
                    </section>

                    <article class="card">
                        <p>
                            開啟左側的工具欄，可以選擇要編輯的功能。<br>
                            請別忘了，先到工具欄的<b>「SEO基本設定」</b>介紹你的網站並埋設追蹤碼！
                        </p>
                        <hr>
                        <p><b>【<u>SEO基本設定</u> 是什麼？】</b>是跟「搜索引擎」介紹網站在做什麼。</p>
                        <ul>
                            <li>管理者可至「SEO基本設定」填寫「Meta、結構化資料」及「埋設追蹤碼」，讓「搜索引擎」更認識你的網站。</li>
                            <li>如不清楚欄位的作用，可滑入「問號」查看說明。<br>
                                <figure class="picBox">
                                    <img src="../images/login/seo-01.png" alt="" class="img-fluid">
                                </figure>
                            </li>
                        </ul>
                        <p><b>【<u>SEO基本設定</u> 能幹嘛？】</b></p>
                        <ul>
                            <li>可設定「Meta值」：網站名稱、描述、關鍵字...等「搜索引擎」需要的Meta值。</li>
                            <li>可設定「結構化資料」：聯絡地址、聯絡電話、聯絡信箱等「網站結構化」需要的參數，協助「搜索引擎」讓「使用者」更正確地找到網站。</li>
                            <li>可設定「追蹤碼」：嵌入GA-Code、GTM；<br>
                                <ul>
                                    <li><a href="https://analytics.google.com/analytics" title="GA官網" target="_blank" rel="noopener noreferrer">Google Analytics (分析)官網</a>：登入可查看GA流量報表。</li>
                                    <li><a href="https://tagmanager.google.com/" title="GTM官網" target="_blank" rel="noopener noreferrer">GTM官網</a></li>
                                </ul>
                            </li>
                        </ul>
                        <p class="smTxt">TSG 天矽團隊</p>
                    </article>

                    <div class="notes__spacer"></div>
                </div>
                <?php require_once('../_footer.php'); ?>
            </main>

        </div>
    </div>

    <?php require_once('../_in_code_bottom.php'); ?>

</body>

</html>
