<?php
/*
  Plugin Name: Gallery Wordpress Paypal
  Description: A library used to order image prints through paypal.
  Author: Mark McKellar
  Text Domain: gallery-order
 */
namespace OrderGallery;
defined('ABSPATH') or die('No script kiddies please!');

global $gallery_order_db_version;
$gallery_order_db_version = '1.1';

define("STATUS_OPEN", 0);
define("STATUS_CANCEL", 1);
define("STATUS_DECLINED", 2);
define("STATUS_APPROVED", 3);
define("STATUS_DELIVERED", 4);

include 'gallery-options.php';
include 'gallery-post.php';
include 'gallery-paypal.php';

function order_link_short($atts, $content = null){
	$a = shortcode_atts(array(
		'id' => uniqid(),
        'order' => 0,
		'button_class' => '',
		'button_text' => 'Purchase',
		'popup_class' => '',
	), $atts, 'order_gallery');
	
	ob_start();
	create_order_link($a);
	$output = ob_get_contents();
	ob_end_clean();
	
	return $output;
}
add_shortcode('order_gallery', 'OrderGallery\order_link_short');

/* Database Functions */
function create_order_database(){
    // From tutorial: https://codex.wordpress.org/Creating_Tables_with_Plugins
    global $wpdb;
    global $gallery_order_db_version;

   $table_name = $wpdb->prefix . "gallery_order"; 

   $charset_collate = $wpdb->get_charset_collate();

   $sql = "CREATE TABLE $table_name (
        id mediumint(10) NOT NULL AUTO_INCREMENT,
        guid varchar(25) NOT NULL,
        token varchar(25) NOT NULL,
        time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        payment text NOT NULL,
        fields text NOT NULL, 
        error text,
        message text,
        status smallint(2) NOT NULL,
        tax DECIMAL(8, 2) NOT NULL,
        total DECIMAL(8, 2) NOT NULL,
        CONSTRAINT guid_key UNIQUE (guid),
        PRIMARY KEY (id)
   ) $charset_collate;";

   require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

    add_option( 'gallery_order_db_version', $gallery_order_db_version );
}

function update_db_check() {
    global $gallery_order_db_version;
    $siteOption = get_site_option('gallery_order_db_version');
    if($siteOption != $gallery_order_db_version){
        create_order_database();
    }
}
add_action('plugins_loaded', 'OrderGallery\update_db_check');

function create_order_entry($guid, $token, $payment, $fields_data, $tax, $total){
    global $wpdb;

    $table_name = $wpdb->prefix . "gallery_order"; 
    $wpdb->insert(
        $table_name,
        array(
            'guid' => $guid,
            'token' => $token,
            'time' => current_time('mysql'),
            'payment' => print_r($payment, 1),
            'fields' => json_encode($fields_data),
            'status' => STATUS_OPEN,
            'tax' => $tax,
            'total' => $total
        )
    );
}

function get_order_entry($guid){
    global $wpdb;

    $table_name = $wpdb->prefix . "gallery_order";
    $query = "SELECT * FROM $table_name WHERE guid = '$guid'";
    $row_result = $wpdb->get_row($query, ARRAY_A);
    
    return $row_result;
}

function update_order_entry($guid, $data){
    global $wpdb;

    $table_name = $wpdb->prefix . "gallery_order";
    $wpdb->update(
        $table_name,
        array(
            'token' => $data['token'],
            'time' => $data['time'],
            'payment' => $data['payment'],
            'fields' => $data['fields'],
            'status' => $data['status'],
            'tax' => $data['tax'],
            'total' => $data['total'],
            'error' => $data['error'],
            'message' => $data['message']
        ),
        array(
            'guid' => $guid
        )
    );
}

/* End Database Functions */

function add_jquery_variable($var_name){
	$gallery_options = get_option('order_gallery_settings');
	$var_result = empty($gallery_options['jquery_name']) ? '$' : $gallery_options['jquery_name'];
	?>
	var <?php echo $var_name ?> = <?php echo $var_result ?>;
	<?php
}

function create_order_button($id, $id_window, $class, $text){
	?>
	<button id='<?php echo $id ?>' class='<?php echo $class ?>'><?php echo $text ?></button>
	<script type="text/javascript">
	{
		<?php add_jquery_variable('og') ?>
		og('#<?php echo $id ?>').click(function(){
			og('#<?php echo $id_window?>').show();
		})
	}
	</script>
	<?php
}

