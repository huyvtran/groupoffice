<?php

namespace go\core\orm;

use DateTime;
use Exception;
use GO;
use go\core\App;
use go\core\db\Query;
use go\core\model\Module;
use go\core\ErrorHandler;
use go\core\jmap;
use PDOException;
use go\core\model\Acl;

/**
 * The EntityType class
 * 
 * This holds information about the entity.
 * 
 * id: The ID in the database used for foreign keys
 * className: The PHP class name used in the PHP API
 * name: The name of the entity for the JMAP client API
 * moduleId: The module ID this entity belongs to
 * 
 * It's also used for routing short routes like "Note/get" instead of "community/notes/Note/get"
 * 
 */
class EntityType implements \go\core\data\ArrayableInterface {

	private $className;	
	private $id;
	private $name;
	private $moduleId;	
  private $clientName;
	private $defaultAclId;
	
	/**
	 * The highest mod sequence used for JMAP data sync
	 * 
	 * @var int
	 */
	protected $highestModSeq;
	
	private $highestUserModSeq;
	
	private $modSeqIncremented = false;
	
	private $userModSeqIncremented = false;
	
	/**
	 * The name of the entity for the JMAP client API
	 * 
	 * eg. "note"
	 * @return string
	 */
	public function getName() {
		return $this->clientName;
	}
	
	/**
	 * The PHP class name used in the PHP API
	 * 
	 * @return string
	 */
	public function getClassName() {
		return $this->className;
	}
	
	/**
	 * The ID in the database used for foreign keys
	 * 
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * The module ID this entity belongs to
	 * 
	 * @return in
	 */
	public function getModuleId() {
		return $this->moduleId;
	}	
	
	
	/**
	 * Get the module this type belongs to.
	 * 
	 * @return Module
	 */
	public function getModule() {
		return Module::findById($this->moduleId);
	}

	/**
	 * Find by PHP API class name
	 * 
	 * @param string $className
	 * @return static
	 */
	public static function findByClassName($className) {

		$e = new static;
		$e->className = $className;
		
		$record = (new Query)
						->select('*')
						->from('core_entity')
						->where('clientName', '=', $className::getClientName())
						->single();

		if (!$record) {
			$module = Module::findByClass($className);
		
			if(!$module) {
				throw new Exception("No module found for ". $className);
			}

			$record = [];
			$record['moduleId'] = isset($module) ? $module->id : null;
			$record['name'] = self::classNameToShortName($className);
      $record['clientName'] = $className::getClientName();
			App::get()->getDbConnection()->insert('core_entity', $record)->execute();

			$record['id'] = App::get()->getDbConnection()->getPDO()->lastInsertId();
		} else
		{
			$e->defaultAclId = $record['defaultAclId'] ?? null; // in the upgrade situation this column is not there yet.
			$e->highestModSeq = (int) $record['highestModSeq'];//) ? (int) $record['highestModSeq'] : null;
		}

		$e->id = $record['id'];
		$e->moduleId = $record['moduleId'];
		$e->clientName = $record['clientName'];
		$e->name = $record['name'];
		
		
		return $e;
	}

	/**
	 * The highest mod sequence used for JMAP data sync
	 * 
	 * @return int
	 */
	public function getHighestModSeq() {
		if(isset($this->highestModSeq)) {
			return $this->highestModSeq;
		}

		$this->highestModSeq = (new Query())
			->selectSingleValue("highestModSeq")
			->from("core_entity")
			->where(["id" => $this->id])			
			->single();

		return $this->highestModSeq;
	}

	/**
	 * Clear cached modseqs.
	 * 
	 * Calling this function is needed when the request is running for a long time and multiple increments are possible.
	 * For example when sending newsletters on a CLI script.
	 * 
	 * @return $this
	 */
	public function clearCache() {

		$this->modSeqIncremented = false;
		$this->userModSeqIncremented = false;
		$this->highestModSeq = null;
		$this->highestUserModSeq = null;

		return $this;
	}


	
	/**
	 * Creates a short name based on the class name.
	 * 
	 * This is used to generate response name. 
	 * 
	 * eg. class go\modules\community\notes\model\Note becomes just "note"
	 * 
	 * @return string
	 */
	private static function classNameToShortName($cls) {
		return substr($cls, strrpos($cls, '\\') + 1);
	}
	
