<?php
namespace Ssg\Core;

use \PDO;
use \PDOException;
use \Exception;

/**
 * Class DatabaseFactory
 *
 * Use it like this:
 * $database = DatabaseFactory::getFactory()->getConnection();
 *
 * That's my personal favourite when creating a database connection.
 * It's a slightly modified version of Jon Raphaelson's excellent answer on StackOverflow:
 * http://stackoverflow.com/questions/130878/global-or-singleton-for-database-connection
 *
 * Full quote from the answer:
 *
 * "Then, in 6 months when your app is super famous and getting dugg and slashdotted and you decide you need more than
 * a single connection, all you have to do is implement some pooling in the getConnection() method. Or if you decide
 * that you want a wrapper that implements SQL logging, you can pass a PDO subclass. Or if you decide you want a new
 * connection on every invocation, you can do do that. It's flexible, instead of rigid."
 *
 * Thanks! Big up, mate!
 */
class SQLSRVDatabaseFactory
{
	private static $factory;
	private $database;

	public static function getFactory()
	{
		if (!self::$factory) {
			self::$factory = new DatabaseFactory();
		}
		return self::$factory;
	}

	public function getConnection() {
		try{
			if (!$this->database) {
				$options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING);
				/*$this->database = new PDO(
					Config::get('SQLSRV_DB_TYPE') . 
					':Server=' . Config::get('SQLSRV_DB_HOST') . 
					';Database=' . Config::get('SQLSRV_DB_NAME'),
					Config::get('SQLSRV_DB_USER'), Config::get('SQLSRV_DB_PASS'), $options
				);*/
				$this->database = new PDO('sqlsrv:Server=SEMATEL-SERVER;Database=db_Sematel','sa', 'SematelServer2014');
			}
		} catch (PDOException $e) {
			throw new Exception("PDOException: ".$e->getMessage());;
		}
		return $this->database;
	}
}