<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' ); ?>

<div class="shop-wrapper">
	<?php woocommerce_content(); ?>
</div>

<?php get_footer( 'shop' ); ?>
