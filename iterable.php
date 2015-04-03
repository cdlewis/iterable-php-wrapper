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

    // iterable limits request size to 3000kb
    private function chunk_request( $input, $max_size = 2 ) {
        $total_length = strlen( json_encode( $input ) );
        $max_length = $max_size * 1024 * 1024;
        $num_chunks = ceil( $total_length / $max_length );

        return array_chunk( $input, floor( count( $input ) / $num_chunks ) );
    }

    private function send_request( $resource, $params = array(),
        $request = 'GET', $decode = true ) {
        $curl_handle = curl_init();

        $url = $this->api_url . $resource . '?api_key=' . $this->api_key;

        if( $request == 'GET' ) {
            $url .= '&' . $this->query_string( $params );
        } else if( $request == 'POST' ) {
            curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, $params );
            curl_setopt( $curl_handle, CURLOPT_POST, 1 );
            curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen( $params )
            ) ); 
        } else {
            throw new Exception( 'Invalid request parameter specified.' );
        }
        curl_setopt( $curl_handle, CURLOPT_URL, $url );
        curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYHOST, 2 );
        curl_setopt( $curl_handle, CURLOPT_TIMEOUT, 0 );

        $buffer = curl_exec( $curl_handle );

        // handle curl error
        if( curl_errno( $curl_handle ) ) {
            return array(
                'success' => false,
                'content' => curl_error( $curl_handle ),
            );
        } else {
            $result = array(
                'response_code' => curl_getinfo( $curl_handle,
                    CURLINFO_HTTP_CODE ),
            );

            if( $result[ 'response_code' ] === 200 ) {
                $result[ 'success' ] = true;

                // try to decode as json
                $decoded_output = $decode ? json_decode( $buffer, true ) : null;
                if( $decoded_output !== null ) {
                    $result[ 'content' ] = $decoded_output;
                } else {
                    $result[ 'content' ] = $buffer;
                }
            } else {
                $result[ 'success' ] = false;
                $result[ 'error_message' ] = $buffer;
            }

            return $result;
        }
    }

    public function __construct( $api_key, $debug = false ) {
        $this->api_key = $api_key;
        $this->debug = $debug;
    }

    /* Lists */

    public function lists() {
        $result = $this->send_request( 'lists' );
        if( $result[ 'success' ] ) {
            $result[ 'content' ] = $result[ 'content' ][ 'lists' ];
        }

        return $result;
    }

    public function list_subscribe( $list_id, $subscribers,
        $resubscribe = false ) {

        // avoid hitting the iterable request size limit
        $result = array();
        foreach( $this->chunk_request( $subscribers ) as $chunk ) {
            $body = array(
                'listId' => (int) $list_id,
                'subscribers' => $chunk,
                'resubscribe' => $resubscribe
            );

            $result = $this->send_request( 'lists/subscribe',
                json_encode( $body ), 'POST' );

            if( !$result[ 'success' ] ) {
                break;
            }
        }

        return $result;
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

    public function event_track( $email, $event_name, $created_at = false,
        $data_fields = false, $user_id = false ) {
        $request = array(
            'email' => $email,
            'eventName' => $event_name,
        );

        $this->set_optionals( $request, array(
            'createdAt' => (int) $created_at,
            'dataFieds' => $data_fields,
            'user_id' => $user_id
        ) );

        return $this->send_request( 'events/track',
            json_encode( $request ), 'POST' );
    }

    public function event_track_conversion() {
        throw new Exception( 'Not yet implemented' );
    }

    public function event_track_push_open() {
        throw new Exception( 'Not yet implemented' );
    }

    /* Users */

    public function user_delete( $email ) {
        $result = $this->send_request( 'users/delete', json_encode( array(
            'email' => $email
        ) ), 'POST' );
        return $result;
    }

    public function user( $email ) {
        $result = $this->send_request( 'users/get', json_encode( array(
            'email' => $email
        ) ), 'POST' );

        if( $result[ 'success' ] ) {
            if( isset( $result[ 'content' ][ 'user' ][ 'dataFields' ] ) ) {
                $result[ 'content' ] = array_keys( $result[ 'content' ][ 'user' ][ 'dataFields' ] );
            } else {
                $result[ 'content' ] = array();
            }
        }

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

    public function user_register_device_token() {
        throw new Exception( 'Not yet implemented' );
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
            $result[ 'content' ] = array_keys( $result[ 'content' ][ 'fields' ] );
        }

        return $result;
    }

    public function user_update( $email = false, $data_fields = false,
        $user_id = false ) {
        // need either an email or user id
        if( $email === false && $user_id === false ) {
            throw new Exception( 'Must specify email or user ID' );
        }

        $request = array();
        $this->set_optionals( $request, array(
            'email' => $email,
            'dataFields' => $data_fields,
            'userId' => $user_id
        ) );

        $result = $this->send_request( 'users/update', 
            json_encode( $request ), 'POST' );
        return $result;
    }

    public function user_disable_device() {
        throw new Exception( 'Not yet implemented' );
    }

    /* Push */

    public function push() {
        throw new Exception( 'Not yet implemented' );
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
        $request = array(
            'user' => $user,
            'items' => $items
        );
        $result = $this->send_request( 'commerce/updateCart',
            json_encode( $request ), 'POST' );

        return $result;
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

        return $this->send_request( 'export/data.' . $type, $request, 'GET', false );
    }

    public function export_json( $data_type_name = 'user', $range = 'Today',
        $start_date_time = false, $end_date_time = false,
        $omit_fields = false, $only_fields = false ) {

        $result = $this->export( 'json', $data_type_name, $range,
            $start_date_time, $end_date_time, $omit_fields, $only_fields );

        // transform into valid json
        if( $result[ 'success' ] && !isset( $result[ 'content'] ) ) { // this should never be possible
            print_r( $result );
            trigger_error( print_r( $result, true ), E_USER_WARNING );
        }
        if( $result[ 'success' ] && $result[ 'content ' ] !== '' ) {
            $result[ 'content' ] = '[' . trim( str_replace( "\n", ',', $result[ 'content' ] ), ',' ) . ']';
        }

        return $result;
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
