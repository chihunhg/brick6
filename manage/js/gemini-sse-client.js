/**
 * Gemini SSE 串流 POST（fetch + ReadableStream）
 */
(function (global) {
    'use strict';

    function parseJsonResponse(raw) {
        var text = String(raw || '').replace(/^\uFEFF/, '').trim();
        if (!text) {
            return null;
        }
        var start = text.indexOf('{');
        var end = text.lastIndexOf('}');
        if (start >= 0 && end > start) {
            text = text.substring(start, end + 1);
        }
        return JSON.parse(text);
    }

    function parseSseEventBlock(block) {
        var lines = String(block || '').split('\n');
        var eventName = 'message';
        var dataLines = [];

        lines.forEach(function (line) {
            if (line.indexOf('event:') === 0) {
                eventName = $.trim(line.slice(6));
            } else if (line.indexOf('data:') === 0) {
                dataLines.push(line.slice(5).replace(/^\s/, ''));
            }
        });

        if (!dataLines.length) {
            return null;
        }

        try {
            return {
                event: eventName,
                data: JSON.parse(dataLines.join('\n'))
            };
        } catch (ignoreErr) {
            return null;
        }
    }

    function decodePartialJsonString(raw) {
        var out = '';
        var i = 0;
        while (i < raw.length) {
            var ch = raw.charAt(i);
            if (ch === '\\' && i + 1 < raw.length) {
                var next = raw.charAt(i + 1);
                if (next === 'n') {
                    out += '\n';
                } else if (next === 'r') {
                    out += '\r';
                } else if (next === 't') {
                    out += '\t';
                } else if (next === '"') {
                    out += '"';
                } else if (next === '\\') {
                    out += '\\';
                } else if (next === '/') {
                    out += '/';
                } else if (next === 'u' && i + 5 < raw.length) {
                    out += String.fromCharCode(parseInt(raw.substr(i + 2, 4), 16));
                    i += 4;
                } else {
                    out += next;
                }
                i += 2;
                continue;
            }
            if (ch === '"') {
                break;
            }
            out += ch;
            i += 1;
        }
        return out;
    }

    function tryPartialHtmlContent(accumulated) {
        var key = '"html_content"';
        var idx = accumulated.indexOf(key);
        if (idx < 0) {
            return null;
        }
        var colon = accumulated.indexOf(':', idx + key.length);
        if (colon < 0) {
            return null;
        }
        var startQuote = accumulated.indexOf('"', colon + 1);
        if (startQuote < 0) {
            return null;
        }
        return decodePartialJsonString(accumulated.slice(startQuote + 1));
    }

    function tryPartialField(accumulated, fieldName) {
        var key = '"' + fieldName + '"';
        var idx = accumulated.indexOf(key);
        if (idx < 0) {
            return null;
        }
        var colon = accumulated.indexOf(':', idx + key.length);
        if (colon < 0) {
            return null;
        }
        var startQuote = accumulated.indexOf('"', colon + 1);
        if (startQuote < 0) {
            return null;
        }
        return decodePartialJsonString(accumulated.slice(startQuote + 1));
    }

    function resolveStreamError(response, rawText) {
        try {
            var parsed = parseJsonResponse(rawText);
            if (parsed && parsed.error) {
                return String(parsed.error);
            }
        } catch (ignoreErr) {
            // fall through
        }
        if (response.status === 401) {
            return '登入已逾時，請重新登入後台';
        }
        if (response.status === 404) {
            return '找不到 API 端點，請確認檔案已部署';
        }
        return '產生失敗（HTTP ' + response.status + '）';
    }

    /**
     * @param {string} url
     * @param {object} payload
     * @param {{onStart?: Function, onDelta?: Function, onDone?: Function}} handlers
     */
    function streamPost(url, payload, handlers) {
        handlers = handlers || {};
        var accumulated = '';

        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Accept': 'text/event-stream'
            },
            body: JSON.stringify(payload || {}),
            credentials: 'same-origin'
        }).then(function (response) {
            var contentType = (response.headers.get('content-type') || '').toLowerCase();
            if (contentType.indexOf('text/event-stream') === -1) {
                return response.text().then(function (text) {
                    throw new Error(resolveStreamError(response, text));
                });
            }

            if (!response.body || !response.body.getReader) {
                throw new Error('瀏覽器不支援串流讀取');
            }

            var reader = response.body.getReader();
            var decoder = new TextDecoder();
            var buffer = '';

            function dispatchEvent(ev) {
                if (!ev || !ev.event) {
                    return;
                }
                if (ev.event === 'start' && typeof handlers.onStart === 'function') {
                    handlers.onStart(ev.data || {});
                } else if (ev.event === 'delta') {
                    var delta = String((ev.data && ev.data.text) || '');
                    if (delta !== '') {
                        accumulated += delta;
                        if (typeof handlers.onDelta === 'function') {
                            handlers.onDelta(delta, accumulated);
                        }
                    }
                } else if (ev.event === 'done') {
                    if (typeof handlers.onDone === 'function') {
                        handlers.onDone(ev.data || {});
                    }
                } else if (ev.event === 'error') {
                    throw new Error(String((ev.data && ev.data.error) || '產生失敗'));
                }
            }

            function processBuffer(finalPass) {
                var parts = buffer.split('\n\n');
                if (!finalPass) {
                    buffer = parts.pop() || '';
                } else {
                    buffer = '';
                }
                parts.forEach(function (block) {
                    block = $.trim(block);
                    if (!block || block.charAt(0) === ':') {
                        return;
                    }
                    dispatchEvent(parseSseEventBlock(block));
                });
            }

            function readChunk() {
                return reader.read().then(function (result) {
                    if (result.done) {
                        processBuffer(true);
                        return;
                    }
                    buffer += decoder.decode(result.value, { stream: true });
                    processBuffer(false);
                    return readChunk();
                });
            }

            return readChunk();
        });
    }

    global.manageGeminiStreamPost = streamPost;
    global.manageGeminiTryPartialHtml = tryPartialHtmlContent;
    global.manageGeminiTryPartialField = tryPartialField;
}(window));
