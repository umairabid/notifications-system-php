<?php

namespace Application\Model;

use Doctrine\ORM\Query\ResultSetMapping;

class Notification extends YoloModel {

    const NEW_LISTERS_OF_OWNED_EXPERIENCE = 1;
    const TAGGED_IN_USER_EXPERIENCE = 2;
    const USER_EXPERIENCE_LIKE = 3;
    const FACEBOOK_INVITATION_ACCEPTED = 4;
    const USER_EXPERIENCE_STORY_LIKE = 5;
    const USER_EXPERIENCE_PHOTO_LIKE = 6;
    const USER_EXPIERENCE_COMMENT = 7;
    const USER_EXPERIENCE_COMMENT_LIKE = 8;
    const USER_EXPERIENCE_USER_COMMENT_LIKE = 9;
    const USER_EXPERIENCE_USER_ALSO_COMMENT = 10;
    const USER_EXPERIENCE_ADDED_IN_TODO_LIST = 11;
    const USER_EXPERIENCE_ADDED_IN_DONE_LIST = 12;
    const EXPERIENCE_CREATED = 13;
    const USER_EXPERIENCE_COMPLETED = 14;
    const FRIEND_REQUEST_RESPONDED = 15;

    public function __construct($em) {
        parent::__construct($em);
        $this->entityClass = 'Application\Entity\Notification';
        $this->repository = $em->getRepository($this->entityClass);
    }

    public function saveNotification($actorId, $subjectId, $objectId, $typeId, $multipleActors = 1) {
        $actor = $this->getServiceLocator()->get('User')->findByPk($actorId);
        $subject = $this->getServiceLocator()->get('User')->findByPk($subjectId);
        $data = array(
            'actor' => $actor,
            'subject' => $subject,
            'object_id' => $objectId,
            'object_type' => 'experience',
            'type_id' => $typeId,
            'status' => 'not_seen',
            'multiple_actor' => $multipleActors,
        );
        $this->save($data);

        return;
    }

    /* public function saveNotification($actorId, $subjectId, $objectId, $typeId) {
      $actor = $this->getServiceLocator()->get('User')->findByPk($actorId);
      $subject = $this->getServiceLocator()->get('User')->findByPk($subjectId);
      $notification = $this->find(array('subject_id' => $subject->id, 'type_id' => $typeId));
      if ($notification && $this->isMultipleEnabled($typeId)) {
      $this->save(array('multiple_actor' => $notification->multiple_actor + 1), $notification->id);
      } else {
      $data = array(
      'actor' => $actor,
      'subject' => $subject,
      'object_id' => $objectId,
      'object_type' => 'experience',
      'type_id' => $typeId,
      'status' => 'not_seen',
      'multiple_actor' => '1',
      );
      $this->save($data);
      }
      return;
      } */

    public function taggedInUserExperience($actorId, $subjectId, $objectId) {
        
    }

