<?php

namespace Jpina\PdoOci8;

use Jpina\Oci8\Oci8ConnectionInterface;
use Jpina\Oci8\Oci8FieldInterface;
use Jpina\Oci8\Oci8StatementInterface;
use Iterator;
use Traversable;

/**
 * Custom PDO_OCI implementation via OCI8 driver
 *
 * @see http://php.net/manual/en/class.pdostatement.php
 */
class PdoOci8Statement implements \Iterator
{
    /** @var  Oci8ConnectionInterface */
    private $connection;

    /** @var  Oci8StatementInterface */
    private $statement;

    /** @var string */
    private $sqlText = '';

    /** @var array */
    private $boundParameters = array();

    /** @var array */
    private $options;

    /** @var \ArrayIterator */
    private $iterator;

    public function __construct(Oci8ConnectionInterface $connection, $sqlText, $options = array())
    {
        if (!is_string($sqlText)) {
            throw new PdoOci8Exception('$sqlText is not a string');
        }

        $this->connection = $connection;
        $this->sqlText = $sqlText;

        $this->options = array(
            \PDO::ATTR_AUTOCOMMIT          => true,
            \PDO::ATTR_CASE                => \PDO::CASE_NATURAL,
            \PDO::ATTR_ERRMODE             => \PDO::ERRMODE_SILENT,
            \PDO::ATTR_ORACLE_NULLS        => \PDO::NULL_NATURAL,
            \PDO::ATTR_PREFETCH            => 100,
            \PDO::ATTR_TIMEOUT             => 600,
            \PDO::ATTR_STRINGIFY_FETCHES   => false,
            \PDO::ATTR_STATEMENT_CLASS     => null,
            \PDO::ATTR_EMULATE_PREPARES    => false,
            \PDO::ATTR_DEFAULT_FETCH_MODE  => \PDO::FETCH_BOTH,
            \PDO::ATTR_FETCH_TABLE_NAMES   => false,
            \PDO::ATTR_FETCH_CATALOG_NAMES => false,
            \PDO::ATTR_MAX_COLUMN_LEN      => 0,
            PdoOci8::OCI_ATTR_RETURN_LOBS  => false,
        );

        foreach ($options as $option => $value) {
            if (array_key_exists($option, $this->options)) {
                $this->options[$option] = $value;
            }
        }

        foreach ($options as $attribute => $value) {
            $this->setAttribute($attribute, $value);
        }

        try {
            $this->statement = $connection->parse($sqlText);
        } catch (\Exception $ex) {
            throw new PdoOci8Exception($ex->getMessage(), $ex->getCode(), $ex);
        }
    }

    /**
     * @param int|string $column
     * @param mixed $param
     * @param int $type
     * @param int $maxlen
     * @param mixed $driverdata
     *
     * @link http://php.net/manual/en/pdostatement.bindcolumn.php
     * @return bool
     */
    public function bindColumn($column, &$param, $type = null, $maxlen = null, $driverdata = null)
    {
        try {
            $type = $type === null ? \PDO::PARAM_STR : $type;
            $dataType = $this->getDriverDataType($type);
            return $this->statement->defineByName($column, $param, $dataType);
        } catch (\Exception $ex) {
            //throw new PdoOci8Exception($ex->getMessage(), $ex->getCode(), $ex);
        }

        return false;
    }

    /**
     * @param string $parameter
     * @param mixed $variable
     * @param int $data_type
     * @param int $length
     * @param mixed $driver_options
     *
     * @link http://php.net/manual/en/pdostatement.bindparam.php
     * @return bool
     */
    public function bindParam(
        $parameter,
        &$variable,
        $data_type = \PDO::PARAM_STR,
        $length = null,
        $driver_options = null
    ) {
        $isBound = false;
        try {
            $data_type = $data_type === null ? \PDO::PARAM_STR : $data_type;
            $dataType = $this->getDriverDataType($data_type);
            $length = $length !== null ? $length : -1;
            $isBound = $this->statement->bindByName($parameter, $variable, $length, $dataType);
            if ($isBound) {
                $this->boundParameters[$parameter] = array(
                    'name'     => is_int($parameter) ? '' : $parameter,
                    'position' => strrpos($this->sqlText, $parameter),
                    'value'    => &$variable,
                    'type'     => $data_type,
                );
            }
        } catch (\Exception $ex) {
            //throw new PdoOci8Exception($ex->getMessage(), $ex->getCode(), $ex);
        }

        return $isBound;
    }

