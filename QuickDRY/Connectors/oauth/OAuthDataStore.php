<?php

namespace QuickDRY\Connectors;

class OAuthDataStore
{
    public function lookup_consumer($consumer_key): ?OAuthConsumer
    {
        // implement me
        return null;
    }

    public function lookup_token($consumer, $token_type, $token): ?OAuthToken
    {
        // implement me
        return null;
    }

    public function lookup_nonce($consumer, $token, $nonce, $timestamp)
    {
        // implement me
        return null;
    }

    public function new_request_token($consumer, $callback = null): ?OAuthToken
    {
        // return a new token attached to this consumer
        return null;
    }

    public function new_access_token($token, $consumer, $verifier = null): ?OAuthToken
    {
        // return a new access token attached to this consumer
        // for the user associated with this token if the request token
        // is authorized
        // should also invalidate the request token
        return null;
    }

}