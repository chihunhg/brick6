# Jodit — 來源與驗證紀錄

Jodit 是 MIT 授權（見 `LICENSE.txt`）。本站自架**未修改**的官方預建檔。

| | |
|---|---|
| 版本 | 4.18.12（2026-10-28 發佈；選它而不選當時最新的 4.19.4，是照供應鏈慣例等一週冷卻期，4.19.x 只有新功能、沒有安全修補） |
| 原始碼 | https://github.com/xdan/jodit ，tag `4.18.12`（commit `c297d43694c5a639ce9e4bdba044034b37fb5fa0`） |
| npm 套件 | `jodit@4.18.12` — https://registry.npmjs.org/jodit/-/jodit-4.18.12.tgz |
| tarball sha256 | `0a63ca3218cbe27bc298fb146052a06252741cfc6e39b51e4fa38bb2f964b341` |
| npm integrity | `sha512-//jljicUy78WlbhrbBvMXqM0lOIHuJJf272IviYuYL2jlP2fVMVb7s6tV6Hb5LnfO2uS0v7K8U4e1+oJy0A3+Q==` |
| 相依套件 | 無 |

## 驗證（2026-11-04）

- `npm audit signatures`：**registry 簽章 verified、provenance attestation verified**。
  provenance（SLSA v1）顯示由 GitHub Actions `.github/workflows/release.yml` 從上表的 tag 與 commit 建置。
- 已知漏洞（GitHub Advisory DB，`affects=jodit`）：7 筆，全部修在 4.13.6 以前（最晚一筆發佈於 2026-07-31），本版不受影響。
- 套件檔裡沒有 `eval` / `new Function`，現有 CSP（沒有 `unsafe-eval`）不用放寬。
- 會連外部的功能已在 `config.js` 關掉：原始碼模式的 ace／js-beautify（cdnjs）、about 與 powered-by（xdsoft）、ai-assistant、語音輸入。
  無頭 Edge 實測：編輯器本身零外部請求。

## 檔案對照

| 本目錄的檔 | 出處 | 狀態 |
|---|---|---|
| `jodit.min.js` | npm 包 `es2021/jodit.min.js`（內含繁中 `zh_tw`） | 未修改（逐位元比對相同） |
| `jodit.min.css` | npm 包 `es2021/jodit.min.css` | 未修改（同上） |
| `LICENSE.txt` | npm 包根目錄 | 未修改 |
| `config.js` | 本站自有（設定、初始化、樣式下拉、瀏覽伺服器、內容進編輯器前的清理） | — |
| `content.css` | 本站自有（編輯區樣式） | — |

升版時：`npm pack jodit@{版本}`、在暫存專案 `npm i jodit@{版本} --ignore-scripts` 後跑 `npm audit signatures`，
查 GitHub Advisory DB，再更新本表的版本、tag、commit、雜湊與驗證結果，並重跑逐位元比對。
