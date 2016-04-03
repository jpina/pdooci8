<?php

namespace Jpina\Test\PdoOci8;

use Jpina\PdoOci8\PdoOci8;
use Jpina\PdoOci8\PdoOci8Statement;

/**
 * @group Statement
 */
class PdoOci8StatementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PdoOci8
     */
    protected static $connection;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $options = array(
            \PDO::ATTR_AUTOCOMMIT => true,
        );
        $db = PdoOci8Test::getNewPdoConnection($options);
        static::$connection = $db;


        try {
            $sql = 'CREATE TABLE PDOOCI8.pdooci8 (dummy VARCHAR2(255))';
            $statement = $db->prepare($sql);
            $statement->execute();
        } catch (\PDOException $ex) {
            throw $ex;
        }

        try {
            $sql = 'TRUNCATE TABLE PDOOCI8.pdooci8';
            $statement = $db->prepare($sql);
            $statement->execute();

            $values = array('A', 'B', 'U', 'D');

            $sql = "INSERT INTO PDOOCI8.pdooci8 (DUMMY) VALUES (:value)";
            $statement = $db->prepare($sql);

            foreach ($values as $value) {
                $statement->bindParam(':value', $value);
                $statement->execute();
            }
        } catch (\PDOException $ex) {
            throw $ex;
        }
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();

        $db = static::$connection;
        $sql = 'DROP TABLE PDOOCI8.pdooci8';
        $statement = $db->prepare($sql);
        $statement->execute();
    }

    /**
     * @return PdoOci8
     */
    protected function getConnection()
    {
        return static::$connection;
    }

    /**
     * @param string $sqlText
     * @return PdoOci8Statement
     */
    protected function getNewStatement($sqlText)
    {
        return $this->getConnection()->prepare($sqlText);
    }

    /**
     * @test
     */
    public function extendsPDOStatement()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $this->assertInstanceOf('\PDOStatement', $statement);
    }

    /**
     * @test
     */
    public function sqlText()
    {
        $this->markTestIncomplete();
        $query = 'SELECT DUMMY FROM SYS.DUAL';
        $statement = $this->getNewStatement($query);
        $this->assertEquals($query, $statement->queryString);
    }

    /**
     * @test
     */
    public function implementsTraversableInterface()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $this->assertInstanceOf('\Traversable', $statement);
    }

    /**
     * @test
     */
    public function bindColumn()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');

        $isBound = $statement->bindColumn('DUMMY', $dummy);
        $this->assertTrue($isBound);

        $isBound = $statement->bindColumn(1, $dummy);
        $this->assertTrue($isBound);
    }

    /**
     * @test
     */
    public function cannotBindColumn()
    {
        $this->markTestIncomplete();
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');

        $isBound = $statement->bindColumn('NOT_FOUND_COLUMN', $dummy);
        $this->assertFalse($isBound);

        $isBound = $statement->bindColumn(0, $dummy);
        $this->assertFalse($isBound);
    }

    /**
     * @test
     */
    public function bindParameterByName()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy');
        $value = 'X';
        $isBound = $statement->bindParam(':dummy', $value);
        $this->assertTrue($isBound);
    }

    /**
     * @test
     */
    public function bindParameterByIndex()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE ?');
        $value = 'X';
        $isBound = $statement->bindParam(1, $value);
        $this->assertFalse($isBound);
    }

    /**
     * @test
     */
    public function cannotBindParameterByName()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy');
        $value = 'X';
        $isBound = $statement->bindParam(':var_not_found', $value);
        $this->assertFalse($isBound);
    }

    /**
     * @test
     */
    public function bindValueToNamedParameter()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy');

        $isBound = $statement->bindValue(':dummy', 'X');
        $this->assertTrue($isBound);
    }

    /**
     * @test
     */
    public function cannotBindValueToNamedParameter()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy');
        $isBound = $statement->bindValue(':var_not_found', 'X');
        $this->assertFalse($isBound);
    }

    /**
     * @test
     */
    public function cannotBindValueToIndexParameter()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE ?');
        $isBound = $statement->bindValue(1, 'X');
        $this->assertFalse($isBound);
    }

    /**
     * @test
     */
    public function closeCursor()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $isClosed = $statement->closeCursor();
        $this->assertTrue($isClosed);
    }

    /**
     * @test
     */
    public function getColumnCount()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $columnCount = $statement->columnCount();

        $this->assertTrue(is_int($columnCount));
        $this->assertGreaterThanOrEqual(0, $columnCount);
    }

    /**
     * @test
     */
    public function debugDumpParamsWithUnorderedBindings()
    {
        $sqlText = 'SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy1 OR DUMMY LIKE :dummy2';
        $statement = $this->getNewStatement($sqlText);
        $dummy2 = 'dummy2';
        $statement->bindParam(':dummy2', $dummy2);
        $dummy1 = 'dummy1';
        $statement->bindParam(':dummy1', $dummy1);

        ob_start();
        $statement->debugDumpParams();
        $debugString = ob_get_contents();
        ob_end_clean();

        $expectedDebugString =
            'SQL: [73] SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy1 OR DUMMY LIKE :dummy2' . PHP_EOL .
            'Params: 2' . PHP_EOL .
            'Key: Name: [7] :dummy1' . PHP_EOL .
            'paramno=1' . PHP_EOL .
            'name=[7]:dummy1' . PHP_EOL .
            'is_param=1' . PHP_EOL .
            'param_type=2' . PHP_EOL .
            'Key: Name: [7] :dummy2' . PHP_EOL .
            'paramno=2' . PHP_EOL .
            'name=[7]:dummy2' . PHP_EOL .
            'is_param=1' . PHP_EOL .
            'param_type=2';

        $this->assertEquals($expectedDebugString, $debugString);
    }

    /**
     * @test
     */
    public function debugDumpParamsWithOrderedBindings()
    {
        $sqlText = 'SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy1 OR DUMMY LIKE :dummy2';
        $statement = $this->getNewStatement($sqlText);
        $dummy1 = 'dummy1';
        $statement->bindParam(':dummy1', $dummy1);
        $dummy2 = 'dummy2';
        $statement->bindParam(':dummy2', $dummy2);

        ob_start();
        $statement->debugDumpParams();
        $debugString = ob_get_contents();
        ob_end_clean();

        $expectedDebugString =
            'SQL: [73] SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy1 OR DUMMY LIKE :dummy2' . PHP_EOL .
            'Params: 2' . PHP_EOL .
            'Key: Name: [7] :dummy1' . PHP_EOL .
            'paramno=1' . PHP_EOL .
            'name=[7]:dummy1' . PHP_EOL .
            'is_param=1' . PHP_EOL .
            'param_type=2' . PHP_EOL .
            'Key: Name: [7] :dummy2' . PHP_EOL .
            'paramno=2' . PHP_EOL .
            'name=[7]:dummy2' . PHP_EOL .
            'is_param=1' . PHP_EOL .
            'param_type=2';

        $this->assertEquals($expectedDebugString, $debugString);
    }

    /**
     * @test
     */
    public function errorCodeIsEmpty()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $code = $statement->errorCode();
        $this->assertNull($code);
    }

    /**
     * @test
     */
    public function errorCodeIsNotEmpty()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM NOT_FOUND_TABLE');
        $statement->execute();
        $code = $statement->errorCode();

        $this->assertTrue(is_string($code));
        $this->assertEquals(5, strlen($code));
    }

    /**
     * @test
     */
    public function errorInfoNoOnSuccess()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $error = $statement->errorInfo();

        $this->assertEquals('00000', $error[0]);
        $this->assertNull($error[1]);
        $this->assertNull($error[2]);
    }

    /**
     * @test
     */
    public function errorInfoOnError()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM NOT_FOUND_TABLE');
        $statement->execute();
        $error = $statement->errorInfo();

        $this->assertEquals('42000', $error[0]);
        $this->assertEquals(942, $error[1]);
        $this->assertEquals('ORA-00942: table or view does not exist', $error[2]);
    }

    /**
     * @test
     */
    public function execute()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $isSuccess = $statement->execute();
        $this->assertTrue($isSuccess);
    }

    /**
     * @test
     */
    public function executeSelect()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $isSuccess = $statement->execute();
        $this->assertTrue($isSuccess);
        $this->assertEquals(0, $statement->rowCount());

        $rows = $statement->fetchAll();
        $this->assertTrue(is_array($rows));
        $this->assertArrayHasKey('0', $rows);
        $this->assertArrayHasKey('DUMMY', $rows[0]);
        $this->assertEquals('X', $rows[0]['DUMMY']);
    }

    /**
     * @test
     */
    public function executeSelectWithParameters()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL WHERE dummy = :dummy");
        $parameters = array(
            'dummy' => 'X',
        );
        $isSuccess = $statement->execute($parameters);
        $this->assertTrue($isSuccess);
        $this->assertEquals(0, $statement->rowCount());

        $rows = $statement->fetchAll();
        $this->assertTrue(is_array($rows));
        $this->assertArrayHasKey('0', $rows);
        $this->assertArrayHasKey('DUMMY', $rows[0]);
        $this->assertEquals('X', $rows[0]['DUMMY']);
    }

    /**
     * @test
     */
    public function cannotExecute()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM NOT_FOUND_TABLE');
        $isSuccess = $statement->execute();
        $this->assertFalse($isSuccess);
    }

    /**
     * @test
     */
    public function executeInsert()
    {
        $statement = $this->getNewStatement("INSERT INTO PDOOCI8.pdooci8 (DUMMY) VALUES ('I')");
        $statement->execute();
        $this->assertEquals(1, $statement->rowCount());

        $statement = $this->getNewStatement("SELECT DUMMY FROM PDOOCI8.pdooci8 WHERE DUMMY = 'I'");
        $statement->execute();
        $row = $statement->fetch();

        $this->assertTrue(is_array($row));
        $this->assertArrayHasKey('DUMMY', $row);
        $this->assertEquals('I', $row['DUMMY']);
    }

    /**
     * @test
     */
    public function executeUpdate()
    {
        $statement = $this->getNewStatement("UPDATE PDOOCI8.pdooci8 SET DUMMY = 'S' WHERE DUMMY = 'U'");
        $statement->execute();
        $this->assertEquals(1, $statement->rowCount());

        $statement = $this->getNewStatement("SELECT DUMMY FROM PDOOCI8.pdooci8 WHERE DUMMY = 'S'");
        $statement->execute();
        $row = $statement->fetch();

        $this->assertTrue(is_array($row));
        $this->assertArrayHasKey('DUMMY', $row);
        $this->assertEquals('S', $row['DUMMY']);
    }

    /**
     * @test
     */
    public function executeDelete()
    {
        $statement = $this->getNewStatement("DELETE FROM PDOOCI8.pdooci8 WHERE DUMMY = 'D'");
        $statement->execute();
        $this->assertEquals(1, $statement->rowCount());

        $statement = $this->getNewStatement("SELECT DUMMY FROM PDOOCI8.pdooci8 WHERE DUMMY = 'D'");
        $statement->execute();
        $row = $statement->fetch();

        $this->assertFalse($row);
    }

    /**
     * @test
     */
    public function fetch()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $statement->execute();
        $row = $statement->fetch();

        $this->assertTrue(is_array($row));
        $this->assertArrayHasKey(0, $row);
        $this->assertArrayHasKey('DUMMY', $row);
    }

    /**
     * @test
     */
    public function fetchAssociativeArray()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $statement->execute();
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue(is_array($row));
        $this->assertArrayNotHasKey(0, $row);
        $this->assertArrayHasKey('DUMMY', $row);
    }

    /**
     * @test
     */
    public function fetchBoth()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $row = $statement->fetch(\PDO::FETCH_BOTH);

        $this->assertTrue(is_array($row));

        $this->assertArrayHasKey('0', $row);
        $this->assertEquals('X', $row[0]);

        $this->assertArrayHasKey('DUMMY', $row);
        $this->assertEquals('X', $row['DUMMY']);
    }

    /**
     * @test
     */
    public function fetchBound()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->bindColumn('DUMMY', $dummy);
        $statement->execute();
        $row = $statement->fetch(\PDO::FETCH_BOUND);

        $this->assertTrue($row);
        $this->assertEquals('X', $dummy);
    }

    /**
     * @test
     */
    public function fetchClass()
    {
        $this->markTestIncomplete('Make fetch lower case');
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $className = get_class(new Dummy());
        $statement->setFetchMode(\PDO::FETCH_CLASS, $className);
        $statement->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        $statement->execute();
        $instance = $statement->fetch();

        $this->assertInstanceOf($className, $instance);
        $this->assertObjectHasAttribute('dummy', $instance);
        $this->assertEquals('X', $instance->dummy);
    }

    /**
     * @test
     */
    public function fetchClassType()
    {
        $this->markTestIncomplete('Make fetch lower case');
        $query = "SELECT CONCAT('Jpina\\Test\\PdoOci8\\', 'Dummy') CLASSNAME, 'X' DUMMY FROM SYS.DUAL";
        $statement = $this->getNewStatement($query);
        $className = get_class(new Dummy());
        $statement->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_CLASSTYPE);
        $statement->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        $statement->execute();
        $instance = $statement->fetch();

        $this->assertInstanceOf($className, $instance);
        $this->assertObjectHasAttribute('dummy', $instance);
        $this->assertEquals('X', $instance->dummy);
    }

    /**
     * @test
     */
    public function fetchInto()
    {
        $this->markTestIncomplete('Make fetch lower case');
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $originalInstance = new Dummy();
        $statement->setFetchMode(\PDO::FETCH_INTO, $originalInstance);
        $statement->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        $statement->execute();
        $instance = $statement->fetch();

        $this->assertInstanceOf(get_class($originalInstance), $instance);
        $this->assertObjectHasAttribute('dummy', $instance);
        $this->assertEquals('X', $instance->dummy);
    }

    /**
     * @test
     */
    public function fetchNumericArray()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $statement->execute();
        $row = $statement->fetch(\PDO::FETCH_NUM);

        $this->assertTrue(is_array($row));
        $this->assertArrayNotHasKey('DUMMY', $row);
        $this->assertArrayHasKey(0, $row);
    }

    /**
     * @test
     */
    public function fetchObject()
    {
        $this->markTestIncomplete('Make fetch lower case');
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->setFetchMode(\PDO::FETCH_OBJ);
        $statement->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
        $statement->execute();
        $instance = $statement->fetch();

        $this->assertInstanceOf('\stdClass', $instance);
        $this->assertObjectHasAttribute('dummy', $instance);
        $this->assertEquals('X', $instance->dummy);
    }

    /**
     * @test
     */
    public function fetchAll()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $rows = $statement->fetchAll();

        $this->assertTrue(is_array($rows));
        $this->assertArrayHasKey('0', $rows);
        $this->assertArrayHasKey('DUMMY', $rows[0]);
    }

    /**
     * @test
     */
    public function fetchAllAsAssociativeArray()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertTrue(is_array($rows));
        $this->assertArrayHasKey('0', $rows);
        $this->assertArrayHasKey('DUMMY', $rows[0]);
    }

    /**
     * @test
     */
    public function fetchAllAsNumericArray()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_NUM);

        $this->assertTrue(is_array($rows));
        $this->assertArrayHasKey('0', $rows);
        $this->assertArrayHasKey('0', $rows[0]);
    }

    /**
     * @test
     */
    public function fetchAllAsObjects()
    {
        $this->markTestIncomplete();
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_OBJ);

        $this->assertTrue(is_array($rows));
        $this->assertArrayHasKey('0', $rows);
        $this->assertArrayHasKey('DUMMY', $rows[0]);
    }

    /**
     * @test
     */
    public function fetchAllAsCustomObjects()
    {
        $this->markTestIncomplete();
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $rows = $statement->fetchAll(\PDO::FETCH_INTO);

        $this->assertTrue(is_array($rows));
        $this->assertArrayHasKey('0', $rows);
        $this->assertArrayHasKey('DUMMY', $rows[0]);
    }

    /**
     * @test
     */
    public function fetchColumn()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $dummy = $statement->fetchColumn();

        $this->assertEquals('X', $dummy);
    }

    /**
     * @test
     */
    public function cannotFetchNonExistentColumn()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $dummy = $statement->fetchColumn(1);

        $this->assertFalse($dummy);
    }

    /**
     * @test
     */
    public function cannotFetchColumnOnEmptyRowset()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $statement->fetchColumn(0);
        $dummy = $statement->fetchColumn(0);

        $this->assertFalse($dummy);
    }

    /**
     * @test
     */
    public function fetchObject2()
    {
        $this->markTestIncomplete('Make fetch lowe case');
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $row = $statement->fetchObject();

        $this->assertInstanceOf('\\stdClass', $row);
        $this->assertTrue(property_exists($row, 'dummy'));
        $this->assertEquals('X', $row->dummy);
    }

    /**
     * @test
     */
    public function fetchCustomObject()
    {
        $this->markTestIncomplete('Make fetch lowe case');
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $className = get_class(new Dummy());
        $row = $statement->fetchObject($className, array(''));

        $this->assertInstanceOf($className, $row);
        $this->assertTrue(property_exists($row, 'dummy'));
        $this->assertEquals('X', $row->dummy);
    }

    /**
     * @test
     */
    public function getColumnMetaByColumnName()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL WHERE DUMMY LIKE '%'");
        $metadata = $statement->getColumnMeta('DUMMY');

        $this->assertTrue(is_array($metadata));

        $this->assertArrayHasKey('native_type', $metadata);
        $this->assertEquals(SQLT_AFC, $metadata['native_type']);

        $this->assertArrayHasKey('driver:decl_type', $metadata);
        $this->assertEquals('CHAR', $metadata['driver:decl_type']);

        $this->assertArrayHasKey('flags', $metadata);
        $this->assertTrue(is_array($metadata['flags']));

        $this->assertArrayHasKey('name', $metadata);
        $this->assertEquals('DUMMY', $metadata['name']);

        $this->assertArrayHasKey('table', $metadata);
        $this->assertEquals('SYS.DUAL', $metadata['table']);

        $this->assertArrayHasKey('len', $metadata);
        $this->assertEquals(1, $metadata['len']);

        $this->assertArrayHasKey('precision', $metadata);
        $this->assertEquals(0, $metadata['precision']);

        $this->assertArrayHasKey('pdo_type', $metadata);
        $this->assertEquals(\PDO::PARAM_STR, $metadata['pdo_type']);
    }

    /**
     * @test
     */
    public function getColumnMetaByColumnIndex()
    {
        $statement = $this->getNewStatement("SELECT DUMMY, CAST(9.9 AS FLOAT) AS D_FLOAT FROM SYS.DUAL");
        $metadata = $statement->getColumnMeta(1);

        $this->assertArrayHasKey('native_type', $metadata);
        $this->assertEquals(SQLT_NUM, $metadata['native_type']);

        $this->assertArrayHasKey('driver:decl_type', $metadata);
        $this->assertEquals('NUMBER', $metadata['driver:decl_type']);

        $this->assertArrayHasKey('flags', $metadata);
        $this->assertTrue(is_array($metadata['flags']));

        $this->assertArrayHasKey('name', $metadata);
        $this->assertEquals('D_FLOAT', $metadata['name']);

        $this->assertArrayHasKey('table', $metadata);
        $this->assertEquals('SYS.DUAL', $metadata['table']);

        $this->assertArrayHasKey('len', $metadata);
        $this->assertEquals(22, $metadata['len']);

        $this->assertArrayHasKey('precision', $metadata);
        $this->assertEquals(253, $metadata['precision']);

        $this->assertArrayHasKey('pdo_type', $metadata);
        $this->assertEquals(\PDO::PARAM_INT, $metadata['pdo_type']);
    }

    /**
     * @test
     */
    public function getColumnMetaFromNestedQuery()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM (SELECT * FROM SYS.DUAL)");
        $metadata = $statement->getColumnMeta(0);

        $this->assertTrue(is_array($metadata));

        $this->assertArrayHasKey('native_type', $metadata);
        $this->assertEquals(SQLT_AFC, $metadata['native_type']);

        $this->assertArrayHasKey('driver:decl_type', $metadata);
        $this->assertEquals('CHAR', $metadata['driver:decl_type']);

        $this->assertArrayHasKey('flags', $metadata);
        $this->assertTrue(is_array($metadata['flags']));

        $this->assertArrayHasKey('name', $metadata);
        $this->assertEquals('DUMMY', $metadata['name']);

        $this->assertArrayHasKey('table', $metadata);
        $this->assertEquals('', $metadata['table']);

        $this->assertArrayHasKey('len', $metadata);
        $this->assertEquals(1, $metadata['len']);

        $this->assertArrayHasKey('precision', $metadata);
        $this->assertEquals(0, $metadata['precision']);

        $this->assertArrayHasKey('pdo_type', $metadata);
        $this->assertEquals(\PDO::PARAM_STR, $metadata['pdo_type']);
    }

    /**
     * @test
     */
    public function getColumnMetaFromNestedNonSelectQuery()
    {
        $statement = $this->getNewStatement("INSERT (DUMMY) INTO SYS.DUAL VALUES ('X')");
        $metadata = $statement->getColumnMeta(0);

        $this->assertFalse($metadata);
    }

    /**
     * @test
     */
    public function canIterateStatement()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        while ($statement->valid()) {
            $row = $statement->current();
            $this->assertArrayHasKey('DUMMY', $row);
            $statement->next();
        }
    }

    /**
     * @test
     */
    public function canTraverseStatement()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        foreach ($statement as $row) {
            $this->assertArrayHasKey('DUMMY', $row);
        }
    }

    /**
     * @test
     */
    public function nextRowset()
    {
        $this->markTestIncomplete();
        $statement = $this->getNewStatement('CALL multiple_rowsets()');
        $statement->execute();

        $rowset = $statement->fetchAll(\PDO::FETCH_NUM);
        $hasMoreRowsets = $statement->nextRowset();
        $this->assertTrue($hasMoreRowsets);

        $rowset = $statement->fetchAll(\PDO::FETCH_NUM);
        $hasMoreRowsets = $statement->nextRowset();
        $this->assertFalse($hasMoreRowsets);
    }

    /**
     * @test
     */
    public function fetchStringLob()
    {
        $this->markTestIncomplete();
        $statement = $this->getNewStatement('SELECT name, picture FROM animal_pictures');
        $statement->bindColumn(1, $nickname, \PDO::PARAM_STR, 32);
        $statement->bindColumn(2, $picture, \PDO::PARAM_LOB);
        $statement->execute();
        $statement->fetch(\PDO::FETCH_BOUND);

        $this->assertTrue(is_string($nickname));
        $this->assertInstanceOf(\OCI-Lob, $picture);
    }
}

class Dummy
{
    public $dummy;

    public function __construct($value = null)
    {
        $this->dummy = $value;
    }
}
