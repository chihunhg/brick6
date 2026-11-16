# Quill — 來源與驗證紀錄

Quill 是 BSD-3-Clause（見 `LICENSE.txt`）。本目錄的 `quill.js`、`quill.snow.css` 是官方預建檔，未修改。

| | |
|---|---|
| 版本 | 2.0.3 |
| npm 套件 | `quill@2.0.3` |
| tarball sha256 | `3a8a6cb4383b65e93552ea3da79a796eb0be5d2ca08390f1add0208fb23e6f42` |
| `quill.js` sha256 | `f6157c72ac9b3f51cdead426335688a027b12405d9d6a4daadd38a676b2d7ff2` |
| `quill.snow.css` sha256 | `1c7948cd13aa92fac6390319bc1e5e461823da171519d3a768db56164f871636` |

預建檔裡有一段 `globalThis` 不存在時才會走到的 `new Function("return this")`。現行瀏覽器有 `globalThis`，這段不會執行，後台 CSP 不必加 `unsafe-eval`。

## 檔案對照

| 本目錄的檔 | 出處 | 狀態 |
|---|---|---|
| `quill.js` | npm 包 `dist/quill.js` | 未修改 |
| `quill.snow.css` | npm 包 `dist/quill.snow.css` | 未修改 |
| `LICENSE.txt` | npm 包 `LICENSE` | 未修改 |
| `config.js` | 本站自有（工具列、初始化、與既有表單／AI 的銜接） | — |
| `content.css` | 本站自有（外框與工具列） | — |

`config.js` 只接管 `textarea.quill-editor`。全站其餘內容欄仍用 Jodit。
