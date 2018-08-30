<?php

namespace WPRedisearch\RediSearch;

use WPRedisearch\Settings;
use WPRedisearch\RediSearch\Setup;
use WPRedisearch\RedisRaw\PredisAdapter;

class Search {

	/**
	 * @param object $client
	 */
  public $client;

  public function __construct( $client ) {
    $this->client = $client;
  }

  /**
  * Search in the index.
  * @since    0.1.0
  * @param
  * @return
  */
  public function search() {
    $index_name = Settings::indexName();
    $query = $_GET['s'];
    // $query = explode( ' ', $query );
    // $search_term = '';
    // $stop_words = [ 'a', 'is', 'the', 'an', 'and', 'are', 'as', 'at', 'be', 'but', 'by', 'for', 'if', 'in', 'into', 'it', 'no', 'not', 'of', 'on', 'or', 'such', 'that',  'their', 'then', 'there', 'these', 'they', 'this', 'to', 'was', 'will',  'with'];
    // foreach ($query as $q) {
    //   if ( !in_array( $q, $stop_words ) ) {
    //     $search_term .= '%' . $q . '% ';
    //   }
    // }
    $search_results = $this->client->rawCommand('FT.SEARCH', [$index_name, $query, 'NOCONTENT']);
    return $search_results;
  }

  /**
  * Return suggestion based on passed term.
  * @since    0.1.0
  * @param
  * @return
  */
  public function suggest( $term ) {
    $index_name = Settings::indexName();
    $results_no = Settings::get( 'wp_redisearch_suggested_results' );
    $suggestion = $this->client->rawCommand('FT.SUGGET', [$index_name . 'Sugg', $term, 'FUZZY', 'WITHPAYLOADS', 'MAX', $results_no]);
    return $suggestion;
  }
}