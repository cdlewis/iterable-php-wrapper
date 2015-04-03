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

        $this->assertTrue( $lists[ 'success' ] && count( $lists[ 'content' ] ) > 0 );

        if( $lists[ 'success' ] && count( $lists[ 'content' ] ) > 0 ) {
            $result = $this->iterable->list_subscribe(
                $lists[ 'content' ][ 0 ][ 'id' ],
                array( array( 'email' => $this->email() ) )
            );
            $this->assertTrue( $result[ 'success' ] );
        }
    }

    public function testListUnsubscribe() {
        $lists = $this->iterable->lists();

        $this->assertTrue( $lists[ 'success' ] && count( $lists[ 'content' ] ) > 0 );

        if( $lists[ 'success' ] && count( $lists[ 'content' ] ) > 0 ) {
            // subscribe user to the first list we can find
            $list_id = $lists[ 'content' ][ 0 ][ 'id' ];
            $result = $this->iterable->list_subscribe(
                $list_id, array( array( 'email' => $this->email() ) )
            );

            $this->assertTrue( $result[ 'success' ] );

            $response = $this->iterable->list_unsubscribe( $list_id,
                array( array( 'email' => $this->email() ) ) );

            $this->assertTrue( $response[ 'success' ] );

            $this->iterable->user_delete( $this->email() );
        }
    }

    /* Events */

    public function testEventTrack() {
        $user = $this->iterable->user( $this->email() );
        $result = $this->iterable->event_track( $this->email(),
            'test event' );
        $this->iterable->user_delete( $this->email() );
        $this->assertTrue( $user[ 'success' ] );
    }

    public function testEventTrackConversion() {
        $this->setExpectedException( 'Exception' );
        $this->iterable->event_track_conversion();
    }

    public function testEventTrackPushOpen() {
        $this->setExpectedException( 'Exception' );
        $this->iterable->event_track_push_open();
    }

    /* User */

    public function testUserGet() {
        $user = $this->iterable->user( $this->email() );
        $this->assertTrue( $user[ 'success' ] );
    }

    public function testUpdateEmail() {
        $original = $this->iterable->user_update( $this->email() );
        $target = $this->iterable->user_delete( $this->email( 2 ) );

        $this->assertTrue( $original[ 'success' ] && $target[ 'success' ] );

        if( $original[ 'success' ] && $target[ 'success' ] ) {
            $response = $this->iterable->user_update_email( $this->email(),
                $this->email( 2 ) );
            $this->assertTrue( $response[ 'success' ] );
        }
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

    public function testUserRegisterDeviceToken() {
        $this->setExpectedException( 'Exception' );
        $this->iterable->user_register_device_token();
    }

    public function testUserUpdateSubscriptions() {
        $response = $this->iterable->user_update_subscriptions(
            $this->email()
        );
        $this->assertTrue( $response[ 'success' ] );
    }

    public function testUserFields() {
        $result = $this->iterable->user_fields();
        $this->assertTrue( $result[ 'success' ] );

        // there must be an email field
        $this->assertTrue( ( array_search( 'email', $result[ 'content' ], true ) !== false ) );
    }

    public function testUserUpdate() {
        $result = $this->iterable->user_update( $this->email() );
        $this->iterable->user_delete( $this->email() );
        $this->assertTrue( $result[ 'success' ] );

        $this->setExpectedException( 'Exception' );
        $this->iterable->user_update( false, array(), false );
    }

    public function testUserDisableDevice() {
        $this->setExpectedException( 'Exception' );
        $this->iterable->user_disable_device();
    }

    /* Push */

    public function testPush() {
        $this->setExpectedException( 'Exception' );
        $this->iterable->push();
    }

    /* Campaigns */

    public function testCampaigns() {
        $result = $this->iterable->campaigns();
        $this->assertTrue( $result[ 'success' ] );
    }

    /* Commerce */

    public function testTrackPurchase() {
        $user = $this->iterable->user( $this->email() );
        if( $user[ 'success' ] ) {
            $result = $this->iterable->commerce_track_purchase(
                $this->email(),
                array(
                    array(
                        'id' => '1',
                        'name' => 'widget',
                        'price' => 10,
                        'quantity' => 1
                    ),
                    array(
                        'id' => '2',
                        'name' => 'knob',
                        'price' => 10,
                        'quantity' => 1
                    )
                )
            );
            $this->assertTrue( $result[ 'success' ] );
            $this->iterable->user_delete( $this->email() );
        }
    }

    public function testUpdateCart() {
        $user = $this->iterable->user( $this->email() );
        if( $user[ 'success' ] ) {
            $result = $this->iterable->commerce_update_cart(
                array( 'email' => $this->email() ),
                array( array(
                    'id' => '1',
                    'name' => 'widget',
                    'price' => 10,
                    'quantity' => 1
                ) )
            );
            $this->assertTrue( $result[ 'success' ] );
            $this->iterable->user_delete( $this->email() );
        }
    }

    /* Email */

    public function testEmail() {
        $this->setExpectedException( 'Exception' );
        $this->iterable->email( 1, $this->email() );
    }

    /* Export */

    public function testExportJSON() {
        $result = $this->iterable->export_json();
        $this->assertTrue( $result[ 'success' ] );
        if( $result[ 'success' ] ) {
            $this->assertTrue( json_decode( $result[ 'content' ] ) !== null );
        }
    }

    public function testExportCSV() {
        $result = $this->iterable->export_csv();
        $this->assertTrue( $result[ 'success' ] );
    }

    /* Workflows */

    public function testTriggerWorkflow() {
        $this->setExpectedException( 'Exception' );
        $this->iterable->trigger_workflow();
    }
}
