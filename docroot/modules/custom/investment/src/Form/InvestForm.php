<?php

namespace Drupal\investment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\node\Entity\Node;

/**
 * Implements the ModalForm form controller.
 *
 * This example demonstrates implementation of a form that is designed to be
 * used as a modal form.  To properly display the modal the link presented by
 * the \Drupal\fapi_example\Controller\Page page controller loads the Drupal
 * dialog and ajax libraries.  The submit handler in this class returns ajax
 * commands to replace text in the calling page after submission .
 *
 * @see \Drupal\Core\Form\FormBase
 */
class InvestForm extends FormBase
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
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $arg = null)
    {
        $node = Node::load($arg);
        $user = User::load(\Drupal::currentUser()->id());

        $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'project');

        // Iterate through the definitions
        foreach (array_keys($definitions) as $field_name) {
            // Get the values for your node
            // Use getValue() if you want to get an array instead of text.
            $values[$field_name] = $node->get($field_name)->value;
        }

        $min_amount = $values['field_minimum_investment_amount'];
        $valuation = $values['field_post_money_valuation'];
        $investee = $values['field_issuer'];
        $full_name = $user->get('field_full_name')->value;
        $nric = $user->get('field_nric')->value;

        $equity = $min_amount / $valuation * 100;
        $equity = number_format($equity, 4, '.', '');

        $today = date("j F Y");


        $form['minimum_amount'] = [
      '#type' => 'hidden',
      '#value' => $min_amount,
      '#attributes' => ['class' => [ 'min_amount' ]],
    ];

        $form['valuation'] = [
      '#type' => 'hidden',
      '#value' => $valuation,
      '#attributes' => ['class' => [ 'valuation' ]],
    ];

        $form['amount'] = [
      '#type' => 'number',
      '#prefix' => '<div class="amount-holder font22 white"><div class="amount-label">RM</div>',
      '#required' => true,
      '#suffix' => '</div>',
      '#attributes' => ['class' => [ 'amount' ]],
      '#min' => $min_amount,
      '#default_value' => $min_amount,
    ];

        // change this if the calculation is fixed
        $form['equity'] = [
      '#type' => 'hidden',
      '#tag' => 'div',
      //'#prefix' => '<div class="equity-holder font22 white"><div class="eq-label">Equity(%)</div>',
      '#attributes' => ['class' => ['eq']],
      '#value' => $equity,
      //'#suffix' => '</div>',
    ];

        $form['full_name'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#prefix' => '<div class="name-holder font22 white"><div class="name-label">Full Name</div>',
      '#value' => $full_name,
      '#suffix' => '</div>',
    ];

        $form['nric'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#prefix' => '<div class="nric-holder font22 white"><div class="nric-label">NRIC / Passport</div>',
      '#value' => $nric,
      '#suffix' => '</div>',
    ];

        $form['investee'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#prefix' => '<div class="investee-holder font22 white"><div class="investee-label">Investee</div>',
      '#value' => $investee,
      '#suffix' => '</div>',
    ];

        $form['date'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#prefix' => '<div class="date-holder font22 white"><div class="date-label">Date</div>',
      '#value' => $today,
      '#suffix' => '</div>',
    ];

        $form['node_id'] = [
      '#type' => 'hidden',
      '#value' => $arg,
    ];

        // Group submit handlers in an actions element with a key of "actions" so
        // that it gets styled correctly, and so that other modules may add actions
        // to the form.
        $form['actions'] = [
      '#type' => 'actions',
    ];

        // Add a submit button that handles the submission of the form.
        $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
      '#attributes' => ['class' => [ 'margintop60 btn ghost-btn-white font20 centered blocked' ]],
    ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'atadeals_invest_amount_form';
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $nodeId = $form_state->getValue('node_id');
        $userId = \Drupal::currentUser()->id();

        $node = Node::load($nodeId);
        $user = User::load($userId);

        $orderId = sprintf(
        'ATA%06dON%s-%s/%s',
      $nodeId,
      "IND",
      $userId,
      $this->generateOrderId()
    );

        $amount = sprintf('%.2f', $form_state->getValue('amount'));
        $merchantId = $this->config[$this->environment]['merchant_id'];
        $verifyKey = $this->config[$this->environment]['verify_key'];
        $vCodeString = implode('', [$amount, $merchantId, $orderId, $verifyKey]);
        $vCode = md5($vCodeString);
        $description = sprintf('Investment for %s by %s', $node->title->value, $user->get('field_full_name')->value);

        $url = sprintf(
      'https://www.onlinepayment.com.my/MOLPay/pay/%s/?orderid=%s&amount=%s&vcode=%s&bill_name=%s&bill_email=%s&bill_mobile=%s&bill_desc=%s',
      $merchantId,
      $orderId,
      $amount,
      $vCode,
      $user->get('field_full_name')->value,
      $user->mail->value,
      $user->get('field_contact_m')->value,
      $description
    );

        $transaction = Node::create([
      'type'          => 'transaction',
      'title'         => $orderId,
      'field_amount'  => $amount,
    ]);
        $transaction->field_username->target_id = $userId;
        $transaction->field_deal_name->target_id = $nodeId;
        $transaction->save();

        $response = new TrustedRedirectResponse($url);

        $metadata = $response->getCacheableMetadata();
        $metadata->setCacheMaxAge(0);

        $form_state->setResponse($response);
    }

    public function generateOrderId($length=6)
    {
        $available  = [2, 3, 4, 5, 6, 7, 8, 9, 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $data       = $available;
        $string     = [];

        for ($i = 0; $i < $length; $i++) {
            $index = rand(0, count($data) - 1);
            $string[] = $data[$index];
            array_splice($data, $index, 1);

            if (count($data) == 0) {
                $data = $available;
            }
        }

        return implode('', $string);
    }
}
