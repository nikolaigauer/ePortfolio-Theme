<?php
/**
 * Title: Landing Page Header
 * Slug: eportfolio-theme/header-landing
 * Inserter: no
 * Description: Header for the cohort landing page (home.html). Contains site title and navigation block for the Student Authors menu.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull"><!-- wp:group {"metadata":{"categories":["header","wireframe"],"patternName":"core/simple-header","name":"Simple header"},"align":"full","className":"is-style-default","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","right":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30"}},"elements":{"link":{"color":{"text":"var:preset|color|base"}}}},"backgroundColor":"contrast","textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-default has-base-color has-contrast-background-color has-text-color has-background has-link-color" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"style":{"spacing":{"blockGap":"24px"},"elements":{"link":{"color":{"text":"var:preset|color|base"}}}},"textColor":"base","layout":{"type":"flex"}} -->
<div class="wp-block-group has-base-color has-text-color has-link-color"><!-- wp:site-title {"style":{"elements":{"link":{"color":{"text":"var:preset|color|base"}}}}} /--></div>
<!-- /wp:group -->

<!-- STUDENT AUTHORS NAVIGATION: This navigation block should contain the "Student Authors" menu.
     To set this up:
     1. Go to Dashboard → ePortfolio
     2. Click "Generate/Update Student Menu" (requires author accounts to exist)
     3. Return to Site Editor → Templates → Landing Page
     4. Click on this navigation block and select the "Student Authors" menu
     
     The navigation block is intentionally empty on fresh install until the menu is generated and assigned.
-->
<!-- wp:navigation {"layout":{"type":"flex","setCascadingProperties":true}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
