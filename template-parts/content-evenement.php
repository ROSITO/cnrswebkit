<?php
/**
 * The template part for displaying single post Événement
 *
 * @package Atos
 * @subpackage CNRS_Web_Kit
 * @since CNRS Web Kit 0.3
 */

global $event_with_partners;

$current_item = new CnrswebkitRichData(get_the_ID());

ob_start();
?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ics-form">
    <input type="hidden" name="action" value="cnrswebkit_download_ics">
    <input type="hidden" name="post_id" value="<?php echo esc_attr(get_the_ID()); ?>">
    <?php wp_nonce_field('cnrswebkit_ics_' . get_the_ID(), 'cnrswebkit_ics_nonce'); ?>
</form>
<?php
$ics_form = ob_get_clean();
?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <header class="entry-header">
        <!--<div class="eventDateHeader"><?php echo get_event_dates($current_item->value('date_de_debut'), $current_item->value('date_de_fin'), 'dateheader'); ?></div>-->
        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
    </header><!-- .entry-header -->
    <div class="entry-content">
        <div class="leftCol">
            <div class="article-chapo"><?php echo $current_item->value('chapo'); ?></div>
            
        </div>
        <div class="rightCol">
            <div class="eventDateRight"><?php echo get_event_dates($current_item->value('date_de_debut'), $current_item->value('date_de_fin'), 'dateeventsingle'); ?></div>
            <div class="addCalendar"><?php echo $ics_form; ?><a href="#" id="ics-vbutton"><?php esc_html_e('Add to calendar', 'cnrswebkit'); ?></a></div>
            <div class="eventLocation">
                <?php
                if (filter_var($current_item->value('lien_vers_la_carte'), FILTER_VALIDATE_URL)) {
                    ?>
                    <a href="<?php echo esc_url($current_item->value('lien_vers_la_carte')); ?>" target="_blank" rel="noopener noreferrer"><?php echo wp_kses_post(text_to_html($current_item->value('adresse_de_levenement'))); ?></a>
                    <?php
                } else {
                    echo wp_kses_post(text_to_html($current_item->value('adresse_de_levenement')));
                }
                ?>
            </div>
            <?php echo get_the_post_thumbnail($current_item->value('ID'), 'cnrsloop-size'); ?>
        </div>
        <?php 
        the_content();
        if ($current_item->value('partenaires')) {
            $event_with_partners = true;
            $custom_params = new CnrswebkitStdListParams();
            $custom_params->where = $current_item->get_where_metadata($current_item->value('partenaires'));
            $partenaires_data = new CnrswebkitPageItemsList('partenaire', $custom_params);
            ?>
            <div class="tutellesContainer">
                <div class="partTitle"><?php _e("Partenaires de l'évènement", 'cnrswebkit') ?></div>
                <div class="partContainer">
                    <?php echo $partenaires_data->get_html_item_list('bottompartenaire'); ?>
                </div>
            </div>
            <?php
        }
        display_bottom_evenements(); 

        ?>
    </div><!-- .entry-content -->
    <footer class="entry-footer">
        <?php cnrswebkit_entry_meta(); ?>
    </footer><!-- .entry-footer -->
</article><!-- #post-## -->
