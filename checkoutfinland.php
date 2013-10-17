<?php

$nzshpcrt_gateways[$num] = array(
	'name' 						=> __('Checkout Finland', 'wpsc'),
	'api_version'				=> 2.0,
	'class_name'				=> 'wpsc_merchant_checkoutfinland',
	'has_recurring_billing' 	=> false,
	'wp_admin_cannot_cancel' 	=> true,
	'display_name'				=> __('Checkout Finland', 'wpsc'),
	'internalname'				=> 'wpsc_merchant_checkoutfinland',
	'form'						=> 'form_checkoutfinland',
	'submit_function'			=> 'submit_checkoutfinland',
	'payment_type'				=> 'checkoutfinland'
);

class wpsc_merchant_checkoutfinland extends wpsc_merchant
{

	public function __construct($purchase_id = null, $is_receiving = false)
	{
		if($purchase_id == null and (isset($_GET['STAMP']) and isset($_GET['MAC']))) {
			$purchase_id = $_GET['STAMP'];
		}

		parent::__construct($purchase_id, $is_receiving);
	}

	public function submit()
	{

		$post['VERSION']		= "0001";
		$post['STAMP']			= $this->purchase_id;
		$post['AMOUNT']			= $this->cart_data['total_price'] * 100; // amount is in cents
		$post['REFERENCE']		= $this->createReferenceNumber();
		$post['MESSAGE']		= "";
		$post['LANGUAGE']		= $this->getLanguage();
		$post['MERCHANT']		= get_option('checkoutfinland_merchant_id');
		// responses from checkout are handled in the same function
		$return_url				= substr($this->cart_data['notification_url'] ."&sessionid=".$this->cart_data['session_id']."&gateway=wpsc_merchant_checkoutfinland", 0, 300);
		$post['RETURN']			= $return_url;
		$post['CANCEL']			= $return_url;
		$post['REJECT']			= $return_url;
		$post['DELAYED']		= $return_url;

		$post['COUNTRY']		= "FIN";
		$post['CURRENCY']		= $this->getCurrencyCode();
		$post['DEVICE']			= "10";
		$post['CONTENT']		= "1";
		$post['TYPE']			= "0";
		$post['ALGORITHM']		= "3";
		$post['DELIVERY_DATE']	= date('Ymd', strtotime("+".get_option('checkoutfinland_delivery_time')." days"));
		$post['FIRSTNAME']		= "".substr($this->cart_data['billing_address']['first_name'], 0, 40);
		$post['FAMILYNAME']		= "".substr($this->cart_data['billing_address']['last_name'], 0, 40);
		$post['ADDRESS']		= "".substr($this->cart_data['billing_address']['address'], 0, 40);
		$post['POSTCODE']		= "".substr($this->cart_data['billing_address']['post_code'], 0, 5);
		$post['POSTOFFICE']		= "".substr($this->cart_data['billing_address']['city'] ." ".$this->cart_data['billing_address']['country'], 0, 18);

		$mac = "";
		foreach($post as $value) {
			$mac .= "$value+";
		}
		$mac .= get_option('checkoutfinland_secret');
		$post['MAC'] = strtoupper(md5($mac));
		
		// post the data
		$response = $this->postData($post);

       	// get the payment url from response
       	$xml = simplexml_load_string($response);
       	if($xml) {
       		// redirect to checkout payment page
       		status_header(302);
			wp_redirect($xml->paymentURL);
			exit;
		}
		else  {
			echo "Virhe. Maksutapahtuman luonti ei onnistunut.";
		}

	}

	private function postData($postData)
	{
		if(ini_get('allow_url_fopen'))
        {
        	$context = stream_context_create(array(
        		'http' => array(
        			'method' => 'POST',
        			'header' => 'Content-Type: application/x-www-form-urlencoded',
        			'content' => http_build_query($postData)
        		)
        	));
        	
        	return file_get_contents('https://payment.checkout.fi', false, $context);
        } 
        elseif(in_array('curl', get_loaded_extensions()) ) 
        {
            $options = array(
                CURLOPT_POST            => 1,
                CURLOPT_HEADER          => 0,
                CURLOPT_URL             => 'https://payment.checkout.fi',
                CURLOPT_FRESH_CONNECT   => 1,
                CURLOPT_RETURNTRANSFER  => 1,
                CURLOPT_FORBID_REUSE    => 1,
                CURLOPT_TIMEOUT         => 4,
                CURLOPT_POSTFIELDS      => http_build_query($postData)
            );
        
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $result = curl_exec($ch);
            curl_close($ch);

            return $result;
        }
        else 
        {
            throw new Exception("No valid method to post data. Set allow_url_fopen setting to On in php.ini file or install curl extension.");
        }
	}

	private function createReferenceNumber()
    {
    	$result = '';
        $orderId = $this->purchase_id;
            
        foreach (str_split($orderId . $this->countRef($orderId), 5) as $part) $result .= $part;
    	return $result;
    }

