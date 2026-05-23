---
name: wp-admin
description: WordPress テーマで、表示内容を管理画面から編集できるようにするときに使う。ACF フィールド、固定ページテンプレート、CPT、初期データ投入、wp_nav_menu、WYSIWYG 本文、出力エスケープ、既存編集を壊さないデモデータ補完を扱う。
---

# WordPress テーマ 管理画面編集対応

WordPress テーマ内で、フロント側に表示される文章・画像・リンク・カード・一覧・長文 HTML を、管理画面から編集できるようにするためのスキル。

このスキルは特定プロジェクト専用ではない。初めて見るテーマでは、既存の `AGENTS.md`、README、テーマ内の `functions.php`、`inc/`、`template-parts/`、ページテンプレート、既存の ACF / CPT / メニュー登録を読んでから、既存設計に合わせて実装する。

## 最初に確認すること

- 編集前にリポジトリまたはテーマ直下の `AGENTS.md` を読む。存在しない場合は、README やテーマ内コメントからプロジェクトルールを確認する。
- `git status --short` を確認し、既存のユーザー変更を壊さない。
- テーマの場所を確認する。例: `wp-content/themes/example-theme/`。
- 使用中の編集方式を確認する。例: ACF ローカル JSON、ACF PHP 登録、ブロックテーマ、クラシックテーマ、独自オプションページ。
- 既存の命名規則を確認する。例: PHP 関数は `theme_prefix_function_name()`、ACF key は `group_theme_*` / `field_theme_*`、CSS クラスは PascalCase または snake_case。
- ユーザー指示または具体的な問題がない限り、本番データに影響する seed 実行、DB 更新、formatter、ビルド、テスト、ブラウザ操作は勝手に行わない。
- `tools/`、`bin/`、`scripts/` 配下の補助スクリプトは、ブラウザでサイトを開いただけでは実行されない。実行が必要な場合は、対象サイト・DB・環境変数を確認してから行う。

## 基本方針

- テンプレートに直書きされた表示文言を、管理画面で編集できるデータへ移す。
- ページ固有の単体項目は ACF または同等のカスタムフィールドで管理する。
- 繰り返し項目は、ACF リピーターを使える方針が明示されていない限り、CPT で管理する。
- ヘッダーやフッターのリンク一覧は `wp_nav_menu()` とメニュー位置で管理する。
- 管理画面上で編集場所が分かるよう、初期値や説明文を入れる。
- 自動投入は未作成・空欄だけを補完し、既存編集を上書きしない。
- テーマが ACF 無効時に fatal error にならないよう、ACF 関数は必ず存在確認する。
- フロント出力では、本文・属性・URL・許可 HTML を用途別にエスケープする。

## 推奨ファイル構成

既存テーマの構成を優先する。新規に置き場所を作る場合は、以下のように役割を分ける。

- `functions.php`: 各機能ファイルの読み込み、テーマセットアップ、メニュー位置登録。
- `inc/acf-front-page.php`: フロントページ専用 ACF フィールド登録。
- `inc/acf-page-templates.php`: 固定ページテンプレート専用 ACF フィールド登録。
- `inc/cpt.php`: CPT 登録。
- `inc/defaults.php`: 表示 fallback や seed に使う共通デフォルト値。
- `inc/demo-content.php`: 管理画面に初期値を補完する処理。
- `inc/template-tags.php`: メタ取得、画像解決、許可 HTML などの表示用ヘルパー。
- `template-parts/`: 再利用するテンプレート部品。
- `tools/`: 明示的に実行する補助スクリプト。通常のリクエストでは自動実行しない。

例:

```php
// functions.php
require get_template_directory() . '/inc/defaults.php';
require get_template_directory() . '/inc/template-tags.php';
require get_template_directory() . '/inc/cpt.php';
require get_template_directory() . '/inc/acf-front-page.php';
require get_template_directory() . '/inc/acf-page-templates.php';
require get_template_directory() . '/inc/demo-content.php';
```

## ACF フィールド設計

ACF を使う場合は、フィールド登録だけで終わらせない。テンプレート出力、fallback、初期値補完まで一貫して実装する。