	/**
	 * Find all registered.
	 * 
	 * @return static[]
	 */
	public static function findAll(Query $query = null) {
		
		if(!isset($query)) {
			$query = new Query();
		}
		
		$records = $query
						->select('e.*, m.name AS moduleName, m.package AS modulePackage')
						->from('core_entity', 'e')
						->join('core_module', 'm', 'm.id = e.moduleId')
						->where(['m.enabled' => true])
						->all();
		
		$i = [];
		foreach($records as $record) {
			$type = static::fromRecord($record);
			$cls = $type->getClassName();
			if(!class_exists($cls)) {
				GO()->warn($cls .' not found!');
				continue;
			}
			$i[] = $type;
		}
		
		return $i;
	}

	/**
	 * Find by db id
	 * 
	 * @param int $id
	 * @return static
	 */
	public static function findById($id) {
		$record = (new Query)
						->select('e.*, m.name AS moduleName, m.package AS modulePackage')
						->from('core_entity', 'e')
						->join('core_module', 'm', 'm.id = e.moduleId')
						->where('id', '=', $id)->where(['m.enabled' => true])
						->single();
		
		if(!$record) {
			return false;
		}
		
		return static::fromRecord($record);
	}
	
	/**
	 * Find by client API name
	 * 
	 * @param string $name
	 * @return static
	 */
	public static function findByName($name) {
		$record = (new Query)
						->select('e.*, m.name AS moduleName, m.package AS modulePackage')
						->from('core_entity', 'e')
						->join('core_module', 'm', 'm.id = e.moduleId')
						->where('clientName', '=', $name)->where(['m.enabled' => true])
						->single();
		
		if(!$record) {
			return false;
		}
		
		return static::fromRecord($record);
	}
	
	/**
	 * Convert array of entity names to ids
	 * 
	 * @param string[] $names eg ['Contact', 'Note']
	 * @return int[] eg. [1,2]
	 */
	public static function namesToIds($names) {
		return array_map(function($name) {
			$e = static::findByName($name);
			if(!$e) {
				throw new \Exception("Entity '$name'  not found");
			}
			return $e->getId();
		}, $names);	
	}
  

	private static function fromRecord($record) {
		$e = new static;
		$e->id = $record['id'];
		$e->name = $record['name'];
    $e->clientName = $record['clientName'];
		$e->moduleId = $record['moduleId'];
		$e->highestModSeq = (int) $record['highestModSeq'];
		$e->defaultAclId = $record['defaultAclId'] ?? null; // in the upgrade situation this column is not there yet.

		if (isset($record['modulePackage'])) {
			if($record['modulePackage'] == 'core') {
				$e->className = 'go\\core\\model\\' . ucfirst($e->name);	
			} else
			{
				$e->className = 'go\\modules\\' . $record['modulePackage'] . '\\' . $record['moduleName'] . '\\model\\' . ucfirst($e->name);
			}
		} else {			
			$e->className = 'GO\\' . ucfirst($record['moduleName']) . '\\Model\\' . ucfirst($e->name);			
		}
		
		return $e;
	}
	
	/**
	 * Register multiple changes for JMAP
	 * 
	 * This function increments the entity type's modSeq so the JMAP sync API 
	 * can detect this change for clients.
	 * 
	 * It writes the changes into the 'core_change' table.
	 * 	 
	 * @param Query|array $changedEntities A query object or an array that provides "entityId", "aclId" and "destroyed" 
	 * in this order. When using an array you may also provide a list of entity ID's. In that case it's assumed that these 
	 * entites have no ACL and are not destroyed but modified.
	 * 
	 */
	public function changes($changedEntities) {		
		
		GO()->getDbConnection()->beginTransaction();
		
		$this->highestModSeq = $this->nextModSeq();		
		
		if(!is_array($changedEntities)) {
			$changedEntities->select('"' . $this->getId() . '", "'. $this->highestModSeq .'", NOW()', true);		
		} else {

			if(empty($changedEntities)) {
				return;
			}

			if(!is_array($changedEntities[0])) {
				$changedEntities = array_map(function($entityId) {
					return [$entityId, null, 0, $this->getId(), $this->highestModSeq, new DateTime()];
				}, $changedEntities);
			} else{
				$changedEntities = array_map(function($r) {
					return array_merge($r, [$this->getId(), $this->highestModSeq, new DateTime()]);
				}, $changedEntities);
			}
		}
		
		
		try {
			$stmt = GO()->getDbConnection()->insert('core_change', $changedEntities, ['entityId', 'aclId', 'destroyed', 'entityTypeId', 'modSeq', 'createdAt']);
			$stmt->execute();
		} catch(\Exception $e) {
			GO()->getDbConnection()->rollBack();
			throw $e;
		}
		
		if(!$stmt->rowCount()) {
			//if no changes were written then rollback the modSeq increment.
			GO()->getDbConnection()->rollBack();
		} else
		{
			GO()->getDbConnection()->commit();
		}				
	}

