<?php

class DatabaseUtils {
	/**
	 * See if a column exists in a table in this database
	 * @param string $tableName name of the table to check
	 * @param string $columnName name of the column to check for
	 * @param mysqli $mysqli database handle
	 * @return true if a column with $columnName exists on table $tableName in this database
	 */
	public static function columnExists($tableName, $columnName, mysqli $mysqli) {
		$columnExistsSQL = "SELECT 1 FROM information_schema.columns
							WHERE table_schema = '".DB_NAME."'
							AND table_name = '".$tableName."'
							AND column_name = '".$columnName."'";
		if(!$mysqli->query($columnExistsSQL)->fetch_all()) {
			return false;
		}

		return true;
	}

	/**
	 * See if a table exists in this database
	 * @param string $tableName name of the table to check for
	 * @param mysqli $mysqli database handle
	 * @return true if a table with $tableName exists in this database
	 */
	public static function tableExists($tableName, mysqli $mysqli) {
		$tableExistsSQL = "SELECT 1 FROM information_schema.tables
						   WHERE table_schema = '".DB_NAME."'
						   AND table_name = '".$tableName."'";
		if(!$mysqli->query($tableExistsSQL)->fetch_all()) {
			return false;
		}

		return true;
	}

	/**
	 * Rename a table
	 * @param string $currentTableName name of the table to be renamed
	 * @param string $newTableName name to rename the table to
	 * @param mysqli $mysqli database handle
	 */
	public static function renameTable($currentTableName, $newTableName, mysqli $mysqli) {
		$mysqli->query("RENAME TABLE `$currentTableName` TO `$newTableName`");
	}

	/**
	 * Find the varchar max length of a column in this database
	 * @param $tableName name of the table to check
	 * @param $columnName name of the column to check
	 * @param mysqli $mysqli database handle
	 * @return int|null if the column exists and is a varchar column, then its length will be returned as an int, else null
	 */
	public static function varcharColumnLength($tableName, $columnName, mysqli $mysqli) {
		$lengthSQL = "SELECT character_maximum_length
					  FROM information_schema.columns
					  WHERE table_schema = '".DB_NAME."'
					  AND table_name = '".$tableName."'
					  AND column_name = '".$columnName."'";

		$row = $mysqli->query($lengthSQL)->fetch_row();
		$result = is_array($row) ? $row[0] : null;
		return $result === null ? null : intval($result);
	}
}

?>