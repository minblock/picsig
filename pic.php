<?php
$version = '0.9.5';

/*

		:!: utf-8 (no bom); unix linefeeds; text: monaco/ProFont 9pt, 4 spaces/tab :!:
			:!: this file does _not_ need to be world-writable (chmod 755-ish) :!:


											picsig

				 			 Picscoin dynamic sig file generator thing


	this program creates a dynamic "now playing" type signature image that can be used in forums,
	emails, etc. 
	
	For full documentation, tips, tricks, feedback, latest downloads, etc, go here..

		http://picscoins.org

	copyright Picscoin 2018 ->

*/



/* 
		global preferences..
									*/


/*
	security
	
	rudimentary security measures..
	you don't want some twat posting "alternative" information to your sig
																			*/
$password = 'PASSWORD(or client code)'; 


/*	ini file..
	[default: $data_file = 'data/pic.ini';]

	location, relative to pic.php, or else specify the *full* valid full path.
	the data file needs to be "world writable" (though windows servers won't care much)
	or at least, writable by the server process. in *nix: chmod 777 /path/to/pic.ini	*/
$data_file = 'data/pic.ini'; // or call it whatever you like


/* 
	schemes..
	[default: $schemes_dir = 'schemes';]
																				 */
// name of the folder you keep your picsig schemes in (relative to pic.php)..
$schemes_dir = 'schemes';

// which scheme to use?..
// note: you don't need to enter the '.scheme' (extension) part of the name.
//$scheme_file = 'spidey';
//$scheme_file = 'simple';
$scheme_file = 'standard';


/*
	random scheme..
	[default: $random_schemes = false;]

	picsig can randomly pick a scheme for you. this makes things interesting. 
	remember to test your schemes first.

		http://domain.com/path/to/pic.php/spidey.jpg	etc.

	to disable a particular scheme from loading, change its extension, i.e. "standard._scheme"
	or else remove it from your schemes folder!	"test.scheme" (or even "my-cool-test.scheme")
	will *not* appear in random results, which is handy if you are testing stuff, and prevents
	it becoming your 'public' sig. For this to work, you'll need to also set a $favourite_scheme.
																								*/
$random_schemes = false;

/* 
	favourite scheme
	[default: $favourite_scheme = 'spidey';]

	this scheme will be *much* more likely to appear..
	enter an empty value (or commant out) if you don't have a favourite.	*/
$favourite_scheme = 'imac-girl';

// random factor.	[a number, from 1 - (no. of schemes)]
// the higher you go, the more likely it is your favourite scheme will appear.
// default: $r_factor = 3;
$r_factor = 3;



// a simple text file to count the number of times your picsig has been viewed
// [default: $counter_file = 'data/counter';]
$counter_file = 'data/counter';


/*
	override

	two values can be overridden by the incoming request, if you wish.
	to specify a particular scheme and output image format, use..

		[img]http://mydomain.com/path/to/pic.php/hal.png[/img]

	which would, unsurprisingly, get you the 'hal' scheme, in 'png' format.
	this is handy if you normally set your picsig to random, but always want
	to show a particular scheme on a particular forum. perhaps something
	related to the forum, I dunno, maybe special email picsig scheme.
	it's a cool feature anyway.
																		*/
$allow_override = true;


// display song rating?	(does nothing yet, just thinking about this)
$rating = false;


/*
		end global prefs
								*/



// init..
$self = $_SERVER['PHP_SELF'];
$root = $_SERVER['DOCUMENT_ROOT'];
if (!is_numeric($r_factor) or $r_factor == 0) $r_factor = 3;
if (isset($scheme_file)) { $scheme_file = $schemes_dir.'/'.$scheme_file.'.scheme'; }


// get the time()..
$time = explode(' ',microtime());
$time = $time[1]; settype($time,'int'); // one second accuracy!


/*

		request was an HTTP POST, so we go into data collecting mode..

																			*/

