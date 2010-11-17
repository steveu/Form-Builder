<?php
/* ------------------------------------------------------------------
Copyright: (c) 2004-[-FR-YEAR] Designition Ltd
Author: Designition Ltd (www.designition.co.uk)
Date: [-FR-DATE]
Site: [-FR-NAME]

These PHP Scripts can not be copied, redistributed or reused on any 
web site other than the one they have been installed on. For full
terms and conditions please visit www.designition.co.uk/terms/

Title: Global Functions
Desc: Used within both the admin section and frontend.
------------------------------------------------------------------ */

/**
 * Determines whether or not video ID is from vimeo or youtube. Works because
 * all vimeo IDs are completely numeric, whereas youtubes seem not to be (so
 * far at least)
 *
 * @param <type> $videoId
 * @return string vimeo | youtube
 */
function global_videoSource($videoId)
{
    if ($videoId == preg_replace("%[^0-9]%", "", $videoId)) {
        return 'vimeo';
    }
    else {
        return 'youtube';
    }
}


/**
 * Sets sessions on login
 * 
 * @param <type> $siteConfig
 * @param <type> $row
 */
function global_start_sessions($siteConfig, $row)
{
    $prefix = $siteConfig['website']['session'];
    $_SESSION[$prefix.'_id']        = global_encode($row['user_id']);
    $_SESSION[$prefix.'_fullname']  = global_encode($row['user_fullname']);
    $_SESSION[$prefix.'_username']  = global_encode($row['user_username']);
    $_SESSION[$prefix.'_email']     = global_encode($row['user_email']);
    $_SESSION[$prefix.'_access']    = global_encode($row['user_access']);
}


/**
 * Generates a fake query when passed a string. It cuts it in half if the title
 * is too big. I dont like this, but am unsure if massive URLs are OK?
 *
 * @param <type> $title
 * @return <type> 
 */
function global_fakequery($title)
{
    $title = global_convert2url($title);
    if(strlen($title) > 25) $title = substr($title, 0, 12) . substr($title, -12);
    return $title;
}


/**
 * Returns the global settings from tbl_settings, which include contact details,
 * notification emails, etc. This is called on all pages on the website.
 * 
 * @param array $siteConfig
 * @return array
 */
function global_return_settings($siteConfig)
{
    $sql = 'SELECT general_id, general_key, general_value FROM tbl_settings ';
    $res = mysql_query($sql) or die(mysql_error());
    $num = mysql_num_rows($res);
    if($num > 0) {
        while ($row = mysql_fetch_assoc($res)) {
            extract ($row);
            $array[$general_key] = $general_value;
        }
    }
    return $array;
}


/**
 * Cuts a string after a given number of characters
 *
 * @param string $string value
 * @param int $num number of characters you want
 * @param bool $dots whether you want three dots at the end,
 * if the values length is greater than $num
 * @return string
 */
function global_trim_string($string, $num, $dots = true)
{
    $done = 0;
    $letters = 0;
    $sentence = '';
    $words = explode(" ", trim($string));
    $totalwords = count($words);
    for($i = 0; $i < $totalwords; $i++) {
        $word_array = preg_split('//', $words[$i], -1, PREG_SPLIT_NO_EMPTY);
        $letters = $letters + count($word_array);
        if (($letters > $num) && ($done == 0)) {
            $sentence = trim($sentence);
            if($dots == true) {
                $sentence.= "&hellip;";
            }
            $done = 1;
        }
        if ($done == 0) {
            $sentence .= $words[$i] . " ";
        }
    }
    return ($sentence);
}


/**
 * Does X = Y?
 * 
 * @todo test this actually works as expected?
 * @param array|string $this X
 * @param array|string $that Y
 * @param string $what selected / checked / other (other = class name)
 * @param bool $attr should attribute (e.g. class="") be included or not?
 * @return string examples: class="active", selected="selected", checked="checked" or just active
 */
function global_is($this, $that, $what, $attr = true)
{
    if ($this == $that && $what == 'selected') {
        $key = $what;
        $value = $what;
    }
    else if ($this == $that && $what == 'checked') {
        $key = $what;
        $value = $what;
    }
    else if ($what == 'even' || $what == 'odd') {
        if ($this % $that) {
            $key = 'class';
            $value = $what. ' ' . $that . ' ' . $that;
        }
    }
    else if (is_array($this) && $this[$that] && $what == 'error')  {
        $value = $what;
    }
    else if ($this == $that) {
        $value = $what;
    }
    else if (is_array($that) && in_array($this, $that)) {
        $value = $what;
    }
    if (!$key) $key = 'class';
    if ($attr == true && $value) {
        return ' ' . $key . '="' . $value . '"';
    }
    else if ($value) {
        return ' ' . $what;
    }
}


