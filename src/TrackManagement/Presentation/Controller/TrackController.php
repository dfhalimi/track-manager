<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Controller;

use App\FileImport\Facade\FileImportFacadeInterface;
use App\ProjectManagement\Facade\ProjectManagementFacadeInterface;
use App\TrackManagement\Domain\Dto\CreateTrackInputDto;
use App\TrackManagement\Domain\Dto\UpdateTrackInputDto;
use App\TrackManagement\Domain\Service\TrackManagementDomainServiceInterface;
use App\TrackManagement\Presentation\Service\TrackDetailPresentationServiceInterface;
use App\TrackManagement\Presentation\Service\TrackFormPresentationServiceInterface;
use App\TrackManagement\Presentation\Service\TrackOverviewPresentationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class TrackController extends AbstractController
{
    public function __construct(
        private readonly TrackOverviewPresentationServiceInterface $trackOverviewPresentationService,
        private readonly TrackDetailPresentationServiceInterface   $trackDetailPresentationService,
        private readonly TrackFormPresentationServiceInterface     $trackFormPresentationService,
        private readonly TrackManagementDomainServiceInterface     $trackManagementDomainService,
        private readonly FileImportFacadeInterface                 $fileImportFacade,
        private readonly ProjectManagementFacadeInterface          $projectManagementFacade
    ) {
    }

    #[Route(path: '/tracks', name: 'track_management.presentation.index', methods: [Request::METHOD_GET])]
    public function indexAction(Request $request): Response
    {
        $viewDto = $this->trackOverviewPresentationService->buildTrackListViewDto(
            $request->query->getString('q', ''),
            $request->query->getString('status', ''),
            $request->query->getString('sortBy', 'updatedAt'),
            $request->query->getString('sortDirection', 'DESC'),
            $request->query->getInt('page', 1),
            $request->query->getInt('perPage', 25)
        );

        return $this->render('@trackmanagement.presentation/index.html.twig', [
            'view' => $viewDto,
        ]);
    }

    #[Route(path: '/tracks/list', name: 'track_management.presentation.list', methods: [Request::METHOD_GET])]
    public function listAction(Request $request): Response
    {
        return $this->render('@trackmanagement.presentation/_list.html.twig', [
            'view' => $this->trackOverviewPresentationService->buildTrackListViewDto(
                $request->query->getString('q', ''),
                $request->query->getString('status', ''),
                $request->query->getString('sortBy', 'updatedAt'),
                $request->query->getString('sortDirection', 'DESC'),
                $request->query->getInt('page', 1),
                $request->query->getInt('perPage', 25)
            ),
        ]);
    }

    #[Route(path: '/tracks/suggestions', name: 'track_management.presentation.suggestions', methods: [Request::METHOD_GET])]
    public function suggestionsAction(Request $request): JsonResponse
    {
        return $this->json([
            'suggestions' => $this->trackOverviewPresentationService->buildTrackSearchSuggestions(
                $request->query->getString('q', ''),
                $request->query->getString('status', ''),
                $request->query->getString('sortBy', 'updatedAt'),
                $request->query->getString('sortDirection', 'DESC'),
                10
            ),
        ]);
    }

    #[Route(path: '/tracks/create', name: 'track_management.presentation.create', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function createAction(Request $request): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            try {
                $track = $this->trackManagementDomainService->createNewTrack(
                    new CreateTrackInputDto(
                        $request->request->getString('beat_name'),
                        $request->request->getString('title'),
                        $this->normalizeNullableString($request->request->getString('publishing_name', '')),
                        $this->normalizeBpms($request->request->all('bpms')),
                        $this->normalizeMusicalKeys($request->request->all('musical_keys')),
                        $this->normalizeNullableString($request->request->getString('notes', '')),
                        $this->normalizeNullableString($request->request->getString('isrc', ''))
                    )
                );

                $this->addFlash('success', 'Track wurde erstellt.');

                return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $track->getUuid()]);
            } catch (Throwable $throwable) {
                $this->addFlash('error', $throwable->getMessage());
            }
        }

        $viewDto = $this->trackFormPresentationService->buildCreateFormViewDto(
            $request->request->getString('beat_name', ''),
            $request->request->getString('title', ''),
            $this->normalizeNullableString($request->request->getString('publishing_name', '')),
            $request->request->has('bpms') ? $this->normalizeBpms($request->request->all('bpms')) : null,
            $request->request->has('musical_keys') ? $this->normalizeMusicalKeys($request->request->all('musical_keys')) : null,
            $this->normalizeNullableString($request->request->getString('notes', '')),
            $this->normalizeNullableString($request->request->getString('isrc', ''))
        );

        return $this->render('@trackmanagement.presentation/form.html.twig', [
            'view' => $viewDto,
        ]);
    }

    #[Route(path: '/tracks/{trackUuid}', name: 'track_management.presentation.show', methods: [Request::METHOD_GET])]
    public function showAction(string $trackUuid): Response
    {
        return $this->render('@trackmanagement.presentation/show.html.twig', [
            'view' => $this->trackDetailPresentationService->buildTrackDetailViewDto($trackUuid),
        ]);
    }

    #[Route(path: '/tracks/{trackUuid}/edit', name: 'track_management.presentation.edit', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function editAction(Request $request, string $trackUuid): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            try {
                $this->trackManagementDomainService->updateTrack(
                    new UpdateTrackInputDto(
                        $trackUuid,
                        $request->request->getString('beat_name'),
                        $request->request->getString('title'),
                        $this->normalizeNullableString($request->request->getString('publishing_name', '')),
                        $this->normalizeBpms($request->request->all('bpms')),
                        $this->normalizeMusicalKeys($request->request->all('musical_keys')),
                        $this->normalizeNullableString($request->request->getString('notes', '')),
                        $this->normalizeNullableString($request->request->getString('isrc', '')),
                        $request->request->getString('replace_title_with_suggestion', '0') === '1'
                    )
                );

                $this->addFlash('success', 'Track wurde aktualisiert.');

                return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
            } catch (Throwable $throwable) {
                $this->addFlash('error', $throwable->getMessage());
            }
        }

        return $this->render('@trackmanagement.presentation/form.html.twig', [
            'view' => $this->trackFormPresentationService->buildEditFormViewDto(
                $trackUuid,
                $request->request->getString('beat_name', '') ?: null,
                $request->request->getString('title', '') ?: null,
                $this->normalizeNullableString($request->request->getString('publishing_name', '')),
                $request->request->has('bpms') ? $this->normalizeBpms($request->request->all('bpms')) : null,
                $request->request->has('musical_keys') ? $this->normalizeMusicalKeys($request->request->all('musical_keys')) : null,
                $this->normalizeNullableString($request->request->getString('notes', '')),
                $this->normalizeNullableString($request->request->getString('isrc', ''))
            ),
        ]);
    }

    #[Route(path: '/tracks/{trackUuid}/delete', name: 'track_management.presentation.delete', methods: [Request::METHOD_POST])]
    public function deleteAction(Request $request, string $trackUuid): Response
    {
        if (!$this->isCsrfTokenValid('delete_track_' . $trackUuid, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Ungültiges CSRF-Token.');

            return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
        }

        $this->fileImportFacade->deleteCurrentTrackFileByTrackUuid($trackUuid);
        $this->projectManagementFacade->removeTrackFromAllProjects($trackUuid);
        $this->trackManagementDomainService->deleteTrack($trackUuid);
        $this->addFlash('success', 'Track wurde gelöscht.');

        return $this->redirectToRoute('track_management.presentation.index');
    }

    private function normalizeNullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return list<float>
     */
    private function normalizeBpms(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $bpms = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $trimmed = trim((string) $value);
            if ($trimmed === '') {
                continue;
            }

            $normalizedDecimal = str_replace(',', '.', $trimmed);
            if (!preg_match('/^\d+(?:\.\d+)?$/', $normalizedDecimal)) {
                continue;
            }

            $bpms[] = (float) $normalizedDecimal;
        }

        return $bpms;
    }

    /**
     * @return list<string>
     */
    private function normalizeMusicalKeys(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $musicalKeys = [];
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $trimmed = trim((string) $value);
            if ($trimmed === '') {
                continue;
            }

            $musicalKeys[] = $trimmed;
        }

        return $musicalKeys;
    }
}
