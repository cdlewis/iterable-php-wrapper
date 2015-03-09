<?php

require_once( dirname( __FILE__ ) . '/iterable.php' );

class iterableTest extends \PHPUnit_Framework_TestCase {
    private $iterable;

    public function __construct() {
        $this->iterable = new Iterable( getenv( 'ITERABLE_KEY' ) );
    }

    // Generate test email strings unique to travis job (if it exists)
    // with optional offset for differentiation
    private function email( $offset = '' ) {
        return getenv( 'TRAVIS_JOB_ID' ) . $offset .  'test@example.com';
    }

    /* Lists */

    public function testLists() {
        $result = $this->iterable->lists();
        $this->assertTrue( $result[ 'success' ] );
    }

    public function testListSubscribe() {
        $lists = $this->iterable->lists();

        if( count( $lists[ 'content' ] ) > 0 ) {
            $result = $this->iterable->list_subscribe(
                $lists[ 'content' ][ 0 ][ 'id' ],
                array( array( 'email' => $this->email() ) )
            );
            $this->assertTrue( $result[ 'success' ] );
        }
    }

    public function testListUnsubscribe() {
        $user = $this->iterable->user( $this->email() );

        if( $user[ 'success' ] ) {
            // make sure the user is actually subscribed to a list
            $list_exists = isset( $user[ 'content' ][ 'emailListIds' ] );
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
        $user = $this->iterable->user( $this->email() );
        $this->assertTrue( $user[ 'success' ] );
    }

    public function testUpdateEmail() {
        // make sure new email doesn't already exist
        $this->iterable->user_delete( $this->email( 2 ) );

        $response = $this->iterable->user_update_email( $this->email(),
            $this->email( 2 ) );
        $this->assertTrue( $response[ 'success' ] );
    }

    public function testUserDelete() {
        $response = $this->iterable->user_delete( $this->email( 2 ) );
        $this->assertTrue( $response[ 'success' ] );
    }

    public function testUserBulkUpdate() {
        $response = $this->iterable->user_bulk_update( array(
            array( 'email' => $this->email() ),
            array( 'email' => $this->email( 2 ) )
        ) );

        $this->iterable->user_delete( $this->email() );
        $this->iterable->user_delete( $this->email( 2 ) );

        $this->assertTrue( $response[ 'success' ] );
    }

    public function testUserFields() {
        $result = $this->iterable->user_fields();
        $this->assertTrue( $result[ 'success' ] );
    }

    public function testUserUpdateSubscriptions() {
        $response = $this->iterable->user_update_subscriptions(
            $this->email()
        );
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
