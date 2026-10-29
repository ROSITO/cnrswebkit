<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_load_more', 'load_more');
add_action('wp_ajax_nopriv_load_more', 'load_more');
add_action('wp_ajax_contact_form', 'contact_form');
add_action('wp_ajax_nopriv_contact_form', 'contact_form');
add_action('wp_ajax_submit_contact_form', 'submit_contact_form');
add_action('wp_ajax_nopriv_submit_contact_form', 'submit_contact_form');
add_action('wp_ajax_submit_emploi_form', 'submit_emploi_form');
add_action('wp_ajax_nopriv_submit_emploi_form', 'submit_emploi_form');
add_action('wp_ajax_submit_newsletter_form', 'submit_newsletter_form');
add_action('wp_ajax_nopriv_submit_newsletter_form', 'submit_newsletter_form');
add_action('admin_post_cnrswebkit_download_ics', 'cnrswebkit_download_ics');
add_action('admin_post_nopriv_cnrswebkit_download_ics', 'cnrswebkit_download_ics');
add_action('wp_enqueue_scripts', 'cnrsajaxenqueue');

function cnrsajaxenqueue() {
    wp_enqueue_script(
        'cnrswebkit-ajax',
        get_template_directory_uri() . '/js/cnrs-ajax.js',
        array('jquery', 'cnrswebkit-init'),
        defined('CNRSWEBKIT_VERSION') ? CNRSWEBKIT_VERSION : '0.5.1',
        true
    );
    wp_localize_script(
        'cnrswebkit-ajax',
        'adminAjax',
        array(
            'url'   => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cnrswebkit_ajax'),
        )
    );
}

function cnrswebkit_verify_ajax_request() {
    check_ajax_referer('cnrswebkit_ajax', 'nonce');
}

function cnrswebkit_ajax_target() {
    $target = isset($_POST['target']) ? sanitize_text_field(wp_unslash($_POST['target'])) : '';
    return preg_match('/^#[A-Za-z0-9_-]+$/', $target) ? $target : '';
}

function cnrswebkit_rate_limit($action, $maximum = 5, $window = 600) {
    $address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
    $key = 'cnrs_rl_' . md5($action . '|' . $address);
    $attempts = (int) get_transient($key);

    if ($attempts >= $maximum) {
        wp_send_json_error(array('message' => __('Trop de tentatives. Merci de réessayer plus tard.', 'cnrswebkit')), 429);
    }

    set_transient($key, $attempts + 1, $window);
}

function load_more() {
    cnrswebkit_verify_ajax_request();
    global $cnrs_global_params;

    $target = cnrswebkit_ajax_target();
    $area = isset($_POST['area']) ? sanitize_key(wp_unslash($_POST['area'])) : '';
    $page = isset($_POST['page']) ? max(1, absint($_POST['page'])) : 1;

    if (!$target || !$cnrs_global_params) {
        wp_send_json_error(array('message' => __('Requête invalide.', 'cnrswebkit')), 400);
    }

    switch ($area) {
        case 'evenement':
            $custom_params = new CnrswebkitStdListParams();
            $custom_params->limit = -1;
            $evenement_data = new CnrswebkitPageItemsList('evenement', $custom_params);
            $total_items = $evenement_data->total_items();
            $limit = max(1, absint($cnrs_global_params->field('nombre_devenements_page_agenda')));
            $custom_params->limit = $limit;
            $custom_params->page = $page;
            $evenement_data = new CnrswebkitPageItemsList('evenement', $custom_params);
            ob_start();
            $evenement_data->get_html_item_list();
            $data_html = ob_get_clean();
            $data_html .= $total_items > ($limit * $page)
                ? "<script>jQuery('.moreEvents a').attr('page', '" . esc_js($page) . "');</script>"
                : "<script>jQuery('.moreEvents').hide();</script>";
            break;

        case 'contact':
            $custom_params = new CnrswebkitStdListParams();
            $custom_params->limit = -1;
            $contact_data = new CnrswebkitPageItemsList('contact', $custom_params);
            $total_items = $contact_data->total_items();
            $limit = max(1, absint($cnrs_global_params->field('nombre_decontacts_page_contact')));
            $custom_params->limit = $limit;
            $custom_params->page = $page;
            $contact_data = new CnrswebkitPageItemsList('contact', $custom_params);
            ob_start();
            $contact_data->get_html_item_list();
            $data_html = ob_get_clean();
            $data_html .= $total_items > ($limit * $page)
                ? "<script>jQuery('.moreContacts a').attr('page', '" . esc_js($page) . "');</script>"
                : "<script>jQuery('.moreContacts').hide();</script>";
            $data_html .= '<script>contactClick();</script>';
            break;

        default:
            wp_send_json_error(array('message' => __('Zone inconnue.', 'cnrswebkit')), 400);
    }

    wp_send_json_success(array('to_html' => array(array('action' => 'append', 'target' => $target, 'data' => $data_html))));
}

