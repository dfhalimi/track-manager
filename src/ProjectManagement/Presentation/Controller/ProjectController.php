<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Controller;

use App\Common\Service\LocalizedDateTimeService;
use App\MediaAssetManagement\Facade\MediaAssetManagementFacadeInterface;
use App\ProjectManagement\Domain\Dto\CreateProjectInputDto;
use App\ProjectManagement\Domain\Dto\PublishProjectInputDto;
use App\ProjectManagement\Domain\Dto\UpdateProjectInputDto;
use App\ProjectManagement\Domain\Service\ProjectManagementDomainServiceInterface;
use App\ProjectManagement\Presentation\Service\ProjectDetailPresentationServiceInterface;
use App\ProjectManagement\Presentation\Service\ProjectFormPresentationServiceInterface;
use App\ProjectManagement\Presentation\Service\ProjectOverviewPresentationServiceInterface;
use DateTimeImmutable;
use EnterpriseToolingForSymfony\SharedBundle\DateAndTime\Service\DateAndTimeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;
use ValueError;

final class ProjectController extends AbstractController
{
    public function __construct(
        private readonly ProjectOverviewPresentationServiceInterface $projectOverviewPresentationService,
        private readonly ProjectDetailPresentationServiceInterface   $projectDetailPresentationService,
        private readonly ProjectFormPresentationServiceInterface     $projectFormPresentationService,
        private readonly ProjectManagementDomainServiceInterface     $projectManagementDomainService,
        private readonly LocalizedDateTimeService                    $localizedDateTimeService,
        private readonly MediaAssetManagementFacadeInterface         $mediaAssetManagementFacade
    ) {
    }

    #[Route(path: '/projects', name: 'project_management.presentation.index', methods: [Request::METHOD_GET])]
    public function indexAction(Request $request): Response
    {
        return $this->render('@projectmanagement.presentation/index.html.twig', [
            'view' => $this->projectOverviewPresentationService->buildProjectListViewDto(
                $request->query->getString('q', ''),
                $request->query->getString('category', ''),
                $request->query->getString('cancelled', ''),
                $request->query->getString('sortBy', 'updatedAt'),
                $request->query->getString('sortDirection', 'DESC'),
                $request->query->getInt('page', 1),
                $request->query->getInt('perPage', 25)
            ),
        ]);
    }

    #[Route(path: '/projects/list', name: 'project_management.presentation.list', methods: [Request::METHOD_GET])]
    public function listAction(Request $request): Response
    {
        return $this->render('@projectmanagement.presentation/_list.html.twig', [
            'view' => $this->projectOverviewPresentationService->buildProjectListViewDto(
                $request->query->getString('q', ''),
                $request->query->getString('category', ''),
                $request->query->getString('cancelled', ''),
                $request->query->getString('sortBy', 'updatedAt'),
                $request->query->getString('sortDirection', 'DESC'),
                $request->query->getInt('page', 1),
                $request->query->getInt('perPage', 25)
            ),
        ]);
    }

    #[Route(path: '/projects/suggestions', name: 'project_management.presentation.suggestions', methods: [Request::METHOD_GET])]
    public function suggestionsAction(Request $request): JsonResponse
    {
        return $this->json([
            'suggestions' => $this->projectOverviewPresentationService->buildProjectSearchSuggestions(
                $request->query->getString('q', ''),
                $request->query->getString('category', ''),
                $request->query->getString('cancelled', ''),
                $request->query->getString('sortBy', 'updatedAt'),
                $request->query->getString('sortDirection', 'DESC'),
                10
            ),
        ]);
    }

