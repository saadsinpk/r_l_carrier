<?php /*
Plugin Name: Sid Techno Customization
Plugin URI: http://sidtechno.com
description: Sid Techno Customization
Version: 1
Author: Muhammad Saad
Author URI: http://sidtechno.com
*/

function isJson($string) {
   json_decode($string);
   return json_last_error() === JSON_ERROR_NONE;
}

// define the woocommerce_new_order callback 
function sid_update_strip( $order_id ) { 
    // get order details data...
    $order = new WC_Order( $order_id );
    $order_detail = $order->get_data();
    $payment_gateway = $order_detail['payment_method'];
    if($payment_gateway == 'stripe_cc') {
        $order_total = $order_detail['total'];
        $stripe_fee = $order_total * 2.9;
        $stripe_fee = $stripe_fee / 100;
        $stripe_fee = $stripe_fee + .30;
        // $stripe_fee = $stripe_fee + .30;

        $order_total_after_fee = $order_total - $stripe_fee;

        update_post_meta($order_id, 'net_revenue_from_stripe', $order_total_after_fee); // add and save the custom field
        update_post_meta($order_id, 'stripe_fee', $stripe_fee); // add and save the custom field
        update_post_meta($order_id, '_net_revenue_from_stripe', $order_total_after_fee); // add and save the custom field
        update_post_meta($order_id, '_stripe_fee', $stripe_fee); // add and save the custom field
    }
}

function sidtechno_get_class($item_density) {
    if($item_density < 1) {
        $class = 400;
    } elseif($item_density >= 1 AND $item_density < 2) {
        $class = 300;
    } elseif($item_density >= 2 AND $item_density < 4) {
        $class = 250;
    } elseif($item_density >= 4 AND $item_density < 6) {
        $class = 175;
    } elseif($item_density >= 6 AND $item_density < 8) {
        $class = 125;
    } elseif($item_density >= 8 AND $item_density < 10) {
        $class = 100;
    } elseif($item_density >= 10 AND $item_density < 12) {
        $class = 92.5;
    } elseif($item_density >= 12 AND $item_density < 15) {
        $class = 85;
    } elseif($item_density >= 15 AND $item_density < 22.5) {
        $class = 70;
    } elseif($item_density >= 22.5 AND $item_density < 30) {
        $class = 65;
    } elseif($item_density >= 30) {
        $class = 60;
    }
    return $class;
}         
function calculate_pieces($item_quantity, $item_height) {
    $total_pieces = 1;
    $second_height = 1.25;
    for ($i=0; $i < $item_quantity; $i++) { 
        if($i == 0) {
            $per_pcs_item_height = $item_height;
        } else {
            $per_pcs_item_height = $per_pcs_item_height + $second_height;
        }
        if($per_pcs_item_height >= 96) {
            $per_pcs_item_height = $item_height;
            $total_pieces++;
        }

    }
    return $total_pieces;
}
add_action( 'woocommerce_new_order', 'sid_update_strip', 10, 1 ); 

