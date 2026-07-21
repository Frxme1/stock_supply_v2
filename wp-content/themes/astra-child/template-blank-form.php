<?php
/**
 * Template Name: Standalone Form Page
 *
 * A blank template suitable for standalone forms (like Google Forms).
 * It removes all theme headers, footers, sidebars, and menus.
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <style>
        /* Force body to be clean and neutral, overriding any theme defaults */
        body {
            background-color: #f0f2f5 !important; /* Light neutral gray, similar to Google Forms */
            background-image: none !important;
            margin: 0 !important;
            padding: 40px 0 !important;
            display: flex !important;
            justify-content: center !important;
            align-items: flex-start !important;
            min-height: 100vh !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
        }
        #page, #content, .site, .ast-container, .site-content {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }
        /* Hide common Astra theme elements */
        header, footer, #masthead, #colophon, .sidebar, .ast-breadcrumbs-wrapper, .site-header, .site-footer {
            display: none !important;
        }
        /* Container for the form to ensure it centers nicely */
        .standalone-wrapper {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            padding: 0 20px;
            box-sizing: border-box;
        }
    </style>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>
    
    <div class="standalone-wrapper">
        <?php
        while ( have_posts() ) :
            the_post();
            the_content();
        endwhile;
        ?>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
