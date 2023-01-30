<?php
   /*
   Plugin Name: AV ADF Email
   Plugin URI: https://aimexperts.com
   description: This allows CF7 Form entries to be recored in the AIM lead records.
   Version: 1.0.0
   Author: Liam Kennedy
   Author URI: https://aimexperts.com
   License: GPL2
   */


   // Ajax request test drive
   function stm_ajax_add_test_drive_adf() {
	   check_ajax_referer( 'stm_ajax_add_test_drive', 'security' );
   
	   $response['errors'] = array();
   
	   if ( ! filter_var( $_POST['name'], FILTER_SANITIZE_STRING ) ) {
		   $response['response']       = esc_html__( 'Please fill all fields', 'motors' );
		   $response['errors']['name'] = true;
	   }
	   if ( ! is_email( $_POST['email'] ) ) {
		   $response['response']        = esc_html__( 'Please enter correct email', 'motors' );
		   $response['errors']['email'] = true;
	   }
	   if ( ! is_numeric( $_POST['phone'] ) ) {
		   $response['response']        = esc_html__( 'Please enter correct phone number', 'motors' );
		   $response['errors']['phone'] = true;
	   }
	   if ( empty( $_POST['date'] ) ) {
		   $response['response']       = esc_html__( 'Please fill all fields', 'motors' );
		   $response['errors']['date'] = true;
	   }
   
	   if ( ! filter_var( $_POST['name'], FILTER_SANITIZE_STRING ) && ! is_email( $_POST['email'] ) && ! is_numeric( $_POST['phone'] ) && empty( $_POST['date'] ) ) {
		   $response['response'] = esc_html__( 'Please fill all fields', 'motors' );
	   }
   
	   $recaptcha = true;
   
	   $recaptcha_enabled    = stm_me_get_wpcfto_mod( 'enable_recaptcha', 0 );
	   $recaptcha_secret_key = stm_me_get_wpcfto_mod( 'recaptcha_secret_key' );
   
	   if ( $recaptcha_enabled ) {
		   if ( isset( $_POST['g-recaptcha-response'] ) ) {
			   $recaptcha = stm_motors_check_recaptcha( $recaptcha_secret_key, sanitize_text_field( $_POST['g-recaptcha-response'] ) );
		   }
	   }
   
	   if ( $recaptcha ) {
		   if ( empty( $response['errors'] ) && ( ! empty( $_POST['vehicle_id'] ) || ! empty( $_POST['vehicle_name'] ) ) ) {
			   $vehicle_id                = ( isset( $_POST['vehicle_id'] ) && ! empty( $_POST['vehicle_id'] ) ) ? intval( filter_var( $_POST['vehicle_id'], FILTER_SANITIZE_NUMBER_INT ) ) : '';
			   $title                     = ( ! empty( $vehicle_id ) ) ? get_the_title( $vehicle_id ) : '';
			   $title                     = ( isset( $_POST['vehicle_name'] ) && ! empty( $_POST['vehicle_name'] ) ) ? sanitize_text_field( $_POST['vehicle_name'] ) : $title;
			   $test_drive['post_title']  = esc_html__( 'New request for test drive', 'motors' ) . ' ' . $title;
			   $test_drive['post_type']   = 'test_drive_request';
			   $test_drive['post_status'] = 'draft';
			   $test_drive_id             = wp_insert_post( $test_drive );
			   update_post_meta( $test_drive_id, 'name', sanitize_text_field( $_POST['name'] ) );
			   update_post_meta( $test_drive_id, 'email', sanitize_email( $_POST['email'] ) );
			   update_post_meta( $test_drive_id, 'phone', sanitize_text_field( $_POST['phone'] ) );
			   update_post_meta( $test_drive_id, 'date', sanitize_text_field( $_POST['date'] ) );
			   $response['response'] = esc_html__( 'Your request was sent', 'motors' );
			   $response['status']   = 'success';
   
			   // Sending Mail to admin.
			   stm_me_set_html_content_type();
   
			   $to = 'liamjken@gmail.com';
   
			   $args = array(
				   'car'       => $title,
				   'name'      => sanitize_text_field( $_POST['name'] ),
				   'email'     => sanitize_email( $_POST['email'] ),
				   'phone'     => sanitize_text_field( $_POST['phone'] ),
				   'best_time' => sanitize_text_field( $_POST['date'] ),
			   );
   
			   $subject = generateSubjectView( 'test_drive', $args );
			   $body    = generateTemplateView( 'test_drive', $args );
   
			   $unique_sequence    = uniqid(); 
				   $url                = get_the_permalink();  
				   $requestdate        = date('Y-m-d H:i:s');  
				   $condition          = get_post_meta($vehicle_id, 'condition', true);    
				   if($condition == 'new-cars') {  
					   $condition = 'new'; 
				   } else {    
					   $condition = 'used';    
				   }   
		   
				   $year       = get_post_meta($vehicle_id, 'ca-year', true);  
				   $make       = get_post_meta($vehicle_id, 'make', true); 
				   $model      = get_post_meta($vehicle_id, 'serie', true);    
				   $trim       = get_post_meta($vehicle_id, 'trim', true); 
		   
				   $finance_bw = get_post_meta($vehicle_id, 'finance_bw', true);   
				   if(!empty($finance_bw)) {   
					   $finance_bw = '';   
				   } else {    
					   $finance_bw = number_format($finance_bw,2); 
				   }   
		   
				   $name = $_POST['name']; 
				   $email = $_POST['email'];   
				   $phone = $_POST['phone'];   
		   
				   $body_adf = '<?xml version="1.0" encoding="UTF-8"?> 
					   <?ADF VERSION "1.0"?>   
					   <adf>   
					   <prospect>  
					   <id source="Schedule a Test Drive">'.$unique_sequence.'</id>    
					   <requestdate>'.$requestdate.'</requestdate> 
					   <vehicle interest="buy" status="'.$condition.'">    
					   <year>'.$year.'</year>  
					   <make>'.$make.'</make>  
					   <model>'.$model.'</model>   
					   </vehicle>  
					   <customer>  
					   <contact>   
					   <name part="full" type="individual">'.$name.'</name>    
					   <email>'.$email.'</email>   
					   <phone type="voice" time="day" preferredcontact="1">'.$phone.'</phone>  
					   </contact>  
					   </customer> 
					   <vendor>    
					   <id source="Langley Chrysler">'.$vehicle_id.'</id>  
					   <vendorname>Langley Chrysler</vendorname>   
					   <url /> 
					   <contact>   
					   <name part="full" type="business"> Schedule a Test Drive </name>    
					   <email />   
					   </contact>  
					   </vendor>   
					   <provider>  
					   <id sequence="1" source=" Schedule a Test Drive ">'.$vehicle_id.'</id>  
					   <name part="full" type="business"> Schedule a Test Drive </name>    
					   <service>'.ucwords($condition).' Car Lead</service> 
					   </provider> 
					   </prospect> 
					   </adf>';    
		   
				   $headers = "MIME-Version: 1.0\r\n"; // Defining the MIME version    
				   $headers .= "From: Abbotsford Chrysler <support@abbotsfordchrysler.com>\r\n";   
					   
				   //mail("leads@abbotsfordchrysler.ca",$subject,$body_adf,$headers);    
				   mail("liamjken@gmail.com",$subject,$body_adf,$headers);
				   //mail("harman.decosoftsolutions@gmail.com",$subject,$body_adf,$headers)
   
			   if ( stm_is_listing() ) {
				   $car_owner = get_post_meta( $vehicle_id, 'stm_car_user', true );
				   if ( ! empty( $car_owner ) ) {
					   $user_fields = stm_get_user_custom_fields( $car_owner );
					   if ( ! empty( $user_fields ) && ! empty( $user_fields['email'] ) ) {
						   do_action( 'stm_wp_mail', $user_fields['email'], $subject, $body, '' );
					   }
				   }
			   } else {
				   do_action( 'stm_wp_mail', $to, $subject, $body, '' );
			   }
   
			   do_action( 'stm_remove_mail_content_type_filter' );
   
		   } else {
			   $response['response'] = esc_html__( 'Please fill all fields', 'motors' );
			   $response['status']   = 'danger';
		   }
   
		   $response['recaptcha'] = true;
	   } else {
		   $response['recaptcha'] = false;
		   $response['status']    = 'danger';
		   $response['response']  = esc_html__( 'Please prove you\'re not a robot', 'motors' );
	   }
   
	   wp_send_json( $response );
	   exit;
   }
   
   add_action( 'wp_ajax_stm_ajax_add_test_drive_adf', 'stm_ajax_add_test_drive_adf' );
   add_action( 'wp_ajax_nopriv_stm_ajax_add_test_driv_adf', 'stm_ajax_add_test_drive_adf' );