<?php    
//## NextScripts Google+ Connection Class
$nxs_snapAPINts[] = array('code'=>'GMB', 'lcode'=>'gmb', 'name'=>'Google+');

if (!class_exists("nxs_class_SNAP_GMB")) { class nxs_class_SNAP_GMB {
    
    var $ntCode = 'GMB';
    var $ntLCode = 'gmb';     
    
    function doPost($options, $message){ if (!is_array($options)) return false; $out = array();
      foreach ($options as $ii=>$ntOpts) $out[$ii] = $this->doPostToNT($ntOpts, $message);
      return $out;
    }
    function doPostToNT($options, $message){ $badOut = array('pgID'=>'', 'isPosted'=>0, 'pDate'=>date('Y-m-d H:i:s'), 'Error'=>''); $lnk = ''; // prr($options); prr($message); die();
      //## Check API Lib
      // if (!function_exists('doPostToGooglePlus')) if (file_exists('apis/postToGooglePlus.php')) require_once ('apis/postToGooglePlus.php'); elseif (file_exists('/home/_shared/deSrc.php')) require_once ('/home/_shared/deSrc.php'); 
      if (!class_exists('nxsAPI_GMB')) { $badOut['Error'] = 'Google API Library not found'; return $badOut; }
      //## Check settings
      if (!is_array($options)) { $badOut['Error'] = 'No Options'; return $badOut; }      
      if (!isset($options['uName']) || trim($options['uPass'])=='') { $badOut['Error'] = 'Not Configured'; return $badOut; }
      if (empty($options['imgSize'])) $options['imgSize'] = ''; // prr($options); die();
      //## Make Post      
      $gmbPostType = $options['postType']; $opVal = array(); $opNm = md5('nxs_snap_gmb'.$options['uName'].$options['uPass']); $opVal = nxs_getOption($opNm); if (!empty($opVal) & is_array($opVal)) $options = array_merge($options, $opVal); // prr($opVal);
      if (!empty($message['pText'])) $msg = $message['pText']; else $msg = nxs_doFormatMsg($options['msgFormat'], $message);// prr($msg); die(); // Make "message default"
      // prr($msg); die(); // Make "message default"
      if (isset($message['imageURL'])) $imgURL = trim(nxs_getImgfrOpt($message['imageURL'], $options['imgSize'])); else $imgURL = '';
      $email = $options['uName'];  $pass = (substr($options['uPass'], 0, 5)=='n5g9a' || substr($options['uPass'], 0, 5)=='g9c1a')?nsx_doDecode(substr($options['uPass'], 5)):$options['uPass'];          
      $nt = new nxsAPI_GMB(); if (!empty($options['proxy'])&&!empty($options['proxyOn'])){ $nt->proxy['proxy'] = $options['proxy']['proxy']; if (!empty($options['proxy']['up'])) $nt->proxy['up'] = $options['proxy']['up']; }      $nt->debug = true;
      if (!empty($options['sid'])){ $nt->session = array('ssid'=>$options['ssid'], 'sid'=>$options['sid'], 'hsid'=>$options['hsid']); }      
      if(!empty($options['ck'])) $nt->ck = $options['ck'];  $loginError = $nt->connect($email, $pass); //  die('STOP IT');
      if (!$loginError){ $data = array('postType'=>$gmbPostType);
        //## Whats New Post
        if ($gmbPostType=='A') { $data['url'] = $message['url']; $data['imgURL'] = $imgURL; $data['btnType'] = $options['btnType']; }
        //## Event Post
        if ($gmbPostType=='E') { $data['url'] = $message['url']; $data['imgURL'] = $imgURL; $data['btnType'] = $options['ebtnType']; $data['dtStart'] = $options['evStDate']; $data['dtStop'] = $options['evEndDate'];  
          if (!empty($message['title'])) $data['title'] = $message['title']; else $data['title'] = nxs_doFormatMsg($options['eTtlFormat'], $message);
        }
        //## Offer Post
        if ($gmbPostType=='O') { $data['url'] = $message['url']; $data['imgURL'] = $imgURL; $data['couponCode'] = $options['couponCode']; $data['dtStart'] = $options['dtStart']; $data['dtStop'] = $options['dtStop']; $data['title'] = $options['title']; $data['terms'] = $options['terms'];}
        //## Product Post
        if ($gmbPostType=='P') { $data['url'] = $message['url']; $data['imgURL'] = $imgURL; $data['btnType'] = $options['btnType']; $data['price'] = $options['price'];  $data['price2'] = $options['price2']; $data['title'] = $options['title']; }        
        $result = $nt -> postGMB($options['postTo'], $msg, $data);
      } else {  $badOut['Error'] = "Login/Connection Error: ". print_r($loginError, true); return $badOut; }       
      //if (is_array($result) && $result['isPosted']=='1') nxs_save_glbNtwrks('gmb', $options['ii'], $nt->ck, 'ck');
      if (is_array($result) && $result['isPosted']=='1') { $opVal['ck'] = $nt->ck; nxs_saveOption($opNm,$opVal); }
      return $result;
    }
    
}}
?>