    public function getUserNotifications($id, $idType = 'subject_id', $limit = NULL, $lastTime = NULL, $status = NULL, $notificationTypes = NULL, $aggregate = FALSE, $orderBy = NULL, $direction = NULL, $loggedInUserId = NULL, $messageType = 'alert') {
        $notifications = array();
        //////////////////
        //$qb = $this->em->createQueryBuilder();
        $columns = array('nf.id', 'nf.actor_id', 'nf.subject_id', 'nf.object_id', 'nf.type_id', 'nf.status', 'nf.created_date', 'nf.updated_date');
        $where = array();
        $params = array();
        if ($idType == 'subject_id') {//is_array($id)
            $where[] = "nf.subject_id " . (is_array($id) ? "IN" : "=") . " (:subject_id)";
            $params[":subject_id"] = $id;
        } else {
            $where[] = "nf.actor_id " . (is_array($id) ? "IN" : "=") . " (:actor_id)";
            $params[":actor_id"] = $id;
        }
        $qLimit = '';
        $qJoin = '';
        $groupBy = '';
        if ($orderBy == NULL || empty($orderBy)) {
            $orderBy = 'nf.id DESC';
        }
        //$qb->add("select", "nf")->from("Application\Entity\Notification", "nf");
        //$qb->add("where", "nf.subject_id = :subject_id")->setParameter("subject_id", $id);
        //$qb->add("orderBy", "nf.id DESC");
        if ($limit != NULL && !empty($limit)) {
            //$qb->setMaxResults($limit);
            $qLimit = "LIMIT $limit";
        }
        if ($lastTime != NULL && !empty($lastTime)) {
            //$qb->andWhere("nf.created_date < :lastTime")->setParameter(":lastTime", $lastTime);
            $where[] = "nf.created_date " . ($direction == 'less' ? '<' : '>') . " :lastTime";
            $params[":lastTime"] = $lastTime;
        }
        if ($status != NULL && !empty($status)) {
            //$qb->andWhere("nf.status = :status")->setParameter(":status", $status);
            $where[] = "nf.status = :status";
            $params[":status"] = $status;
        }
        /* if ($shownIn != NULL && !empty($shownIn)) {
          //$qb->innerJoin('Application\Entity\Notification', 'nft', 'ON', 'nf.type_id = nft.id');
          //$qb->andWhere("nft.shown_in LIKE :shown_in")->setParameter(":shown_in", "%$shownIn%");
          $qJoin = "INNER JOIN yolo_notification_type AS nft ON nf.type_id = nft.id";
          $where[] = "nft.shown_in LIKE :shown_in";
          $params[":shown_in"] = "%$shownIn%";
          } */
        if ($notificationTypes != NULL && !empty($notificationTypes)) {
            $where[] = "nf.type_id " . (is_array($notificationTypes) ? "IN" : "=") . " (" . (is_array($notificationTypes) ? implode(',', $notificationTypes) : $notificationTypes) . ")";
            //$params[":type_id"] = $notificationTypes;
        }
        if ($aggregate === TRUE) {
            $aggregateTypes = $this->getMultipleEnabledTypes();
            $aggregateTypes = implode(',', $aggregateTypes);
            $columns[] = 'SUM(nf.multiple_actor) AS multiple_actor';
            $groupBy = " GROUP BY " .
                    "CASE WHEN nf.type_id IN ($aggregateTypes) THEN nf.subject_id ELSE nf.id END," .
                    "CASE WHEN nf.type_id IN ($aggregateTypes) THEN nf.object_id ELSE nf.id END," .
                    "CASE WHEN nf.type_id IN ($aggregateTypes) THEN nf.type_id ELSE nf.id END";
            //$params[":aggregateTypes"] = implode(',', $aggregateTypes);
            //$params[":aggregateTypes"] = $aggregateTypes;
            //->setParameter(':aggregateTypes', $aggregateTypes);
            /* $qb->groupBy('CASE WHEN nf.type_id IN (:aggregateTypes) THEN nf.subject_id ELSE nf.id END')
              ->addGroupBy('CASE WHEN nf.type_id IN (:aggregateTypes) THEN nf.object_id ELSE nf.id END')
              ->addGroupBy('CASE WHEN nf.type_id IN (:aggregateTypes) THEN nf.type_id ELSE nf.id END')
              ->setParameter(':aggregateTypes', $aggregateTypes); */
        } else if ($idType == 'actor_id') {//Aggregate tagged yoloers (Client's requirement)
            $aggregate = TRUE;
            $aggregateTypes = Notification::TAGGED_IN_USER_EXPERIENCE . ', ' . Notification::FACEBOOK_INVITATION_ACCEPTED;
            $columns[] = 'SUM(nf.multiple_actor) AS multiple_actor';
            $groupBy = " GROUP BY " .
                    "CASE WHEN nf.type_id IN ($aggregateTypes) THEN nf.actor_id ELSE nf.id END," .
                    "CASE WHEN nf.type_id IN ($aggregateTypes) THEN nf.object_id ELSE nf.id END," .
                    "CASE WHEN nf.type_id IN ($aggregateTypes) THEN nf.type_id ELSE nf.id END";
        } else {
            $columns[] = 'nf.multiple_actor';
        }
        //Apply actor's privacy settings...
        $qJoin = "LEFT JOIN (yolo_user_sharing_settings sc INNER JOIN yolo_sharing_setting_type st ON sc.type_id = st.id) ON (nf.actor_id = sc.user_id AND nf.type_id = st.notification_type_id)";
        $qJoin .= " LEFT JOIN yolo_user actor ON actor.id = nf.actor_id"; //join for actor
        $qJoin .= " LEFT JOIN yolo_user subject ON subject.id = nf.subject_id"; //join for subject
        $where[] = "(sc.yolo_status IS NULL OR sc.yolo_status='1')"; //Can be 1, can also be NULL
        $where[] = "actor.status = 'active'";
        $where[] = "subject.status = 'active'";
        //...Apply actor's privacy settings
        //Don't notify user about his own actions...
        if ($loggedInUserId == NULL or empty($loggedInUserId)) {
            $loggedInUserId = $this->getServiceLocator()->get('AuthStorage')->read()->id;
        }
        if ($loggedInUserId != NULL && !empty($loggedInUserId)) {
            /* if ($idType == 'actor_id') {
              $where[] = "(nf.subject_id IS NULL OR nf.subject_id != (:loggeInUserId))";
              } else {
              $where[] = "nf.actor_id != (:loggeInUserId)";
              }
              $params[":loggeInUserId"] = $loggedInUserId; */
            if ($idType == 'subject_id') {
                $where[] = "nf.actor_id != (:loggeInUserId)";
                $params[":loggeInUserId"] = $loggedInUserId;
            }
        }
        //...Don't notify user about his own actions
        $columns = implode(',', $columns);
        $where = implode(' AND ', $where);
        $queryString = "SELECT $columns FROM yolo_notifications AS nf $qJoin WHERE $where $groupBy ORDER BY $orderBy";
        $queryString2 = "SELECT nf.id,SUM(nf.multiple_actor) AS multiple_actor FROM yolo_notifications AS nf $qJoin WHERE $where $groupBy ORDER BY $orderBy $qLimit";
        //echo $queryString; exit;
        //Mapping...
        $rsm = new ResultSetMapping;
        $rsm->addEntityResult('Application\Entity\Notification', 'nf');
        $rsm->addFieldResult('nf', 'id', 'id');
        $rsm->addFieldResult('nf', 'actor_id', 'actor_id');
        $rsm->addFieldResult('nf', 'subject_id', 'subject_id');
        $rsm->addFieldResult('nf', 'object_id', 'object_id');
        $rsm->addFieldResult('nf', 'type_id', 'type_id');
        $rsm->addFieldResult('nf', 'status', 'status');
        $rsm->addFieldResult('nf', 'multiple_actor', 'multiple_actor');
        $rsm->addFieldResult('nf', 'created_date', 'created_date');
        $rsm->addFieldResult('nf', 'updated_date', 'updated_date');

        $query = $this->em->createNativeQuery($queryString, $rsm);
        $query->setParameters($params);

        $userNotifications = $query->getResult();
        //...Mapping
        if ($aggregate) {
            //get multiple actors...
            $multipleActors = array();
            $conn = $this->em->getConnection();
            $stmt = $conn->prepare($queryString2);
            foreach ($params as $key => $param) {
                $key = ltrim($key, ':');
                if (is_array($param)) {
                    $param = implode(',', $param);
                }
                $stmt->bindValue($key, $param);
            }
            $stmt->execute();
            $results = $stmt->fetchAll();
            foreach ($results as $result) {
                $multipleActors[$result['id']] = $result['multiple_actor'];
            }
        }
        //...Mapping
        //$userNotifications = $this->findAll(array('subject_id' => $id, 'status' => 'not_seen'), array('id' => 'desc'));
        //////////////////
        foreach ($userNotifications as $notification) {
            if ($aggregate && isset($multipleActors[$notification->id])) {
                $notification->multiple_actor = $multipleActors[$notification->id];
            }
            $object = $this->getNotificationObject($notification->type_id, $notification->object_id);
            $userExperience = $this->getUserExperience($object);
            if (!$object || (!empty($userExperience) && $userExperience->status == 'deleted')) {//skip
                continue;
            }
            $message = $this->getNotificationMessage($notification, $object, $messageType);
            if ($notification->type_id != 4) {
                $notifications[] = array(
                    'message' => $message,
                    'object' => $notification,
                    'foreign_object' => $object
                );
            } elseif (!$this->getServiceLocator()->get('User')->isFriend($notification->actor->id)) {
                $notifications[] = array(
                    'message' => $message,
                    'object' => $notification,
                    'foreign_object' => $object
                );
            }
        }
        return $notifications;
    }