if (isset($_POST['pass'])) {	// collect data..

	if ($_POST['pass'] == $password) {

		$data = '';// grab old ini data..
		$config = read_ini($data_file);


		/*	
				"NowPlaying" compatibility layer!	*/

		if (isset($_POST['Filename1'])) {
			// altering the _POST array directly is evil, but we're gonna do it anyway..
			if (isset($_POST['Playing'])) { 
				if ($_POST['Playing'] == '1') { $_POST['playing'] = 'playing'; }
				if ($_POST['Playing'] == '0') { $_POST['playing'] = 'stopped'; }
				unset ($_POST['Playing']);	// we keep 'playing'
			}
			$_POST['title'] = $_POST['Artist1'].' - '.$_POST['Title1'];
			$_POST['album'] = $_POST['Album1'];
			$lenny = explode(':',$_POST['Length1']);
			$_POST['length'] = (60 * $lenny[0]) + $lenny[1];
			// delete all *****1 type keys..
			foreach ($_POST as $i => $pval) {
				// they forgot to set NowPlaying history to '1', the default is '2'.
				if (strstr($i, '1') or strstr($i, '2')) unset($_POST[$i]);
			}
		}


		/*
			check for any changes..
										*/
		// uptime sync ..[uTu]..
		if (isset($_POST['uptime'])) {
			$config['uptime_diff'] = ($time - $_POST['uptime']);
		}
		// update "last played"..
		if (isset($_POST['title'])) {
			if (stripslashes($_POST['title']) != @$config['title']) {
				$config['last_played'] = $config['title'];
			}
		}
		// paused or stopped play..
		if (isset($_POST['playing'])) {
			$_POST['playing'] = strtolower($_POST['playing']); 

			if ($_POST['playing'] == 'paused' or $_POST['playing'] == 'stopped') { 
				if (($config['playing'] != 'paused') and ($config['playing'] != 'stopped')) {
					$config['static_bar'] = $time;
				} else { $config['static_bar'] = $config['started'] + @$_POST['pos']; }
			}


			// you pressed play!
			if ($_POST['playing'] == 'playing') {
				unset($config['static_bar']);
				// shift "started at" to a new time
				$config['started'] = $time - (@$_POST['pos']); 
				if (!isset($_POST['album']) and !isset($_POST['dir'])) {
					$config['album'] = $config['dir'] = 'album unknown';
				}
			}
		}
		
		if (!isset($config['status']) and !isset($_POST['status'])) { 
			$config['status'] = 'no status given';
		}
		
		// merge the old and new settings and data..
		$config = array_merge($config, $_POST);


		// write out our "ini" file..
		write_ini($data_file,$config);
	} else { die ('YOU HAVE NOT TEH AUTHORITEE!'); }
} elseif (isset($_GET['version'])) { die ("picsig v$version"); } else {


/*
	
		or else generate an image from the current data set..

																	  */

// random scheme?..
if (isset($random_schemes) and $random_schemes) {
	
	// go into the dir and scan..
	if ($dir_handle = opendir($schemes_dir)) {
		$schemes_pool = array();
		$i = 0;
		while (($file = readdir($dir_handle)) !== false) {    
			if ((ord($file) != 46)  and (substr(strrchr($file, "."), 1) == 'scheme')) { 
				$schemes_pool[$i] = $schemes_dir.'/'.$file;
				$i++;
			}
		} 
	closedir($dir_handle);
	$pool_count = count($schemes_pool);
	}

	// favourite scheme?
	if (isset($favourite_scheme)) {
		if ($favourite_scheme != '') {
			$pool = rand(0, ($pool_count * 2) - ($pool_count / $r_factor));
			if ($pool > ($pool_count - 1)) { 
				$scheme_file = $schemes_dir.'/'.$favourite_scheme.'.scheme';
			} else { 
				$scheme_file = $schemes_pool[$pool];
			}
			if (strstr($scheme_file, 'test.scheme')) { 
				$scheme_file = $schemes_dir.'/'.$favourite_scheme.'.scheme';
			}
		} else { 
			$scheme_file = $schemes_pool[rand(0, (count($schemes_pool) - 1))];
		}
	}
}


// specified scheme in the URL?..
if ($allow_override and isset($_SERVER['PATH_INFO'])) {
	$p_nfo = explode('/', $_SERVER['PATH_INFO']);
	$o_scheme = substr($p_nfo[count($p_nfo) - 1], 0, strpos($p_nfo[count($p_nfo) - 1], "."));
	if (file_exists($schemes_dir.'/'.$o_scheme.'.scheme')) { 
		$scheme_file = $schemes_dir.'/'.$o_scheme.'.scheme'; }
} else { $allow_override = false; }


// include the scheme now..
if (isset($scheme_file) and file_exists('./'.$scheme_file)) {
	include ('./'.$scheme_file);
} else { die ("scheme file not found. sorree."); }


// override output format?..
if ($allow_override) {
	$get_format = substr(strrchr($p_nfo[count($p_nfo) - 1], "."), 1);
	if (($get_format == 'jpg') or ($get_format == 'jpeg')) { $output_format = 'jpg'; } 
	elseif ($get_format == 'png') { $output_format = 'png'; } 
	elseif ($get_format == 'gif') { $output_format = 'gif'; }
}




	/*
		check scheme preferences, insert defaults if need be..
																	*/
	if (!isset($fields[1]['id'])) $fields[1]['id'] = 'playing';
	if (!isset($fields[2]['id'])) $fields[2]['id'] = 'status';
	if (!isset($fields[3]['id'])) $fields[3]['id'] = 'title';
	if (!isset($fields[4]['id'])) $fields[4]['id'] = 'album';
	if (!isset($status_affixes)) $status_affixes = array('[', ']');
	if (!isset($playing_string)) $playing_string = 'now playing..'; 
	if (!isset($stopped_string)) $stopped_string = 'last played..';
	if (!isset($truecolor)) $truecolor = false;
	if (!isset($q)) $q = 100;
	if (!isset($output_format)) $output_format = 'png';
	if (!isset($interlace)) $interlace = false;
	if (!isset($reduce)) $reduce = false;
	if (!isset($greyscale)) $greyscale = false;
	if (!isset($gamma)) $gamma = '1.0';
	if (!isset($r_balance)) $r_balance = 0;
	if (!isset($g_balance)) $g_balance = 0;
	if (!isset($b_balance)) $b_balance = 0;
	if (isset($do_col_bg)) $color_layer = $do_col_bg;	// depricated!
	if (!isset($color_layer)) $color_layer = true;
	if ((!isset($img_width) or !isset($img_height)) and (!isset($image_file))) { $img_width = 350; $img_height = 80; }
	if (!isset($merge_images)) $merge_images = false;
	if (!isset($pre_merge)) $pre_merge = true;
	if (!isset($transparency)) $transparency = 50;
	if (!isset($merge_x)) $merge_x = 0;
	if (!isset($merge_y)) $merge_y = 0;
	if (!isset($mx_nudge)) $mx_nudge = 0;
	if (!isset($my_nudge)) $my_nudge = 0;
	if (!isset($border)) $border = 1;
	if (!isset($target_color)) $target_color = '#f0ff32';
	if (!isset($variation)) $variation = 15;
	if (!isset($do_frame)) $do_frame = true;
	if (!isset($font_size)) $font_size = 2;
	if (!isset($ttf)) $ttf = false;
	if (!isset($ttf_size)) $ttf_size = '9';
	if (!isset($bold)) $bold = false;
	if (!isset($align)) $align = 'center';
	if (!isset($antialiasing)) $antialiasing = true;
	if (!isset($trim_adjust)) $trim_adjust = 0;
	if (!isset($auto_shrink)) $auto_shrink = true;
	if (!isset($field[1]['em'])) $field[1]['em'] = 1;
	if (!isset($leading)) $leading = 1;
	if (!isset($nudge)) $nudge = 0;
	if (!isset($push)) $push = 6;
	if (!isset($skew)) $skew = 0;
	if (!isset($field[2]['fp'])) $field[2]['fp'] = -1;
	if (!isset($do_bargraph)) $do_bargraph = true;
	if (!isset($thickness)) $thickness = 14;
	if (!isset($squeeze)) $squeeze = 0;
	if (!isset($xnudge)) $xnudge = 0;
	if (!isset($ynudge)) $ynudge = 0;
	if (!isset($do_btxt)) $do_btxt = true;
	if (!isset($btxt_trans)) $btxt_trans = false;
	if (!isset($btxt_size)) $btxt_size = 1;
	if (!isset($btxt_valign)) $btxt_valign = 'middle';
	if (!isset($do_browser_info)) $do_browser_info = true;
	if (!isset($ip_string)) $ip_string = 'your ip is.. ';
	if (!isset($percent_string)) $percent_string = '';
	if (!isset($use_themes)) $use_themes = true;
	if (!isset($calc_red)) $calc_red 	= '255-(($i/2)/255)';
	if (!isset($calc_green)) $calc_green = '255-($i/2)';
	if (!isset($calc_blue)) $calc_blue 	= '($i/2)-255';
	if (!isset($lf)) $lf = 1;
	if (!isset($termination)) $termination = 1;
	if (!isset($bar_frame)) $bar_frame = 0;
	if (!isset($bar_trans)) $bar_trans = 0;
	if (!isset($rating)) $rating = false;
	if (($ttf) and (!isset($font_face))) { $ttf = false; }
	// what were they thinking!

	// old version schemes..
	if (isset($center)) $align = 'center';
	

	//digest the ini..
	$config = read_ini($data_file);

	//foreach ($config as $key => $value) {
	//   $$key = $value;// ooh! clever!
	//} // i find it clearer and quicker to work with $status, than $config['status'], see.
	// this creates lots of "interesting" security holes. 

	// increment the view counter..
	if (is_writable($counter_file)) {
		$count = implode('', file($counter_file));
		$count++;
		$file_handle = fopen($counter_file, 'w+');
		fwrite($file_handle, $count);
		fclose($file_handle);
	} else { $count = '[no file]'; }


	// are we paused or stopped?
	if ($config['playing'] == 'stopped') { 
		$header = $stopped_string;
	} else {
		// how spidey's eyes light up if you are listening to a song..
		$me_paths = pathinfo($self);
		if (isset($image_file)) {
			if (stristr($me_paths['dirname'], 'pic.php')) { // if you append /spidey.png, everything moves..
				$me_paths['dirname'] = substr($me_paths['dirname'], 0, strrpos($me_paths['dirname'],'/'));
			} // generally speaking, it's a bad idea to alter this directly, but hey!
			$foo = str_replace($me_paths['dirname'], '', $image_file);
			$playing_img = $root.$me_paths['dirname'].'/'.substr($foo, 0, strpos($foo, ".")).'_play.png';
			if (file_exists($playing_img)) { $image_file = $playing_img; }
		}
		$header = $playing_string;
	}

	// prepare the colours..
	$target_color = hex2dec($target_color);
	$red_limit = $target_color[0];
	$green_limit = $target_color[1];
	$blue_limit = $target_color[2];

	// randomise..
	$variation = array(0, $variation);
	$red_level = rand($variation[0], $variation[1]);
	$green_level = rand($variation[0], $variation[1]);
	$blue_level = rand($variation[0], $variation[1]);

	$red_value = abs($red_limit-$red_level);
	$green_value = abs($green_limit-$green_level);
	$blue_value = abs($blue_limit-$blue_level);


	/*
			create the base image

			I fancy doing a plug-in API for this. hmm.
															*/


	if (isset($image_file)) {
		$img2 = imagecreatefrompng($image_file);
		imagesavealpha ($img2, true);
		$img_width2 = imagesx($img2);
		$img_height2 = imagesy($img2);
		if (!isset($img_width) or !isset($img_height)) { 
			$img_width = $img_width2; 
			$img_height = $img_height2; 
		}
	}

	if ($color_layer) {

		if ($truecolor) { 
			$img = imagecreatetruecolor($img_width, $img_height);
		} else { 
			$img = imagecreate($img_width, $img_height);
		}

		if (isset($img2)) {
			// smaller size has been specified, need to crop..
			if  (($img_width != $img_width2) or ($img_height != $img_height2)) { 
				$t_img = imagecreatefrompng($image_file);
				imagecopy($img2, $t_img, $mx_nudge, $my_nudge, $merge_x, $merge_y, $img_width, $img_height);
				imagedestroy ($t_img);
			} else { 
				imagecopy($img2, $img2, $mx_nudge, $my_nudge, $merge_x, $merge_y, $img_width, $img_height); 
			}
		}
	} else {
		
		if (isset($img2)) {
			$img = $img2;
			//imagedestroy($img_b); // amazingly, this destroys $img! erm. hello!
			unset($img2); // no merging possible now.
		}
	}


	if (!isset($img)) { die ("no image has been specified!"); }
	$img_width = imagesx($img);
	$img_height = imagesy($img);


	// setup transparent background..
	if (isset($trans_color)) { // you can still set this, though it is depricated.
		$trans_color = hex2dec($trans_color);
		$t_bg = imagecolorallocate($img, $trans_color[0], $trans_color[1], $trans_color[2]);
		imagecolortransparent($img, $t_bg);
	}

	// add our randomised color layer, inside the frame..
	if ($color_layer) {
		if ($do_frame == true) { $bf = $border; } else { $bf = 0; }
		$bg = imagecolorallocate($img, $red_value, $green_value ,$blue_value);
		imagefilledrectangle($img, $bf, $bf, $img_width-$bf-1, $img_height-$bf-1, $bg);
	}

	// create frame colour..
	if (isset($frame_color)) {
		$frame_color = hex2dec($frame_color);
		$frame = imagecolorallocate($img, $frame_color[0], $frame_color[1], $frame_color[2]);
	} else { 
		$frame = imagecolorallocate($img, $red_level, $green_level, $blue_level);
	}

	// draw the frame..
	if ($do_frame == true) {
		for ($i = 0; $i < $border; $i++) { 
			$points = array($i, $i, $img_width-1-$i, $i, $img_width-1-$i, $img_height-1-$i, $i, $img_height-1-$i);
			imagepolygon ($img, $points, 4, $frame);
		}
	}


	// allocate the text colors (early, to avoid obliteration!)
	if (isset($text_color)) { $text_color = hex2dec($text_color); } 
		else { $text_color = array(255 - $red_value, 255 - $green_value, 255 - $blue_value); }

	$text_color = imagecolorallocate($img, $text_color[0], $text_color[1], $text_color[2]);
	if (isset($btxt_color) and !$btxt_trans) {
		$btxt_color = hex2dec($btxt_color); 
		$btxt_color = imagecolorallocate($img, $btxt_color[0], $btxt_color[1], $btxt_color[2]);
	} else { $btxt_color = $text_color; }
	if ($btxt_trans) { $btxt_color = imagecolorallocate($img, $red_value, $green_value ,$blue_value); }

	/*
			prepare the text fields..
											*/

	// no album name? let's create it from the folder name..
	if ($config['album']  == '') { $config['album'] = $config['dir']; } // or..
	if ($config['album'] == '' and ($config['length'] == 0 or $config['length'] = $config['pos'])) $config['album'] = 'Internet Audio Stream';
	

	// run through the fields one-at-a-time, select the data type and format as we go..
	foreach ($fields as $key => $value) {

		// if you want to add new data types, this is where to do it..
		switch ($value['id']) {

			case 'playing':
				$fields[$key]['txt'] = $header;
				break;

			case 'status':
				$fields[$key]['txt'] = $status_affixes[0].$config['status'].$status_affixes[1];
				break;

			case 'title':
				$fields[$key]['txt'] = $config['title'];
				break;

			case 'artist':
				$fields[$key]['txt'] = $config['artist'];
				break;

			case 'album':
				$fields[$key]['txt'] = $config['album'];
				break;

			case 'last':
				$fields[$key]['txt'] = 'last played: '.$config['last_played'];
				break;

			case 'uptime':
				$fields[$key]['txt'] = 'uptime: '.calc_uptime($config['uptime_diff']);
				break;

			case 'counter':
				$fields[$key]['txt'] = 'This picsig has been viewed '.$count.' times.';
				break;

			case 'custom':
				if (isset($config['custom']) and $config['custom'] != '') { 
					$fields[$key]['txt'] = $config['custom'];
				} else { $fields[$key]['txt'] = 'custom text not available!'; }
				break;

			case 'custom2':
				if (isset($config['custom2']) and $config['custom2'] != '') { 
					$fields[$key]['txt'] = $config['custom2'];
				} else { $fields[$key]['txt'] = 'custom text not available!'; }
				break;

			case 'custom3':
				if (isset($config['custom3']) and $config['custom3'] != '') { 
					$fields[$key]['txt'] = $config['custom3'];
				} else { $fields[$key]['txt'] = 'custom text not available!'; }
				break;

			// add more data types here.

			default: // blank lines
				$fields[$key]['txt'] = '   ';
				$fields[$key]['id'] = 'blank';
				break;
		}


		// per-line user overrides..


		// add the 'extra' field user prefs.. 
		if (isset($field[$key]['em'])) { $fields[$key]['em'] = $field[$key]['em'];}  else { $fields[$key]['em'] = 0; }
		if (isset($field[$key]['fp'])) $fields[$key]['fp'] = $field[$key]['fp'];  else { $fields[$key]['fp'] = 0; }

		// alignment (individual lines can be overridden) ..
		$fields[$key]['align'] = $align;
		if (isset($field[$key]['align'])) $fields[$key]['align'] = $field[$key]['align'];
		
		// we could theoretically allow any override, the mechanism is already in place.
		
		/*
				calculate sizes for this line of text (and trim, if necessary) ..
																					*/

		if ($ttf) { // truetypes..

			$alloc_width = $img_width - (2 * $nudge) - (2 * $border) - $trim_adjust;
			$bx = imagettfbbox($ttf_size + $fields[$key]['em'], $skew, $font_face, '|'.$fields[$key]['txt']);
			$txt_width = abs($bx[0]) + abs($bx[2]);












			// oversized text fields..
			if (($txt_width) >= $alloc_width) {
				if ($auto_shrink) {
					//$trim_adjust = 0;
					while ($txt_width >= $alloc_width) {
						$fields[$key]['em']--;

						$bx = imagettfbbox($ttf_size + $fields[$key]['em'], 
							$skew, $font_face, '|'.$fields[$key]['txt']);
						$txt_width = abs($bx[0]) + abs($bx[2]);

					}
				} else {
					$fields[$key]['txt'] = substr($fields[$key]['txt'], 0, 
						round(strlen($fields[$key]['txt']) 
						- ( ($txt_width - $alloc_width) /  ($txt_width / strlen($fields[$key]['txt'])) ) 
							- $trim_adjust, 2)).'...';
				}
				// everything has changed now, do it again! (the '|' is to vsize 'blank' lines)
				$bx = imagettfbbox($ttf_size + $fields[$key]['em'], $skew, $font_face, '|'.$fields[$key]['txt']);
				$txt_width = abs($bx[0]) + abs($bx[2]);
			}

			// work out the x factor..
			if ($fields[$key]['align'] == 'center') {
				$fields[$key]['x'] = ((($img_width - (2 * $border)) - $txt_width) / 2) + $nudge + $border;
			} elseif ($fields[$key]['align'] == 'right') {
				$fields[$key]['x'] = $img_width - $border - $txt_width + $nudge;
			} else {
				$fields[$key]['x'] = $border + (2 * $nudge);
			}










			$fields[$key]['y'] = abs($bx[1]) + abs($bx[7]);

		} else { // GD built-in font..

			// constants..
			$halfway = ($img_width / 2) + $nudge;
			$chr_width = imagefontwidth($font_size + $fields[$key]['em']);
			$trim = ($img_width - (2 * $border) - $squeeze + $trim_adjust) / $chr_width;




			// for some reason, it's NOT possible to add an "&#133;" (ellipsis). damn!
			if (strlen($fields[$key]['txt']) > $trim) {
				$fields[$key]['txt'] = substr($fields[$key]['txt'], 0, $trim - 3).'...';
			}

			$string_width = strlen($fields[$key]['txt']) * $chr_width; 

		
			if ($fields[$key]['align'] == 'center') {
				$fields[$key]['x'] = $halfway - ($string_width / 2);
			} elseif ($fields[$key]['align'] == 'right') {
				$fields[$key]['x'] = $img_width - $border - $string_width + $nudge;
			} else {
				$fields[$key]['x'] = 2 * $nudge + $border;
			}
		}   

	} // end foreach()


	// we'll lay down the text after the bar-graph..



	// pre-merge (for saturation)
	if ($merge_images and isset($img2) and $pre_merge) { 
		imagecopymerge($img, $img2, 0, 0, 0, 0, $img_width, $img_height, $transparency);
	}



	/*
		the gradient bar-graph
		the funkiest coolest neatest thing in the known universe, bar, erm..
																					*/
	if ($do_bargraph) {

		// subtle 3D effect on termination, etc..
		if ($thickness == 'fill') { $thickness = $img_height-(2 * $border); $shadadd = 0; } else { $shadadd = 1; }
		if ($termination > 1) { $shadadd = 0; }

		// are we paused or stopped..
		if (isset($config['static_bar'])) { $played = $config['static_bar'] - $config['started']; 
			} else { $played = $time - $config['started']; }
		if ($config['length'] > 0) { $percent_done = floor(($played / $config['length']) * 100); } else { $percent_done = 0; }
		
		if ($percent_done > 100) $percent_done  = 100; // *shouldn't* happen

		// for the displayed text
		if ($do_btxt) { $percent = $percent_string.$percent_done.'%'; 
			} else { $percent = ''; }

		// total length of the bar
		$bar_len = ($percent_done / 100) * ($img_width - $border - $squeeze);		
		if ($bar_len > 0) { if ($truecolor) { $img3 = imagecreatetruecolor($bar_len, $thickness); } 
			else { $img3 = imagecreate($bar_len, $thickness); }
		}
		if (isset($img3)) {
			if ($use_themes) { @do_theme($theme); } // @ in case they forget to actually specify a theme

			// create the gradient effect..
			for ($i = 1; $i <= $bar_len; $i++) {
				eval("\$r = $calc_red;"); eval("\$g = $calc_green;"); eval("\$b = $calc_blue;");
				$fill = imagecolorallocate($img3, $r, $g, $b);
				imagefilledrectangle($img3, $i, 0, $i, $thickness - 1, $fill);	
			}

			// vertical line at the end of the bar-graph (very important!)
			if ($termination) {

				if (!isset($term_color)) { 
					$term_color = $frame_color; 
				} else { 
					$term_color = hex2dec($term_color);
				}

				$term_color = imagecolorallocate($img, $term_color[0], $term_color[1], $term_color[2]);
				imagefilledrectangle($img3,
					$bar_len - $termination, $shadadd, $bar_len, $thickness - 1, $term_color);
			}
			
			// a border around the progress bar?
			if ($bar_frame) {
				for ($i=0; $i<$bar_frame; $i++) {
					imagerectangle ($img3, $i + 1, $i, $bar_len - $i, $thickness - $i -1, $frame);
				}
			}
			// lay the progress bar down onto the image..
			imagecopymerge($img, $img3, $border + ($squeeze / 2) + $xnudge, 
			$img_height - $border - $thickness + $ynudge, 1, 0, $bar_len -1, $thickness, 100 - $bar_trans);
		}


		// vertical alignment of the progress bar text .. (no ttfs here)
		$bar_txt_fsize = imagefontheight($btxt_size);
		switch($btxt_valign) {
			case 'top':
				$vpos = ($img_height - (2 * $border) - $thickness) + ($bar_txt_fsize / 2);
				break;
			case 'bottom':
				$vpos = ($img_height - $border) - ($bar_txt_fsize + 2);
				break;
			default: // middle (easiest to misspell!)
				$vpos = ($img_height - $thickness) + ($thickness / 2) - ($bar_txt_fsize / 2) - $border;
				break;
		}

	}


	// merge the images
	// all text will go on after this..
	if ($merge_images and isset($img2)) { 
		imagecopymerge($img, $img2, 0, 0, 0, 0, $img_width, $img_height, $transparency);
		imagedestroy($img2);
	}

	// progress bar text..
	if ($do_bargraph) {
		if ($do_btxt) {
			$b_string = $percent;
			if ($do_browser_info) { $b_string = $ip_string.$_SERVER['REMOTE_ADDR'].'   '.$b_string; }
		} else { $b_string = ''; }
		if ($percent_done == 100 or isset($_GET['credit'])) { $b_string = $config['copyright'].'   '.$b_string; }
		// come on, credit where it's due.. (this only shows at exactly 100%)
		$str_length = imagefontwidth($btxt_size) * strlen($b_string);
		if ($str_length > $bar_len - 2) { 
			$bar_limit = (floor($bar_len) / imagefontwidth($btxt_size));
			if (!$squeeze) $sq = 1; else $sq = abs($squeeze);
			if (!$xnudge) $nud = $sq; else $nud = abs($xnudge);
			$bar_limit = floor(abs($bar_limit - ( (floor($sq / $nud)) / imagefontwidth($btxt_size))) - 1 );
			if ($bar_limit > 0 )  { $b_string = substr($b_string, - $bar_limit); } else { $b_string = ''; }
		}
		imagestring ($img, $btxt_size, 
			$bar_len - (imagefontwidth($btxt_size) * strlen($b_string)) + ($xnudge + ($squeeze / 2.05)) - 4, 
				$vpos + $ynudge, $b_string, $btxt_color);
	}


	/*
			your bar-graph is complete!	
												*/


	/*
		finally, lay down the text fields..	
		we do it last so it's always on top
												*/
	$txt_height = imagefontheight($font_size);

	if ($ttf) {
		$tpush = $push;
		if ($antialiasing != true) { $text_color = -$text_color; }
	}
	$ttfh=$border;
	$push -= $txt_height;
	//settype($bold, 'int');

	foreach ($fields as $key => $value) {
		$bl = (int) $bold;
		if ($key == 1) { $ld = 0; } else { $ld = $leading; } // no leading on first row
		if  ($ttf) {
			do {
				imagettftext($img, $ttf_size + $fields[$key]['em'], $skew, $fields[$key]['x'] + $bl,
					$tpush + $ld + $ttfh + $fields[$key]['y'] + $fields[$key]['fp'] - $key, 
						$text_color, $font_face, $fields[$key]['txt']);
				$bl--;
			} while ($bl >= 0);

			if ($skew != 0) { 
				$ttfh += $fields[$key]['y'] + ($skew * ($fields[$key]['y'] / $skew)) + $leading; // what!?!
				// if anyone desperately needs, or better still, works this out, get in touch!
			} else { $ttfh += $fields[$key]['y'] + $ld; }

		} else { // gd built-in font..
			do {
				imagestring ($img, $font_size + $fields[$key]['em'], $fields[$key]['x'] + $bl,
					$push + $fields[$key]['fp'] + (($txt_height + $ld) * $key - $fields[$key]['em']),
						$fields[$key]['txt'], $text_color);
				$bl--;
			} while ($bl >= 0);

		}
	}

output_image($img);
}


