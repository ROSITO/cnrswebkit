<?php
global $cnrs_global_params; 
$custom_params = new CnrswebkitStdListParams();
$custom_params->where = [
    'key' => 'ID',
    'value' => get_the_ID(),
    'compare' => '!='
];
$custom_params->orderby = 'ID DESC';
$custom_params->limit = 2;
$emploi_data = new CnrswebkitPageItemsList('emploi', $custom_params);

$emploi_page = $cnrs_global_params->field('pageliste_emploi');
$liste_emploi_url = is_array($emploi_page) && !empty($emploi_page['ID'])
    ? get_permalink(absint($emploi_page['ID']))
    : false;

if ($liste_emploi_url) {
    $liste_emploi_url = '<a href="'. $liste_emploi_url . '">' . __('Return to recruitment list', 'cnrswebkit') . '</a>';
}

if ($emploi_data->has_items()) {
    ?>
    <div class="nextEvents">
        <header><h1><?php _e('Other recruitment', 'cnrswebkit') ?></h1><?php echo ($liste_emploi_url? $liste_emploi_url: '')?></a></header>
        <?php
        echo $emploi_data->get_html_item_list('bottomemploi');
        ?>  
    </div>
    <?php
}
