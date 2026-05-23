<?php
/**
 * Hero mindMap section (Next.tsx inline section).
 *
 * @package Theme
 */

declare(strict_types=1);

$hero_brand   = (string) theme_portfolio_meta( 'pf_hero_brand', "yuremono\nworks" );
$hero_heading = (string) theme_portfolio_meta( 'pf_hero_heading', "2025/05からAI駆動開発を開始\nヴィジュアル表現をAIでブーストし\nコンテキストエンジニアリングに注力しています" );
$hero_node_1  = (string) theme_portfolio_meta( 'pf_hero_node_1', 'Context' );
$hero_node_2  = (string) theme_portfolio_meta( 'pf_hero_node_2', 'Development' );
$hero_node_3  = (string) theme_portfolio_meta( 'pf_hero_node_3', 'Web' );
?>
<section class="out mindMap text-center font-thin">
	<?php if ( false ) : ?>
	<p class="hidden mmPin about_p lg:w-[calc(var(--wid)/2)] text-[--GR] font-light text-center p-4 px-6 right-1/2 top-1/2 lg:translateYH static lg:absolute" style="font-size: 3em;">
		<?php echo wp_kses_post( nl2br( esc_html( $hero_brand ), false ) ); ?>
	</p>
	<?php endif; ?>
	<h1 class="BudouxShow text-xl font-normal budoux mmPin static leading-[2em] left-1/2 top-1/2 z-10 text-left px-[--PX2] md:p-4 bg-background/80">
		<?php echo wp_kses_post( nl2br( esc_html( $hero_heading ), false ) ); ?>
	</h1>
	<?php if ( '' !== $hero_node_3 ) : ?>
		<p class="mm2-3" style="font-size: 5em;"><?php echo esc_html( $hero_node_3 ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $hero_node_1 ) : ?>
		<p class="hidden lg:inline-block mm3-8" style="font-size: 4em;"><?php echo esc_html( $hero_node_1 ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $hero_node_2 ) : ?>
		<p class="mm9-5" style="font-size: 4em;"><?php echo esc_html( $hero_node_2 ); ?></p>
	<?php endif; ?>
</section>
