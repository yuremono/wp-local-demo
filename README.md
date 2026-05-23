# Portfolio Corporate

`https://yuremono.github.io/portfolio/`(ReactSPA)を`https://yuremono.com/`(wordpress)に移植しています。
フロントページ、固定ページ、`work` / `news` の一覧、問い合わせフォーム、SEO 設定をまとめて扱います。
ローカルで編集し、CLI で初期データを補完し、SSH + WP-CLI で本番へ反映する流れを前提にしています。

## 要件

- WordPress 6.x
- PHP 8.0+
- Node.js / npm
- Composer
- Advanced Custom Fields
- Local WP などのローカル WordPress 環境

推奨プラグイン:

- Contact Form 7
- SEO SIMPLE PACK
- WP Multibyte Patch

## 何が入っているか

- `front-page.php` と ACF でフロントページを構成
- `work` CPT で Other Works / RepulsionLists を管理
- `news` CPT でお知らせを管理
- `primary` / `footer` のメニュー位置を登録
- 固定ページテンプレートごとの ACF フィールド
- `assets/theme.scss` / `assets/tailwind.scss` の CSS ビルド
- `assets/src/mindmap-front.ts` からの `assets/mindmap-runtime.js` 生成
- `tools/` の初期データ補完とデプロイ用スクリプト

## 使い方

1. `wp-content/themes/` に配置する。
2. テーマと必要プラグインを有効化する。
3. 固定ページを作成し、表示設定でホームページに指定する。
4. パーマリンク設定を保存する。
5. `npm install` のあと `npm run build` を実行する。
6. 初回のみ `composer install` を実行し、`composer run phpcs` を通す。

## 自動化の流れ

この構成では、手作業を次の範囲まで減らせます。

- ローカルでテーマを編集する
- `tools/` のスクリプトで初期データやデモ内容を補完する
- `npm run deploy` でテーマをビルドして ZIP 化する
- `npm run deploy:prod` でテーマ同期と XML 取り込みをまとめて実行する
- デプロイの詳細は [.codex/skills/wp-deploy/SKILL.md](./.codex/skills/wp-deploy/SKILL.md) を参照する
- SSH + WP-CLI が使える環境なら、管理画面の初期状態まで CLI で寄せる

管理画面の細かな文言修正や最終確認は WordPress 側で行いますが、テーマ反映とコンテンツ移行はコマンドでまとめられます。
このため、エージェントはローカル編集、初期データ投入、配布用 ZIP 作成、本番同期、XML 取り込みまでを一連で進められます。

## 管理画面で確認すること

- ACF が有効化されている
- ホームページが固定ページに設定されている
- `work` と `news` を追加できる
- `primary` と `footer` にメニューが割り当てられている
- お問い合わせページにフォームを置ける
- パーマリンク保存後に `news` が 404 にならない

## 参照

- [DEPLOYMENT.md](./DEPLOYMENT.md)
- [UPDATE_TO_PROD.md](./UPDATE_TO_PROD.md)
- [tools/AGENTS.md](./tools/AGENTS.md)
- [LOCAL_WP_CLI.md](./LOCAL_WP_CLI.md)

## スキル

- `wp-admin`: WordPress テーマの管理画面編集、ACF、CPT、メニュー化を扱うときに使う。
- `wp-deploy`: WordPress テーマの本番反映、SSH 同期、XML 取り込み、デプロイ関連ドキュメント整備を扱うときに使う。
