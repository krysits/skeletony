<?php
namespace Krysits;

use PDO, stdClass;
use Krysits\Traits\Hash;

class Model {

	// traits
	use Hash;

	// vars
	public $_id;

	// config
	public $_table;
	public $_schema;
	public $_showId = false;
	public $_db;
	public $_out;

	// constructor
	public function __construct()
	{
		$this->_db = Database::getDb();	// getDB

		$this->setTable(); // derived method call
	}

	// methods
	public function setId($id) {
		return $this->_id = intval($id);
	}

	protected function setTable($table = '')
	{
		return $this->_table = ((new Config)->type == 'pgsql' ? $this->_schema . '.' : '') . $table;
	}

	public function getTableNameByNamespace($namespace = ''){

		if(empty($namespace)) return false;

		if(strrpos($namespace, "\\")){
			$result = @end(explode("\\", $namespace));
			return $this->_schema . '.' . $result . 's';
		}

		return false;
	}

	public function getIdByAnotherId($val = '', $keyIn = 'id', $keyOut = 'id')
	{
		if(empty($val)) return 0;

		if($id = $this->getRecords([$keyIn => $val], [$keyOut], 1)[0]->$keyOut) {
			return $id;
		}

		return 0;
	}

	public function getError($stmt)
	{
		echo "\nPDOStatement::errorInfo():\n";
		print_r($stmt->errorInfo());
	}

	public function setData($data = [])
	{
		if(empty($data)) return false;

		$this->_out = new stdClass;

		foreach (get_object_vars($this) as $variable => $value) {

			if (isset($data[$variable]) && substr($variable, 0, 1) != '_') {

				if(!$this->_showId && $variable == 'id') {
					// does not expose id to output
					//$this->$variable = $data[$variable];
				}
				else {
					$this->$variable = $this->_out->$variable = $data[$variable];
				}
			}
		}

		return $this->_out;
	}

	public function save($data = [], $id = 0)
	{
		if(empty($data)) return false;

		if($this->setId($id)) {
			return $this->update($data, $id);
		}

		$dbObj = $this->setData($data);

		if(property_exists($this, 'created_at')) {
			$dbObj->created_at = date('Y-m-d H:i:s');
		}

		if(property_exists($this, 'last_update')) {
			$dbObj->last_update = date('Y-m-d H:i:s');
		}

		$fields1 = $fields2 = $values = [];

		foreach($dbObj as $variable => $value) {
			if (isset($value)) {
				$fields1[] = " :" . $variable;
				$fields2[] = " " . $variable;
				$values[$variable] = $value;
			}
		}

		$sql = "INSERT INTO "
			. $this->_table
			." ("
			.implode(", ", $fields2)
			.") VALUES ("
			.implode(", ", $fields1)
			.")";

		$stmt = $this->_db->prepare($sql);
		$result = $stmt->execute($values);

		if(!$result) {
			$this->getError($stmt);
		}

		return $this->setId($this->_db->lastInsertId());
	}

	public function update($data = [], $id)
	{
		if(empty($data) && !$this->setId($id)) {
			return false;
		}

		$dbObj = $this->setData($data);

		$dbObj->updated_at = date('Y-m-d H:i:s');

		$fields = $values = [];

		$idField = '';

		foreach($dbObj as $variable => $value) {

			if (isset($value)) {

				if(!$idField) {
					$idField = $variable;
				}
				$fields[] = $variable . " =:" . $variable;
				$values[$variable] = $value;
			}
		}

		$sql = "UPDATE ";
		$sql .= $this->_table." SET " . implode(", ", $fields);
		$sql .= " WHERE $idField = " . intval($id);

		$stmt = $this->_db->prepare($sql);
		$result = $stmt->execute($values);

		if(!$result) {
			$this->getError($stmt);
		}

		return $result;
	}

	public function delete($id)
	{
		if(!$this->setId($id)) {
			return false;
		}

		$tmpObj = new stdClass;

		$tmpObj->updated_at = date('Y-m-d H:i:s');

		$tmpObj->deleted_at = date('Y-m-d H:i:s');

		$fields = $values = [];

		$idField = '';

		foreach($tmpObj as $variable => $value) {

			if (isset($value)) {

				if(!$idField) {
					$idField = $variable;
				}
				$fields[] = $variable . " =:" . $variable;
				$values[$variable] = $value;
			}
		}

		$sql = "UPDATE ";
		$sql .= $this->_table." SET " . implode(", ", $fields);
		$sql .= " WHERE id = " . intval($id);

		$stmt = $this->_db->prepare($sql);
		$result = $stmt->execute($values);

		if(!$result) {
			$this->getError($stmt);
		}

		return $result;
	}

	public function getRecord($id, $key = 'id')
	{
		if(empty($id)) return false;

		if($key == 'id') {
			$this->setId($id);
		}
		elseif(substr($key,1,2) == 'id' && strlen($id)==32) {}
		else {
			$id = intval($id);
		}

		$sql = "SELECT * FROM ".$this->_table." WHERE $key =:myid";
		$stmt = $this->_db->prepare($sql);
		$stmt->execute(['myid' => $id]);

		$data = $stmt->fetch(PDO::FETCH_ASSOC);

		if($data) return $this->setData($data);

		return false;
	}

	public function getRecords($filters = [], $selection = [], $limit = 100, $skip = 0) {

		$sql = "SELECT ".((empty($selection))?"*":implode(", ",$selection))." FROM ".$this->_table;
		
		$limitSql = '';
		if($limit || $skip)	{
			if((new Config)->type == 'pgsql') {
				$limitSql = " OFFSET ".$skip." LIMIT ".$limit;
			}
			else {
				$limitSql = " LIMIT ".$skip.", ".$limit;
			}
		}

		if(!empty($filters)){

			$vars = get_object_vars($this);
			$whereSql = [];

			foreach ($filters as $key => $value){
				if(in_array($key, array_keys($vars))) $whereSql[] = $key." = :".$key." ";
			}

			$sql .= " WHERE ".implode(" AND ", $whereSql);
			$sql .= $limitSql;

			$stmt = $this->_db->prepare($sql);

			$stmt->execute($filters);

			return $stmt->fetchAll(PDO::FETCH_OBJ);
		}
		$sql .= $limitSql;
		$result = $this->_db->query($sql);

		if($result) return $result->fetchAll(PDO::FETCH_OBJ);

		return [];
	}
};