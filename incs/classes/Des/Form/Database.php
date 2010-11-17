<?php

/**
 * Extends the base form to INSERT to DB
 * and to optionally pull initial values from DB, and update the Record
 *
 * @author steve
 * @copyright Designition Ltd
 */
class Des_Form_Database extends Des_Form_Base
{

    // Table to Map to (fieldKeys == Column Names)
    protected $dbTable = '';

    // Unique Column, if set will UPDATE rather than INSERT
    /* Format as below
    array( 'field'=>'user_id', 'value'=>1 );
    */
    protected $dbUpdate = array();

    // Fields which must also match
    protected $dbMatch = array();

    // Extra Fields to INSERT/UPDATE (not form inputs)
    // e.g. array('user_active'=>'Y')
    protected $dbFields = array();

    // ID of Inserted Record
    protected $insertedID = false;

    // Only insert, not select
    protected $onlyInsert = false;

    /**
     * Adds a field to be updated alongside the form config ones, e.g. userid = 1
     * 
     * @param string $column
     * @param string/int $value
     */
    public function addUpdateColumn($column, $value)
    {
        $this->dbFields[$column] = $value;
    }


    /**
     * Assigns POST Values If Available, Otherwise Gets from DB
     * @author steve
     */
    protected function assignValues()
    {

        
        $dbGetFields = array();
        $dbFieldsets = array();

        // run parent assigniment (POST if set)
        Des_Form_Base::assignValues();

        // GET or POST
        $valueArray = $this->getMethodArray();

        // If No Values and db row passed, lookup record
        if (empty($valueArray) && count($this->dbUpdate) > 0 && !$this->onlyInsert) {

            
            foreach($this->fieldsets AS $setKey => $setArray) {

                $dbFieldsets[$setKey] = array();

                foreach($setArray['fields'] AS $fieldKey => $fieldArray) {
                    
                    if ($fieldArray['db']) {
                        
                        $dbGetFields[] = $fieldKey;

                        $dbFieldsets[$setKey][] = $fieldKey;
                    }
                }
            }
            
            // build query
            $sql    = 'SELECT ' . implode(", ", $dbGetFields) . ' '
                    . 'FROM ' . $this->dbTable . ' '
                    . 'WHERE ' . $this->dbUpdate['field'] . ' = '
                    . '"' . $this->dbUpdate['value'] . '"';

            // loop extra db fields to match
            $matches = array();
            foreach($this->dbMatch AS $field => $value) {
                $matches[] = $field . ' = ' . '"' . $value . '"';
            }
            if (count($matches) > 0) {
                $sql .= ' AND ' . implode(' AND ', $matches);
            }
            
            $res = mysql_query($sql) or die(mysql_error());
            $num = mysql_num_rows($res);
            if($num > 0) {

                $row = mysql_fetch_assoc($res);
                extract ($row);

                foreach($dbFieldsets AS $setKey => $fieldArray) {

                    foreach($fieldArray AS $fieldKey) {

                        // Assign Database Value
                        $this->fieldsets[$setKey]['fields'][$fieldKey]['value'] = $row[$fieldKey];
                    }
                }
            }

            else {
                echo 'Not found';
                exit();
            }
        }
    }


    /**
     * Process the Form; INSERT OR UPDATE DB
     *
     * @author steve
     */
    public function process()
    {

        // If Passed Update Row, Build UPDATE Query
        if (count($this->dbUpdate) > 0) {

            $updates = array();

            foreach($this->fieldsets AS $setKey => $setArray) {

                foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                    // if db TRUE
                    if ($fieldArray['db']) {

                        // Any Special DB Transformations
                        $fieldArray['value'] = $this->fieldSQLTransform($fieldArray);
      
                        $updates[] = $fieldKey . ' = ' . '"' . $fieldArray['value'] . '"';
                    }
                }
            }


            // loop extra db fields
            foreach($this->dbFields AS $field => $value) {
                
                if ($value=='NOW') {
                    $updates[] = $field . ' = NOW()';
                }
                else {
                    $updates[] = $field . ' = ' . '"' . $val = mysql_real_escape_string($value) . '"';
                }
            }

            // Build Query
            $sql    = 'UPDATE ' . $this->dbTable .' SET '
                    . implode(", ",$updates) . ' '
                    . 'WHERE ' . $this->dbUpdate['field'] . ' = ' . $this->dbUpdate['value'];
        }


        // Else, Build INSERT query
        else {

            $f = '';
            $v = '';

            foreach($this->fieldsets AS $setKey => $setArray) {

                foreach($setArray['fields'] AS $fieldKey => $fieldArray) {

                    if ($fieldArray['db']) {

                        // Any Special DB Transformations
                        $fieldArray['value'] = $this->fieldSQLTransform($fieldArray);

                        $f .= $fieldKey . ', ';

                        $v .= '"' . $fieldArray['value'] . '", ';

                    }
                }
            }

            // loop extra db fields
            foreach($this->dbFields AS $field => $value) {

                $f .= $field . ', ';

                if ($value=='NOW') {
                    $v .= 'NOW(), ';
                }
                else {
                    $v .= '"' . $value . '", ';
                }
               
            }

            // remove trailing commas
            $f = rtrim($f, ', ');
            $v = rtrim($v, ', ');

            // Build Query
            $sql = 'INSERT INTO ' . $this->dbTable . ' (' . $f . ') VALUES (' . $v . ')';

        }

        //echo $sql.'<br />';
        //exit();
        
        // Execute Query
        mysql_query($sql) or die(mysql_error());

        // Updates insertedID property (If INSERT!)
        if (count($this->dbUpdate) < 1) {
            $this->insertedID = mysql_insert_id();
        }

        return true;
    }


    /**
     *
     * @param array $field
     * @return string
     */
    protected function fieldSQLTransform($field) {

        // Return Early if Nothing Required
        if (!$field['sql']) {
            return $field['value'];
        }

        else {

            switch($field['sql']) {

                case 'md5':
                    return md5($field['value']);
                    break;

                case 'price':
                    $val = str_replace(",","",$field['value']);
                    return floatval($val);
                    break;

                case 'checkbox-to-enum':

                    if ($field['value']=='on') return 'Y';
                    else return 'N';

                    break;

                default:
                    return $field['value'];
            }
        }
    }



    
    

}