<?php
declare(strict_types=1);
/**
 * 美工頁 FAQ 欄位（module_qa，每語系 5 組 Question / Answer）
 * 需由父層提供迴圈變數 $i（語系 slot）
 */
$qaLangSlot = (int)($i ?? 0);
if ($qaLangSlot <= 0) {
    return;
}
$Question = is_array($Question ?? null) ? $Question : [];
$Answer = is_array($Answer ?? null) ? $Answer : [];
$qaSlotMax = function_exists('module_qa_slot_max') ? module_qa_slot_max() : 5;
?>
                                    <div class="editView__section editView__section--nested">
                                        <h5 class="editView__sectionTitle">FAQ（結構化資料）</h5>
                                        <?php for ($n = 1; $n <= $qaSlotMax; $n++) {
                                            $qField = module_qa_field_question($n, $qaLangSlot);
                                            $aField = module_qa_field_answer($n, $qaLangSlot);
                                            ?>
                                        <div class="formGrid">
                                            <label class="col--2 inputLabel editView__formLabel" for="<?php echo e($qField); ?>">
                                                問題 <?php echo $n; ?>
                                            </label>
                                            <div class="col--10">
                                                <input name="<?php echo e($qField); ?>" type="text"
                                                    id="<?php echo e($qField); ?>" class="formInput" maxlength="100"
                                                    value="<?php echo e((string)($Question[$n][$qaLangSlot] ?? '')); ?>">
                                            </div>
                                        </div>
                                        <div class="formGrid">
                                            <label class="col--2 inputLabel editView__formLabel" for="<?php echo e($aField); ?>">
                                                答案 <?php echo $n; ?>
                                            </label>
                                            <div class="col--10">
                                                <textarea name="<?php echo e($aField); ?>" id="<?php echo e($aField); ?>"
                                                    class="formInput" style="height:100px" maxlength="500"
                                                    placeholder="請輸入 FAQ 答案（500 字元內）"><?php echo e((string)($Answer[$n][$qaLangSlot] ?? '')); ?></textarea>
                                            </div>
                                        </div>
                                        <?php } ?>
                                    </div>