function sid_woocommerce_checkout_update_order_meta($order_id) {
    $order = new WC_Order( $order_id );
    // if($order->get_shipping_method() == 'PHS Inc. Standard Delivery Service - 3 day(s)') {
        $order_detail = $order->get_data();
        $shipment = new RLC_Shipment();

        $shipments = $shipment->getOrderShipments($order_id);

        if ( !sizeof( $shipments) ){
            foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
                $quoteNumber = $shipping_item_obj->get_meta('quote');
            }
        } else{
            foreach ( $shipments as $shipment ) {
                foreach ( $shipment->getQuotes() as $quote ) {
                    $quoteNumber = $quote->quote_number;
                }

            }
        }

        $client = new SoapClient("http://api.rlcarriers.com/1.0.3/BillOfLadingService.asmx?WSDL", array("trace" => 1));
        $codes = json_decode(file_get_contents('http://country.io/iso3.json'), true);

        $item = array();
        foreach ($order_detail['line_items'] as $item_key => $item_value) {
            $item_data = $item_value->get_data();
            $item_product = $item_data['product_id'];
            $item_quantity = $item_data['quantity'];
            $item_weight = get_post_meta($item_product,'_weight', true) * $item_quantity;
            $item_lenght = get_post_meta($item_product,'_length', true) * $item_quantity;
            $item_width = get_post_meta($item_product,'_width', true) * $item_quantity;
            $item_height = get_post_meta($item_product,'_height', true) * $item_quantity;
            $item_title = get_the_title($item_product);

            $item_lbs = $item_weight * 2.20462;
            $item_cubic_feet = $item_lenght * $item_width * $item_height;
            $item_cubic_feet = $item_cubic_feet / 1728;
            $item_density = $item_weight / $item_cubic_feet;
            $class = sidtechno_get_class($item_density);



            $package_Type = 'PLT';
            $terms = get_the_terms ( $item_product, 'product_cat' );
            foreach ( $terms as $term ) {
                if($term->name == 'wire') {
                    $package_Type = 'BNDL';
                }
            }
            $total_pieces = calculate_pieces($item_quantity, get_post_meta($item_product,'_height', true));
            $item[] = array('IsHazmat' => false, 'Pieces' => $total_pieces, 'NMFCItemNumber'=> '150390', 'NMFCSubNumber'=> '01', 'PackageType' => $package_Type,'Class' => $class, 'Weight' => ceil($item_weight), 'Description' => '('.$item_quantity.') '.$item_title );

        }
        if(empty($order_detail['billing']['email'])) {
            $order_detail['billing']['email'] = '-';
        }
        if(empty($order_detail['billing']['phone'])) {
            $order_detail['billing']['phone'] = '';
        }
        $wc_get_order = wc_get_order( $order_id );

        $billing_email  = $wc_get_order->get_billing_email();
        $billing_phone  = $wc_get_order->get_billing_phone();
        $post_data['BillOfLading'] = array(
                    'BOLDate' => date("m/d/Y"),
                    'Shipper' => array('CompanyName' => 'Premier Handling Solutions', 'AddressLine1' => '1415 Davis Road', 'CountryCode' => 'USA', 'ZipOrPostalCode' => '60123', 'City' => 'ELGIN', 'StateOrProvince' => 'IL', 'PhoneNumber'=> '(847) 278-2321'  ),
                    'Consignee' => array('CompanyName' => $order_detail['shipping']['first_name'].' '.$order_detail['shipping']['last_name'], 'AddressLine1' => $order_detail['shipping']['address_1'], 'AddressLine2' => $order_detail['shipping']['address_2'], 'CountryCode' => $codes[$order_detail['shipping']['country']], 'ZipOrPostalCode' => $order_detail['shipping']['postcode'], 'City' => $order_detail['shipping']['city'], 'StateOrProvince' => $order_detail['shipping']['state'], 'PhoneNumber'=> $billing_phone, 'EmailAddress'=> $billing_email, 'Attention' => $order_detail['shipping']['first_name'].' '.$order_detail['shipping']['first_name'] ),
                    'BillTo' => array('CompanyName' => 'Premier Handling Solutions', 'AddressLine1' => '1415 Davis Road', 'CountryCode' => 'USA', 'ZipOrPostalCode' => '60123', 'City' => 'ELGIN', 'StateOrProvince' => 'IL', 'PhoneNumber'=> '(847) 278-2321'),
                    'AdditionalServices' => array('DestinationLiftgate', 'OriginLiftgate', 'InsidePickup', 'LimitedAccessPickup', 'LimitedAccessDelivery'),
                    'ServiceLevel' => 'Standard',
                    'SpecialInstructions' => $order_detail['customer_note'],
                    'Items' => $item,
                    'FreightChargePaymentMethod' => 'Prepaid',
                    'ReferenceNumbers' => array('RateQuoteNumber' => $quoteNumber, 'PONumber'=>$order_id),
                );
        if($_POST['residential-i-need-a-lift-gatecommercial-location-has-dock'] == 'residential-i-need-a-lift-gate') {
            $post_data['BillOfLading']['NoSignatureDelivery'] = true;
        }
        $post_data = json_encode($post_data);
        $response = sidtechno_curl_post('/BillOfLading', $post_data);
        // exit();
        if(isset($response['status'])) {
            if($response['status'] == 'success') {
                $response = json_decode($response['response']);

                $proID = $response->ProNumber;
                update_post_meta($order_id, 'proID', $proID); // add and save the custom field
                update_post_meta($order_id, 'quoteID', $quoteNumber); // add and save the custom field
                // $pro_order_id = $order_id;
                // $order_id = wc_clean($order_id);
                // $args     =  array(
                //     array(
                //         'slug'              => 'rl-carriers',
                //         'tracking_number'   => $proID,
                //         'tracking_id' => md5( "{rl-carriers}-{".$proID."}" ),
                //         'additional_fields' => array(
                //             'account_number'      => '',
                //             'key'                 => '',
                //             'postal_code'         => '',
                //             'ship_date'           => date("y-m-d"),
                //             'destination_country' => '',
                //             'state'               => '',
                //         ),
                //     ),
                // );
                // update_post_meta($pro_order_id, '_aftership_tracking_items', $args); // add and save the custom field
                // update_post_meta($pro_order_id, '_aftership_tracking_number', $proID); // add and save the custom field
                // update_post_meta($pro_order_id, '_aftership_tracking_provider_name', 'rl-carriers'); // add and save the custom field

            }
        }
    // }
}
add_action( 'woocommerce_checkout_update_order_meta', 'sid_woocommerce_checkout_update_order_meta', 99, 1 ); 

