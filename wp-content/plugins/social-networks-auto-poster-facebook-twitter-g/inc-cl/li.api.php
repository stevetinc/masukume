<?php    
//########################################
//##    LI API V2 Combined Edition
//########################################
$nxs_snapAPINts[] = array('code'=>'LI', 'lcode'=>'li', 'name'=>'LinkedIn');

if (!class_exists("nxs_class_SNAP_LI")) { class nxs_class_SNAP_LI {
    
    var $ntCode = 'LI';
    var $ntLCode = 'li';     
                                                                                      
    function postShare($tkn, $usr, $msg, $pgID='', $title='', $url='', $imgURL='', $dsc='') { 
      $dsc =  nxs_decodeEntitiesFull(strip_tags($dsc));  $msg = strip_tags(nxs_decodeEntitiesFull($msg));  $title =  nxs_decodeEntitiesFull(strip_tags($title)); 
      $toPost = array('lifecycleState'=>'PUBLISHED', 'specificContent'=>array('com.linkedin.ugc.ShareContent'=> array('shareCommentary'=> array('text'=> htmlspecialchars($msg, ENT_NOQUOTES, "UTF-8")), "shareMediaCategory"=> "NONE")), 
        'visibility'=>array('com.linkedin.ugc.MemberNetworkVisibility'=>'PUBLIC') 
      ); $toPost['author'] = !empty($pgID)?'urn:li:organization:'.$pgID:'urn:li:person:'.$usr;
      if (!empty($url)) { $toPost['specificContent']['com.linkedin.ugc.ShareContent']['media'] = array(array('status'=>'READY', 'originalUrl'=>$url, 'txitle'=>array('text'=>htmlspecialchars($title, ENT_NOQUOTES, "UTF-8")), 'description'=>array('text'=> htmlspecialchars($dsc, ENT_NOQUOTES, "UTF-8"))));
           $toPost['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
      }
      //if (!empty($imgURL)) $toPost['content']['submitted-image-url'] = $imgURL;    //   prr($toPost);
      $nURL = 'https://api.linkedin.com/v2/ugcPosts';  $hdrsArr=nxs_getNXSHeaders(); $hdrsArr['Authorization'] = 'Bearer '.$tkn; $hdrsArr['X-RestLi-Protocol-Version']='2.0.0';  $hdrsArr['Content-Type']='application/json'; $hdrsArr['x-li-format']='json'; $toPost = json_encode($toPost);       
      $advSet = nxs_mkRemOptsArr($hdrsArr, '', $toPost); $response  = nxs_remote_post($nURL, $advSet); if (is_nxs_error($response) || empty($response['body'])) return "ERROR: ".print_r($response, true); //     prr($response);
      $post = json_decode($response['body'], true); return $post; 
    }
    
    function doPost($options, $message){ if (!is_array($options)) return false; $out = array();
      foreach ($options as $ii=>$ntOpts) $out[$ii] = $this->doPostToNT($ntOpts, $message);
      return $out;
    }        
    function doPostToNT($options, $message){ $badOut = array('postID'=>'', 'isPosted'=>0, 'pDate'=>date('Y-m-d H:i:s'), 'Error'=>''); $liPostID = ''; // prr($message); prr($options);

      //## Check settings
      if (!is_array($options)) { $badOut['Error'] = 'No Options'; return $badOut; }      
      if ((!isset($options['uName']) || empty($options['uPass'])) && (empty($options['accessToken'])))  { $badOut['Error'] = 'Not Configured'; return $badOut; }                  
      if (empty($options['imgSize'])) $options['imgSize'] = ''; if (empty($options['msgTFormat'])) $options['msgTFormat'] = '%TITLE%'; if (empty($options['msgATFormat'])) $options['msgATFormat'] = '%TITLE%'; 
      //## Format
      if (!empty($message['pText'])) $msg = $message['pText']; else $msg = nxs_doFormatMsg($options['msgFormat'], $message); 
      if (!empty($message['pTitle'])) $msgT = $message['pTitle']; else $msgT = nxs_doFormatMsg($options['msgTFormat'], $message);               
      
      if (!empty($message['urlTitle'])) $msgAT = $message['urlTitle']; else $msgAT = nxs_doFormatMsg($options['msgATFormat'], $message);               
      if (!empty($message['urlDescr'])) $msgA = $message['urlDescr']; else $msgA = nxs_doFormatMsg($options['msgAFormat'], $message);               
      
      if ($options['whToPost']=='P') { $title = nxs_doFormatMsg($options['msgCTFormat'], $message); $html = nxs_doFormatMsg($options['msgCFormat'], $message); }
      if(empty($options['postType'])) $options['postType'] = 'A';
      
      if ( $options['postType'] == 'A' || $options['postType'] == 'I') { 
        if (isset($message['imageURL'])) $imgURL = trim(nxs_getImgfrOpt($message['imageURL'], $options['imgSize'])); else $imgURL = '';  if (preg_match("/noImg.\.png/i", $imgURL)) $imgURL = '';           
        if (empty($msgA)) $msgA = $msg;     if (!empty($msgAT)) $msgAT = nxs_html_to_utf8($msgAT);     
        $msgA = strip_tags($msgA); $msgA = nxs_decodeEntitiesFull($msgA);  if (!empty($msgA)) $msgA = nxs_html_to_utf8($msgA);  $msgA = nsTrnc($msgA, 300);        
      }        
      $msg  = strip_tags($msg); if (!empty($msg)) $msg = nxs_html_to_utf8($msg);  if (!empty($msgT)) $msgT = nxs_html_to_utf8($msgT); $urlToGo = $message['url'];
      
      if (class_exists('nxsAPI_LI') && $options['uName']!='' && $options['uPass']!='') {
        //## Get Saved Login Info
        if (function_exists('nxs_getOption')) { $opVal = array(); $opNm = 'nxs_snap_li_'.sha1('nxs_snap_li'.$options['uName'].$options['uPass']); $opVal = nxs_getOption($opNm); if (!empty($opVal) & is_array($opVal)) $options = array_merge($options, $opVal); } //  prr($opVal);
        $uname = $options['uName']; $pass = (substr($options['uPass'], 0, 5)=='n5g9a'||substr($options['uPass'], 0, 5)=='g9c1a'||substr($options['uPass'], 0, 5)=='b4d7s')?nsx_doDecode(substr($options['uPass'], 5)):$options['uPass'];       
        $ck = !empty($options['ck'])?maybe_unserialize($options['ck']):''; if (!empty($ck)) $ck = nxsClnCookies($ck);
        $nt = new nxsAPI_LI(); if (!empty($ck)) $nt->ck = $ck; if (!empty($message['session']) || !empty($options['session'])) { $nt->sid = !empty($message['session'])?$message['session']:$options['session']; } 
        if (!empty($options['proxy'])&&!empty($options['proxyOn'])){ $nt->proxy['proxy'] = $options['proxy']['proxy']; if (!empty($options['proxy']['up'])) $nt->proxy['up'] = $options['proxy']['up']; }
        $loginErr = $nt->connect($uname, $pass); 
        //## LinkedIn Email Code Verification.
        if (is_array($loginErr) && !empty($loginErr['out']) ) { if (function_exists('update_option')) update_option('nxs_li_ctp_save', $loginErr['ser'], false); $text = $loginErr['out'];
          echo "#2. LinkedIn asked you to enter verification code. Please check your email or phone, enter the code and click \"Confirm Code\""; $text = str_ireplace('This login attempt seems suspicious. ', '', $text);  echo $text;                
          echo '<br/><input type="hidden" id="nxsLiNum" name="nxsLiNum" value="'.$options['ii'].'" /><input type="button" value="Click to Confirm Code" onclick="doCtpSave(); return false;" id="results_ok_button" name="nxs_go" class="button" /><br/><br/><div style="color:red;" id="nxsLITestResults"></div>';
          ?><script type="text/javascript"> function doCtpSave(){ var u = jQuery('#verification-code').val(); var ii = jQuery('#nxsLiNum').val(); //alert(ii);                               
            jQuery.post(ajaxurl,{s:u, i:ii, action: 'nxs_snap_aj',"nxsact":"getItFromNT", "fName":"nxsCptCheck", nt:"LI", id: 0, _wpnonce: jQuery('input#nxsSsPageWPN_wpnonce').val()}, function(j){
              jQuery('#nxsLITestResults').html(j);
            }, "html")
          }</script> <?php return '<br/>#2. LinkedIn asked you to enter verification code<br/>';
        }        
        if ($loginErr) { $badOut['Error'] .= 'Can\'t Connect - '.print_r($loginErr, true); return $badOut; }         
        $opVal['ck'] = $nt->ck; nxs_saveOption($opNm,$opVal); //$options['ck'] = $nt->ck; if (function_exists('nxs_save_glbNtwrks')) nxs_save_glbNtwrks('li', $options['ii'], $nt->ck, 'ck');         
        if ( $options['whToPost']=='C' && !empty($options['pgcID'])) $to = 'https://www.linkedin.com/company/'.$options['pgcID']; elseif ( $options['whToPost']=='G' && !empty($options['pggID'])) $to = 'https://www.linkedin.com/groups/'.$options['pggID'];
          elseif ( $options['whToPost']=='PR' ) $to = 'https://www.linkedin.com/home';
        
        $lnk = array(); $msg = str_ireplace('&nbsp;',' ',$msg);  $msg = nsTrnc(strip_tags($msg), 700); $lnk['postTitle'] = $msgT; 
        if ($options['postType'] == 'A'){ $lnk['title']=$message['urlTitle']; $lnk['desc'] =  $message['urlDescr']; $lnk['url'] = $urlToGo; $lnk['img'] = $imgURL; $lnk['postType'] = 'A';}
        if ($options['postType'] == 'I'){ $lnk['title'] = '';  $lnk['desc']=''; $lnk['url'] = $imgURL; $lnk['img'] = $imgURL; $lnk['postType'] = 'I'; $lnk['postTitle'] = $msgT;}             
        if ($options['postType'] == 'T'){ $lnk['postType'] = 'T'; } // prr($msg); prr($lnk); prr($to);
        if ($options['whToPost']=='P') $ret = $nt->postToPulse($msg, $title, $html, $imgURL); else $ret = $nt->post($msg, $lnk, $to); if (is_array($ret) && !empty($ret['isPosted'])) return $ret; $liPostID = $to;
      } else { 
        if (!empty($options['apiToUse']) && ($options['apiToUse']=='liv2' || $options['apiToUse']=='liv1')) { if (empty($options['pgID'])||$options['pgID']=='p') $options['pgID'] = ''; //## V2          
          if($options['postType'] == 'A') $ret = $this->postShare($options['accessToken'], $options['liUserID'],$msg, $options['pgID'], nsTrnc($msgAT, 200), $urlToGo, $imgURL, $msgA); else $ret = $this->postShare($options['accessToken'], $options['liUserID'],$msg, $options['pgID']);            
        }        
        if ($liPostID=='') $liPostID = $options['liUserInfo'];        
      }  //prr($ret);
      if (!is_array($ret) && stripos($ret, '<update-url>')!==false) { $rurl = CutFromTo($ret,'<update-url>','</update-url>'); $ret = array('updateUrl'=>$rurl); }      
      if (is_array($ret) && !empty($ret['id'])) return array('isPosted'=>'1', 'postID'=>$ret['id'], 'postURL'=>'https://www.linkedin.com/feed/update/'.$ret['id'], 'pDate'=>date('Y-m-d H:i:s'));    
      if (is_array($ret) && !empty($ret['updateUrl'])) { if (stripos($ret['updateUrl'], 'topic=')!==false) $liPostID = CutFromTo($ret['updateUrl'], 'topic=','&'); else $liPostID = ''; 
        return array('isPosted'=>'1', 'postID'=>$liPostID, 'postURL'=>$ret['updateUrl'], 'pDate'=>date('Y-m-d H:i:s'));  
      } else  { $badOut['Error'] .= print_r($ret, true); }
      return $badOut;     
   }    
}}
?>