function include_dir(){
    return dirname(__FILE__) . '/includes';
}

function paypal_dir(){
    return include_dir() . '/PayPal/';
}

function create_order_window($order_post, $id, $class){
    $price = get_post_meta($order_post->ID, '_go_price', true);
    $fields = explode("\n", get_post_meta($order_post->ID, "_go_fields", true));

	$id_modal = $id . '_modal';
	?>
	<div id="<?php echo $id ?>" style="display: none; position: fixed; top: 0; left: 0; bottom: 0; right: 0; bottom: 0; z-index: 10000; background-color: rgba(0,0,0,0.4);">
		<div id="<?php echo $id_modal ?>" class="<?php echo $class ?>" style="width: 99%; max-width: 400px; margin-top: 10px; padding: 10px; border-radius: 5px; background-color: #fff; margin-left: auto; margin-right: auto; z-index: 10001">
		    <h2>Order <?php echo htmlspecialchars($order_post->post_title) ?>: $<?php echo htmlspecialchars($price) ?></h2>
            <form method="POST" action="">
                <?php foreach($fields as $key => $value){ 
                    $name = preg_replace("/[^A-Za-z0-9]/", "", $value);
                    $field_id = $id . '_' . $name; 
                    ?>
                    <div>
                        <label for="<?php echo $field_id ?>"><?php echo htmlspecialchars($value) ?></label>
                        <input type="text" id="<?php echo htmlentities($field_id) ?>" name="<?php echo htmlentities($name) ?>" class="input"/>
                        <span class="error"></span>
                    </div>
                <?php } ?>
                
                <input type="hidden" name="gallery_order_post_id_field" value="<?php echo $order_post->ID ?>" />

                <div>
                    <input type="submit" name="gallery_order_submit_button"/>
                    <button id="<?php echo $id ?>_cancel">Cancel</button>
                </div>
            </form>
		</div>
	</div>
	<script type="text/javascript">
		<?php add_jquery_variable('og') ?>
		og('#<?php echo $id ?>_cancel').click(function(){
			og('#<?php echo $id?>').hide();
            return false;
		});
        og('#<?php echo $id_modal ?> > form').submit(function(event){
            var validated = true;

            og('#<?php echo $id_modal ?> > form input.input').each(function(index){
                var isValid = false;
                var val = og(this).val();
                
                if(val && val != null && val.trim().length > 0){
                    isValid = true;
                }

                if(!isValid){
                    validated = false;
                    og(this).siblings('.error').text('Please enter a value');
                }else{
                    og(this).siblings('.error').text('');
                }
            });
            
            if(!validated){
                event.preventDefault();
            }
        });
	</script>
	<?php
}

function create_order_link($att){
	$id_window = $att['id'] . '_window';
    $order_post = get_post($att['order']);
	create_order_button($att['id'], $id_window, $att['button_class'], $att['button_text']);
	create_order_window($order_post, $id_window, $att['popup_class']);
}