/*
		fin
					*/



/*
	finally, send our image to the browser..
												*/
function output_image($img) {
global $gamma, $greyscale, $interlace, $output_format, $q, $reduce, $r_balance, $g_balance, $b_balance;

	// Bael discovered some funky extra headers..
	Header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
	Header('Expires: Tue, 01 Apr 1970 00:00:00 GMT');
	Header('Pragma: no-cache');


	
	if ($greyscale == true) { 
		$img = make_greyscale($img, $r_balance, $g_balance, $b_balance);
	}

	if (($gamma) != '1.0' ) { // probably make reduce only
		$img = adjust_gamma($img, $gamma);
	}

	if ($interlace) imageinterlace($img, 1);

	if ($output_format == 'png') { 
		// this tells the browser to interpret whatever comes next as an image. 
		if ($reduce) { $img = reduce_img($img, 256); }
		header('Content-type: image/png');
		imagepng($img,"",$q); // the actual image is created HERE, and sent to browser. 
	} else if ($output_format == 'jpg'){ 
		header('Content-type: image/jpeg');
		imagejpeg($img,"",$q);
	} else if ($output_format == 'gif'){ 
		$img = reduce_img($img, 256);
		header('Content-type: image/gif');
		imagegif ($img,"",$q);
	}// you MUST destroy the image afterwards, to free-up the server's memory..
	imagedestroy($img);

	die();
}