	/**
	 * Register a change for JMAP
	 * 
	 * This function increments the entity type's modSeq so the JMAP sync API 
	 * can detect this change for clients.
	 * 
	 * It writes the changes into the 'core_change' table.
	 * 
	 * It also writes user specific changes 'core_user_change' table ({@see \go\core\orm\Mapping::addUserTable()). 
	 * 
	 * @param jmap\Entity $entity
	 */
	public function change(jmap\Entity $entity) {
		$this->highestModSeq = $this->nextModSeq();

		$record = [
				'modSeq' => $this->highestModSeq,
				'entityTypeId' => $this->id,
				'entityId' => $entity->id(),
				'aclId' => $entity->findAclId(),
				'destroyed' => $entity->isDeleted(),
				'createdAt' => new DateTime()
						];

		if(!GO()->getDbConnection()->insert('core_change', $record)->execute()) {
			throw new \Exception("Could not save change");
		}
	}
		
	/**
	 * Checks if a saved entity needs changes for the JMAP API with change() and userChange()
	 * 
	 * @param Entity $entity
	 * @throws Exception
	 */
	public function checkChange(Entity $entity) {
		
//		GO()->debug($entity->getClientName(). ' checkChange() ' . $entity->getId() . 'mod: '.implode(', ', array_keys($entity->getModified())));
		
		if(!$entity->isDeleted()) {
			$modifiedPropnames = array_keys($entity->getModified());		
			$userPropNames = $entity->getUserProperties();

			$entityModified = !empty(array_diff($modifiedPropnames, $userPropNames));
			$userPropsModified = !empty(array_intersect($userPropNames, $modifiedPropnames));
		} else
		{
			$entityModified = true;
			$userPropsModified = false;
		}
		
	
		if($entityModified) {			
			$this->change($entity);
		}
		
		if($userPropsModified) {
			$this->userChange($entity);
		}
		
		if($entity->isDeleted()) {
			
			$where = [
					'entityTypeId' => $this->id,
					'entityId' => $entity->id(),
					'userId' => GO()->getUserId()
							];
			
			$stmt = GO()->getDbConnection()->delete('core_change_user', $where);
			if(!$stmt->execute()) {
				throw new \Exception("Could not delete user change");
			}
		}
	}
	
	private function userChange(Entity $entity) {
		$data = [
				'modSeq' => $this->nextUserModSeq()			
						];

		$where = [
				'entityTypeId' => $this->id,
				'entityId' => $entity->id(),
				'userId' => GO()->getUserId()
						];

		$stmt = GO()->getDbConnection()->update('core_change_user', $data, $where);
		if(!$stmt->execute()) {
			throw new \Exception("Could not save user change");
		}

		if(!$stmt->rowCount()) {
			$where['modSeq'] = 1;
			if(!GO()->getDbConnection()->insert('core_change_user', $where)->execute()) {
				throw new \Exception("Could not save user change");
			}
		}
	}
	
	/**
	 * Get the modSeq for the user specific properties.
	 * 
	 * @return string
	 */
	public function getHighestUserModSeq() {
		if(!isset($this->highestUserModSeq)) {
			$this->highestUserModSeq = (int) (new Query())
						->selectSingleValue("highestModSeq")
						->from("core_change_user_modseq")
						->where(["entityTypeId" => $this->id, "userId" => GO()->getUserId()])
						->single();					
		}
		return $this->highestUserModSeq;
	}
	
	
	/**
	 * Get the modification sequence
	 * 
	 * @param string $entityClass
	 * @return int
	 */
	public function nextModSeq() {
		
		if($this->modSeqIncremented) {
			return $this->highestModSeq;
		}
		/*
		 * START TRANSACTION
		 * SELECT counter_field FROM child_codes FOR UPDATE;
		  UPDATE child_codes SET counter_field = counter_field + 1;
		 * COMMIT
		 */
		$modSeq = (new Query())
						->selectSingleValue("highestModSeq")
						->from("core_entity")
						->where(["id" => $this->id])
						->forUpdate()
						->single();
		$modSeq++;

		App::get()->getDbConnection()
						->update(
										"core_entity", 
										['highestModSeq' => $modSeq],
										["id" => $this->id]
						)->execute(); //mod seq is a global integer that is incremented on any entity update
	
		$this->modSeqIncremented = true;
		
		$this->highestModSeq = $modSeq;
		
		return $modSeq;
	}	
	