function handle_submit(){
    if(isset($_POST['gallery_order_post_id_field']) && isset($_POST['gallery_order_submit_button'])){
        $gallery_options = get_option('order_gallery_settings');

        // Get the order information
        $order_post = get_post(intval($_POST['gallery_order_post_id_field']));
        $price = get_post_meta($order_post->ID, '_go_price', true);
        $fields = explode("\n", get_post_meta($order_post->ID, "_go_fields", true));
        $tax_percent = floatval(isset($gallery_options['tax_percent']) ? $gallery_options['tax_percent'] : "0.00") / 100.0;

        $order_id = uniqid();
        $order_sku = strval($order_post->ID);
        $order_name = $order_post->post_title;
        $order_description = $order_post->post_content;
        $order_price = floatval($price);
        $order_tax = round($order_price * $tax_percent, 2, PHP_ROUND_HALF_EVEN);
        $order_fields = array();


        foreach($fields as $key => $value){
            $name = preg_replace("/[^A-Za-z0-9]/", "", $value);
            $order_fields[$name] = sanitize_text_field($_POST[$name]);
        }

        $returning_page = strtok($_SERVER['HTTP_REFERER'], '?');

        // Call paypal
        $result = create_payment($order_id, $order_sku, $order_name, $order_description, $order_price, $order_tax, $order_fields, $returning_page);
        $payment = $result['payment']->toJSON();

        create_order_entry($order_id, $payment->token ? $payment->token : 'NONE', $payment, $order_fields, $order_tax, $order_tax + $order_price);

        //Navigate to the paypal page
        if($result['hasError']){
            $order_entry = get_order_entry($order_id);
            $order_entry['status'] = STATUS_DECLINED;
            $order_entry['error'] = $result['error'];
            $order_entry['message'] = "Error creating the order.";
            $order_entry['time'] = current_time('mysql');
        }else{
            wp_redirect($result['approvalUrl']);
            exit;
        }

        
    }else if(isset($_GET['cancel']) && isset($_GET['token'])){
        // Handle Cancel
        $order_entry = get_order_entry($_GET['cancel']);
        
        if($order_entry['status'] == STATUS_OPEN){
            $order_entry['status'] = STATUS_CANCEL;
            $order_entry['message'] = "Canceled by payer.";
            $order_entry['time'] = current_time('mysql');

            update_order_entry($_GET['cancel'], $order_entry);
        } 
    }
    else if(isset($_GET['PayerID']) && isset($_GET['paymentId'])){
        $order_entry = get_order_entry($_GET['order']);
        $order_result = handle_payment($order_entry, $_GET['paymentId'], $_GET['PayerID']);

        update_order_entry($_GET['order'], $order_result);

        //TODO: Send email
    }
}
add_action('init', 'OrderGallery\handle_submit');

/* View Payments Page */
function payments_generate_link($satus, $page, $per_page){
    $link = strtok($_SERVER['REQUEST_URI'], '?');
    $link .= '?status=' . $status;
    $link .= "&page=$page&perpage=$per_page";

    return $link;
}

function payments_status_string($link_status){
    switch($link_status){
        case STATUS_OPEN:
            return "Open";
            break;
        case STATUS_CANCEL:
            return "Cancel";
            break;
        case STATUS_DECLINED:
            return "Declined";
            break;
        case STATUS_APPROVED:
            return "Approved";
            break;
        case STATUS_DELIVERED:
            return "Delivered";
            break;
    }
}

function payments_html(){
    global $wpdb;
    $table_name = $wpdb->prefix . "gallery_order";

    $status = isset($_GET['status']) ? intval($_GET['status']) : STATUS_APPROVED;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
    $per_page = isset($_GET['perpage']) ? intval($_GET['perpage']) : 1000;
    $start_index = $page * $per_page;

    $query = "SELECT * FROM $table_name ORDER BY time DESC LIMIT $start_index,$per_page;";

    ?>
    <style>
        .order_table .head {
            background-color:  #9fdaea;
        }
        
        .order_table .body {
            background-color:  #fff;
        }
    </style>
    <h1>Orders for <?php _e(payments_status_string($status))?></h1>
    <div>
       <a href="<?php echo payments_generate_link(STATUS_APPROVED, $page, $per_page) ?>">Approved</a>
       <a href="<?php echo payments_generate_link(STATUS_DELIVERED, $page, $per_page) ?>">Delivered</a>
    </div>
    <div>
        <table class="order_table">
            <tr class="head">
                <th>Order Id</th>
                <th>Last Updated</th>
                <th>Fields</th>
                <th>Status</th>
                <th>Subtotal</th>
                <th>Tax</th>
                <th>Total</th>
                <th>Actions</th>
            </tr>
            <?php foreach($wpdb->get_results($query, ARRAY_A) as $key => $row){ ?>
            <tr class="body">
                <td><?php echo htmlspecialchars($row['guid']) ?></td>
                <td><?php echo htmlspecialchars($row['time']) ?></td>
                <td><?php echo htmlspecialchars(prettyPrint($row['fields'])) ?></td>
                <td><?php echo payments_status_string($row['status']) ?></td>
                <td><?php echo htmlspecialchars($row['total'] - $row['tax']) ?></td>
                <td><?php echo htmlspecialchars($row['tax']) ?></td>
                <td><?php echo htmlspecialchars($row['total']) ?></td>
                <td></td>
            </tr>
            <?php } ?>
        </table>
    </div>
    <?php
}

function send_order_email(){
    
}