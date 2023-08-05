<?php

namespace QuickDRY\Connectors\oauth;


/**
 *
 */
class OAuthDataStore
{
    /**
     * @param $consumer_key
     * @return OAuthConsumer|null
     */
    public function lookup_consumer($consumer_key): ?OAuthConsumer
    {
        // implement me
        return null;
    }

    /**
     * @param $consumer
     * @param $token_type
     * @param $token
     * @return OAuthToken|null
     */
    public function lookup_token($consumer, $token_type, $token): ?OAuthToken
    {
        // implement me
        return null;
    }

    /**
     * @param $consumer
     * @param $token
     * @param $nonce
     * @param $timestamp
     * @return null
     */
    public function lookup_nonce($consumer, $token, $nonce, $timestamp)
    {
        // implement me
        return null;
    }

    /**
     * @param $consumer
     * @param $callback
     * @return OAuthToken|null
     */
    public function new_request_token($consumer, $callback = null): ?OAuthToken
    {
        // return a new token attached to this consumer
        return null;
    }

    /**
     * @param $token
     * @param $consumer
     * @param $verifier
     * @return OAuthToken|null
     */
    public function new_access_token($token, $consumer, $verifier = null): ?OAuthToken
    {
        // return a new access token attached to this consumer
        // for the user associated with this token if the request token
        // is authorized
        // should also invalidate the request token
        return null;
    }

}