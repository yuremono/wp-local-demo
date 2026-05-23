# tools/

`tools/` は、Local WP や本番の WordPress に対して明示的に実行する作業コマンドをまとめる場所です。ブラウザを開いただけでは動かず、ターミナルから実行してください。

## 使い方

- 実行前に、対象の WordPress 環境が正しいか確認する。
- `WP_LOAD_PATH` や `local-wp-load.path` は、Local 用の対象サイトを指す。
- 本番同期系は `DEPLOY_HOST` / `DEPLOY_USER` / `DEPLOY_PATH` を使う。
- デモ投入系は、既存データを壊さないことを前提に使う。

## 主要ファイル

- `deploy.sh`
  - `npm run build`
  - `composer run phpcs` 相当の静的チェック
  - `dist/0520portfolio-wp-theme.zip` の作成
  - 必要に応じて `rsync + ssh` で本番テーマへ同期
  - `--import-xml` を付けると `exports/yuremono-wp-content.xml` を本番の `wp import` で取り込む
  - `npm run deploy:prod` はテーマ同期と XML 取り込みを一度に行う既定の本番更新コマンド
  - ルートの `.env.deploy` を自動読み込みするので、接続先はそこにまとめてよい
- `run-set-front-demo-meta.sh`
  - Local 同梱 PHP を見つけて `set-front-demo-meta.php` を実行する補助。
- `set-front-demo-meta.php`
  - フロントページの ACF 初期値を補完する。
- `set-portfolio-demo-meta.php`
  - ポートフォリオ TOP 用の ACF 初期値を補完する。
- `sync-portfolio-nav.php`
  - Local の `primary` / `footer` メニューを、React 版に近いポートフォリオ構成へ同期する。
- `seed-standard-pages.php`
  - 標準ページとメニューの初期データを補完する。別案件向けの古い seed なので、このテーマでは通常使わない。
- `seed-works.php`
  - `work` CPT の初期データを補完する。
- `seed-posts-news-media.php`
  - 投稿、`news`、メディアの初期データを補完する。
- `expand-apply-to-scss.mjs`
  - SCSS の `@apply` を展開する補助。
- `local-wp-load.path.example`
  - Local の `wp-load.php` パス設定のサンプル。

## 安全上の注意

- `local-wp-load.path` は、実行先の Local サイトが分かるときだけ使う。
- 既存編集を上書きする可能性があるコマンドは、内容を確認してから実行する。
- 本番に向けるときは、`DEPLOY_PATH` が `wp-content/themes/` 配下を指しているか確認する。