// Adding Meta container admin shop_order pages
add_action( 'add_meta_boxes', 'mv_add_meta_boxes' );
if ( ! function_exists( 'mv_add_meta_boxes' ) )
{
    function mv_add_meta_boxes()
    {
        add_meta_box( 'rl_carrier_bill_of_ladding_pdf_download', __('R+L Carriers PDF','woocommerce'), 'sid_r_l_carrier_pdf_download', 'shop_order', 'side', 'core' );
        // add_meta_box( 'rl_carrier_custom_fileds', __('R+L Carriers Custom Fields','woocommerce'), 'sid_r_l_carrier_custom_fields', 'product', 'normal', 'core' );
    }
}

function sid_r_l_carrier_pdf_download()
{
    global $post;
    $post_id = $_GET['post'];
    $order = new WC_Order( $post_id );
    
    if(!empty(get_post_meta($post_id,'proID', true))) {
        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        echo "<b>Quote ID: ".get_post_meta($post_id,'quoteID', true)."</b>";
        echo "<br>";
        echo "<b>BOLID: ".get_post_meta($post_id,'proID', true)."</b>";
        echo "<br>";
        echo "<b>Pickup ID: ".get_post_meta($post_id,'pickupID', true)."</b>";
        echo "<br>";
        echo "<a href='".get_site_url()."/wp-admin/admin.php?page=sales-manager&order_id=".$post_id."' class='button'>VIEW R+L Detail</a>";
        echo "<br>";
        echo "<a href='".get_site_url()."/wp-admin/post.php?post=".$post_id."&download_rlcarrier=rlpdf&action=edit' class='button'>DOWNLOAD BOL</a>";
        echo "<br>";
        echo "<a href='".get_site_url()."/wp-admin/post.php?post=".$post_id."&download_rlcarrier=rllabelpdf&action=edit' class='button'>DOWNLOAD BOL LABEL</a>";
    }

}

