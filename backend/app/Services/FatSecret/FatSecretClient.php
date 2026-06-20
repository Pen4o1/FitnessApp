<?php

namespace App\Services\FatSecret;

use App\Exceptions\FatSecretApiException;
use App\Exceptions\FatSecretFoodNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FatSecretClient
{
    private const TOKEN_CACHE_KEY = 'fatsecret_access_token';

    private const TOKEN_EXPIRY_BUFFER_SECONDS = 60;

    /**
     * @return array<string, mixed>
     */
    public function searchFoods(string $query, int $page = 0, int $maxResults = 20): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->get($this->apiUrl('foods/search/v5'), [
                'search_expression' => $query,
                'page_number' => $page,
                'max_results' => min($maxResults, 50),
                'format' => 'json',
                'flag_default_serving' => 'true',
                'region' => config('services.fatsecret.region'),
            ]);

        if (! $response->successful()) {
            throw new FatSecretApiException(
                'FatSecret search request failed with status '.$response->status()
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new FatSecretApiException('FatSecret search returned an invalid response.');
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function findFoodByBarcode(string $barcode): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->get($this->apiUrl('food/barcode/find-by-id/v2'), [
                'barcode' => $barcode,
                'format' => 'json',
                'flag_default_serving' => 'true',
                'include_food_attributes' => 'true',
                'region' => config('services.fatsecret.region'),
            ]);

        $payload = $response->json();

        if (is_array($payload)) {
            $errorCode = data_get($payload, 'error.code');

            if ($errorCode === 14 || $errorCode === '14') {
                throw new FatSecretApiException(
                    'FatSecret barcode scope is not enabled. Set FATSECRET_SCOPE to "premier barcode".'
                );
            }

            if ($errorCode === 211 || $errorCode === '211') {
                throw new FatSecretFoodNotFoundException('No food item detected for barcode.');
            }
        }

        if (! $response->successful()) {
            throw new FatSecretApiException(
                'FatSecret barcode lookup failed with status '.$response->status()
            );
        }

        if (! is_array($payload)) {
            throw new FatSecretApiException('FatSecret barcode lookup returned an invalid response.');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function searchRecipes(string $query, int $page = 0, int $maxResults = 20, array $filters = []): array
    {
        $params = [
            'search_expression' => $query,
            'page_number' => $page,
            'max_results' => min($maxResults, 50),
            'format' => 'json',
            'must_have_images' => 'true',
            'region' => config('services.fatsecret.region'),
        ];

        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $params[$key] = $value;
            }
        }

        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->get($this->apiUrl('recipes/search/v3'), $params);

        if (! $response->successful()) {
            throw new FatSecretApiException(
                'FatSecret recipe search request failed with status '.$response->status()
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new FatSecretApiException('FatSecret recipe search returned an invalid response.');
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecipe(string $recipeId): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->get($this->apiUrl('recipe/v2'), [
                'recipe_id' => $recipeId,
                'format' => 'json',
                'region' => config('services.fatsecret.region'),
            ]);

        if (! $response->successful()) {
            throw new FatSecretApiException(
                'FatSecret recipe get request failed with status '.$response->status()
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new FatSecretApiException('FatSecret recipe get returned an invalid response.');
        }

        return $payload;
    }

    private function getAccessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        return $this->requestAccessToken();
    }

    private function requestAccessToken(): string
    {
        $response = Http::asForm()
            ->acceptJson()
            ->post(config('services.fatsecret.token_url'), [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.fatsecret.client_id'),
                'client_secret' => config('services.fatsecret.client_secret'),
                'scope' => config('services.fatsecret.scope'),
            ]);

        if (! $response->successful()) {
            throw new FatSecretApiException(
                'FatSecret token request failed with status '.$response->status()
            );
        }

        $accessToken = $response->json('access_token');
        $expiresIn = $response->json('expires_in');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new FatSecretApiException('FatSecret token response did not include an access token.');
        }

        $ttl = is_numeric($expiresIn)
            ? max((int) $expiresIn - self::TOKEN_EXPIRY_BUFFER_SECONDS, 60)
            : 3600;

        Cache::put(self::TOKEN_CACHE_KEY, $accessToken, $ttl);

        return $accessToken;
    }

    private function apiUrl(string $path): string
    {
        return rtrim((string) config('services.fatsecret.api_base_url'), '/').'/'.$path;
    }
}
