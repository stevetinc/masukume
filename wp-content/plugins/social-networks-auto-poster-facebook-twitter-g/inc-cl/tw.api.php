<?php    
//## NextScripts Twitter Connection Class
$nxs_snapAPINts[] = array('code'=>'TW', 'lcode'=>'tw', 'name'=>'Twitter');

if (!class_exists("nxs_class_SNAP_TW")) { class nxs_class_SNAP_TW {
    
    var $ntCode = 'TW';
    var $ntLCode = 'tw';
    
    function doPost($options, $message){ if (!is_array($options)) return false; $out = array(); // return false;
      foreach ($options as $ii=>$ntOpts) $out[$ii] = $this->doPostToNT($ntOpts, $message);
      return $out;
    }
    
    function twReq($reqURL, $params_array, $options, $img=''){ $kv = array(); $proxy = ''; $sparams_array = ''; $nounce = md5(time().'666'); $ts = time(); if (empty($params_array)) $params_array = array(); uksort($params_array, 'strcmp');      
      $arrToSign = array_merge(array('oauth_consumer_key'=>nxs_gak($options['appKey']),'oauth_nonce'=>$nounce,'oauth_signature_method'=>'HMAC-SHA1','oauth_timestamp'=>$ts,'oauth_token'=>$options['accessToken'],'oauth_version'=>'1.0'), $params_array);
      uksort($arrToSign, 'strcmp'); foreach ($arrToSign as $k => $v) $kv[] = $k.'='.rawurlencode($v); $sparams_array = implode('&', $kv); $base = 'POST&'.rawurlencode($reqURL).'&'.rawurlencode($sparams_array); 
      $signKey = (nxs_gas($options['appSec']).'&'.$options['accessTokenSec']); $signature = rawurlencode(base64_encode(hash_hmac("sha1", $base, $signKey, true))); $hdrsArr = nxs_makeHeaders($reqURL);       
      $hdrsArr['Authorization'] = 'OAuth oauth_consumer_key="'.nxs_gak($options['appKey']).'", oauth_nonce="'.$nounce.'", oauth_signature="'.$signature.'", oauth_signature_method="HMAC-SHA1", oauth_timestamp="'.$ts.'", oauth_token="'.$options['accessToken'].'", oauth_version="1.0"';      
      if (!empty($img)) $hdrsArr['Content-type'] = 'multipart/form-data';  $advSet = nxs_mkRemOptsArr($hdrsArr, '', (!empty($img)?$img:$sparams_array), $proxy); if (!empty($img)) $advSet['usearray'] = 1; $ret = nxs_remote_post( $reqURL, $advSet); return $ret;
    }
    
    function doPostToNT($options, $message){ global $nxs_urlLen; $badOut = array('pgID'=>'', 'isPosted'=>0, 'pDate'=>date('Y-m-d H:i:s'), 'Error'=>''); 
      if (!function_exists('nxs_remote_get') && function_exists('nxs_remote_get')) { function nxs_remote_get($url){return nxs_remote_get($url);} }
      if (!function_exists('is_nxs_error') && function_exists('is_nxs_error')) { function is_nxs_error($a){return is_nxs_error($a);} }
      //## Check settings
      if (!is_array($options)) { $badOut['Error'] = 'No Options'; return $badOut; }      
      if (!isset($options['accessToken']) || trim($options['accessToken'])=='') { $badOut['Error'] = 'No Auth Token Found'; return $badOut; }
      if (empty($options['imgSize'])) $options['imgSize'] = '';
      //## Old Settings Fix
      if ($options['attchImg']=='1') $options['attchImg'] = 'large'; if ($options['attchImg']=='0') $options['attchImg'] = false;
      if (isset($message['img']) && is_string($message['img']) ) $img = trim($message['img']); else $img = ''; 
      //## Format Post
      if (!empty($message['pText'])) $msg = $message['pText']; else $msg = nxs_doFormatMsg($options['msgFormat'], $message);
      if ($options['attchImg']!=false) { if (isset($message['imageURL'])) $imgURL = trim(nxs_getImgfrOpt($message['imageURL'], $options['imgSize'])); else $imgURL = ''; }
      if (empty($imgURL) && $img=='') $options['attchImg'] = false; 
      //$ret = $this->twReq('https://api.twitter.com/1.1/account/settings.json', '', $options); prr($ret); die();
      //## Make Post
      //$options['attchImg']='1'; $imgURL = 'http://ecx.images-amazon.com/images/I/41caE5Uc5ML._AA160_.jpg';
      $hdrsArr['User-Agent']='Mozilla/5.0 (Windows NT 6.1; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0'; $advSet=array('headers'=>$hdrsArr,'httpversion'=>'1.1','timeout'=>45,'sslverify'=>false);
      //$msg = $message['message']; $imgURL = trim($message['imageURL']); $img = trim($message['img']); $nxs_urlLen = $message['urlLength'];           
      if ($options['attchImg']!=false && $img=='' && $imgURL!='' ) { $imgURL = str_replace(' ', '%20', $imgURL);
        if( ini_get('allow_url_fopen') ) { if (getimagesize($imgURL)!==false) { $img = nxs_remote_get($imgURL, $advSet); if(is_nxs_error($img)) $options['attchImg'] = false; else $img = $img['body']; } else $options['attchImg'] = false; } 
          else { $img = nxs_remote_get($imgURL, $advSet); if(is_nxs_error($img)) $options['attchImg'] = false; elseif (isset($img['body'])&& trim($img['body'])!='') $img = $img['body'];  else $options['attchImg'] = false; }   
      }  
      $twLim = 280; if ($nxs_urlLen>0) { $msg = nsTrnc($msg, $twLim-22+$nxs_urlLen); } else $msg = nsTrnc($msg, $twLim);
      if (substr($msg, 0, 1)=='@') $msg = ' '.$msg;  $params_array = array('status' =>$msg); if (!empty($options['in_reply_to_id'])) $params_array['in_reply_to_status_id'] = $options['in_reply_to_id']; $mid = '';
      if ($options['attchImg']!=false && $img!='') { $pa =array( 'media' => $img); 
        $ret = $this->twReq('https://upload.twitter.com/1.1/media/upload.json', '', $options, $pa); $code = $ret['response']['code']; $resp =  $ret['body'];  $respJ = json_decode($resp, true);
        if ($code=='200') { if (!empty($respJ['media_id'])) $mid = $respJ['media_id_string']; }
      } if (!empty($mid)) $params_array['media_ids'] = $mid; $ret = $this->twReq('https://api.twitter.com/1.1/statuses/update.json', $params_array, $options);  $code = $ret['response']['code']; $resp =  $ret['body'];  $respJ = json_decode($resp, true);
      if ( $code=='403' && stripos($resp, 'User is over daily photo limit')!==false && $options['attchImg']!=false && $img!='') { 
         $badOut['Error'] .= "User is over daily photo limit. Will post without image\r\n"; 
         $ret = $this->twReq('https://api.twitter.com/1.1/statuses/update.json', array('status' =>$msg), $options);  $code = $ret['response']['code']; $resp =  $ret['body'];  $respJ = json_decode($resp, true);
      }        
      if ($code == 200){
         $twResp = json_decode($resp, true);  if (is_array($twResp) && isset($twResp['id_str'])) $twNewPostID = $twResp['id_str'];  
         if (is_array($twResp) && isset($twResp['user'])) $twPageID = $twResp['user']['screen_name'];
         return array('postID'=>$twNewPostID, 'isPosted'=>1, 'postURL'=>'https://twitter.com/'.$twPageID.'/status/'.$twNewPostID, 'pDate'=>date('Y-m-d H:i:s'));          
      } else { $badOut['Error'] .= "Resp: ".print_r($resp, true)."| Error: ".print_r($respJ['errors'], true)."| MSG: ".print_r($msg, true); 
        return $badOut;
      }
      return $badOut;
    }  
    
}}
?>