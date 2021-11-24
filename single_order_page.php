<?php 
ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL);
    $post_id = $_GET['order_id'];
    $wc_order = new WC_Order( $post_id );
    $order_items = $wc_order->get_items();
    $wc_get_order = wc_get_order( $post_id );

    $billing_email  = $wc_get_order->get_billing_email();
    $billing_phone  = $wc_get_order->get_billing_phone();

    if(isset($_GET['pickupDelete'])) {
        $delete_id = $_GET['pickupDelete'];
        $post = json_encode(array("PickupRequestId"=>$delete_id,"Reason"=>"Freight not ready"));
        $response = sidtechno_curl_delete('/PickupRequest', $post);
        if(isset($response['status'])) {
            if($response['status'] == 'success') {
                $pickupid = get_post_meta($order_id, 'pickupID', true);
                $pickupid_arr = explode (",", $pickupid); 
                $update_pickupid = '';
                $count = 0;
                foreach ($pickupid_arr as $pickup_key => $pickup_value) {
                    if(!empty($pickup_value)){
                        if($pickup_value != $delete_id) {
                            if($count == 0) {
                                $update_pickupid = $pickup_value;
                            } else {
                                $update_pickupid = $update_pickupid.",".$pickup_value;
                            }
                            $count++;
                        }
                    }
                }
                update_post_meta($order_id, 'pickupID', $update_pickupid);
                wp_redirect(get_site_url().'/wp-admin/admin.php?page=sales-manager&order_id='.$post_id);
            }
        } else {
                echo "<h4>Error!</h4>";
                $error_list = json_decode($response['message']);
                foreach ($error_list->Errors as $Errors_key => $Errors_value) {
                    echo "<p><b>".$Errors_value->ErrorMessage."</b></p>";
                }
            }
    }
    if(isset($_POST['hidden_quote_number'])) {
        update_post_meta($order_id, 'quoteID', $_POST['hidden_quote_number']);
        echo "<h3>Successfully Quote Updated</h3>";
    }


    if(isset($_POST['update_pickup_request'])){
        if(empty($_POST['Pickup']['Shipper']['EmailAddress'])) {
            unset($_POST['Pickup']['Shipper']['EmailAddress']);
        }
        if(empty($_POST['Pickup']['Contact']['EmailAddress'])) {
            unset($_POST['Pickup']['Contact']['EmailAddress']);
        }
        if(!empty($_POST['Pickup']['PickupDate'])) {
            $_POST['Pickup']['PickupDate'] = date("m/d/Y", strtotime($_POST['Pickup']['PickupDate'])); 
        }
        if(!empty($_POST['Pickup']['ReadyTime'])) {
            $_POST['Pickup']['ReadyTime'] = date("h:i A", strtotime($_POST['Pickup']['ReadyTime'])); 
        }
        if(!empty($_POST['Pickup']['CloseTime'])) {
            $_POST['Pickup']['CloseTime'] = date("h:i A", strtotime($_POST['Pickup']['CloseTime'])); 
        }

        $post_data['PickupRequestId'] = $_POST['PickupRequestId'];
        $post_data['Pickup'] = $_POST['Pickup'];
        $post_data = json_encode($post_data);
        $response = sidtechno_curl_put('/PickupRequest', $post_data);
        if(isset($response['status'])) {
            if($response['status'] == 'success') {
                $response = json_decode($response['response']);
                update_post_meta($order_id, 'pickupID', $response->PickupRequestId);
                echo "<h3>Successfully Pickup Request send</h3>";
            }
        } else {
                echo "<h4>Error!</h4>";
                $error_list = json_decode($response['message']);
                foreach ($error_list->Errors as $Errors_key => $Errors_value) {
                    echo "<p><b>".$Errors_value->ErrorMessage."</b></p>";
                }
            }
    }

    if(isset($_POST['submit_pickup_request'])){
        if(empty($_POST['Pickup']['Shipper']['EmailAddress'])) {
            unset($_POST['Pickup']['Shipper']['EmailAddress']);
        }
        if(empty($_POST['Contact']['EmailAddress'])) {
            unset($_POST['Contact']['EmailAddress']);
        }
        if(!empty($_POST['Pickup']['PickupDate'])) {
            $_POST['Pickup']['PickupDate'] = date("m/d/Y", strtotime($_POST['Pickup']['PickupDate'])); 
        }
        if(!empty($_POST['Pickup']['ReadyTime'])) {
            $_POST['Pickup']['ReadyTime'] = date("h:i A", strtotime($_POST['Pickup']['ReadyTime'])); 
        }
        if(!empty($_POST['Pickup']['CloseTime'])) {
            $_POST['Pickup']['CloseTime'] = date("h:i A", strtotime($_POST['Pickup']['CloseTime'])); 
        }

        $post_data['ProNumber'] = get_post_meta($_GET['order_id'], 'proID', true);
        $post_data['PickupInformation'] = $_POST['Pickup'];
        $post_data['Contact'] = $_POST['Contact'];
        $post_data = json_encode($post_data);
        $response = sidtechno_curl_post('/PickupRequest/FromBOL', $post_data);
        if(isset($response['status'])) {
            if($response['status'] == 'success') {
                $response = json_decode($response['response']);
                update_post_meta($order_id, 'pickupID', $response->PickupRequestId);
                echo "<h3>Successfully Pickup Request send</h3>";
            }
        } else {
                echo "<h4>Error!</h4>";
                $error_list = json_decode($response['message']);
                foreach ($error_list->Errors as $Errors_key => $Errors_value) {
                    echo "<p><b>".$Errors_value->ErrorMessage."</b></p>";
                }
            }
    }

    if(isset($_POST['submit_bol_form'])){
        if(empty($_POST['BillOfLading']['Consignee']['EmailAddress'])) {
            unset($_POST['BillOfLading']['Consignee']['EmailAddress']);
        }
        if(empty($_POST['BillOfLading']['Broker']['EmailAddress'])) {
            unset($_POST['BillOfLading']['Broker']['EmailAddress']);
        }
        if(empty($_POST['BillOfLading']['Broker']['CompanyName'])) {
            unset($_POST['BillOfLading']['Broker']['CompanyName']);
        }
        if(empty($_POST['BillOfLading']['Broker']['AddressLine1'])) {
            unset($_POST['BillOfLading']['Broker']);
        }
        if(empty($_POST['BillOfLading']['Shipper']['EmailAddress'])) {
            unset($_POST['BillOfLading']['Shipper']['EmailAddress']);
        }
        $_POST['BillOfLading']['BillTo'] = $_POST['BillOfLading']['Shipper'];
        $_POST['BillOfLading']['ReferenceNumbers']['RateQuoteNumber'] = get_post_meta($order_id, 'quoteID', true);
        $_POST['BillOfLading']['ReferenceNumbers']['PONumber'] = $order_id;
        // $_POST['BillOfLading']['Items'] = $item_bol;
        $_POST['BillOfLading']['BOLDate'] = date("m/d/Y", strtotime($_POST['BillOfLading']['BOLDate']));
        $_POST['RateQuote']['Items'] = $item_rate_quote; 

        $post_data['BillOfLading'] = $_POST['BillOfLading'];
        if(empty($_POST['Pickup']['Shipper']['EmailAddress'])) {
            unset($_POST['Pickup']['Shipper']['EmailAddress']);
        }
        if(empty($_POST['Contact']['EmailAddress'])) {
            unset($_POST['Contact']['EmailAddress']);
        }
        if(!empty($_POST['Pickup']['PickupDate'])) {
            $_POST['Pickup']['PickupDate'] = date("m/d/Y", strtotime($_POST['Pickup']['PickupDate'])); 
        }
        if(!empty($_POST['Pickup']['ReadyTime'])) {
            $_POST['Pickup']['ReadyTime'] = date("h:i A", strtotime($_POST['Pickup']['ReadyTime'])); 
        }
        if(!empty($_POST['Pickup']['CloseTime'])) {
            $_POST['Pickup']['CloseTime'] = date("h:i A", strtotime($_POST['Pickup']['CloseTime'])); 
        }


        $post_data['AddPickupRequest'] = true;
        $post_data['PickupRequest']['PickupInformation'] = $_POST['Pickup'];
        $post_data['PickupRequest']['Contact'] = $_POST['Contact'];

        foreach ($post_data['BillOfLading']['NoSignatureNotificationContacts']['EmailAddresses'] as $key => $value) {
            unset($post_data['BillOfLading']['NoSignatureNotificationContacts']['EmailAddresses'][$key]);
        }
        foreach ($post_data['BillOfLading']['NoSignatureNotificationContacts']['PhoneNumbers'] as $key => $value) {
            unset($post_data['BillOfLading']['NoSignatureNotificationContacts']['PhoneNumbers'][$key]);
        }
        if(count($post_data['BillOfLading']['NoSignatureNotificationContacts']['EmailAddresses']) == 0) {
            unset($post_data['BillOfLading']['NoSignatureNotificationContacts']['EmailAddresses']);
        }
        if(count($post_data['BillOfLading']['NoSignatureNotificationContacts']['PhoneNumbers']) == 0) {
            unset($post_data['BillOfLading']['NoSignatureNotificationContacts']['PhoneNumbers']);
        }
        if(!isset($post_data['BillOfLading']['NoSignatureNotificationContacts']['PhoneNumbers']) AND !isset($post_data['BillOfLading']['NoSignatureNotificationContacts']['EmailAddresses'])) {
            unset($post_data['BillOfLading']['NoSignatureNotificationContacts']);
        }
        // $post_data['BillOfLading']['NoSignatureNotificationContacts'] = array('EmailAddresses'=> array('saad_sinpk@yahoo.com'), 'PhoneNumbers' => array('8472782321'));
        if(isset($post_data['BillOfLading']['AdditionalServices'])) {
            if(in_array('LimitedAccessPickup', $post_data['BillOfLading']['AdditionalServices']) || in_array('LimitedAccessDelivery', $post_data['BillOfLading']['AdditionalServices'])) {
                $post_data['BillOfLading']['NoSignatureDelivery'] = 1;
            }
        }
            
            

        // $post_data['BillOfLading']['NoSignatureDelivery'] = 1;
        // echo "<pre>";
        //     print_r($post_data);
        // echo "</pre>";
        // exit();
        $post_data = json_encode($post_data);
        $response = sidtechno_curl_post('/BillOfLading', $post_data);

        if(isset($response['status'])) {
            if($response['status'] == 'success') {
                $response = json_decode($response['response']);
                if(isset($response->ProNumber)) {
                    update_post_meta($order_id, 'proID', $response->ProNumber);
                }
                if(isset($response->PickupRequestNumber)) {
                    if(!empty($response->PickupRequestNumber)) {
                        $old_id = get_post_meta($order_id, 'pickupID', true);
                        $new_id = $response->PickupRequestNumber;
                        if(!empty($old_id)) {
                            $new_id = $old_id.','.$response->PickupRequestNumber;
                        }
                        update_post_meta($order_id, 'pickupID', $new_id);
                    }
                }
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


                echo "<h3>Successfully BOLID generated</h3>";
            } else {
                echo "<h4>Error!</h4>";
                if(isset($response['message'])) {
                    if(isJson($response['message'])) {
                        $error_list = json_decode($response['message']);
                        foreach ($error_list->Errors as $Errors_key => $Errors_value) {
                            echo "<p><b>".$Errors_value->ErrorMessage."</b><br>".$Errors_value->ExceptionMessage."</p>";
                        }
                    } else {
                        echo $response['message'];
                    }
                }
            }
        }
    }
    if(isset($_POST['submit_rate_quote'])) {
        if(empty($_POST['RateQuote']['CODAmount'])) {
            unset($_POST['RateQuote']['CODAmount']);
        }
        $_POST['RateQuote']['Items'] = $item_rate_quote; 
        $_POST['RateQuote']['PickupDate'] = date("m/d/Y", strtotime($_POST['RateQuote']['PickupDate'])); 

        $post_data = json_encode(array("RateQuote" => $_POST['RateQuote']));
        $response = sidtechno_curl_post('/RateQuote', $post_data);
        if(isset($response['status'])) {
            if($response['status'] == 'success') {
                $response = json_decode($response['response']);

                if(isset($response->Errors) AND count($response->Errors) > 0) {
                    echo '<h3>Error</h3>';
                    foreach ($response->Errors as $key => $value) {
                        echo '<p>'.$value->ErrorMessage.'</p>';
                    }
                } else {
                    $charge_detail = $response->RateQuote->ServiceLevels[0];
                    echo "<br><br><b>Name</b> ".$charge_detail->Name.'<br>';
                    echo "<b>Code</b> ".$charge_detail->Code.'<br>';
                    echo "<b>Quote</b> ".$charge_detail->QuoteNumber.'<br>';
                    echo "<b>Service Days</b> ".$charge_detail->ServiceDays.'<br>';
                    echo "<b>Charge</b> ".$charge_detail->Charge.'<br>';
                    echo "<b>Net Charge</b> ".$charge_detail->NetCharge.'<br>';
                    echo "<form action='' method='post'>
                        <h2>Do you want to update with this order with this quote?
                        <input type='hidden' name='hidden_quote_number' value='".$charge_detail->QuoteNumber."'>
                        <button type='submit' class='btn btn-success' name='submit_update_rate_quote'>Submit</button>
                    </form>";
                }
            }
        } else {
                echo "<h4>Error!</h4>";
                $error_list = json_decode($response['message']);
                foreach ($error_list->Errors as $Errors_key => $Errors_value) {
                    echo "<p><b>".$Errors_value->ErrorMessage."</b></p>";
                }
            }
    }