// reduce to a pallette..
function reduce_img($img, $colors) {
global $trans_color;

	// create a temporary truecolor image..
	$width = imagesx($img);
	$height = imagesy($img);
	$t_img = imagecreatetruecolor($width, $height);
	imagecopymerge($t_img, $img, 0, 0, 0, 0, $width, $height, 100);

	// convert original image to paletted image..
	imagetruecolortopalette($img, true, $colors);

	// match color palette with original
	imagecolormatch($t_img, $img);

	// get the top-left pixel color, make transparent..
	$t_color = imagecolorat($img, 1, 1);

	imagedestroy($t_img);
	imagecolortransparent ($img, $t_color);
	return $img;
}/*
end function reduce_img()	*/


/*
make a greyscale image (8 bit)	*/
function make_greyscale($img, $r_bal, $g_bal, $b_bal) {
global $width, $height;

	$width = imagesx($img);
	$height = imagesy($img);
	$t_img = imagecreate($width, $height);

	for ($idx = 0; $idx < 256; $idx++) {
		// loop the colours.
		$r = $idx + $r_bal; $g = $idx + $g_bal; $b = $idx + $b_bal;
		if ($r < 0 ) { $r = 0 - $r; } elseif ($r > 256 ) { $r = $r - 256; }
		if ($g < 0 ) { $g = 0 - $g; } elseif ($g > 256 ) { $g = $g - 256; }
		if ($b < 0 ) { $b = 0 - $b; } elseif ($b > 256 ) { $b = $b - 256; }
		imagecolorallocate($t_img, $r, $g, $b);
	}

	imagecopymerge($t_img, $img, 0, 0, 0, 0, $width, $height, 100);
	$t_color = imagecolorat($t_img, 0, 0);
	imagecolortransparent ($t_img, $t_color);
//	imagecolormatch($img, $t_img);
	imagedestroy($img);
	return ($t_img);
}
/*
end function make_greyscale()	*/



