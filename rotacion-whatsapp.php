<?php
function start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'start_session', 1);
/*
Plugin Name: Rotación de Vendedores WhatsApp
Description: Plugin para rotar números de WhatsApp de vendedores.
Version: 1.0
Author: Mintaka Software
*/

function whatsapp_vendedores_install() {
    global $wpdb;

    $table_name = $wpdb->prefix . "whatsapp_vendedores";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        numero_whatsapp varchar(15) NOT NULL,
        contador_contacto int DEFAULT 0 NOT NULL,
        fecha_ultimo_contacto timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $table_name_stats = $wpdb->prefix . "whatsapp_vendedores_stats";

    $sql = "CREATE TABLE $table_name_stats (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        vendedor_id mediumint(9) NOT NULL,
        date timestamp NOT NULL,
        clicks mediumint(9) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        FOREIGN KEY (vendedor_id) REFERENCES $table_name(id)
    ) $charset_collate;";

    dbDelta($sql);
}

register_activation_hook( __FILE__, 'whatsapp_vendedores_install' );

function whatsapp_vendedores_menu() {
    add_menu_page('Vendedores WhatsApp', 'Vendedores WhatsApp', 'manage_options', 'whatsapp_vendedores', 'whatsapp_vendedores_page_content');
    add_submenu_page('whatsapp_vendedores', 'Estadísticas WhatsApp', 'Estadísticas', 'manage_options', 'whatsapp_vendedores_stats', 'whatsapp_vendedores_stats_content');
}

add_action('admin_menu', 'whatsapp_vendedores_menu');

function whatsapp_vendedores_page_content() {
    global $wpdb;
    $table_name = $wpdb->prefix . "whatsapp_vendedores";

    // Guardar un nuevo número
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["numero_whatsapp"])) {
        $numero_whatsapp = sanitize_text_field($_POST["numero_whatsapp"]);
        $wpdb->insert($table_name, ['numero_whatsapp' => $numero_whatsapp]);
        echo "<div class='updated'><p>Número agregado con éxito</p></div>";
    }

    // Eliminar un número
    if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["action"]) && $_GET["action"] == "delete") {
        $id = intval($_GET["id"]);
        $wpdb->delete($table_name, ['id' => $id]);
        echo "<div class='updated'><p>Número eliminado con éxito</p></div>";
    }

    $vendedores = $wpdb->get_results("SELECT * FROM $table_name");
    ?>
    
    <div class="wrap">
        <h2>Agregar nuevo vendedor WhatsApp</h2>
        <form method="post">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="numero_whatsapp">Número de WhatsApp</label>
                        </th>
                        <td>
                            <input name="numero_whatsapp" type="text" id="numero_whatsapp" class="regular-text">
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button('Agregar número'); ?>
        </form>

        <!-- Lista de números -->
        <h2>Números existentes</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column">Número de WhatsApp</th>
                    <th scope="col" class="manage-column">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendedores as $vendedor): ?>
                    <tr>
                        <td><?php echo $vendedor->numero_whatsapp; ?></td>
                        <td>
                            <a href="?page=whatsapp_vendedores&action=edit&id=<?php echo $vendedor->id; ?>">Editar</a> | 
                            <a href="?page=whatsapp_vendedores&action=delete&id=<?php echo $vendedor->id; ?>" onclick="return confirm('¿Estás seguro de que quieres eliminar este número?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function register_whatsapp_click($vendedor_id) {

    global $wpdb;
    $table_name = $wpdb->prefix . "whatsapp_vendedores_stats";
    $current_date = date('Y-m-d');

    $entry = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE vendedor_id = %d AND date = %s",
        $vendedor_id, $current_date
    ));

    if ($entry) {
        $wpdb->update(
            $table_name,
            ['clicks' => $entry->clicks + 1],
            ['id' => $entry->id]
        );
    } else {
        $wpdb->insert($table_name, [
            'vendedor_id' => $vendedor_id,
            'date' => $current_date,
            'clicks' => 1
        ]);
    }
    $table_name = $wpdb->prefix . "whatsapp_vendedores";

    // Obtener el vendedor que tiene el menor número de contactos.
    $vendedor = $wpdb->get_row("SELECT * FROM $table_name ORDER BY fecha_ultimo_contacto ASC LIMIT 1");

    if ($vendedor) {
        // Incrementar el contador_contacto para ese vendedor
        $wpdb->update($table_name, ['contador_contacto' => $vendedor->contador_contacto + 1, 'fecha_ultimo_contacto' => current_time('mysql')], ['id' => $vendedor->id]);

        return $vendedor;
    } else {
        return null;  // No se encontró ningún vendedor.
    }
}

