<?php
/*
Plugin Name: RTLer
Description: This plugin generates the RTL stylesheet for you from your theme's 'style.css' file.
Author: Louy Alakkad
Version: 1.6
Author URI: http://louyblog.wordpress.com/
Plugin URL: http://l0uy.wordpress.com/tag/rtler/
Text Domain: rtler
Domain Path: /languages
*/
/**
 * init RTLer by adding our page to the 'Tools' menu.
 */
function rtler_init() {
	add_submenu_page( 'tools.php', 'RTLer', 'RTLer', 'edit_themes', 'rtler', 'rtler_page' );
}
add_action( 'admin_menu', 'rtler_init' );

// Load translations
load_plugin_textdomain( 'rtler', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

/**
 * display the RTLer tool page.
 */
function rtler_page() {
	
	// theme, file and save fields values
	$theme = '';
	$file = 'style.css';
	$save = '';
	
	// get themes list
	$themes = array_merge(array(''=>array('data'=>'')),get_allowed_themes());
	
	// textareas values
	$rtled = '';
	$tortl = '';
	
	// check for submitted filename
	if( isset( $_GET['file'] ) && $_GET['file'] ) {
		$file = $_GET['file'];
		$file = str_replace('\\','/',$file); // sanitize for Win32 installs
		$file = preg_replace('|\.\./|','/', $file); // remove any dir up string
		$file = preg_replace('|/+|','/', $file); // remove any duplicate slash
	}
	
	// check save option
	if( isset( $_GET['save'] ) && $_GET['save'] == 'true' ) {
		$save = true;
	}
	
	// check for submitted theme
	if( isset( $_GET['theme'] ) && !empty($_GET['theme']) ) {
		
		$theme = trim($_GET['theme']);
		
		// if we don't have a file name, use style.css
		if( empty( $file ) ) {
			$file = 'style.css';
		}
		
		// theme directory
		$dir = WP_CONTENT_DIR . '/themes/' . $theme . '/';
		
		// file path
		$path = dirname($dir.$file).'/'.basename($file);
		
		$path = str_replace('\\','/',$path); // sanitize for Win32 installs
		$path = preg_replace('|/+|','/', $path); // remove any duplicate slash
		
		// check if it's a css file
		if ( '.css' == substr( $file, strrpos( $file, '.' ) ) ) {
			
			if( is_file( $path ) ) { // check for file existance
				
				// read the file
				$f = fopen($path, 'r');
				$c = fread($f, filesize($path));
				fclose($f);
				
				$tortl = $c;
				
				// create RTL object
				$RTLer = new RTLer;
				
				// do our job! LOL
				$rtled = $RTLer->parse_css($c);
				
				if( $rtled ) {
					
					// now, save.
					if( $save ) {
						
						$error = false;
						
						$_file = preg_match( '/^(.*\\/)?style\.css$/', $path ) ? substr($path, 0, -9) . 'rtl.css' : substr($path, 0, -4) . '-rtl.css';
						
						if( is_file( $_file ) ) {
							
							// file exists so rename it.
							$__file = substr( $_file, 0, -4 ) . '.bak.css';
							$__file_b = substr( $_file, 0, -4 ) . '.bak-%%.css';
							
							$n = 0;
							while( is_file( $__file ) ) {
								$__file = str_replace( '%%', $n, $__file_b );
								$n ++;
							}
							
							unset( $n );
							
							rename($_file, $__file) or $error = true;
							
							if( $error )
								echo '<div id="message" class="error fade"><p><strong>'.sprintf(__('Error renaming <code>%s</code>, please edit manually.', 'rtler'),esc_html($_file)).'</strong></p></div>';
						}
						
						if( !$error ) {
							
							$fp = fopen($_file, 'w');
							
							if( $fp ) {
								
								// write new file
								fwrite( $fp, $rtled, strlen( $rtled ) );
								fclose( $fp );
								
								echo '<div id="message" class="updated fade"><p><strong>'.sprintf(__('File %s saved successfuly.', 'rtler'),esc_html($_file)).'</strong></p></div>';
								
							} else {
								$error = true;
								echo '<div id="message" class="error fade"><p><strong>'.sprintf(__('Error saving <code>%s</code>, file not writable.', 'rtler'),esc_html($_file)).'</strong></p></div>';
							}
						}
						
					}
					
				} else {
					// No need to rtl
					echo '<div id="message" class="updated fade"><p><strong>'.sprintf(__('File %s doesn&#38;t need any rtling.', 'rtler'),esc_html($_file)).'</strong></p></div>';
				}
					
			} else {
				echo '<div id="message" class="error fade"><p><strong>'.sprintf(__('the file <code>%s</code> was not found.', 'rtler'),esc_html($path)).'</strong></p></div>';
			}
		} else { // not a CSS file
			echo '<div id="message" class="error fade"><p><strong>'.__('The selected file is not a CSS file.', 'rtler').'</strong></p></div>';
		}
		
	} elseif( isset( $_POST['tortl'] ) && !empty( $_POST['tortl'] ) ) { // we have file content submitted
		
		// get the submitted data
		$tortl = $_POST['tortl'];
		
		// create the RTL object
		$RTLer = new RTLer;
		
		// RTL!
		$rtled = $RTLer->parse_css($tortl);
	}
	
	// TODO: allow user to save the file.
	if ( isset( $_REQUEST['saved'] ) ) echo '<div id="message" class="updated fade"><p><strong>'.__('Options saved.', 'android', 'rtler').'</strong></p></div>';
	
?>
<div class="wrap">

	<h2><?php _e('RTLer', 'rtler'); ?></h2>

	<div style="float:<?php echo is_rtl() ? 'left' : 'right'; ?>; margin: 5px;"><?php printf( __('Version %s by', 'rtler'), '1.6' ); ?> <a href="<?php _e('http://louyblog.wordpress.com/','rtler'	); ?>"><?php _e('Louy Alakkad','rtler'); ?></a></div>
	
	<p><?php _e('', 'rtler'); ?></p>
	
<form method="get" action="">
	
	<input type="hidden" name="page" value="rtler" />
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="theme"><?php _e('Theme', 'rtler'); ?></label></th>
			<td>
				<select name="theme" id="theme">
					<?php
					foreach( $themes as $name => $data ) {
						?><option value="<?php echo esc_attr($data['Template']); ?>"<?php if( $data['Template'] == $theme ) { echo ' selected="selected"'; } ?>><?php echo $name; ?></option>
					<?php
					}
					?>

				</select>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="file"><?php _e('File path', 'rtler'); ?></label></th>
			<td>
				<input name="file" type="text" value="<?php echo esc_attr($file); ?>" id="file" class="regular-text" /> <small><?php _e('must be inside the theme directory, leave blank to use <code>style.css</code>. No <code>../</code> allowed!', 'rtler'); ?></small>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="save"><?php _e('Save file', 'rtler'); ?></label></th>
			<td>
				<input name="save" type="checkbox" value="true" <?php echo $save ? 'checked="checked" ' : ''; ?>id="save" /> <small><?php _e('check this to automatically save the rtled file.', 'rtler'); ?></small>
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="rtled"><?php _e('RTLed file', 'rtler'); ?></label></th>
			<td>
				<textarea rows="10" cols="50" class="large-text code" readonly="readonly" id="rtled"><?php echo esc_html($rtled); ?></textarea>
			</td>
		</tr>
	</table>
		
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Generate rtl.css', 'rtler'); ?>" />
	</p>
</form>

<p><?php _e('Or, just enter the file contents here.', 'rtler'); ?></p>

<form method="post" action="tools.php?page=rtler">
	
	<input type="hidden" name="page" value="rtler" />
	
	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="tortl"><?php _e('CSS to RTL', 'rtler'); ?></label></th>
			<td>
				<textarea rows="10" cols="50" class="large-text code" id="tortl" name="tortl"><?php echo esc_html($tortl); ?></textarea>
				<small><?php _e('I won&#39;t validate the CSS, I don&#39;t have time to!', 'rtler'); ?></small>
			</td>
		</tr>
	</table>
		
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('RTL!', 'rtler'); ?>" />
	</p>
</form>

</div><?php
}

class RTLer {
	
	/**
	 * these bools are used to see if we add a (|padding-|margin-|border-)(right|left) so we can
	 * 		 set the other direction's value. if both directions are added then we add nothing.
	 *
	 * has   => right or left
	 * has_p => padding
	 * has_m => margin
	 * has_b => border
	 */
	var $has   = false;
	var $has_p = false;
	var $has_m = false;
	var $has_b = false;
	
	/**
	 * parse one line of css, if it has something that can be RTLed then do our job.
	 * anyway, if not, return false.
	 */
	function parse_line( $line ) {
		// check if it has right or left word.
		if( preg_match( '/(right|left)/', $line ) ) {
			
			// if it's right; 5px for example, we set $has to 'right'
			// if $has is left, then we reset it to false.
			if( preg_match( '/^([\s\t]*)(right|left)\:/', $line ) ) {
				$is_right = preg_match( '/^([\s\t]*)right\:/', $line );
				if( $this->has === ($is_right?'left':'right') ) {
					$this->has = false;
				} else {
					$this->has = $is_right?'right':'left';
				}
			}
			
			// same as the above code
			if( preg_match( '/^([\s\t]*)padding-(right|left)\:/', $line ) ) {
				$is_right = preg_match( '/^([\s\t]*)padding-right\:/', $line );
				if( $this->has_p === ($is_right?'left':'right') ) {
					$this->has_p = false;
				} else {
					$this->has_p = $is_right?'right':'left';
				}
			}
			
			// same as the above code
			if( preg_match( '/^([\s\t]*)margin-(right|left)\:/', $line ) ) {
				$is_right = preg_match( '/^([\s\t]*)margin-right\:/', $line );
				if( $this->has_m === ($is_right?'left':'right') ) {
					$this->has_m = false;
				} else {
					$this->has_m = $is_right?'right':'left';
				}
			}
			
			// same as the above code
			if( preg_match( '/^([\s\t]*)border-(right|left)\:/', $line ) ) {
				$is_right = preg_match( '/^([\s\t]*)border-right\:/', $line );
				if( $this->has_b === ($is_right?'left':'right') ) {
					$this->has_b = false;
				} else {
					$this->has_b = $is_right?'right':'left';
				}
			}
			
			// flip right snf left.
			$line = $this->right_to_left( $line );
			
		} elseif( preg_match( '/(padding|margin):(([\s\t]*)([^\s\t]+)([\s\t]+)([^\s\t]+)([\s\t]+)([^\s\t]+)([\s\t]+)([^\s\t]*)([\s\t]*)(!important)?([\s\t]*);)/', $line, $matches ) ) {
			// If it's <code>padding: 1 2 3 4;</code> we'll flip the 2nd and the 4th values.
			
			// if they are equal, return false.
			if( $matches[6] == $matches[10] )
			
				$line = false;
				
			else
			
				// now flip
				$line = str_replace( $matches[2], $matches[3].$matches[4].$matches[5].$matches[10].$matches[7].$matches[8].$matches[9].$matches[6].$matches[11].$matches[11].';', $line );
				
		} else { // no RTL to do, return false
			$line = false;
		}
		
		// return the result.
		return $line;
	}
	
	/**
	 * explode block to lines, call $this->parse_line on each,
	 * then add neccesary code to the end of block;
	 */
	function parse_block( $block ) {
		
		// reset some vars
		$this->has   = false;
		$this->has_p = false;
		$this->has_m = false;
		$this->has_b = false;
		
		// explode to lines
		$block = explode( ";", $block );
		
		// prepare return array
		$return = array();
		
		// loop
		foreach( $block as $line ) {
			$line = preg_replace('/\\/\\*.*\\*\\//', '', $line); // remove comments
			if( !$line ) continue;
			$line = trim($line) . ';';
			preg_replace( '/^[\s\t]*([a-z\-]+)\:[\s\t]*(.+)[\s\t]*;/', '$1: $2;', $line );
			$c = $this->parse_line( $line );
			if( $c ) {
				$return[] = '	'.$c;
			}
		}
		
		// check for right/left
		if( $this->has ) {
			$t = ($this->has   === 'right' ) ? $this->has   : 'left';
			$return[] = "\t$t: auto;";
		}
		
		// check for padding
		if( $this->has_p ) {
			$t = ($this->has_p === 'right' ) ? $this->has_p : 'left';
			$return[] = "\tpadding-$t: 0;";
		}
		
		// check for margin
		if( $this->has_m ) {
			$t = ($this->has_m === 'right' ) ? $this->has_m : 'left';
			$return[] = "\tmargin-$t: auto;";
		}
		
		// check for border
		if( $this->has_b ) {
			$t = ($this->has_b === 'right' ) ? $this->has_b : 'left';
			$return[] = "\tborder-$t: none;";
		}
		
		// return
		return count($return) ? implode("\n", $return) : false;
	}
	
	/**
	 * extract blocks from css file, then $this->parse_block() on each.
	 */
	function parse_css($css) {
		
		/**
		 * TODO: store comments so if comment includes {} we don't get confused :S
		 */
		$comments = array();
		
		$b = explode( '}', $css );
		
		/**
		 * this return array contains values in the form:
		 * 				array( $selector, $code );
		 */
		$return = array();
		
		// media vals
		$is_media = false;
		$media_selector = '';
		$media_i = 0;
		
		// loop throw blocks.
		foreach( $b as $_b ) {
			
			// explode to selector and code.
			$_b = explode( '{', $_b );
			
			// check header to see if it's @media.
			$h = $this->remove_comments($_b[0]);
			
			if( preg_match( '/@media/', $h ) ) {
				
				$is_media = true;
				$media_selector = $_b[0];
				$media_i = 0;
				
				array_shift($_b);
				
			} elseif( count($_b) == 1 && $is_media ) {
				
				if( $media_i ) {
					$return = array_slice( $return, 0, -$media_i );
					$a = array_slice( $return, -$media_i, $media_i );
					
					$s = '';
					
					// loop throw the array
					foreach( $a as $_a ) {
						if( $_a[1] )
							$s .= "\n" . trim( $_a[0] ) . " {\n$_a[1]\n}\n";
						else
							$s .= "\n" . $_a[0];
					}
					
					$return[] = array( $media_selector, $s );
					
				} else {
					
					// lets at least add the comments
					/*
					$c = $this->keep_comments($media_selector);
					
					if( !empty( $c ) ) {
						$return[] = array( $c );
					}
					*/
					
					// or i'll keep the selector!
					$c = $media_selector;
					
					if( !empty( $c ) ) {
						$return[] = array( $c , '' );
					}
				}
				
				$is_media = false;
				$madia_selector = '';
				$media_i = 0;
				
				continue;
			} elseif( preg_match( '/^\\.f[rl]$/', trim($h) ) ) {
				
				//leave comments alone!
				$c = $this->keep_comments($_b[0]);
				if( !empty( $c ) ) {
					$return[] = array( $c );
				}
				
				// continue
				continue;
			}
			
			// parse code
			$t = $this->parse_block( $_b[1] );
			
			// add to the $return array
			if( $t ) {
				$media_i++;
				$return[] = array( $this->right_to_left($_b[0]), $t );
			} else {
				
				//leave comments alone!
				$c = $this->keep_comments($_b[0]);
				if( !empty( $c ) ) {
					$return[] = array( $c );
				}
				
			}
		}
		
		// return string
		$x = '';
		
		// loop throw the array
		foreach( $return as $r ) {
			if( count($r)>1 )
				$x .= "\n" . trim( $r[0] ) . " {\n$r[1]\n}\n";
			else
				$x .= "\n" . $r[0];
		}
		
		//remove 3+ empty lines
		$x = preg_replace( '/(\n)\n+/', '$1$1', $x );
		
		// first char is an empty line!
		$x = preg_replace( '/^\n+/', '', $x );
		
		if( empty($x) )
			return false;
		
		// add some credits
		$x .= "\n\n/* ".__('Generated by the RTLer', 'rtler').' - http://wordpress.org/extend/plugins/rtler/ */';
		
		// Done. whew!
		return $x;
	}
	
	/**
	 * remove the css comments from a string.
	 */
	function remove_comments($string) {
		
		// first, remove the //comments
		$s = explode( "\n", $string );
		$r = array();
		foreach( $s as $_s ) {
			$_s = trim( $_s );
			if( substr( $_s, 0, 2 ) != '//' ) {
				$r[] = $_s;
			}
		}
		$s = implode( "\n", $r );
		
		// now, remove the /*comments*/
		$s = explode( '*/', $s );
		$r = array();
		foreach( $s as $_s ) {
			$t = explode( '/*', $_s );
			if( !empty( $t[0] ) ) {
				$r[] = $t[0];
			}
		}
		
		// and return
		return implode( "\n", $r );
	}
	
	/**
	 * remove everything except the comments from a string.
	 */
	function keep_comments($string) {
		
		// look for /*comments*/
		$s = explode( '*/', $string );
		$x = '';
		
		foreach( $s as $_s ) {
			$t = explode( '/*', $_s );
			if( count( $t )>1 ) {
				$x .= "/*{$t[1]}*/\n";
			}
		}
		
		// and return
		return $x;
	}
	
	/**
	 * replace "right" width "left" and vice versa
	 */
	function right_to_left($str) {
		
		// replace left with a TMP string.
		$s = str_replace( 'left', 'TMP_LEFT_STR', $str );
		
		// flip right to left.
		$s = str_replace( 'right', 'left', $s );
		
		// flip left to right.
		$s = str_replace( 'TMP_LEFT_STR', 'right', $s );
		
		// return
		return $s;
	}
	
}