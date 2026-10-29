<?php
global $cnrs_global_params;
$custom_params = new CnrswebkitStdListParams();
$custom_params->where = [
    'relation' => 'AND',
    [
        'key' => 'date_de_fin',
        'value' => current_time('mysql'),
        'compare' => '>='
    ],
    [
        'key' => 'ID',
        'value' => get_the_ID(),
        'compare' => '!='
    ]
];
$custom_params->limit = 2;
$evenement_data = new CnrswebkitPageItemsList('evenement', $custom_params);
$event_page = $cnrs_global_params->field('pageliste_evenement');
$liste_evenement_url = is_array($event_page) && !empty($event_page['ID'])
    ? get_permalink(absint($event_page['ID']))
    : false;
if ($liste_evenement_url) {
	$liste_evenement_url = '<a href="'. $liste_evenement_url . '">' . __('Browse Events list', 'cnrswebkit') . '</a>';
}

if ($evenement_data->has_items()) {
    ?>
    <div class="nextEvents">
        <header><h1><?php _e('Next events', 'cnrswebkit') ?></h1><?php echo ($liste_evenement_url? $liste_evenement_url: '')?></header>
        <?php
        echo $evenement_data->get_html_item_list('bottomevenement');
        ?>
    </div>
    <?php
}