function the_dramatist_fire_on_admin_screen_initialization() {
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // error_reporting(E_ALL);
    $custom_cap = 'sidtechno_manage_options';
    $roles = wp_roles();
    $role_contri = $roles->role_objects['contributor'];
    $role_sale = $roles->role_objects['sales_team'];
    $role = $roles->role_objects['administrator'];
    $role->add_cap($custom_cap);
    $role_sale->add_cap($custom_cap);

    if(isset($_GET['download_rlcarrier'])) {
        if(isset($_GET['post'])) {
            $post_id = $_GET['post'];
        } else {
            $post_id = $_GET['order_id'];
        }


        $bolID = get_post_meta($post_id,'proID', true);

        if($_GET['download_rlcarrier'] == 'rlpdf') {

            $response = sidtechno_curl_get('/BillOfLading/PrintBOL?ProNumber='.$bolID);
            $response = json_decode($response['response']);

            $pdf_base64 = $response->BolDocument;
        } elseif($_GET['download_rlcarrier'] == 'rllabelpdf') {


            $response = sidtechno_curl_get('/BillOfLading/PrintShippingLabels?ProNumber='.$bolID.'&Style=1&StartPosition=1&NumberOfLabels=1');
            $response = json_decode($response['response']);

            $pdf_base64 = $response->ShippingLabelsFile;
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename=rl_carrier.pdf');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($pdf_base64));
        ob_clean();
        flush();
        echo base64_decode($pdf_base64);
        exit();
    }

    if(isset($_GET['test'])) {
        
        $post_data['RateQuote']['Origin'] = array('City' => 'Ocala', 'StateOrProvince'=>'FL','ZipOrPostalCode'=>'34471','CountryCode'=>'USA' );
        $post_data['RateQuote']['Destination'] = array('City' => 'Wilmington', 'StateOrProvince'=>'OH','ZipOrPostalCode'=>'45177','CountryCode'=>'USA' );
        $post_data['RateQuote']['iTEMS'] = array('Width' => 0, 'Hegiht'=>0, 'Length'=>0, 'Class'=>65, 'Weight'=>115 );
        echo "<pre>";
            print_r($post_data);
        echo "</pre>";
        $post_data = json_encode($post_data);
        // $post_data = '{
        // \n  "BillOfLading": {
        // \n    "BOLDate": "08/17/2020",
        // \n    "Shipper": {
        // \n      "CompanyName": "Shipper Test",
        // \n      "AddressLine1": "123 ship test",
        // \n      "PhoneNumber": "6145558888",
        // \n      "City": "Ocala",
        // \n      "StateOrProvince": "FL",
        // \n      "ZipOrPostalCode": "34471",
        // \n      "CountryCode": "USA"
        // \n    },
        // \n    "Consignee": {
        // \n      "CompanyName": "Consignee Test",
        // \n      "AddressLine1": "123 consignee test",
        // \n      "City": "Wilmington",
        // \n      "StateOrProvince": "OH",
        // \n      "ZipOrPostalCode": "45177",
        // \n      "CountryCode": "USA"
        // \n    },
        // \n    "Items": [
        // \n      {
        // \n        "Class": "70",
        // \n        "Pieces": 1,
        // \n        "Weight": 110,
        // \n        "PackageType": "BAG",
        // \n        "Description": "Test description"
        // \n      }
        // \n    ],
        // \n    "SpecialInstructions": "Test special instructions",
        // \n    "FreightChargePaymentMethod": "Prepaid",
        // \n    "ServiceLevel": "Standard"
        // \n  },
        // \n  "GenerateUniversalPro": true
        // \n}';
        echo $post_data;
        $response = sidtechno_curl_post('/RateQuote', $post_data);
        // $response = sidtechno_curl_get('/ShipmentTracing?TraceNumbers=I41449863&TraceType=PRO');
        echo "<pre>";
        print_r($response);
        echo "</pre>";
        exit();
    }
}
add_action( 'admin_init', 'the_dramatist_fire_on_admin_screen_initialization' );

function sidtechno_update_tracking_order() {
    if(isset($_GET['update_tracking_once_a_Day'])) {
        global $wpdb;

        $posts = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}woocommerce_order_items"));
        foreach ( $posts as $post ) {
            update_post_meta($post->order_id, 'checked_today', 0);
        }
        exit();
    }

    if(isset($_GET['update_tracking_every_one_mins'])) {
        global $wpdb;

        $posts = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}woocommerce_order_items"));
        $count = 0;
        foreach ( $posts as $post ) {
            $check_today = get_post_meta($post->order_id, 'checked_today', true);
            $rl_ship_update = get_post_meta($post->order_id, 'rl_ship_update', true);
            $pickupID = get_post_meta($post->order_id, 'pickupID', true);
            if(!empty($pickupID)) {
                if($check_today == 0 AND $rl_ship_update != 1) {
                    $proID = get_post_meta($post->order_id, 'proID', true);
                    if(empty($proID)) {
                        update_post_meta($post->order_id, 'checked_today', 1);
                    } else {
                        $response_tracing = sidtechno_curl_get('/ShipmentTracing?request.traceNumbers='.$proID.'&request.traceType=PRO');
                        if($response_tracing['status'] == 'error') {
                            update_post_meta($post->order_id, 'checked_today', 1);
                        } else {
                            $resp = json_decode($response_tracing['response']);
                            if(isset($resp->Shipments[0])) {
                                $shipmment_number = $resp->Shipments[0]->ShipmentNumber;
                                $shipmment_number = ltrim($shipmment_number, $shipmment_number[0]);
                                $last_character = $shipmment_number[-1];
                                $shipmment_number[-1] = '-';
                                $shipmment_number = $shipmment_number.$last_character;

                                $args     =  array(
                                    array(
                                        'slug'              => 'rl-carriers',
                                        'tracking_number'   => $shipmment_number,
                                        'tracking_id' => md5( "{rl-carriers}-{".$shipmment_number."}" ),
                                        'additional_fields' => array(
                                            'account_number'      => '',
                                            'key'                 => '',
                                            'postal_code'         => '',
                                            'ship_date'           => date("y-m-d"),
                                            'destination_country' => '',
                                            'state'               => '',
                                        ),
                                    ),
                                );
                                update_post_meta($post->order_id, '_aftership_tracking_items', $args); // add and save the custom field
                                update_post_meta($post->order_id, '_aftership_tracking_number', $shipmment_number); // add and save the custom field
                                update_post_meta($post->order_id, '_aftership_tracking_provider_name', 'rl-carriers'); // add and save the custom field
                                update_post_meta($post->order_id, 'checked_today', 1);
                                update_post_meta($post->order_id, 'rl_ship_update', 1);
                            } else {
                                update_post_meta($post->order_id, 'checked_today', 1);
                            }
                        }
                        $count++;
                    }
                    if($count == 5) {
                        break;
                        exit();
                    }
                }
            }
        }
        exit();
    }
}
add_action( 'init', 'sidtechno_update_tracking_order' );
function sidtechno_curl_post($url,$post) {

    $api_key = 'YtYxMGEDg2YikwNWItMzQ0ZC00MDljLTg3NGN2I2Q3ZDOTJjC';
    $base_url = 'https://api.rlc.com';

    require_once 'vendor/autoload.php'; // Only when installed with PEAR

    $request = new HTTP_Request2();
    $request->setUrl($base_url.$url);
    $request->setMethod(HTTP_Request2::METHOD_POST);
    $request->setConfig(array(
      'follow_redirects' => TRUE
    ));
    $request->setHeader(array(
        'ApiKey' => $api_key,
        'Content-Type' => 'application/json'
    ));
    $request->setBody($post);
         
    try {
      $response = $request->send();
      if ($response->getStatus() == 200) {
        return array('status' => 'success', 'response' => $response->getBody());
      }
      else {
        return array('status' => 'error','message' => $response->getBody(), 'response' => 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
        $response->getReasonPhrase());
      }
    }
     
    catch(HTTP_Request2_Exception $e) {
        return array('status' => 'error','message' => $response,  'response' => 'Error: ' . $e->getMessage());
    }     


}
function sidtechno_curl_put($url,$post) {

    $api_key = 'YtYxMGEDg2YikwNWItMzQ0ZC00MDljLTg3NGN2I2Q3ZDOTJjC';
    $base_url = 'https://api.rlc.com';

    require_once 'vendor/autoload.php'; // Only when installed with PEAR

    $request = new HTTP_Request2();
    $request->setUrl($base_url.$url);
    $request->setMethod(HTTP_Request2::METHOD_PUT);
    $request->setConfig(array(
      'follow_redirects' => TRUE
    ));
    $request->setHeader(array(
        'ApiKey' => $api_key,
        'Content-Type' => 'application/json'
    ));
    $request->setBody($post);
         
    try {
      $response = $request->send();
      if ($response->getStatus() == 200) {
        return array('status' => 'success', 'response' => $response->getBody());
      }
      else {
        return array('status' => 'error','message' => $response, 'response' => 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
        $response->getReasonPhrase());
      }
    }
     
    catch(HTTP_Request2_Exception $e) {
        return array('status' => 'error','message' => $response,  'response' => 'Error: ' . $e->getMessage());
    }     


}
function sidtechno_curl_delete($url, $post) {
    $api_key = 'YtYxMGEDg2YikwNWItMzQ0ZC00MDljLTg3NGN2I2Q3ZDOTJjC';
    $base_url = 'https://api.rlc.com';

    require_once 'vendor/autoload.php'; // Only when installed with PEAR

    $request = new HTTP_Request2();
    $request->setUrl($base_url.$url);
    $request->setMethod(HTTP_Request2::METHOD_DELETE);
    $request->setConfig(array(
      'follow_redirects' => TRUE
    ));
    $request->setHeader(array(
      'ApiKey' => $api_key,
      'Content-Type' => 'application/json'
    ));
    $request->setBody($post);
         
    try {
      $response = $request->send();
      if ($response->getStatus() == 200) {
        return array('status' => 'success', 'response' => $response->getBody());
      }
      else {
        return array('status' => 'error','message' => $response, 'response' => 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
        $response->getReasonPhrase());
      }
    }
     
    catch(HTTP_Request2_Exception $e) {
        return array('status' => 'error','message' => $response,  'response' => 'Error: ' . $e->getMessage());
    }     
}
function sidtechno_curl_get($url) {

    $api_key = 'YtYxMGEDg2YikwNWItMzQ0ZC00MDljLTg3NGN2I2Q3ZDOTJjC';
    $base_url = 'https://api.rlc.com';

    require_once 'vendor/autoload.php'; // Only when installed with PEAR

    $request = new HTTP_Request2();
    $request->setUrl($base_url.$url);
    $request->setMethod(HTTP_Request2::METHOD_GET);
    $request->setConfig(array(
      'follow_redirects' => TRUE
    ));
     
    $request->setHeader(array(
        'ApiKey' => $api_key
    ));
     
    try {
      $response = $request->send();
      if ($response->getStatus() == 200) {
        return array('status' => 'success', 'response' => $response->getBody());
      }
      else {
        return array('status' => 'error','message' => $response->getBody(), 'response' => 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
        $response->getReasonPhrase());
      }
    }
     
    catch(HTTP_Request2_Exception $e) {
        return array('status' => 'error','message' => $response,  'response' => 'Error: ' . $e->getMessage());
    }     
}
add_action( 'admin_menu', 'my_admin_menu' );

