<?php

/**
 * @file
 * Contains functions which utilize the database and other internal helpers.
 */

use Drupal\comment\CommentInterface;

/**
 * Get the notification preferences for a specific user.
 *
 * @param int $uid
 *   The user id.
 *
 * @return mixed
 *   StdClass if found, else NULL
 */
function comment_notify_get_user_notification_setting($uid) {
  $users = &drupal_static(__FUNCTION__);
  if (!isset($users[$uid])) {
    if (is_null($uid)) {
      throw new Exception('Cannot get user preference, uid missing');
    }
    // Handle anonymous users with defaults.
    if ($uid == 0) {
      $users[0] = comment_notify_get_default_notification_setting();
    }
    else {
      $setting = db_select('comment_notify_user_settings', 'cnus')
        ->fields('cnus')
        ->condition('uid', $uid)
        ->execute()
        ->fetchObject();

      if (!$setting) {
        return NULL;
      }
      else {
        $users[$uid] = $setting;
      }
    }
  }
  return $users[$uid];
}

/**
 * Returns the default values of the notification settings.
 */
function comment_notify_get_default_notification_setting() {
  return (object) [
    'comment_notify' => \Drupal::config('comment_notify.settings')->get('enable_default.watcher'),
    'entity_notify' => \Drupal::config('comment_notify.settings')->get('enable_default.entity_author'),
  ];
}

/**
 * Remove comment notification preferences for a user.
 *
 * @param int $uid
 *   The user id.
 *
 * @return bool
 *   TRUE if the preferences were removed correctly, FALSE if weren't removed.
 */
function comment_notify_delete_user_notification_setting($uid) {
  return (bool) db_delete('comment_notify_user_settings')
    ->condition('uid', $uid)
    ->execute();
}

/**
 * Get a user's default preference for comment notification.
 *
 * @param int $uid
 *   The User ID.
 *
 * @return int
 *   Return the comment preference.
 */
function comment_notify_get_user_comment_notify_preference($uid) {
  $setting = comment_notify_get_user_notification_setting($uid);
  if (!$setting) {
    $setting = comment_notify_get_default_notification_setting();
  }
  return $setting->comment_notify;
}

/**
 * Get a user's default preference for entity update notification.
 *
 * This is notification on entity where the user is the author.
 *
 * @param int $uid
 *   The User ID.
 *
 * @return int
 *   Return the node_notify value.
 */
function comment_notify_get_user_entity_notify_preference($uid) {
  $setting = comment_notify_get_user_notification_setting($uid);
  if (!$setting) {
    $setting = comment_notify_get_default_notification_setting();
  }
  return $setting->entity_notify;
}

/**
 * Sets the notification preferences for a specific user.
 *
 * @param int $uid
 *   The User ID.
 * @param int $entity_notification
 *   The entity notification value.
 * @param int $comment_notification
 *   The comment notification value.
 *
 * @return bool
 *   TRUE if the notification was set correctly.
 */
function comment_notify_set_user_notification_setting($uid, $entity_notification = NULL, $comment_notification = NULL) {
  if (!$uid) {
    throw new Exception('Cannot set user preference, uid missing');
  }
  $fields = ['uid' => $uid];

  if (!is_null($entity_notification)) {
    $fields['entity_notify'] = $entity_notification;
  }
  if (!is_null($comment_notification)) {
    $fields['comment_notify'] = $comment_notification;
  }
  if (comment_notify_get_user_notification_setting($uid)) {
    $query = db_update('comment_notify_user_settings');
    $query->condition('uid', $uid);
  }
  else {
    $query = db_insert('comment_notify_user_settings');
  }
  return (bool) $query
    ->fields($fields)
    ->execute();
}

/**
 * Add a notification against a comment.
 *
 * @param int $cid
 *   Comment Id.
 * @param int $notify
 *   The notification type.
 * @param string $notify_hash
 *   The comment hash.
 * @param int|null $notified
 *   If the user has been already notified.
 *
 * @return bool
 *   TRUE if the notification was added correctly.
 */
function comment_notify_add_notification($cid, $notify, $notify_hash, $notified) {
  // Check if comment already exist.
  $results = db_select('comment_notify', 'cn')
    ->fields('cn', ['cid'])
    ->condition('cn.cid', $cid)
    ->execute()
    ->fetchField();

  // Update comment if exist.
  if ($results) {
    return (bool) db_update('comment_notify')
      ->fields([
        'notify' => $notify === NULL ? 0 : $notify,
        'notify_hash' => $notify_hash,
        'notified' => $notified === NULL ? 0 : $notified,
      ])
      ->condition('cid', $cid)
      ->execute();
  }

  // Create new entry.
  else {
    return (bool) db_insert('comment_notify')
      ->fields([
        'cid' => $cid,
        'notify' => $notify === NULL ? 0 : $notify,
        'notify_hash' => $notify_hash,
        'notified' => $notified === NULL ? 0 : $notified,
      ])
      ->execute();
  }
}

/**
 * Remove all the notifications linked with a comment.
 *
 * @param int $cid
 *   The comment ID.
 *
 * @return bool
 *   TRUE if all the notifications were removed correctly.
 */
function comment_notify_remove_all_notifications($cid) {
  return (bool) db_delete('comment_notify')
    ->condition('cid', $cid)
    ->execute();
}

