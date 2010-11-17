<?php


class Des_Search {


    // Query string
	public $query = '';

    // page number of resultset
    public $page_num = 1;
    
    // Array of words to search
	protected $words = array();

    // Arry to hold results
    protected $results = array();

    // Table to search
    protected $db_table = 'tbl_questions';

    protected $common_words = array('cat', 'dog');

    public $num_results = 0;

    // Array of columns to pull from DB (where match!)
    protected $extract_columns = array(
        'question_id', 'catid', 'userid',
        'question_title', 'question_body',
        'question_replies', 'question_added',
    );

    protected $db_match = array(
        'question_active = "Y"',
    );

    // Structure of results array (key => column)
    protected $results_format = array(
        'id'        => 'question_id',
        'catid'     => 'catid',
        'added'     => 'question_added',
        'userid'    => 'userid',
        'title'     => 'question_title',
        'replies'   => 'question_replies',
        'score'     => 'score',
    );


    // Columns to search on
    protected $search_columns = array(
        array(
            'field' => 'question_title',
            'score' => 2,
        ),
        array(
            'field' => 'question_body',
            'score' => 1,
        ),
    );




    public function __construct($q)
    {
       
        // set query to all lower case
        $this->query = strtolower($q);

        $this->prepareQuery();

        $this->getResults();

        $this->sortResults();

    }

    protected function prepareQuery()
    {
        
        // Set words array from query (respects quotes)
        $this->words = $this->queryToArray();

        $this->removeStopWords();

        // Stem the word array (Porter Stemming)
        //$stemmer = new Stemmer;
        //$this->words = $stemmer->stem_list($this->words);
    }


    private function queryToArray() {

        $stemmer = new Stemmer;
        
        $q = $this->query;

        // Check params okay.
        if (!isset($q) || false === $q || !is_string($q)) {

            return false;
        }

        // Remove trailing spaces
        $x = trim($q);

        // Short circuit if empty
        if ('' === $x) {
            return array();
        }

        $chars = str_split($x);
        $mode = 'normal';
        $token = '';
        $tokens = array();

        for ($i = 0; $i < count($chars); $i++) {

            switch ($mode) {
                case 'normal':
                    if ('"' == $chars[$i]) {
                        if ('' != $token) {

                            $tokens[] = $stemmer->stem($token);
       
                        }
                        $token = '';
                        $mode = 'quoting';
                    }
                    else if (' ' == $chars[$i] || "\t" == $chars[$i] || "\n" == $chars[$i]) {
                        if ('' != $token) {

                            $tokens[] = $stemmer->stem($token);
           
                        }
                        $token = '';
                    }
                    else {
                        $token .= $chars[$i];
                    }
                    break;

                case 'quoting':
                    if ('"' == $chars[$i]) {
                        if ('' != $token) {
                            $tokens[] = $token;
                        }
                        $token = '';
                        $mode = 'normal';
                    }
                    else {
                        $token .= $chars[$i];
                    }
                    break;
            } // switch
        } // foreach

        if ('' != $token) {

            $tokens[] = $stemmer->stem($token);

            //$tokens[] = $token;
        }

        // Return.
        return $tokens;
    }


    protected function getResults()
    {

        //if (count($this->words) < 1) {
        //    return array();
        //}

        // start query
        $sql    = 'SELECT ' . implode(', ', $this->extract_columns) . ' '
                . 'FROM ' . $this->db_table . ' '
                . 'WHERE ';


        // Loop words, building where query
        if (count($this->words) > 0) {

            $sql .= '(';
            
            foreach($this->words AS $word) {

                $sql .= '(';

                foreach($this->search_columns AS $column) {
                    $sql .= $column['field'] . ' LIKE "%' . $word . '%" OR ';
                }

                $sql=substr($sql,0,(strLen($sql)-3)); // Eat last OR

                $sql .= ') OR ';

            }

            $sql=substr($sql,0,(strLen($sql)-3)); // Eat last OR

            $sql .= ')';
        }

        // loop extra fields to match (if present)
        if (is_array($this->db_match) && count($this->db_match) > 0) {

            if (count($this->words) > 0) {
                $sql .= ' AND ';
            }

            $sql .= implode(' AND ', $this->db_match);

        }

        //echo $sql;
        //exit();
        

        $res = mysql_query($sql) or die(mysql_error());
        $num = mysql_num_rows($res);

        $this->num_results = $num;
        

        if ($num > 0) {

            while($row = mysql_fetch_assoc($res)) {

                extract($row);

                // initialise score
                $score = 0;
                
                // calculate relevance score;
                foreach($this->words AS $word) {

                    
                    if (in_array($word, $this->common_words)) {
                        $match_worth = 0.1;
                    }
                    else {
                        $match_worth = 1;
                    }

                    // Loop searched columns
                    foreach($this->search_columns AS $column) {

                        // Count occurances of keyword in column
                        $matches = substr_count(strtolower($$column['field']),$word);

                        // add to score (adjusted for column weight and common word score)
                        $score = $score + (($matches * $column['score']) * $match_worth);

                    }

                }


                $result = array();

                // loop results format, needs to be key => $variable_name
                foreach($this->results_format AS $key => $var) {

                    $result[$key] = $$var; // variable variable
                }

                
                // find id variable for results array
                $column_id = $this->results_format['id'];
                $result_id = $$column_id;

                // add result to array
                $this->results[$result_id] = $result;


            }

        }


    }


