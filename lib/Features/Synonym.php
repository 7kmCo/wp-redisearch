<?php

namespace WPRedisearch\Features;

use WPRedisearch\Redisearch\Setup;
use WPRedisearch\Settings;
use WPRedisearch\Features;

class Synonym {

	/**
   * Redis client.
   * @since 0.2.0
	 * @var object
	 */
  public static $client;

	/**
   * Index name for this website.
   * @since 0.2.0
	 * @var string
	 */
  public static $index_name;

  /**
  * Initiate synonym terms to be added to the index
  * @since 0.2.0
  * @param
  * @return
  */
  public function __construct() {
    self::$client = Setup::connect();
    self::$index_name = Settings::indexName();
    Features::init()->register_feature( 'synonym', array(
      'title' => 'Synonym',
      'setup_cb' => array( $this, 'setup' ),
      'activation_cb' => array( $this, 'activated' ),
      'deactivation_cb' => array( $this, 'deactivated' ),
      'feature_desc_cb' => array( $this, 'feature_desc' ),
      'feature_options_cb' => array( $this, 'feature_options' ),
      'requires_reindex' => true,
      'deactivation_requires_reindex' => true,
    ) );
  }

	/**
	 * This method will run on each page load.
   * You can hook functions which must run always.
	 *
	 * @since 0.2.0
	 */
  public function setup () {
    add_action( 'wp_redisearch_after_index_created', array( __CLASS__, 'add' ) );
  }
  
	/**
	 * Fires after feature activation.
	 *
	 * @since 0.2.0
	 */
  public function activated () {
    self::add();
  }
  
	/**
	 * Fires after feature deactivation.
	 *
	 * @since 0.2.0
	 */
  public function deactivated () {
    self::delete();
  }

	/**
	 * Feature description.
   * This will be added to feature setting box.
	 *
	 * @since 0.2.0
	 */
  public function feature_desc () {
    ?>
      <p><?php esc_html_e( 'This will add synonym matching support. A synonym is a word or phrase that means exactly or nearly the same as another lexeme (word or phrase) in the same language.', 'wp-redisearch' ) ?></p>
    <?php
  }

	/**
	 * Feature option/settings.
   * Here we're adding fields to plugin options page.
	 *
	 * @since 0.2.0
	 */
  public function feature_options () {
    \SevenFields\Fields\Fields::add( 'header', null, __( 'Synonyms support', 'wp-redisearch' ) );
    \SevenFields\Fields\Fields::add( 'textarea', 'wp_redisearch_synonyms_list', __( 'Synonym words list.', 'wp-redisearch' ), __('Add each group on a line and separate terms by comma. <br /><b>For example: </b><br />boy, child, baby<br />girl, child, baby<br />man, person, adult<br /><br />When these three groups are located inside the synonym data structure, it is possible to search for \'child\' and receive documents contains \'boy\', \'girl\', \'child\' and \'baby\'. <br />Keep in mined, only those posts indexed after adding synonyms list will be affected.', 'wp-redisearch' ) );
  }

  /**
  * Add synonym terms to the index
  * @since 0.2.0
  * @param
  * @return
  */
  public static function add() {
    $synonym_terms = Settings::get( 'wp_redisearch_synonyms_list' );
    if ( !isset( $synonym_terms ) || empty( $synonym_terms ) ) {
      return;
    }
    $synonym_terms = preg_split("/\\r\\n|\\r|\\n/", $synonym_terms );
    $synonym_terms = array_map( 'trim', $synonym_terms );
    foreach ($synonym_terms as $synonym) {
      $synonym_group = array_map( 'trim', explode( ',', $synonym) );
      $synonym_command = array_merge( [ self::$index_name ], $synonym_group );

      self::$client->rawCommand('FT.SYNADD', $synonym_command);
    }
  }

  /**
  * Remove synonym terms from the index
  * @since 0.2.0
  * @param
  * @return
  */
  public static function delete() {
    $deactivation_command = array_merge( [ self::$index_name ] );
    self::$client->rawCommand('FT.SYNDUMP', $deactivation_command);
  }

}