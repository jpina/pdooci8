<?php

namespace Jpina\PdoOci8;

use Jpina\Oci8\Oci8Connection;
use Jpina\Oci8\Oci8ConnectionInterface;
use Jpina\Oci8\Oci8Exception;
use Jpina\Oci8\Oci8PersistentConnection;

/**
 * Custom PDO_OCI implementation via OCI8 driver
 *
 * @see http://php.net/manual/en/class.pdo.php
 */
class PdoOci8
{
    const OCI_ATTR_SESSION_MODE = 8000;

    const OCI_ATTR_RETURN_LOBS = 8001;

    /**
     * @var Oci8ConnectionInterface
     */
    protected $connection;

    /**
     * @var int
     */
    private $autoCommitMode = false;

    /**
     * @var array
     */
    protected $options = array();

    // TODO Receive Oci8ConnectionInterface?
    public function __construct($dsn, $username = '', $password = '', $options = array())
    {
        $this->options = array(
            \PDO::ATTR_AUTOCOMMIT         => true,
            \PDO::ATTR_CASE               => \PDO::CASE_NATURAL,
            \PDO::ATTR_CLIENT_VERSION     => '',
            \PDO::ATTR_CONNECTION_STATUS  => '',
            \PDO::ATTR_DRIVER_NAME        => 'oci',
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_SILENT,
            \PDO::ATTR_ORACLE_NULLS       => \PDO::NULL_NATURAL,
            \PDO::ATTR_PERSISTENT         => false,
            \PDO::ATTR_PREFETCH           => 100,
            \PDO::ATTR_SERVER_INFO        => '',
            \PDO::ATTR_SERVER_VERSION     => '',
            \PDO::ATTR_TIMEOUT            => 600,
            \PDO::ATTR_STRINGIFY_FETCHES  => false,
            \PDO::ATTR_STATEMENT_CLASS    => null,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_BOTH,
            static::OCI_ATTR_SESSION_MODE => OCI_DEFAULT,
            static::OCI_ATTR_RETURN_LOBS  => false,
        );

        foreach ($options as $option => $value) {
            if (array_key_exists($option, $this->options)) {
                $this->options[$option] = $value;
            }
        }

        foreach ($options as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }

        $connection = $this->getOracleConnection($dsn, $username, $password);
        $this->setAttribute(\PDO::ATTR_CLIENT_VERSION, $connection->getClientVersion());
        $this->setAttribute(\PDO::ATTR_SERVER_VERSION, $connection->getServerVersion());

        $this->connection = $connection;
    }

    protected function getCharset($dsn)
    {
        $connectionStringItems = $this->getConnectionStringItems($dsn);

        return $connectionStringItems['charset'];
    }

    /**
     * @return Oci8ConnectionInterface
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @return Oci8ConnectionInterface
     */
    protected function getOracleConnection($dsn, $username = null, $password = null)
    {
        $connectionString = $this->getConnectionString($dsn);
        $charset = $this->getCharset($dsn);
        $sessionMode = $this->getAttribute(static::OCI_ATTR_SESSION_MODE);

        $connection = null;
        try {
            if ($this->getAttribute(\PDO::ATTR_PERSISTENT)) {
                $connection = new Oci8PersistentConnection(
                    $username,
                    $password,
                    $connectionString,
                    $charset,
                    $sessionMode
                );
            } else {
                $connection = new Oci8Connection(
                    $username,
                    $password,
                    $connectionString,
                    $charset,
                    $sessionMode
                );
            }
        } catch (Oci8Exception $ex) {
            throw new PdoOci8Exception($ex->getMessage(), $ex->getCode(), $ex);
        }

        return $connection;
    }

    protected function getConnectionString($dsn)
    {
        $connectionStringItems = $this->getConnectionStringItems($dsn);
        $hostname = $connectionStringItems['hostname'];
        $port = $connectionStringItems['port'];
        $database = $connectionStringItems['database'];
        $connectionString = "//{$hostname}:{$port}/{$database}";

        return $connectionString;
    }