/**
 * Adds plural 's' onto string based on given value
 * @param int $value
 * @return string s
 */
function global_s($value)
{
    echo 'Replaced with global_pluralise';
    exit;
    if($value != 1) return 's';
}


/**
 * Get a specific variable within a passed array
 *
 * @param array $array the array to look in
 * @param string $known the array key you already know
 * @param mixed $unique the value of the array key you know
 * @return array
 */
function global_return_vars($array, $known, $unique)
{
    foreach ($array as $key => $val) {
        if ($val[$known] == $unique) {
            return $val;
        }
    }
}


/**
 * Delete a file, if it exists.
 * 
 * @param string $filePath the full root path of the file
 */
function global_delete_file($filePath)
{
    if (file_exists($filePath)) unlink($filePath);
}


/**
 * Encode value (needs more work, as easy to get)
 *
 * @param string $value the value you want encoded
 * @return string the encoded version
 */
function global_encode($value)
{
    $value = $value.CONFIG_SETTINGS_HASH;
    return base64_encode($value);
}


/**
 * Decode value (needs more work, as easy to get)
 *
 * @param string $value the value you want decoded
 * @return string the decoded value
 */
function global_decode($value)
{
    $value = base64_decode($value);
    $len = strlen(CONFIG_SETTINGS_HASH);
    $value = substr($value, 0, -$len);
    return $value;
}


/**
 * Converts
 *
 * @todo when needed, account for currency symbols that appear on the right
 * @param int|decimal $amount the value
 * @param string $currency the currency symbol. defaults at sterling
 * @return <type>
 */
function global_price($amount, $currency = false)
{
    if (!$currency) $currency = '&pound;';
    return $currency.number_format($amount, 2, '.', ',');
}


/**
 * Returns formatted filesize for documents
 *
 * @param int $fileSize the filesize in bytes
 * @return string the filesize in megabytes or kilobytes
 */
function global_return_filesize($fileSize)
{
    $fileSize = ($fileSize / 1024);

    if($fileSize > 1000) {
        $return = round(($fileSize/1024),2).'MB';
    }
    elseif($fileSize > 100) {
        $return = round($fileSize,0).'kb';
    }
    else {
        $return = round($fileSize,2).'kb';
    }
    return $return;
}


/**
 * Round a number up. Useful for VAT values
 *
 * @param decimal $value value
 * @param int $dp number of decimal points
 * @return <type>
 */
function global_roundup($value, $dp)
{
    $value = number_format($value, 2, '.', '');
    return ceil($value*pow(10, $dp))/pow(10, $dp);
}


/**
 * Convert string to URL. Generally used when items are added/updated through
 * the CMS, in which case the database query field will be updated too.
 * 
 * @param <type> $value
 * @return <type>
 */
function global_convert2url($value)
{
    $value = trim($value);
    $value = str_replace('-', '', $value);
    $value = str_replace(' ', '-', $value);
    $value = strtolower($value);
    $value = ereg_replace("[^0-9a-z-]", "", $value);
    $value = str_replace('--', '-', $value);
    return $value;
}


/**
 * Converts a date/datetime into a given format
 * @param date $datestamp the original date value
 * @param string $format the format you want it returned in (e.g. jS M Y)
 * @return string 
 */
function global_convert_datetime($datestamp, $format)
{
    if ($datestamp!=0) {
        list ($date, $time)=split(" ", $datestamp);
        list ($year, $month, $day)=split("-", $date);
        if ($time) {
            list($hour, $minute, $second)=split(":", $time);
        }
        else {
            $hour='0000';
            $minute='00';
            $second='00';
        }
        $stampeddate=mktime($hour,$minute,$second,$month,$day,$year);
        $datestamp=date($format,$stampeddate);
        return $datestamp;
    }
}


/**
 * Sorts an associative array by column in either direction
 *
 * @param <type> $the_array
 * @param <type> $column
 * @param <type> $desc
 * @return <type>
 */
function global_sort_array($the_array, $column, $desc = false)
{
    $the_array = (array) $the_array;
    $column = (string) trim($column);
    $desc = (bool) $desc;
    $str_sort_type = ($desc) ? SORT_DESC : SORT_ASC;

    foreach ($the_array as $key => $row) {
        ${$column}[$key] = $row[$column];
    }

    array_multisort($$column, $str_sort_type, $the_array);

    return $the_array;
}