- フロントページの入力欄は、フロントページ専用の field group に集約する。
- 固定ページテンプレート専用の入力欄は、`page_template` ルールで対象テンプレートだけに表示する。
- ACF group key / field key は公開後に変えない。既存 DB の関連付けが壊れるため。
- field `name` は保存されるメタキーなので、分かりやすい接頭辞を付ける。例: `hero_title`, `hero_image`, `service_1_body`, `contact_button_url`。
- field `label` と `instructions` は、初めて編集する人が表示場所を理解できる内容にする。
- ACF 登録処理は `function_exists( 'acf_add_local_field_group' )` で囲む。

例:

```php
add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'      => 'group_theme_front_page',
				'title'    => 'Front Page Content',
				'fields'   => array(
					array(
						'key'           => 'field_theme_hero_title',
						'label'         => 'Hero Title',
						'name'          => 'hero_title',
						'type'          => 'text',
						'instructions'  => 'フロントページのメイン見出しに表示されます。',
						'default_value' => 'Default hero title',
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'page_type',
							'operator' => '==',
							'value'    => 'front_page',
						),
					),
				),
			)
		);
	}
);
```

## 値取得と fallback

ACF の値を直接テンプレート内で何度も取得すると、ACF 無効時や空欄時の扱いが散らばる。ヘルパー関数に集約する。

例:

```php
function theme_meta( string $key, int $post_id = 0, $default = '' ) {
	$post_id = $post_id ?: get_the_ID();

	if ( function_exists( 'get_field' ) ) {
		$value = get_field( $key, $post_id );
	} else {
		$value = get_post_meta( $post_id, $key, true );
	}

	if ( '' === $value || null === $value || false === $value ) {
		return $default;
	}

	return $value;
}
```

画像フィールドは、ACF 配列、添付 ID、数字文字列、URL fallback に対応させる。

例:

```php
function theme_image_url( $image, string $size = 'large', string $fallback = '' ): string {
	if ( is_array( $image ) && ! empty( $image['ID'] ) ) {
		$url = wp_get_attachment_image_url( (int) $image['ID'], $size );
		return $url ?: $fallback;
	}

	if ( is_numeric( $image ) ) {
		$url = wp_get_attachment_image_url( (int) $image, $size );
		return $url ?: $fallback;
	}

	if ( is_string( $image ) && '' !== $image ) {
		return $image;
	}

	return $fallback;
}
```

## 出力エスケープ

ACF、CPT メタ、メニュー項目、オプション値は信用しない。用途別に必ずエスケープする。

- 本文テキスト: `esc_html()`
- 属性値: `esc_attr()`
- URL: `esc_url()`
- textarea の改行あり本文: `wp_kses_post( wpautop( $value ) )` など、要件に合わせて処理する。
- 編集可能な HTML 本文: `wp_kses()` と専用の許可リストを使う。

例:

```php
function theme_allowed_html(): array {
	$allowed = wp_kses_allowed_html( 'post' );

	$allowed['section'] = array(
		'class' => true,
		'id'    => true,
	);
	$allowed['article'] = array(
		'class' => true,
	);
	$allowed['details'] = array(
		'class' => true,
		'open'  => true,
	);
	$allowed['summary'] = array(
		'class' => true,
	);

	return $allowed;
}
```

テンプレート例:

```php
$hero_title = theme_meta( 'hero_title', get_the_ID(), 'Default hero title' );
$hero_url   = theme_meta( 'hero_button_url', get_the_ID(), home_url( '/' ) );
?>
<h1><?php echo esc_html( $hero_title ); ?></h1>
<a href="<?php echo esc_url( $hero_url ); ?>">
	<?php echo esc_html__( 'View more', 'theme-textdomain' ); ?>
</a>
```

## メニュー

ヘッダー、フッター、SNS リンク、ページ内ナビなどのリンク一覧は、テンプレート直書きではなく WordPress メニューで編集できるようにする。