    protected function getConnectionStringItems($dsn)
    {
        $dsnRegex = $this->getDsnRegex();
        $matches = array();
        if (preg_match("/^{$dsnRegex}$/i", $dsn, $matches) !== 1) {
            throw new PdoOci8Exception('Invalid DSN');
        }

        // TODO Remove the variables below
        $hostname = 'localhost';
        $port     = 1521;
        $database = null;
        $charset  = 'AL32UTF8';

        switch (count($matches)) {
            case 16:
                $charset  = empty($matches[15]) ? $charset : $matches[15];
                // fall through next case to get the rest of the variables
            case 14:
                $port     = empty($matches[12]) ? $port : (int)$matches[12];
                $hostname = $matches[4];
                $database = $matches[13];
                break;
            default:
                $database = $matches[1];
        }

        return array(
            'hostname' => $hostname,
            'port'     => $port,
            'database' => $database,
            'charset'  => $charset,
        );
    }

    protected function getDsnRegex()
    {
        $hostnameRegrex = "(([a-z]|[a-z][a-z0-9\-_]*[a-z0-9])\.)*([a-z]|[a-z][a-z0-9\-_]*[a-z0-9])";
        $validIpAddressRegex = "(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}" .
            "([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])";
        $hostnameOrIpAddressRegrex = "\/\/({$hostnameRegrex}|{$validIpAddressRegex})";
        $databaseRegex = "([a-z_][a-z\-_\d]*)|({$hostnameOrIpAddressRegrex}(:(\d+))?\/([a-z_][a-z\-_\d]*))";
        $charsetRegex = "(charset=([a-z\d\-_]+))?";
        $dsnRegex = "oci\:database=({$databaseRegex});?{$charsetRegex}";

        return $dsnRegex;
    }

    /**
     * @link http://php.net/manual/en/pdo.begintransaction.php
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->setAttribute(\PDO::ATTR_AUTOCOMMIT, false);
    }

    /**
     * @link http://php.net/manual/en/pdo.commit.php
     * @return bool
     */
    public function commit()
    {
        try {
            $isSuccess = $this->getConnection()->commit();
        } catch (Oci8Exception $ex) {
            $isSuccess = false;
        }

        $this->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);

