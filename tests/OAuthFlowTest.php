<?php

declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Reflex\Challonge\Auth\OAuth\AccessToken;
use Reflex\Challonge\Auth\OAuth\AuthorizationCodeFlow;
use Reflex\Challonge\Auth\OAuth\ClientCredentialsFlow;
use Reflex\Challonge\Auth\OAuth\DeviceAuthorizationFlow;
use Reflex\Challonge\Auth\OAuth\OAuthConfig;
use Reflex\Challonge\Auth\OAuth\OAuthTokenAuth;
use Reflex\Challonge\Challonge;
use Reflex\Challonge\DTO\Tournament;

/**
 * Tests OAuth 2.0 authentication flows
 *
 * Tests all three supported OAuth flows:
 * - Authorization Code Flow (web applications)
 * - Device Authorization Grant Flow (games/consoles)
 * - Client Credentials Flow (server-to-server)
 */
class OAuthFlowTest extends TestCase
{
    private function createHttpFactory(): HttpFactory
    {
        return new HttpFactory();
    }

    // ==================== AUTHORIZATION CODE FLOW ====================

    public function testAuthorizationCodeFlowGeneratesAuthUrl(): void
    {
        $httpFactory = $this->createHttpFactory();
        $config = new OAuthConfig(
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret',
            redirectUri: 'https://example.com/callback',
            scopes: [OAuthConfig::SCOPE_TOURNAMENTS_READ, OAuthConfig::SCOPE_TOURNAMENTS_WRITE]
        );

        $client = $this->createMock(ClientInterface::class);
        $flow = new AuthorizationCodeFlow($config, $client, $httpFactory, $httpFactory);

        $authUrl = $flow->getAuthorizationUrl(['state' => 'random_state_123']);

        $this->assertStringContainsString('https://api.challonge.com/oauth/authorize', $authUrl);
        $this->assertStringContainsString('client_id=test_client_id', $authUrl);
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fexample.com%2Fcallback', $authUrl);
        $this->assertStringContainsString('response_type=code', $authUrl);
        $this->assertStringContainsString('state=random_state_123', $authUrl);
        $this->assertStringContainsString('scope=', $authUrl);
    }

