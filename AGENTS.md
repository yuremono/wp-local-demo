# AGENTS.md

## 命名

| 種別 | ルール | 例 |
| --- | --- | --- |
| PHP グローバル関数 | snake_case | `theme_front_meta`, `pc_seed_upsert_post` |
| 定数 | `THEME_` + SCREAMING_SNAKE_CASE | `THEME_GETTEXT_DOMAIN` |
| テーマ公開フィルター | `theme_` で開始 | `theme_brand` |
| ACF グループ／フィールドの `key` | `group_pc_*` / `field_pc_*`（公開後は変更しない） | `group_pc_front_page` |
| ACF フィールドの `name`（メタキー） | snake_case、ブロックごとに接頭辞をそろえる | `hero_*`, `service_1_*`, `footer_contact_*` |
| クラス名 | 独自クラスは PascalCase。ただし Tailwind CSS ユーティリティと RepulsionLists 関連クラスは kebab-case を許可 | `PageRoot`, `NavLi`, `repulsion-lists-module`, `min-h-screen` |
| CPT スラッグ | 小文字の短い英単語 | `news` |
| メニュー位置 | 短い英単語 | `primary` |
| `wp_enqueue_*` ハンドル | `theme-` + kebab-case | `theme-main-css` |

## 出力（ACF 含む）

テンプレートでは **属性・本文・URL ごとに** `esc_attr` / `esc_html` / `esc_url`（など）を使う。ACF の戻りはそのまま `echo` しない。

## 入力欄をどう増やすか（このテーマの前提）

ページに載せる文章や画像の**中身**は、「そのページの編集画面」から入れる。

フロントページは `group_pc_front_page`（ロケーション: フロントページ）。その他のカスタムページテンプレートはテーマ内 `inc/acf-page-templates.php` で **テンプレートファイルごと** に `group_pc_page_*` を定義し、`page_template` ルールで表示を切り替える。

一方で、入力欄の**種類や並び**はテーマの PHP に書いてある想定。**種類を増やしたり並び替えたりするときはコードを直す前提**で、拡張機能の設定画面だけで項目を増やす運用は想定しない。

有料版のリピーター機能は使用予定なし。

## WordPress 画面構築の基本ルール

- フロントページに表示されるテキスト・URL・画像・長文HTMLは、原則として `group_pc_front_page` の ACF フィールド、WordPress メニュー、または専用 CPT から取得する。テンプレートに表示文言を直書きし続けない。
- ACF フィールドを追加しただけで終わらせず、管理画面で編集者が場所を理解できるように初期値も投入する。フロントページの初期メタは `inc/demo-content.php`、表示用の共通デフォルトは `inc/portfolio-defaults.php` に集約する。
- 管理画面から編集する一覧・カード群は、リピーターを使わず CPT で管理する。Other Works / RepulsionLists は `work` CPT とし、タイトル・リンク・ポップアップ本文・表示順・追加クラスを編集可能にする。
- 初期データ投入は既存編集を上書きしない。空欄または未作成の項目だけ補完する。強制上書きが必要なときだけ `tools/` の明示的なスクリプトを使う。
- ACF は導入済み前提だが、テーマコードでは `function_exists( 'get_field' )` や `get_post_meta()` fallback を使い、ACF 無効時の fatal error を避ける。
- WYSIWYG でHTMLを扱う場合は `wp_kses()` と専用の許可HTMLリストを使う。必要なタグを `theme_portfolio_kses_allowed_html()` に追加し、`wp_kses_post()` だけに頼って構造が消えないようにする。
- ヘッダー・フッターリンクはテンプレート直書きではなく `wp_nav_menu()` で出力する。メインメニュー位置は `primary`、フッターは `footer`。既存デザインに必要なHTML構造は Walker または fallback menu で維持する。
- 旧デモ用途の ACF グループや文言を残して管理画面を混乱させない。現在のフロントページに不要なフィールド登録は読み込まない。

## [重要]ページのスタイル方針

- 既にテンプレート側で使っている Tailwind 系クラスは、そのまま残す。
- 新規作成または作り直しのセクション・コンポーネントは、原則としてカスタムクラスを作成してまとめる。
- 新しい Tailwind ユーティリティを `assets/tailwind.scss` に追加して増やす運用はしない。
- 既存のカスタムクラスに対する見た目の派生は、`Is` 接頭辞のモディファイアで切り替える。
- モディファイアを増やす場合、基本スタイルそのものは変えず、バリエーションだけを足す。

## 専用スキル

このテーマの管理画面編集・ACF・CPT・メニュー化を扱うときは、必要に応じて `.codex/skills/wp-admin/SKILL.md` を読む。

## PHP の静的チェック（PHPCS）

テーマ直下で **`composer install`**（初回のみ）のあと **`composer run phpcs`**。WordPress のコーディング規約に沿っているかを機械的に検査する。自動修正できるものは **`composer run phpcbf`**。

## `tools/`

ブラウザでサイトを開いているだけでは**実行されない**。ターミナルから `php` で読み込んで動かす補助スクリプト。

つなぐデータベースやサイトは、実行するときの環境設定で決まる。**意図していないサイト／データに対してデモ投入などを流さない**こと（別フォルダのサイトを指していたら、そのサイトの内容が変わる）。

Local WP の初期投入や更新は [LOCAL_WP_CLI.md](./LOCAL_WP_CLI.md)、本番反映は [DEPLOYMENT.md](./DEPLOYMENT.md) と [UPDATE_TO_PROD.md](./UPDATE_TO_PROD.md) を見る。
本番反映の手順整理や `DEPLOY_PORT` / SSH config の扱いは `.codex/skills/wp-deploy/SKILL.md` を参照する。

実行手順の詳細は各ファイル先頭のコメントにある。`tools/deploy.sh` は `npm run deploy` と `npm run deploy:prod` の実体。

## メニューのテーマロケーション

`register_nav_menus` の **`primary`** がメインメニュー。**プライマリに紐付けたメニューをデモ生成する**ときは、`tools/` のシード用スクリプトや外観 → メニューの割り当てで `primary` を指定する。
