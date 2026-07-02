<?php
declare(strict_types=1);
/** 檔案槽區塊（需已設定 manageFileSlotFrom、managePhotoSlotMax、$isAdd、$Photo、$PhotoS） */
if (!isset($manageHasFileSlots)) {
    $manageHasFileSlots = (int)($manageFileSlotFrom ?? 0) <= (int)($managePhotoSlotMax ?? 0);
}
$Ext = is_array($Ext ?? null) ? $Ext : [];
if ($manageHasFileSlots) {
    ?>
                                <div class="formGrid">
                                    <label class="col--2 inputLabel editView__formLabel">檔案</label>
                                    <div class="col--10 inputGroup">
                                        <?php for ($n = (int)$manageFileSlotFrom; $n <= (int)$managePhotoSlotMax; $n++) {
                                            $filePath = (!$isAdd) ? (string)($Photo[$n] ?? '') : '';
                                            $ext = (string)($Ext[$n] ?? manage_file_ext_from_path($filePath));
                                            manage_render_upload_document_slot(
                                                $n,
                                                $isAdd,
                                                $filePath,
                                                (int)($PhotoS[$n] ?? 0),
                                                $ext
                                            );
                                        } ?>
                                        <div class="notes">
                                            <ul class="notes__list">
                                                <?php echo $remark_file1 ?? ''; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
    <?php
}
