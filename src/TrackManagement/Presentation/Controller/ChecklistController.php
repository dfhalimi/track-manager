<?php

declare(strict_types=1);

namespace App\TrackManagement\Presentation\Controller;

use App\TrackManagement\Domain\Dto\AddChecklistItemInputDto;
use App\TrackManagement\Domain\Dto\RemoveChecklistItemInputDto;
use App\TrackManagement\Domain\Dto\RenameChecklistItemInputDto;
use App\TrackManagement\Domain\Dto\ReorderChecklistItemsInputDto;
use App\TrackManagement\Domain\Dto\ToggleChecklistItemInputDto;
use App\TrackManagement\Domain\Service\ChecklistDomainServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;
use ValueError;

final class ChecklistController extends AbstractController
{
    public function __construct(
        private readonly ChecklistDomainServiceInterface $checklistDomainService
    ) {
    }

    #[Route(path: '/tracks/{trackUuid}/checklist/add', name: 'track_management.presentation.checklist.add', methods: [Request::METHOD_POST])]
    public function addAction(Request $request, string $trackUuid): Response
    {
        try {
            $this->checklistDomainService->addChecklistItem(
                new AddChecklistItemInputDto($trackUuid, $request->request->getString('label'))
            );
            $this->addFlash('success', 'Checklisten-Eintrag wurde hinzugefügt.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
    }

    #[Route(path: '/tracks/{trackUuid}/checklist/{itemUuid}/rename', name: 'track_management.presentation.checklist.rename', methods: [Request::METHOD_POST])]
    public function renameAction(Request $request, string $trackUuid, string $itemUuid): Response
    {
        try {
            $this->checklistDomainService->renameChecklistItem(
                new RenameChecklistItemInputDto($trackUuid, $itemUuid, $request->request->getString('label'))
            );
            $this->addFlash('success', 'Checklisten-Eintrag wurde umbenannt.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
    }

    #[Route(path: '/tracks/{trackUuid}/checklist/{itemUuid}/toggle', name: 'track_management.presentation.checklist.toggle', methods: [Request::METHOD_POST])]
    public function toggleAction(Request $request, string $trackUuid, string $itemUuid): Response
    {
        try {
            $this->checklistDomainService->toggleChecklistItem(
                new ToggleChecklistItemInputDto(
                    $trackUuid,
                    $itemUuid,
                    $request->request->getString('is_completed', '0') === '1'
                )
            );
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
    }

    #[Route(path: '/tracks/{trackUuid}/checklist/{itemUuid}/remove', name: 'track_management.presentation.checklist.remove', methods: [Request::METHOD_POST])]
    public function removeAction(string $trackUuid, string $itemUuid): Response
    {
        try {
            $this->checklistDomainService->removeChecklistItem(
                new RemoveChecklistItemInputDto($trackUuid, $itemUuid)
            );
            $this->addFlash('success', 'Checklisten-Eintrag wurde entfernt.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
    }

    #[Route(path: '/tracks/{trackUuid}/checklist/reorder', name: 'track_management.presentation.checklist.reorder', methods: [Request::METHOD_POST])]
    public function reorderAction(Request $request, string $trackUuid): Response
    {
        try {
            $orderedItemUuids = json_decode($request->request->getString('ordered_item_uuids'), true);

            if (!is_array($orderedItemUuids)) {
                throw new ValueError('Checklist reorder payload is invalid.');
            }

            $orderedItemUuids = array_values(
                array_filter(
                    $orderedItemUuids,
                    static fn (mixed $itemUuid): bool => is_string($itemUuid) && $itemUuid !== ''
                )
            );

            $this->checklistDomainService->reorderChecklistItems(
                new ReorderChecklistItemsInputDto($trackUuid, $orderedItemUuids)
            );

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Checklisten-Reihenfolge wurde gespeichert.',
                ]);
            }

            $this->addFlash('success', 'Checklisten-Reihenfolge wurde aktualisiert.');
        } catch (Throwable $throwable) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => $throwable->getMessage(),
                ], Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('track_management.presentation.show', ['trackUuid' => $trackUuid]);
    }
}