    private function removeStopWords()
    {
        $stopwords = array('a', 'about', 'above', 'above', 'across', 'after', 'afterwards', 'again', 'against', 'all', 'almost', 'alone', 'along', 'already', 'also','although','always','am','among', 'amongst', 'amoungst', 'amount',  'an', 'and', 'another', 'any','anyhow','anyone','anything','anyway', 'anywhere', 'are', 'around', 'as',  'at', 'back','be','became', 'because','become','becomes', 'becoming', 'been', 'before', 'beforehand', 'behind', 'being', 'below', 'beside', 'besides', 'between', 'beyond', 'bill', 'both', 'bottom','but', 'by', 'call', 'can', 'cannot', 'cant', 'co', 'con', 'could', 'couldnt', 'cry', 'de', 'describe', 'detail', 'do', 'done', 'down', 'due', 'during', 'each', 'eg', 'eight', 'either', 'eleven','else', 'elsewhere', 'empty', 'enough', 'etc', 'even', 'ever', 'every', 'everyone', 'everything', 'everywhere', 'except', 'few', 'fifteen', 'fify', 'fill', 'find', 'fire', 'first', 'five', 'for', 'former', 'formerly', 'forty', 'found', 'four', 'from', 'front', 'full', 'further', 'get', 'give', 'go', 'had', 'has', 'hasnt', 'have', 'he', 'hence', 'her', 'here', 'hereafter', 'hereby', 'herein', 'hereupon', 'hers', 'herself', 'him', 'himself', 'his', 'how', 'however', 'hundred', 'ie', 'if', 'in', 'inc', 'indeed', 'interest', 'into', 'is', 'it', 'its', 'itself', 'keep', 'last', 'latter', 'latterly', 'least', 'less', 'ltd', 'made', 'many', 'may', 'me', 'meanwhile', 'might', 'mill', 'mine', 'more', 'moreover', 'most', 'mostly', 'move', 'much', 'must', 'my', 'myself', 'name', 'namely', 'neither', 'never', 'nevertheless', 'next', 'nine', 'no', 'nobody', 'none', 'noone', 'nor', 'not', 'nothing', 'now', 'nowhere', 'of', 'off', 'often', 'on', 'once', 'one', 'only', 'onto', 'or', 'other', 'others', 'otherwise', 'our', 'ours', 'ourselves', 'out', 'over', 'own','part', 'per', 'perhaps', 'please', 'put', 'rather', 're', 'same', 'see', 'seem', 'seemed', 'seeming', 'seems', 'serious', 'several', 'she', 'should', 'show', 'side', 'since', 'sincere', 'six', 'sixty', 'so', 'some', 'somehow', 'someone', 'something', 'sometime', 'sometimes', 'somewhere', 'still', 'such', 'system', 'take', 'ten', 'than', 'that', 'the', 'their', 'them', 'themselves', 'then', 'thence', 'there', 'thereafter', 'thereby', 'therefore', 'therein', 'thereupon', 'these', 'they', 'thickv', 'thin', 'third', 'this', 'those', 'though', 'three', 'through', 'throughout', 'thru', 'thus', 'to', 'together', 'too', 'top', 'toward', 'towards', 'twelve', 'twenty', 'two', 'un', 'under', 'until', 'up', 'upon', 'us', 'very', 'via', 'was', 'we', 'well', 'were', 'what', 'whatever', 'when', 'whence', 'whenever', 'where', 'whereafter', 'whereas', 'whereby', 'wherein', 'whereupon', 'wherever', 'whether', 'which', 'while', 'whither', 'who', 'whoever', 'whole', 'whom', 'whose', 'why', 'will', 'with', 'within', 'without', 'would', 'yet', 'you', 'your', 'yours', 'yourself', 'yourselves', 'the');

        foreach($this->words AS $num => $word) {

            // is it a stopword?
            if (in_array($word, $stopwords)) {

                // remove word
                unset($this->words[$num]);
            }
        }
    }


    public function sortResults()
    {
        if ($this->num_results > 0) {
            $this->results = global_sort_array($this->results, 'score', true);
        }
    }

    public function showResults()
    {
        echo '<pre>';
        print_r($this->results);
        echo '</pre>';
    }

    public function getSearchQuery()
    {
        $query = htmlspecialchars($this->query);

        return $query;

    }
    


}