function my_admin_menu() {
    add_menu_page( 'Sales Manager', 'Sales Manager', 'sidtechno_manage_options', 'sales-manager', 'sidtechno_sales_manager', 'dashicons-tickets', 6  );
}
function sidtechno_sales_manager(){
    if(isset($_GET['order_id'])) {
        $order_id = $_GET['order_id'];
        $order = new WC_Order( $order_id );
        $order_detail = $order->get_data();

        $post_id = $_GET['order_id'];
        $item_rate_quote = array();
        $pickup_req_total_weight = 0;
        $pickup_req_total_pcs = 0;
        foreach ($order_detail['line_items'] as $item_key => $item_value) {
            $item_data = $item_value->get_data();
            $item_product = $item_data['product_id'];
            $item_quantity = $item_data['quantity'];
            $item_weight = get_post_meta($item_product,'_weight', true) * $item_quantity;
            $item_lenght = get_post_meta($item_product,'_length', true) * $item_quantity;
            $item_width = get_post_meta($item_product,'_width', true) * $item_quantity;
            $item_height = get_post_meta($item_product,'_height', true) * $item_quantity;
            $item_title = get_the_title($item_product);

            $item_lbs = $item_weight * 2.20462;
            $item_cubic_feet = $item_lenght * $item_width * $item_height;
            $item_cubic_feet = $item_cubic_feet / 1728;
            $item_density = $item_weight / $item_cubic_feet;
            $class = sidtechno_get_class($item_density);



            $package_Type = 'PLT';
            $terms = get_the_terms ( $item_product, 'product_cat' );
            foreach ( $terms as $term ) {
                if($term->name == 'wire') {
                    $package_Type = 'BNDL';                    
                }
            }

            // $item_rate_quote[] = array('Height' => $item_height, 'Length' => $item_lenght, 'Width' => $item_width, 'Weight' => $item_weight,'Class' => $class);
            $item_rate_quote[] = array('Weight' => $item_weight,'Class' => $class);
            $total_pieces = calculate_pieces($item_quantity, get_post_meta($item_product,'_height', true));
            $item_bol[] = array('IsHazmat' => false, 'Pieces' => $total_pieces, 'NMFCItemNumber'=> '150390', 'NMFCSubNumber'=> '01', 'PackageType' => $package_Type,'Class' => 400, 'Weight' => ceil($item_weight), 'Description' => '('.$item_quantity.') '.$item_title );
            $pickup_req_total_weight = $pickup_req_total_weight + $item_lbs;
            $pickup_req_total_pcs = $pickup_req_total_pcs + $item_quantity;
        }

        include 'single_order_page.php';
    } else {
        echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

        <div class="wrap">
            <h2>Welcome To Sales Manager</h2>
        </div>';

        $paged = ($_GET['pages']) ? $_GET['pages'] : 1;

        $args = array(
            'post_type'=>'shop_order', // Your post type name
            'posts_per_page' => 10,
            'post_status'    => 'any',
            'paged' => $paged,
        );

        $loop = new WP_Query( $args );
        if ( $loop->have_posts() ) {
            echo '<table class="table">
              <thead>
                <tr>
                  <th scope="col">Order ID</th>
                  <th scope="col">Quote ID</th>
                  <th scope="col">BOL ID</th>
                  <th scope="col">Pickup</th>
                </tr>
              </thead>
              <tbody>';
                //           update_post_meta($order_id, 'proID', $bolID); // add and save the custom field
                // update_post_meta($order_id, 'proID', $proID); // add and save the custom field
                // update_post_meta($order_id, 'quoteid', $quoteNumber); // add and save the custom field

            while ( $loop->have_posts() ) : $loop->the_post();
                echo '    <tr>
                  <th><a href="'.get_site_url().'/wp-admin/admin.php?page=sales-manager&order_id='.get_the_ID().'">#'.get_the_ID().'</a></th>
                  <td><a href="'.get_site_url().'/wp-admin/admin.php?page=sales-manager&order_id='.get_the_ID().'">'.get_post_meta(get_the_ID(), 'quoteID', true).'</a></td>
                  <td><a href="'.get_site_url().'/wp-admin/admin.php?page=sales-manager&order_id='.get_the_ID().'">'.get_post_meta(get_the_ID(), 'proID', true).'</a></td>
                  <td><a href="'.get_site_url().'/wp-admin/admin.php?page=sales-manager&order_id='.get_the_ID().'">'.get_post_meta(get_the_ID(), 'pickupID', true).'</a></td>
                </tr>';
            endwhile;
            echo '</tbody></table>';

            $total_pages = $loop->max_num_pages;

            if ($total_pages > 1){

                $current_page = max(1, $_GET['pages']);
                echo '<ul class="pagination">';

                $pagination = paginate_links(array(
                    'base' => get_site_url().'/wp-admin/admin.php?page=sales-manager%_%',
                    'format' => '&pages=%#%',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_text'    => __('« prev'),
                    'next_text'    => __('next »'),
                    'type' => 'array'
                ));

                if ( ! empty( $pagination ) ) {
                        foreach ( $pagination as $key => $page_link ) { ?>
                            <li class="page-item <?php if ( strpos( $page_link, 'current' ) !== false ) { echo ' active'; } ?>"><?php echo str_replace("page-numbers","page-link",$page_link) ?></li>
                        <?php }
                }
                echo '</ul>';
            }    
        }
        wp_reset_postdata();
    }
}

