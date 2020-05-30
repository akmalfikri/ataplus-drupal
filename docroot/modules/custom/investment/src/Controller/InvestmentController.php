<?php
namespace Drupal\investment\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\investment\Entity\InvestmentInterface;
use Drupal\investment\Entity\Investment;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class InvestmentController.
 *
 *  Returns responses for Investment routes.
 */
class InvestmentController extends ControllerBase implements ContainerInjectionInterface
{
    protected $environment = 'prod';

    protected $config = [
        'dev' => [
            'merchant_id' => 'ataplus_Dev',
            'verify_key' => '95a785b2beeaaeddafc0463ef45dd6f8',
        ],
        'prod' => [
            'merchant_id' => 'ataplus',
            'verify_key' => '1a5d64cd93b4d650dc8eb3df953c93f4',
        ],
    ];

    /**
     * Displays a Investment  revision.
     *
     * @param int $investment_revision
     *   The Investment  revision ID.
     *
     * @return array
     *   An array suitable for drupal_render().
     */
    public function revisionShow($investment_revision)
    {
        $investment = $this->entityManager()->getStorage('investment')->loadRevision($investment_revision);
        $view_builder = $this->entityManager()->getViewBuilder('investment');

        return $view_builder->view($investment);
    }

    /**
     * Page title callback for a Investment  revision.
     *
     * @param int $investment_revision
     *   The Investment  revision ID.
     *
     * @return string
     *   The page title.
     */
    public function revisionPageTitle($investment_revision)
    {
        $investment = $this->entityManager()->getStorage('investment')->loadRevision($investment_revision);
        return $this->t('Revision of %title from %date', ['%title' => $investment->label(), '%date' => format_date($investment->getRevisionCreationTime())]);
    }