- `register_nav_menus()` でメニュー位置を登録する。
- 一般的な位置名は `primary`、`footer`、`social` など。
- 既存デザインに必要な HTML 構造やクラスは、`wp_nav_menu()` の引数、Walker、fallback 関数で維持する。
- fallback は、メニュー未割り当て時に最低限のリンクを表示するためのもの。管理画面で登録したメニューより優先してはいけない。

例:

```php
add_action(
	'after_setup_theme',
	function () {
		register_nav_menus(
			array(
				'primary' => __( 'Primary Menu', 'theme-textdomain' ),
				'footer'  => __( 'Footer Menu', 'theme-textdomain' ),
			)
		);
	}
);
```

```php
wp_nav_menu(
	array(
		'theme_location' => 'primary',
		'container'      => false,
		'menu_class'     => 'GlobalNavList',
		'fallback_cb'    => 'theme_primary_menu_fallback',
	)
);
```

## CPT で繰り返し項目を管理する

カード一覧、実績一覧、ニュース、FAQ、リンクチップ、スタッフ紹介などの反復コンテンツは、CPT にすると管理画面で追加・削除・並び替えしやすい。

CPT には必要に応じて以下を持たせる。

- タイトル
- 本文または抜粋
- アイキャッチ画像
- 内部パス
- 外部 URL
- ボタンラベル
- 追加 CSS クラス
- 表示 / 非表示フラグ
- 注目表示フラグ
- WYSIWYG の詳細本文
- `menu_order` による表示順

例: 「リンクカード一覧」を管理する場合。

- CPT slug: `link_card`
- 投稿タイトル: カード見出し
- カスタムフィールド: `link_card_url`, `link_card_label`, `link_card_body`, `link_card_class`
- 表示順: `menu_order`
- フロント表示: `post_type => 'link_card'`, `orderby => 'menu_order'`, `order => 'ASC'`

CPT 投稿がない場合だけ、テーマ内の fallback データを表示する。CPT 投稿が存在するのに一部メタが空の場合は、その項目だけ default を補うか、空欄として扱うかを要件に合わせて決める。

## 初期データ投入

管理画面で編集できるようにしたら、編集者が場所を理解できる初期値も用意する。

- 初期値は ACF の `default_value`、テーマ内 fallback、または seed 処理で管理する。
- seed 処理は既存編集を上書きしない。
- 固定ページのメタは、対象ページを特定して空欄だけ埋める。
- CPT の初期投稿は、タイトルや安定した識別メタで既存投稿を探してから作る。
- 強制上書きが必要な処理は、通常のページ表示や `init` で自動実行しない。`tools/` の明示的なスクリプトに分ける。

空欄だけ補完する例:

```php
function theme_update_empty_meta( int $post_id, string $key, $value ): void {
	$current = get_post_meta( $post_id, $key, true );

	if ( '' !== $current && null !== $current && array() !== $current ) {
		return;
	}

	update_post_meta( $post_id, $key, $value );
}
```

CPT の初期投稿を安全に補完する例:

```php
function theme_seed_card( array $item ): void {
	$existing = get_posts(
		array(
			'post_type'      => 'link_card',
			'title'          => $item['title'],
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	$post_id = $existing ? (int) $existing[0] : 0;

	if ( ! $post_id ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => 'link_card',
				'post_title'  => $item['title'],
				'post_status' => 'publish',
				'menu_order'  => $item['menu_order'] ?? 0,
			)
		);
	}

	if ( $post_id && ! is_wp_error( $post_id ) ) {
		theme_update_empty_meta( $post_id, 'link_card_url', $item['url'] ?? '' );
		theme_update_empty_meta( $post_id, 'link_card_body', $item['body'] ?? '' );
	}
}
```

## 長い HTML 本文と WYSIWYG

モーダル本文、詳細カード、FAQ 回答、経歴、商品説明などの長い HTML をテンプレートにだけ置くと、管理画面で編集場所が分からなくなる。

- 長い本文は WYSIWYG、textarea、または CPT 本文に実データとして保存する。
- HTML 構造が必要な場合は `wp_kses()` の許可リストに必要タグを追加する。
- デフォルト HTML は関数にまとめ、ACF 初期値、seed、表示 fallback で重複させない。
- 管理画面に表示する説明文で、どの画面・どの部品に反映されるかを書く。

