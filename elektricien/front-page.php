<?php
get_header();
$options = get_option('dbt_settings');


?>

<?php get_template_part( 'template-parts/hero' ); ?>
<?php get_template_part( 'template-parts/about-section' ); ?>
<?php get_template_part( 'template-parts/reviews-section' ); ?>
<?php get_template_part( 'template-parts/why-choose-us' ); ?>
<?php get_template_part( 'template-parts/services-section' ); ?>
<?php get_template_part( 'template-parts/theme-costs' ); ?>
<?php get_template_part( 'template-parts/theme-faq' ); ?>
<?php get_template_part( 'template-parts/theme-map' ); ?>
<?php get_template_part( 'template-parts/theme-contact' ); ?>




<?php get_footer(); ?>
