<?php

namespace WPRedisearch\RediSearch;

use WPRedisearch\WPRedisearch;
use WPRedisearch\Settings;
use WPRedisearch\RediSearch\Setup;
use WPRedisearch\RedisRaw\PredisAdapter;

class Index {

	/**
	 * @param object $client
	 */
  public $client;

	/**
	 * @param object $index
	 */
  private $index;

  public function __construct( $client ) {
    $this->client = $client;
  }

  /**
  * Drop existing index.
  * @since    0.1.0
  * @param
  * @return
  */
  public function drop() {
    // First of all, we reset saved index_meta from optinos
    delete_option( 'wp_redisearch_index_meta' );

    $index_name = Settings::indexName();
    return $this->client->rawCommand('FT.DROP', [$index_name]);
  }

  /**
  * Create connection to redis server.
  * @since    0.1.0
  * @param
  * @return
  */
  public function create() {
    // First of all, we reset saved index_meta from optinos
    $num_docs = 0;
    if ( isset( WPRedisearch::$indexInfo ) && gettype( WPRedisearch::$indexInfo ) == 'array' ) {
      $num_docs_offset = array_search( 'num_docs', WPRedisearch::$indexInfo ) + 1;
      $num_docs = WPRedisearch::$indexInfo[$num_docs_offset];
    }
    if ( $num_docs == 0 ) {
      delete_option( 'wp_redisearch_index_meta' );
    }

    $index_name = Settings::indexName();
    $title_schema = ['postTitle', 'TEXT', 'WEIGHT', 5.0, 'SORTABLE'];
    $body_schema = ['postContent', 'TEXT'];
    $post_id_schema = ['postID', 'NUMERIC'];
    $post_link_schema = ['postLink', 'TEXT'];
    $schema = array_merge( [$index_name, 'SCHEMA'], $title_schema , $body_schema, $post_id_schema, $post_link_schema );
    $this->index = $this->client->rawCommand('FT.CREATE', $schema);
    return $this;
  }

  /**
  * Prepare items (posts) to be indexed.
  * @since    0.1.0
  * @param
  * @return object $this
  */
  public function add() {
    $index_meta = get_option( 'wp_redisearch_index_meta' );
    if ( empty( $index_meta ) ) {
      $index_meta['offset'] = 0;
    }
    $posts_per_page = apply_filters( 'wp_redisearch_posts_per_page', 20 );

    $default_args = Settings::query_args();
    $default_args['posts_per_page'] = $posts_per_page;
    $default_args['offset'] = $index_meta['offset'];

    $args = apply_filters( 'wp_redisearch_posts_args', $default_args);

    $query = new \WP_Query( $args );
    $index_meta['found_posts'] = $query->found_posts;

    if ( $index_meta['offset'] >= $index_meta['found_posts'] ) {
      $index_meta['offset'] = $index_meta['found_posts'];
    }
    
    if ( $query->have_posts() ) {
      $index_name = Settings::indexName();
      $suggestion = Settings::get( 'wp_redisearch_suggestion' );
      
      while ( $query->have_posts() ) {
        $query->the_post();

        $title = get_the_title();
        $permalink = get_permalink();
        $content = rtrim( implode( "\n", get_the_content() ) ) . "\n";
        $id = get_the_id();
        $fields = array('postTitle', $title, 'postContent', $content, 'postId', $id, 'postLink', $permalink);
        $this->addPosts($index_name, $id, $fields);
        if ( $suggestion ) {
          $this->addSuggestion($index_name, $permalink, $title, 1);
        }
      }
      $index_meta['offset'] = absint( $index_meta['offset'] + $posts_per_page );
      update_option( 'wp_redisearch_index_meta', $index_meta );
    }
    return $index_meta;
  }

  /**
  * Add to index or in other term, index items.
  * @since    0.1.0
  * @param
  * @return object $this
  */
  public function addPosts($index_name, $id, $fields) {
    $command = array_merge( [$index_name, $id , 1, 'LANGUAGE', 'norwegian', 'FIELDS'], $fields );
    $index = $this->client->rawCommand('FT.ADD', $command);
    return $index;
  }

  /**
  * Add to suggestion list.
  * @since    0.1.0
  * @param
  * @return object $this
  */
  public function addSuggestion($index_name, $permalink, $title, $score) {
    $command = array_merge( [$index_name . 'Sugg', $title , $score, 'PAYLOAD', $permalink] );
    $this->client->rawCommand('FT.SUGADD', $command);
  }


}