例:

```php
function theme_default_profile_body(): string {
	return '<section class="ProfileBody"><h2>Profile</h2><p>Default body text.</p></section>';
}

$body = theme_meta( 'profile_body', get_the_ID(), theme_default_profile_body() );
echo wp_kses( $body, theme_allowed_html() );
```

## 固定ページテンプレート別の入力欄

複数の固定ページテンプレートがあるテーマでは、全ページに全フィールドを出すと管理画面が分かりにくくなる。テンプレートごとに field group を分ける。

例:

- `page-templates/about.php`: `group_theme_page_about`
- `page-templates/contact.php`: `group_theme_page_contact`
- `page-templates/service.php`: `group_theme_page_service`

ACF location 例:

```php
'location' => array(
	array(
		array(
			'param'    => 'page_template',
			'operator' => '==',
			'value'    => 'page-templates/about.php',
		),
	),
),
```

## 既存テンプレートから管理画面編集へ移す手順

1. 対象テンプレートを読み、直書きされている文章・URL・画像・長文 HTML・繰り返し項目を洗い出す。
2. 単体項目、ページ固有項目、反復項目、メニュー項目に分類する。
3. 単体項目は ACF field、反復項目は CPT、ナビゲーションは `wp_nav_menu()` に移す。
4. field name、CPT slug、メニュー位置、ヘルパー関数名を既存命名規則に合わせて決める。
5. テンプレートを、メタ取得ヘルパーと適切なエスケープを使う形に変える。
6. fallback と初期値補完を追加する。
7. ACF 無効時、CPT 投稿なし、メニュー未割り当て、画像未設定の挙動を確認する。
8. 可能なら PHPCS、PHP lint、既存テスト、ブラウザ表示確認を行う。

## 検証

環境に応じて、実行できる範囲で確認する。

- PHP 構文確認: `php -l path/to/file.php`
- WordPress Coding Standards がある場合: `composer run phpcs`
- 自動修正がプロジェクトで許可されている場合: `composer run phpcbf`
- テーマにビルドがある場合: `npm run build`、`npm run lint` など既存 scripts を確認してから実行する。
- ブラウザ確認が必要な場合: ローカル WordPress の URL、対象ページ、ログイン状態を確認してから行う。

管理画面編集の仕組みを変えた後は、必要に応じて以下を確認する。

- 対象ページ編集画面に ACF フィールドが表示される。
- field label と instructions で編集場所が理解できる。
- CPT 一覧、CPT 編集画面、表示順が機能する。
- メニュー位置に割り当てたメニューがフロントに反映される。
- ACF 無効時に fatal error にならない。
- 空欄時に fallback が出る。
- 既存編集済みのデータが seed で上書きされない。
- フロント出力で `esc_html()`、`esc_attr()`、`esc_url()`、`wp_kses()` が用途別に使われている。

## よくある判断基準

- 1 ページ内の見出しや説明文だけを編集したい: ACF field。
- 複数ページで共通するサイト設定を編集したい: テーマオプションページ、Customizer、または既存の設定方式。
- 編集者が項目を増減したい一覧: CPT。
- 編集者がリンク順を変えたいナビゲーション: WordPress メニュー。
- HTML 構造を含む長文: WYSIWYG + `wp_kses()` 許可リスト。
- テーマ側で固定すべき装飾やレイアウト: テンプレートと CSS に残す。

## 避けること

- ACF 値、CPT メタ、オプション値をそのまま `echo` する。
- ACF 関数を存在確認なしで呼ぶ。
- seed 処理で既存編集を無条件に上書きする。
- 反復項目をテンプレートに配列で直書きし続ける。
- メニューにできるリンク一覧をテンプレートに固定する。
- ACF key を公開後に変更する。
- 本番や意図しないローカルサイトに対して seed / 強制上書きスクリプトを実行する。
- テーマ固有のルールを確認せず、汎用例のファイル名や関数名をそのまま押し付ける。