function sid_show_sales_man() { 
    if(!is_admin() AND is_user_logged_in() AND !wp_is_json_request()) {
        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );
        $user_roles = $user->roles;
        if(isset($user_roles[0])) {
            if($user_roles[0] == 'administrator' || $user_roles[0] == 'sales_team') {
                if(isset($_GET['order_id'])) {
                    $order_id = $_GET['order_id'];
                    $order = new WC_Order( $order_id );
                    $order_detail = $order->get_data();

                    $post_id = $_GET['order_id'];
                    $item_rate_quote = array();
                    $pickup_req_total_weight = 0;
                    $pickup_req_total_pcs = 0;
                    foreach ($order_detail['line_items'] as $item_key => $item_value) {
                        $item_data = $item_value->get_data();
                        $item_product = $item_data['product_id'];
                        $item_quantity = $item_data['quantity'];
                        $item_weight = get_post_meta($item_product,'_weight', true) * $item_quantity;
                        $item_lenght = get_post_meta($item_product,'_length', true) * $item_quantity;
                        $item_width = get_post_meta($item_product,'_width', true) * $item_quantity;
                        $item_height = get_post_meta($item_product,'_height', true) * $item_quantity;
                        $item_title = get_the_title($item_product);

                        $item_lbs = $item_weight * 2.20462;
                        $item_cubic_feet = $item_lenght * $item_width * $item_height;
                        $item_cubic_feet = $item_cubic_feet / 1728;
                        $item_density = $item_weight / $item_cubic_feet;
                        $class = sidtechno_get_class($item_density);



                        $package_Type = 'PLT';
                        $terms = get_the_terms ( $item_product, 'product_cat' );
                        foreach ( $terms as $term ) {
                            if($term->name == 'wire') {
                                $package_Type = 'BNDL';                    
                            }
                        }

                        // $item_rate_quote[] = array('Height' => $item_height, 'Length' => $item_lenght, 'Width' => $item_width, 'Weight' => $item_weight,'Class' => $class);
                        $item_rate_quote[] = array('Weight' => $item_weight,'Class' => $class);
                        $total_pieces = calculate_pieces($item_quantity, get_post_meta($item_product,'_height', true));
                        $item_bol[] = array('IsHazmat' => false, 'Pieces' => $total_pieces, 'NMFCItemNumber'=> '150390', 'NMFCSubNumber'=> '01', 'PackageType' => $package_Type,'Class' => 400, 'Weight' => ceil($item_weight), 'Description' => '('.$item_quantity.') '.$item_title );
                        $pickup_req_total_weight = $pickup_req_total_weight + $item_lbs;
                        $pickup_req_total_pcs = $pickup_req_total_pcs + $item_quantity;
                    }

                    include 'single_order_page.php';
                } else {
                    echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

                    <div class="wrap">
                        <h2>Welcome To Sales Manager</h2>
                    </div>';

                    $paged = ($_GET['pages']) ? $_GET['pages'] : 1;

                    $args = array(
                        'post_type'=>'shop_order', // Your post type name
                        'posts_per_page' => 10,
                        'post_status'    => 'any',
                        'paged' => $paged,
                    );

                    $loop = new WP_Query( $args );
                    if ( $loop->have_posts() ) {
                        echo '<table class="table">
                          <thead>
                            <tr>
                              <th scope="col">Order ID</th>
                              <th scope="col">Quote ID</th>
                              <th scope="col">BOL ID</th>
                              <th scope="col">Pickup</th>
                            </tr>
                          </thead>
                          <tbody>';
                            //           update_post_meta($order_id, 'proID', $bolID); // add and save the custom field
                            // update_post_meta($order_id, 'proID', $proID); // add and save the custom field
                            // update_post_meta($order_id, 'quoteid', $quoteNumber); // add and save the custom field

                        while ( $loop->have_posts() ) : $loop->the_post();
                            echo '    <tr>
                              <th><a href="?order_id='.get_the_ID().'">#'.get_the_ID().'</a></th>
                              <td><a href="?order_id='.get_the_ID().'">'.get_post_meta(get_the_ID(), 'quoteID', true).'</a></td>
                              <td><a href="?order_id='.get_the_ID().'">'.get_post_meta(get_the_ID(), 'proID', true).'</a></td>
                              <td><a href="?order_id='.get_the_ID().'">'.get_post_meta(get_the_ID(), 'pickupID', true).'</a></td>
                            </tr>';
                        endwhile;
                        echo '</tbody></table>';

                        $total_pages = $loop->max_num_pages;

                        if ($total_pages > 1){

                            $current_page = max(1, $_GET['pages']);
                            echo '<ul class="pagination">';

                            $pagination = paginate_links(array(
                                'base' => '%_%',
                                'format' => '?pages=%#%',
                                'current' => $current_page,
                                'total' => $total_pages,
                                'prev_text'    => __('« prev'),
                                'next_text'    => __('next »'),
                                'type' => 'array'
                            ));

                            if ( ! empty( $pagination ) ) {
                                    foreach ( $pagination as $key => $page_link ) { ?>
                                        <li class="page-item <?php if ( strpos( $page_link, 'current' ) !== false ) { echo ' active'; } ?>"><?php echo str_replace("page-numbers","page-link",$page_link) ?></li>
                                    <?php }
                            }
                            echo '</ul>';
                        }    
                    }
                    wp_reset_postdata();
                }
            }
        }
    }
} 
add_shortcode('show_sales_man', 'sid_show_sales_man'); 
?>
