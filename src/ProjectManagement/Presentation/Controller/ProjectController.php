<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Controller;

use App\MediaAssetManagement\Facade\MediaAssetManagementFacadeInterface;
use App\ProjectManagement\Domain\Dto\CreateProjectInputDto;
use App\ProjectManagement\Domain\Dto\UpdateProjectInputDto;
use App\ProjectManagement\Domain\Service\ProjectManagementDomainServiceInterface;
use App\ProjectManagement\Presentation\Service\ProjectDetailPresentationServiceInterface;
use App\ProjectManagement\Presentation\Service\ProjectFormPresentationServiceInterface;
use App\ProjectManagement\Presentation\Service\ProjectOverviewPresentationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class ProjectController extends AbstractController
{
    public function __construct(
        private readonly ProjectOverviewPresentationServiceInterface $projectOverviewPresentationService,
        private readonly ProjectDetailPresentationServiceInterface   $projectDetailPresentationService,
        private readonly ProjectFormPresentationServiceInterface     $projectFormPresentationService,
        private readonly ProjectManagementDomainServiceInterface     $projectManagementDomainService,
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
                        $request->request->getString('category_name')
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
                $request->request->getString('category_name', '')
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
        if ($request->isMethod(Request::METHOD_POST)) {
            try {
                $this->projectManagementDomainService->updateProject(
                    new UpdateProjectInputDto(
                        $projectUuid,
                        $request->request->getString('title'),
                        $request->request->getString('category_name')
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
                $request->request->getString('category_name', '') ?: null
            ),
        ]);
    }

    #[Route(path: '/projects/{projectUuid}/delete', name: 'project_management.presentation.delete', methods: [Request::METHOD_POST])]
    public function deleteAction(Request $request, string $projectUuid): Response
    {
        if (!$this->isCsrfTokenValid('delete_project_' . $projectUuid, $request->request->getString('_token'))) {
            $this->addFlash('error', 'Ungültiges CSRF-Token.');

            return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
        }

        $this->mediaAssetManagementFacade->deleteCurrentProjectMediaAssetByProjectUuid($projectUuid);
        $this->projectManagementDomainService->deleteProject($projectUuid);
        $this->addFlash('success', 'Projekt wurde gelöscht.');

        return $this->redirectToRoute('project_management.presentation.index');
    }
}