/**
 * Pluralises a word when given a count value. Can be used for words where the
 * plural adds an "s" on the end, or more complicated ones such as category and
 * categories.
 *
 * @param int $count total
 * @param string $singular singular word (e.g. category)
 * @param string $plural Optional, plural word (e.g. categories)
 * @return <type>
 */
function global_pluralise($count, $singular, $plural = false)
{
    if (!$plural) $plural = $singular . 's';
    return ($count == 1 ? $singular : $plural);
}


/**
 * Generates a gravatar URL when passed an email
 * 
 * @param string $email email address
 * @param int $size Optional, gravatar width. Default is 40 pixels
 * @param <type> $default
 * @return string the gravatar URL
 */
function global_gravatar($email, $size = 40, $default = false)
{
    $grav_url = "http://www.gravatar.com/avatar/" . md5( strtolower( $email ) );
    if ($default) {
        $grav_url.= "?default=" . urlencode( $default );
    }
    $grav_url.= "&size=" . $size;
    return $grav_url;
}


/**
 * Inserts a record into the activity table when something is done on the front
 * end or back end. Rarely used.
 *
 * @param <type> $siteConfig
 * @param <type> $insertArray
 */
function global_insert_activity($siteConfig, $insertArray)
{
    $insertSql = 'INSERT INTO tbl_admin_activity (activity_date, activity_title, activity_user, activity_link, activity_desc) VALUES (';
    $insertSql.= '"'.date('Y-m-d H:i:s').'", "'.$insertArray['title'].'", "'.$insertArray['user'].'", "'.$insertArray['link'].'", "'.$insertArray['desc'].'")';
    mysql_query($insertSql);
}


/**
 * Displays errors when passed an array.
 *
 * @param array $errors
 */
function global_display_errors($errors)
{
    if ($errors['intro']) {
        $intro = $errors['intro'];
        unset($errors['intro']);
    }
    if ($errors['outro']) {
        $outro = $errors['outro'];
        unset($errors['outro']);
    }
    if (count($errors) > 0) {
        echo "\t\t".'<div class="alert warning">'."\n";
            if ($intro) echo $intro;
            echo "\t\t\t".'<ul>'."\n";
            foreach ($errors AS $field=>$message) {
                echo "\t\t\t\t".'<li>'.$message.'</li>'."\n";
            }
            echo "\t\t\t".'</ul>'."\n";
            if ($outro) echo $outro;
        echo "\t\t".'</div>'."\n";
    }
}


/**
 * Clean values of an array
 *
 * @param array $values
 * @return array
 */
function global_clean_values($values)
{
    if($values) {
        foreach($values AS $key=>$val) {
            $values[$key] = stripslashes($val);
        }
    }
    return $values;
}


function global_clean($val) {

    if (get_magic_quotes_gpc()) $val = stripslashes($val);

    $val = trim($val);

    $val = htmlentities($val);

    return $val;
}


/**
 * Checks for the existance of an image given parameters in an array and
 * returns either the HTML to display the image, a default image, or attributes
 * of the image.
 *
 * @todo remove the fact it has to look for two types of image (gif / jpg)
 * @todo add support for .png
 *
 * @param array $config CONFIGURATION ARRAY, CONTAINING:
 * @param string $directory the directory its in within assets (e.g. thumbs)
 * @param string $filename the filename of the image (e.g. news)
 * @param int $id the id of the image
 * @param bool $refresh if true, a random query will be added to end to prevent the image being cached
 * @param string $style the style value for the returned html
 * @param string $class the class value for the returned html
 * @param string $title the title value for the returned html
 * @param bool $attributes If true, will return an array containing the images path, width, height and html
 * @param array $default an array containing the following:
 * @param string $file the filename (file must be in /img/design/)
 * @param int $width the width of the default image
 * @param int $height the height of the default image
 *
 * @return array|string either the HTML to display the image or an array containing the HTML and its attributes.
 */
