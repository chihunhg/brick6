<?php
$pageName = "p4";
$subPageName = "p4_1";
require("_inc.php");
$detailName = "AIMA為葡萄牙黃金簽證持有者推出線上續簽平台";
?>

<!DOCTYPE html>
<html <?php echo $lang_text["lang"][$this_lang]; ?>>
<head>
<?php require("_in_code_head.php"); ?>
<?php require("_in_javascript.php"); ?>
</head>

<body <?php if ( !empty($bodytxt) ){ echo $bodytxt; } ?>>
<?php require("_header.php"); ?>
<?php require("_banner.php"); ?>

<main class="pgContent">
    <section class="blockHeight blockHeight--news">
        <div class="container">
            <div class="newsDBox">
                <div class="articleTop">
                    <h2 class="articleTt"><?php echo $detailName?></h2>
                    <div class="dateTxt">2026/03/09</div>
                </div>
                 <div class="articleMain">
                    <article class="tx01">
                        <div class="text">
                            <p>葡萄牙融合、移民與庇護署（AIMA）於 2 月 16 日正式為黃金簽證（投資居留許可，Autorização de Residência para Atividade de Investimento，簡稱 ARI）持有人開放線上續簽平台。「續簽入口網站」（Portal das Renovações）將成為更新黃金簽證的唯一管道，全面取消臨櫃遞件申請。</p>
                            <p>在新制度下，黃金簽證持有人及其依親家人，皆須透過該平台提交續簽申請並繳納相關費用。僅當既有之生物辨識資料失效時，才需要赴移民機構實體預約，重新採集指紋與照片等生物辨識資訊。</p>
                            <br>
                            <div class="decoTt">運作方式</div>
                            <p>自申請遞交起至審核決定，AIMA將全程以數位方式處理續簽案件。若申請人的生物辨識資料需要更新，該機關會自動安排預約，並通知簽證持有人相關的日期、時間與地點。</p>
                            <p>在完成生物辨識資料採集後，其餘程序將回到線上平台進行。然而，最終核准仍以完成生物辨識為前提；未完成生物辨識採集，續期程序即無法結案。</p>
                            <p>對於已經預約生物辨識採集實體時段的申請人，仍須依原預約時間到場辦理。AIMA在公告中明確指出，這些先前通知並標示的既有預約仍具約束力。</p>
                            <p style="color:red">※ 想了解更多關於葡萄牙移民的資訊嗎？歡迎查看相關說明，或直接預約諮詢<br>→ 點選「相關說明」會來到各國移民項下葡萄牙的介紹頁面，點選「預約諮詢」則到官網聯絡表單</p>
                        </div>
                    </article>
                </div>
                <div class="linkBox">
                    <a href="#" target="_blank" rel="noopener noreferrer" class="linkBox__item">
                        <span class="txt">相關說明</span>
                    </a>
                    <a href="contact.htm" target="_blank" rel="noopener noreferrer" class="linkBox__item">
                        <span class="txt">預約諮詢</span>
                    </a>
                </div>
            </div>
            <div class="btnWrap btnWrap--center">
                <a href="news.htm" class="btnStyle">
                    <span class="txt">回上一頁</span>
                </a>
            </div>
        </div>
    </section>
</main>

<?php require("_footer.php"); ?>
<?php require("_in_code_bottom.php"); ?>
</body>
</html>