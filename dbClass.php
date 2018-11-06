<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
include_once FJ_ADMIN_PATH . '/connect.php';
require_once '/var/www/virtual/fjServer/config.php';

class DBPolls {

    private $pdo;
    private $dbo;
    public $lastQueyTime = 0;

    public function __construct() {
        global $db_fja;
        $this->pdo = $db_fja;
        $this->dbo = new Dbo($this->pdo);
    }

    public function getAdminPolls() {
        $polls = $this->getAllPolls();



        if (!empty($polls)) {
            foreach ($polls as $key => $poll) {
                $polls[$key]['locales'] = $this->getPollLocales($poll['id']);
                $polls[$key]['coins'] = $this->getPollCoins($poll['id']);
                if (!empty($polls[$key]['coins'])) {
                    foreach ($polls[$key]['coins'] as $k => $c) {
                        $polls[$key]['coins'][$k]['locales'] = $this->getCoinLocales($c['id']);
                    }
                }
            }
        }




        return $polls;
    }

    protected function getPollLocales($pollId) {
        $stmt = $this->pdo->prepare(''
                . 'SELECT pl.* '
                . ' FROM  polls_polls_locale AS pl  '
                . ' WHERE pl.poll_id=:poll_id'
                . ''
                . ' ORDER BY pl.id DESC'
                . '');
        $stmt->bindValue(':poll_id', $pollId, PDO::PARAM_INT);
        $stmt->execute();
        $ret = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $ret;
    }

    protected function getCoinLocales($coinId) {
        $stmt = $this->pdo->prepare(''
                . 'SELECT pl.* '
                . ' FROM  polls_coins_locale AS pl  '
                . ' WHERE pl.coin_id=:coin_id'
                . ''
                . ' ORDER BY pl.id DESC'
                . '');
        $stmt->bindValue(':coin_id', $coinId, PDO::PARAM_INT);
        $stmt->execute();
        $ret = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $ret;
    }

