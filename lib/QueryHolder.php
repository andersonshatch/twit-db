<?php

/**
 * Holds queries for reuse, closes them on shutdown
 */
class QueryHolder
{
	private static $queries = [];
	private static $registeredShutdownHook = false;

	/**
	 * Prepare a SQL statement and cache it
	 * If $querySQL is unseen, a new statement will be prepared; otherwise, a cached statement will be reset and returned
	 *
	 * @param mysqli $mysqli database handle
	 * @param $querySQL SQL query to prepare
	 * @return mysqli_stmt prepared statement
	 */
	public static function prepareAndHoldQuery(mysqli $mysqli, $querySQL) {
		if(!self::$registeredShutdownHook) {
			register_shutdown_function("QueryHolder::closeQueries");
			self::$registeredShutdownHook = true;
		}

		if(array_key_exists($querySQL, self::$queries)) {
			$statement = self::$queries[$querySQL];
			$statement->reset();
			return $statement;
		}

		$statement = $mysqli->prepare($querySQL);
		self::$queries[$querySQL] = $statement;

		return $statement;
	}

	/**
	 * Close all queries held
	 *
	 * Will be called on shutdown automatically (if any queries are held)
	 */
	public static function closeQueries() {
		foreach(self::$queries as $querySQL => $statement) {
			$statement->close();
			unset(self::$queries[$querySQL]);
		}
	}
}