    #[Route(path: '/projects/create', name: 'project_management.presentation.create', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function createAction(Request $request): Response
    {
        if ($request->isMethod(Request::METHOD_POST)) {
            try {
                $uploadedFile = $request->files->get('project_image');

                $project = $this->projectManagementDomainService->createProject(
                    new CreateProjectInputDto(
                        $request->request->getString('title'),
                        $request->request->getString('category_name'),
                        $this->filterArtists($request->request->all('artists'))
                    )
                );

                if ($uploadedFile instanceof UploadedFile) {
                    $this->mediaAssetManagementFacade->uploadProjectMediaAsset($project->getUuid(), $uploadedFile);
                }

                $this->addFlash('success', 'Projekt wurde erstellt.');

                return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $project->getUuid()]);
            } catch (Throwable $throwable) {
                $this->addFlash('error', $throwable->getMessage());
            }
        }

        return $this->render('@projectmanagement.presentation/form.html.twig', [
            'view' => $this->projectFormPresentationService->buildCreateFormViewDto(
                $request->request->getString('title', ''),
                $request->request->getString('category_name', ''),
                $this->filterArtists($request->request->all('artists'))
            ),
        ]);
    }

    #[Route(path: '/projects/{projectUuid}', name: 'project_management.presentation.show', methods: [Request::METHOD_GET])]
    public function showAction(string $projectUuid): Response
    {
        return $this->render('@projectmanagement.presentation/show.html.twig', [
            'view' => $this->projectDetailPresentationService->buildProjectDetailViewDto($projectUuid),
        ]);
    }

    #[Route(path: '/projects/{projectUuid}/edit', name: 'project_management.presentation.edit', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function editAction(Request $request, string $projectUuid): Response
    {
        if ($this->projectManagementDomainService->getProjectByUuid($projectUuid)->isCancelled()) {
            $this->addFlash('error', 'Archivierte Projekte koennen nicht bearbeitet werden.');

            return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
        }

        if ($request->isMethod(Request::METHOD_POST)) {
            try {
                $this->projectManagementDomainService->updateProject(
                    new UpdateProjectInputDto(
                        $projectUuid,
                        $request->request->getString('title'),
                        $request->request->getString('category_name'),
                        $this->filterArtists($request->request->all('artists')),
                        $this->resolveEditPublishedAt($projectUuid, $request)
                    )
                );

                $uploadedFile = $request->files->get('project_image');
                if ($uploadedFile instanceof UploadedFile) {
                    $this->mediaAssetManagementFacade->replaceProjectMediaAsset($projectUuid, $uploadedFile);
                }

                $this->addFlash('success', 'Projekt wurde aktualisiert.');

                return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
            } catch (Throwable $throwable) {
                $this->addFlash('error', $throwable->getMessage());
            }
        }

        return $this->render('@projectmanagement.presentation/form.html.twig', [
            'view' => $this->projectFormPresentationService->buildEditFormViewDto(
                $projectUuid,
                $request->request->getString('title', '') ?: null,
                $request->request->getString('category_name', '') ?: null,
                $this->filterArtists($request->request->all('artists')),
                $request->request->getString('published_at', '') ?: null
            ),
        ]);
    }

    #[Route(path: '/projects/{projectUuid}/cancel', name: 'project_management.presentation.cancel', methods: [Request::METHOD_POST])]
    public function cancelAction(Request $request, string $projectUuid): Response
    {
        if (!$this->isCsrfTokenValid('cancel_project_' . $projectUuid, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Ungültiges CSRF-Token.');

            return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
        }

        $this->projectManagementDomainService->cancelProject($projectUuid);
        $this->addFlash('success', 'Projekt wurde archiviert.');

        return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
    }

    #[Route(path: '/projects/{projectUuid}/reactivate', name: 'project_management.presentation.reactivate', methods: [Request::METHOD_POST])]
    public function reactivateAction(Request $request, string $projectUuid): Response
    {
        if (!$this->isCsrfTokenValid('reactivate_project_' . $projectUuid, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Ungültiges CSRF-Token.');

            return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
        }

        $this->projectManagementDomainService->reactivateProject($projectUuid);
        $this->addFlash('success', 'Projekt wurde reaktiviert.');

        return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
    }

    #[Route(path: '/projects/{projectUuid}/publish', name: 'project_management.presentation.publish', methods: [Request::METHOD_POST])]
    public function publishAction(Request $request, string $projectUuid): Response
    {
        if (!$this->isCsrfTokenValid('publish_project_' . $projectUuid, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Ungültiges CSRF-Token.');

            return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
        }

        try {
            $this->projectManagementDomainService->publishProject(
                new PublishProjectInputDto(
                    $projectUuid,
                    $this->parsePublishedAtInput($request->request->getString('published_at', ''), false)
                        ?? DateAndTimeService::getDateTimeImmutable()
                )
            );
            $this->addFlash('success', 'Projekt wurde veröffentlicht.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
    }

    #[Route(path: '/projects/{projectUuid}/unpublish', name: 'project_management.presentation.unpublish', methods: [Request::METHOD_POST])]
    public function unpublishAction(Request $request, string $projectUuid): Response
    {
        if (!$this->isCsrfTokenValid('unpublish_project_' . $projectUuid, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Ungültiges CSRF-Token.');

            return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
        }

        try {
            $this->projectManagementDomainService->unpublishProject($projectUuid);
            $this->addFlash('success', 'Projekt wurde zurück auf unveröffentlicht gesetzt.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
    }

    /**
     * @param array<mixed> $artists
     *
     * @return list<string>
     */
    private function filterArtists(array $artists): array
    {
        return array_values(
            array_filter(
                $artists,
                static fn (mixed $artist): bool => is_string($artist)
            )
        );
    }

    private function resolveEditPublishedAt(string $projectUuid, Request $request): ?DateTimeImmutable
    {
        $project = $this->projectManagementDomainService->getProjectByUuid($projectUuid);
        if (!$project->isPublished()) {
            return null;
        }

        return $this->parsePublishedAtInput($request->request->getString('published_at', ''), true);
    }

    private function parsePublishedAtInput(string $submittedValue, bool $required): ?DateTimeImmutable
    {
        $normalizedValue = trim($submittedValue);
        if ($normalizedValue === '') {
            if ($required) {
                throw new ValueError('Bitte gib ein Veröffentlichungsdatum an.');
            }

            return null;
        }

        try {
            return $this->localizedDateTimeService->parseInputToUtc($normalizedValue);
        } catch (ValueError) {
            throw new ValueError('Bitte gib ein gültiges Veröffentlichungsdatum mit Uhrzeit an.');
        }
    }
}