	/**
	 * Get the modification sequence
	 * 
	 * @param string $entityClass
	 * @return int
	 */
	public function nextUserModSeq() {
		
		if($this->userModSeqIncremented) {
			return $this->getHighestUserModSeq();
		}
		
		$modSeq = (new Query())
			->selectSingleValue("highestModSeq")
			->from("core_change_user_modseq")
			->where(["entityTypeId" => $this->id, "userId" => GO()->getUserId()])
			->forUpdate()
			->single();

		$modSeq++;

		App::get()->getDbConnection()
						->replace(
										"core_change_user_modseq", 
										[
												'highestModSeq' => $modSeq,
												"entityTypeId" => $this->id,
												"userId" => GO()->getUserId()
										]
						)->execute(); //mod seq is a global integer that is incremented on any entity update
	
		$this->userModSeqIncremented = true;
		
		$this->highestUserModSeq = $modSeq;
		
		return $modSeq;
	}
	
	private function createAcl() {
		$acl = new \go\core\model\Acl();
		$acl->usedIn = 'core_entity.defaultAclId';
		$acl->ownedBy = 1;
		//$acl->addGroup(\go\core\model\Group::ID_INTERNAL, \go\core\model\Acl::LEVEL_WRITE);
		if(!$acl->save()) {
			throw new \Exception('Could not save default ACL');
		}
		
		return $acl;
	}
	
	public function getDefaultAclId() {
		if(!$this->isAclOwner()) {
			return null;
		}
		
		if(!isset($this->defaultAclId)) {
			
			GO()->getDbConnection()->beginTransaction();
			
			$acl = $this->createAcl();
			
			if(!GO()->getDbConnection()->update('core_entity', ['defaultAclId' => $acl->id], ['id' => $this->getId()])->execute()) {
				GO()->getDbConnection()->rollBack();
				throw new \Exception("Could not save defaultAclId");
			}
			
			GO()->getDbConnection()->commit();
			
			$this->defaultAclId = $acl->id;
		}
		
		return $this->defaultAclId;
	}
	
	/**
	 * Returns true when this entity type holds an ACL id for permissions.
	 * 
	 * @return bool
	 */
	public function isAclOwner() {
		$cls = $this->getClassName();
		return $cls != \go\core\model\Search::class && 
						(
							is_subclass_of($cls, \go\core\acl\model\AclOwnerEntity::class) || 
							(is_subclass_of($cls, \GO\Base\Db\ActiveRecord::class) && $cls::model()->aclField() && !$cls::model()->isJoinedAclField)
						);
	}
	
	/**
	 * Returns true if this entity supports custom fields
	 * 
	 * @return bool
	 */
	public function supportsCustomFields() {
		return method_exists($this->getClassName(), "getCustomFields");
	}
	
	/**
	 * Returns true if the entity supports a files folder.
	 * 
	 * @return bool
	 */
	public function supportsFiles() {
		return property_exists($this->getClassName(), 'filesFolderId') || property_exists($this->getClassName(), 'files_folder_id');
	}

	/**
	 * Returns an array with group ID as key and permission level as value.
	 * 
	 * @return array eg. ["2" => 50, "3" => 10]
	 */
	public function getDefaultAcl() {

		$defaultAclId = $this->getDefaultAclId();
		if(!$defaultAclId) {
			return null;
		}
		$a = Acl::findById($defaultAclId);
		$acl = [];
		foreach($a->groups as $group) {
			$acl[$group->groupId] = $group->level;
		}

		return $acl;
	}

	public function setDefaultAcl($acl) {
		$defaultAclId = $this->getDefaultAclId();
		if(!$defaultAclId) {
			throw new \Exception("Entity '".$this->name."' does not support a default ACL");
		}
		$a = Acl::findById($defaultAclId);
		foreach($acl as $groupId => $level) {
			$a->addGroup($groupId, $level);
		}
		return $a->save();
	}

	public function toArray($properties = null) {
		return [
				"name" => $this->getName(),
				"isAclOwner" => $this->isAclOwner(),
				"defaultAcl" => $this->getDefaultAcl(),
				"supportsCustomFields" => $this->supportsCustomFields(),
				"supportsFiles" => $this->supportsFiles()
		];
	}
}
