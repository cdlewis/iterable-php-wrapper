<?php

require_once( dirname( __FILE__ ) . '/iterable.php' );

class iterableTest extends \PHPUnit_Framework_TestCase {
    private $iterable;
    private $instance; // for multiple concurrent tests

    public function __construct() {
        $this->iterable = new Iterable( getenv( 'ITERABLE_KEY' ) );
        $this->instance = getenv( 'TRAVIS_JOB_ID' );
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
            $email = 'test' . $this->instance . '@example.com';
            $result = $this->iterable->list_subscribe(
                $lists[ 'content' ][ 0 ][ 'id' ],
                array( array( 'email' => $email ) )
            );
            $this->assertTrue( $result[ 'success' ] );
        }
    }

    public function testListUnsubscribe() {
        $email = 'test' . $this->instance . '@example.com';
        $user = $this->iterable->user( $email );
        $this->assertTrue( $user[ 'success' ] );

        if( $user[ 'success' ] ) {
            // make sure the user is actually subscribed to a list
            $list_exists = isset( $user[ 'content' ][ 'emailListIds' ] );
            $this->assertTrue( $list_exists );
            if( !$list_exists ) {
                break;
            }

            foreach( $user[ 'content' ][ 'emailListIds' ] as $list_id ) {
                $response = $this->iterable->list_unsubscribe( $list_id,
                    array( array(
                        'email' => $user[ 'content' ][ 'email' ] ) ) );
                $this->assertTrue( $response[ 'success' ] );
            }
        }
    }

    /* User */

    public function testUserGet() {
        $email = 'test' . $this->instance . '@example.com';
        $user = $this->iterable->user( $email );
        $this->assertTrue( $user[ 'success' ] );
    }

    public function testUpdateEmail() {
        $old_email = 'test' . $this->instance . '@example.com';
        $new_email = 'test2' . $this->instance . '@example.com';

        // make sure user doesn't already exist
        $this->iterable->user_delete( $new_email );

        $response = $this->iterable->user_update_email( $old_email,
            $new_email );
        $this->assertTrue( $response[ 'success' ] );
    }

    public function testUserDelete() {
        $new_email = 'test2' . $this->instance . '@example.com';
        $response = $this->iterable->user_delete( $new_email );
        $this->assertTrue( $response[ 'success' ] );
    }

    public function testUserBulkUpdate() {
        $email1 = 'test' . $this->instance . '@example.com';
        $email2 = 'test2' . $this->instance . '@example.com';

        $response = $this->iterable->user_bulk_update( array(
            array( 'email' => $email1 ),
            array( 'email' => $email2 )
        ) );

        $this->iterable->user_delete( $email1 );
        $this->iterable->user_delete( $email2 );

        $this->assertTrue( $response[ 'success' ] );
    }

    public function testUserUpdateSubscriptions() {
        $email = 'test' . $this->instance . '@example.com';
        $response = $this->iterable->user_update_subscriptions( $email );
        $this->assertTrue( $response[ 'success' ] );
    }

    /* Campaigns */

    public function testCampaigns() {
        $result = $this->iterable->campaigns();
        $this->assertTrue( $result[ 'success' ] );
    }

    /* Commerce */

    /* Email */

    /* Export */

    public function testExportJSON() {
        $result = $this->iterable->export_json();
        $this->assertTrue( $result[ 'success' ] );
    }

    public function testExportCSV() {
        $result = $this->iterable->export_csv();
        $this->assertTrue( $result[ 'success' ] );
    }

    /* Workflows */
}
