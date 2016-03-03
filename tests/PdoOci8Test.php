<?php

namespace Jpina\Test\PdoOci8;

use Jpina\PdoOci8\PdoOci8;

class PdoOci8Test extends \PHPUnit_Framework_TestCase
{
    /** @var PdoOci8 */
    protected static $connection;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        static::$connection = static::getNewPdoConnection();
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
    public static function getNewPdoConnection()
    {
        $dsn = static::getConnectionString((int)getenv('DB_PORT'), getenv('DB_CHARSET'));
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');

        return new PdoOci8($dsn, $username, $password);
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
    protected function getNewConnection()
    {
        return static::getNewPdoConnection();
    }

    /**
     * @expectedException \Jpina\PdoOci8\PdoOci8Exception
     * @expectedExceptionMessage oci_new_connect(): ORA-12541: TNS:no listener
     * @expectedExceptionCode 12541
     */
    public function testCannotConnect()
    {
        $dsn = 'oci:database=//localhost:1521/XE';
        $username = 'NO_USER';
        $password = 'NO_PASSWORD';
        new PdoOci8($dsn, $username, $password);
    }

    public function testCanConnectWithPort()
    {
        $dsn = static::getConnectionString((int)getenv('DB_PORT'));
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $db = new PdoOci8($dsn, $username, $password);

        $this->assertInstanceOf('Jpina\PdoOci8\PdoOci8', $db);
    }

    public function testCanConnectWithPortAndCharset()
    {
        $dsn = static::getConnectionString((int)getenv('DB_PORT'), getenv('DB_CHARSET'));
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $db = new PdoOci8($dsn, $username, $password);

        $this->assertInstanceOf('Jpina\PdoOci8\PdoOci8', $db);
    }

    /**
     * @expectedException \Jpina\PdoOci8\PdoOci8Exception
     * @expectedExceptionMessage Invalid DSN
     */
    public function testBadDsn()
    {
        $dsn = 'this is a bad DSN';
        new PdoOci8($dsn);
    }

    public function testCanBeginTransaction()
    {
        $dsn = static::getConnectionString((int)getenv('DB_PORT'), getenv('DB_CHARSET'));
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $db = new PdoOci8($dsn, $username, $password);
        $isSuccess = $db->beginTransaction();
        $isAutoCommitEnabled = $db->getAttribute(\PDO::ATTR_AUTOCOMMIT);

        $this->assertTrue($isSuccess);
        $this->assertFalse($isAutoCommitEnabled);
    }

    public function testCanCommit()
    {
        $dsn = static::getConnectionString((int)getenv('DB_PORT'), getenv('DB_CHARSET'));
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $db = new PdoOci8($dsn, $username, $password);
        $isSuccess = $db->commit();
        $isAutoCommitEnabled = $db->getAttribute(\PDO::ATTR_AUTOCOMMIT);

        $this->assertTrue($isSuccess);
        $this->assertTrue($isAutoCommitEnabled);
    }

    public function testErrorCodeIsEmpty()
    {
        $dsn = static::getConnectionString((int)getenv('DB_PORT'), getenv('DB_CHARSET'));
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $db = new PdoOci8($dsn, $username, $password);
        $code = $db->errorCode();

        $this->assertNull($code);
    }

    public function testErrorCodeIsNotEmpty()
    {
        $this->markTestIncomplete();
        $dsn = static::getConnectionString((int)getenv('DB_PORT'), getenv('DB_CHARSET'));
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $db = new PdoOci8($dsn, $username, $password);

        // TODO Create a broken statement and execute it to trigger an error

        $this->assertNull($code);
    }

    public function testErrorInfo()
    {
        $db = $this->getNewConnection();
        $error = $db->errorInfo();

        $this->assertEquals('00000', $error[0]);
        $this->assertNull($error[1]);
        $this->assertNull($error[2]);
    }

    public function testErrorInfoOnError()
    {
        $this->markTestIncomplete();
        $db = $this->getConnection();

        // TODO Create a broken statement and execute it to trigger an error

        $error = $db->errorInfo();

        $this->assertEquals('00000', $error[0]);
        $this->assertNull($error[1]);
        $this->assertNull($error[2]);
    }

    public function testCanExecuteSelectStmt()
    {
        $db = $this->getConnection();
        $query = 'SELECT * FROM DUAL';
        $rowsAffected = $db->exec($query);

        $this->assertFalse($rowsAffected);
    }

    public function testCanExecuteInsertStmt()
    {
        $db = $this->getConnection();
        $query = 'INSERT INTO test_123 (dummy) VALUES (1)';
        $rowsAffected = $db->exec($query);

        $this->assertTrue(is_int($rowsAffected));
        $this->assertGreaterThanOrEqual(0, $rowsAffected);
    }

    public function testCanExecuteUpdateStmt()
    {
        $db = $this->getConnection();
        $query = 'Update test_123 SET dummy = 2 WHERE dummy = 1';
        $rowsAffected = $db->exec($query);

        $this->assertTrue(is_int($rowsAffected));
        $this->assertGreaterThanOrEqual(0, $rowsAffected);
    }

    public function testCanExecuteDeleteStmt()
    {
        $db = $this->getConnection();
        $query = 'DELETE FROM test_123 WHERE dummy = 2';
        $rowsAffected = $db->exec($query);

        $this->assertTrue(is_int($rowsAffected));
        $this->assertGreaterThanOrEqual(0, $rowsAffected);
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
        $db = $this->getConnection();
        $isInTransaction = $db->inTransaction();
        $this->assertFalse($isInTransaction);
    }

    public function testGetLastInsertId()
    {
        $this->markTestIncomplete();
    }

    public function testPrepare()
    {
        $db = $this->getConnection();
        $statement = $db->prepare('SELECT * FROM DUAL');

        $this->assertInstanceOf('Jpina\PdoOci8\PdoOci8Statement', $statement);
    }

    public function testQuery()
    {
        $this->markTestIncomplete();
    }

    public function testQuoteObject()
    {
        $db = $this->getConnection();
        $quotedString = $db->quote(new \stdClass());
        $this->assertFalse($quotedString);
    }

    public function testQuoteInteger()
    {
        $db = $this->getConnection();

        $quotedString = $db->quote(5);
        $this->assertEquals("'5'", $quotedString);

        $quotedString = $db->quote(5, \PDO::PARAM_INT);
        $this->assertEquals("'5'", $quotedString);
    }

    public function testQuoteString()
    {
        $db = $this->getConnection();

        $quotedString = $db->quote('My string value');
        $this->assertEquals("'My string value'", $quotedString);
    }

    public function testQuoteNaughtyString()
    {
        $db = $this->getConnection();

        $quotedString = $db->quote("Naughty \' string");
        $this->assertEquals("'Naughty '' string'", $quotedString);
    }

    public function testQuoteComplexString()
    {
        $db = $this->getConnection();

        $quotedString = $db->quote("Co'mpl''ex \"st'\"ring");
        $this->assertEquals("'Co''mpl''''ex \"st''\"ring'", $quotedString);
    }

    public function testCanRollback()
    {
        $dsn = static::getConnectionString((int)getenv('DB_PORT'), getenv('DB_CHARSET'));
        $username = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $db = new PdoOci8($dsn, $username, $password);
        $isSuccess = $db->rollback();
        $isAutoCommitEnabled = $db->getAttribute(\PDO::ATTR_AUTOCOMMIT);

        $this->assertTrue($isSuccess);
        $this->assertTrue($isAutoCommitEnabled);
    }
}
