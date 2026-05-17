<?php

declare(strict_types=1);

namespace App\Common\Presentation\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class OverviewNavigationState
{
    private const string TRACK_OVERVIEW_QUERY_KEY   = 'track_overview_query';
    private const string PROJECT_OVERVIEW_QUERY_KEY = 'project_overview_query';

    /**
     * @var list<string>
     */
    private const array TRACK_OVERVIEW_QUERY_KEYS = [
        'q',
        'status',
        'cancelled',
        'sortBy',
        'sortDirection',
        'page',
        'perPage',
    ];

    /**
     * @var list<string>
     */
    private const array PROJECT_OVERVIEW_QUERY_KEYS = [
        'q',
        'category',
        'cancelled',
        'sortBy',
        'sortDirection',
        'page',
        'perPage',
    ];

    public function __construct(
        private RequestStack          $requestStack,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function rememberTrackOverview(Request $request): void
    {
        $this->rememberOverviewQuery(self::TRACK_OVERVIEW_QUERY_KEY, self::TRACK_OVERVIEW_QUERY_KEYS, $request);
    }

    public function rememberProjectOverview(Request $request): void
    {
        $this->rememberOverviewQuery(self::PROJECT_OVERVIEW_QUERY_KEY, self::PROJECT_OVERVIEW_QUERY_KEYS, $request);
    }

    public function buildTrackOverviewUrl(): string
    {
        return $this->urlGenerator->generate(
            'track_management.presentation.index',
            $this->getRememberedOverviewQuery(self::TRACK_OVERVIEW_QUERY_KEY)
        );
    }

    public function buildProjectOverviewUrl(): string
    {
        return $this->urlGenerator->generate(
            'project_management.presentation.index',
            $this->getRememberedOverviewQuery(self::PROJECT_OVERVIEW_QUERY_KEY)
        );
    }

    public function buildTrackShowUrl(string $trackUuid): string
    {
        return $this->urlGenerator->generate(
            'track_management.presentation.show',
            ['trackUuid' => $trackUuid] + $this->getRememberedOverviewQuery(self::TRACK_OVERVIEW_QUERY_KEY)
        );
    }

    public function buildProjectShowUrl(string $projectUuid): string
    {
        return $this->urlGenerator->generate(
            'project_management.presentation.show',
            ['projectUuid' => $projectUuid] + $this->getRememberedOverviewQuery(self::PROJECT_OVERVIEW_QUERY_KEY)
        );
    }

    /**
     * @param list<string> $allowedKeys
     */
    private function rememberOverviewQuery(string $sessionKey, array $allowedKeys, Request $request): void
    {
        $query            = [];
        $hasOverviewQuery = false;

        foreach ($allowedKeys as $key) {
            if (!$request->query->has($key)) {
                continue;
            }

            $hasOverviewQuery = true;
            $value            = $request->query->get($key);

            if (!is_scalar($value)) {
                continue;
            }

            $normalizedValue = trim((string) $value);
            if ($normalizedValue === '') {
                continue;
            }

            $query[$key] = $normalizedValue;
        }

        if (!$hasOverviewQuery) {
            return;
        }

        $this->requestStack->getSession()->set($sessionKey, $query);
    }

    /**
     * @return array<string, string>
     */
    private function getRememberedOverviewQuery(string $sessionKey): array
    {
        $query = $this->requestStack->getSession()->get($sessionKey, []);

        if (!is_array($query)) {
            return [];
        }

        $normalizedQuery = [];
        foreach ($query as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }

            $normalizedQuery[$key] = (string) $value;
        }

        return $normalizedQuery;
    }
}