    public function getNotificationMessage($notification, $object, $messageType) {
        //$translate = $this->getServiceLocator()->get('viewhelpermanager')->get('translate');
        $url = $this->getServiceLocator()->get('viewhelpermanager')->get('url');
        $trim = $this->getServiceLocator()->get('viewhelpermanager')->get('trim');
        $loggedInUserId = $this->getServiceLocator()->get('AuthStorage')->read()->id;

        $message = '';
        switch ($notification->type_id) {
            case Notification::NEW_LISTERS_OF_OWNED_EXPERIENCE:
                $link = $url('experience', array('action' => 'notlisteddetail', 'id' => $object->id));
                $message = "<h3><a class='fancybox' data-fancybox-type='ajax' href='$link'>$object->title</a> <span>has $notification->multiple_actor new " . ($notification->multiple_actor == 1 ? 'lister' : 'listers') . "</span></h3>";
                break;

            case Notification::TAGGED_IN_USER_EXPERIENCE:
                if ($notification->multiple_actor == 1) {
                    if ($notification->subject_id == $loggedInUserId) {
                        $subject = "you";
                    } else {
                        $subject = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->subject->id))}'>" . $notification->subject->getName() . "</a>";
                    }
                    $link = $url('experience', array('action' => 'listeddetail', 'id' => $object->id));
                    $link_add = $url('add', array('action' => 'addtype', 'id' => $object->experience->id));
                    $actorGender = $notification->actor->gender == 'Male' ? 'his' : 'her';
                    $addButton = ($messageType == 'alert') ? "</span><a class='fancybox iconAdd request_button' href='$link_add' data-fancybox-type='ajax'>Add</a>" : "";
                    $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>" . $notification->actor->getName() . "</a><span> added $subject to $actorGender experience <a class='fancybox' data-fancybox-type='ajax' href='$link'>" . $object->experience->title . "</a></span>$addButton</h3>";
                } else {
                    $link = $url('user', array('action' => 'profile', 'id' => $object->user_id)) . "?ue={$object->id}";
                    $actorGender = $notification->actor->gender == 'Male' ? 'his' : 'her';
                    $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>" . $notification->actor->getName() . "</a><span> added $notification->multiple_actor yoloers to $actorGender experience <a href='$link'>" . $object->experience->title . "</a></span></h3>";
                }
                break;

            case Notification::USER_EXPERIENCE_LIKE:
                //$link = $url('user', array('action' => 'profile', 'id' => $object->user_experiences->user_id)) . "?ue={$object->user_experiences->id}";
                $link = $url('experience', array('action' => 'listeddetail', 'id' => $object->user_experience_id));
                if ($messageType == 'alert') {
                    if ($notification->multiple_actor == 1) {
                        $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>{$notification->actor->getName()}</a><span> liked</span> <a class='fancybox' data-fancybox-type='ajax' href='$link'>{$object->user_experiences->experience->title}</a></h3>";
                    } else {
                        $message = "<h3><a href='javascript:void(0)'>" . $notification->multiple_actor . "</a><span> yoloers liked</span> <a class='fancybox' data-fancybox-type='ajax' href='$link'>{$object->user_experiences->experience->title}</a></h3>";
                    }
                } else {
                    if ($notification->subject->id == $notification->actor->id):
                        $actorGender = $notification->actor->gender == 'Male' ? 'his' : 'her';
                        $message = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>{$notification->actor->getName()}</a> liked $actorGender own experience – <a class='fancybox' data-fancybox-type='ajax' href='$link'>{$object->user_experiences->experience->title}</a>";
                    else:
                        $message = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>{$notification->actor->getName()}</a> liked <a href='{$url('user', array('action' => 'profile', 'id' => $notification->subject->id))}'>{$notification->subject->getName()}</a>'s experience – <a class='fancybox' data-fancybox-type='ajax' href='$link'>{$object->user_experiences->experience->title}</a>";
                    endif;
                }
                break;

            case Notification::FACEBOOK_INVITATION_ACCEPTED:
                if ($notification->subject_id == $loggedInUserId) {
                    $subject = "your";
                } else {
                    $subject = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->subject->id))}'>" . $notification->subject->getName() . "</a>'s";
                }
                if ($messageType == 'alert') {
                    $isReq = $this->getServiceLocator()->get('User')->isRequestedForAlert($notification->actor->id);
                    if ($isReq) {
                        $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>" . $notification->actor->getName() . "</a><span> has joined yolo </span><a class='request_button' href='#'>Sent</a></h3>";
                    } else {
                        $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>" . $notification->actor->getName() . "</a><span> has joined yolo </span><a class='request_button' onclick='sendFriendRequestFromAlert(" . $notification->actor->id . ", this);' href='javascript:void(0)'>Request</a></h3>";
                    }
                } else {
                    $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>" . $notification->actor->getName() . "</a><span> has joined yolo </span></h3>";
                }
                break;

            case Notification::USER_EXPERIENCE_STORY_LIKE:
                $storylink = $url('experience', array('action' => 'story', 'id' => $object->user_experience->id));
                if ($messageType == 'alert') {
                    $link = $url('user', array('action' => 'profile', 'id' => $object->user_experience->user_id)) . "?ue={$object->user_experience->id}";
                    if ($loggedInUserId == $notification->subject_id) {
                        $subject = "your";
                    } else {
                        $subject = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->subject->id))}'>{$notification->subject->getName()}</a>'s";
                    }
                    if ($notification->multiple_actor == 1) {
                        $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>{$notification->actor->getName()}</a> liked $subject <a href='$link'>{$object->user_experience->experience->title}</a> <a href='$storylink' class='fancybox' data-fancybox-type='ajax'>story</a></h3>";
                    } else {
                        $message = "<h3><a href='javascript:void(0)'>" . $notification->multiple_actor . "</a> yoloers liked $subject <a href='$link'>{$object->user_experience->experience->title}</a> <a href='$storylink' class='fancybox' data-fancybox-type='ajax'>story</a></h3>";
                    }
                } else {
                    $storyText = $trim($object->user_experience->story, 20);
                    if ($notification->actor->id == $notification->subject->id):
                        $actorGender = $notification->actor->gender == 'Male' ? 'his' : 'her';
                        $message = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>" . $notification->actor->getName() . "</a> liked $actorGender own <a href='$storyText' class='fancybox' data-fancybox-type='ajax'>story</a><br/>\"$storyText\"";
                    else:
                        $message = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>" . $notification->actor->getName() . "</a> liked <a href='{$url('user', array('action' => 'profile', 'id' => $notification->subject->id))}'>" . $notification->subject->getName() . "</a>'s <a href='$storylink' class='fancybox' data-fancybox-type='ajax'>story</a><br/>\"$storyText\"";
                    endif;
                }
                break;

            case Notification::USER_EXPERIENCE_PHOTO_LIKE:
                if ($messageType == 'alert') {
                    $link = $url('experience', array('action' => 'listeddetail', 'id' => $object->user_experience_id));
                    //$link = $url('user', array('action' => 'profile', 'id' => $object->user_experience->user_id)) . "?ue={$object->user_experience->id}";
                    if ($loggedInUserId == $notification->subject_id) {
                        $subject = "your";
                    } else {
                        $subject = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->subject->id))}'>{$notification->subject->getName()}</a>'s";
                    }
                    if ($notification->multiple_actor == 1) {
                        $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>{$notification->actor->getName()}</a> liked $subject <a href='$link'>{$object->user_experience->experience->title}</a> <a href='javascript:void(0)' onclick='openUserGallery(" . $notification->subject->id . ", " . $object->id . ")'>photo</a></h3>";
                    } else {
                        $message = "<h3><a href='javascript:void(0)'>" . $notification->multiple_actor . "</a> yoloers liked $subject <a href='$link'>{$object->user_experience->experience->title}</a> <a href='javascript:void(0)' onclick='openUserGallery(" . $notification->subject->id . ", " . $object->id . ")'>photo</a></h3>";
                    }
                } else {
                    if ($notification->actor->id == $notification->subject->id):
                        $actorGender = $notification->actor->gender == 'Male' ? 'his' : 'her';
                        $message = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>{$notification->actor->getName()}</a> liked $actorGender own photo<br/><a href='javascript:void(0)' onclick='openUserGallery(" . $notification->subject->id . ", " . $object->id . ")'><img src='$object->picture_url'/></a>";
                    else:
                        $message = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>{$notification->actor->getName()}</a> liked <a href='{$url('user', array('action' => 'profile', 'id' => $notification->subject->id))}'>{$notification->subject->getName()}</a>'s photo<br/><a href='javascript:void(0)' onclick='openUserGallery(" . $notification->subject->id . ", " . $object->id . ")'><img src='$object->picture_url'/></a>";
                    endif;
                }
                break;

            case Notification::USER_EXPIERENCE_COMMENT:
                //$link = $url('user', array('action' => 'profile', 'id' => $object->user_experience->user_id)) . "?ue={$object->user_experience->id}";
                $link = $url('experience', array('action' => 'comment', 'id' => $object->user_experience_id));
                if ($messageType == 'alert') {
                    if ($notification->multiple_actor == 1) {
                        $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>" . $notification->actor->getName() . "</a><span> commented on</span> <a href='$link'>{$object->user_experience->experience->title}</a></h3>";
                    } else {
                        $message = "<h3><a href='javascript:void(0)'>" . $notification->multiple_actor . "</a><span>yoloers commented on</span> <a href='$link'>{$object->user_experience->experience->title}</a></h3>";
                    }
                } else {
                    $commentText = $trim($object->comment, 20);
                    $commentlink = $url('experience', array('action' => 'comment', 'id' => $object->user_experience->id));
                    if ($notification->actor->id == $notification->subject->id):
                        $actorGender = $notification->actor->gender == 'Male' ? 'his' : 'her';
                        $message = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>" . $notification->actor->getName() . "</a> commented on $actorGender own experience – <a href='$link'>{$object->user_experience->experience->title}</a><br/><a href='$commentlink' class='fancybox' data-fancybox-type='ajax'>\"$commentText\"</a>";
                    else:
                        $message = "<a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>" . $notification->actor->getName() . "</a> commented on <a href='{$url('user', array('action' => 'profile', 'id' => $notification->subject->id))}'>" . $notification->subject->getName() . "</a>'s experience – <a href='$link'>{$object->user_experience->experience->title}</a><br/><a href='$commentlink' class='fancybox' data-fancybox-type='ajax'>\"$commentText\"</a>";
                    endif;
                }
                break;

            case Notification::USER_EXPERIENCE_COMMENT_LIKE:
                //$link = $url('user', array('action' => 'profile', 'id' => $object->user_experience->user_id)) . "?ue={$object->user_experience->id}";
                $link = $url('experience', array('action' => 'comment', 'id' => $object->user_experience_id));
                if ($messageType == 'alert') {
                    if ($loggedInUserId == $object->user_id) {
                        $commenter = "your";
                    } else {
                        $commenter = "<a href='{$url('user', array('action' => 'profile', 'id' => $object->user->id))}'>{$object->user->getName()}</a>'s";
                    }
                    if ($notification->multiple_actor == 1) {
                        $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>{$notification->actor->getName()}</a> liked $commenter comment on <a href='$link'>{$object->user_experience->experience->title}</a></h3>";
                    } else {
                        $message = "<h3><a href='javascript:void(0)'>" . $notification->multiple_actor . "</a> yoloers liked $commenter comment on <a href='$link'>{$object->user_experience->experience->title}</a></h3>";
                    }
                } else {
                    $commentText = $trim($object->comment, 20);
                    $commentlink = $url('experience', array('action' => 'comment', 'id' => $object->user_experience->id));
                    if ($notification->actor->id == $notification->subject->id):
                        $actorGender = $notification->actor->gender == 'Male' ? 'his' : 'her';
                        $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>{$notification->actor->getName()}</a> liked $actorGender own comment, <a href='$commentlink' class='fancybox' data-fancybox-type='ajax'>\"$commentText\"</a> on <a href='$link'>{$object->user_experience->experience->title}</a></h3>";
                    else:
                        $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>{$notification->actor->getName()}</a> liked <a href='{$url('user', array('action' => 'profile', 'id' => $object->user->id))}'>{$object->user->getName()}</a>'s comment, <a href='$commentlink' class='fancybox' data-fancybox-type='ajax'>\"$commentText\"</a> on <a href='$link'>{$object->user_experience->experience->title}</a></h3>";
                    endif;
                }
                break;

            case Notification::USER_EXPERIENCE_USER_ALSO_COMMENT:
                $link = $url('user', array('action' => 'profile', 'id' => $object->user_experience->user_id)) . "?ue={$object->user_experience->id}";
                if ($notification->multiple_actor == 1) {
                    $message = "<h3><a href='{$url('user', array('action' => 'profile', 'id' => $notification->actor->id))}'>" . $notification->actor->getName() . "</a><span> also commented on</span> <a href='$link'>{$object->user_experience->experience->title}</a></h3>";
                } else {
                    $message = "<h3><a href='javascript:void(0)'>" . $notification->multiple_actor . "</a><span> yoloers also commented on</span> <a href='$link'>{$object->user_experience->experience->title}</a></h3>";
                }
                break;

            case Notification::USER_EXPERIENCE_ADDED_IN_TODO_LIST:
            case Notification::USER_EXPERIENCE_ADDED_IN_DONE_LIST:
            case Notification::USER_EXPERIENCE_COMPLETED:
                $link = $url('user', array('action' => 'profile', 'id' => $object->user_id)) . "?ue={$object->id}";
                $actorGender = $notification->actor->gender == 'Male' ? 'his' : 'her';
                $objectType = $object->type == 'todo' ? 'To Do' : 'Done';
                $message = "<h3><a href='" . $url('user', array('action' => 'profile', 'id' => $notification->actor->id)) . "'>{$notification->actor->getName()}</a> added <a href='$link'>{$object->experience->title}</a> to $actorGender $objectType list</h3>";
                break;

            case Notification::EXPERIENCE_CREATED:
                $link = $url('experience', array('action' => 'notlisteddetail', 'id' => $object->id));
                //$message = "<h3><a href='" . $url('user', array('action' => 'profile', 'id' => $notification->actor->id)) . "'>{$notification->actor->getName()}</a> created a new experience, <a class='fancybox' data-fancybox-type='ajax' href='$link'>$object->title</a><h3>";
                $message = "<h3><a href='" . $url('user', array('action' => 'profile', 'id' => $notification->actor->id)) . "'>{$notification->actor->getName()}</a> created <a class='fancybox' data-fancybox-type='ajax' href='$link'>$object->title</a><h3>";
                break;

            /* case Notification::USER_EXPERIENCE_COMPLETED:
              $link = $url('user', array('action' => 'profile', 'id' => $object->user_id)) . "?ue={$object->id}";
              $message = "<h3><a href='" . $url('user', array('action' => 'profile', 'id' => $notification->actor->id)) . "'>{$notification->actor->getName()}</a> has completed <a href='$link'>{$object->experience->title}</a><h3>";
              break; */

            case Notification::FRIEND_REQUEST_RESPONDED:
                $subjectUser = $object->user;
                $actorUser = $object->requested_friend;
                if ($messageType == 'alert') {
                    if ($object->status == 'accepted') {
                        if ($loggedInUserId == $actorUser->id) {
                            $actor = "You";
                        } else {
                            $actor = "<a href='" . $url('user', array('action' => 'profile', 'id' => $actorUser->id)) . "'>{$actorUser->getName()}</a>";
                        }
                        if ($loggedInUserId == $subjectUser->id) {
                            $subject = 'you';
                        } else {
                            $subject = "<a href='" . $url('user', array('action' => 'profile', 'id' => $subjectUser->id)) . "'>{$subjectUser->getName()}</a>";
                        }
                        $message = "<h3>$actor <span>and</span> $subject <span>are now yoloers</span></h3>";
                    } else {
                        if ($loggedInUserId == $actorUser->id) {
                            $actor = "You";
                        } else {
                            $actor = "<a href='" . $url('user', array('action' => 'profile', 'id' => $actorUser->id)) . "'>{$actorUser->getName()}</a>";
                        }
                        if ($loggedInUserId == $subjectUser->id) {
                            $subject = 'your';
                        } else {
                            $subject = "<a href='" . $url('user', array('action' => 'profile', 'id' => $subjectUser->id)) . "'>{$subjectUser->getName()}</a>'s";
                        }
                        $message = "<h3>$actor <span>denied</span> $subject <span>friend request</span></h3>";
                    }
                } else {
                    $message = "<h3><a href='" . $url('user', array('action' => 'profile', 'id' => $notification->actor->id)) . "'>{$notification->actor->getName()}</a> is now yoloers with <a href='" . $url('user', array('action' => 'profile', 'id' => $notification->subject->id)) . "'>{$notification->subject->getName()}</a></h3>";
                }
                break;
        }

        return $message;
    }

    public function getNotificationObject($typeId, $id) {
        switch ($typeId) {
            case Notification::NEW_LISTERS_OF_OWNED_EXPERIENCE:
                $object = $this->getServiceLocator()->get('Experience')->findByPK($id);
                break;

            case Notification::TAGGED_IN_USER_EXPERIENCE:
                $object = $this->getServiceLocator()->get('UserExperience')->findByPK($id);
                break;

            case Notification::USER_EXPERIENCE_LIKE:
                $object = $this->getServiceLocator()->get('UserExperienceLike')->findByPK($id);
                break;

            case Notification::FACEBOOK_INVITATION_ACCEPTED:
                $object = $this->getServiceLocator()->get('User')->findByPK($id);
                break;

            case Notification::USER_EXPERIENCE_STORY_LIKE:
                $object = $this->getServiceLocator()->get('UserExperienceStoryLike')->findByPK($id);
                break;

            case Notification::USER_EXPERIENCE_PHOTO_LIKE:
                $object = $this->getServiceLocator()->get('UserExperiencePictures')->findByPK($id);
                break;

            case Notification::USER_EXPERIENCE_COMMENT_LIKE:
                $object = $this->getServiceLocator()->get('UserExperienceComments')->findByPK($id);
                break;

            case Notification::USER_EXPIERENCE_COMMENT:
                $object = $this->getServiceLocator()->get('UserExperienceComments')->findByPK($id);
                break;

            case Notification::USER_EXPERIENCE_USER_ALSO_COMMENT:
                $object = $this->getServiceLocator()->get('UserExperienceComments')->findByPK($id);
                break;
            case Notification::USER_EXPERIENCE_ADDED_IN_TODO_LIST:
            case Notification::USER_EXPERIENCE_ADDED_IN_DONE_LIST:
            case Notification::USER_EXPERIENCE_COMPLETED:
                $object = $this->getServiceLocator()->get('UserExperience')->findByPK($id);
                break;
            case Notification::EXPERIENCE_CREATED:
                $object = $this->getServiceLocator()->get('Experience')->findByPK($id);
                break;
            case Notification::FRIEND_REQUEST_RESPONDED:
                $object = $this->getServiceLocator()->get('UserFriendRequests')->findByPK($id);
                break;
        }
        return $object;
    }

    public function getUserExperience($object) {
        $userExperience = NULL;
        $objectClass = get_class($object);
        $objectClass = substr($objectClass, strrpos($objectClass, "\\") + 1);
        switch ($objectClass) {
            case 'UserExperience':
                $userExperience = $object;
                break;
            case 'UserExperienceLike':
                $userExperience = $object->user_experiences;
                break;
            case 'UserExperienceComments':
                $userExperience = $object->user_experience;
                $experience = $userExperience->experience;
                break;
            case 'UserExperienceStoryLike':
                $userExperience = $object->user_experience;
                $experience = $userExperience->experience;
                break;
            case 'UserExperiencePictures':
                $userExperience = $object->user_experience;
                $experience = $userExperience->experience;
                break;
        }
        return $userExperience;
    }

    public function isMultipleEnabled($typeId) {
        if (in_array($typeId, $this->getMultipleEnabledTypes())) {
            return false;
        } else {
            return true;
        }
    }

    public function getMultipleEnabledTypes() {
        return array(
            Notification::TAGGED_IN_USER_EXPERIENCE,
            Notification::FACEBOOK_INVITATION_ACCEPTED,
            Notification::USER_EXPERIENCE_STORY_LIKE,
            Notification::USER_EXPERIENCE_PHOTO_LIKE,
            Notification::USER_EXPERIENCE_COMMENT_LIKE
        );
    }

    public function markSeen() {
        $qry = "UPDATE Application\Entity\Notification ue SET ue.status = 'seen'";
        return $this->em->createQuery($qry)->getSingleScalarResult();
    }

}