    /**
     * Generates an overview table of older revisions of a Investment .
     *
     * @param \Drupal\investment\Entity\InvestmentInterface $investment
     *   A Investment  object.
     *
     * @return array
     *   An array as expected by drupal_render().
     */
    public function revisionOverview(InvestmentInterface $investment)
    {
        $account = $this->currentUser();
        $langcode = $investment->language()->getId();
        $langname = $investment->language()->getName();
        $languages = $investment->getTranslationLanguages();
        $has_translations = (count($languages) > 1);
        $investment_storage = $this->entityManager()->getStorage('investment');

        $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $investment->label()]) : $this->t('Revisions for %title', ['%title' => $investment->label()]);
        $header = [$this->t('Revision'), $this->t('Operations')];

        $revert_permission = (($account->hasPermission("revert all investment revisions") || $account->hasPermission('administer investment entities')));
        $delete_permission = (($account->hasPermission("delete all investment revisions") || $account->hasPermission('administer investment entities')));

        $rows = [];

        $vids = $investment_storage->revisionIds($investment);

        $latest_revision = true;

        foreach (array_reverse($vids) as $vid) {
            /** @var \Drupal\investment\InvestmentInterface $revision */
            $revision = $investment_storage->loadRevision($vid);
            // Only show revisions that are affected by the language that is being
            // displayed.
            if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
                $username = [
                    '#theme' => 'username',
                    '#account' => $revision->getRevisionUser(),
                  ];

                // Use revision link to link to revisions that are not active.
                $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
                if ($vid != $investment->getRevisionId()) {
                    $link = $this->l($date, new Url('entity.investment.revision', ['investment' => $investment->id(), 'investment_revision' => $vid]));
                } else {
                    $link = $investment->link($date);
                }

                $row = [];
                $column = [
                  'data' => [
                    '#type' => 'inline_template',
                    '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
                    '#context' => [
                      'date' => $link,
                      'username' => \Drupal::service('renderer')->renderPlain($username),
                      'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
                    ],
                  ],
                ];
                $row[] = $column;

                if ($latest_revision) {
                    $row[] = [
                      'data' => [
                        '#prefix' => '<em>',
                        '#markup' => $this->t('Current revision'),
                        '#suffix' => '</em>',
                      ],
                    ];
                    foreach ($row as &$current) {
                        $current['class'] = ['revision-current'];
                    }
                    $latest_revision = false;
                } else {
                    $links = [];
                    if ($revert_permission) {
                        $links['revert'] = [
                          'title' => $this->t('Revert'),
                          'url' => $has_translations ?
                          Url::fromRoute('entity.investment.translation_revert', ['investment' => $investment->id(), 'investment_revision' => $vid, 'langcode' => $langcode]) :
                          Url::fromRoute('entity.investment.revision_revert', ['investment' => $investment->id(), 'investment_revision' => $vid]),
                        ];
                    }

                    if ($delete_permission) {
                        $links['delete'] = [
                          'title' => $this->t('Delete'),
                          'url' => Url::fromRoute('entity.investment.revision_delete', ['investment' => $investment->id(), 'investment_revision' => $vid]),
                        ];
                    }

                    $row[] = [
                      'data' => [
                        '#type' => 'operations',
                        '#links' => $links,
                      ],
                    ];
                }

                $rows[] = $row;
            }
        }

        $build['investment_revisions_table'] = [
            '#theme' => 'table',
            '#rows' => $rows,
            '#header' => $header,
          ];

        return $build;
    }

    public function response()
    {
        $params = \Drupal::request()->request->all();
        if (empty($params)) {
            drupal_set_message('No payment data', 'error');
            return $this->redirect('<front>');
        }

        $tranId     = $params['tranID'];
        $orderId    = $params['orderid'];
        $status     = $params['status'];
        $merchantId = $params['domain'];
        $amount     = $params['amount'];
        $currency   = $params['currency'];

        $payDate    = $params['paydate'];
        $appCode    = $params['appcode'];
        $vKey       = $this->config[$this->environment]['verify_key'];

        $skey   = $params['skey'];
        $key0   = md5($tranId.$orderId.$status.$merchantId.$amount.$currency);
        $key1   = md5($payDate.$merchantId.$key0.$appCode.$vKey);

        $fPayDate = \DateTime::createFromFormat('Y-m-d H:i:s', $payDate);
        $fPayDate = $fPayDate->format('Y-m-d\TH:i:s');

        if ($skey === $key1) {
            \Drupal::logger('transaction')->info(sprintf('Payment with %s ORDER ID have been received.', $orderId));

            $query = \Drupal::entityQuery('node')
                        ->condition('type', 'transaction')
                        ->condition('title', $orderId)
                        ->execute();

            $transactionId  = array_values($query)[0];
            $transaction    = Node::load($transactionId);
            $dealId         = $transaction->get('field_deal_name')->target_id;
            $deal           = Node::load($dealId);


            if ($transaction->get('field_transaction_status')->value != 'success') {
                if ($status === '00') {
                    $transaction->set('field_transaction_status', 'success');

                    $amountRaised       = $deal->get('field_value_raised')->value;
                    $newAmountRaised    = (float) $amount + (float) $amountRaised;
                    $deal->set('field_value_raised', $newAmountRaised);

                    $noInvestors        = $deal->get('field_no_of_investors')->value;
                    $newNoInvestors     = (int) $noInvestors + 1;
                    $deal->set("field_no_of_investors", $newNoInvestors);

                    $investmentId = explode('/', $orderId)[0];

                    $minAmount  = $deal->get('field_minimum_investment_amount')->value;
                    $valuation  = $deal->get('field_post_money_valuation')->value;

                    if (empty($valuation)) {
                        $equity = '';
                    } else {
                        $equity     = $minAmount / $valuation * 100;
                        $equity     = number_format($equity, 4, '.', '');
                    }


                    $query = \Drupal::entityQuery('node')
                                ->condition('type', 'investments')
                                ->condition('title', $investmentId)
                                ->condition('field_transaction_id', $tranId, '=')
                                ->execute();

                    if (count(array_values($query)) == 0) {
                        $investment = Node::create([
                            'type'                  => 'investments',
                            'title'                 => $investmentId,
                            'field_amount2'         => $amount,
                            'field_payment_date'    => $fPayDate,
                            'field_equit'           => $equity,
                            'field_transaction_id'  => $tranId,
                        ]);

                        $investment->field_username->target_id = $transaction->field_username->target_id;
                        $investment->field_deal_name->target_id = $dealId;
                        $investment->save();
                    } else {
                        $investment = Node::load(array_values($query)[0]);
                    }

                    $transaction->field_investment_id2->target_id = $investment->nid->value;

                    $mailManager = \Drupal::service('plugin.manager.mail');
                    $user = User::load($transaction->get('field_username')->target_id);
                    $params = [
                        'deal' => $deal->title->value,
                        'issuer' => $deal->get('field_issuer')->value,
                        'name' => $user->get('field_full_name')->value,
                        'time' => $payDate,
                        'equity' => $investment->get('field_equit')->value || '0.00%',
                        'orderId' => $orderId,
                        'tranId' => $tranId,
                        'amount' => $investment->get('field_amount2')->value,
                    ];
                    $mailManager->mail('investment', 'invest', $user->mail->value, 'en', $params, null, true);
                } else {
                    $transaction->set('field_transaction_status', 'failed');
                    $transaction->set('field_transaction_message', sprintf('Transaction failed with status code %s,%s - %s', $status, $params['error_code'], $params['error_desc']));
                }

                $amount = (int) $amount;
                $transaction->set('field_amount', $amount);
                $transaction->set('field_transaction_timestamp', $fPayDate);
                $transaction->set('field_transaction_id', $tranId);

                $transaction->save();
                $deal->save();
            }

            return [
                '#theme' => 'investment_status',
                '#tranID' => $tranId,
                '#orderID' => $orderId,
                '#amount' => $amount,
                '#paydate' => $payDate,
                '#status' => $transaction->get('field_transaction_status')->value == 'success' ? 'Success' : 'Failed',
                '#url' => \Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$dealId),
                '#userID' => $transaction->get('field_username')->target_id,
            ];
        } else {
            drupal_set_message('Invalid payment', 'error');
            return $this->redirect('<front>');
        }
    }

    public function payCallback()
    {
        $params = \Drupal::request()->request->all();
        if (empty($params)) {
            drupal_set_message('No payment data', 'error');
            return $this->redirect('<front>');
        }

        $tranId     = $params['tranID'];
        $orderId    = $params['orderid'];
        $status     = $params['status'];
        $merchantId = $params['domain'];
        $amount     = $params['amount'];
        $currency   = $params['currency'];

        $payDate    = $params['paydate'];
        $appCode    = $params['appcode'];
        $vKey       = $this->config[$this->environment]['verify_key'];

        $skey   = $params['skey'];
        $key0   = md5($tranId.$orderId.$status.$merchantId.$amount.$currency);
        $key1   = md5($payDate.$merchantId.$key0.$appCode.$vKey);

        $fPayDate = \DateTime::createFromFormat('Y-m-d H:i:s', $payDate);
        $fPayDate = $fPayDate->format('Y-m-d\TH:i:s');

        if ($skey === $key1) {
            \Drupal::logger('transaction')->info(sprintf('Payment with %s ORDER ID have been received.', $orderId));

            $query = \Drupal::entityQuery('node')
                        ->condition('type', 'transaction')
                        ->condition('title', $orderId)
                        ->execute();

            $transactionId  = array_values($query)[0];
            $transaction    = Node::load($transactionId);
            $dealId         = $transaction->get('field_deal_name')->target_id;
            $deal           = Node::load($dealId);


            if ($transaction->get('field_transaction_status')->value != 'success') {
                if ($status === '00') {
                    $transaction->set('field_transaction_status', 'success');

                    $amountRaised       = $deal->get('field_value_raised')->value;
                    $newAmountRaised    = (float) $amount + (float) $amountRaised;
                    $deal->set('field_value_raised', $newAmountRaised);

                    $noInvestors        = $deal->get('field_no_of_investors')->value;
                    $newNoInvestors     = (int) $noInvestors + 1;
                    $deal->set("field_no_of_investors", $newNoInvestors);

                    $investmentId = explode('/', $orderId)[0];

                    $minAmount  = $deal->get('field_minimum_investment_amount')->value;
                    $valuation  = $deal->get('field_post_money_valuation')->value;

                    if (empty($valuation)) {
                        $equity = '';
                    } else {
                        $equity     = $minAmount / $valuation * 100;
                        $equity     = number_format($equity, 4, '.', '');
                    }


                    $query = \Drupal::entityQuery('node')
                                ->condition('type', 'investments')
                                ->condition('title', $investmentId)
                                ->condition('field_transaction_id', $tranId, '=')
                                ->execute();

                    if (count(array_values($query)) == 0) {
                        $investment = Node::create([
                            'type'                  => 'investments',
                            'title'                 => $investmentId,
                            'field_amount2'         => $amount,
                            'field_payment_date'    => $fPayDate,
                            'field_equit'           => $equity,
                            'field_transaction_id'  => $tranId,
                        ]);

                        $investment->field_username->target_id = $transaction->field_username->target_id;
                        $investment->field_deal_name->target_id = $dealId;
                        $investment->save();
                    } else {
                        $investment = Node::load(array_values($query)[0]);
                    }

                    $transaction->field_investment_id2->target_id = $investment->nid->value;

                    $mailManager = \Drupal::service('plugin.manager.mail');
                    $user = User::load($transaction->get('field_username')->target_id);
                    $params = [
                        'deal' => $deal->title->value,
                        'issuer' => $deal->get('field_issuer')->value,
                        'name' => $user->get('field_full_name')->value,
                        'time' => $payDate,
                        'equity' => $investment->get('field_equit')->value || '0.00%',
                        'orderId' => $orderId,
                        'tranId' => $tranId,
                        'amount' => $investment->get('field_amount2')->value,
                    ];
                    $mailManager->mail('investment', 'invest', $user->mail->value, 'en', $params, null, true);
                } else {
                    $transaction->set('field_transaction_status', 'failed');
                    $transaction->set('field_transaction_message', sprintf('Transaction failed with status code %s,%s - %s', $status, $params['error_code'], $params['error_desc']));
                }

                $amount = (int) $amount;
                $transaction->set('field_amount', $amount);
                $transaction->set('field_transaction_timestamp', $fPayDate);
                $transaction->set('field_transaction_id', $tranId);

                $transaction->save();
                $deal->save();
            }
        }

        $response = new Response();
        $response->setContent('CBTOKEN:MPSTATOK');
        $response->headers->set('Content-Type', 'text/plain');
        return $response;
    }

    public function payReturn()
    {
        $vkey2 = "95a785b2beeaaeddafc0463ef45dd6f8"; // Dev version
        $vkey = "1a5d64cd93b4d650dc8eb3df953c93f4"; // live version

        $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'project');

        if (!empty($params)) {
            /******************************** *Don't change below parameters ********************************/
            $tranID = $params['tranID'];
            $orderid = $params['orderid'];
            $status = $params['status'];
            $merchant = $params['domain'];
            $amount = $params['amount'];
            $currency = $params['currency'];
            $appcode = $params['appcode'];
            $paydate = $params['paydate'];
            $skey = $params['skey'];

            //$given = new \Drupal\Core\Datetime\DrupalDateTime($paydate);
            //$given->setTimezone(new \DateTimeZone("UTC"));

            $formatted_date = $paydate->format("Y-m-d h:i:s");

            $key0 = md5($tranID.$orderid.$status.$merchant.$amount.$currency);
            $key1 = md5($paydate.$merchant.$key0.$appcode.$vkey);

            if ($skey === $key1) {
                if ($status === '00') {
                    $message = "Payment Order ID ". $orderid . " have been made";

                    \Drupal::logger('investments')->info($message);

                    //$node_running = substr($orderID, 8);
                    $node_id = substr($orderid, 3, 5);
                    $node_id = ltrim($node_id, '0');
                    $pieces = explode("-", $orderid);
                    $user_id = $pieces[1];
                    if ($user_id == "GUE") {
                        $user_id = "";
                    }

                    $alias_deal = \Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$node_id);
                    $status = "Success";

                    // Load the node
                    $node = Node::load($node_id);

                    // Iterate through the definitions
                    foreach (array_keys($definitions) as $field_name) {
                        // Get the values for your node
                        // Use getValue() if you want to get an array instead of text.
                        $values[$field_name] = $node->get($field_name)->value;
                    }

                    $min_amount = $values['field_minimum_investment_amount'];
                    $valuation = $values['field_post_money_valuation'];
                    $equity = $min_amount / $valuation * 100;
                    $equity = number_format($equity, 4, '.', '');

                    $invest = Node::create([
                        'type'        => 'investments',
                        'title'       => $orderid,
                        'field_amount2' => $amount,
                        'field_payment_date' => $payDate,
                        'field_equit' => $equity,
                        'field_transaction_id' => $tranID,
                    ]);

                    $invest->field_username->target_id = $user_id;
                    $invest->field_deal_name->target_id = $node_id;


                    // Saves the new amount raised value in deal page
                    $amountRaised       = $values['field_value_raised'];
                    $noInvestors        = $values['field_no_of_investors'];
                    $newAmountRaised    = (int) $amountRaised + (int) $amount;
                    $newNoInvestors     = (int) $noInvestors + 1;
                    $node->set("field_no_of_investors", $newNoInvestors);
                    $node->set("field_value_raised", $newAmountRaised);
                    $node->save();
                    $invest->save();

                    $message = "Investment ID ". $orderid . " have been saved. The new amount raised is " . $newAmountRaised;
                    \Drupal::logger('investments')->info($message);

                    // Flag the investor
                    // $flag_id = 'invested';
                    // $flag_service = \Drupal::service('flag');
                    // $flag = $flag_service->getFlagById($flag_id);
                    // $account = \Drupal::currentUser();

                    // if ($flag && $flag->is_flagged($node->nid)) {
                    // } else {
                    //     // Flag an entity with a specific flag.
                    //     $flag_service->flag($flag, $node, $account);
                    // }

                    return [
                      '#theme' => 'investment_status',
                      '#tranID' => $tranID,
                      '#orderID' => $orderid,
                      '#amount' => $amount,
                      '#paydate' => $paydate,
                      '#status' => $status,
                      '#url' => $alias_deal,
                      '#userID' => $user_id,
                    ];
                } else {
                    return [
                        '#markup' => '<p>Payment Failed</p>',
                    ];
                }
            } elseif ($skey != $key1) {
                $message = "Payment Failed. SKEY : " . $skey . " Key 1 : " . $key1;
                \Drupal::logger('investment')->error($message);

                return [
                  '#markup' => '<p>Payment Failed</p>',
                ];
            } else {
                $message = "Error in payment";
                \Drupal::logger('investment')->error($message);

                return [
                    '#markup' => '<p>Error in Payment</p>',
                ];
            }
        } else {
            return $this->redirect('<front>');
        }
    }
}