    protected function getPollCoins($pollId) {
        $stmt = $this->pdo->prepare(''
                . 'SELECT p.* '
                . ' FROM  polls_coins AS p  '
                . ' WHERE p.poll_id=:poll_id'
                . ''
                . ' ORDER BY p.id DESC'
                . '');
        $stmt->bindValue(':poll_id', $pollId, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllPolls($locale = 'en') {
        $stmt = $this->pdo->prepare(''
                . 'SELECT p.* '
                . ',p.title as admin_title'
                . ',pl.title as title'
                . ',pl.description as description'
                . ' FROM `polls_polls` as p '
                . ' LEFT JOIN polls_polls_locale AS pl ON pl.poll_id=p.id '
                . ' WHERE pl.locale_code=:locale'
                . ''
                . ' GROUP BY p.id '
                . ' ORDER BY p.id DESC'
                . '');
        $stmt->bindValue(':locale', $locale, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPollsVotes($data = array(), $order_by, $order_type, $locale = 'en') {

        $where = array();


        $start_date = (isset($data['start_date'])) ? $data['start_date'] . ":00" : '';
        $end_date = (isset($data['end_date']) && !empty($data['end_date'])) ? $data['end_date'] . ":00" : '';

        if ($start_date != '') {
            $where[] = "  v.create_date >='" . $start_date . "'";
        }

        if ($end_date != '') {
            $where[] = "  v.create_date <='" . $end_date . "'";
        }

        if (isset($data['user_name']) && $data['user_name'] != '') {
            $where[] = "u.player LIKE '%" . $data['user_name'] . "%'";
        }

        if (isset($data['email']) && $data['email'] != '') {
            $where[] = " u.email LIKE '%" . $data['email'] . "%'";
        }

        if (!empty($data[poll_id])) {
            $where[] = '`v`.`poll_id`= :poll_id ';
            $bindings['poll_id'] = $data[poll_id];
        }

        $where = !empty($where) ? ' WHERE ( ' . implode(' ) AND ( ', $where) . ' ) ' : '';

        $sort_array = array('', 'pl.title', 'cl.title', 'u.player', '', 'u.reged', 'u.last_login_ip', 'v.ip_long', '', '', '', '', 'v.create_date');


        if (isset($sort_array[$order_by]) && $sort_array[$order_by] != '') {
            $sort = " ORDER BY " . $sort_array[$order_by] . " " . $order_type;
        } else {
            $sort = '';
        }


        $stmt = $this->pdo->prepare('SELECT v.* '
                . ', cl.title AS coin_title'
                . ', p.title AS poll_admin_title'
                . ', pl.title AS poll_title'
                . ', u.email'
                . ', u.player'
                . ', u.last_login_ip'
                . ', u.reged AS registration_date'
                . ', ud.phone AS phone'
                . ' FROM `polls_votes` AS `v`'
                . ' LEFT JOIN fortunejack.users AS u ON u.pid = v.user_id'
                . ' LEFT JOIN fortunejack.users_data AS ud ON ud.user_id = v.user_id'
                . ' LEFT JOIN  `polls_coins_locale` AS `cl` ON `cl`.`coin_id`=`v`.`coin_id`'
                . ' LEFT JOIN `polls_polls` as `p` ON `p`.`id`=`v`.`poll_id`'
                . ' LEFT JOIN `polls_polls_locale` as `pl` ON `pl`.`poll_id`=`v`.`poll_id`'
                . $where
                . ' GROUP BY `v`.`id`'
                . $sort
                . ''
        );
        if (!empty($bindings)) {
            foreach ($bindings as $key => $one) {
                $stmt->bindValue(':' . $key, $one, PDO::PARAM_STR);
            }
        }

        $stmt->execute();

        $ret = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $ret;
    }

    public function getPollResult($pollId, $locale = 'en') {

        $stmt = $this->pdo->prepare('SELECT `c`.*'
                . ', `l`.`title`'
                . ', `l`.`description`'
                . ', count(*) AS total'
                . ' FROM `polls_coins` AS `c`'
                . ' LEFT JOIN  `polls_coins_locale` AS `l` ON `l`.`coin_id`=`c`.`id`'
                . ' LEFT JOIN `polls_votes` as `v` ON `c`.`id`=`v`.`coin_id`'
                . ' WHERE `v`.`poll_id`=:id'
                . ' GROUP BY `c`.`id`'
                . ' ORDER BY `c`.`ordering`'
                . ''
        );
        $stmt->bindValue(':id', $pollId, PDO::PARAM_INT);
        $stmt->execute();
        $ret = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $ret;
    }

    public function savePoll($data) {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $poll = $this->dbo->read('fortunejack.polls_polls', $data->id)->fetch();


        // save poll
        $pollColumns = $this->dbo->getTableColumns('fortunejack.polls_polls');
        foreach ($pollColumns as $key => $value) {
            if ($key == 'id' || $key == 'create_date') {
                unset($pollColumns[$key]);
                continue;
            }
            if (isset($data->$key)) {
                $pollColumns[$key] = $data->$key;
            } else {
                $pollColumns[$key] = NULL;
                unset($pollColumns[$key]);
            }
        }

        try {
            if (!empty($data->id)) {
                $res = $this->dbo->update('fortunejack.polls_polls', $pollColumns, $data->id);
            } else {
                $res = $this->dbo->create('fortunejack.polls_polls', $pollColumns);
            }
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        }

        $pollId = (!empty($data->id)) ? $data->id : $this->dbo->id();

        // save poll locales
        if (!empty($data->localesFormgroup)) {
            foreach ($data->localesFormgroup as $one) {
                $pollLocaleColumns = $this->dbo->getTableColumns('fortunejack.polls_polls_locale');
                foreach ($pollLocaleColumns as $key => $value) {
                    if ($key == 'id' || $key == 'create_date') {
                        unset($pollLocaleColumns[$key]);
                        continue;
                    }
                    if (isset($one->$key)) {
                        $pollLocaleColumns[$key] = $one->$key;
                    } else {
                        $pollLocaleColumns[$key] = NULL;
                        unset($pollLocaleColumns[$key]);
                    }
                }
                $pollLocaleColumns['poll_id'] = $pollId;

                if (!empty($one->id)) {
                    $res = $this->dbo->update('fortunejack.polls_polls_locale', $pollLocaleColumns, $one->id);
                } else {
                    $res = $this->dbo->create('fortunejack.polls_polls_locale', $pollLocaleColumns);
                }
            }
        }


        // save coins
        if (!empty($data->coinsFormgroup)) {
            foreach ($data->coinsFormgroup as $one) {


                if (!empty($one->file)) {


                    $img = str_replace('data:image/png;base64,', '', $one->file);
                    $img = str_replace(' ', '+', $img);
                    $dataImage = base64_decode($img);

                    $im = imagecreatefromstring($dataImage);
                    $ext = 'png';

                    $fh = fopen('php://memory', 'rw');
                    fwrite($fh, $dataImage);
                    rewind($fh);

                    if (!empty($ext)) {
                        ini_set('display_errors', 1);
                        ini_set('display_startup_errors', 1);
                        error_reporting(E_ALL);

                        $ch = curl_init();

                        $remoteFile = md5($one->file . rand(1, 1000000)) . '.' . $ext;


                        curl_setopt($ch, CURLOPT_URL, Config::services()['cdnWebDav']['url'] . '/img/' . $remoteFile);
                        curl_setopt($ch, CURLOPT_USERPWD, Config::services()['cdnWebDav']['user'] . ":" . Config::services()['cdnWebDav']['pass']);
                        curl_setopt($ch, CURLOPT_PUT, 1);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                        //   $fh_res = fopen($target_file, 'r');

                        curl_setopt($ch, CURLOPT_INFILE, $fh);
                        curl_setopt($ch, CURLOPT_INFILESIZE, strlen($dataImage));

                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE); // --data-binary

                        $curl_response_res = curl_exec($ch);
                        if (curl_errno($ch)) {
                            $err = curl_error($ch);
                        }
                        if (strpos($curl_response_res, '<h1>Created</h1>')) {
                            $one->icon = 'img/' . $remoteFile;
                        }
                    }
                }


                $pollLocaleColumns = $this->dbo->getTableColumns('fortunejack.polls_coins');
                foreach ($pollLocaleColumns as $key => $value) {
                    if ($key == 'id' || $key == 'create_date') {
                        unset($pollLocaleColumns[$key]);
                        continue;
                    }
                    if (isset($one->$key)) {
                        $pollLocaleColumns[$key] = $one->$key;
                    } else {
                        $pollLocaleColumns[$key] = NULL;
                        unset($pollLocaleColumns[$key]);
                    }
                }

                if (!empty($one->id)) {
                    $res = $this->dbo->update('fortunejack.polls_coins', $pollLocaleColumns, $one->id);
                } else {
                    $res = $this->dbo->create('fortunejack.polls_coins', $pollLocaleColumns);
                }

                $coinId = (!empty($one->id)) ? $one->id : $this->dbo->id();


                //save coins locales

                if (!empty($one->localesFormGroup)) {
                    foreach ($one->localesFormGroup as $two) {
                        $pollCoinLocaleColumns = $this->dbo->getTableColumns('fortunejack.polls_coins_locale');

                        foreach ($pollCoinLocaleColumns as $lKey => $lValue) {
                            if ($lKey == 'id' || $lKey == 'create_date') {
                                unset($pollCoinLocaleColumns[$lKey]);
                                continue;
                            }
                            if (isset($two->$lKey)) {
                                $pollCoinLocaleColumns[$lKey] = $two->$lKey;
                            } else {
                                $pollCoinLocaleColumns[$lKey] = NULL;
                                unset($pollCoinLocaleColumns[$lKey]);
                            }
                        }
                        if (!empty($one->id)) {
                            $res = $this->dbo->update('fortunejack.polls_coins_locale', $pollCoinLocaleColumns, $two->id);
                        } else {
                            $res = $this->dbo->create('fortunejack.polls_coins_locale', $pollCoinLocaleColumns);
                        }
                    }
                }
            }
        }

        return true;
    }

    public function getFileTipe($encoded) {

        $result = null;

        $ar = explode(';base64', $encoded);



        if (strpos(strtolower($ar[0]), 'image/jpeg')) {
            return 'jpeg';
        }

        if (strpos(strtolower($ar[0]), 'mage/png')) {
            return 'jpeg';
        }
        return false;
    }

    public function getImageData($encoded) {
        $result = null;

        $ar = explode('base64', $encoded);


        if (count($ar) > 1) {

            if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == 'Debug') {
                echo '<pre>' . __FILE__ . ' -->>| <b> Line </b>' . __LINE__ . '</pre><pre>';
                print_r($ar[count($ar) - 1]);
                die;
            }

            return $ar[count($ar) - 1];
        }
        return false;
    }

}

class Dbo {
    /* Configuration */

    /**
     * Configuration storage
     * @var array
     */
    protected static $config = array(
        'driver' => 'mysql'
        , 'host' => 'localhost'
        , 'port' => 3307
        , 'fetch' => 'stdClass'
    );

    /**
     * Get and set default Db configurations
     * @uses   static::config
     * @param  string|array $key   [Optional] Name of configuration or hash array of configurations names / values
     * @param  mixed        $value [Optional] Value of the configuration
     * @return mixed        Configuration value(s), get all configurations when called without arguments
     */
    static public function config($key = null, $value = null) {
        if (!isset($key))
            return static::$config;
        if (isset($value))
            return static::$config[(string) $key] = $value;
        if (is_array($key))
            return array_map('static::config', array_keys((array) $key), array_values((array) $key));
        if (isset(static::$config[$key]))
            return static::$config[$key];
    }

    /* Static instances */

    /**
     * Multiton instances
     * @var array
     */
    protected static $instance = array();
    protected static $arguments = array('driver', 'host', 'database', 'user', 'password');

    /**
     * Get singleton instance
     * @uses   static::config
     * @uses   static::__construct
     * @param string $driver   [Optional] Database driver
     * @param string $host     [Optional] Database host
     * @param string $database [Optional] Database name
     * @param string $user     [Optional] User name
     * @param string $pass     [Optional] User password
     * @return Db Singleton instance
     */
    static public function __callStatic($name, $config) {
        if (isset(static::$instance[$name]))
            return static::$instance[$name];
        $config = array_merge(
                static::config(), array_filter(array_combine(static::$arguments, $config + array_fill(0, count(static::$arguments), null)))
        );
        return static::$instance[$name] = new static($config['driver'], $config['host'], $config['database'], $config['user'], $config['password']);
    }

    /* Constructor */

    /**
     * Database connection
     * @var PDO
     */
    protected $db;

    /**
     * Latest query statement
     * @var PDOStatement
     */
    protected $result;

    /**
     * Database information
     * @var stdClass
     */
    protected $info;

    /**
     * Statements cache
     * @var array
     */
    protected $statement = array();

    /**
     * Tables shema information cache
     * @var array
     */
    protected $table = array();

    /**
     * Primary keys information cache
     * @var array
     */
    protected $key = array();

    /**
     * Constructor
     * @uses  PDO
     * @throw PDOException
     * @param string $driver   Database driver
     * @param string $host     Database host
     * @param string $database Database name
     * @param string $user     User name
     * @param string $pass     [Optional] User password
     * @see   http://php.net/manual/fr/pdo.construct.php
     * @todo  Support port/socket within DSN?
     */
    public function __construct($driver, $host, $database, $user, $password = null) {


        if ($driver instanceof PDO) {
            $this->db = $driver;
            $this->db->exec('SET NAMES "UTF8"');
            return true;
        }

        set_exception_handler(array(__CLASS__, 'safe_exception'));
        $this->db = new pdo($driver . ':host=' . $host . ';dbname=' . $database, $user, $password, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ));
        restore_exception_handler();
        $this->db->exec('SET NAMES "UTF8"');
        $this->info = (object) array_combine(static::$arguments, func_get_args());
        unset($this->info->password);
    }

    /**
     * Avoid exposing exception informations
     * @param Exception $exception [Optional] User password
     */
    public static function safe_exception(Exception $exception) {
        die('Uncaught exception: ' . $exception->getMessage());
    }

    /* SQL query */

    /**
     * Get latest SQL query
     * @return string Latest SQL query
     */
    public function __toString() {
        return $this->result ?
                $this->result->queryString :
                null;
    }

    /* Query methods */

    /**
     * Execute raw SQL query
     * @uses   PDO::query
     * @throw  PDOException
     * @param  string $sql Plain SQL query
     * @return Db     Self instance
     * @todo   ? detect USE query to update dbname ?
     */
    public function raw($sql) {
        $this->result = $this->db->query($sql);
        return $this;
    }

    /**
     * Execute SQL query with paramaters
     * @uses   PDO::prepare
     * @uses   self::_uncomment
     * @uses   PDOStatement::execute
     * @throw  PDOException
     * @param  string $sql    SQL query with placeholder
     * @param  array  $params SQL parameters to escape (quote)
     * @return Db     Self instance
     */
    public function query($sql, array $params) {
        $this->result = isset($this->statement[$sql]) ?
                $this->statement[$sql] :
                $this->statement[$sql] = $this->db->prepare(self::_uncomment($sql));
        $this->result->execute($params);
        $this->statuses[] = $this->result->errorInfo();
        return $this;
    }

    /**
     * Execute SQL select query
     * @uses   PDO::query
     * @throw  PDOException
     * @param  string       $table  
     * @param  string|array $fields [Optional] 
     * @param  string|array $where  [Optional] 
     * @param  string       $order  [Optional] 
     * @param  string|int   $limit  [Optional] 
     * @return Db     Self instance
     * @todo   Need complete review
     */
    public function select($table, $fields = '*', $where = null, $order = null, $limit = null) {
        $sql = 'SELECT ' . self::_fields($fields) . ' FROM ' . $this->_table($table);
        if ($where && $where = $this->_conditions($where))
            $sql .= ' WHERE ' . $where->sql;
        if ($order)
            $sql .= ' ORDER BY ' . ( is_array($order) ? implode(', ', $order) : $order );
        if ($limit)
            $sql .= ' LIMIT ' . $limit;
        return $where ?
                $this->query($sql, $where->params) :
                $this->raw($sql);
    }

    /* Query formating helpers */

    /**
     * Check if data is a plain key (without SQL logic)
     * @param  mixed $data Data to check
     * @return bool
     */
    static protected function _is_plain($data) {
        if (!is_scalar($data))
            return false;
        return is_string($data) ? !preg_match('/\W/i', $data) : true;
    }

    /**
     * Check if array is a simple indexed list
     * @param  array $array Array to check
     * @return bool
     */
    static protected function _is_list(array $array) {
        foreach (array_keys($array) as $key)
            if (!is_int($key))
                return false;
        return true;
    }

    /**
     * Remove all (inline & multiline bloc) comments from SQL query
     * @param  string $sql SQL query string
     * @return string SQL query string without comments
     */
    static protected function _uncomment($sql) {
        /* '@
          (([\'"`]).*?[^\\\]\2) # $1 : Skip single & double quoted expressions
          |(                    # $3 : Match comments
          (?:\#|--).*?$       # - Single line comments
          |                   # - Multi line (nested) comments
          /\*                 #   . comment open marker
          (?: [^/*]         #   . non comment-marker characters
          |/(?!\*)        #   . ! not a comment open
          |\*(?!/)        #   . ! not a comment close
          |(?R)           #   . recursive case
          )*                #   . repeat eventually
          \*\/                #   . comment close marker
          )\s*                  # Trim after comments
          |(?<=;)\s+            # Trim after semi-colon
          @msx' */
        return trim(preg_replace('@(([\'"`]).*?[^\\\]\2)|((?:\#|--).*?$|/\*(?:[^/*]|/(?!\*)|\*(?!/)|(?R))*\*\/)\s*|(?<=;)\s+@ms', '$1', $sql));
    }

    /**
     * Format query parameters
     * @uses   self::_escape
     * @param  string|array $data     Data to format
     * @param  string       $operator [Optional] 
     * @param  string       $glue     [Optional] 
     * @return string       SQL params query chunk
     * @todo   Handle integer keys like in self::_conditions
     */
    static protected function _params($data, $operator = '=', $glue = ', ') {
        $params = is_string($data) ? array($data) : array_keys((array) $data);
        foreach ($params as &$param)
            $param = implode(' ', array(self::_escape($param), $operator, ':' . $param));
        return implode($glue, $params);
    }

    /**
     * Format query fields
     * @uses   self::_is_plain
     * @param  string  $field Field String
     * @return string  SQL field query chunk
     */
    static protected function _escape($field) {
        return self::_is_plain($field) ?
                '`' . $field . '`' :
                $field;
    }

    static protected function _extract($table, $type = 'table') {
        static $infos = array(
            'database' => '@(?:(`?)(?P<database>\w+)\g{-2})\.(`?)(?P<table>\w+)\g{-2}(?:\.(`?)(?P<field>\w+)\g{-2})?@'
            , 'table' => '@(?:(`?)(?P<database>\w+)\g{-2}\.)?(?:(`?)(?P<table>\w+)\g{-2})(?:\.(`?)(?P<field>\w+)\g{-2})?@'
            , 'field' => '@(?:(`?)(?P<database>\w+)\g{-2}\.)?(?:(`?)(?P<table>\w+)\g{-2}\.)?(`?)(?P<field>\w+)\g{-2}@'
        );
        if (!isset($infos[$type]) || !preg_match($infos[$type], $table, $match))
            return;
        $match = array_filter(array_intersect_key($match, $infos));
        return $match[$type];
    }

    static protected function _alias(array $alias) {
        foreach ($alias as $k => $v)
            $_alias[] = self::_escape($v) . ( is_string($k) ? ' AS ' . self::_escape($k) : '' );
        return $_alias;
    }

    static protected function _fields($fields) {
        if (empty($fields))
            return '*';
        if (is_string($fields))
            return $fields;
        return implode(', ', self::_alias($fields));
    }

    //@todo
    static protected function _conditions(array $conditions) {
        $sql = array();
        $params = array();
        $i = 0;
        foreach ($conditions as $condition => $param) {
            if (is_string($condition)) {
                for ($keys = array(), $n = 0; false !== ( $n = strpos($condition, '?', $n) ); $n ++)
                    $condition = substr_replace($condition, ':' . ( $keys[] = '_' . ++$i ), $n, 1);
                if (!empty($keys))
                    $param = array_combine($keys, (array) $param);
                if (self::_is_plain($condition)) {
                    $param = array($condition => (string) $param);
                    $condition = self::_params($condition);
                }
                $params += (array) $param;
            } else
                $condition = $param;
            $sql[] = $condition;
        }
        return (object) array(
                    'sql' => '( ' . implode(' ) AND ( ', $sql) . ' )',
                    'params' => $params
        );
    }

    protected function _table($table, $escape = true) {
        return $escape ?
                self::_escape($this->_database($table)) . '.' . self::_escape(self::_extract($table, 'table')) :
                $this->_database($table) . '.' . self::_extract($table, 'table');
    }

    protected function _database($table = null) {
        return self::_extract($table, 'database') ?:
                $this->info->database;
    }

    /* Data column helpers */

    static protected function _column(array $data, $field) {
        $column = array();
        foreach ($data as $key => $row)
            if (is_object($row) && isset($row->{$field}))
                $column[$key] = $row->{$field};
            else if (is_array($row) && isset($row[$field]))
                $column[$key] = $row[$field];
            else
                $column[$key] = null;
        return $column;
    }

    static protected function _index(array $data, $field) {
        return array_combine(
                self::_column($data, $field), $data
        );
    }

    /* CRUD methods */

    public function create($table, array $data) {
        $keys = array_keys($data);
        $sql = 'INSERT INTO ' . $this->_table($table) . ' (' . implode(', ', $keys) . ') VALUES (:' . implode(', :', $keys) . ')';
        return $this->query($sql, $data);
    }

    //public function read ( $table, $where ) 
    public function read($table, $id, $key = null) {
        $key = $key ?: current($this->key($table));
        $sql = 'SELECT * FROM ' . $this->_table($table) . ' WHERE ' . self::_params($key);
        return $this->query($sql, array(':' . $key => $id));
    }

    //public function update ( $table, $data, $where )
    public function update($table, $data, $value = null, $id = null, $key = null) {
        if (is_array($data)) {
            $key = $id;
            $id = $value;
        } else
            $data = array($data => $value);
        $key = $key ?: current($this->key($table));
        if (is_null($id) && isset($data[$key]) && !( $id = $data[$key] ))
            throw new Exception('No `' . $key . '` key value to update `' . $table . '` table, please specify a key value');
        $sql = 'UPDATE ' . $this->_table($table) . ' SET ' . self::_params($data) . ' WHERE ' . self::_params($key);
        return $this->query($sql, array_merge($data, array(':' . $key => $id)));
    }

    //public function delete ( $table, $where )
    public function delete($table, $id, $key = null) {
        $key = $key ?: current($this->key($table));
        $sql = 'DELETE FROM ' . $this->_table($table) . ' WHERE ' . self::_params($key);
        return $this->query($sql, array(':' . $key => $id));
    }

    /* Fetch methods */

    public function fetch($class = null) {
        if (!$this->result)
            throw new Exception('Can\'t fetch result if no query!');
        return $class === false ?
                $this->result->fetch(PDO::FETCH_ASSOC) :
                $this->result->fetchObject($class ?: self::config('fetch'));
    }

    public function all($class = null) {
        if (!$this->result)
            throw new Exception('Can\'t fetch results if no query!');
        return $class === false ?
                $this->result->fetchAll(PDO::FETCH_ASSOC) :
                $this->result->fetchAll(PDO::FETCH_CLASS, $class ?: self::config('fetch'));
    }

    public function column($field, $index = null) {
        $data = $this->all(false);
        $values = self::_column($data, $field);
        return is_string($index) ?
                array_combine(self::_column($data, $index), $values) :
                $values;
    }

    /* Table infos */

    public function key($table) {
        $table = $this->_table($table, false);
        if (self::config($table . ':PK'))
            return self::config($table . ':PK');
        else if (isset($this->key[$table]))
            return $this->key[$table];
        $keys = array_keys(self::_column($this->fields($table), 'key'), 'PRI');
        if (empty($keys))
            throw new Exception('No primary key on ' . $this->_table($table) . ' table, please set a primary key');
        return $this->key[$table] = $keys;
    }

    public function fields($table) {
        $table = $this->_table($table, false);
        if (isset($this->table[$table]))
            return $this->table[$table];
        $sql = 'SELECT 
				`COLUMN_NAME`                                               AS `name`, 
				`COLUMN_DEFAULT`                                            AS `default`, 
				NULLIF( `IS_NULLABLE`, "NO" )                               AS `null`, 
				`DATA_TYPE`                                                 AS `type`, 
				COALESCE( `CHARACTER_MAXIMUM_LENGTH`, `NUMERIC_PRECISION` ) AS `length`, 
				`CHARACTER_SET_NAME`                                        AS `encoding`, 
				`COLUMN_KEY`                                                AS `key`, 
				`EXTRA`                                                     AS `auto`, 
				`COLUMN_COMMENT`                                            AS `comment`
			FROM `INFORMATION_SCHEMA`.`COLUMNS`
			WHERE 
				`TABLE_SCHEMA` = ' . $this->quote(self::_database($table)) . ' AND 
				`TABLE_NAME` = ' . $this->quote(self::_extract($table)) . '
			ORDER BY `ORDINAL_POSITION` ASC';
        $fields = $this->db->query($sql);
        if (!$fields->rowCount())
            throw new Exception('No ' . $this->_table($table) . ' table, please specify a valid table');
        return $this->table[$table] = self::_index($fields->fetchAll(PDO::FETCH_CLASS), 'name');
    }

    /* Quote Helper */

    public function quote($value) {
        return is_null($value) ?
                'NULL' :
                $this->db->quote($value);
    }

    public function database($table = null) {
        return $this->_table($table, true) ?:
                $this->info->database;
    }

    /* Statement infos */

    public function id() {
        // !! see http://php.net/manual/fr/pdo.lastinsertid.php
        return $this->db->lastInsertId();
    }

    public function count() {
        return $this->result ?
                $this->result->rowCount() :
                null;
    }

    public function getTableColumns($table, $typeOnly = true) {

        $result = array();
        // Set the query to get the table fields statement.
        $fields = $this->raw('SHOW FULL COLUMNS FROM ' . ($table))->all();


        // If we only want the type as the value add just that to the list.
        if ($typeOnly) {
            foreach ($fields as $field) {
                $result[$field->Field] = preg_replace('/[(0-9)]/', '', $field->Type);
            }
        }
        // If we want the whole field data object add that to the list.
        else {
            foreach ($fields as $field) {
                $result[$field->Field] = $field;
            }
        }
        return $result; 
    } 

}