/**
 * Updated a notification with a different notification type.
 *
 * @param int $cid
 *   The comment id.
 * @param int $notify
 *   The value that is going to be updated.
 *
 * @return bool
 *   TRUE if the notification was updated correctly.
 */
function comment_notify_update_notification($cid, $notify) {
  return (bool) db_update('comment_notify')
    ->fields([
      'notify' => $notify === NULL ? 0 : $notify,
    ])
    ->condition('cid', $cid)
    ->execute();
}

/**
 * Get the type of notification for a comment notification record.
 *
 * @param int $cid
 *   The comment id.
 *
 * @return int
 *   Return the notification type.
 */
function comment_notify_get_notification_type($cid) {
  return db_select('comment_notify', 'cn')
    ->fields('cn', ['notify'])
    ->condition('cid', $cid)
    ->execute()
    ->fetchField();
}

/**
 * Get a list of mails which need to be contacted for an entity.
 *
 * @param int $entity_id
 *   The entity id.
 * @param string $comment_type
 *   The comment type.
 *
 * @return \Drupal\comment\CommentInterface[]
 *   A list of comment entities.
 */
function comment_notify_get_watchers($entity_id, $comment_type) {
  $cids = db_query("SELECT c.cid FROM {comment_field_data} c INNER JOIN {comment_notify} cn ON c.cid = cn.cid LEFT JOIN {users_field_data} u ON c.uid = u.uid WHERE c.entity_id = :entity_id AND c.comment_type = :comment_type AND c.status = :status AND cn.notify <> :notify AND (u.uid = 0 OR u.status = 1)", [
    ':entity_id' => $entity_id,
    ':comment_type' => $comment_type,
    ':status' => CommentInterface::PUBLISHED,
    ':notify' => COMMENT_NOTIFY_DISABLED,
  ])->fetchCol();
  return \Drupal::entityManager()->getStorage('comment')->loadMultiple($cids);
}

/**
 * Records that the owner of a comment notification request has been notified.
 *
 * @param int $comment
 *   The comment ID.
 *
 * @return bool
 *   True if it can be updated correctly.
 */
function comment_notify_mark_comment_as_notified($comment) {
  // First, mark the passed comment (an object, so passed by reference).
  $comment->notified = 1;

  // Next, store this fact in the DB as well.
  return (bool) db_update('comment_notify')
    ->fields([
      'notified' => 1,
    ])
    ->condition('cid', $comment->id())
    ->execute();
}

/**
 * Unsubscribe all comment notification requests associated with an email.
 *
 * If the email belongs to a user, it will unsubscribe all of their Comment
 * Notify records. If it does not, then it will unsubscribe all anonymous users.
 *
 * @param string $mail
 *   The mail that is going to be unsubscribed.
 *
 * @return bool
 *   TRUE if the comment was unsubscribed correctly.
 */
function comment_notify_unsubscribe_by_email($mail) {
  $update_query = db_update('comment_notify');
  $update_query->fields(['notify' => 0]);

  $comment_query = \Drupal::entityQuery('comment');

  if ($user = user_load_by_mail($mail)) {
    $comment_query->condition('uid', $user->id());
  }
  else {
    $comment_query->condition('mail', $mail);
  }
  $update_query->condition('cid', $comment_query->execute(), 'IN');

  return (bool) $update_query->execute();
}

/**
 * Unsubscribe comment notification requests associated with a hash.
 *
 * This is used in the unsubscribe link.
 *
 * @param string $hash
 *   The hash that identified the comment.
 *
 * @return bool
 *   Returns TRUE if the comment was unsubscribed correctly, FALSE otherwise.
 */
function comment_notify_unsubscribe_by_hash($hash) {
  $query = db_select('comment_notify', 'cn');
  $query->join('comment_field_data', 'cf', 'cn.cid = cf.cid');
  $query->condition('cn.notify_hash', $hash)
    ->condition('cn.notify', COMMENT_NOTIFY_DISABLED, '!=')
    ->fields('cn', ['cid', 'notify', 'notified'])
    ->fields('cf', ['entity_id', 'entity_type', 'uid'])
    ->execute()->fetchObject();
  $notification = $query->execute()->fetchObject();

  if (empty($notification)) {
    return FALSE;
  }

  // If this notification is at the entity level and the commenter has a Drupal
  // account, delete all notifications for this entity.
  if (COMMENT_NOTIFY_ENTITY == $notification->notify && $notification->uid) {
    $result = db_query("SELECT cid FROM {comment_field_data} WHERE entity_id = :entity_id AND entity_type = :entity_type AND uid = :uid", [
      ':entity_id' => $notification->entity_id,
      ':entity_type' => $notification->entity_type,
      ':uid' => $notification->uid,
    ]);
    $cids = $result->fetchCol();

    // Update all comment notifications to be disabled.
    return (bool) db_update('comment_notify')
      ->fields([
        'notify' => 0,
      ])
      ->condition('cid', $cids, 'IN')
      ->execute();
  }
  else {
    // Update this notification to be disabled.
    return (bool) db_update('comment_notify')
      ->fields([
        'notify' => 0,
      ])
      ->condition('notify_hash', $hash)
      ->execute();
  }
}