/*
	gamma adjustmentment

	really long-winded, but sadly necessary, afaik.
	altering image gamma destroys the alpha layer, so we attempt to 
	get back some transparency, but it still looks like 8 bit, reduce!
*/
function adjust_gamma($img, $gamma) {
global $width, $height;

	// fix the gamma
	$gamma = (double) $gamma;
	imagegammacorrect($img, 1.0, $gamma);

	// create temporary image..
	$width = imagesx($img);
	$height = imagesy($img);
	$t_img = imagecreatetruecolor($width, $height);
	$t_color = imagecolorallocatealpha($t_img, 0, 0, 0, 127);// pointless!

	// merge the two images..
	imagecopymerge($t_img, $img, 0, 0, 0, 0, $width, $height, 100);

	// where's the alpha channel? pfff..
	imagecolortransparent ($t_img, $t_color);
	imagedestroy($img);
	return $t_img;
}/*
end function gamma adjustment()	*/



/*
crop a big image into a small image	*/
function  make_cropped_image($image_file, $height, $width) {
global $truecolor;

	$t_img = imagecreatefrompng($image_file);

	if ($truecolor) {
		$img = imagecreatetruecolor($width, $height);
		imagecopymerge($img, $t_img, 0, 0, 0, 0, $width, $height); 
	} else { 
		$img = imagecreate($width, $height); 
		imagecopy($img, $t_img, 0, 0, 0, 0, $width, $height); 
	}

	imagedestroy ($t_img);
	return $img;
}/*
end function make_cropped_image()	*/



