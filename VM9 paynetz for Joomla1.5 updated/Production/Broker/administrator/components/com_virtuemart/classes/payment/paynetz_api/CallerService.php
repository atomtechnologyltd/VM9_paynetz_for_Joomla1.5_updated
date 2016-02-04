<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
/**
* @author Ashish Solanki
*/
	function requestMerchant($param){
		$_url  = '';
		$_bankId = "1234";
		$datenow = date("d/m/Y h:m:s");
		$encodedDate = str_replace(" ", "%20", $datenow);
		$_url = $_url.$param['paynetz_url'];
		$postFields  = "";
		$postFields .= "&login=".$param['login_id'];
		$postFields .= "&pass=".$param['password'];
		$postFields .= "&ttype=".$param['ttype'];
		$postFields .= "&prodid=".$param['prod_id'];
		$postFields .= "&amt=".$param['amount'];
		$postFields .= "&txncurr=".$param['curr'];
		$postFields .= "&txnscamt=".$param['txnamt'];
		$postFields .= "&clientcode=".urlencode(base64_encode($param['client_code']));
		$postFields .= "&txnid=".$param['ordernum'];
		$postFields .= "&date=".$encodedDate;
		$postFields .= "&custacc=".$param['customer_acc_no'];
		//Uncomment below line for broker
		$postFields .= "&bankid=".$_bankId;
		$sendUrl = $_url."?".substr($postFields,1)."\n";

		$returnData = curlExec($sendUrl);
		$xmlObjArray     = xmltoarray($returnData);

		$url = $xmlObjArray['url'];
		$postFields  = "";
		$postFields .= "&ttype=".$param['ttype'];
		$postFields .= "&tempTxnId=".$xmlObjArray['tempTxnId'];
		$postFields .= "&token=".$xmlObjArray['token'];
		$postFields .= "&txnStage=1";
		$url = $_url."?".$postFields;
		header("Location: ".$url);
	}

	function xmltoarray($data){
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); 
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($data), $xml_values);
		xml_parser_free($parser);
		
		$returnArray = array();
		$returnArray['url'] = $xml_values[3]['value'];
		$returnArray['tempTxnId'] = $xml_values[5]['value'];
		$returnArray['token'] = $xml_values[6]['value'];

		return $returnArray;
	}

	function curlExec($base_url){
		$ch = curl_init($base_url);
		curl_setopt_array($ch, array(
		CURLOPT_URL            => $base_url,
		CURLOPT_RETURNTRANSFER => TRUE,
		CURLOPT_TIMEOUT        => 30,
		CURLOPT_USERAGENT      => 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)',
		CURLOPT_SSL_VERIFYPEER =>0,
		CURLOPT_SSL_VERIFYHOST => 0
	  ));

	  $results = curl_exec($ch);
	  return $results;
	}

?>