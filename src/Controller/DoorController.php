<?php

declare(strict_types=1);

namespace BLInc\Controller;

use BLInc\Managers\DoorManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\SerializerInterface;

final class DoorController
{
    private DoorManager $doorManager;

    private ValidatorInterface $validator;

    private SerializerInterface $serializer;

    private UrlGeneratorInterface $urlGenerator;

    private Constraint $doorConstraint;

    public function __construct(DoorManager $doorManager, ValidatorInterface $validator, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator)
    {
        $this->doorManager = $doorManager;
        $this->validator = $validator;
        $this->serializer = $serializer;
        $this->urlGenerator = $urlGenerator;
        $this->doorConstraint = new Assert\Collection([
            'fields' => [
                'name' => new Assert\NotBlank(),
                'identifier' => new Assert\NotBlank(),
            ],
        ]);
    }

    #[Route(path: '/api/doors', name: 'get_doors', methods: Request::METHOD_GET)]
    public function getDoors(Request $request): Response
    {
        $doors = $this->doorManager->findAll();

        return new JsonResponse(['items' => $doors, 'count' => count($doors)]);
    }

    #[Route(path: '/api/doors', name: 'post_door', methods: Request::METHOD_POST)]
    public function postDoor(Request $request): Response
    {
        $content = $request->getContent();

        $doorRequest = json_decode($content, true, JSON_THROW_ON_ERROR);

        if (!is_array($doorRequest)) {
            return new JsonResponse([['message' => 'Request must contain a hash or properties.']], 400);
        }

        $violations = $this->validator->validateValue($doorRequest, $this->doorConstraint, 'new');

        if (count($violations)) {
            return new Response(
                $this->serializer->serialize($violations, 'json'),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $doorId = $this->doorManager->create($doorRequest);

        $this->doorManager->update($doorId, $doorRequest);

        return new JsonResponse(null, 201, [
            'Location' => $this->urlGenerator->generate('get_door', ['id' => $doorId]),
        ]);
    }

    #[Route(path: '/api/door/{id}', name: 'get_door', methods: Request::METHOD_GET)]
    public function getDoor(Request $request, string $id): Response
    {
        $door = $this->doorManager->find($id);

        if ($door === null) {
            return new JsonResponse([['message' => 'Door not found.']], 404);
        }

        return new JsonResponse($door);
    }

    #[Route(path: '/api/door/{id}', name: 'put_door', methods: Request::METHOD_PUT)]
    public function putDoor(Request $request, string $id): Response
    {
        $door = $this->doorManager->find($id);

        if ($door === null) {
            return new JsonResponse([['message' => 'Door not found.']], 404);
        }

        $content = $request->getContent();

        $doorRequest = json_decode($content, true, JSON_THROW_ON_ERROR);

        if (!is_array($doorRequest)) {
            return new JsonResponse([['message' => 'Request must contain a hash or properties.']], 400);
        }

        $violations = $this->validator->validateValue($doorRequest, $this->doorConstraint, 'edit');

        if (count($violations)) {
            return new Response(
                $this->serializer->serialize($violations, 'json'),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $this->doorManager->update($id, $doorRequest);

        return new JsonResponse(null, 201, [
            'Location' => $this->urlGenerator->generate('get_door', ['id' => $id]),
        ]);
    }
}
