<?php

namespace Jpina\PdoOci8;

use Jpina\PdoOci8\SqlStateCode;

class OracleSqlStateCode
{
    private function __construct()
    {
    }

    /**
     * @param int $errorCode
     * @return string
     * @see https://docs.oracle.com/cd/F49540_01/DOC/server.815/a58231/appd.htm
     */
    public static function getSqlStateErrorCode($errorCode)
    {
        switch (true) {
            case $errorCode === 0:
                return SqlStateCode::SUCCESSFUL_COMPLETION;
            case $errorCode === 1095 || $errorCode === 1403:
                return SqlStateCode::NO_DATA;
            case $errorCode === 2126:
                return SqlStateCode::INVALID_DESCRIPTOR_COUNT;
            case $errorCode === 2121:
                return SqlStateCode::CONNECTION_DOES_NOT_EXIST;
            case $errorCode >= 3000 && $errorCode <= 3099:
                return SqlStateCode::FEATURE_NOT_SUPPORTED;
            case $errorCode === 1427 || $errorCode === 2112:
                return SqlStateCode::CARDINALITY_VIOLATION;
            case $errorCode === 1401 || $errorCode === 1406:
                return SqlStateCode::STRING_DATA_RIGHT_TRUNCATION;
            case $errorCode === 1405 || $errorCode === 2124:
                return SqlStateCode::INDICATOR_VARIABLE_NOT_SUPPLIED;
            case $errorCode === 1426 || $errorCode === 1438:
            case $errorCode === 1455 || $errorCode === 1457:
                return SqlStateCode::NUMERIC_VALUE_OUT_OF_RANGE;
            case $errorCode >= 1800 && $errorCode <= 1899:
                return SqlStateCode::DATETIME_FIELD_OVERFLOW;
            case $errorCode === 1476:
                return SqlStateCode::DIVISION_BY_ZERO;
            case $errorCode === 911 || $errorCode === 1425:
                return SqlStateCode::INVALID_ESCAPE_CHAR;
            case $errorCode === 1411:
                return SqlStateCode::INDICATOR_OVERFLOW;
            case $errorCode === 1025 || $errorCode === 1488:
            case $errorCode >= 4000 && $errorCode <= 4019:
                return SqlStateCode::INVALID_PARAM_VALUE;
            case $errorCode >= 1479 && $errorCode <= 1480:
                return SqlStateCode::UNTERMINATED_C_STRING;
            case $errorCode === 1424:
                return SqlStateCode::INVALID_ESCAPE_SEQUENCE;
            case $errorCode === 1:
            case $errorCode >= 2290 && $errorCode <= 2299:
                return SqlStateCode::INTEGRITY_CONSTRAINT_VIOLATION_1;
            case $errorCode >= 1001 && $errorCode <= 1003:
            case $errorCode === 1410 || $errorCode === 8006:
            case $errorCode === 2114 || $errorCode === 2117:
            case $errorCode === 2118 || $errorCode === 2122:
                return SqlStateCode::INVALID_CURSOR_STATE;
            case $errorCode === 2091 || $errorCode === 2092:
                return SqlStateCode::TRANSACTION_ROLLBACK;
            case $errorCode === 22 || $errorCode === 251:
            case $errorCode >= 900 && $errorCode <= 999:
            case $errorCode === 1031:
            case $errorCode >= 1490 && $errorCode <= 1493:
            case $errorCode >= 1700 && $errorCode <= 1799:
            case $errorCode >= 1900 && $errorCode <= 2099:
            case $errorCode >= 2140 && $errorCode <= 2289:
            case $errorCode >= 2420 && $errorCode <= 2424:
            case $errorCode >= 2450 && $errorCode <= 2499:
            case $errorCode >= 3276 && $errorCode <= 3299:
            case $errorCode >= 4040 && $errorCode <= 4059:
            case $errorCode >= 4070 && $errorCode <= 4099:
                return SqlStateCode::SYNTAX_ERROR_OR_ACCESS_RULE_VIOLATION_3;
            case $errorCode === 1402:
                return SqlStateCode::WITH_CHECK_OPTION_VIOLATION;
            case $errorCode >= 370 && $errorCode <= 429:
            case $errorCode >= 600 && $errorCode <= 899:
            case $errorCode >= 6430 && $errorCode <= 6449:
            case $errorCode >= 7200 && $errorCode <= 7999:
            case $errorCode >= 9700 && $errorCode <= 9999:
                return SqlStateCode::SYSTEM_ERRORS;
            case $errorCode >= 18 && $errorCode <= 35:
            case $errorCode >= 50 && $errorCode <= 68:
            case $errorCode >= 2376 && $errorCode <= 2399:
            case $errorCode >= 4020 && $errorCode <= 4039:
                return SqlStateCode::RESOURCE_ERROR;
            case $errorCode >= 100 && $errorCode <= 120:
            case $errorCode >= 440 && $errorCode <= 569:
                return SqlStateCode::MULTI_THREADED_SERVER_OR_DETACHED_PROCESS_ERROR;
            case $errorCode >= 150 && $errorCode <= 159:
            case $errorCode === 2128:
            case $errorCode >= 2700 && $errorCode <= 2899:
            case $errorCode >= 3100 && $errorCode <= 3199:
            case $errorCode >= 6200 && $errorCode <= 6249:
                return SqlStateCode::ORACLE_XA_AND_2_TASK_INTERFACE_ERROR;
            case $errorCode >= 200 && $errorCode <= 369:
            case $errorCode >= 1100 && $errorCode <= 1250:
                return SqlStateCode::ARCHIVAL_AND_MEDIA_RECOVERY_ERROR;
            case $errorCode >= 6500 && $errorCode <= 6599:
                return SqlStateCode::PL_SQL_ERROR;
            case $errorCode >= 6000 && $errorCode <= 6149:
            case $errorCode >= 6250 && $errorCode <= 6429:
            case $errorCode >= 6600 && $errorCode <= 6999:
            case $errorCode >= 12100 && $errorCode <= 12299:
            case $errorCode >= 12500 && $errorCode <= 12599:
                return SqlStateCode::SQL_NET_DRIVER_ERROR;
            case $errorCode >= 430 && $errorCode <= 439:
                return SqlStateCode::LICENSING_ERROR;
            case $errorCode >= 570 && $errorCode <= 599:
            case $errorCode >= 7000 && $errorCode <= 7199:
                return SqlStateCode::SQL_CONNECT_ERROR;
            case $errorCode >= 1000 && $errorCode <= 1099:
            case $errorCode >= 1400 && $errorCode <= 1489:
            case $errorCode >= 1495 && $errorCode <= 1499:
            case $errorCode >= 1500 && $errorCode <= 1699:
            case $errorCode >= 2400 && $errorCode <= 2419:
            case $errorCode >= 2425 && $errorCode <= 2449:
            case $errorCode >= 4060 && $errorCode <= 4069:
            case $errorCode >= 8000 && $errorCode <= 8190:
            case $errorCode >= 12000 && $errorCode <= 12019:
            case $errorCode >= 12300 && $errorCode <= 12499:
            case $errorCode >= 12700 && $errorCode <= 21999:
                return SqlStateCode::SQL_EXECUTE_PHASE_ERROR;
            case $errorCode === 2100:
                return SqlStateCode::OUT_OF_MEMORY;
            case $errorCode === 2101:
                return SqlStateCode::INCONSISTENT_CURSOR_CACHE_MISMATCH;
            case $errorCode === 2102:
                return SqlStateCode::INCONSISTENT_CURSOR_CACHE_NO_ENTRY;
            case $errorCode === 2103:
                return SqlStateCode::INCONSISTENT_CURSOR_CACHE_REF_OUT_OF_RANGE;
            case $errorCode === 2104:
                return SqlStateCode::INCONSISTENT_HOST_CACHE_NOT_AVAILABLE;
            case $errorCode === 2105:
                return SqlStateCode::INCONSISTENT_CURSOR_CACHE_NOT_FOUND;
            case $errorCode === 2106:
                return SqlStateCode::INCONSISTENT_CURSOR_CACHE_INVALID_NUMBER;
            case $errorCode === 2107:
                return SqlStateCode::PROGRAM_TOO_OLD;
            case $errorCode === 2108:
                return SqlStateCode::INVALID_DESCRIPTOR_PASSED;
            case $errorCode === 2109:
                return SqlStateCode::INCONSISTENT_HOST_CACHE_REF_OUT_OF_RANGE;
            case $errorCode === 2110:
                return SqlStateCode::INCONSISTENT_HOST_CACHE_INVALID_ENTRY_TYPE;
            case $errorCode === 2111:
                return SqlStateCode::HEAP_CONSISTENCY_ERROR;
            case $errorCode === 2113:
                return SqlStateCode::UNABLE_TO_OPEN_MESSAGE_FILE;
            case $errorCode === 2115:
                return SqlStateCode::CODE_GENERATION_INTERNAL_CONSISTENCY_FAILED;
            case $errorCode === 2116:
                return SqlStateCode::REENTRANT_CODE_GENERATOR_GAVE_INVALID_CONTEXT;
            case $errorCode === 2119:
                return SqlStateCode::INVALID_HSTDEF_ARGUMENT;
            case $errorCode === 2120:
                return SqlStateCode::FIRST_AND_SECOND_ARGS_TO_SQLRCN_BOTH_NULL;
            case $errorCode === 2122:
                return SqlStateCode::INVALID_OPEN_OR_PREPARE;
            case $errorCode === 2123:
                return SqlStateCode::APPLICATION_CONTEXT_NOT_FOUND;
            case $errorCode === 2125:
                return SqlStateCode::CONNECTION_ERROR_NO_ERROR_TEXT;
            case $errorCode === 2127:
                return SqlStateCode::PRECOMPILER_VERSION_MISMATCH;
            case $errorCode === 2129:
                return SqlStateCode::FETCHED_NUMBER_OF_BYTES_IS_ODD;
            case $errorCode === 2130:
                return SqlStateCode::EXEC_TOOLS_INTERFACE_NOT_AVAILABLE;
            case $errorCode >= 10000 && $errorCode <= 10999:
                return SqlStateCode::DEBUG_EVENTS;
        }

        return SqlStateCode::GENERAL_ERROR;
    }
}
