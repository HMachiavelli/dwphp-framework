<?php

namespace DwPhp\Library\Models;

use DwPhp\Library\Sql;

abstract class AbstractObject
{
	public function __construct($params = null)
	{
		$this->setDbTable($this->getNameTable());
		if ($params !== null) {
			$this->setCreateMethods($params);
		}
	}

	public function setCreateMethods($params = [])
	{
		foreach ($params as $property => $value) {
			if (is_int($property)) {
				continue;
			}
			$method = 'set' . ucfirst(strtolower(str_replace('_', '', $property)));
			if (method_exists($this, $method)) {
				$this->$method($value);
			}
		}
	}

	public function getNum()
	{
		$db = new sql();

		$db->setTable($this->dbTable);
		$db->setFields('id');
		$db->Select();

		return $db->getRecords();
	}

	public function getAll($order = '', $limit = '', $search = array())
	{
		$db = new sql();

		$db->setTable($this->dbTable);
		$db->setFields('*');

		if (!empty($order)) {
			$db->setOrderBy($order);
		}
		if (!empty($limit)) {
			$db->setLimit($limit);
		}
		if (count($search) > 0) {
			$where = $this->getSearchQuery($db->getWhere(), $search);
			$db->setWhere($where);
		}
		$db->Select();
		$child = get_called_class();

		$return = array();
		while ($row = $db->getRow()) {
			$return[] = new $child($row);
		}

		return $return;
	}

	public function getById($id)
	{
		$db = new sql();

		$db->setTable($this->dbTable);
		$db->setFields('*');
		$db->setWhere(array('id' => $id));
		$db->setLimit(1);
		$db->Select();

		$return = false;
		if ($db->getRecords() > 0) {
			$row = $db->getRow();

			$child = get_called_class();

			$return = new $child($row);
		}

		return $return;
	}

	public function getSearchQuery($current, $search)
	{
		$where = ($current != '' ? $current . ' AND ' : '');
		foreach ($search as $i => $item) {
			$where .= $i . ' LIKE "%' . $item . '%" ' . ($i < count($search) - 1 ? ' OR ' : '');
		}

		return $where;
	}

	public function insert($debug = false)
	{

		$params_tmp = get_object_vars($this);
		if (isset($params_tmp['dbTable'])) {
			unset($params_tmp['dbTable']);
		}
		if (isset($params_tmp['dateUpdate'])) {
			unset($params_tmp['dateUpdate']);
		}
		if (isset($params_tmp['userUpdate'])) {
			unset($params_tmp['userUpdate']);
		}

		$params = array_filter($params_tmp, function ($var) {
			return !is_null($var);
		});

		$db = new sql();
		$db->setTest($debug);
		$db->setTable($this->dbTable);
		$db->setSet($params);
		$db->Insert();
		$this->id = $db->getInsertId();

		return $this->id;
	}

	public function update($debug = false)
	{
		$params_tmp = get_object_vars($this);

		if (isset($params_tmp['dbTable'])) {
			unset($params_tmp['dbTable']);
		}
		if (isset($params_tmp['dateCreate'])) {
			unset($params_tmp['dateCreate']);
		}
		if (isset($params_tmp['userCreate'])) {
			unset($params_tmp['userCreate']);
		}

		$params = array_filter($params_tmp, function ($var) {
			return !is_null($var);
		});

		$db = new sql();
		$db->setTest($debug);
		$db->setTable($this->dbTable);
		$db->setSet($params);
		$db->setWhere(array('id' => $this->getId()));
		$db->Update();

		return $this->getId();
	}

	public function delete($id = 0)
	{
		$db = new sql();
		$db->setTable($this->dbTable);
		$db->setWhere(array('id' => $this->getId()));
		if ((int)$id != 0) {
			$db->setWhere(array('id' => $id));
		}

		return $db->Delete();
	}

	public function setId($id)
	{
		$this->id = (int)$id;

		return $this;
	}

	public function getDbTable()
	{
		return $this->dbTable;
	}

	public function setDbTable($dbTable)
	{
		$this->dbTable = $dbTable;

		return $this;
	}

	public function toArray()
	{
		$ret = get_object_vars($this);

		unset($ret['dbTable']);

		return $ret;
	}

	public function __toString()
	{
		return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}
}
