<?php

/**
 * Magic function, autoloads classes based on class name
 *
 * @param string $className
 * @author steve
 */
function __autoload($className) {

    $classPath = str_replace('_', '/', $className);

    require_once('incs/classes/' . $classPath) . '.php';

}