function global_return_image($config)
{
    $imageJpg = CONFIG_SETTINGS_IMAGES.$config['directory'].'/'.$config['filename'].$config['id'].'.jpg';
    $imageGif = CONFIG_SETTINGS_IMAGES.$config['directory'].'/'.$config['filename'].$config['id'].'.gif';

    if (file_exists($imageJpg)) {
        $imagePath = $imageJpg;
        $imageExtension = 'jpg';
    }
    else if (file_exists($imageGif)) {
        $imagePath = $imageGif;
        $imageExtension = 'gif';
    }

    if ($imagePath) {
        $size = GetImageSize($imagePath);
        list($foo,$width,$bar,$height) = explode("\"",$size[3]);
        $array['image'] = '<img src="/assets/'.$config['directory'].'/'.$config['filename'].$config['id'].'.'.$imageExtension;
        if ($config['refresh'] == true) $array['image'].= '?'.date("dHis");

        $array['image'].= '" width="'.$width.'" height="'.$height.'" ';

         // alt="" is mandatory - will be empty if not configured
        $array['image'].= 'alt="'.$config['alt'].'" ';

        if($config['style']) $array['image'].= 'id="'.$config['style'].'" ';
        if($config['class']) $array['image'].= 'class="'.$config['class'].'" ';
        if($config['title']) $array['image'].= 'title="'.$config['title'].'" ';
        $array['image'].= '/>';
    }
    else {
        $array['image'] = false;
    }

    if ($config['attributes'] == true && $array['image']) {
        $attributesArray = array(
            'path' => '/assets/'.$config['directory'].'/'.$config['filename'].$config['id'].'.'.$imageExtension,
            'width' => $width,
            'height' => $height,
            'image' => $array['image']
        );

        $array['image'] = $attributesArray;
    }

    if ($config['default']['file'] && !$array['image']) {
        $array['image'] = '<img src="/img/design/'.$config['default']['file'].'" width="'.$config['default']['width'].'" height="'.$config['default']['height'].'" alt="'.$config['alt'].'" ';
        if ($config['style']) $array['image'].= 'id="'.$config['style'].'" ';
        if ($config['class']) $array['image'].= 'class="'.$config['class'].'" ';
        if ($config['title']) $array['image'].= 'title="'.$config['title'].'" ';
        $array['image'].= '/>';
    }

    return $array['image'];
}


/**
 * Resizes an image
 *
 * @param array $config CONFIGURATION ARRAY, CONTAINING:
 * @param int $orig_height original height
 * @param int $orig_width original width
 * @param int $new_height new height
 * @param int $new_width new width
 * @param string $resizeby how the image should be resized
 * <br/>width = by width. height will be caculated
 * <br/>height = by height. height will be caculated
 * <br/>either = by whichever is greater, so new image sits within specified
 * height and width
 * <br/>crop-XXX = center, north, south, east, west
 * @param string $extension the original extension
 * @param int $quality resize quality (percent)
 * @param string $orig_path full root path of original file
 * @param string $new_path full root path of new file
 */
function global_resize_image($config)
{

    $thisRatio = ($config['orig_height']/$config['orig_width']);

    $photoRatio = ($config['new_height']/ $config['new_width']);

    $fullWidth = $config['new_width']; $fullHeight = $config['new_height'];

    if ($config['resizeby'] == 'width') {
        $fullHeight = (int)(($config['orig_height'] * $config['new_width'])/ $config['orig_width']);
    }
    elseif ($config['resizeby'] == 'height') {
        $fullWidth = (int)(($config['orig_width'] * $config['new_height'])/ $config['orig_height']);
    }
    elseif ($config['resizeby'] == 'either') {
        if ($photoRatio > $thisRatio) {
            $fullHeight = (int)(($config['orig_height'] * $config['new_width'])/ $config['orig_width']);
        } 
        else {
            $fullWidth = (int)(($config['orig_width'] * $config['new_height'])/ $config['orig_height']);
        }
    }
    elseif (substr($config['resizeby'],0,4) == 'crop') {
        $wantRatio = ($config['new_width']/$config['new_height']);
        $cropDirection = str_replace('crop-','',$config['resizeby']);
        if (($config['orig_height']*$wantRatio) > $config['orig_width']) {
            $fullHeight = (int)(($config['orig_height'] * $config['new_width'])/ $config['orig_width']);
        }
        else {
            $fullWidth = (int)(($config['orig_width'] * $config['new_height'])/ $config['orig_height']);
        }
        $fullCrop = true;
    }

    // the resize
    if($fullCrop==true) { $fullQuality = 100; } else { $fullQuality = $config['quality']; }
    
    $commandResize = 'convert -quality '.$fullQuality.' -scale '.$fullWidth.'!x'.$fullHeight.'! ';
    $commandResize.= $config['orig_path'].' '.$config['extension'].':'.$config['new_path'];
    exec($commandResize);

    if($fullCrop == true) {

        // Local
        if (CONFIG_SETTINGS_DEVMODE == 'Y') {
            $commandCrop = 'convert    -quality '.$config['quality'].' '.$config['new_path'].' -gravity '.$cropDirection;
            $commandCrop.= ' -crop '.$config['new_width'].'x'.$config['new_height'].'+0+0 -page +0+0 '.$config['new_path']; // works on local server
        }
        // Bytemark / Clic
        else {
            $commandCrop = 'convert    -quality '.$config['quality'].' '.$config['new_path'].' -gravity '.$cropDirection;
            $commandCrop.= ' -crop '.$config['new_width'].'x'.$config['new_height'].'+0+0 +repage '.$config['new_path'];
        }

        // Alt
        //$commandCrop.= ' -crop '.$config['new_width'].'x'.$config['new_height'].'+0+0 '.$config['new_path']; // works on live server (i think?)

        exec($commandCrop);
    }

}


