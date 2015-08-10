<?php

global $current_user;
get_currentuserinfo();
				
### If Form Is Submitted
if(isset($_POST['saveChanges'])) {
	
	if ( get_magic_quotes_gpc() ) {
		$_POST      = array_map( 'stripslashes_deep', $_POST );
		$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );
	}
	
	// Update options After Validating
	$rp_send_email=isset($_POST['rp_send_email']) && is_numeric($_POST['rp_send_email'])?$_POST['rp_send_email']:0;
	$rp_email_address=isset($_POST['rp_email_address'])?$_POST['rp_email_address']:get_option('admin_email');
	$rp_display_text=isset($_POST['rp_display_text'])?$_POST['rp_display_text']:'[!] Report This Post';
	$rp_thanks_msg=isset($_POST['rp_thanks_msg'])?$_POST['rp_thanks_msg']:'<strong>Thanks for Reporting [TITLE]</strong>';
	$report_options=isset($_POST['reportoptions'])?$_POST['reportoptions']:'Report contents';
	$report_options=str_replace("\n","|",$report_options);
	$report_options=str_replace("\r","",$report_options);
	$report_options=str_replace("\t","",$report_options);
	$report_if=(int)isset($_POST['report_if'])?$_POST['report_if']:1;
	
	$rp_registeronly=(int)isset($_POST['registeronly'])?$_POST['registeronly']:0;
	$rp_page=(int)isset($_POST['is_page'])?$_POST['is_page']:0;
	$rp_categories=isset($_POST['post_category'])?$_POST['post_category']:array();
	$rp_categories = implode(",",$rp_categories);
	
	// Update
	$update_query=array();
	$update_text=array();
	
	$update_query[]=update_option("rp_send_email",$rp_send_email);
	$update_query[]=update_option("rp_email_address",$rp_email_address);
	$update_query[]=update_option("rp_display_text",$rp_display_text);
	$update_query[]=update_option("rp_thanks_msg",$rp_thanks_msg);
	$update_query[]=update_option("rp_options",$report_options);
	$update_query[]=update_option("rp_if",$report_if);
	
	$update_query[]=update_option("rp_registeronly",$rp_registeronly);
	$update_query[]=update_option("rp_page",$rp_page);
	$update_query[]=update_option("rp_categories",$rp_categories);
	
	$update_text[]=__("Sending Email Option","pp-reportpost");
	$update_text[]=__("Sending Email Address","pp-reportpost");
	$update_text[]=__("Link Text","pp-reportpost");
	$update_text[]=__("Thank you Message","pp-reportpost");
	$update_text[]=__("Report Options","pp-reportpost");
	$update_text[]=__("Attach Option","pp-reportpost");
	
	$update_text[]=__("Register users Only","pp-reportpost");
	$update_text[]=__("Display on Pages","pp-reportpost");
	$update_text[]=__("Limited Categories","pp-reportpost");
	
	$i = 0;
	$text = '';
	foreach($update_query as $u_query) {
		if($u_query) {
			$text .= '<font color="green">'.$update_text[$i].' '.__('Updated', 'pp-reportpost').'</font><br />';
		}
		$i++;
	}
	if(empty($text)) {
		$text = '<font color="red">'.__('No Option Updated', 'pp-reportpost').'</font>';
	}
	
	
} // End IF


### Needed Variables
$rp_send_email=intval(get_option("rp_send_email"));
$rp_email_address=get_option("rp_email_address");
$rp_display_text=get_option("rp_display_text");
$rp_thanks_msg=get_option("rp_thanks_msg");
$report_options=get_option("rp_options");
$report_if=(int)get_option("rp_if");

$rp_registeronly=(int)get_option("rp_registeronly");
$rp_page=(int)get_option("rp_page");
$rp_categories=get_option("rp_categories");

if(!$report_if || !is_numeric($report_if) || $report_if < 0 || $report_if > 3)
	$report_if = 1 ;

if(!$rp_categories || empty($rp_categories))
{
	$rp_categories = array();
}else{
	$rp_categories = explode(",",$rp_categories); // Convert to Array
}

/* CHECK FOR UPGRADE */
global $wpdb;

$old_table = $wpdb->prefix . "reportpost";
$upgrade_required = false;

// IF UPGRADE REQUESTED
if(isset($_POST['upgrade']))
{
	if ( get_magic_quotes_gpc() ) {
		$_POST      = array_map( 'stripslashes_deep', $_POST );
		$_REQUEST   = array_map( 'stripslashes_deep', $_REQUEST );
	}
	// Get current user
	
	// Get All OLD DATA
	global $wpdb;
	$data = $wpdb->get_results($wpdb->prepare("SELECT * FROM $old_table"), OBJECT);
	
	// Call Class
	include_once('ReportPost.class.php');
	$wprp = new ReportPost($wpdb);
	
	// Disable EMAIL FOR NOW
	$email_opt = get_option("rp_send_email");
	update_option("rp_send_email", "0");
	
	if($data != NULL && is_array($data) && count($data) > 0)
	foreach($data as $report)
	{
		// Split Data
		$comments = $report->description;
		
		$comments = split("<br />", $comments); # Split Different Contents
		
		foreach($comments as $comment)
		{
			$comment_array = split(":", $comment, 2); # GETS IP
			
			$IP = $comment_array[0];
			$IP = str_replace(array("[", "]"),"",trim($IP));
		
			$comment_array = split('\|', $comment_array[1], 2); # GETS Type & Actual Comment
			
			// Now we Start to INSERT into NEW
			$wprp->add($report->post_id, $comment_array[0], $comment_array[1], $report->stamp, $IP);
		}// comments
		
		// Update Archive Status
		if($report->status =="1" && $wprp->insert_id > 0)
			$wprp->archive($wprp->insert_id, $current_user->ID, "Converted during Upgrade");
		
		$wprp->insert_id = 0; // Just to be SAFE!
	}
	
	// Delete OLD TABLE
	$wpdb->query($wpdb->prepare("DROP TABLE $old_table"));
	
	// Restore Email Option
	update_option("rp_send_email", $email_opt);
	
	$text = '<font color="green"> * successfully updated</font>';
}

