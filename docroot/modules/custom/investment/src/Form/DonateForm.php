<?php

namespace Drupal\investment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Investment\Controller\InvestmentController;
use Drupal\user\Entity\User;
use Drupal\Core\Routing\TrustedRedirectResponse;

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
class DonateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $arg = NULL) {

    //$node = \Drupal::routeMatch()->getParameter('node');

    $form['amount'] = [
      '#type' => 'textfield',
      '#prefix' => '<div class="amount-holder font22 white"><div class="amount-label">RM</div>',
      '#required' => TRUE,
      '#suffix' => '</div>',
      '#attributes' => array('class' => array( 'amount' )),
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
      '#value' => $this->t('Submit'),
      '#attributes' => array('class' => array( 'margintop30 btn ghost-btn-white font20 centered blocked' )),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'atadeals_invest_amount_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
      $amount = $form_state->getValue('amount');
      $node_id = $form_state->getValue('node_id');
      $user_id = \Drupal::currentUser()->id();
      if($user_id == "") {
        $user_id = "ANY";
      }

      $trans_id = $this->generateOrderId();
      
      $user_investor_type = "IND";
      $payment_type = "DON";

      $merchantID = "ataplus"; // Live
      $merchantID_dev = "ataplus_Dev"; // Dev


      //$current_number = 1; //TODO : get current running number from DB

      $new_number = str_pad($node_id, 5, 0, STR_PAD_LEFT);

      $orderid = "ATA".$new_number."ON".$payment_type.$user_investor_type."-".$user_id."-".$trans_id;

      $verifykey = "1a5d64cd93b4d650dc8eb3df953c93f4"; // live version
      $verifykey2 = "95a785b2beeaaeddafc0463ef45dd6f8"; // Dev version

      $vcode = md5( $amount.$merchantID.$orderid.$verifykey );

      $url = "https://www.onlinepayment.com.my/MOLPay/pay/".$merchantID."/?orderid=".$orderid."&amount=".$amount."&vcode=".$vcode;

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