?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

<link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.10.0/css/all.css" integrity="sha384-AYmEC3Yw5cVb3ZcuHtOA93w35dYTsvhLPVnYs9eStHfGJvOvKxVfELGroGkvsg+p" crossorigin="anonymous" />

<!-- Custome css -->
<link rel="stylesheet" href="<?php echo plugin_dir_url( __FILE__ );?>assets/style.css">
<!--  -->

<!-- Latest compiled and minified JavaScript -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="./assets/main.js"></script>
<script type="text/javascript">
    jQuery( document ).ready(function() {
        jQuery(document).on('click', '.delete_this_filed', function(){
            jQuery(this).closest(".clone_this_div").remove();
        });
        jQuery(document).on('click', '.add_this_filed', function(){
            jQuery(this).closest(".append_here_clone_div").append(jQuery(this).closest(".clone_this_div").clone());
        });
        jQuery(document).on('click', '.addDestinationPickup', function(){
          var clone_html = jQuery(this).closest('.MainDestination').clone();
          jQuery(this).closest('.MainDestination').after(clone_html);
        });
        jQuery(document).on('change', '#additional_service input', function(){
            var cur_value = jQuery(this).val();
            if(jQuery('#additional_service input[value="LimitedAccessPickup"]').is(":checked") || jQuery('#additional_service input[value="LimitedAccessDelivery"]').is(":checked")) {
                jQuery("#signature_id").show();
            } else {
                jQuery("#signature_id").hide();
            }
            // if($('#additional_service input[value="'+cur_value+'"]').is(":checked")) {
            //     console.log("test");
            // } else{
            //     console.log("test2");
            // }
        });
        jQuery(document).on('change', '.item_number_change', function(){
            var current_number = jQuery(this).val();
            console.log(current_number);
            if(current_number == 1) {
                jQuery(this).closest(".main_item").find(".item_class_value").val("400").change();
            } else if(current_number == 2) {
                jQuery(this).closest(".main_item").find(".item_class_value").val("300").change();
            } else if(current_number == 3) {
                jQuery(this).closest(".main_item").find(".item_class_value").val("250").change();
            } else if(current_number == 4) {
                jQuery(this).closest(".main_item").find(".item_class_value").val("175").change();
            } else if(current_number == 5) {
                jQuery(this).closest(".main_item").find(".item_class_value").val("125").change();
            } else if(current_number == 6) {
                jQuery(this).closest(".main_item").find(".item_class_value").val("100").change();
            } else if(current_number == 7) {
                jQuery(this).closest(".main_item").find(".item_class_value").val("92.5").change();
            } else if(current_number == 8) {
                jQuery(this).closest(".main_item").find(".item_class_value").val("85").change();
            } else if(current_number == 9) {
                jQuery(this).closest(".main_item").find(".item_class_value").val("70").change();
            } else if(current_number == 10) {
                jQuery(this).closest(".main_item").find(".item_class_value").val("65").change();
            } else if(current_number == 11) {
                jQuery(this).closest(".main_item").find(".item_class_value").val("60").change();
            }

        });
    });