// Check IS require to UPGRADE
if($wpdb->get_var("SHOW TABLES LIKE '$old_table'") == $old_table) // FOUND YA!
	$upgrade_required = true;
?>

<div class="wrap"> 
	<h2><?php _e('Settings', 'pp-reportpost'); ?></h2>
	
	<?php if(!empty($text)) { echo '<!-- Last Action --><div id="message" class="updated fade"><p>'.$text.'</p></div>'; } ?>
	    
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" enctype="multipart/form-data">
	    
	<?php wp_nonce_field('update-options'); ?>
    
	<div style="background-color:#FFF; border:1px dotted #CCC; padding:10px;">
    
    <table class="form-table" border="0">
    	<tr>
    	  <th>Users Only:</th>
    	  <td><input type="checkbox" name="registeronly" value="1" <?php if($rp_registeronly > 0) echo ' checked="checked"'; ?> /> Tick this box, If you want to Limit to Logged-in users only can report.</td>
  	  </tr>
        <tr>
        	<th style="border-top:1px dotted #CCC;">Report Post Link text</th>
        	<td style="border-top:1px dotted #CCC;"><textarea name="rp_display_text" style="width:70%;" cols="30" rows="5"><?php echo $rp_display_text; ?></textarea><br /><small>You can use HTML</small></td>
        </tr>
        
        <tr>
        	<th style="border-top:1px dotted #CCC;">Options</th>
        	<td style="border-top:1px dotted #CCC;"><textarea name="reportoptions" cols="30" rows="5" style="width:70%;"><?php echo str_replace("|","\n",$report_options); ?></textarea><br /><small>* One Per Line</small></td>
        </tr>
        <tr>
        	<th style="border-top:1px dotted #CCC;">Thank you Message</th>
        	<td style="border-top:1px dotted #CCC;"><textarea name="rp_thanks_msg" cols="30" rows="5" style="width:70%;"><?php echo $rp_thanks_msg; ?></textarea><br /><small>* [TITLE] will be Replaced with Original Title on reporting. you can use HTML</small></td>
        </tr>
        
        <tr>
        	<th style="border-top:1px dotted #CCC;">Send Email: </th>
        	<td style="border-top:1px dotted #CCC;">
            	<input type="checkbox" name="rp_send_email" value="1" <?php if($rp_send_email==1){ echo 'checked="checked"';}?>/> to: <input type="text" name="rp_email_address" value="<?php echo $rp_email_address; ?>" /><br />
                <small>* Only One Email Will be Send Per POST</small>
            </td>
        </tr>
        <tr>
        	<td colspan="2" style="border-top:1px dotted #CCC; font-size:1px;">&nbsp;</td>
        </tr>
        
    </table>
	<p class="submit" style="text-align:right">
		<input type="submit" name="saveChanges" value="<?php _e('Save Changes') ?>" />
	</p>
    </div>
    
	</form>
    
</div>
<?php
/* HACK to wp_category_checklist*/
if(!function_exists("wp_category_checklist"))
{
function wp_category_checklist( $post_id = 0, $descendants_and_self = 0, $selected_cats = false, $popular_cats = false, $walker = null ) {
	if ( empty($walker) || !is_a($walker, 'Walker') )
		$walker = new Walker_Category_Checklist;

	$descendants_and_self = (int) $descendants_and_self;

	$args = array();

	if ( is_array( $selected_cats ) )
		$args['selected_cats'] = $selected_cats;
	elseif ( $post_id )
		$args['selected_cats'] = wp_get_post_categories($post_id);
	else
		$args['selected_cats'] = array();

	if ( is_array( $popular_cats ) )
		$args['popular_cats'] = $popular_cats;
	else
		$args['popular_cats'] = get_terms( 'category', array( 'fields' => 'ids', 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );

	if ( $descendants_and_self ) {
		$categories = get_categories( "child_of=$descendants_and_self&hierarchical=0&hide_empty=0" );
		$self = get_category( $descendants_and_self );
		array_unshift( $categories, $self );
	} else {
		$categories = get_categories('get=all');
	}

	// Post process $categories rather than adding an exclude to the get_terms() query to keep the query the same across all posts (for any query cache)
	$checked_categories = array();
	$keys = array_keys( $categories );

	foreach( $keys as $k ) {
		if ( in_array( $categories[$k]->term_id, $args['selected_cats'] ) ) {
			$checked_categories[] = $categories[$k];
			unset( $categories[$k] );
		}
	}

	// Put checked cats on top
	echo call_user_func_array(array(&$walker, 'walk'), array($checked_categories, 0, $args));
	// Then the rest of them
	echo call_user_func_array(array(&$walker, 'walk'), array($categories, 0, $args));
}
}
?>