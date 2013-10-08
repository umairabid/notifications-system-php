<?php

class Notifications
{
	
	const COMMENT_ON_USER_STATUS = 1; 
	
	public $mysqli;
	
	public function __construct(){
		$this->mysqli = new mysqli('localhost', 'root', '', 'notifications_system');
	}
	
	public function saveNotification($object_id, $subject_id, $actor_id, $type_id)
	{
		$created_date = date("Y-m-d H:i:s");
		$this->mysqli->query("INSERT INTO notifications 
		(actor_id, subject_id, object_id, type_id, status, count, created_date, updated_date)
		VALUES ($actor_id, $subject_id, $object_id, $type_id, 'unseen', 1, '$created_date', '')");
	}
	
	public function getNotifications($subjectId, $offset = 0)
	{
		$result = $this->mysqli->query("SELECT nf.*, actor.name AS actor_name, subject.name AS subject_name
				FROM notifications nf
				INNER JOIN users actor ON nf.actor_id = actor.id
				INNER JOIN users SUBJECT ON nf.subject_id = SUBJECT.id
				WHERE subject_id = $subjectId
				AND status = 'unseen'
				LIMIT $offset, 5");
		$rows = array();
		while($row = $result->fetch_assoc()){
			$row['object'] = $this->getObjectRow($row['type_id'], $row['object_id']);
			$rows[] = $row;
		}
		
		$notifications = array();
		
		foreach($rows as $row){
			$notification = array(
				'message' => $this->getNotificationMessage($row),
				'actor_id' => $row['actor_id'],
				'subject_id' => $row['subject_id'],
				'object' => $row['object_id'],
			);
			$notifications[] = $notification;
		}
		
		return $notifications;
	}
	
	protected function getObjectRow($typeId, $objectId)
	{
		switch($typeId){
			case self::COMMENT_ON_USER_STATUS:
				return $this->mysqli->query("SELECT * FROM status WHERE id = $objectId")->fetch_assoc();
		}
	}
	
	protected function getNotificationMessage($row){
		switch($row['type_id']){
			case self::COMMENT_ON_USER_STATUS:
				return "{$row['actor_name']} commented on {$row['subject_name']} status {$row['object']['status']}";
		}
	}
	
	public function markSubjectNotificationsSeen($subjectId){
		$result = $this->mysqli->query("SELECT nf.* FROM notifications nf WHERE subject_id = $subjectId");
		$rows = array();
		while($row = $result->fetch_assoc()){
			$this->mysqli->query("Update notifications SET status = 'seen' Where id = {$row['id']}");
		}
		return;
	}
}

?>