    public function testAuthorizationCodeFlowExchangesCodeForToken(): void
    {
        $tokenResponse = json_encode([
            'access_token' => 'at_test_access_token_12345',
            'token_type' => 'Bearer',
            'expires_in' => 7200,
            'refresh_token' => 'rt_test_refresh_token_67890',
            'scope' => 'tournaments:read tournaments:write',
            'created_at' => time(),
        ]);

        $httpFactory = $this->createHttpFactory();
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $this->assertEquals('POST', $request->getMethod());
                $this->assertStringContainsString('/oauth/token', (string) $request->getUri());

                $body = (string) $request->getBody();
                $this->assertStringContainsString('grant_type=authorization_code', $body);
                $this->assertStringContainsString('code=auth_code_12345', $body);

                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $tokenResponse));

        $config = new OAuthConfig(
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret',
            redirectUri: 'https://example.com/callback'
        );

        $flow = new AuthorizationCodeFlow($config, $client, $httpFactory, $httpFactory);
        $token = $flow->exchangeCodeForToken('auth_code_12345');

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals('at_test_access_token_12345', $token->getAccessToken());
        $this->assertEquals('rt_test_refresh_token_67890', $token->getRefreshToken());
        $this->assertEquals('Bearer', $token->getTokenType());
        $this->assertFalse($token->isExpired());
    }

    public function testAuthorizationCodeFlowRefreshesToken(): void
    {
        $tokenResponse = json_encode([
            'access_token' => 'at_new_access_token_99999',
            'token_type' => 'Bearer',
            'expires_in' => 7200,
            'refresh_token' => 'rt_new_refresh_token_88888',
            'scope' => 'tournaments:read tournaments:write',
            'created_at' => time(),
        ]);

        $httpFactory = $this->createHttpFactory();
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $body = (string) $request->getBody();
                $this->assertStringContainsString('grant_type=refresh_token', $body);
                $this->assertStringContainsString('refresh_token=rt_old_refresh_token', $body);
                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $tokenResponse));

        $config = new OAuthConfig(
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret',
            redirectUri: 'https://example.com/callback'
        );

        $flow = new AuthorizationCodeFlow($config, $client, $httpFactory, $httpFactory);
        $newToken = $flow->refreshToken('rt_old_refresh_token');

        $this->assertEquals('at_new_access_token_99999', $newToken->getAccessToken());
        $this->assertEquals('rt_new_refresh_token_88888', $newToken->getRefreshToken());
    }

    // ==================== DEVICE AUTHORIZATION FLOW ====================

    public function testDeviceFlowRequestsDeviceCode(): void
    {
        $deviceResponse = json_encode([
            'device_code' => 'dc_device_code_12345',
            'user_code' => 'ABCD-EFGH',
            'verification_uri' => 'https://challonge.com/activate',
            'verification_uri_complete' => 'https://challonge.com/activate?user_code=ABCD-EFGH',
            'expires_in' => 900,
            'interval' => 5,
        ]);

        $httpFactory = $this->createHttpFactory();
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $this->assertEquals('POST', $request->getMethod());
                $this->assertStringContainsString('/oauth/device/code', (string) $request->getUri());

                $body = (string) $request->getBody();
                $this->assertStringContainsString('client_id=test_client_id', $body);
                $this->assertStringContainsString('scope=', $body);

                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $deviceResponse));

        $config = new OAuthConfig(
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret',
            scopes: [OAuthConfig::SCOPE_ME]
        );

        $flow = new DeviceAuthorizationFlow($config, $client, $httpFactory, $httpFactory);
        $deviceData = $flow->requestDeviceCode([OAuthConfig::SCOPE_ME]);

        $this->assertArrayHasKey('device_code', $deviceData);
        $this->assertArrayHasKey('user_code', $deviceData);
        $this->assertArrayHasKey('verification_uri', $deviceData);
        $this->assertEquals('ABCD-EFGH', $deviceData['user_code']);
        $this->assertEquals('https://challonge.com/activate', $deviceData['verification_uri']);
    }

    public function testDeviceFlowPollsForToken(): void
    {
        $tokenResponse = json_encode([
            'access_token' => 'at_device_token_12345',
            'token_type' => 'Bearer',
            'expires_in' => 7200,
            'refresh_token' => 'rt_device_refresh_67890',
            'scope' => 'me',
            'created_at' => time(),
        ]);

        $httpFactory = $this->createHttpFactory();
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $body = (string) $request->getBody();
                $this->assertStringContainsString('grant_type=urn%3Aietf%3Aparams%3Aoauth%3Agrant-type%3Adevice_code', $body);
                $this->assertStringContainsString('device_code=dc_device_code_12345', $body);
                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $tokenResponse));

        $config = new OAuthConfig(
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret'
        );

        $flow = new DeviceAuthorizationFlow($config, $client, $httpFactory, $httpFactory);
        $token = $flow->pollForToken('dc_device_code_12345');

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals('at_device_token_12345', $token->getAccessToken());
    }

    public function testDeviceFlowHandlesAuthorizationPending(): void
    {
        $pendingResponse = json_encode([
            'error' => 'authorization_pending',
            'error_description' => 'User has not yet authorized the device',
        ]);

        $httpFactory = $this->createHttpFactory();
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturn(new Response(400, ['Content-Type' => 'application/json'], $pendingResponse));

        $config = new OAuthConfig(
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret'
        );

        $flow = new DeviceAuthorizationFlow($config, $client, $httpFactory, $httpFactory);
        $token = $flow->pollForToken('dc_device_code_12345');

        $this->assertNull($token); // Should return null when authorization is pending
    }

    // ==================== CLIENT CREDENTIALS FLOW ====================

    public function testClientCredentialsFlowGetsToken(): void
    {
        $tokenResponse = json_encode([
            'access_token' => 'at_client_creds_token_12345',
            'token_type' => 'Bearer',
            'expires_in' => 7200,
            'scope' => 'tournaments:read matches:read',
            'created_at' => time(),
        ]);

        $httpFactory = $this->createHttpFactory();
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $body = (string) $request->getBody();
                $this->assertStringContainsString('grant_type=client_credentials', $body);
                $this->assertStringContainsString('client_id=test_client_id', $body);
                $this->assertStringContainsString('client_secret=test_client_secret', $body);
                $this->assertStringContainsString('scope=tournaments%3Aread+matches%3Aread', $body);

                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $tokenResponse));

        $config = new OAuthConfig(
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret',
            redirectUri: '',
            scopes: [OAuthConfig::SCOPE_TOURNAMENTS_READ, OAuthConfig::SCOPE_MATCHES_READ]
        );

        $flow = new ClientCredentialsFlow($config, $client, $httpFactory, $httpFactory);
        $token = $flow->getAccessToken();

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals('at_client_creds_token_12345', $token->getAccessToken());
        $this->assertNull($token->getRefreshToken()); // Client credentials don't have refresh tokens
    }

    // ==================== ACCESS TOKEN ====================

    public function testAccessTokenExpirationDetection(): void
    {
        // Token that expires in 1 hour (not expired)
        $token = new AccessToken(
            accessToken: 'at_valid_token',
            tokenType: 'Bearer',
            expiresIn: 3600,
            refreshToken: null,
            scope: null,
            createdAt: new DateTimeImmutable()
        );

        $this->assertFalse($token->isExpired());

        // Token that expired 1 hour ago
        $expiredToken = new AccessToken(
            accessToken: 'at_expired_token',
            tokenType: 'Bearer',
            expiresIn: 3600,
            refreshToken: null,
            scope: null,
            createdAt: (new DateTimeImmutable())->modify('-2 hours')
        );

        $this->assertTrue($expiredToken->isExpired());
    }

    public function testAccessTokenHasRefreshToken(): void
    {
        $tokenWithRefresh = new AccessToken(
            accessToken: 'at_token',
            tokenType: 'Bearer',
            expiresIn: 3600,
            refreshToken: 'rt_refresh_token',
            scope: null,
            createdAt: new DateTimeImmutable()
        );

        $this->assertTrue($tokenWithRefresh->hasRefreshToken());

        $tokenWithoutRefresh = new AccessToken(
            accessToken: 'at_token',
            tokenType: 'Bearer',
            expiresIn: 3600,
            refreshToken: null,
            scope: null,
            createdAt: new DateTimeImmutable()
        );

        $this->assertFalse($tokenWithoutRefresh->hasRefreshToken());
    }

    public function testAccessTokenSerializationRoundtrip(): void
    {
        $original = new AccessToken(
            accessToken: 'at_test_token',
            tokenType: 'Bearer',
            expiresIn: 7200,
            refreshToken: 'rt_test_refresh',
            scope: 'tournaments:read tournaments:write',
            createdAt: new DateTimeImmutable()
        );

        $array = $original->toArray();
        $restored = AccessToken::fromArray($array);

        $this->assertEquals($original->getAccessToken(), $restored->getAccessToken());
        $this->assertEquals($original->getTokenType(), $restored->getTokenType());
        $this->assertEquals($original->getExpiresIn(), $restored->getExpiresIn());
        $this->assertEquals($original->getRefreshToken(), $restored->getRefreshToken());
        $this->assertEquals($original->getScope(), $restored->getScope());
    }

    // ==================== OAUTH TOKEN AUTH ====================

    public function testOAuthTokenAuthProvidesBearerHeader(): void
    {
        $token = new AccessToken(
            accessToken: 'at_oauth_token_12345',
            tokenType: 'Bearer',
            expiresIn: 7200,
            refreshToken: null,
            scope: null,
            createdAt: new DateTimeImmutable()
        );

        $auth = new OAuthTokenAuth($token);

        $this->assertEquals('v2', $auth->getAuthorizationType());
        $this->assertEquals('Bearer at_oauth_token_12345', $auth->getAuthorizationHeader());
        $this->assertTrue($auth->isValid());
    }

    public function testOAuthTokenAuthDetectsExpiredToken(): void
    {
        $expiredToken = new AccessToken(
            accessToken: 'at_expired_token',
            tokenType: 'Bearer',
            expiresIn: 3600,
            refreshToken: null,
            scope: null,
            createdAt: (new DateTimeImmutable())->modify('-2 hours')
        );

        $auth = new OAuthTokenAuth($expiredToken);

        $this->assertFalse($auth->isValid());
    }

    // ==================== INTEGRATION WITH CHALLONGE CLIENT ====================

    public function testChallongeClientWithOAuthToken(): void
    {
        $tournamentJson = file_get_contents(__DIR__ . '/Fixtures/Tournament.json');

        $httpClient = $this->createMock(ClientInterface::class);
        $httpClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function (RequestInterface $request) {
                $authHeader = $request->getHeader('Authorization');
                $this->assertNotEmpty($authHeader);
                $this->assertStringStartsWith('Bearer ', $authHeader[0]);
                $this->assertStringContainsString('at_oauth_token', $authHeader[0]);
                return true;
            }))
            ->willReturn(new Response(200, ['Content-Type' => 'application/json'], $tournamentJson));

        $token = new AccessToken(
            accessToken: 'at_oauth_token_12345',
            tokenType: 'Bearer',
            expiresIn: 7200,
            refreshToken: null,
            scope: null,
            createdAt: new DateTimeImmutable()
        );

        $auth = new OAuthTokenAuth($token);
        $challonge = new Challonge($httpClient, $auth);

        $tournament = $challonge->fetchTournament('example_tournament');
        $this->assertInstanceOf(Tournament::class, $tournament);
    }

    public function testOAuthConfigDefaultScopes(): void
    {
        $config = new OAuthConfig(
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret'
        );

        // Verify all scope constants are defined
        $this->assertIsString(OAuthConfig::SCOPE_ME);
        $this->assertIsString(OAuthConfig::SCOPE_APPLICATION_ORGANIZER);
        $this->assertIsString(OAuthConfig::SCOPE_APPLICATION_PLAYER);
        $this->assertIsString(OAuthConfig::SCOPE_TOURNAMENTS_READ);
        $this->assertIsString(OAuthConfig::SCOPE_TOURNAMENTS_WRITE);
        $this->assertIsString(OAuthConfig::SCOPE_MATCHES_READ);
        $this->assertIsString(OAuthConfig::SCOPE_MATCHES_WRITE);
        $this->assertIsString(OAuthConfig::SCOPE_PARTICIPANTS_READ);
        $this->assertIsString(OAuthConfig::SCOPE_PARTICIPANTS_WRITE);
        $this->assertIsString(OAuthConfig::SCOPE_COMMUNITIES_MANAGE);
    }

    public function testOAuthConfigUrls(): void
    {
        $config = new OAuthConfig(
            clientId: 'test_client_id',
            clientSecret: 'test_client_secret'
        );

        $this->assertEquals('https://api.challonge.com/oauth/authorize', $config->getAuthorizeUrl());
        $this->assertEquals('https://api.challonge.com/oauth/token', $config->getTokenUrl());
        $this->assertEquals('https://api.challonge.com/oauth/device/code', $config->getDeviceCodeUrl());
    }
}