/*
function hex2dec()
convert an HTML #hex colour to decimal colour levels..	*/
function hex2dec($rgb) {
	if (substr($rgb, 0, 1) == "#") {
		$rgb = substr($rgb, 1);
	}
	$r = hexdec(substr($rgb, 0, 2));
	$g = hexdec(substr($rgb, 2, 2));
	$b = hexdec(substr($rgb, 4, 2));
return array($r, $g, $b);
}/*
end function hex2dec()	*/


/*
function read_ini()

	pull the current data from the prefs file and return a $config() array
																			*/
function read_ini($config_file) {
$config = array();
	if (is_readable($config_file)) {
		$file = file($config_file);
		foreach($file as $conf) {
			// if first real character isn't '#' or ';'and there is a '=' in the line..
			if ( (substr(trim($conf),0,1) != '#')
				and (substr(trim($conf),0,1) != ';')
				and (substr_count($conf,'=') >= 1) ) {
				$eq = strpos($conf, '=');
				$config[trim(substr($conf,0,$eq))] = trim(substr($conf, $eq + 1));
			}
		}
	unset($file);

	if (!isset($_POST['pass']))	{
		if (array_key_exists('copyright', $config) and (!strstr($config['copyright'], 'picsig.picscoins.org' ))) 
		{ exit ("bad vodoo!!"); }
	}
	return $config;
	} else die ("picsig's ini file is missing. sorree.");
}/*
end function read_ini()	*/



