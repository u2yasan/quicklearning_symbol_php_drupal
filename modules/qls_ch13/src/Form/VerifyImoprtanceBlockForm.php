<?php

namespace Drupal\qls_ch13\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use SymbolSdk\Symbol\Models\BlockType;

use Drupal\quicklearning_symbol\Service\FacadeService;
use Drupal\quicklearning_symbol\Service\BlockService;

/**
 *
 * @see \Drupal\Core\Form\FormBase
 */
class VerifyImoprtanceBlockForm extends FormBase {

  /**
   * @var \Drupal\quicklearning_symbol\Service\FacadeService
   */
  protected $facadeService;
  /**
   * @var \Drupal\quicklearning_symbol\Service\BlockService
   */
  protected $blcokService;

  /**
   * コンストラクタでServiceを注入
   */
  public function __construct(
    FacadeService $facade_service,
    BlockService $block_service
    ) {
      $this->facadeService = $facade_service;
      $this->blcokService = $block_service;
  }

  /**
   * createでサービスコンテナから依存性を注入
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('quicklearning_symbol.facade_service'),
      $container->get('quicklearning_symbol.block_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'verify_importance_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $form['#attached']['library'][] = 'qls_ss11/account_restriction';
    $form['description'] = [
      '#type' => 'item',
      '#markup' => '13.2.2 '.$this->t('importance ブロックの検証'),
    ];

    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Height'),
    ];

    // This container wil be replaced by AJAX.
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'box-container'],
    ];
    // The box contains some markup that we can change on a submit request.
    $form['container']['box'] = [
      '#type' => 'markup',
      '#markup' => '<h1>Block Info</h1>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::promptCallback',
        'wrapper' => 'box-container',
      ],
    ];

    return $form;
  }

  
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }

  public function promptCallback(array &$form, FormStateInterface $form_state) {

    $facade = $this->facadeService->getFacade();
    $blockApi = $this->blcokService->getBlockApi();

    $height = $form_state->getValue('height');
    $blockInfo = $blockApi->getBlockByHeight($height);
    $block = $blockInfo["block"];
    $previousBlockHash = $blockApi->getBlockByHeight($height - 1);
    $previousBlockHash = $previousBlockHash["meta"]["hash"];

    if($block['type'] === BlockType::IMPORTANCE){
      $hasher = hash_init('sha3-256');
    
      hash_update($hasher, hex2bin($block['signature'])); // signature
      hash_update($hasher, hex2bin($block['signer_public_key'])); // publicKey
    
      hash_update($hasher, hex2bin($this->reverseHex($block['version'],1)));
      hash_update($hasher, hex2bin($this->reverseHex($block['network'],1)));
      hash_update($hasher, hex2bin($this->reverseHex($block['type'],1)));
      hash_update($hasher, hex2bin($this->reverseHex($block['height'], 8)));
      hash_update($hasher, hex2bin($this->reverseHex($block['timestamp'], 8)));
      hash_update($hasher, hex2bin($this->reverseHex($block['difficulty'], 8)));
    
      hash_update($hasher, hex2bin($block['proof_gamma']));
      hash_update($hasher, hex2bin($block['proof_verification_hash']));
      hash_update($hasher, hex2bin($block['proof_scalar']));
      hash_update($hasher, hex2bin($previousBlockHash));
      hash_update($hasher, hex2bin($block['transactions_hash']));
      hash_update($hasher, hex2bin($block['receipts_hash']));
      hash_update($hasher, hex2bin($block['state_hash']));
      hash_update($hasher, hex2bin($block['beneficiary_address']));
      hash_update($hasher, hex2bin($this->reverseHex($block['fee_multiplier'], 4)));
      hash_update($hasher, hex2bin($this->reverseHex($block['voting_eligible_accounts_count'], 4)));
      hash_update($hasher, hex2bin($this->reverseHex($block['harvesting_eligible_accounts_count'], 8)));
      hash_update($hasher, hex2bin($this->reverseHex($block['total_voting_balance'], 8)));
      hash_update($hasher, hex2bin($block['previous_importance_block_hash']));
    
      $hash = strtoupper(bin2hex(hash_final($hasher, true)));
    
      // echo "===importanceブロックの検証===" . PHP_EOL;
      // var_dump($hash === $blockInfo['meta']['hash']);
    }

    $hasher = hash_init('sha3-256');
    hash_update($hasher, hex2bin($blockInfo['meta']['state_hash_sub_cache_merkle_roots'][0]));
    hash_update($hasher, hex2bin($blockInfo['meta']['state_hash_sub_cache_merkle_roots'][1]));
    hash_update($hasher, hex2bin($blockInfo['meta']['state_hash_sub_cache_merkle_roots'][2]));
    hash_update($hasher, hex2bin($blockInfo['meta']['state_hash_sub_cache_merkle_roots'][3]));
    hash_update($hasher, hex2bin($blockInfo['meta']['state_hash_sub_cache_merkle_roots'][4]));
    hash_update($hasher, hex2bin($blockInfo['meta']['state_hash_sub_cache_merkle_roots'][5]));
    hash_update($hasher, hex2bin($blockInfo['meta']['state_hash_sub_cache_merkle_roots'][6]));
    hash_update($hasher, hex2bin($blockInfo['meta']['state_hash_sub_cache_merkle_roots'][7]));
    hash_update($hasher, hex2bin($blockInfo['meta']['state_hash_sub_cache_merkle_roots'][8]));
    $hash = strtoupper(bin2hex(hash_final($hasher, true)));
    // echo "===stateHashの検証===" . PHP_EOL;
    // var_dump($hash === $blockInfo['block']['state_hash']);

    $element = $form['container'];
    $element['box']['#markup'] = '<h1>Block Info</h1>'
    .'<h1>importanceブロックの検証</h1><pre>'.print_r($hash === $blockInfo['meta']['hash'], TRUE).'</pre>'
    .'<h1>blockInfo</h1><pre>'.print_r($blockInfo, TRUE).'</pre>'
    .'<h1>stateHashの検証</h1><pre>'.print_r($hash === $blockInfo['block']['state_hash'], TRUE).'</pre>';
    // $element['box']['#markup'] = $this->transactionToHtml($tx);
    return $element;
  }

 

  //検証用共通関数

  public function reverseHex($hex, $bytes = 1) {
    // 10進数を16進数に変換し、必要に応じてゼロパディング
    $hex = str_pad(dechex($hex), $bytes * 2, "0", STR_PAD_LEFT);
    // 16進数の文字列をバイナリデータに変換
    $bin = hex2bin($hex);
    // バイナリデータを逆順にする
    $reversed = strrev($bin);
    // バイナリデータを16進数の文字列に変換
    $reversedHex = bin2hex($reversed);
    return $reversedHex;
  }

  //葉のハッシュ値取得関数
  public function getLeafHash($encodedPath, $leafValue) {
    $hasher = hash_init('sha3-256');
    hash_update($hasher, hex2bin($encodedPath . $leafValue));
    $hash = strtoupper(bin2hex(hash_final($hasher, true)));
    return $hash;
  }
  // getLeafHash("200F84DD2830B37539EF766DD37A0DA6150FB8E14AEE2ED2773262F4AF14CF","39B9DF440E50AF995D7E8DD94FA38BF68033CC39053B8C9FA1BFC2AA25C99F91");

  // 枝のハッシュ値取得関数
  public function getBranchHash($encodedPath, $links) {
    $branchLinks = array_fill(0, 16, bin2hex(str_repeat(chr(0), 32)));
    foreach ($links as $link) {
        $index = hexdec($link['bit']);
        $branchLinks[$index] = $link['link'];
    }
    $concatenated = $encodedPath . implode("", $branchLinks);
    $hasher = hash_init('sha3-256');
    hash_update($hasher, hex2bin($concatenated));

    return strtoupper(bin2hex(hash_final($hasher, true)));
  }
  // $array = [
  //   ["bit" => 2, "link" => "513DB50C2C5D5ADFEE727C4DAB2FD15D67D445DACE0EE4A91D2316BB6B5184DB"],
  //   ["bit" => 4, "link" => "B6BFC203326047B79F350E8D397B7278B52B05B8061CBAE31A1E3E75EB374A11"],
  //   ["bit" => 5, "link" => "A196EDBEDD6025B5A1C91D07033067FD7C231954FFD68CA7845053F6E0DECDE9"],
  //   ["bit" => 6, "link" => "BE463563FE9855B88AC5EB5D4C648FCD0ACBF88FB470B4A156C6DF699469A7B0"],
  //   ["bit" => 7, "link" => "3879965782C68D7BAFD9A3A5318606AC7720633E05F16A74998C979CE7CF6F4D"],
  //   ["bit" => 8, "link" => "D92E9488B9A54EBF96760969B05E260B97E4FD0C4BCA87193ED88CC50C6A6610"],
  //   ["bit" => 9, "link" => "D5AD2127DD636D531F26609A6F0055BB5B607C978BF4E8C7E3515125A76A956B"],
  //   ["bit" => "A", "link" => "65DFF47A75A11595830C0B6E03E44DC3E869A57EFC51632350B4A999D69BB24E"],
  //   ["bit" => "B", "link" => "38E66E2F0B029AE498F6EE0DC22C4F2836AF9F0E02B7E4EDD94A13EF0451FDA5"],
  //   ["bit" => "C", "link" => "17F2795C8028161B541527BE46B12353CCADE3E01FCEF52C71FE5BC6AEDEA6B7"],
  //   ["bit" => "E", "link" => "E64C24870C17F255F96E19120929AF366C81ADCDFB585D9976F3F26C855EF71C"],
  //   ["bit" => "F", "link" => "AAC621E07225A53114FBDE5A4EA0C79860743C0122F25A26BAAD0638C697E5FD"]
  // ];
  // $res = getBranchHash('00', $array);
  // echo $res . PHP_EOL;

  // ワールドステートの検証

  public function checkState($stateProof, $stateHash, $pathHash, $rootHash) {
    $merkleLeaf = null;
    $merkleBranches = [];
    foreach($stateProof['tree'] as $n){
      if($n['type'] === 255){
        $merkleLeaf = $n;
      } else {
        $merkleBranches[] = $n;
      }
    }
    $merkleBranches = array_reverse($merkleBranches);
    $leafHash = getLeafHash($merkleLeaf['encoded_path'], $stateHash);

    $linkHash = $leafHash;  // リンクハッシュの初期値は葉ハッシュ
    $bit = "";
    for($i=0; $i <  count($merkleBranches); $i++){
      $branch = $merkleBranches[$i];
      $branchLink = array_filter($branch['links'], function($link) use ($linkHash) {
        return $link['link'] === $linkHash;
      });
      $branchLink = reset($branchLink); // 最初の要素を取得
      $linkHash = getBranchHash($branch['encoded_path'], $branch['links']);
      $bit = substr($merkleBranches[$i]['path'], 0, $merkleBranches[$i]['nibble_count']) . $branchLink['bit'] . $bit;
    }
    $treeRootHash = $linkHash; //最後のlinkHashはrootHash
    $treePathHash = $bit . $merkleLeaf['path'];
    if(strlen($treePathHash) % 2 == 1){
      $treePathHash = substr($treePathHash, 0, -1);
    }

    // 検証
    var_dump($treeRootHash === $rootHash);
    var_dump($treePathHash === $pathHash);

  }

  public function hexToUint8($hex) {
    // 16進数文字列をバイナリデータに変換
    $binary = hex2bin($hex);
    // バイナリデータを配列に変換
    return array_values(unpack('C*', $binary));
  }

}