</script>
<h2>Order # <?php echo $post_id;?></h2>
<div class="d-flex align-items-start mt-5 gap-5">

    <div class="border shadow-sm col-md-2">
        <div class="nav flex-column" id="ship-tab" role="tablist" aria-orientation="vertical">
            <a class="nav-link <?php if(!isset($_GET['pickupEdit'])) { echo 'active'; }?>" id="rate-tab" data-bs-toggle="tab" data-bs-target="#rate" type="button" role="tab" aria-controls="rate" aria-selected="false">Rate of quote</a>
            <a class="nav-link" id="bill-lading-tab" data-bs-toggle="tab" data-bs-target="#bill-lading" type="button" role="tab" aria-controls="bill-lading" aria-selected="true">BOL (Bill of lading)</a>
        </div>
    </div>

    <!-- Tab panes -->
    <div class="tab-content col-md-8 border shadow p-4" id="ship-tab-content">
        <div class="tab-pane fade show <?php if(!isset($_GET['pickupEdit'])) { echo 'active'; }?>" id="rate" role="tabpanel " aria-labelledby="rate-tab ">
            <h2 class="mb-3">Rate of Quote</h2>
            <form action="" method="post">
                <div class="col mb-3">
                    All fields and sections are required unless they are noted as optional.
                </div>

                <div class="mb-3">
                    <h6 class="subtitle">Quote Information</h6>

                    <div class="row mb-3">

                        <div class="col">
                            <label for="rate_quote_pickupDate" class="form-label">Pickup Date</label>
                            <input type="date" class="form-control form-control-sm" name="RateQuote[PickupDate]" id="rate_quote_pickupDate" value="<?php echo get_the_date('Y-m-d', $post_id);?>">
                        </div>

                    </div>

                    <div class="row mb-3">

                        <div class="col">
                            <label for="rate_quote_origin_zipCode" class="form-label">Origin Zip Code</label>
                            <input type="text" class="form-control form-control-sm" name="RateQuote[Origin][ZipOrPostalCode]" id="rate_quote_origin_zipCode" value="60123">
                        </div>

                        <div class="col">
                            <label for="rate_quote_d_zipCode" class="form-label">Destination Zip Code</label>
                            <input type="text" class="form-control form-control-sm" name="RateQuote[Destination][ZipOrPostalCode]" id="rate_quote_d_zipCode" value="<?php echo $order_detail['shipping']['postcode'];?>">
                        </div>

                    </div>

                    <div class="row mb-3">

                        <div class="col-md-6">
                            <label for="rate_quote_o_city" class="form-label">Origin City</label>
                            <input type="text" class="form-control form-control-sm" name="RateQuote[Origin][City]" id="rate_quote_o_city" value="ELGIN">
                        </div>

                        <div class="col-md-3">
                            <label for="rate_quote_d_city" class="form-label">Destination City</label>
                            <input type="text" class="form-control form-control-sm" name="RateQuote[Destination][City]" id="rate_quote_d_city" value="<?php echo $order_detail['shipping']['city'];?>">
                        </div>

                    </div>

                    <div class="row mb-3">

                        <div class="col-md-6">
                            <label for="rate_quote_o_state" class="form-label">Origin State</label>
                            <select class="form-control form-control-sm" name="RateQuote[Origin][StateOrProvince]" id="rate_quote_o_state">
                                <option value="AL">Alabama</option>
                                <option value="AK">Alaska</option>
                                <option value="AZ">Arizona</option>
                                <option value="AR">Arkansas</option>
                                <option value="CA">California</option>
                                <option value="CO">Colorado</option>
                                <option value="CT">Connecticut</option>
                                <option value="DE">Delaware</option>
                                <option value="DC">District Of Columbia</option>
                                <option value="FL">Florida</option>
                                <option value="GA">Georgia</option>
                                <option value="HI">Hawaii</option>
                                <option value="ID">Idaho</option>
                                <option value="IL" selected="selected">Illinois</option>
                                <option value="IN">Indiana</option>
                                <option value="IA">Iowa</option>
                                <option value="KS">Kansas</option>
                                <option value="KY">Kentucky</option>
                                <option value="LA">Louisiana</option>
                                <option value="ME">Maine</option>
                                <option value="MD">Maryland</option>
                                <option value="MA">Massachusetts</option>
                                <option value="MI">Michigan</option>
                                <option value="MN">Minnesota</option>
                                <option value="MS">Mississippi</option>
                                <option value="MO">Missouri</option>
                                <option value="MT">Montana</option>
                                <option value="NE">Nebraska</option>
                                <option value="NV">Nevada</option>
                                <option value="NH">New Hampshire</option>
                                <option value="NJ">New Jersey</option>
                                <option value="NM">New Mexico</option>
                                <option value="NY">New York</option>
                                <option value="NC">North Carolina</option>
                                <option value="ND">North Dakota</option>
                                <option value="OH">Ohio</option>
                                <option value="OK">Oklahoma</option>
                                <option value="OR">Oregon</option>
                                <option value="PA">Pennsylvania</option>
                                <option value="RI">Rhode Island</option>
                                <option value="SC">South Carolina</option>
                                <option value="SD">South Dakota</option>
                                <option value="TN">Tennessee</option>
                                <option value="TX">Texas</option>
                                <option value="UT">Utah</option>
                                <option value="VT">Vermont</option>
                                <option value="VA">Virginia</option>
                                <option value="WA">Washington</option>
                                <option value="WV">West Virginia</option>
                                <option value="WI">Wisconsin</option>
                                <option value="WY">Wyoming</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="rate_quote_d_state" class="form-label">Destination State</label>
                            <select class="form-control form-control-sm" name="RateQuote[Destination][StateOrProvince]" id="rate_quote_d_state">
                                <option value="AL" <?php if($order_detail['shipping']['state'] == 'AL') { echo 'selected="selected"'; }?>>Alabama</option>
                                <option value="AK" <?php if($order_detail['shipping']['state'] == 'AK') { echo 'selected="selected"'; }?>>Alaska</option>
                                <option value="AZ" <?php if($order_detail['shipping']['state'] == 'AZ') { echo 'selected="selected"'; }?>>Arizona</option>
                                <option value="AR" <?php if($order_detail['shipping']['state'] == 'AR') { echo 'selected="selected"'; }?>>Arkansas</option>
                                <option value="CA" <?php if($order_detail['shipping']['state'] == 'CA') { echo 'selected="selected"'; }?>>California</option>
                                <option value="CO" <?php if($order_detail['shipping']['state'] == 'CO') { echo 'selected="selected"'; }?>>Colorado</option>
                                <option value="CT" <?php if($order_detail['shipping']['state'] == 'CT') { echo 'selected="selected"'; }?>>Connecticut</option>
                                <option value="DE" <?php if($order_detail['shipping']['state'] == 'DE') { echo 'selected="selected"'; }?>>Delaware</option>
                                <option value="DC" <?php if($order_detail['shipping']['state'] == 'DC') { echo 'selected="selected"'; }?>>District Of Columbia</option>
                                <option value="FL" <?php if($order_detail['shipping']['state'] == 'FL') { echo 'selected="selected"'; }?>>Florida</option>
                                <option value="GA" <?php if($order_detail['shipping']['state'] == 'GA') { echo 'selected="selected"'; }?>>Georgia</option>
                                <option value="HI" <?php if($order_detail['shipping']['state'] == 'HI') { echo 'selected="selected"'; }?>>Hawaii</option>
                                <option value="ID" <?php if($order_detail['shipping']['state'] == 'ID') { echo 'selected="selected"'; }?>>Idaho</option>
                                <option value="IL" <?php if($order_detail['shipping']['state'] == 'IL') { echo 'selected="selected"'; }?>>Illinois</option>
                                <option value="IN" <?php if($order_detail['shipping']['state'] == 'IN') { echo 'selected="selected"'; }?>>Indiana</option>
                                <option value="IA" <?php if($order_detail['shipping']['state'] == 'IA') { echo 'selected="selected"'; }?>>Iowa</option>
                                <option value="KS" <?php if($order_detail['shipping']['state'] == 'KS') { echo 'selected="selected"'; }?>>Kansas</option>
                                <option value="KY" <?php if($order_detail['shipping']['state'] == 'KY') { echo 'selected="selected"'; }?>>Kentucky</option>
                                <option value="LA" <?php if($order_detail['shipping']['state'] == 'LA') { echo 'selected="selected"'; }?>>Louisiana</option>
                                <option value="ME" <?php if($order_detail['shipping']['state'] == 'ME') { echo 'selected="selected"'; }?>>Maine</option>
                                <option value="MD" <?php if($order_detail['shipping']['state'] == 'MD') { echo 'selected="selected"'; }?>>Maryland</option>
                                <option value="MA" <?php if($order_detail['shipping']['state'] == 'MA') { echo 'selected="selected"'; }?>>Massachusetts</option>
                                <option value="MI" <?php if($order_detail['shipping']['state'] == 'MI') { echo 'selected="selected"'; }?>>Michigan</option>
                                <option value="MN" <?php if($order_detail['shipping']['state'] == 'MN') { echo 'selected="selected"'; }?>>Minnesota</option>
                                <option value="MS" <?php if($order_detail['shipping']['state'] == 'MS') { echo 'selected="selected"'; }?>>Mississippi</option>
                                <option value="MO" <?php if($order_detail['shipping']['state'] == 'MO') { echo 'selected="selected"'; }?>>Missouri</option>
                                <option value="MT" <?php if($order_detail['shipping']['state'] == 'MT') { echo 'selected="selected"'; }?>>Montana</option>
                                <option value="NE" <?php if($order_detail['shipping']['state'] == 'NE') { echo 'selected="selected"'; }?>>Nebraska</option>
                                <option value="NV" <?php if($order_detail['shipping']['state'] == 'NV') { echo 'selected="selected"'; }?>>Nevada</option>
                                <option value="NH" <?php if($order_detail['shipping']['state'] == 'NH') { echo 'selected="selected"'; }?>>New Hampshire</option>
                                <option value="NJ" <?php if($order_detail['shipping']['state'] == 'NJ') { echo 'selected="selected"'; }?>>New Jersey</option>
                                <option value="NM" <?php if($order_detail['shipping']['state'] == 'NM') { echo 'selected="selected"'; }?>>New Mexico</option>
                                <option value="NY" <?php if($order_detail['shipping']['state'] == 'NY') { echo 'selected="selected"'; }?>>New York</option>
                                <option value="NC" <?php if($order_detail['shipping']['state'] == 'NC') { echo 'selected="selected"'; }?>>North Carolina</option>
                                <option value="ND" <?php if($order_detail['shipping']['state'] == 'ND') { echo 'selected="selected"'; }?>>North Dakota</option>
                                <option value="OH" <?php if($order_detail['shipping']['state'] == 'OH') { echo 'selected="selected"'; }?>>Ohio</option>
                                <option value="OK" <?php if($order_detail['shipping']['state'] == 'OK') { echo 'selected="selected"'; }?>>Oklahoma</option>
                                <option value="OR" <?php if($order_detail['shipping']['state'] == 'OR') { echo 'selected="selected"'; }?>>Oregon</option>
                                <option value="PA" <?php if($order_detail['shipping']['state'] == 'PA') { echo 'selected="selected"'; }?>>Pennsylvania</option>
                                <option value="RI" <?php if($order_detail['shipping']['state'] == 'RI') { echo 'selected="selected"'; }?>>Rhode Island</option>
                                <option value="SC" <?php if($order_detail['shipping']['state'] == 'SC') { echo 'selected="selected"'; }?>>South Carolina</option>
                                <option value="SD" <?php if($order_detail['shipping']['state'] == 'SD') { echo 'selected="selected"'; }?>>South Dakota</option>
                                <option value="TN" <?php if($order_detail['shipping']['state'] == 'TN') { echo 'selected="selected"'; }?>>Tennessee</option>
                                <option value="TX" <?php if($order_detail['shipping']['state'] == 'TX') { echo 'selected="selected"'; }?>>Texas</option>
                                <option value="UT" <?php if($order_detail['shipping']['state'] == 'UT') { echo 'selected="selected"'; }?>>Utah</option>
                                <option value="VT" <?php if($order_detail['shipping']['state'] == 'VT') { echo 'selected="selected"'; }?>>Vermont</option>
                                <option value="VA" <?php if($order_detail['shipping']['state'] == 'VA') { echo 'selected="selected"'; }?>>Virginia</option>
                                <option value="WA" <?php if($order_detail['shipping']['state'] == 'WA') { echo 'selected="selected"'; }?>>Washington</option>
                                <option value="WV" <?php if($order_detail['shipping']['state'] == 'WV') { echo 'selected="selected"'; }?>>West Virginia</option>
                                <option value="WI" <?php if($order_detail['shipping']['state'] == 'WI') { echo 'selected="selected"'; }?>>Wisconsin</option>
                                <option value="WY" <?php if($order_detail['shipping']['state'] == 'WY') { echo 'selected="selected"'; }?>>Wyoming</option>
                            </select>
                        </div>

                    </div>

                    <div class="row mb-3">

                        <div class="col-md-6">
                            <label for="rate_quote_o_country" class="form-label">Origin Country</label>
                            <select type="text" class="form-control form-control-sm" name="RateQuote[Origin][CountryCode]" id="rate_quote_o_country">
                                <option value="USA">United State</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="rate_quote_d_country" class="form-label">Destination Country</label>
                            <select type="text" class="form-control form-control-sm" name="RateQuote[Destination][CountryCode]" id="rate_quote_d_country">
                                <option value="USA">United State</option>
                            </select>
                        </div>

                    </div>

                    <div class="row mb-3">

                        <div class="col-md-4">
                            <label for="rate_quote_cod_amount" class="form-label">COD Amount ($) (optional)</label>
                            <input type="text" class="form-control form-control-sm" name="RateQuote[CODAmount]" id="rate_quote_cod_amount">
                        </div>

                    </div>
                </div>

                <div class="mb-3">
                    <h6 class="subtitle">Additional Services Required (optional)</h6>

                    <div class="row mb-3">

                        <div class="col">
                            <label class="form-label text-decoration-underline fw-bold">Origin Options</label>

                            <div class="d-flex mb-3 align-items-center gap-2">
                                <input type="checkbox" name="RateQuote[AdditionalServices][]" id="rate_additional_origin_liftgate" value="OriginLiftgate">
                                <label for="rate_additional_origin_liftgate" class="form-label mb-0">Origin Liftgate</label>
                            </div>
                            <div class="d-flex mb-3 align-items-center gap-2">
                                <input type="checkbox" name="RateQuote[AdditionalServices][]" id="rate_additional_residential" value="LimitedAccessPickup">
                                <label for="rate_additional_residential" class="form-label mb-0">Residential/Limited Access Pickup</label>
                            </div>
                            <div class="d-flex mb-3 align-items-center gap-2">
                                <input type="checkbox" name="RateQuote[AdditionalServices][]" id="rate_additional_inside" value="InsidePickup">
                                <label for="rate_additional_inside" class="form-label mb-0">Inside Pickup</label>
                            </div>
                        </div>

                        <div class="col">
                            <label class="form-label text-decoration-underline fw-bold">Destination Options</label>

                            <div class="d-flex mb-3 align-items-center gap-2">
                                <input type="checkbox" name="RateQuote[AdditionalServices][]" id="rate_additional_destination_liftgate" value="DestinationLiftgate">
                                <label for="rate_additional_destination_liftgate" class="form-label mb-0">Destination Liftgate</label>
                            </div>
                            <div class="d-flex mb-3 align-items-center gap-2">
                                <input type="checkbox" name="RateQuote[AdditionalServices][]" id="rate_destination_residential" value="LimitedAccessDelivery">
                                <label for="rate_destination_residential" class="form-label mb-0">Residential/Limited Access Delivery</label>
                            </div>
                            <div class="d-flex mb-3 align-items-center gap-2">
                                <input type="checkbox" name="RateQuote[AdditionalServices][]" id="rate_destination_delivery" value="DeliveryAppointment">
                                <label for="rate_destination_delivery" class="form-label mb-0">Delivery Notification</label>
                            </div>
                            <div class="d-flex mb-3 align-items-center gap-2">
                                <input type="checkbox" name="RateQuote[AdditionalServices][]" id="rate_destination_sorSegregate" value="SortandSegregate">
                                <label for="rate_destination_sorSegregate" class="form-label mb-0">Sort and Segregate</label>
                            </div>
                        </div>

                        <div class="col">
                            <label class="form-label text-decoration-underline fw-bold">Shipment Options</label>

                            <div class="d-flex mb-3 align-items-center gap-2">
                                <input type="checkbox" name="RateQuote[AdditionalServices][]" id="rate_additional_shipment_freezable" value="Freezable">
                                <label for="rate_additional_shipment_freezable" class="form-label mb-0">Freezable</label>
                            </div>
                            <div class="d-flex mb-3 align-items-center gap-2">
                                <input type="checkbox" name="RateQuote[AdditionalServices][]" id="rate_shipment_hazmat" value="Hazmat">
                                <label for="rate_shipment_hazmat" class="form-label mb-0">Hazmat</label>
                            </div>
                            <div class="d-flex mb-3 align-items-center gap-2">
                                <input type="checkbox" name="RateQuote[AdditionalServices][]" id="rate_shipment_overdimensions" value="Overdimension">
                                <label for="rate_shipment_overdimensions" class="form-label mb-0">Overdimensions</label>
                            </div>
                        </div>

                    </div>

                </div>

                <div>
                    <div class="col-12 mb-3 ">
                        <button type="submit " class="btn btn-success" name="submit_rate_quote">Submit</button>
                    </div>
                </div>
            </form>
        </div>
        <div class="tab-pane fade show" id="bill-lading" role="tabpanel" aria-labelledby="bill-lading-tab">
            <h2 class="mb-3">Bill of Lading ID:<b><?php echo get_post_meta($order_id, 'proID', true); ?></b></h2>
            <?php $pickupid = get_post_meta($order_id, 'pickupID', true);
            $pickupid_arr = explode (",", $pickupid); 
                foreach ($pickupid_arr as $pickup_key => $pickup_value) {
                if(!empty($pickup_value)){ ?>
                    <b><?php echo $pickup_value;?></b><br><a href="<?php echo get_site_url().'/wp-admin/admin.php?page=sales-manager&order_id='.$post_id;?>&pickupDelete=<?php echo $pickup_value;?>">Delete</a> - <a href="<?php echo get_site_url().'/wp-admin/admin.php?page=sales-manager&order_id='.$post_id;?>&pickupView=<?php echo $pickup_value;?>">View</a>
                    <?php 
                    if(isset($_GET['pickupView']) AND $_GET['pickupView'] == $pickup_value) {
                        $response = sidtechno_curl_get('/PickupRequest?request.pickupRequestId='.$pickup_value);
                        if(isset($response['response'])) {
                            $resp  = json_decode($response['response'])->Pickup;
                            echo "<b>Contact CompanyName</b>".$resp->Contact->CompanyName."<br>";
                            echo "<b>Contact PhoneNumber</b>".$resp->Contact->PhoneNumber."<br>";
                            echo "<b>PickupDate</b>".$resp->PickupDate."<br>";
                            echo "<b>ReadyTime</b>".$resp->ReadyTime."<br>";
                            echo "<b>CloseTime</b>".$resp->CloseTime."<br>";
                        }

                    }
                    ?>
                    <br>
                <?php } 
                } ?>

            <a href='<?php echo get_site_url()."/wp-admin/admin.php?page=sales-manager&order_id=".$post_id."&download_rlcarrier=rlpdf&action=edit";?>' class='button'>DOWNLOAD BOL</a>
            <br>
            <a href='<?php echo get_site_url()."/wp-admin/admin.php?page=sales-manager&order_id=".$post_id."&download_rlcarrier=rllabelpdf&action=edit"?>' class='button'>DOWNLOAD BOL LABEL</a>
            <br>

            <form action="" method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="date_input" class="form-label">Date</label>
                        <input type="Date" class="form-control form-control-sm" name="BillOfLading[BOLDate]" id="date_input" value="<?php echo get_the_date('Y-m-d', $post_id);?>">
                    </div>
                </div>

                <div>
                    <h6 class="subtitle"><a data-bs-toggle="collapse" href="#shipper_from" role="button" aria-expanded="false" aria-controls="broker_info"><i class="fas fa-plus-square text-gray"></i></a> Shipper From</h6>
                    <div class="collapse" id="shipper_from">

                        <div class="row mb-3">

                            <div class="col">
                                <label for="company_name" class="form-label">Company name</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Shipper][CompanyName]" id="company_name" value="Premier Handling Solutions">
                            </div>

                            <div class="col">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control form-control-sm" name="BillOfLading[Shipper][EmailAddress]" id="email">
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col">
                                <label for="address_1" class="form-label">Address Line 1</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Shipper][AddressLine1]" id="address_1" value="1415 Davis Road">
                            </div>

                            <div class="col">
                                <label for="address_2" class="form-label">Address Line 2</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Shipper][AddressLine2]" id="address_2">
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-6">
                                <label for="country" class="form-label">Country</label>
                                <select type="text" class="form-control form-control-sm" name="BillOfLading[Shipper][CountryCode]" id="country">
                                    <option value="USA">United State</option>
                                </select>

                            </div>

                            <div class="col-md-3">
                                <label for="zip_postal" class="form-label">Zip Code</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Shipper][ZipOrPostalCode]" id="zip_postal" value="60123">
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Shipper][City]" id="city" value="ELGIN">
                            </div>

                            <div class="col-md-3">
                                <label for="state_province" class="form-label">State/Province</label>
                                <select class="form-control form-control-sm" name="BillOfLading[Shipper][StateOrProvince]" id="state_province">
                                    <option value="AL">Alabama</option>
                                    <option value="AK">Alaska</option>
                                    <option value="AZ">Arizona</option>
                                    <option value="AR">Arkansas</option>
                                    <option value="CA">California</option>
                                    <option value="CO">Colorado</option>
                                    <option value="CT">Connecticut</option>
                                    <option value="DE">Delaware</option>
                                    <option value="DC">District Of Columbia</option>
                                    <option value="FL">Florida</option>
                                    <option value="GA">Georgia</option>
                                    <option value="HI">Hawaii</option>
                                    <option value="ID">Idaho</option>
                                    <option value="IL" selected="selected">Illinois</option>
                                    <option value="IN">Indiana</option>
                                    <option value="IA">Iowa</option>
                                    <option value="KS">Kansas</option>
                                    <option value="KY">Kentucky</option>
                                    <option value="LA">Louisiana</option>
                                    <option value="ME">Maine</option>
                                    <option value="MD">Maryland</option>
                                    <option value="MA">Massachusetts</option>
                                    <option value="MI">Michigan</option>
                                    <option value="MN">Minnesota</option>
                                    <option value="MS">Mississippi</option>
                                    <option value="MO">Missouri</option>
                                    <option value="MT">Montana</option>
                                    <option value="NE">Nebraska</option>
                                    <option value="NV">Nevada</option>
                                    <option value="NH">New Hampshire</option>
                                    <option value="NJ">New Jersey</option>
                                    <option value="NM">New Mexico</option>
                                    <option value="NY">New York</option>
                                    <option value="NC">North Carolina</option>
                                    <option value="ND">North Dakota</option>
                                    <option value="OH">Ohio</option>
                                    <option value="OK">Oklahoma</option>
                                    <option value="OR">Oregon</option>
                                    <option value="PA">Pennsylvania</option>
                                    <option value="RI">Rhode Island</option>
                                    <option value="SC">South Carolina</option>
                                    <option value="SD">South Dakota</option>
                                    <option value="TN">Tennessee</option>
                                    <option value="TX">Texas</option>
                                    <option value="UT">Utah</option>
                                    <option value="VT">Vermont</option>
                                    <option value="VA">Virginia</option>
                                    <option value="WA">Washington</option>
                                    <option value="WV">West Virginia</option>
                                    <option value="WI">Wisconsin</option>
                                    <option value="WY">Wyoming</option>
                                </select>
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-4">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="phone" class="form-control form-control-sm" name="BillOfLading[Shipper][PhoneNumber]" id="phone_number" value="(847) 278-2321">
                            </div>

                            <div class="col-md-2">
                                <label for="phone_extension" class="form-label">Extension</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Shipper][PhoneExtension]" id="phone_extension">
                            </div>

                        </div>
                    </div>
                </div>

                <div>
                    <h6 class="subtitle"><a data-bs-toggle="collapse" href="#shipper_to" role="button" aria-expanded="false" aria-controls="broker_info"><i class="fas fa-plus-square text-gray"></i></a> Shipper To</h6>
                    <div class="collapse" id="shipper_to">

                        <div class="row mb-3">

                            <div class="col">
                                <label for="company_name" class="form-label">Company name</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Consignee][CompanyName]" id="company_name" value="<?php echo $order_detail['shipping']['first_name'];?> <?php echo $order_detail['shipping']['last_name'];?>">
                            </div>

                            <div class="col">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control form-control-sm" name="BillOfLading[Consignee][EmailAddress]" id="email" value="<?php echo $billing_email;?>">
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col">
                                <label for="address_1" class="form-label">Address Line 1</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Consignee][AddressLine1]" id="address_1" value="<?php echo $order_detail['shipping']['address_1'];?>">
                            </div>

                            <div class="col">
                                <label for="address_2" class="form-label">Address Line 2</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Consignee][AddressLine2]" id="address_2" value="<?php echo $order_detail['shipping']['address_2'];?>">
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-6">
                                <label for="country" class="form-label">Country</label>
                                <select type="text" class="form-control form-control-sm" name="BillOfLading[Consignee][CountryCode]" id="country">
                                    <option value="USA">United State</option>
                                </select>

                            </div>

                            <div class="col-md-3">
                                <label for="zip_postal" class="form-label">Zip Code</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Consignee][ZipOrPostalCode]" id="zip_postal" value="<?php echo $order_detail['shipping']['postcode'];?>">
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Consignee][City]" id="city" value="<?php echo $order_detail['shipping']['city'];?>">
                            </div>

                            <div class="col-md-3">
                                <label for="state_province" class="form-label">State/Province</label>
                                <select class="form-control form-control-sm" name="BillOfLading[Consignee][StateOrProvince]" id="state_province">
                                    <option value="AL" <?php if($order_detail['shipping']['state'] == 'AL') { echo 'selected="selected"'; }?>>Alabama</option>
                                    <option value="AK" <?php if($order_detail['shipping']['state'] == 'AK') { echo 'selected="selected"'; }?>>Alaska</option>
                                    <option value="AZ" <?php if($order_detail['shipping']['state'] == 'AZ') { echo 'selected="selected"'; }?>>Arizona</option>
                                    <option value="AR" <?php if($order_detail['shipping']['state'] == 'AR') { echo 'selected="selected"'; }?>>Arkansas</option>
                                    <option value="CA" <?php if($order_detail['shipping']['state'] == 'CA') { echo 'selected="selected"'; }?>>California</option>
                                    <option value="CO" <?php if($order_detail['shipping']['state'] == 'CO') { echo 'selected="selected"'; }?>>Colorado</option>
                                    <option value="CT" <?php if($order_detail['shipping']['state'] == 'CT') { echo 'selected="selected"'; }?>>Connecticut</option>
                                    <option value="DE" <?php if($order_detail['shipping']['state'] == 'DE') { echo 'selected="selected"'; }?>>Delaware</option>
                                    <option value="DC" <?php if($order_detail['shipping']['state'] == 'DC') { echo 'selected="selected"'; }?>>District Of Columbia</option>
                                    <option value="FL" <?php if($order_detail['shipping']['state'] == 'FL') { echo 'selected="selected"'; }?>>Florida</option>
                                    <option value="GA" <?php if($order_detail['shipping']['state'] == 'GA') { echo 'selected="selected"'; }?>>Georgia</option>
                                    <option value="HI" <?php if($order_detail['shipping']['state'] == 'HI') { echo 'selected="selected"'; }?>>Hawaii</option>
                                    <option value="ID" <?php if($order_detail['shipping']['state'] == 'ID') { echo 'selected="selected"'; }?>>Idaho</option>
                                    <option value="IL" <?php if($order_detail['shipping']['state'] == 'IL') { echo 'selected="selected"'; }?>>Illinois</option>
                                    <option value="IN" <?php if($order_detail['shipping']['state'] == 'IN') { echo 'selected="selected"'; }?>>Indiana</option>
                                    <option value="IA" <?php if($order_detail['shipping']['state'] == 'IA') { echo 'selected="selected"'; }?>>Iowa</option>
                                    <option value="KS" <?php if($order_detail['shipping']['state'] == 'KS') { echo 'selected="selected"'; }?>>Kansas</option>
                                    <option value="KY" <?php if($order_detail['shipping']['state'] == 'KY') { echo 'selected="selected"'; }?>>Kentucky</option>
                                    <option value="LA" <?php if($order_detail['shipping']['state'] == 'LA') { echo 'selected="selected"'; }?>>Louisiana</option>
                                    <option value="ME" <?php if($order_detail['shipping']['state'] == 'ME') { echo 'selected="selected"'; }?>>Maine</option>
                                    <option value="MD" <?php if($order_detail['shipping']['state'] == 'MD') { echo 'selected="selected"'; }?>>Maryland</option>
                                    <option value="MA" <?php if($order_detail['shipping']['state'] == 'MA') { echo 'selected="selected"'; }?>>Massachusetts</option>
                                    <option value="MI" <?php if($order_detail['shipping']['state'] == 'MI') { echo 'selected="selected"'; }?>>Michigan</option>
                                    <option value="MN" <?php if($order_detail['shipping']['state'] == 'MN') { echo 'selected="selected"'; }?>>Minnesota</option>
                                    <option value="MS" <?php if($order_detail['shipping']['state'] == 'MS') { echo 'selected="selected"'; }?>>Mississippi</option>
                                    <option value="MO" <?php if($order_detail['shipping']['state'] == 'MO') { echo 'selected="selected"'; }?>>Missouri</option>
                                    <option value="MT" <?php if($order_detail['shipping']['state'] == 'MT') { echo 'selected="selected"'; }?>>Montana</option>
                                    <option value="NE" <?php if($order_detail['shipping']['state'] == 'NE') { echo 'selected="selected"'; }?>>Nebraska</option>
                                    <option value="NV" <?php if($order_detail['shipping']['state'] == 'NV') { echo 'selected="selected"'; }?>>Nevada</option>
                                    <option value="NH" <?php if($order_detail['shipping']['state'] == 'NH') { echo 'selected="selected"'; }?>>New Hampshire</option>
                                    <option value="NJ" <?php if($order_detail['shipping']['state'] == 'NJ') { echo 'selected="selected"'; }?>>New Jersey</option>
                                    <option value="NM" <?php if($order_detail['shipping']['state'] == 'NM') { echo 'selected="selected"'; }?>>New Mexico</option>
                                    <option value="NY" <?php if($order_detail['shipping']['state'] == 'NY') { echo 'selected="selected"'; }?>>New York</option>
                                    <option value="NC" <?php if($order_detail['shipping']['state'] == 'NC') { echo 'selected="selected"'; }?>>North Carolina</option>
                                    <option value="ND" <?php if($order_detail['shipping']['state'] == 'ND') { echo 'selected="selected"'; }?>>North Dakota</option>
                                    <option value="OH" <?php if($order_detail['shipping']['state'] == 'OH') { echo 'selected="selected"'; }?>>Ohio</option>
                                    <option value="OK" <?php if($order_detail['shipping']['state'] == 'OK') { echo 'selected="selected"'; }?>>Oklahoma</option>
                                    <option value="OR" <?php if($order_detail['shipping']['state'] == 'OR') { echo 'selected="selected"'; }?>>Oregon</option>
                                    <option value="PA" <?php if($order_detail['shipping']['state'] == 'PA') { echo 'selected="selected"'; }?>>Pennsylvania</option>
                                    <option value="RI" <?php if($order_detail['shipping']['state'] == 'RI') { echo 'selected="selected"'; }?>>Rhode Island</option>
                                    <option value="SC" <?php if($order_detail['shipping']['state'] == 'SC') { echo 'selected="selected"'; }?>>South Carolina</option>
                                    <option value="SD" <?php if($order_detail['shipping']['state'] == 'SD') { echo 'selected="selected"'; }?>>South Dakota</option>
                                    <option value="TN" <?php if($order_detail['shipping']['state'] == 'TN') { echo 'selected="selected"'; }?>>Tennessee</option>
                                    <option value="TX" <?php if($order_detail['shipping']['state'] == 'TX') { echo 'selected="selected"'; }?>>Texas</option>
                                    <option value="UT" <?php if($order_detail['shipping']['state'] == 'UT') { echo 'selected="selected"'; }?>>Utah</option>
                                    <option value="VT" <?php if($order_detail['shipping']['state'] == 'VT') { echo 'selected="selected"'; }?>>Vermont</option>
                                    <option value="VA" <?php if($order_detail['shipping']['state'] == 'VA') { echo 'selected="selected"'; }?>>Virginia</option>
                                    <option value="WA" <?php if($order_detail['shipping']['state'] == 'WA') { echo 'selected="selected"'; }?>>Washington</option>
                                    <option value="WV" <?php if($order_detail['shipping']['state'] == 'WV') { echo 'selected="selected"'; }?>>West Virginia</option>
                                    <option value="WI" <?php if($order_detail['shipping']['state'] == 'WI') { echo 'selected="selected"'; }?>>Wisconsin</option>
                                    <option value="WY" <?php if($order_detail['shipping']['state'] == 'WY') { echo 'selected="selected"'; }?>>Wyoming</option>
                                </select>
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-4">
                                <label for="phone_number" class="form-label">Phone Number</label>
                                <input type="phone" class="form-control form-control-sm" name="BillOfLading[Consignee][PhoneNumber]" id="phone_number" value="<?php echo $billing_phone;?>">
                            </div>

                            <div class="col-md-2">
                                <label for="phone_extension" class="form-label">Extension</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Consignee][PhoneExtension]" id="phone_extension">
                            </div>

                            <div class="col-md-6">
                                <label for="consignee" class="form-label">Consignee Attention (optional)</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Consignee][Attention]" id="consignee">
                            </div>

                        </div>
                    </div>

                </div>

                <div>
                    <h6 class="subtitle"><a data-bs-toggle="collapse" href="#freight_charges" role="button" aria-expanded="false" aria-controls="broker_info"><i class="fas fa-plus-square text-gray"></i></a> Freight Charges paid by</h6>
                    <div class="collapse" id="freight_charges">
                        <div class="row mb-3">

                            <div class="col-md-4">
                                <input type="radio" class="" name="BillOfLading[FreightChargePaymentMethod]" id="prepaidCollection2" value="Prepaid" checked>
                                <label for="prepaidCollection2" class="form-label" data-bs-toggle="tab" data-bs-target="#prepaidContent1">Same as Ship From (Prepaid)</label>
                            </div>

                            <div class="col-md-2">
                                <input type="radio" class="" name="BillOfLading[FreightChargePaymentMethod]" id="prepaidCollection3" value="Collect">
                                <label for="prepaidCollection3" class="form-label" data-bs-toggle="tab" data-bs-target="#prepaidContent3">Same as Ship To (Collect)</label>
                            </div>

                        </div>
                    </div>
                </div>

                <div>
                    <h6 class="subtitle">
                        <a data-bs-toggle="collapse" href="#broker_info" role="button" aria-expanded="false" aria-controls="broker_info"><i class="fas fa-plus-square text-gray"></i></a> Broker Information (optional section)
                    </h6>

                    <div class="collapse" id="broker_info">
                        <div class="row mb-3">

                            <div class="col">
                                <label for="broker_name" class="form-label">Broker Name</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Broker][CompanyName]" id="broker_name">
                            </div>

                            <div class="col">
                                <label for="broker_email" class="form-label">Broker Email Address (optional)</label>
                                <input type="email" class="form-control form-control-sm" name="BillOfLading[Broker][EmailAddress]" id="broker_email">
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col">
                                <label for="broker_address_1" class="form-label">Broker Address Line 1 (optional)</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Broker][AddressLine1]" id="broker_address_1">
                            </div>

                            <div class="col">
                                <label for="broker_address_2" class="form-label">Broker Address Line 2 (optional)</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Broker][AddressLine2]" id="broker_address_2">
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-6">
                                <label for="broker_country" class="form-label">Broker Country (optional)</label>
                                <select type="text" class="form-control form-control-sm" name="BillOfLading[Broker][CountryCode]" id="broker_country">
                                    <option value="USA">United State</option>
                                </select>

                            </div>

                            <div class="col-md-3">
                                <label for="zip_postal" class="form-label">Zip Code (optional)</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Broker][ZipOrPostalCode]" id="zip_postal">
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-6">
                                <label for="broker_city" class="form-label">Broker City (optional)</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Broker][City]" id="broker_city">
                            </div>

                            <div class="col-md-3">
                                <label for="broker_state_province" class="form-label">Broker State/Province (optional)</label>
                                <input type="text" class="form-control form-control-sm" name="BillOfLading[Broker][StateOrProvince]" id="broker_state_province">
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-6 row">
                                <label for="broker_phone_number" class="form-label">Broker Phone Number/Extension (optional)</label>
                                <div class="col-md-8">
                                    <input type="phone" class="form-control form-control-sm" name="BillOfLading[Broker][PhoneNumber]" id="broker_phone_number ">
                                </div>
                                <div class="col-md-4">
                                    <input type="text " class="form-control form-control-sm" name="BillOfLading[Broker][PhoneExtension]" id="broker_phone_extension ">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <h6 class="subtitle"><a data-bs-toggle="collapse" href="#item_information" role="button" aria-expanded="false" aria-controls="remit"><i class="fas fa-plus-square text-gray"></i></a> Item Information</h6>
                    <div class="collapse" id="item_information">
                        <?php
                        $item_key = 0;
                        foreach ($order_items as $key => $item) {
                            $item_product_id = $item->get_product()->get_id();
                            $item_product_title = $item->get_product()->get_name();
                            $item_product_qty = $item->get_quantity();
                            $item_product_weight = get_post_meta($item_product_id,'_weight', true) * $item_product_qty;
                            $package_Type = 'PLT';
                            $total_pieces = calculate_pieces($item_product_qty, get_post_meta($item_product_id,'_height', true));

                            $terms = get_the_terms ( $item_product_id, 'product_cat' );
                            foreach ( $terms as $term ) {
                                if($term->name == 'wire') {
                                    $package_Type = 'BNDL';
                                }
                            }
                            $count = 1;
                            echo '<div class="d-flex justify-content-between mb-3">
                                <div class="d-flex align-items-center gap-4">
                                    <div class="font-bold">ITEM '.$count.'</div>
                                </div>
                            </div>
                            <input type="hidden" name="BillOfLading[Items]['.$item_key.'][IsHazmat]" value="false">
                            <div class="row mb-3 main_item">
                                <div class="col-md-3 d-flex gap-2">
                                    <div class="col-md-6">
                                        <label for="item-pieces" class="form-label">Pieces</label>
                                        <input type="text" class="form-control form-control-sm" name="BillOfLading[Items]['.$item_key.'][Pieces]" id="item-pieces-'.$count.'" value="'.$total_pieces.'">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="item_pkg" class="form-label">Pkg. Type</label>
                                        <select class="form-control form-control-sm text-uppercase" name="BillOfLading[Items]['.$item_key.'][PackageType]" id="item_pkg-'.$count.'">
                                            <option value="PLT"'; if($package_Type == 'PLT') { echo ' selected '; } echo '>PLT</option>
                                            <option value="BNDL"'; if($package_Type == 'BNDL') { echo ' selected '; } echo '>BNDL</option>
                                        </select>
                                    </div>
                                </div>


                                <div class="col-md-3">
                                    <label for="item-pieces" class="form-label">NMFC Item Number</label>
                                    <div class="d-flex gap-2">
                                        <div class="col-md-6">
                                            <input type="text" class="form-control form-control-sm" name="BillOfLading[Items]['.$item_key.'][NMFCItemNumber]" id="item-number-'.$count.'" value="150390">
                                        </div>
                                        <div class="col-md-6">
                                            <select class="form-control form-control-sm text-uppercase item_number_change" name="BillOfLading[Items]['.$item_key.'][NMFCSubNumber]" id="item_number-'.$count.'">
                                                <option value="01">1</option>
                                                <option value="02">2</option>
                                                <option value="03">3</option>
                                                <option value="04">4</option>
                                                <option value="05">5</option>
                                                <option value="06">6</option>
                                                <option value="07">7</option>
                                                <option value="08">8</option>
                                                <option value="09">9</option>
                                                <option value="10">10</option>
                                                <option value="11">11</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 d-flex gap-2">
                                    <div class="col-md-6">
                                        <label for="item_class" class="form-label">Class</label>
                                        <select class="form-control form-control-sm text-uppercase item_class_value" name="BillOfLading[Items]['.$item_key.'][Class]" id="item_class-'.$count.'">
                                            <option value="400">400</option>
                                            <option value="300">300</option>
                                            <option value="250">250</option>
                                            <option value="175">175</option>
                                            <option value="125">125</option>
                                            <option value="100">100</option>
                                            <option value="92.5">92.5</option>
                                            <option value="85">85</option>
                                            <option value="70">70</option>
                                            <option value="65">65</option>
                                            <option value="60">60</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="item_weight" class="form-label">Weight INT*</label>
                                        <input type="text" class="form-control form-control-sm" name="BillOfLading[Items]['.$item_key.'][Weight]" id="item_weight-'.$count.'" value="'.ceil($item_product_weight).'">
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label for="item_description" class="form-label">Desciption</label>
                                    <textarea name="BillOfLading[Items]['.$item_key.'][Description]" id="item_description-'.$count.'" rows="4" class="form-control">('.$item_product_qty.') '.$item_product_title.'</textarea>
                                </div>
                            </div>';
                            $count++;
                            $item_key++;
                        }?>

                    </div>
                </div>
                <div>
                    <h6 class="subtitle"><a data-bs-toggle="collapse" href="#additional_service" role="button" aria-expanded="false" aria-controls="remit"><i class="fas fa-plus-square text-gray"></i></a> Additional Services Needed (optional section)</h6>
                    <div class="collapse" id="additional_service">

                        <div class="row mb-3">

                            <div class="col">
                                <input type="checkbox" class="" name="BillOfLading[AdditionalServices][]" id="additional_origin_liftgate" value="OriginLiftgate">
                                <label for="additional_origin_liftgate" class="form-label">Origin Liftgate</label>
                            </div>

                            <div class="col">
                                <input type="checkbox" class="" name="BillOfLading[AdditionalServices][]" id="additional_destination" value="DestinationLiftgate">
                                <label for="additional_destination" class="form-label">Destination Liftgate</label>
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col">
                                <input type="checkbox" class="" name="BillOfLading[AdditionalServices][]" id="additional_inside_pickup" value="InsidePickup">
                                <label for="additional_inside_pickup" class="form-label">Inside Pickup</label>
                            </div>

                            <div class="col">
                                <input type="checkbox" class="" name="BillOfLading[AdditionalServices][]" id="additional_inside_delivery" value="InsideDelivery">
                                <label for="additional_inside_delivery" class="form-label">Inside Delivery</label>
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col">
                                <input type="checkbox" class="" name="BillOfLading[AdditionalServices][]" id="additional_residential_orig" value="LimitedAccessPickup">
                                <label for="additional_residential_orig" class="form-label">Residential/Limited Access (Orig.)</label>
                            </div>

                            <div class="col">
                                <input type="checkbox" class="" name="BillOfLading[AdditionalServices][]" id="additional_residential_dest" value="LimitedAccessDelivery">
                                <label for="additional_residential_dest" class="form-label">Residential/Limited Access (Dest.)</label>
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col">
                                <input type="checkbox" class="" name="BillOfLading[AdditionalServices][]" id="additional_d_notification" value="DeliveryAppointment">
                                <label for="additional_d_notification" class="form-label">Delivery Notification</label>
                            </div>

                            <div class="col">
                                <input type="checkbox" class="" name="BillOfLading[AdditionalServices][]" id="additional_freeze" value="Freezable">
                                <label for="additional_freeze" class="form-label">Freeze Protection</label>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="mb-3" id="signature_id" style="display: none;">
                    <h6 class="subtitle"><a data-bs-toggle="collapse" href="#no_signature" role="button" aria-expanded="false" aria-controls="remit"><i class="fas fa-plus-square text-gray"></i></a> No Signature Required For Delivery(optional section)</h6>
                    <div class="collapse" id="no_signature">
                        <div class="row mb-12 main_item append_here_clone_div">
                            <div class="col-md-12 d-flex gap-2 clone_this_div">
                                <div class="col-md-9">
                                    <label for="item-pieces" class="form-label">Email</label>
                                    <input type="text" class="form-control form-control-sm" name="BillOfLading[NoSignatureNotificationContacts][EmailAddresses][]" value="">
                                </div>
                                <div class="col-md-3">
                                    <span class="delete_this_filed" style="cursor: pointer;">-</span>
                                    <span class="add_this_filed" style="cursor: pointer;">+</span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-12 main_item append_here_clone_div">
                            <div class="col-md-12 d-flex gap-2 clone_this_div">
                                <div class="col-md-9">
                                    <label for="item-pieces" class="form-label">Mobile Number</label>
                                    <input type="text" class="form-control form-control-sm" name="BillOfLading[NoSignatureNotificationContacts][PhoneNumbers][]" value="">
                                </div>
                                <div class="col-md-3">
                                    <span class="delete_this_filed" style="cursor: pointer;">-</span>
                                    <span class="add_this_filed" style="cursor: pointer;">+</span>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>

                <div>
                    <h6 class="subtitle"><a data-bs-toggle="collapse" href="#select_service_level" role="button" aria-expanded="false" aria-controls="remit"><i class="fas fa-plus-square text-gray"></i></a> Select Service Level</h6>
                    <div class="collapse" id="select_service_level">

                        <div class="d-flex w-100 align-items-center border-bottom pb-3 gap-4 mb-3">
                            <input type="radio" class="" name="BillOfLading[ServiceLevel]" id="stand_service" value="Standard" checked>
                            <label for="stand_service" class="form-label mb-0">
                                <div class="fw-bold">Standard Service</div>
                                The value you've come to expect from R+L Carriers at an affordable price.
                            </label>
                        </div>
                    </div>

                </div>

                    <div class="mb-3">
                        <h6 class="subtitle">Contact Information</h6>

                        <div class="col fw-bold mb-3">
                            (Fill out only if different than Shipper Info.)
                        </div>

                        <div class="row mb-3">

                            <div class="col">
                                <label for="pickup_contact_contact_name" class="form-label">Contact Name (optional)</label>
                                <input type="text" class="form-control form-control-sm" name="Contact[CompanyName]" id="pickup_contact_contact_name" value="Premier Handling Solutions<?php if(isset($_GET['pickupEdit'])) { echo $response->Pickup->Shipper->CompanyName; }?>">
                            </div>

                        </div>

                        <div class="row mb-3">

                            <div class="col-md-6 row m-0 p-0">
                                <label for="pickup_contact_phone" class="form-label">Phone Number/Extension (optional)</label>
                                <div class="col-md-8">
                                    <input type="phone" class="form-control form-control-sm" name="Contact[PhoneNumber]" id="pickup_contact_phone " value="(847) 278-2321<?php if(isset($_GET['pickupEdit'])) { echo $response->Pickup->Shipper->CompanyName; }?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="text " class="form-control form-control-sm" name="Contact[PhoneExtension] " id="pickup_contact_extension " value="<?php if(isset($_GET['pickupEdit'])) { echo $response->Pickup->Shipper->CompanyName; }?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="pickup_contact_contact_email" class="form-label">Contact Email Address (optional)</label>
                                <input type="text" class="form-control form-control-sm" name="Contact[EmailAddress]" id="pickup_contact_contact_email" value="<?php if(isset($_GET['pickupEdit'])) { echo $response->Pickup->Shipper->CompanyName; }?>">
                            </div>

                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="subtitle">Pickup Information</h6>

                        <div class="row mb-3">

                            <div class="col">
                                <label for="pickup_info_date" class="form-label">Pickup Date</label>
                                <input type="date" class="form-control form-control-sm" name="Pickup[PickupDate]" id="pickup_info_date">
                            </div>

                            <div class="col">
                                <label for="pickup_info_readyTime" class="form-label">Ready Time</label>
                                <input type="time" class="form-control form-control-sm" name="Pickup[ReadyTime]" id="pickup_info_readyTime">
                            </div>

                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="pickup_info_closeTime" class="form-label">Close Time</label>
                                <input type="time" class="form-control form-control-sm" name="Pickup[CloseTime]" id="pickup_info_closeTime">
                            </div>
                        </div>
                        <div class="row mb-3">

                            <div class="col">
                                <div class="d-flex mb-3 align-items-center gap-2">
                                    <input type="checkbox" name="Pickup[LoadAttributes][]" id="rate_additional_origin_liftgate" value="Food">
                                    <label for="rate_additional_origin_liftgate" class="form-label mb-0">Food</label>
                                </div>

                                <div class="d-flex mb-3 align-items-center gap-2">
                                    <input type="checkbox" name="Pickup[LoadAttributes][]" id="rate_additional_origin_liftgate" value="Hazmat">
                                    <label for="rate_additional_origin_liftgate" class="form-label mb-0">Hazmat</label>
                                </div>
                            </div>

                            <div class="col">
                                <div class="d-flex mb-3 align-items-center gap-2">
                                    <input type="checkbox" name="Pickup[LoadAttributes][]" id="rate_additional_origin_liftgate" value="Poison">
                                    <label for="rate_additional_origin_liftgate" class="form-label mb-0">Poison</label>
                                </div>
                                <div class="d-flex mb-3 align-items-center gap-2">
                                    <input type="checkbox" name="Pickup[LoadAttributes][]" id="rate_additional_origin_liftgate" value="Freezable">
                                    <label for="rate_additional_origin_liftgate" class="form-label mb-0">Freezable</label>
                                </div>
                            </div>

                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label for="item_description" class="form-label">Additional Instructions</label>
                                <textarea name="Pickup[AdditionalInstructions]" id="item_description" rows="4" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>


                <div>
                    <div class="col-12 mb-3 ">
                        <button type="submit " class="btn btn-primary" name="submit_bol_form">Submit</button>
                    </div>

                </div>

            </form>
        </div>
    </div>

</div>