/**
 * Returns a link - dont think we use this anymore
 * 
 * @param <type> $url
 * @param <type> $title
 * @param <type> $class
 * @return <type>
 */
function global_return_link($url, $title, $class = false)
{
    $display = str_replace('http://','',$url);
    $direct = 'http://'.$display;
    if($title) $display = $title;
    $return = '<a href="'.$direct.'"';
    if($class) $return.= ' class="'.$class.'"';
    $return.= '>'.$display.'</a>';
    return $return;
}


/**
 * Create SQL values in SQL statements
 *
 * I have no idea where this came from, but it probably needs looking at.
 *
 * @param <type> $input values
 * @param string $type text | long | int | double | date | defined
 * @param <type> $defined
 * @param <type> $notdefined
 * @return <type>
 */
function create_sql_value($input, $type, $defined = "", $notdefined = "")
{
    $input = (!get_magic_quotes_gpc()) ? addslashes($input) : $input;
    switch ($type) {
        case "text": $input = ($input != "") ? "'" . $input . "'" : "NULL"; break;
        case "long":
        case "int": $input = ($input != "") ? intval($input) : "NULL"; break;
        case "double": $input = ($input != "") ? "'" . doubleval($input) . "'" : "NULL"; break;
        case "date": $input = ($input != "") ? "'" . $input . "'" : "NULL"; break;
        case "defined": $input = ($input != "") ? $defined : $notdefined; break;
    }
    if($type) {
        $input = convert_smart_quotes($input);
    }
    return $input;
}


/**
 *
 * @param <type> $string
 * @return <type>
 */
function convert_smart_quotes($string)
{ 
    $search = array(
        chr(145), 
        chr(146), 
        chr(147), 
        chr(148), 
        chr(151),
        '�',
        '& ',
//        '"'
    );
    $replace = array(
        '&lsquo;', 
        '&rsquo;', 
        '&ldquo;', 
        '&rdquo;', 
        '&mdash;',
        '&pound;',
        '&amp; ',
//        '&quot;'
    );
    return str_replace($search, $replace, $string); 
} 


/**
 * Generates a random password
 *
 * @param int $length the length of the string you want?
 * @return string
 */
function global_generate_password($length)
{ 
  $salt = "abchefghjkmnpqrstuvwxyzABCHEFGHJKMNPQRSTUVWXYZ0123456789";
  srand((double)microtime()*1000000); 
  $i = 0;
  while ($i <= $length) {
    $num = rand() % 62;
    $tmp = substr($salt, $num, 1);
    $pass = $pass . $tmp;
    $i++;
  }
  return $pass;
}


/**
 * Wraps non HTMLised text in paragraphs acording to page breaks
 * 
 * @param <type> $string
 * @return <type>
 */
function global_nl2p($string)
{
    if (strlen($string) > 1) {
        $string = '<p>' . preg_replace('#(<br\s*?/?>\s*?){2,}#', '</p>' . "\n" . '<p>', nl2br($string)) . '</p>';
        return $string;
    }
}


/**
 * ----------------------------------------------------------------------------
 * Functions below this point are likely ones I don't know if are being used
 * or not - when you next look through, if they've caused no issues. Get rid!
 * ----------------------------------------------------------------------------
 */


/**
 * Replaced pre 19/08/2010
 *
 * @todo Remove this function
 * @deprecated replaced by global_generate_password
 * @param <type> $length
 * @return <type>
 */
function generate_password($length)
{
    if (CONFIG_SETTINGS_DEVMODE == 'Y') {
        echo '<h1>This function has been replaced by global_generate_password</h1>';
        exit;
    }
    else {
        return global_generate_password($length);
    }
}

/**
 *
 * @param <type> $text
 * @return <type>
 */
function global_hyperlink(&$text)
{
    // $line = "Check the links: www.yahoo.com http://www.php.net";
    // echo hyperlink($line);

    // match protocol://address/path/
    $text = ereg_replace('[a-zA-Z]+://([.]?[a-zA-Z0-9_/-])*', '<a rel="nofollow" href="\\0">\\0</a>', $text);

    // match www.something
    $text = ereg_replace('(^| )(www([.]?[a-zA-Z0-9_/-])*)', '\\1<a rel="nofollow" href="http://\\2">\\2</a>', $text);

    return $text;
}
