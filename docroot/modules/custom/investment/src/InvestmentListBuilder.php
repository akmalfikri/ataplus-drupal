<?php

namespace Drupal\investment;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Investment entities.
 *
 * @ingroup investment
 */
class InvestmentListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Investment ID');
    $header['investment_invoice_id'] = $this->t('Invoice ID');
    $header['investment_value'] = $this->t('Value');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\investment\Entity\Investment */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.investment.edit_form',
      ['investment' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
