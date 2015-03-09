<?php
/**
 * Iterable API
 *
 */

class Iterable {
    private $api_key = '';
    private $api_url = 'https://api.iterable.com:443/api/';
    private $debug = false;

    private function set_optionals( &$array, $values ) {
        foreach( $values as $key => $value ) {
            if( $value ) {
                $array[ $key ] = $value;
            }
        }
    }

    private function query_string( $query ) {
        $query_array = array();

        foreach( $query as $key => $value ) {
            $query_array[] = urlencode( $key ) . '=' . urlencode( $value );
        }

        return implode( '&', $query_array );
    }

    private function send_request( $resource, $params = array(),
        $request = 'GET' ) {
        $curl_handle = curl_init();

        $url = $this->api_url . $resource . '?api_key=' . $this->api_key;

        if( $request == 'GET' ) {
            $url .= '&' . $this->query_string( $params );
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
            'response_code' => curl_getinfo( $curl_handle,
                CURLINFO_HTTP_CODE ),
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
            $result[ 'content' ] = array_map( 'get_object_vars',
                $result[ 'content' ]->lists );
        }

        return $result;
    }

    public function list_subscribe( $list_id, $subscribers,
        $resubscribe = false ) {
        $body = array(
            'listId' => (int) $list_id,
            'subscribers' => $subscribers,
            'resubscribe' => $resubscribe
        );
        return $this->send_request( 'lists/subscribe',
            json_encode( $body ), 'POST' );
    }

    public function list_unsubscribe( $list_id, $subscribers,
        $campaign_id = false, $channel_unsubscribe = false ) {
        $request = array(
            'listId' => (int) $list_id,
            'subscribers' => $subscribers,
        );

        $this->set_optionals( $request, array(
            'campaignId' => $campaign_id,
            'channelUnsubscribe' => $channel_unsubscribe
        ) );

        return $this->send_request( 'lists/unsubscribe',
            json_encode( $request ), 'POST' );
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
            if( isset( $result[ 'content' ]->user->dataFields ) ) {
                $result[ 'content' ] = get_object_vars(
                    $result[ 'content' ]->user->dataFields );
            } else {
                $result[ 'content' ] = array();
            }
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
        $result = $this->send_request( 'users/updateEmail',
            json_encode( array(
            'currentEmail' => $current_email,
            'newEmail' => $new_email
        ) ), 'POST' );
        return $result;
    }

    public function user_bulk_update( $users ) {
        $result = $this->send_request( 'users/bulkUpdate',
            json_encode( array(
            'users' => $users
        ) ), 'POST' );
        return $result;
    }

    public function user_update_subscriptions( $email,
        $email_list_ids = false, $unsub_channel_ids = false,
        $unsub_message_ids = false, $campaign_id = false,
        $template_id = false ) {

        $request = array( 'email' => $email );

        $this->set_optionals( $request, array(
            'emailListIds' => $email_list_ids,
            'unsubscribedChannelIds' => $unsub_channel_ids,
            'unsubscribedMessageTypeIds' => $unsub_message_ids,
            'campaignId' => $campaign_id,
            'templateId' => $template_id
        ) );

        return $this->send_request( 'users/updateSubscriptions',
            json_encode( $request ), 'POST' );
    }

    public function user_fields() {
        $result = $this->send_request( 'users/getFields' );

        if( $result[ 'success' ] ) {
            $result[ 'content' ] = array_keys( get_object_vars(
                $result[ 'content' ]->fields ) );
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

    public function commerce_track_purchase( $user, $items, $total = false,
        $campaign_id = false, $template_id = false, $data_fields = false ) {

        // create user object from email if necessary
        if( is_string( $user ) ) {
            $user = array( 'email' => $user );
        }

        // calculate total purchase amount if necessary
        if( !$total ) {
            $total = 0;
            foreach( $items as $i ) {
                if( isset( $i[ 'price' ] ) ) {
                    $total += (int) $i[ 'price' ];
                }
            }
        }

        $request = array(
            'user' => $user,
            'items' => $items,
            'total' => $total
        );

        $this->set_optionals( $request, array(
            'campaignId' => $campaign_id,
            'templateId' => $template_id,
            'dataFields' => $data_fields
        ) );

        $result = $this->send_request( 'commerce/trackPurchase',
            json_encode( $request ), 'POST' );

        return $result;
    }

    public function commerce_update_cart( $user, $items ) {
        throw new Exception( 'Not yet implemented' );
    }

    /* Email */

    public function email( $campaign_id, $recipient, $send_at = false,
        $inline_css = false, $attachments = false ) {
        throw new Exception( 'Not yet implemented' );
    }

    /* Export */

    private function export( $type, $data_type_name, $range,
        $start_date_time, $end_date_time, $omit_fields, $only_fields ) {

        $request = array(
            'dataTypeName' => $data_type_name,
            'range' => $range
        );

        $this->set_optionals( $request, array(
            'startDateTime' => $start_date_time,
            'endDateTime' => $end_date_time,
            'omitFields' => $omit_fields,
            'onlyFields' => $only_fields
        ) );

        return $this->send_request( 'export/data.' . $type, $request );
    }

    public function export_json( $data_type_name = 'user', $range = 'Today',
        $start_date_time = false, $end_date_time = false,
        $omit_fields = false, $only_fields = false ) {

        return $this->export( 'json', $data_type_name, $range,
            $start_date_time, $end_date_time, $omit_fields, $only_fields );
    }

    public function export_csv( $data_type_name = 'user', $range = 'Today',
        $start_date_time = false, $end_date_time = false,
        $omit_fields = false, $only_fields = false ) {

        return $this->export( 'csv', $data_type_name, $range,
            $start_date_time, $end_date_time, $omit_fields, $only_fields );
    }

    /* Workflows */

    public function trigger_workflow() {
        throw new Exception( 'Not yet implemented' );
    }
}
