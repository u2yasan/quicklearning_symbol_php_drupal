<?php

namespace Drupal\qls_ch3\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Facade\SymbolFacade;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class GenerateAccountFromPvtKeyForm extends FormBase {

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'item',
      '#markup' => '3.1.4 '. $this->t('秘密鍵からアカウント生成'),
    ];

    // $form['network_type'] = [
    //   '#type' => 'radios',
    //   '#title' => $this->t('Network Type'),
    //   '#description' => $this->t('Select either testnet or mainnet'),
    //   '#options' => [
    //     'testnet' => $this->t('Testnet'),
    //     'mainnet' => $this->t('Mainnet'),
    //   ],
    //   '#default_value' => 'testnet', // デフォルト選択を設定
    //   '#required' => TRUE,
    // ];

    $form['private_key'] = [
      '#type' => 'password',
      '#title' => $this->t('Private Key'),
      '#description' => $this->t('64文字（16進数）'),
      '#required' => TRUE,
    ];

    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form. This is not required, but is convention.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate From Private Key'),
    ];

    return $form;
  }


  public function getFormId() {
    return 'generate_account_from_pvtkey_form';
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $pvtKey = $form_state->getValue('private_key');
    if (strlen($pvtKey) !=  64) {
      // Set an error for the form element with a key of "title".
      $form_state->setErrorByName('private_key', $this->t('The private_key must be 64 characters long.'));
    }
  }

  /**
   * Implements a form submit handler.
   *
   * The submitForm method is the default method called for any submit elements.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    // $network_type = $form_state->getValue('network_type');
    $config = \Drupal::config('quicklearning_symbol.settings');
    $network_type = $config->get('network_type');

    $pvtkey = $form_state->getValue('private_key');

    // SymbolFacadeを使って新しいアカウントを作成
    $facade = new SymbolFacade($network_type);

    //3.1.4 秘密鍵からアカウント生成
    $accountKey = $facade->createAccount(new PrivateKey($pvtkey));
    $accountRawAddress = $accountKey->address;

    // 出力例
    // /admin/reports/dblog でログを確認
    //\Drupal::logger('qls_ch3')->notice('<pre>@object</pre>', ['@object' => print_r($aliceKey, TRUE)]);   

    // $this->messenger()->addMessage($this->t('You specified a network_type of %network_type.', ['%network_type' => $network_type]));
    $this->messenger()->addMessage($this->t('accountKey->addresst:<pre>@object</pre>', ['@object' => print_r($accountRawAddress, TRUE)]));
    $this->messenger()->addMessage($this->t('Account created from Private Key! RawAddress: @rawAddress', ['@rawAddress' => $accountRawAddress]));
  }

}