        return $isSuccess;
    }

    /**
     * @link http://php.net/manual/en/pdo.errorcode.php
     * @return null|string
     */
    public function errorCode()
    {
        $driverError = $this->getConnection()->getError();
        if (!$driverError) {
            return null;
        }

        $error = $this->errorInfo();
        $sqlStateErrorCode = $error[0];

        return $sqlStateErrorCode;
    }

    /**
     * @link http://php.net/manual/en/pdo.errorinfo.php
     * @return array
     */
    public function errorInfo()
    {
        $driverError = $this->getConnection()->getError();
        if ($driverError) {
            $driverErrorMessage = $driverError['message'];
            $driverErrorCode = $driverError['code'];
        } else {
            $driverErrorMessage = null;
            $driverErrorCode = null;
        }

        $sqlStateErrorCode = OracleSqlStateCode::getSqlStateErrorCode((int)$driverErrorCode);
        $error = array(
            $sqlStateErrorCode,
            $driverErrorCode,
            $driverErrorMessage
        );

        return $error;
    }

    /**
     * @param string $statement
     *
     * @link http://php.net/manual/en/pdo.exec.php
     * @return bool|int
     */
    public function exec($statement)
    {
        //TODO Implement
        if (strpos($statement, 'SELECT') === 0) {
            return false;
        }

        // TODO If DELETE, UPDATE or DELETE, then return affected rows, otherwise FALSE

        return rand(0, 100);

//        $statement = $this->prepare($statement);
//        $statement->execute()
//        return $stmt->rowCount();
    }

    /**
     * @param int $attribute
     *
     * @link http://php.net/manual/en/pdo.getattribute.php
     * @return mixed
     */
    public function getAttribute($attribute)
    {
        if (array_key_exists($attribute, $this->options)) {
            return $this->options[$attribute];
        }

        return null;
    }

    /**
     * @link http://php.net/manual/en/pdo.getavailabledrivers.php
     * @return array
     */
    public static function getAvailableDrivers()
    {
        return array('oci');
    }

    /**
     * @link http://php.net/manual/en/pdo.intransaction.php
     * @return bool
     */
    public function inTransaction()
    {
        $inTransaction = !$this->getAttribute(\PDO::ATTR_AUTOCOMMIT);
        return $inTransaction;
    }

    /**
     * @param string $name
     *
     * @link http://php.net/manual/en/pdo.lastinsertid.php
     * @return string
     */
    public function lastInsertId($name = null)
    {
        $statement = $this->query("select {$name}.currval from dual");
        $lastInsertedId = $statement->fetch();

        return $lastInsertedId;
    }

    /**
     * @param string $statement
     * @param array $driver_options
     *
     * @link http://php.net/manual/en/pdo.prepare.php
     * @return bool|PdoOci8Statement
     */
    public function prepare($statement, $driver_options = array())
    {
        // TODO Use $driver_options, eg. configure a cursor
        $exception = null;
        try {
            return new PdoOci8Statement($this->getConnection(), $statement, $driver_options);
        } catch (\Exception $ex) {
            $exception = new PdoOci8Exception($ex->getMessage(), $ex->getCode(), $ex);
        }

        // TODO Create error handler
        $errorMode = $this->getAttribute(\PDO::ATTR_ERRMODE);
        switch ($errorMode) {
            case \PDO::ERRMODE_EXCEPTION:
                throw $exception;
            case \PDO::ERRMODE_WARNING:
                trigger_error($exception->getMessage(), E_WARNING);
                break;
            case \PDO::ERRMODE_SILENT:
            default:
        }

        return false;
    }

    /**
     * @param string $statement
     * @param int $mode
     * @param int|string|object $arg3
     * @param array $ctorargs
     *
     * @link http://php.net/manual/en/pdo.query.php
     * @return bool|PdoOci8Statement
     */
    public function query($statement, $mode = \PDO::ATTR_DEFAULT_FETCH_MODE, $arg3 = null, $ctorargs = array())
    {
        $statement = $this->prepare($statement);
        $statement->execute();
        // TODO consider $mode
//        if ($mode) {
//            $statement->setFetchMode($mode, $arg3, $ctorArgs);
//        }

        return $statement;
    }

    /**
     * @param string $string
     * @param int $parameter_type
     *
     * @link http://php.net/manual/en/pdo.quote.php
     * @return bool|string
     */
    public function quote($string, $parameter_type = \PDO::PARAM_STR)
    {
        if (!is_scalar($string)) {
            return false;
        }

        try {
            if ($parameter_type !== \PDO::PARAM_STR) {
                $quotedString = (string)$string;
                $quotedString = "'" . $quotedString . "'";
                return $quotedString;
            }
        } catch (\Exception $ex) {
            return false;
        }

        $quotedString = str_replace("\'", "'", $string);
        $quotedString = str_replace("'", "''", $quotedString);
        $quotedString = "'" . $quotedString . "'";

        return $quotedString;
    }

    /**
     * @link http://php.net/manual/en/pdo.rollback.php
     * @return bool
     */
    public function rollback()
    {
        try {
            $isSuccess = $this->getConnection()->rollback();
        } catch (Oci8Exception $ex) {
            $isSuccess = false;
        }

        $this->setAttribute(\PDO::ATTR_AUTOCOMMIT, true);

        return $isSuccess;
    }

    /**
     * @param int $attribute
     * @param mixed $value
     *
     * @link http://php.net/manual/en/pdo.setattribute.php
     * @return bool
     */
    public function setAttribute($attribute, $value)
    {
        $readOnlyAttributes = array(
            \PDO::ATTR_CLIENT_VERSION,
            \PDO::ATTR_CONNECTION_STATUS,
            \PDO::ATTR_DRIVER_NAME,
            \PDO::ATTR_PERSISTENT,
            \PDO::ATTR_SERVER_INFO,
            \PDO::ATTR_SERVER_VERSION,
        );

        if (array_search($attribute, $readOnlyAttributes) !== false ||
            !array_key_exists($attribute, $this->options)) {
            return false;
        }

        $this->options[$attribute] = $value;

        return true;
    }
}