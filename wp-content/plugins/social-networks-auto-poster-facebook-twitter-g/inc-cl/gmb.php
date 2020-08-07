<?php    
//## NextScripts Facebook Connection Class
$nxs_snapAvNts[] = array('code'=>'GMB', 'lcode'=>'gmb', 'name'=>'Google My Business', 'type'=>'Blogs/Publishing Platforms', 'ptype'=>'P', 'status'=>'A', 'desc'=>'Create Google My Business post');

if (!class_exists("nxs_snapClassGMB")) { class nxs_snapClassGMB extends nxs_snapClassNT {   
  var $ntInfo = array('code'=>'GMB', 'lcode'=>'gmb', 'name'=>'Google My Business', 'defNName'=>'uName', 'tstReq' => false, 'imgAct'=>'E', 'instrURL'=>'https://www.nextscripts.com/instructions/setup-installation-google-my-business-social-networks-auto-poster/');
  var $noFuncMsg = 'Sorry, but Full Official Google My Business API doesn\'t available for general public yet. <br/>You need a special API library module to be able to publish your content to My Business';
  var $defO = array('nName'=>'', 'do'=>'1', 'uName'=>'', 'uPass'=>'', 'postType'=>'A', 'postTo'=>'',  'msgFormat'=>"New post: %TITLE% - %URL%",  'eTtlFormat'=>"%TITLE%", 'eStdate'=>'', 'eEnddate'=>'', 'btnType'=>'', 'ebtnType'=>'');
    
  function checkIfFunc() { return class_exists('nxsAPI_GMB'); }
  //#### Show Common Settings
  function showGenNTSettings($ntOpts){ $this->nt = $ntOpts; $this->showNTGroup(); return; }  
  //#### Show NEW Settings Page
  function showNewNTSettings($ii){ $this->showGNewNTSettings($ii, $this->defO); }
  //#### Show Unit  Settings  
  function checkIfSetupFinished($options) { return !empty($options['uPass']); }
  function accTab($ii, $options, $isNew=false){ if (empty($options['sid'])) $options['sid']=''; if (empty($options['ssid'])) $options['ssid']=''; if (empty($options['nid'])) $options['nid']=''; if (empty($options['hsid'])) $options['hsid']=''; $options = array_merge($this->defO, $options);
    $ntInfo = $this->ntInfo; $nt = $ntInfo['lcode']; ?> <div style="color:darkred; font-size: 16px;">*****[Early Beta] Can make only "What's New" posts<br/><br/></div>
    
    <div id="ups<?php echo $nt.$ii; ?>UP" class="ups<?php echo $nt.$ii; ?>">
      <?php $p = $options['uPass']; $this->elemUserPass($ii, $options['uName'], $p);  ?>
    </div>
    <div id="ups<?php echo $nt.$ii; ?>UPS" style="padding-top: 10px;"><a href="#" onclick="jQuery('#ups<?php echo $nt.$ii; ?>S').show();return false;">Use session</a> (Optional - use only if you are having login problems)</div>
    <div id="ups<?php echo $nt.$ii; ?>S" class="ups<?php echo $nt.$ii; ?>"  style="padding-left: 15px; padding-top: 10px; display:none;">
       SID:&nbsp;&nbsp;<input style="width:400px;" name="<?php echo $nt; ?>[<?php echo $ii; ?>][sid]" style="width: 30%;" value="<?php _e(apply_filters('format_to_edit', htmlentities($options['sid'], ENT_COMPAT, "UTF-8")), 'social-networks-auto-poster-facebook-twitter-g') ?>" /> <br/>
       SSID:&nbsp;<input style="width:400px;" name="<?php echo $nt; ?>[<?php echo $ii; ?>][ssid]" style="width: 30%;" value="<?php _e(apply_filters('format_to_edit', htmlentities($options['ssid'], ENT_COMPAT, "UTF-8")), 'social-networks-auto-poster-facebook-twitter-g') ?>" /> <br/>
       HSID:&nbsp;<input style="width:400px;" name="<?php echo $nt; ?>[<?php echo $ii; ?>][hsid]" style="width: 30%;" value="<?php _e(apply_filters('format_to_edit', htmlentities($options['hsid'], ENT_COMPAT, "UTF-8")), 'social-networks-auto-poster-facebook-twitter-g') ?>" /> <br/>
    </div>
    
    <?php
    //prr($options['postTo'], 'POST TO');  prr($options['postAs'], 'POST AS'); // prr($options);
    if (!empty($p)) { $p = (substr($p, 0, 5)=='n5g9a'||substr($p, 0, 5)=='g9c1a'||substr($p, 0, 5)=='b4d7s')?nsx_doDecode(substr($p, 5)):$p; $options['uPass'] = 'g9c1a'.nsx_doEncode($p); $tPST = (!empty($_POST))?$_POST:''; 
      $_POST['pg'] = $options['postTo']; $_POST['u'] = $options['uName']; $_POST['p'] = $p; $_POST['ii'] = $ii; $ntw[$nt][$ii]=$options;
      $opNm = 'nxs_snap_gmb_'.sha1('nxs_snap_gmb'.$options['uName'].$options['uPass']); $opVal = nxs_getOption($opNm); 
      if (!empty($opVal) & !is_array($opVal)) $options['uMsg'] = $opVal; else { if (!empty($opVal) & is_array($opVal)) $options = array_merge($options, $opVal);
        $opNm = 'nxs_snap_gmb_wh'.sha1('nxs_snap_gmbwh'.$options['uName'].$options['uPass']); $opVal = nxs_getOption($opNm);         
      } $_POST = $tPST;
    } 
    ?>
    
    <br/>
    <div style="width:100%;"><strong><?php _e('Where to Post', 'social-networks-auto-poster-facebook-twitter-g'); ?>:</strong><i><?php _e('Your Google My Business URL', 'social-networks-auto-poster-facebook-twitter-g'); ?></i></div><input name="gmb[<?php echo $ii; ?>][postTo]" style="width: 50%;" value="<?php _e(apply_filters('format_to_edit', htmlentities($options['postTo'], ENT_COMPAT, "UTF-8")), 'social-networks-auto-poster-facebook-twitter-g') ?>" /><br/><br/>    
    
   <div style="width:100%;"><strong id="altFormatText">Post Type:</strong> </div>                      
<div style="margin-left: 10px;">        
        <div id="<?php echo $nt.$ii; ?>PostTypeT" style="margin-left: 5px;">
          <input type="radio" name="gmb[<?php echo $ii; ?>][postType]" value="A" <?php if (empty($options['postType']) || $options['postType'] == 'A') echo 'checked="checked"'; ?> /> <?php _e('What\'s New Post', 'nxs_snap'); ?> - <i><?php _e('Blogpost with link', 'nxs_snap'); ?></i><br/>
          <div id="<?php echo $nt.$ii; ?>PostTypeTSub"  style="margin-left: 15px;">
            <?php _e('Add Button with link', 'social-networks-auto-poster-facebook-twitter-g'); ?>:
            <select id="<?php echo $nt.$ii; ?>PostTypeTSubButSel" onchange="" name="<?php echo $nt; ?>[<?php echo $ii; ?>][btnType]">  <option value="X"><?php _e('No Button', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $options['btnType'] =='BOOK') echo 'selected="selected"'; ?> value="BOOK"><?php _e('Book', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $options['btnType'] =='ORDER') echo 'selected="selected"'; ?> value="ORDER"><?php _e('Order Online', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $options['btnType'] =='SHOP') echo 'selected="selected"'; ?>  value="SHOP"><?php _e('Buy', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $options['btnType'] =='LEARN_MORE') echo 'selected="selected"'; ?>  value="LEARN_MORE"><?php _e('Learn More', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $options['btnType'] =='SIGN_UP') echo 'selected="selected"'; ?>  value="SIGN_UP"><?php _e('Sign Up', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $options['btnType'] =='GET_OFFER') echo 'selected="selected"'; ?>  value="GET_OFFER"><?php _e('Get Offer', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>              
            </select>
          </div>
        </div>                    
        <div id="<?php echo $nt.$ii; ?>PostTypeT" style="margin-left: 5px;">
          <input type="radio" name="gmb[<?php echo $ii; ?>][postType]" value="E" <?php if (!empty($options['postType']) && $options['postType'] == 'E') echo 'checked="checked"'; ?> /> <?php _e('Event', 'nxs_snap'); ?> - <i><?php _e('Event', 'nxs_snap'); ?></i><br/>
          
          
          
          <div id="<?php echo $nt.$ii; ?>PostTypeTSub"  style="margin-left: 15px;"> <?php $this->elemTitleFormat($ii,'Event Title','eTtlFormat',$options['eTtlFormat']);?>
            <?php _e('Add Button with link', 'social-networks-auto-poster-facebook-twitter-g'); ?>:
            <select id="<?php echo $nt.$ii; ?>PostTypeTSubButSel" onchange="" name="<?php echo $nt; ?>[<?php echo $ii; ?>][ebtnType]">  <option value="X"><?php _e('No Button', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $options['ebtnType'] =='BOOK') echo 'selected="selected"'; ?> value="BOOK"><?php _e('Book', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $options['ebtnType'] =='ORDER') echo 'selected="selected"'; ?> value="ORDER"><?php _e('Order Online', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $options['ebtnType'] =='SHOP') echo 'selected="selected"'; ?>  value="SHOP"><?php _e('Buy', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $options['ebtnType'] =='LEARN_MORE') echo 'selected="selected"'; ?>  value="LEARN_MORE"><?php _e('Learn More', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $options['ebtnType'] =='SIGN_UP') echo 'selected="selected"'; ?>  value="SIGN_UP"><?php _e('Sign Up', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>              
            </select>
          </div>
        </div>                    
        Offer and Product are coming soon...
        
   </div><br/>
    
     <?php $this->elemMsgFormat($ii,'Message Format','msgFormat',$options['msgFormat']); 
  }
  function advTab($ii, $options){$this->askForSURL( $this->ntInfo['lcode'], $ii, $options);  $this->showProxies($this->ntInfo['lcode'], $ii, $options); return; ?>
  
   <div style="width:100%;"><strong style="font-size: 16px;"><?php _e('Facebook Comments:', 'social-networks-auto-poster-facebook-twitter-g'); ?></strong> </div>
   <div style="margin-bottom: 5px; margin-left: 10px; ">
   <p style="font-size: 11px; margin: 0px;"><?php _e('Plugin could grab comments from Facebook and import them as Wordpress Comments', 'social-networks-auto-poster-facebook-twitter-g'); ?></p>
   
   <?php global $nxs_SNAP; $gOptions = $nxs_SNAP->nxs_options; if ( !empty($gOptions['riActive']) && $gOptions['riActive'] == '1' ) { ?>
   <input value="1"  id="apFBMsgAFrmtA<?php echo $ii; ?>" <?php if (!empty($options['riComments']) && trim($options['riComments'])=='1') echo "checked"; ?> type="checkbox" name="<?php echo  $this->ntInfo['lcode']; ?>[<?php echo $ii; ?>][riComments]"/> <strong><?php _e('Import Facebook Comments', 'social-networks-auto-poster-facebook-twitter-g'); ?></strong>
   <br/>
   
   <div style="margin-bottom: 5px; margin-left: 10px; ">
     <input value="1"  id="apFBMsgAFrmtA<?php echo $ii; ?>" <?php if (!empty($options['riCommentsAA']) && trim($options['riCommentsAA'])=='1') echo "checked"; ?> type="checkbox" name="<?php echo  $this->ntInfo['lcode']; ?>[<?php echo $ii; ?>][riCommentsAA]"/> <strong><?php _e('Auto-approve imported comments', 'social-networks-auto-poster-facebook-twitter-g'); ?></strong></div>   
     <?php } else { echo "<br/>"; _e('Please activate the "Comments Import" from SNAP Settings Tab', 'social-networks-auto-poster-facebook-twitter-g'); } ?>
   
   </div><?php
  
  }
  //#### Set Unit Settings from POST
  function setNTSettings($post, $options){ 
    foreach ($post as $ii => $pval){       
      if (!empty($pval['uPass']) && !empty($pval['uPass'])){ if (!isset($options[$ii])) $options[$ii] = array(); $options[$ii] = $this->saveCommonNTSettings($pval,$options[$ii]);    
        //if (empty($options[$ii]['pageID'])) { $loginError = $nt->connect($pval['uName'], $pval['uPass']); if (!$loginError){ $nt->getPgsCmns($pval['pageID']); }
        
        if (isset($pval['sid']))  $options[$ii]['sid'] = trim($pval['sid']);                
        if (isset($pval['ssid']))  $options[$ii]['ssid'] = trim($pval['ssid']);             
        if (isset($pval['hsid']))  $options[$ii]['hsid'] = trim($pval['hsid']);                   
        if (isset($pval['nid']))  $options[$ii]['nid'] = trim($pval['nid']);                
        if (isset($pval['apiToUse2']))  $options[$ii]['apiToUse2'] = trim($pval['apiToUse2']);                
        
        if (isset($pval['postTo']))  $options[$ii]['postTo'] = trim($pval['postTo']);                
        
        if (isset($pval['postType']))  $options[$ii]['postType'] = trim($pval['postType']); else $options[$ii]['postType'] = 'A';
        if (isset($pval['btnType']))  $options[$ii]['btnType'] = trim($pval['btnType']); else $options[$ii]['btnType'] = 'X';
        
        
        if (isset($pval['riComments'])) $options[$ii]['riComments'] = $pval['riComments']; else $options[$ii]['riComments'] = 0;        
        if (isset($pval['riCommentsAA'])) $options[$ii]['riCommentsAA'] = $pval['riCommentsAA']; else $options[$ii]['riCommentsAA'] = 0;                
        
      } elseif ( count($pval)==1 ) if (isset($pval['do'])) $options[$ii]['do'] = $pval['do']; else $options[$ii]['do'] = 0; 
    } return $options;
  }
   
  function showEdPostNTSettingsV4($ntOpt, $post){ $post_id = $post->ID; $nt = $this->ntInfo['lcode']; $ntU = $this->ntInfo['code']; $ii = $ntOpt['ii']; $ntOpt = array_merge($this->defO, $ntOpt);
        if (empty($ntOpt['imgToUse'])) $ntOpt['imgToUse'] = ''; if (empty($ntOpt['urlToUse'])) $ntOpt['urlToUse'] = ''; if (empty($ntOpt['postType']) || $ntOpt['postType']=='T') $ntOpt['postType']='A';
        $msgFormat = !empty($ntOpt['msgFormat'])?htmlentities($ntOpt['msgFormat'], ENT_COMPAT, "UTF-8"):''; $msgTFormat = !empty($ntOpt['msgTFormat'])?htmlentities($ntOpt['msgTFormat'], ENT_COMPAT, "UTF-8"):'';
        $imgToUse = $ntOpt['imgToUse'];  $urlToUse = $ntOpt['urlToUse']; if (empty($ntOpt['btnType'])) $ntOpt['btnType'] = 'X';
        
        $this->elemEdMsgFormat($ii, __('Message Format:', 'social-networks-auto-poster-facebook-twitter-g'),$msgFormat);            
        ?>
        
   <div class="nxsPostEd_ElemWrap">   
     <div class="nxsPostEd_ElemLabel"><?php _e('Post Type:', 'social-networks-auto-poster-facebook-twitter-g'); ?></div>   
     <div class="nxsPostEd_Elem">   
        <div id="<?php echo $nt.$ii; ?>PostTypeT" style="margin-left: 5px;">
          <input type="radio" name="gmb[<?php echo $ii; ?>][postType]" value="A" <?php if (empty($ntOpt['postType']) || $ntOpt['postType'] == 'A') echo 'checked="checked"'; ?> class="nxsEdElem" data-ii="<?php echo $ii; ?>" data-nt="<?php echo $nt; ?>" /> <?php _e('What\'s New Post', 'nxs_snap'); ?> - <i><?php _e('Blogpost with link', 'nxs_snap'); ?></i><br/>
          <div id="<?php echo $nt.$ii; ?>PostTypeTSub"  style="margin-left: 15px;">
            <?php _e('Add Button with link', 'social-networks-auto-poster-facebook-twitter-g'); ?>:
            <select class="nxsEdElem" id="<?php echo $nt.$ii; ?>PostTypeTSubButSel" onchange="" name="<?php echo $nt; ?>[<?php echo $ii; ?>][btnType]">  <option value="X"><?php _e('No Button', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $ntOpt['btnType'] =='BOOK') echo 'selected="selected"'; ?> value="BOOK"><?php _e('Book', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $ntOpt['btnType'] =='ORDER') echo 'selected="selected"'; ?> value="ORDER"><?php _e('Order Online', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $ntOpt['btnType'] =='SHOP') echo 'selected="selected"'; ?>  value="SHOP"><?php _e('Buy', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $ntOpt['btnType'] =='LEARN_MORE') echo 'selected="selected"'; ?>  value="LEARN_MORE"><?php _e('Learn More', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $ntOpt['btnType'] =='SIGN_UP') echo 'selected="selected"'; ?>  value="SIGN_UP"><?php _e('Sign Up', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $ntOpt['btnType'] =='GET_OFFER') echo 'selected="selected"'; ?>  value="GET_OFFER"><?php _e('Get Offer', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>              
            </select>
          </div>
        </div>
     
     </div>
     
     <div class="nxsPostEd_Elem">   
        <div id="<?php echo $nt.$ii; ?>PostTypeE" style="margin-left: 5px;">
          <input type="radio" name="gmb[<?php echo $ii; ?>][postType]" value="E" <?php if (!empty($ntOpt['postType']) && $ntOpt['postType'] == 'E') echo 'checked="checked"'; ?> class="nxsEdElem" data-ii="<?php echo $ii; ?>" data-nt="<?php echo $nt; ?>" /> <?php _e('Event', 'nxs_snap'); ?><br/>
          <div id="<?php echo $nt.$ii; ?>PostTypeESub"  style="margin-left: 15px;">
            
            
          Event Title:<input name="<?php echo $nt; ?>[<?php echo $ii; ?>][eTtlFormat]" style="width: 95%;max-width: 610px;" value="<?php echo $ntOpt['eTtlFormat']; ?>" class="nxsEdElem" data-ii="<?php echo $ii; ?>" data-nt="<?php echo $nt; ?>" />  <br/>
          Event Start Date:<input name="<?php echo $nt; ?>[<?php echo $ii; ?>][evStDate]" style="width: 95%;max-width: 610px;" value="<?php echo date("F j, Y, g:i a", strtotime('+10 min'));  ?>" class="nxsEdElem" data-ii="<?php echo $ii; ?>" data-nt="<?php echo $nt; ?>" /><br/>
          Event End Date:<input name="<?php echo $nt; ?>[<?php echo $ii; ?>][evEndDate]" style="width: 95%;max-width: 610px;" value="<?php echo date("F j, Y, g:i a", strtotime('+1 day')); ?>" class="nxsEdElem" data-ii="<?php echo $ii; ?>" data-nt="<?php echo $nt; ?>" /><br/>
            
            
            
            <?php _e('Add Button with link', 'social-networks-auto-poster-facebook-twitter-g'); ?>:
            <select class="nxsEdElem" id="<?php echo $nt.$ii; ?>PostTypeTSubButSel" onchange="" name="<?php echo $nt; ?>[<?php echo $ii; ?>][ebtnType]">  <option value="X"><?php _e('No Button', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $ntOpt['ebtnType'] =='BOOK') echo 'selected="selected"'; ?> value="BOOK"><?php _e('Book', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $ntOpt['ebtnType'] =='ORDER') echo 'selected="selected"'; ?> value="ORDER"><?php _e('Order Online', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $ntOpt['ebtnType'] =='SHOP') echo 'selected="selected"'; ?>  value="SHOP"><?php _e('Buy', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $ntOpt['ebtnType'] =='LEARN_MORE') echo 'selected="selected"'; ?>  value="LEARN_MORE"><?php _e('Learn More', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>
              <option <?php if ( $ntOpt['ebtnType'] =='SIGN_UP') echo 'selected="selected"'; ?>  value="SIGN_UP"><?php _e('Sign Up', 'social-networks-auto-poster-facebook-twitter-g'); ?></option>              
            </select>
          </div>
        </div>
     
     </div>
     
      
     
   </div><?php
        // ## Select Image & URL 
        nxs_showImgToUseDlg($nt, $ii, $imgToUse, !($ntOpt['postType'] == 'I'));            
        nxs_showURLToUseDlg($nt, $ii, $urlToUse); 
  }
  //#### Save Meta Tags to the Post
  function adjMetaOpt($optMt, $pMeta){ $optMt = $this->adjMetaOptG($optMt, $pMeta);     
    if (!empty($pMeta['btnType'])) $optMt['btnType'] = $pMeta['btnType'];       
    if (!empty($pMeta['ebtnType'])) $optMt['ebtnType'] = $pMeta['ebtnType'];       
    if (!empty($pMeta['eTtlFormat'])) $optMt['eTtlFormat'] = $pMeta['eTtlFormat'];       
    if (!empty($pMeta['evStDate'])) $optMt['evStDate'] = $pMeta['evStDate'];       
    if (!empty($pMeta['evEndDate'])) $optMt['evEndDate'] = $pMeta['evEndDate'];       
    return $optMt;
  }
  
  function adjPublishWP(&$options, &$message, $postID){ $addParams = nxs_makeURLParams(array('NTNAME'=>$this->ntInfo['name'], 'NTCODE'=>$this->ntInfo['code'], 'POSTID'=>$postID, 'ACCNAME'=>$options['nName'])); 
      if (!empty($options['eTtlFormat'])) $options['eTtlFormat'] = nsFormatMessage( $options['eTtlFormat'], $postID, $addParams);
  } 

}}


if (!function_exists("nxs_doPublishToGMB")) { function nxs_doPublishToGMB($postID, $options){ if (!is_array($options)) $options = maybe_unserialize(get_post_meta($postID, $options, true));
  ini_set('memory_limit','256M'); $cl = new nxs_snapClassGMB(); $cl->nt[$options['ii']] = $options; return $cl->publishWP($options['ii'], $postID); 
}}  
?>