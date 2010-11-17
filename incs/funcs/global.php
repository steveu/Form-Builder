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