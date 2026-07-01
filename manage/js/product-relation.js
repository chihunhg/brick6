/**

 * 產品關聯：#query jQuery UI autocomplete + 動態清單（CSP：無 inline onclick）

 */

(function ($) {

    'use strict';



    function resolveManNo(form) {

        var manNoEl = form.querySelector('[name="manNo"]');

        var manNo = parseInt(manNoEl && manNoEl.value ? manNoEl.value : '0', 10) || 0;

        if (manNo > 0) {

            return manNo;

        }

        var fromData = parseInt(form.getAttribute('data-man-no') || '0', 10) || 0;

        if (fromData > 0) {

            return fromData;

        }

        try {

            var params = new URLSearchParams(window.location.search);

            return parseInt(params.get('manNo') || '0', 10) || 0;

        } catch (e) {

            return 0;

        }

    }



    function getFormConfig() {

        var form = document.getElementById('form1');

        if (!form) {

            return null;

        }

        return {

            autocompleteUrl: form.getAttribute('data-product-relation-autocomplete') || 'product_relation_autocomplete.php',

            delUrl: form.getAttribute('data-product-relation-del') || '_del_relation.php',

            excludePkey: parseInt(form.getAttribute('data-product-relation-exclude-pkey'), 10) || 0,

            manNo: resolveManNo(form)

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

        $('#item1_list input[id^="Accessory"]').each(function () {

            if (parseInt(this.value, 10) === targetPKey) {

                found = true;

                return false;

            }

        });

        return found;

    }



    function productRelationItemAdd(targetPKey, strName, rowPKey) {

        rowPKey = rowPKey || 0;

        targetPKey = parseInt(targetPKey, 10);

        if (targetPKey <= 0 || relationExists(targetPKey)) {

            if (targetPKey > 0 && relationExists(targetPKey)) {

                window.alert('此產品已在關聯清單中');

            }

            return;

        }

        var total = parseInt($('#Accessory_Total').val(), 10) || 0;

        total += 1;

        var $li = $('<li></li>').attr('id', 'item1_' + total);

        var $btn = $('<button type="button" class="link-pd__tag"></button>')

            .attr('data-manage-action', 'product-relation-remove')

            .attr('data-relation-index', String(total))

            .text(strName);

        $li.append($btn);

        $li.append($('<input>').attr({

            type: 'hidden',

            name: 'PKey' + total,

            id: 'PKey' + total,

            value: String(rowPKey)

        }));

        $li.append($('<input>').attr({

            type: 'hidden',

            name: 'Accessory_Name' + total,

            id: 'Accessory_Name' + total,

            value: strName

        }));

        $li.append($('<input>').attr({

            type: 'hidden',

            name: 'Accessory' + total,

            id: 'Accessory' + total,

            value: String(targetPKey)

        }));

        $('#item1_list').append($li);

        $('#Accessory_Total').val(String(total));

    }



    function finishRelationPick(cfg, targetPKey, displayName, $query) {

        targetPKey = parseInt(targetPKey, 10) || 0;

        displayName = String(displayName || '').trim();

        if (targetPKey <= 0) {

            window.alert('查無產品');

            $query.val('');

            return;

        }

        if (cfg.excludePkey > 0 && targetPKey === cfg.excludePkey) {

            window.alert('無法將產品關聯到自己');

            $query.val('');

            return;

        }

        productRelationItemAdd(targetPKey, displayName, 0);

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

        if (typeof data === 'string' && data.indexOf('|') > 0) {

            var parts = data.split('|');

            return {

                pkey: parseInt(parts[0], 10) || 0,

                name: String(parts[1] || '').trim(),

                error: ''

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

                manNo: cfg.manNo,

                excludePKey: cfg.excludePkey

            }

        }).done(function (data) {

            var parsed = parseResolveResponse(data);

            if (parsed.pkey <= 0) {

                window.alert(parsed.error || '查無產品');

                $query.val('');

                return;

            }

            finishRelationPick(cfg, parsed.pkey, parsed.name || picked, $query);

        }).fail(function (xhr) {

            console.warn('product-relation resolve:', xhr.status, xhr.responseText ? xhr.responseText.slice(0, 120) : '');

            window.alert('查詢產品失敗，請稍後再試');

        });

    }



    function productRelationItemDel(index) {

        index = parseInt(index, 10);

        if (!index) {

            return;

        }

        var rowPKey = parseInt($('#PKey' + index).val(), 10) || 0;

        var cfg = getFormConfig();

        if (rowPKey > 0 && cfg) {

            $.ajax({

                type: 'POST',

                url: cfg.delUrl,

                data: { PKey: rowPKey }

            });

        }

        $('#item1_' + index).remove();

    }



    function initAutocomplete() {

        var $query = $('#query');

        if (!$query.length) {

            return;

        }

        if (!$.fn || typeof $.fn.autocomplete !== 'function') {

            console.warn('product-relation: jQuery UI autocomplete 未載入');

            return;

        }

        var cfg = getFormConfig();

        if (!cfg || cfg.manNo <= 0) {

            console.warn('product-relation: manNo 無效，無法啟用 autocomplete');

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

                        manNo: cfg.manNo,

                        excludePKey: cfg.excludePkey

                    }

                }).done(function (data) {

                    response(mapAutocompleteItems(data));

                }).fail(function (xhr) {

                    console.warn('product-relation autocomplete:', xhr.status, xhr.responseText ? xhr.responseText.slice(0, 120) : '');

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



    window.productRelationItemAdd = productRelationItemAdd;

    window.productRelationItemDel = productRelationItemDel;



    document.addEventListener('click', function (e) {

        var el = e.target.closest('[data-manage-action="product-relation-remove"]');

        if (!el) {

            return;

        }

        e.preventDefault();

        if (!window.confirm('確定移除關聯嗎？')) {

            return;

        }

        var index = el.getAttribute('data-relation-index');

        if (index) {

            productRelationItemDel(index);

        }

    });

})(jQuery);

