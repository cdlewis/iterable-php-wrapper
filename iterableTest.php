<?php

require_once( dirname( __FILE__ ) . '/iterable.php' );

class iterableTest extends \PHPUnit_Framework_TestCase {
  private $iterable;
  private $subscribed_user;
  public function __construct() {
    $this->iterable = new Iterable( getenv( 'ITERABLE_KEY' ) );
  }

  /* Lists */

  public function testLists() {
    $result = $this->iterable->lists();
    $this->assertTrue( $result[ 'success' ] );
  }
  public function testListSubscribe() {
    $lists = $this->iterable->lists();
    $this->assertTrue( $lists[ 'success' ] );

    if( count( $lists[ 'content' ] ) > 0 ) {
      $result = $this->iterable->list_subscribe( $lists[ 'content' ][ 0 ][ 'id' ],
        array( array( 'email' => 'test@example.com' ) ) );
      $this->assertTrue( $result[ 'success' ] );
    }
  }

  public function testListUnsubscribe() {
    $user = $this->iterable->user( 'test@example.com' );
    $this->assertTrue( $user[ 'success' ] );

    foreach( $user[ 'content' ][ 'emailListIds' ] as $list_id ) {
      $response = $this->iterable->list_unsubscribe( $list_id,
        array( array( 'email' => $user[ 'content' ][ 'email' ] ) ) );
      $this->assertTrue( $response[ 'success' ] );
    }
  }

  /* User */

  public function testUserGet() {
    $user = $this->iterable->user( 'test@example.com' );
    $this->assertTrue( $user[ 'success' ] );
  }

  public function testUpdateEmail() {
    // make sure user doesn't already exist
    $this->iterable->user_delete( 'test2@example.com' );

    $response = $this->iterable->user_update_email( 'test@example.com', 'test2@example.com' );
    $this->assertTrue( $response[ 'success' ] );
  }

  public function testUserDelete() {
    $response = $this->iterable->user_delete( 'test2@example.com' );
    $this->assertTrue( $response[ 'success' ] );
  }
}