function contact_form() {
    cnrswebkit_verify_ajax_request();
    $form_id = isset($_POST['formid']) ? absint($_POST['formid']) : 0;
    $area = isset($_POST['area']) ? sanitize_key(wp_unslash($_POST['area'])) : '';
    $target = cnrswebkit_ajax_target();

    if (!$form_id || !$target || !in_array($area, array('emploi', 'contact'), true)) {
        wp_send_json_error(array('message' => __('Formulaire invalide.', 'cnrswebkit')), 400);
    }

    ob_start();
    include locate_template('template-parts/' . ('emploi' === $area ? 'emploi_form.php' : 'contact_form.php'));
    $data_html = ob_get_clean();
    $action = 'emploi' === $area ? 'submit_emploi_form' : 'submit_contact_form';
    $close = 'emploi' === $area ? 'closeEmploiFormPopin();' : '';
    $data_html .= '<script>
    jQuery("#contact-form-submit-' . esc_js($form_id) . '").off("click").on("click", function () {
        runAjaxQuery({
            action: "' . esc_js($action) . '", area: "' . esc_js($area) . '",
            formid: "' . esc_js($form_id) . '", target: "' . esc_js($target) . '",
            object: jQuery("#object' . esc_js($form_id) . '").val(),
            nom: jQuery("#nom' . esc_js($form_id) . '").val(),
            prenom: jQuery("#prenom' . esc_js($form_id) . '").val(),
            email: jQuery("#email' . esc_js($form_id) . '").val(),
            message: jQuery("#message' . esc_js($form_id) . '").val()
        });
    });
    jQuery("#contact-form-cancel-' . esc_js($form_id) . '").off("click").on("click", function () {
        jQuery("' . esc_js($target) . '").empty(); ' . $close . '
    });
</script>';

    wp_send_json_success(array('to_html' => array(array('action' => 'html', 'target' => $target, 'data' => $data_html))));
}