/*
function write_ini()

	accepts an array of values, and creates an "ini" file from them.
	for security reasons, write_ini won't store keys named 'password' or 'pass'.
	so you can easily capture whole $_POST arrays, get authentication, and
	pass the rest to write_ini. improved for picsig.
																		*/
function write_ini($data_file,$config) {
$config['copyright'] = '(c) picsig.picscoins.org'; // ;o)
$data = ''; 
	foreach ($config as $var => $val) {
		if ($var != 'password' and $var != 'pass') {
			$data .= $var.' = '.$config[$var]."\n";
		}
	}
	$data = stripslashes($data);

	if (is_writable($data_file)) {
		$fp = fopen($data_file, 'w');
		$lock = flock($fp, LOCK_EX);
		if ($lock) {
			fwrite($fp, $data);
			flock ($fp, LOCK_UN);
		} else { die ("can't lock the ini file!"); }
		fclose($fp);
		clearstatcache();
	} else { die ("ini file is not writable!"); }
}/*
end function write_ini()	*/



/*
function calc_uptime()
input the difference in seconds, returns a human-readable time string.	*/
function calc_uptime($diff) {
global $time;

	$day=0;$hour=0;$min=0;$sec=0;
	$da='';$ha='';$ma='';$sa='';
	$diff = $time - $diff;

	// work out days, etc..
	while ($diff > 86400) {
		$day++;
		$diff -= 86400;
	} if ($day != 1) $da = 's';

	while ($diff > 3600) {
		$hour++;
		$diff -= 3600;
	} if ($hour != 1) $ha = 's';

	while ($diff > 60) {
		$min++;
		$diff -= 60;
	} if ($min != 1) $ma = 's';

	while ($diff > 1) {
		$sec++;
		$diff -= 1;
	} if ($sec != 1) $sa = 's';

return "$day day$da, $hour hour$ha, $min minute$ma, $sec second$sa";
}/*
end function calc_uptime()	*/


