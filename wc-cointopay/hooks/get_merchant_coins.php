<?php
add_action( 'wp_ajax_nopriv_getCTPMerchantCoinsByAjax', 'getCTPMerchantCoinsByAjax' );
add_action( 'wp_ajax_getCTPMerchantCoinsByAjax', 'getCTPMerchantCoinsByAjax' );
function getCTPMerchantCoinsByAjax()
{
	$merchantId = 0;
	$merchantId = intval($_REQUEST['merchant']);
	if(isset($merchantId) && $merchantId !== 0)
	{
		$option = '';
		$arr = getCTPMerchantCoins($merchantId);
		foreach($arr as $key => $value)
		{
			$option .= '<option value="'.$key.'">'.$value.'</option>';
		}
		
		echo $option;exit();
	}
}

function getCTPMerchantCoins($merchantId)
{
	$params = array(
		'body' => 'MerchantID=' . $merchantId . '&output=json',
	);
	$url = 'https://cointopay.com/CloneMasterTransaction';
	$response  = wp_safe_remote_post($url, $params);
	if (( false === is_wp_error($response) ) && ( 200 === $response['response']['code'] ) && ( 'OK' === $response['response']['message'] )) {
		$php_arr = json_decode($response['body']);
		$new_php_arr = array();

		if(!empty($php_arr))
		{
			for($i=0;$i<count($php_arr)-1;$i++)
			{
				if(($i%2)==0)
				{
					$new_php_arr[$php_arr[$i+1]] = $php_arr[$i];
				}
			}
		}
		
		return $new_php_arr;
	}
}