function cnrswebkit_submit_message($type) {
    cnrswebkit_verify_ajax_request();
    cnrswebkit_rate_limit($type, 5, 600);

    $form_id = isset($_POST['formid']) ? absint($_POST['formid']) : 0;
    $target = cnrswebkit_ajax_target();
    $object = isset($_POST['object']) ? sanitize_text_field(wp_unslash($_POST['object'])) : '';
    $nom = isset($_POST['nom']) ? sanitize_text_field(wp_unslash($_POST['nom'])) : '';
    $prenom = isset($_POST['prenom']) ? sanitize_text_field(wp_unslash($_POST['prenom'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
    $errors = array();

    if (!$form_id || !$target) {
        wp_send_json_error(array('message' => __('Impossible de trouver le destinataire.', 'cnrswebkit')), 400);
    }
    if (!$nom) {
        $errors[] = __('Vous devez indiquer votre nom.', 'cnrswebkit');
    }
    if (!$prenom) {
        $errors[] = __('Vous devez indiquer votre prénom.', 'cnrswebkit');
    }
    if (!$email || !is_email($email)) {
        $errors[] = __('Vous devez indiquer une adresse de messagerie valide.', 'cnrswebkit');
    }
    if (!$message) {
        $errors[] = __('Vous devez saisir un message.', 'cnrswebkit');
    }

    if ($errors) {
        wp_send_json_success(array('to_html' => array(array(
            'action' => 'html',
            'target' => $target . '-errors',
            'data' => implode('<br />', array_map('esc_html', $errors)),
        ))));
    }

    $contact_info = new CnrswebkitRichData($form_id);
    $is_job = 'emploi' === $type;
    $to = sanitize_email($contact_info->value($is_job ? 'email_contact' : 'email'));
    if (!$to || !is_email($to)) {
        wp_send_json_error(array('message' => __('Le destinataire ne possède pas d’adresse valide.', 'cnrswebkit')), 422);
    }

    if (!$object) {
        $object = sprintf(__('Nouveau message de %1$s %2$s depuis le site %3$s', 'cnrswebkit'), $prenom, $nom, get_bloginfo('name'));
    }

    ob_start();
    include locate_template($is_job ? 'template-parts/mailbody-emploi.php' : 'template-parts/mailbody-contact.php');
    $mail_body = ob_get_clean();
    $site_email = sanitize_email(get_option('admin_email'));
    $site_name = sanitize_text_field(get_bloginfo('name'));
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $prenom . ' ' . $nom . ' <' . $email . '>',
    );
    if ($site_email && is_email($site_email)) {
        $headers[] = 'From: ' . $site_name . ' <' . $site_email . '>';
    }

    if (!wp_mail($to, $object, $mail_body, $headers)) {
        wp_send_json_error(array('message' => __('Le message n’a pas pu être envoyé.', 'cnrswebkit')), 500);
    }

    wp_send_json_success(array('to_html' => array(array(
        'action' => 'html', 'target' => $target, 'data' => esc_html__('Votre message a bien été envoyé.', 'cnrswebkit'),
    ))));
}

function submit_contact_form() {
    cnrswebkit_submit_message('contact');
}

function submit_emploi_form() {
    cnrswebkit_submit_message('emploi');
}

function submit_newsletter_form() {
    cnrswebkit_verify_ajax_request();
    cnrswebkit_rate_limit('newsletter', 5, 3600);
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

    if (!$email || !is_email($email)) {
        wp_send_json_error(array('message' => __('Adresse email incorrecte.', 'cnrswebkit')), 422);
    }

    $custom_params = new CnrswebkitStdListParams();
    $custom_params->where = array(
        'relation' => 'AND',
        array('key' => 'email', 'value' => $email, 'compare' => '='),
        array('key' => 'post_status', 'value' => array('publish', 'draft'), 'compare' => 'IN'),
    );
    $custom_params->limit = 1;
    $custom_params->orderby = 'email';
    $previous_registration = new CnrswebkitPageItemsList('newsletter', $custom_params);

    if (!$previous_registration->has_items()) {
        pods('newsletter')->save(array('title' => $email, 'email' => $email));
        $message = __('Votre email a bien été enregistré.', 'cnrswebkit');
    } else {
        $message = __('Votre email est déjà enregistré.', 'cnrswebkit');
    }

    wp_send_json_success(array('to_html' => array(array(
        'action' => 'html', 'target' => '#NewsletterResult', 'data' => esc_html($message),
    ))));
}

function cnrswebkit_download_ics() {
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $nonce = isset($_POST['cnrswebkit_ics_nonce']) ? sanitize_text_field(wp_unslash($_POST['cnrswebkit_ics_nonce'])) : '';

    if (!$post_id || !wp_verify_nonce($nonce, 'cnrswebkit_ics_' . $post_id)) {
        wp_die(esc_html__('Lien de calendrier invalide ou expiré.', 'cnrswebkit'), '', array('response' => 403));
    }

    $post = get_post($post_id);
    if (!$post || 'publish' !== $post->post_status || 'evenement' !== $post->post_type) {
        wp_die(esc_html__('Évènement introuvable.', 'cnrswebkit'), '', array('response' => 404));
    }

    $item = new CnrswebkitRichData($post_id);
    require_once get_template_directory() . '/tools/ICS.php';

    try {
        $ics = new ICS(array(
            'location' => (string) $item->value('adresse_de_levenement'),
            'description' => wp_strip_all_tags((string) $item->value('chapo')),
            'dtstart' => (string) $item->value('date_de_debut'),
            'dtend' => (string) $item->value('date_de_fin'),
            'summary' => get_the_title($post),
            'url' => get_permalink($post),
        ));
        $contents = $ics->to_string();
    } catch (Exception $exception) {
        wp_die(esc_html__('Les dates de cet évènement sont invalides.', 'cnrswebkit'), '', array('response' => 422));
    }

    nocache_headers();
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($post->post_name ?: 'evenement-cnrs') . '.ics"');
    header('X-Content-Type-Options: nosniff');
    echo $contents; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- RFC 5545 document.
    exit;
}
