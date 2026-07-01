/**
 * 關聯標籤：#tag_query jQuery UI autocomplete + 動態清單（CSP：無 inline onclick）
 */
(function ($) {
    'use strict';

    function resolveTagManNo(form) {
        var fromData = parseInt(form.getAttribute('data-tag-man-no') || '0', 10) || 0;
        if (fromData > 0) {
            return fromData;
        }
        var el = form.querySelector('[name="tagManNo"]');
        return parseInt(el && el.value ? el.value : '0', 10) || 0;
    }

    function getFormConfig() {
        var form = document.getElementById('form1');
        if (!form) {
            return null;
        }
        return {
            autocompleteUrl: form.getAttribute('data-tag-relation-autocomplete') || '../ajax/tag_relation_autocomplete.php',
            delUrl: form.getAttribute('data-tag-relation-del') || '../ajax/_del_tag_relation.php',
            tagManNo: resolveTagManNo(form)
        };
    }

    function mapAutocompleteItems(data) {
        var items = [];
        if (!Array.isArray(data)) {
            return items;
        }
        data.forEach(function (entry) {
            var name = '';
            var pkey = 0;
            if (typeof entry === 'string') {
                name = entry;
            } else if (entry && typeof entry === 'object') {
                name = entry.label || entry.value || '';
                pkey = parseInt(entry.pkey, 10) || 0;
            }
            name = String(name || '').trim();
            if (name !== '') {
                items.push({ label: name, value: name, pkey: pkey });
            }
        });
        return items;
    }

    function relationExists(targetPKey) {
        var found = false;
        $('#tag_item_list input[id^="Tag"]').each(function () {
            if (this.id.indexOf('Tag_Name') === 0) {
                return;
            }
            if (parseInt(this.value, 10) === targetPKey) {
                found = true;
                return false;
            }
        });
        return found;
    }

    function tagRelationItemAdd(targetPKey, strName, rowPKey) {
        rowPKey = rowPKey || 0;
        targetPKey = parseInt(targetPKey, 10);
        if (targetPKey <= 0 || relationExists(targetPKey)) {
            if (targetPKey > 0 && relationExists(targetPKey)) {
                window.alert('此標籤已在關聯清單中');
            }
            return;
        }

        var total = parseInt($('#Tag_Total').val(), 10) || 0;
        total += 1;
        var $li = $('<li></li>').attr('id', 'tag_item_' + total);
        var $btn = $('<button type="button" class="link-pd__tag"></button>')
            .attr('data-manage-action', 'tag-relation-remove')
            .attr('data-relation-index', String(total))
            .text(strName);
        $li.append($btn);
        $li.append($('<input>').attr({
            type: 'hidden',
            name: 'TagRowPKey' + total,
            id: 'TagRowPKey' + total,
            value: String(rowPKey)
        }));
        $li.append($('<input>').attr({
            type: 'hidden',
            name: 'Tag_Name' + total,
            id: 'Tag_Name' + total,
            value: strName
        }));
        $li.append($('<input>').attr({
            type: 'hidden',
            name: 'Tag' + total,
            id: 'Tag' + total,
            value: String(targetPKey)
        }));
        $('#tag_item_list').append($li);
        $('#Tag_Total').val(String(total));
    }

    function finishRelationPick(cfg, targetPKey, displayName, $query) {
        targetPKey = parseInt(targetPKey, 10) || 0;
        displayName = String(displayName || '').trim();
        if (targetPKey <= 0) {
            window.alert('查無標籤');
            $query.val('');
            return;
        }
        tagRelationItemAdd(targetPKey, displayName, 0);
        $query.val('');
    }

    function parseResolveResponse(data) {
        if (data && typeof data === 'object' && !Array.isArray(data)) {
            return {
                pkey: parseInt(data.pkey, 10) || 0,
                name: String(data.name || '').trim(),
                error: String(data.error || '').trim()
            };
        }
        return { pkey: 0, name: '', error: typeof data === 'string' ? data : '' };
    }

    function resolveRelationByName(cfg, picked, $query) {
        $.ajax({
            type: 'POST',
            url: cfg.autocompleteUrl,
            dataType: 'json',
            data: {
                RType: 4,
                strName: picked,
                tagManNo: cfg.tagManNo
            }
        }).done(function (data) {
            var parsed = parseResolveResponse(data);
            if (parsed.pkey <= 0) {
                window.alert(parsed.error || '查無標籤');
                $query.val('');
                return;
            }
            finishRelationPick(cfg, parsed.pkey, parsed.name || picked, $query);
        }).fail(function () {
            window.alert('查詢標籤失敗，請稍後再試');
        });
    }

    function tagRelationItemDel(index) {
        index = parseInt(index, 10);
        if (!index) {
            return;
        }
        var rowPKey = parseInt($('#TagRowPKey' + index).val(), 10) || 0;
        var cfg = getFormConfig();
        if (rowPKey > 0 && cfg) {
            $.ajax({
                type: 'POST',
                url: cfg.delUrl,
                data: { PKey: rowPKey }
            });
        }
        $('#tag_item_' + index).remove();
    }

    function initAutocomplete() {
        var $query = $('#tag_query');
        if (!$query.length) {
            return;
        }
        if (!$.fn || typeof $.fn.autocomplete !== 'function') {
            console.warn('tag-relation: jQuery UI autocomplete 未載入');
            return;
        }
        var cfg = getFormConfig();
        if (!cfg || cfg.tagManNo <= 0) {
            console.warn('tag-relation: tagManNo 無效，無法啟用 autocomplete');
            return;
        }
        if ($query.data('ui-autocomplete')) {
            $query.autocomplete('destroy');
        }
        $query.autocomplete({
            appendTo: 'body',
            minLength: 1,
            delay: 200,
            source: function (request, response) {
                $.ajax({
                    url: cfg.autocompleteUrl,
                    dataType: 'json',
                    data: {
                        RType: 2,
                        term: request.term,
                        tagManNo: cfg.tagManNo
                    }
                }).done(function (data) {
                    response(mapAutocompleteItems(data));
                }).fail(function () {
                    response([]);
                });
            },
            select: function (event, ui) {
                if (!ui.item) {
                    return false;
                }
                var picked = ui.item.value || ui.item.label;
                var pkey = parseInt(ui.item.pkey, 10) || 0;
                if (pkey > 0) {
                    finishRelationPick(cfg, pkey, picked, $query);
                } else {
                    resolveRelationByName(cfg, picked, $query);
                }
                return false;
            }
        });
    }

    $(function () {
        initAutocomplete();
    });

    window.tagRelationItemAdd = tagRelationItemAdd;
    window.tagRelationItemDel = tagRelationItemDel;

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-manage-action="tag-relation-remove"]');
        if (!el) {
            return;
        }
        e.preventDefault();
        if (!window.confirm('確定移除關聯標籤嗎？')) {
            return;
        }
        var index = el.getAttribute('data-relation-index');
        if (index) {
            tagRelationItemDel(index);
        }
    });
})(jQuery);
