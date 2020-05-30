<?php

namespace Drupal\investment\Entity;

use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Investment entities.
 *
 * @ingroup investment
 */
interface InvestmentInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Investment name.
   *
   * @return string
   *   Name of the Investment.
   */
  public function getName();

  /**
   * Sets the Investment name.
   *
   * @param string $name
   *   The Investment name.
   *
   * @return \Drupal\investment\Entity\InvestmentInterface
   *   The called Investment entity.
   */
  public function setName($name);

  /**
   * Gets the Investment creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Investment.
   */
  public function getCreatedTime();

  /**
   * Sets the Investment creation timestamp.
   *
   * @param int $timestamp
   *   The Investment creation timestamp.
   *
   * @return \Drupal\investment\Entity\InvestmentInterface
   *   The called Investment entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Investment published status indicator.
   *
   * Unpublished Investment are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Investment is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Investment.
   *
   * @param bool $published
   *   TRUE to set this Investment to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\investment\Entity\InvestmentInterface
   *   The called Investment entity.
   */
  public function setPublished($published);

  /**
   * Gets the Investment revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Investment revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\investment\Entity\InvestmentInterface
   *   The called Investment entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Investment revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Investment revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\investment\Entity\InvestmentInterface
   *   The called Investment entity.
   */
  public function setRevisionUserId($uid);

}
