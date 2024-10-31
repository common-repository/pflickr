<?php
/*
Plugin Name: Photo of the Day - flickr widget
Plugin URI: http://www.blusb.eu/blog/2006/11/22/pflickr-v11/
Description: Adds a sidebar widget to display photo of the day images from flickr photo stream
Author: Christos
Version: 1.1
Author URI: http://www.blusb.eu
*/

/*
 * $HeadURL: https://plugins.svn.wordpress.org/pflickr/trunk/pflickr.php $
 * $Date: 2006-12-15 08:15:53 -0600 (Fri, 15 Dec 2006) $
 * $Author: blusb $
 * $Rev: 7096 $
 * $Id: pflickr.php 7096 2006-12-15 14:15:53Z blusb $
 */

/*
 * NOTE: if you update your photostream during the changing interval the random number
 * generator is using different range and hence the widget will return a differnt image(s)
 */


// This gets called at the plugins_loaded action

require_once("phpFlickr/phpFlickr.php");
function pflickr($fkey, $username, $count, $psize, $pfreq, $c)
{

	$f = new phpFlickr($fkey);

	if ($c) {
		$dbstr="mysql://".DB_USER.":".DB_PASSWORD."@".DB_HOST."/".DB_NAME; 
		$expire = 216000; // Keep cache contents for 1day=216000sec
		$f->enableCache("db", $dbstr, $expire, "wp_pflickr_cache");
	}



    // Find the NSID of the username inputted via the form
    $person = $f->people_findByUsername($username);
    
    // Get the friendly URL of the user's photos
    $photos_url = $f->urls_getUserPhotos($person['id']);
    
    // Read the total number of photos;
    $photos = $f->people_getPublicPhotos($person['id'], NULL, 1, 1);

	$psizes=array("Square", "thumbnail", "small", "medium", "large", "original");
	$pfreq_strings=array("U", "iHmdY", "HdmY", "dmY", "wY", "mY", "Y");
	
	srand((float)date($pfreq_strings[$pfreq]));

  for ($i=0; $i<$count; $i++)
  {
	// Select 1 at random
    $myimg=rand(0,$photos[total]);

	// Now read this photo;
    $photos = $f->people_getPublicPhotos($person['id'], NULL, 1, $myimg);
    
// 		  $out = $out . "<li>\n";
        $out = $out . "\t<a href=\"$photos_url".$photos[photo][0][id]."\" title=\"".$photos[photo][0][title]."\">\n";
        $out = $out . "\t<img class=\"randimg\" alt=\"".$photos[photo][0][title]."\" \n";
        
        $out = $out . "\t\tsrc=\"" . $f->buildPhotoURL($photos[photo][0], $psizes[$psize]) . "\"/></a><br/><br/>\n";
//  		  $out = $out . "</li>\n";

  }
        return $out;
}