    /**
     * @param string $parameter
     * @param $value
     * @param int $data_type
     *
     * @link http://php.net/manual/en/pdostatement.bindvalue.php
     * @return bool
     */
    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR)
    {
        return $this->bindParam($parameter, $value, $data_type);
    }

    /**
     * @link http://php.net/manual/en/pdostatement.closecursor.php
     * @return bool
     */
    public function closeCursor()
    {
        return $this->statement->cancel();
    }

    /**
     * @link http://php.net/manual/en/pdostatement.columncount.php
     * @return int
     */
    public function columnCount()
    {
        return 0;
    }

    /**
     * @link http://php.net/manual/en/pdostatement.debugdumpparams.php
     */
    public function debugDumpParams()
    {
        $sqlText = $this->sqlText;
        $sqlTextLength = strlen($sqlText);
        $parameters = $this->boundParameters;
        usort($parameters, function ($a, $b) {
            if ($a['position'] === $b['position']) {
                return 0;
            }

            return $a['position'] < $b['position'] ? -1 : 1;
        });
        $parametersCount = count($parameters);

        echo "SQL: [{$sqlTextLength}] {$sqlText}". PHP_EOL .
            "Params: {$parametersCount}";
        foreach ($parameters as $key => $parameter) {
            //TODO Add parameter position and number
            $position = $parameter['position'];
            $nameLength = strlen($parameter['name']);
            $name = $parameter['name'];
            $index = $key + 1;
            $dataType = $parameter['type'];

            echo PHP_EOL;
            if ($name === '') {
                echo "Key: Position #{$position}:". PHP_EOL;
            } else {
                echo "Key: Name: [{$nameLength}] {$name}". PHP_EOL;
            }
            echo "paramno={$index}". PHP_EOL .
                "name=[{$nameLength}]{$name}". PHP_EOL .
                "is_param=1". PHP_EOL .
                "param_type={$dataType}";
        }
    }

    /**
     * @link http://php.net/manual/en/pdostatement.errorcode.php
     * @return string
     */
    public function errorCode()
    {
        $driverError = $this->statement->getError();
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
        $driverError = $this->statement->getError();
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
     * @param array $input_parameters
     *
     * @link http://php.net/manual/en/pdostatement.execute.php
     * @return bool
     */
    public function execute($input_parameters = array())
    {
        try {
            foreach ($input_parameters as $key => $value) {
                if (is_int($key)) {
                    $parameterName = $key + 1;
                } else {
                    $parameterName = $key;
                }
                $this->bindValue($parameterName, $value);
            }

            if ($this->getAttribute(\PDO::ATTR_AUTOCOMMIT)) {
                $isCommitOnSuccess = OCI_NO_AUTO_COMMIT;
            } else {
                $isCommitOnSuccess = OCI_COMMIT_ON_SUCCESS;
            }

            $result = $this->statement->execute($isCommitOnSuccess);

            return $result;
        } catch (\Exception $ex) {
            //TODO Handle Exception
            new PdoOci8Exception($ex->getMessage(), $ex->getCode(), $ex);
        }

        return false;
    }

    /**
     * @param int $fetch_style
     * @param int $cursor_orientation
     * @param int $cursor_offset
     *
     * @link http://php.net/manual/en/pdostatement.fetch.php
     * @return mixed
     */
    public function fetch(
        $fetch_style = \PDO::ATTR_DEFAULT_FETCH_MODE,
        $cursor_orientation = \PDO::FETCH_ORI_NEXT,
        $cursor_offset = 0
    ) {
        if ($fetch_style === null) {
            $fetch_style === \PDO::ATTR_DEFAULT_FETCH_MODE;
        }

        try {
//            if ($fetch_style === \PDO::ATTR_DEFAULT_FETCH_MODE) {
//                $fetch_style = $this->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE);
//            }

            switch ($fetch_style) {
                case \PDO::FETCH_ASSOC:
                    $mode = OCI_ASSOC;
                    break;
                case \PDO::FETCH_BOUND:
                    // returns TRUE and assigns the values of the columns in your result set to the PHP
                    // variables to which they were bound with the PDOStatement::bindColumn() method
                    return $this->statement->fetch();
                case \PDO::FETCH_CLASS:
                    // returns a new instance of the requested class, mapping the columns of the result
                    // set to named properties in the class
                    break;
                case \PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE:
                    // the name of the class is determined from a value of the first column.
                    break;
                case \PDO::FETCH_INTO:
                    // updates an existing instance of the requested class, mapping the columns of the
                    // result set to named properties in the class
                    break;
                case \PDO::FETCH_LAZY:
                case \PDO::FETCH_BOTH + \PDO::FETCH_OBJ:
                     // combines PDO::FETCH_BOTH and PDO::FETCH_OBJ, creating the object variable names
                    // as they are accessed
                    break;
                case \PDO::FETCH_NAMED:
                    // returns an array with the same form as PDO::FETCH_ASSOC, except that if there are
                    // multiple columns with the same name, the value referred to by that key will be an
                    // array of all the values in the row that had that column name
                    break;
                case \PDO::FETCH_NUM:
                    $mode = OCI_NUM;
                    break;
                case \PDO::FETCH_OBJ:
                    // returns an anonymous object with property names that correspond to the column names
                    // returned in your result set
                    return $this->statement->fetchObject();
                    break;
                case \PDO::FETCH_BOTH:
                default:
                    $mode = OCI_BOTH;
                    break;
            }
            // TODO Combine other flags: eg. OCI_NULLS and OCI_LOBS
            // TODO update $this->numRows on successfull fetch
            // TODO update $this->isIteratorValid on NOT successfull fetch
            return $this->statement->fetchArray($mode);
        } catch (\Exception $ex) {
            new PdoOci8Exception($ex->getMessage(), $ex->getCode(), $ex);
        }

        return false;
    }

    /**
     * @param int $fetch_style
     * @param mixed $fetch_argument
     * @param array $ctor_args
     *
     * @link http://php.net/manual/en/pdostatement.fetchall.php
     * @return array
     */
    public function fetchAll($fetch_style = null, $fetch_argument = null, $ctor_args = array())
    {
        // TODO Implement properly (use all other fetch modes)
        $this->statement->fetchAll($rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW | OCI_ASSOC);

        return $rows;
    }

    /**
     * @param int $column_number
     *
     * @link http://php.net/manual/en/pdostatement.fetchcolumn.php
     * @return mixed
     */
    public function fetchColumn($column_number = 0)
    {
        $row = $this->fetch(\PDO::FETCH_NUM);
        if ($row === false) {
            return false;
        }

        if (array_key_exists($column_number, $row)) {
            return $row[$column_number];
        }

        // TODO Throw Exception
        return false;
    }


    /**
     * @param string $class_name
     * @param array $ctor_args
     *
     * @link http://php.net/manual/en/pdostatement.fetchobject.php
     * @return bool|object
     */
    public function fetchObject($class_name = "stdClass", $ctor_args = array())
    {
        $row = $this->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return false;
        }

        $reflexionClass = new \ReflectionClass($class_name);
        $classInstance = $reflexionClass->newInstanceArgs($ctor_args);

        foreach ($row as $property => $value) {
            $classInstance->{$property} = $value;
        }

        return $classInstance;
    }

    /**
     * @param int $attribute
     *
     * @link http://php.net/manual/en/pdostatement.getattribute.php
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
     * @param int $column
     *
     * @throws PdoOci8Exception
     *
     * @link http://php.net/manual/en/pdostatement.getcolumnmeta.php
     * @return bool|array
     */
    public function getColumnMeta($column)
    {
        $statementType = $this->statement->getType();
        if ($statementType !== 'SELECT') {
            return false;
        }

        $table = $this->getTableName();

        $sqlText = $this->sqlText;
        $statement = $this->getConnection()->parse($sqlText);
        $statement->execute(OCI_DESCRIBE_ONLY);
        $field = $statement->getField($column + 1);

        if ($field instanceof Oci8FieldInterface) {
            // Oracle returns attributes in upper case by default
            $fieldName = $field->getName();
            if ($this->getAttribute(\PDO::ATTR_CASE) === \PDO::CASE_LOWER) {
                $fieldName = strtolower($fieldName);
            }

            return array(
                'native_type'      => $field->getRawType(),
                'driver:decl_type' => $field->getType(),
                'flags'            => array(),
                'name'             => $fieldName,
                'table'            => $table,
                'len'              => $field->getSize(),
                'precision'        => $field->getPrecision() - $field->getScale(),
                'pdo_type'         => $this->getPDODataType($field->getType()),
            );
        }

        return false;
    }

    /**
     * @link http://php.net/manual/en/pdostatement.nextrowset.php
     * @return bool
     */
    public function nextRowset()
    {
        // TODO Implement
    }

    /**
     * @link http://php.net/manual/en/pdostatement.rowcount.php
     * @return int
     */
    public function rowCount()
    {
        return $this->statement->getNumRows();
    }


    /**
     * @param $attribute
     * @param $value
     *
     * @link http://php.net/manual/en/pdostatement.setattribute.php
     * @return bool
     */
    public function setAttribute($attribute, $value)
    {
        $readOnlyAttributes = array(
            \PDO::ATTR_AUTOCOMMIT,
        );

        if (array_search($attribute, $readOnlyAttributes) !== false ||
            !array_key_exists($attribute, $this->options)) {
            return false;
        }

        $this->options[$attribute] = $value;

        return true;
    }

    /**
     * @param int $mode
     * @param string|int|object $target
     * @param array $ctor_args
     *
     * @link http://php.net/manual/en/pdostatement.setfetchmode.php
     * @return bool
     */
    public function setFetchMode($mode, $target = null, $ctor_args = array())
    {
        $isSuccess = $this->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, $mode);
        if ($isSuccess) {
            $this->fetchTarget = $target;
            $this->fetchTargetConstructorArgs = $ctor_args;
        }

        return $isSuccess;
    }

    /**
     * @return string
     */
    protected function getTableName()
    {
        $statementType = $this->statement->getType();
        if ($statementType !== 'SELECT') {
            return '';
        }

        $sqlText = strtoupper($this->sqlText);
        $idx     = strpos($sqlText, ' FROM ');
        $table   = substr($this->sqlText, $idx + 6);
        $table   = trim($table);

        if (strpos($table, '(') !== false) {
            return '';
        }

        $idxSpace = strpos($table, ' ');
        if ($idxSpace !== false) {
            $table = substr($table, 0, $idxSpace);
        }

        return $table;
    }

    /**
     * @param string $data_type The data type name
     *
     * @return int
     */
    protected function getPDODataType($data_type)
    {
        //TODO Add all oracle data types
        $pdoDataType = \PDO::PARAM_STR;
        switch ($data_type) {
            case 'NUMBER':
                $pdoDataType = \PDO::PARAM_INT;
                break;
            case 'CHAR':
            case 'VARCHAR2':
            case 'NVARCHAR2':
                $pdoDataType = \PDO::PARAM_STR;
                break;
            case 'LOB':
            case 'CLOB':
            case 'BLOB':
            case 'NCLOB':
                $pdoDataType = \PDO::PARAM_LOB;
                break;
            case 'BOOLEAN':
                $pdoDataType = \PDO::PARAM_BOOL;
        }

        return $pdoDataType;
    }

    /**
     * @param int $type
     * @throws PdoOci8Exception
     * @return int
     */
    protected function getDriverDataType($type)
    {
        $dataType = null;
        switch ($dataType) {
            case \PDO::PARAM_BOOL:
                $dataType = SQLT_BOL;
                break;
            case \PDO::PARAM_INT:
                $dataType = SQLT_INT;
                break;
            case \PDO::PARAM_LOB:
                $dataType = SQLT_CLOB;
                break;
            case \PDO::PARAM_STMT:
                throw new PdoOci8Exception('Parameter type \PDO::PARAM_STMT is not currently supported.');
            case \PDO::PARAM_NULL:
            case \PDO::PARAM_STR:
                $dataType = SQLT_CHR;
                break;
            case \PDO::PARAM_INPUT_OUTPUT:
            case \PDO::PARAM_INPUT_OUTPUT | \PDO::PARAM_BOOL:
            case \PDO::PARAM_INPUT_OUTPUT | \PDO::PARAM_INT:
            case \PDO::PARAM_INPUT_OUTPUT | \PDO::PARAM_LOB:
            case \PDO::PARAM_INPUT_OUTPUT | \PDO::PARAM_STMT:
            case \PDO::PARAM_INPUT_OUTPUT | \PDO::PARAM_NULL:
            case \PDO::PARAM_INPUT_OUTPUT | \PDO::PARAM_STR:
                throw new PdoOci8Exception('Parameter type \PDO::PARAM_INPUT_OUTPUT is not currently supported.');
        }

        return $dataType;
    }

    /**
     * @return Oci8ConnectionInterface
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return \Traversable
     */
    protected function getInternalIterator()
    {
        if ($this->iterator instanceof \Traversable) {
            return $this->iterator;
        }

        $rows = $this->fetchAll();
        if ($rows === false) {
            //TODO Throw Exception?
            $rows = array();
        }

        $this->iterator = new \ArrayIterator($rows);

        return $this->iterator;
    }

    /**
     * @return array
     */
    public function current()
    {
        $iterator = $this->getInternalIterator();

        return $iterator->current();
    }

    public function next()
    {
        $iterator = $this->getInternalIterator();

        $iterator->next();
    }

    /**
     * @return int|null
     */
    public function key()
    {
        $iterator = $this->getInternalIterator();

        return $iterator->key();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        $iterator = $this->getInternalIterator();

        return $iterator->valid();
    }

    public function rewind()
    {
        $iterator = $this->getInternalIterator();

        $iterator->rewind();
    }
}
