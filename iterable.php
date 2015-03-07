<?php
/**
 * Iterable API
 *
 */

class Iterable {
    private $api_key = '';
    private $api_url = 'https://api.iterable.com:443/api/';
    private $debug = false;

    private function query_string( $query ) {
        $query_array = array();

            foreach( $query as $key => $key_value ) {
                $query_array[] = urlencode( $key ) . '=' . urlencode( $key_value );
            }

        return implode( '&', $query_array );
    }

    private function send_request( $resource, $params = array(), $request = 'GET' ) {
        $curl_handle = curl_init();

        $url = $this->api_url . $resource . '?api_key=' . $this->api_key;

        if( $request == 'GET' ) {
            $url .= $this->query_string( $params );
        } else if( $request == 'POST' ) {
            curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, $params );
            curl_setopt( $curl_handle, CURLOPT_POST, 1 );
        } else {
            throw new Exception( 'Invalid request parameter specified.' );
        }

        if( $this->debug ) {
            curl_setopt( $curl_handle, CURLOPT_VERBOSE, true );
        }

        curl_setopt( $curl_handle, CURLOPT_URL, $url );
        curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYHOST, 2 );
        curl_setopt( $curl_handle, CURLOPT_TIMEOUT, 5 );

        $buffer = curl_exec( $curl_handle );

        if( $this->debug ) {
            var_dump( $buffer );
        }

        $result = array(
            'response_code' => curl_getinfo( $curl_handle, CURLINFO_HTTP_CODE ),
        );

        if( $result[ 'response_code' ] === 200 ) {
            $result[ 'success' ] = true;
            $result[ 'content' ] = json_decode( $buffer );
        } else {
            $result[ 'success' ] = false;
            $result[ 'error_message' ] = $buffer;
        }

        return $result;
    }

    public function __construct( $api_key, $debug = false ) {
        $this->api_key = $api_key;
        $this->debug = $debug;
    }

    /* Lists */

    public function lists() {
        $result = $this->send_request( 'lists' );
        if( $result[ 'success' ] ) {
            $result[ 'content' ] = array_map( 'get_object_vars', $result[ 'content' ]->lists );
        }

        return $result;
    }

    public function list_subscribe( $list_id, $subscribers, $resubscribe = false ) {
        $body = array(
            'listId' => (int) $list_id,
            'subscribers' => $subscribers,
            'resubscribe' => $resubscribe
        );
        return $this->send_request( 'lists/subscribe', json_encode( $body ), 'POST' );
    }

    public function list_unsubscribe( $list_id, $subscribers, $campaign_id = false, $channel_unsubscribe = false ) {
        $request = array(
            'listId' => (int) $list_id,
            'subscribers' => $subscribers,
        );

        // optionals
        if( $campaign_id ) {
            $request[ 'campaignId' ] = $campaign_id;
        }
        if( $channel_unsubscribe ) {
            $request[ 'channelUnsubscribe' ] = $channel_unsubscribe;
        }

        return $this->send_request( 'lists/unsubscribe', json_encode( $request ), 'POST' );
    }

    /* Events */

    public function event_track() {
        throw new Exception( 'Not yet implemented' );
    }

    public function event_track_conversation() {
        throw new Exception( 'Not yet implemented' );
    }

    /* Users */

    public function user( $email ) {
        $result = $this->send_request( 'users/get', json_encode( array(
            'email' => $email
        ) ), 'POST' );

        if( $result[ 'success' ] ) {
            $result[ 'content' ] = get_object_vars( $result[ 'content' ]->user->dataFields );
        }

        return $result;
    }

    public function user_delete( $email ) {
        $result = $this->send_request( 'users/delete', json_encode( array(
            'email' => $email
        ) ), 'POST' );
        return $result;
    }

    public function user_update_email( $current_email, $new_email ) {
        $result = $this->send_request( 'users/updateEmail', json_encode( array(
            'currentEmail' => $current_email,
            'newEmail' => $new_email
        ) ), 'POST' );
        return $result;
    }

    public function user_bulk_update( $users ) {
        $result = $this->send_request( 'users/bulkUpdate', json_encode( array(
            'users' => $users
        ) ), 'POST' );
        return $result;
    }

    public function user_update_subscriptions( $email, $email_list_ids = false, $unsubscribed_channel_ids = false, $unsubscribed_message_type_ids = false, $campaign_id = false, $template_id = false ) {
        $request = array(
            'email' => $email
        );

        // optional arguments
        if( $email_list_ids ) $request[ 'emailListIds' ] = $email_list_ids;
        if( $unsubscribed_channel_ids ) $request[ 'unsubscribedChannelIds' ] = $unsubscribed_channel_ids;
        if( $unsubscribed_message_type_ids ) $request[ 'unsubscribedMessageTypeIds' ] = $unsubscribed_message_type_ids;
        if( $campaign_id ) $request[ 'campaignId' ] = $campaign_id;
        if( $template_id ) $request[ 'templateId' ] = $template_id;

        $result = $this->send_request( 'users/updateSubscriptions', json_encode( $request ), 'POST' );
        return $result;
    }

    public function user_fields() {
        $result = $this->send_request( 'users/getFields' );

        if( $result[ 'success' ] ) {
            $result[ 'content' ] = array_keys( get_object_vars( $result[ 'content' ]->fields ) );
        }

        return $result;
    }

    public function user_update( $email, $data_fields, $user_id ) {
        $result = $this->send_request( 'users/update', json_encode( array(
            'email' => $email,
            'dataFields' => $data_fields,
            'user_id' => $user_id
        ) ), 'POST' );
        return $result;
    }

    /* Campaigns */

    public function campaigns() {
        return $this->send_request( 'campaigns' );
    }

    /* Commerce */

    public function commerce_track_purchase( $user, $items, $campaign_id = false, $template_id = false, $data_fields = false ) {
        throw new Exception( 'Not yet implemented' );
    }

    public function commerce_update_cart( $user, $items ) {
        throw new Exception( 'Not yet implemented' );
    }

    /* Email */

    public function email( $campaign_id, $recipient, $send_at = false, $inline_css = false, $attachments = false ) {
        throw new Exception( 'Not yet implemented' );
    }

    /* Export */

    public function export_json() {
        throw new Exception( 'Not yet implemented' );
    }

    public function export_csv() {
        throw new Exception( 'Not yet implemented' );
    }

    /* Workflows */

    public function trigger_workflow() {
        throw new Exception( 'Not yet implemented' );
    }
}
