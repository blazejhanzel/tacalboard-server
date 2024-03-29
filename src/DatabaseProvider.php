<?php

class DatabaseProvider {
    private static self $instance;
    private object $connection;
    private static bool $transaction = false;

    private function __construct() {
        define('DB_SERVER', 'localhost');
        define('DB_USERNAME', 'root');
        define('DB_PASSWORD', '');
        define('DB_NAME', 'tacalboard');
    }

    private static function checkInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DatabaseProvider();
            self::$instance->connect();
        }
        else if (self::$instance->connection->connect_errno) {
            self::$instance->connect();
        }
    }

    private function connect() {
        $this->connection = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($this->connection->connect_errno) {
            echo "Failed to connect to MySQL: " . $this->connection->connect_error;
            exit();
        }
        $this->connection->set_charset("utf8");
    }

    public static function query($sql) {
        self::checkInstance();

        if (self::$transaction) {
            try {
                return self::$instance->connection->query($sql);
            } catch (mysqli_sql_exception $exception) {
                self::$instance->connection->rollback();
                throw $exception;
            }
        }
        return self::$instance->connection->query($sql);
    }

    public static function transactionBegin() {
        self::checkInstance();

        self::$transaction = true;
        self::$instance->connection->begin_transaction();
    }

    public static function transactionEnd() {
        self::checkInstance();

        self::$transaction = false;
        try {
            self::$instance->connection->commit();
        } catch (mysqli_sql_exception $exception) {
            self::$instance->connection->rollback();
            throw $exception;
        }
    }
}