	private function countRef($refnumber)
	{
	    $multipliers = array(7,3,1);
	    $length = strlen($refnumber);
	    $refnumber = str_split($refnumber);
	    $sum = 0;
	    for ($i = $length - 1; $i >= 0; --$i) {
	      $sum += $refnumber[$i] * $multipliers[($length - 1 - $i) % 3];
	    }
	    return (10 - $sum % 10) % 10;
	}

	private function getCurrencyCode()
	{
		if ( empty( $this->local_currency_code ) ) {
			global $wpdb;
			$this->local_currency_code = $wpdb->get_var("SELECT `code` FROM `".WPSC_TABLE_CURRENCY_LIST."` WHERE `id`='".get_option('currency_type')."' LIMIT 1");
		}

		if($this->local_currency_code == 'EUR')
			return $this->local_currency_code;
		else
			throw new Exception('Wrong currency. Checkout Finland only supports transactions in EURO.');
	}

	private function getLanguage()
	{
		$lang = get_bloginfo('language');

		if($lang == 'fi_FI')
			return 'FI';
		else
			return 'EN';
	}

	public function process_gateway_notification()
	{
		global $wpdb;
		
		$version   	= $_GET['VERSION'];
    	$stamp     	= $_GET['STAMP'];
    	$reference 	= $_GET['REFERENCE'];
    	$payment   	= $_GET['PAYMENT'];
    	$status    	= $_GET['STATUS'];
    	$algorithm 	= $_GET['ALGORITHM'];
    	$mac       	= $_GET['MAC'];
    	$secret 	= get_option('checkoutfinland_secret');

    	if($algorithm == 1)
    		$expected_mac = strtoupper(md5("$version+$stamp+$reference+$payment+$status+$algorithm+$secret"));
    	elseif($algorithm == 2)
    		$expected_mac = strtoupper(md5("$secret&$version&$stamp&$reference&$payment&$status&$algorithm"));
    	elseif($algorithm == 3) 
    		$expected_mac = strtoupper(hash_hmac("sha256","$version&$stamp&$reference&$payment&$status&$algorithm", $secret));
    	else throw new Exception('Unsuported algorithm: '.$algorithm);

		if($expected_mac == $mac)
    	{
    		switch($status)
    		{
    			case '2':
    			case '5':
    			case '6':
    			case '8':
    			case '9':
    			case '10':
					$this->set_purchase_processed_by_purchid(3);
    				break;
    			case '7':
    			case '3':
    			case '4':
    				$this->set_transaction_details($stamp, 2);
    				break;
    			case '-1':
    				$this->set_transaction_details($stamp, 6);
    				break;
    			case '-2':
    			case '-3':
    			case '-4':
    			case '-10':
    				$this->set_transaction_details($stamp, 6);
    				break;
    		}

    		status_header(302);
			wp_redirect(get_option('transact_url')."&sessionid=".$_GET['sessionid']);
    	}
    	else
    	{
    		$this->set_purchase_processed_by_purchid(6);
    		throw new Exception('MAC mismatc');
    	}


	}
}

function submit_checkoutfinland()
{

	if(isset($_POST['checkoutfinland_merchant_id']))
		update_option('checkoutfinland_merchant_id', $_POST['checkoutfinland_merchant_id']);
	
	if(isset($_POST['checkoutfinland_secret']))
		update_option('checkoutfinland_secret', $_POST['checkoutfinland_secret']);

	if(isset($_POST['checkoutfinland_delivery_time']))
		update_option('checkoutfinland_delivery_time', $_POST['checkoutfinland_delivery_time']);
	return true;
}

function form_checkoutfinland()
{
	global $wpdb, $wpsc_gateways;

	$options = "";
	$selected = get_option('checkoutfinland_delivery_time');
	for($i = 0; $i < 31; $i++)
	{
		if($selected == $i)
			$options .= "<option value='$i' selected='selected'>$i</option>";
		else
			$options .= "<option value='$i'>$i</option>";
	}

	$output = "
		<tr>
			<td>". __('Kauppiastunnus', 'wpsc') ."</td>
			<td>
				<input type='text' name='checkoutfinland_merchant_id' value='".get_option('checkoutfinland_merchant_id')."' /><br />
				<small>".__('Checkout Finland kauppiastunnuksesi', 'wpsc')."</small>
			</td>
		</tr>
		<tr>
			<td>". __('Turva-avain', 'wpsc') ."</td>
			<td>
				<input type='text' name='checkoutfinland_secret' value='".get_option('checkoutfinland_secret')."' /><br />
				<small>".__('Salainen turva-avaimesi')."</small>
			</td>
		</tr>
		<tr>
			<td>". __('Toimitusaika', 'wpsc') ."</td>
			<td>
				<select name='checkoutfinland_delivery_time'>
					$options
				</select><br />
				<small>".__('Tuotteidenne keskimääräinen toimitusaika päivissä')."</small>
			</td>
		</tr>
	";

	return $output;
}
?>