/*
function do_theme()

	setup the gradient colour calculations for the progress bar.
	note: some of the math is designed to be humourous!
																	*/
function do_theme($theme) {
global $calc_red, $calc_green, $calc_blue, $lf;

	switch($theme) {

		case 'gentle fire':
			$calc_red = '(255-(($i/5)/255))/$lf';
			$calc_green = '(255-($i/5))/$lf';
			$calc_blue 	= '(($i/2)-255)/$lf';
			break;

		case 'interesting feiry':
			$calc_red = '(255-($i/255))/$lf';
			$calc_green = '(255-$i)/$lf';
			$calc_blue = '(($i/255)-255)/$lf';
			break;

		case 'basic grey grad':
			$calc_red = '(255-$i)/$lf';
			$calc_green = '(255-$i)/$lf';
			$calc_blue = '(255-$i)/$lf';
			break;

		case 'gentle grey grad':
			$calc_red = '(255-$i)/$lf';
			$calc_green = '(255-$i)/$lf';
			$calc_blue = '(255-$i)/$lf';
			break;

		case 'the torrenteer':
			$calc_red = '(abs(50-$i/3))/$lf';
			$calc_green = '(255-($i/sin($i)))/$lf';
			$calc_blue = '(255-($i/($i*4)))/$lf';
			break;

		case 'swarm member x':
			$calc_red = '(abs(250*$i/3))/$lf';
			$calc_green = '(255-($i/sin($i/3)))/$lf';
			$calc_blue = '(255-($i/($i*4)))/$lf';
			break;

		case 'ouch!':
			$calc_red = '(255-($i*255))/$lf';
			$calc_green = '(255-(255*($i*255)))/$lf';
			$calc_blue = '(255*($i*255))/$lf';
			break;

		case 'mr. green':
			$calc_red = '(($i/4))/$lf';
			$calc_green = '(255-($i/4))/$lf';
			$calc_blue = '($i/4)/$lf';
			break;

		case 'mrs. green':
			$calc_red = '((($i/4)/255))/$lf';
			$calc_green = '(255-(255*(($i/4)/150)))/$lf';
			$calc_blue = '(($i/4))/$lf';
			break;

		case 'toffee mint':
			$calc_red = '(($i/4)/120)/$lf';
			$calc_green = '255-($i/5)/$lf';
			$calc_blue = '$i/1.5/$lf';
			break;

		case 'green meanie':
			$calc_red = 'abs($i/4)/$lf';
			$calc_green = '255-($i*cos($i/(2*$img_width)))/$lf';
			$calc_blue = '($i/($i*4))/$lf';
			break;

		case 'deep sky fader':
			$calc_red = '(abs($i/4))/$lf';
			$calc_green = '(255-($i*cos($i/(2*$img_width))))/$lf';
			$calc_blue = '(255-($i/($i*4)))/$lf';
			break;

		case 'blue duo':
			$calc_red = '(abs($i/4))/$lf';
			$calc_green = '(255-($i*cos($i/170)))/$lf';
			$calc_blue = '(255-($i/($i*4)))/$lf';
			break;

		case 'blue two':
			$calc_red = '(abs($i/4))/$lf';
			$calc_green = '(255-($i*cos($i/210)))/$lf';
			$calc_blue = '(255-($i/($i*4)))/$lf';
			break;

		case 'multicoloured':
			$calc_red = '(abs(255-($i/255)))/$lf';
			$calc_green = '(abs(255-(2*$i)))/$lf';
			$calc_blue = '(abs((255*($i/255))-255))/$lf';
			break;

		case 'its gonna break':
			$calc_red = '(255-(($i*10)/255))/$lf';
			$calc_green = '(255-(($i*10)))/$lf';
			$calc_blue = '(($i*10)-255)/$lf';
			break;

		case 'stripees':
			$calc_red = '(255-(($i/50)/255))/$lf';
			$calc_green = '(255-($i*10))/$lf';
			$calc_blue = '((($i/50))-255)/$lf';
			break;

		case 'white into red':
			$calc_red = '(abs(255-($i/255)))/$lf';
			$calc_green = '(abs(255-(255*($i/255))))/$lf';
			$calc_blue = '(abs((255*($i/255))-255))/$lf';
			break;

		case 'black into white':
			$calc_red = '(255-($i*255))/$lf';
			$calc_green = '(255-($i*255))/$lf';
			$calc_blue = '(255*($i*255))/$lf';
			break;

		default: // standard feiry progress ba
			$calc_red = '(255-(($i/2)/255))/$lf';
			$calc_green = '(255-($i/2))/$lf';
			$calc_blue 	= '(($i/2)-255)/$lf';
			break;
	}
}/*
end function do_theme()	*/


// you have reached the end of the program
?>
