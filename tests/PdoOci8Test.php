<?php

namespace Jpina\Test\PdoOci8;

use Jpina\PdoOci8\PdoOci8;
use Jpina\PdoOci8\PdoOci8Exception;

/**
 * @group Connection
 */
class PdoOci8Test extends \PHPUnit_Framework_TestCase
{
    /** @var PdoOci8 */
    protected static $connection;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $db = static::getNewPdoConnection();
        static::$connection = $db;

        $options = array(
            \PDO::ATTR_AUTOCOMMIT => true,
        );

        try {
            $sql = 'CREATE TABLE PDOOCI8.pdooci8 (dummy VARCHAR2(255))';
            $statement = $db->prepare($sql, $options);
            $statement->execute();
        } catch (\PDOException $ex) {
            throw $ex;
        }

        try {
            $sql = 'TRUNCATE TABLE PDOOCI8.pdooci8';
            $statement = $db->prepare($sql, $options);
            $statement->execute();

            $values = array('A', 'B');

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

    protected static function getConnectionString($port = null, $charset = null)
    {
        if ($port) {
            $dsn = 'oci:database=//' . getenv('DB_HOST') . ':' . $port . '/' . getenv('DB_SCHEMA');
        } else {
            $dsn = 'oci:database=//' . getenv('DB_HOST') . '/' . getenv('DB_SCHEMA');
        }

        if ($charset) {
            $dsn = $dsn . ';charset=' . $charset;
        }

        return $dsn;
    }

    /**
     * @return \Jpina\PdoOci8\PdoOci8
     */
    public static function getNewPdoConnection($options = array())
    {
        $dsn = static::getConnectionString((int)getenv('DB_PORT'), getenv('DB_CHARSET'));
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');

        return new PdoOci8($dsn, $username, $password, $options);
    }

    /**
     * @return \Jpina\PdoOci8\PdoOci8
     */
    protected function getConnection()
    {
        return static::$connection;
    }

    /**
     * @return \Jpina\PdoOci8\PdoOci8
     */
    protected function getNewConnection($options = array())
    {
        return static::getNewPdoConnection($options);
    }

    /**
     * @test
     */
    public function extendsPDO()
    {
        $db = $this->getConnection();
        $this->assertInstanceOf('\PDO', $db);
    }

    /**
     * @test
     * @expectedException \Jpina\PdoOci8\PdoOci8Exception
     * @expectedExceptionMessage oci_new_connect(): ORA-12541: TNS:no listener
     * @expectedExceptionCode 12541
     */
    public function connecttionError()
    {
        $dsn = 'oci:database=//localhost:1234/XE';
        $username = 'NO_USER';
        $password = 'NO_PASSWORD';
        new PdoOci8($dsn, $username, $password);
    }

    /**
     * @test
     */
    public function connectWithPort()
    {
        $dsn = static::getConnectionString((int)getenv('DB_PORT'));
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $db = new PdoOci8($dsn, $username, $password);

        $this->assertInstanceOf('Jpina\PdoOci8\PdoOci8', $db);
    }

    /**
     * @test
     */
    public function connectWithPortAndCharset()
    {
        $dsn = static::getConnectionString((int)getenv('DB_PORT'), getenv('DB_CHARSET'));
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $db = new PdoOci8($dsn, $username, $password);

        $this->assertInstanceOf('Jpina\PdoOci8\PdoOci8', $db);
    }

    /**
     * @test
     * @expectedException \Jpina\PdoOci8\PdoOci8Exception
     * @expectedExceptionMessage Invalid DSN
     */
    public function connectWithBadDsn()
    {
        $dsn = 'this is a bad DSN';
        new PdoOci8($dsn);
    }

    /**
     * @test
     */
    public function persistentConnection()
    {
        $resource1 = $this->getPersistentConnection();
        $resource2 = $this->getPersistentConnection();

        $this->assertEquals('oci8 persistent connection', get_resource_type($resource1));
        $this->assertEquals('oci8 persistent connection', get_resource_type($resource2));

        $this->assertSame($resource1, $resource2);
    }

    /**
     * @return resource
     */
    public function getPersistentConnection()
    {
        $options = array(
            \PDO::ATTR_PERSISTENT => true,
        );

        $connection = $this->getNewConnection($options);
        $class = new \ReflectionClass($connection);
        $property = $class->getProperty('connection');
        $property->setAccessible(true);
        $oci8Connection = $property->getValue($connection);
        $class = new \ReflectionClass($oci8Connection);
        $property = $class->getProperty('resource');
        $property->setAccessible(true);
        $resource = $property->getValue($oci8Connection);

        return $resource;
    }

    /**
     * @test
     */
    public function beginTransaction()
    {
        $db = $this->getNewPdoConnection();
        $isSuccess = $db->beginTransaction();
        $this->assertTrue($isSuccess);
    }

    /**
     * @test
     */
    public function cannotStartMoreThanOneTransaction()
    {
        $db = $this->getNewPdoConnection();
        $isSuccess = $db->beginTransaction();
        $this->assertTrue($isSuccess);
        $isSuccess = $db->beginTransaction();
        $this->assertFalse($isSuccess);
    }

    /**
     * @test
     */
    public function commit()
    {
        $db = $this->getNewConnection();
        $isSuccess = $db->commit();
        $isAutoCommitEnabled = $db->getAttribute(\PDO::ATTR_AUTOCOMMIT);

        $this->assertTrue($isSuccess);
        $this->assertTrue($isAutoCommitEnabled);
    }

    /**
     * @test
     */
    public function errorCodeIsEmpty()
    {
        $db = $this->getNewConnection();
        $code = $db->errorCode();

        $this->assertNull($code);
    }

    /**
     * @test
     */
    public function errorInfoNoError()
    {
        $db = $this->getNewConnection();
        $error = $db->errorInfo();

        $this->assertEquals('00000', $error[0]);
        $this->assertNull($error[1]);
        $this->assertNull($error[2]);
    }

    /**
     * @test
     */
    public function errorInfo()
    {
        $this->markTestIncomplete();
        $db = $this->getConnection();
        $db->query('bogus sql');
        $error = $db->errorInfo();

        $this->assertEquals('42000', $error[0]);
        $this->assertEquals(900, $error[1]);
        $this->assertEquals('ORA-00900: invalid SQL statement', $error[2]);
    }

    /**
     * @test
     */
    public function getAffectedRowsFromSelect()
    {
        $db = $this->getConnection();
        $rowsAffected = $db->exec('SELECT * FROM DUAL');

        $this->assertFalse($rowsAffected);
    }

    /**
     * @test
     */
    public function getAffectedRowsFromInsert()
    {
        $db = $this->getConnection();
        $rowsAffected = $db->exec("INSERT INTO PDOOCI8.pdooci8 (DUMMY) VALUES ('C')");

        $this->assertEquals(1, $rowsAffected);
    }

    /**
     * @test
     */
    public function getAffectedRowsFromUpdate()
    {
        $db = $this->getConnection();
        $rowsAffected = $db->exec("UPDATE PDOOCI8.pdooci8 SET DUMMY = 'Z' WHERE DUMMY = 'A'");

        $this->assertEquals(1, $rowsAffected);
    }

    /**
     * @test
     */
    public function getAffectedRowsFromDelete()
    {
        $db = $this->getConnection();
        $rowsAffected = $db->exec("DELETE FROM PDOOCI8.pdooci8 WHERE DUMMY = 'B'");

        $this->assertEquals(1, $rowsAffected);
    }

    /**
     * @test
     */
    public function getAffectedRowsFromSelectStoredProcedure()
    {
        $this->markTestIncomplete();
        $db = $this->getConnection();
        $rowsAffected = $db->exec("DELETE FROM PDOOCI8.pdooci8 WHERE DUMMY = 'B'");

        $this->assertEquals(1, $rowsAffected);
    }

    /**
     * @test
     */
    public function getAffectedRowsFromInsertStoredProcedure()
    {
        $this->markTestIncomplete();
        $db = $this->getConnection();
        $rowsAffected = $db->exec("DELETE FROM PDOOCI8.pdooci8 WHERE DUMMY = 'B'");

        $this->assertEquals(1, $rowsAffected);
    }

    public function canGetAttributes()
    {
        $db = $this->getConnection();

        $isAutoCommit = $db->getAttribute(\PDO::ATTR_AUTOCOMMIT);
        $this->assertTrue($isAutoCommit);

        $case = $db->getAttribute(\PDO::ATTR_CASE);
        $this->assertEquals(\PDO::CASE_NATURAL, $case);

        $clientVersion = $db->getAttribute(\PDO::ATTR_CLIENT_VERSION);
        $this->assertEquals('', $clientVersion);

        $connectionStatus = $db->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
        $this->assertEquals('', $connectionStatus);

        $driverName = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $this->assertEquals('oci', $driverName);

        $errorMode = $db->getAttribute(\PDO::ATTR_ERRMODE);
        $this->assertEquals(\PDO::ERRMODE_SILENT, $errorMode);

        $oracleNulls = $db->getAttribute(\PDO::ATTR_ORACLE_NULLS);
        $this->assertTrue($oracleNulls);

        $isPersistent = $db->getAttribute(\PDO::ATTR_PERSISTENT);
        $this->assertFalse($isPersistent);

        $prefetch = $db->getAttribute(\PDO::ATTR_PREFETCH);
        $this->assertTrue(is_int($prefetch));
        $this->assertGreaterThanOrEqual(0, $prefetch);

        $serverInfo = $db->getAttribute(\PDO::ATTR_SERVER_INFO);
        $this->assertEquals('', $serverInfo);

        $serverVersion = $db->getAttribute(\PDO::ATTR_SERVER_VERSION);
        $this->assertEquals('', $serverVersion);

        $timeout = $db->getAttribute(\PDO::ATTR_TIMEOUT);
        $this->assertTrue(is_int($timeout));
        $this->assertGreaterThanOrEqual(0, $timeout);

        $sessionMode = $db->getAttribute(PdoOci8::OCI_ATTR_SESSION_MODE);
        $this->assertEquals(OCI_DEFAULT, $sessionMode);

        $returnLobs = $db->getAttribute(\PDO::OCI_ATTR_RETURN_LOBS);
        $this->assertFalse($returnLobs);
    }

    public function testAvailableDrivers()
    {
        $drivers = PdoOci8::getAvailableDrivers();
        $this->assertTrue(is_array($drivers));
        $this->assertArraySubset(array('oci'), $drivers, true);
    }

    public function testInTransaction()
    {
        $options = array(
            \PDO::ATTR_AUTOCOMMIT => true,
        );
        $db = $this->getNewConnection($options);
        $isInTransaction = $db->inTransaction();
        $this->assertFalse($isInTransaction);

        $db->beginTransaction();
        $isInTransaction = $db->inTransaction();
        $this->assertTrue($isInTransaction);
    }

    /**
     * @test
     */
    public function getLastInsertId()
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     */
    public function prepareStatement()
    {
        $db = $this->getConnection();
        $statement = $db->prepare('SELECT * FROM DUAL');

        $this->assertInstanceOf('Jpina\PdoOci8\PdoOci8Statement', $statement);
        $this->assertInstanceOf('\Traversable', $statement);
    }

    /**
     * @test
     */
    public function testQueryFetchDefault()
    {
        $db = $this->getConnection();
        $statement = $db->query('SELECT DUMMY FROM PDOOCI8.pdooci8');

        $this->assertInstanceOf('Jpina\PdoOci8\PdoOci8Statement', $statement);
    }

    /**
     * @test
     */
    public function testQueryFetchColumn()
    {
        $this->markTestIncomplete();
        $db = $this->getConnection();
        $statement = $db->query('SELECT * FROM PDOOCI8.pdooci8');
    }

    /**
     * @test
     */
    public function testQueryFetchClass()
    {
        $this->markTestIncomplete();
        $db = $this->getConnection();
        $statement = $db->query('SELECT * FROM PDOOCI8.pdooci8');
    }

    /**
     * @test
     */
    public function testQueryFetchInto()
    {
        $this->markTestIncomplete();
        $db = $this->getConnection();
        $statement = $db->query('SELECT * FROM PDOOCI8.pdooci8');
    }

    /**
     * @test
     */
    public function quoteObject()
    {
        $db = $this->getConnection();
        $quotedString = $db->quote(new \stdClass());
        $this->assertFalse($quotedString);
    }

    /**
     * @test
     */
    public function quoteInteger()
    {
        $db = $this->getConnection();

        $quotedString = $db->quote(5);
        $this->assertEquals("'5'", $quotedString);

        $quotedString = $db->quote(5, \PDO::PARAM_INT);
        $this->assertEquals("'5'", $quotedString);
    }

    /**
     * @test
     */
    public function quoteString()
    {
        $db = $this->getConnection();

        $quotedString = $db->quote('My string value');
        $this->assertEquals("'My string value'", $quotedString);
    }

    /**
     * @test
     */
    public function quoteNaughtyString()
    {
        $db = $this->getConnection();

        $quotedString = $db->quote("Naughty \' string");
        $this->assertEquals("'Naughty '' string'", $quotedString);
    }

    /**
     * @test
     */
    public function quoteComplexString()
    {
        $db = $this->getConnection();

        $quotedString = $db->quote("Co'mpl''ex \"st'\"ring");
        $this->assertEquals("'Co''mpl''''ex \"st''\"ring'", $quotedString);
    }

    /**
     * @test
     * @expectedException \Jpina\PdoOci8\PdoOci8Exception
     * @expectedExceptionMessage There is no active transaction
     */
    public function cannotRollback()
    {
        $db = $this->getNewConnection();
        $isSuccess = $db->rollback();
    }

    /**
     * @test
     */
    public function rollback()
    {
        $options = array(
            \PDO::ATTR_AUTOCOMMIT => true,
        );
        $db = $this->getNewConnection($options);
        $db->beginTransaction();
        $isSuccess = $db->rollback();
        $isAutoCommitEnabled = $db->getAttribute(\PDO::ATTR_AUTOCOMMIT);

        $this->assertTrue($isSuccess);
        $this->assertTrue($isAutoCommitEnabled);
    }

    /**
     * @test
     */
    public function rollbackNoAutoCommit()
    {
        $options = array(
            \PDO::ATTR_AUTOCOMMIT => false,
        );
        $db = $this->getNewConnection($options);
        $db->beginTransaction();
        $isSuccess = $db->rollback();
        $isAutoCommitEnabled = $db->getAttribute(\PDO::ATTR_AUTOCOMMIT);

        $this->assertTrue($isSuccess);
        $this->assertFalse($isAutoCommitEnabled);
    }
}