function widget_pflickr_init() {
	
	// Check for the required API functions
	if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') )
		return;

	// This saves options and prints the widget's config form.
	function widget_pflickr_control() {
		$options = $newoptions = get_option('widget_pflickr');
		if ( $_POST['pflickr-submit'] ) {
			$newoptions['title'] = strip_tags(stripslashes($_POST['pflickr-title']));
			$newoptions['fkey'] = strip_tags(stripslashes($_POST['pflickr-key']));
			$newoptions['username'] = strip_tags(stripslashes($_POST['pflickr-username']));
			$newoptions['count'] = (int) $_POST['pflickr-count'];
			$newoptions['psize'] = strip_tags(stripslashes($_POST['pflickr-size']));
			$newoptions['pfreq'] = strip_tags(stripslashes($_POST['pflickr-freq']));
			$newoptions['pcache'] = isset($_POST['pflickr-cache']);
		}
		if ( $options != $newoptions ) {
			$options = $newoptions;
			update_option('widget_pflickr', $options);
		}
	?>
				<div style="text-align:right;">
				
				<label for="pflickr-title" style="line-height:20px;display:block;"><?php _e('Widget title:', 'widgets'); ?> <input type="text" id="pflickr-title" name="pflickr-title" value="<?php echo wp_specialchars($options['title'], true); ?>" /></label>
				<label for="pflickr-key" style="line-height:20px;display:block;"><?php _e('Flickr API Key:', 'widgets'); ?> <input type="text" id="pflickr-key" name="pflickr-key" value="<?php echo wp_specialchars($options['fkey'], true); ?>" /></label>
				<label for="pflickr-username" style="line-height:20px;display:block;"><?php _e('flickr username:', 'widgets'); ?> <input type="text" id="pflickr-username" name="pflickr-username" value="<?php echo wp_specialchars($options['username'], true); ?>" /></label>
				<label for="pflickr-count" style="line-height:20px;display:block;"><?php _e('Number of photos:', 'widgets'); ?> <input type="text" id="pflickr-count" name="pflickr-count" value="<?php echo $options['count']; ?>" /></label>
      		<label for="pflickr-size" style="line-height:20px;display:block;"><?php _e('Photo Size', 'widgets'); ?> 
      		<select id="pflickr-size" name="pflickr-size">
	      		<option value="0" <?php if ($options['psize']=='0') echo "selected='selected'"?>>Square</option>
	      		<option value="1" <?php if ($options['psize']=='1') echo "selected='selected'"?>>Thumbnail</option>
	      		<option value="2" <?php if ($options['psize']=='2') echo "selected='selected'"?>>Small</option>
	      		<option value="3" <?php if ($options['psize']=='3') echo "selected='selected'"?>>Medium</option>
	      		<option value="4" <?php if ($options['psize']=='4') echo "selected='selected'"?>>Large</option>
			<option value="5" <?php if ($options['psize']=='5') echo "selected='selected'"?>>Original</option>
	      	</select>
				</label>
		<label for="pflickr-freq" style="line-height:35px;display:block;"><?php _e('Change every', 'widgets'); ?>
		 <select id="pflickr-freq" name="pflickr-freq">
			<option value="0" <?php if ($options['pfreq']=='0') echo "selected='selected'"?>>Second</option>
			<option value="1" <?php if ($options['pfreq']=='1') echo "selected='selected'"?>>Minute</option>
			<option value="2" <?php if ($options['pfreq']=='2') echo "selected='selected'"?>>Hour</option>
			<option value="3" <?php if ($options['pfreq']=='3') echo "selected='selected'"?>>Day</option>
			<option value="4" <?php if ($options['pfreq']=='4') echo "selected='selected'"?>>Week</option>
			<option value="5" <?php if ($options['pfreq']=='5') echo "selected='selected'"?>>Month</option>
			<option value="6" <?php if ($options['pfreq']=='6') echo "selected='selected'"?>>Year</option>
		 </select>
		</label>
		<label for="pflickr-cache" style="line-height:20px;display:block;"><?php _e('Enable Caching ', 'widgets'); ?>
		<input class="checkbox" type="checkbox" <?php echo $options['pcache'] ? 'checked="checked"' : ''; ?> id="pflickr-cache" name="pflickr-cache" />
		</lablel>


	<input type="hidden" name="pflickr-submit" id="pflickr-submit" value="1" />
			</div>
	<?php
	}

	// This prints the widget
	function widget_pflickr($args) {
		extract($args);
		$defaults = array('fkey'=>'Paste Key Here', 'count' => 10, 'psize'=> 1, 'pfreq'=>2, 'pcache'=>1, 'username' => 'westgla');
		$options = (array) get_option('widget_pflickr');

		foreach ( $defaults as $key => $value )
			if ( !isset($options[$key]) )
				$options[$key] = $defaults[$key];
		
		?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . "<a href='http://www.flickr.com/photos/{$options['username']}'>{$options['title']}</a>" . $after_title; ?>
		<div id="pflickr-box" style="margin:0;padding:0;border:none; text-align: center">
				<?php echo pflickr($options[fkey], $options[username], $options[count], $options['psize'], $options['pfreq'], $options['pcache']);
			    ?>
			    <small><a href="http://www.blusb.eu/blog/2006/11/22/pflickr-v11/">Widget</a> by <a href="http://www.blusb.eu/">Christos</a></small>
			
			</div>
			
   		<?php echo $after_widget; ?>
<?php
	}

	// Tell Dynamic Sidebar about our new widget and its control
	register_sidebar_widget(array('pflickr', 'widgets'), 'widget_pflickr');
	register_widget_control(array('pflickr', 'widgets'), 'widget_pflickr_control');
	
}

// Delay plugin execution to ensure Dynamic Sidebar has a chance to load first
add_action('widgets_init', 'widget_pflickr_init');

?>
