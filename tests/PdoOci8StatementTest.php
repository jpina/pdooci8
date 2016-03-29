<?php

namespace Jpina\Test\PdoOci8;

use Jpina\PdoOci8\PdoOci8;
use Jpina\PdoOci8\PdoOci8Statement;

class PdoOci8StatementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PdoOci8
     */
    protected static $connection;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$connection = PdoOci8Test::getNewPdoConnection();
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

    public function testCanBindColumn()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');

        $isBound = $statement->bindColumn('DUMMY', $dummy);
        $this->assertTrue($isBound);

        $isBound = $statement->bindColumn(1, $dummy);
        $this->assertTrue($isBound);
    }

    public function testCannotBindColumn()
    {
        $this->markTestIncomplete();
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');

        $isBound = $statement->bindColumn('NOT_FOUND_COLUMN', $dummy);
        $this->assertFalse($isBound);

        $isBound = $statement->bindColumn(0, $dummy);
        $this->assertFalse($isBound);
    }

    public function testCannotBindVariableToNamedParameter()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy');
        $value = 'X';
        $isBound = $statement->bindParam(':dummy', $value);
        $this->assertTrue($isBound);
    }

    public function testCannotBindVariableToIndexedParameter()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE ?');
        $value = 'X';
        $isBound = $statement->bindParam(1, $value);
        $this->assertFalse($isBound);
    }

    public function testCannotBindVariable()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy');
        $value = 'X';
        $isBound = $statement->bindParam(':var_not_found', $value);
        $this->assertFalse($isBound);
    }

    public function testCanBindValueToNamedParameter()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy');

        $isBound = $statement->bindValue(':dummy', 'X');
        $this->assertTrue($isBound);
    }

    public function testCannotBindValueToIndexedParameter()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE ?');
        $isBound = $statement->bindValue(1, 'X');
        $this->assertFalse($isBound);
    }

    public function testCannotBindValue()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL WHERE DUMMY LIKE :dummy');
        $isBound = $statement->bindValue(':var_not_found', 'X');
        $this->assertFalse($isBound);
    }

    public function testCanCloseCursor()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $isClosed = $statement->closeCursor();
        $this->assertTrue($isClosed);
    }

    public function testGetColumnCount()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $columnCount = $statement->columnCount();

        $this->assertTrue(is_int($columnCount));
        $this->assertGreaterThanOrEqual(0, $columnCount);
    }

    public function testDebugDumpParamsUnorderedBingings()
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

    public function testDebugDumpParamsOrderedBingings()
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

    public function testErrorCodeIsEmpty()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $code = $statement->errorCode();
        $this->assertNull($code);
    }

    public function testErrorCodeIsNotEmpty()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM NOT_FOUND_TABLE');
        $statement->execute();
        $code = $statement->errorCode();

        $this->assertTrue(is_string($code));
        $this->assertEquals(5, strlen($code));
    }

    public function testErrorInfo()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $error = $statement->errorInfo();

        $this->assertEquals('00000', $error[0]);
        $this->assertNull($error[1]);
        $this->assertNull($error[2]);
    }

    public function testErrorInfoOnError()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM NOT_FOUND_TABLE');
        $statement->execute();
        $error = $statement->errorInfo();

        $this->assertEquals('42000', $error[0]);
        $this->assertEquals(942, $error[1]);
        $this->assertEquals('ORA-00942: table or view does not exist', $error[2]);
    }

    public function testCanExecute()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $isSuccess = $statement->execute();
        $this->assertTrue($isSuccess);
    }

    public function testCannotExecute()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM NOT_FOUND_TABLE');
        $isSuccess = $statement->execute();
        $this->assertFalse($isSuccess);
    }

    public function testCanFetch()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $statement->execute();
        $row = $statement->fetch();

        $this->assertTrue(is_array($row));
        $this->assertArrayHasKey(0, $row);
        $this->assertArrayHasKey('DUMMY', $row);
    }

    public function testCanFetchAssociative()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $statement->execute();
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        $this->assertTrue(is_array($row));
        $this->assertArrayNotHasKey(0, $row);
        $this->assertArrayHasKey('DUMMY', $row);
    }

    public function testCanFetchNumeric()
    {
        $statement = $this->getNewStatement('SELECT DUMMY FROM SYS.DUAL');
        $statement->execute();
        $row = $statement->fetch(\PDO::FETCH_NUM);

        $this->assertTrue(is_array($row));
        $this->assertArrayNotHasKey('DUMMY', $row);
        $this->assertArrayHasKey(0, $row);
    }

    public function testCanFetchBound()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->bindColumn('DUMMY', $dummy);
        $statement->execute();
        $isSuccess = $statement->fetch(\PDO::FETCH_BOUND);

        $this->assertTrue($isSuccess);
        $this->assertEquals('X', $dummy);
    }

    public function testCanFetchObject()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $row = $statement->fetch(\PDO::FETCH_OBJ);

        $this->assertTrue($row instanceof \stdClass);
        $this->assertTrue(property_exists($row, 'DUMMY'));
        $this->assertEquals('X', $row->DUMMY);
    }

    public function testCanFetchAll()
    {
        $this->markTestIncomplete();
    }

    public function testCanFetchColumn()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $dummy = $statement->fetchColumn();

        $this->assertEquals('X', $dummy);
    }

    public function testCannotFetchNonExistentColumn()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $dummy = $statement->fetchColumn(1);

        $this->assertFalse($dummy);
    }

    public function testCannotFetchColumnOnEmptyRowset()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $statement->fetchColumn(0);
        $dummy = $statement->fetchColumn(0);

        $this->assertFalse($dummy);
    }

    public function testCanFetchCustomObject()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL");
        $statement->execute();
        $className = get_class(new Dummy());
        $row = $statement->fetchObject($className, array(''));

        $this->assertTrue($row instanceof Dummy);
        $this->assertTrue(property_exists($row, 'DUMMY'));
        $this->assertEquals('X', $row->DUMMY);
    }

    public function testGetColumnMetaString()
    {
        $statement = $this->getNewStatement("SELECT 'X' DUMMY FROM SYS.DUAL WHERE DUMMY LIKE '%'");
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
        $this->assertEquals('SYS.DUAL', $metadata['table']);

        $this->assertArrayHasKey('len', $metadata);
        $this->assertEquals(1, $metadata['len']);

        $this->assertArrayHasKey('precision', $metadata);
        $this->assertEquals(0, $metadata['precision']);

        $this->assertArrayHasKey('pdo_type', $metadata);
        $this->assertEquals(\PDO::PARAM_STR, $metadata['pdo_type']);
    }

    public function testGetColumnMetaNumber()
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

    public function testGetColumnMetaNestedQuery()
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

    public function testGetColumnMetaNestedNonSelectQuery()
    {
        $statement = $this->getNewStatement("INSERT (DUMMY) INTO SYS.DUAL VALUES ('X')");
        $metadata = $statement->getColumnMeta(0);

        $this->assertFalse($metadata);
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