function whatsapp_vendedores_stats_content() {
     global $wpdb;
    $table_name_vendedores = $wpdb->prefix . "whatsapp_vendedores";
    $table_name_stats = $wpdb->prefix . "whatsapp_vendedores_stats";

    $stats = $wpdb->get_results(
        "SELECT wv.numero_whatsapp, ws.date, ws.clicks 
        FROM $table_name_vendedores wv 
        INNER JOIN $table_name_stats ws ON wv.id = ws.vendedor_id 
        ORDER BY ws.date DESC, ws.clicks DESC"
    );

    echo '<div class="wrap">';
    echo '<h2>Estadísticas de Vendedores</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Número</th><th>Fecha</th><th>Clicks</th></tr></thead><tbody>';

    foreach ($stats as $stat) {
        echo "<tr>";
        echo "<td>{$stat->numero_whatsapp}</td>";
        echo "<td>{$stat->date}</td>";
        echo "<td>{$stat->clicks}</td>";
        echo "</tr>";
    }

    echo '</tbody></table>';
    echo '</div>';
}

function whatsapp_vendedores_enqueue_scripts() {
     wp_enqueue_style('whatsapp-vendedores', plugins_url('css/whatsapp-vendedores.css', __FILE__));
    wp_enqueue_script('whatsapp-vendedores-js', plugins_url('js/whatsapp-vendedores.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('whatsapp-vendedores-js', 'myAjax', array(
    'ajaxurl' => admin_url('admin-ajax.php')
));
}
add_action('wp_enqueue_scripts', 'whatsapp_vendedores_enqueue_scripts');

function render_whatsapp_button() {
     global $wpdb;
    $table_name = $wpdb->prefix . "whatsapp_vendedores";

    // Obtener todos los vendedores
    $vendedores = $wpdb->get_results("SELECT * FROM $table_name");

    $vendedor = get_next_vendedor();

    // Crear el enlace de WhatsApp
    $whatsapp_url = "https://wa.me/+52{$vendedor->numero_whatsapp}";

    echo "<button id='w-btn' data-whatsapp-url='{$whatsapp_url}' data-vendedor-id='". $vendedor->id . "'><img src='" . plugins_url('img/whatsapp-icon.png', __FILE__) . "' alt='WhatsApp'></button>";

}
add_action('wp_footer', 'render_whatsapp_button');



function get_next_vendedor() {
    global $wpdb;
    $table_name = $wpdb->prefix . "whatsapp_vendedores";

    // Obtener el vendedor que tiene el menor número de contactos.
    $vendedor = $wpdb->get_row("SELECT * FROM $table_name ORDER BY fecha_ultimo_contacto ASC LIMIT 1");

    if ($vendedor) {
        return $vendedor;
    } else {
        return null;  // No se encontró ningún vendedor.
    }
}

function increment_click_counter() {
    global $wpdb;

    $table_name = $wpdb->prefix . "whatsapp_vendedores_stats";
    $vendedor_id = intval($_POST['vendedor_id']);
    $vendedor_record = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE id = %d", $vendedor_id));

	if ($vendedor_record > 0) {
	    // Si el vendedor ya tiene un registro, actualiza el contador
	    $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $vendedor_id));
	} else {
	    // Si el vendedor no tiene un registro, inserta uno con el contador en 1
$wpdb->query($wpdb->prepare("INSERT INTO wp_whatsapp_vendedores_stats (vendedor_id, clicks) VALUES (%d)", $vendedor_id));

	}
    //$wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $vendedor_id));

    wp_send_json_success(['message' => 'Contador actualizado']);
}
add_action('wp_ajax_increment_click_counter', 'increment_click_counter'); // Si el usuario está logueado
add_action('wp_ajax_nopriv_increment_click_counter', 'increment_click_counter'); // Si el usuario no está logueado
function get_next_whatsapp_number() {
    global $wpdb;
    $table_name = $wpdb->prefix . "whatsapp_vendedores";

    // Obtener el vendedor que tiene el menor número de contactos.
    $vendedor = $wpdb->get_row("SELECT * FROM $table_name ORDER BY contador_contacto ASC, fecha_ultimo_contacto ASC LIMIT 1");

    if ($vendedor) {
        // Incrementar el contador_contacto para ese vendedor
        $wpdb->update($table_name, ['contador_contacto' => $vendedor->contador_contacto + 1, 'fecha_ultimo_contacto' => current_time('mysql')], ['id' => $vendedor->id]);

        return $vendedor->numero_whatsapp;
    } else {
        return false;  // No se encontró ningún vendedor.
    }
}


function print_whatsapp_button() {
    $whatsapp_number = get_next_whatsapp_number();

    if ($whatsapp_number) {
        $url = "https://wa.me/{$whatsapp_number}";
        echo "<a href='{$url}' id='whatsapp-btn' data-whatsapp-url='{$url}' data-vendedor-id='{$whatsapp_number}' style='position:fixed;bottom:20px;right:20px;'><img src='path_to_whatsapp_icon.png' alt='Chat with us on WhatsApp'></a>";
    }
}
add_action('wp_ajax_register_click', 'ajax_register_click');
add_action('wp_ajax_nopriv_register_click', 'ajax_register_click');

function ajax_register_click() {
    if(isset($_POST['vendedor_id'])) {
        $vendedor_id = intval($_POST['vendedor_id']);
        register_whatsapp_click($vendedor_id);
        echo 'Click registrado';
    } else {
        echo 'Error registrando el click';
    }
    die();
}
