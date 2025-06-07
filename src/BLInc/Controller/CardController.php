<?php

declare(strict_types=1);

namespace BLInc\Controller;

use BLInc\Managers\CardManager;
use BLInc\Managers\ScheduleManager;
use BLInc\Model\CardSerialNumber;
use BLInc\Validator\Constraints\Unique;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class CardController
{
    private CardManager $cardManager;

    private ScheduleManager $scheduleManager;

    private ValidatorInterface $validator;

    private SerializerInterface $serializer;

    private UrlGeneratorInterface $urlGenerator;

    private Constraint  $constraint;

    public function __construct(
        CardManager $cardManager,
        ScheduleManager $scheduleManager,
        ValidatorInterface $validator,
        SerializerInterface $serializer,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->cardManager = $cardManager;
        $this->scheduleManager = $scheduleManager;
        $this->validator = $validator;
        $this->serializer = $serializer;
        $this->urlGenerator = $urlGenerator;
        $this->constraint = new Assert\Collection([
            'fields' => [
                'name' => new Assert\NotBlank(),
                'facilityCode' => [
                    new Assert\NotBlank(),
                    new Assert\Type('digit'),
                    new Assert\Range(['min' => 1, 'max' => 255]),
                ],
                'cardNumber' => [
                    new Assert\NotBlank(),
                    new Assert\Type('digit'),
                    new Assert\Range(['min' => 1, 'max' => 65535]),
                ],
                'code' => [
                    new Assert\NotBlank(),
                    new Unique(['table' => 'cards', 'column' => 'code']),
                ],
                'pin' => [
                    new Assert\Type('digit'),
                    new Assert\Length(['min' => 3]),
                ],
                'isActive' => new Assert\Type(['type' => 'boolean']),
                'schedules' => [new Assert\Count(['min' => 1]), new Assert\All([
                    new Assert\Collection([
                        'fields' => [
                            'id' => [
                                new Assert\NotBlank(),
                                // Valid Schedule Id
                            ],
                        ],
                    ]),
                ])],
            ],
        ]);
    }

    public function getCards(): Response
    {
        $cards = $this->cardManager->findAll();
        $cards = $this->injectSchedules($cards);

        return new JsonResponse(['items' => $cards, 'count' => count($cards)]);
    }

    public function getCard(string $id): Response
    {
        $card = $this->cardManager->find($id);

        if (!is_array($card)) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($card);
    }

    public function postCard(Request $request): Response
    {
        $content = $request->getContent();

        $card = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([['message' => 'Failed to parse request.']], 400);
        }

        if (!is_array($card)) {
            return new JsonResponse([['message' => 'Request must contain a hash or properties.']], 400);
        }

        if (isset($card['facilityCode'], $card['cardNumber'])) {
            $card['code'] = CardSerialNumber::createFromStrings($card['facilityCode'], $card['cardNumber'])->getHexCsn();
        }

        $violations = $this->validator->validateValue($card, $this->constraint, 'new');

        if (count($violations)) {
            return new Response(
                $this->serializer->serialize($violations, 'json'),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        unset($card['facilityCode'], $card['cardNumber']);

        $card_id = $this->cardManager->create($card);

        $response = new JsonResponse();
        $response->setStatusCode(201);
        $response->headers->set('Location', $this->urlGenerator->generate('get_card', ['id' => $card_id]));

        return $response;
    }

    public function putCard(Request $request, string $id): Response
    {
        $content = $request->getContent();

        $card = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([['message' => 'Failed to parse request.']], 400);
        }

        if (!is_array($card)) {
            return new JsonResponse([['message' => 'Request must contain a hash or properties.']], 400);
        }

        $constraints = clone $this->constraint;
        $constraints->allowMissingFields = true;

        if (isset($card['facilityCode'], $card['cardNumber'])) {
            $card['code'] = CardSerialNumber::createFromStrings($card['facilityCode'], $card['cardNumber'])->getHexCsn();
        }

        $violations = $this->validator->validateValue($card, $constraints, 'edit');

        if (count($violations)) {
            return new Response(
                $this->serializer->serialize($violations, 'json'),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        unset($card['facilityCode'], $card['cardNumber']);

        $newId = $this->cardManager->update($id, $card);

        $response = new JsonResponse();
        $response->setStatusCode(201);
        $response->headers->set('Location', $this->urlGenerator->generate('get_card', ['id' => $newId]));

        return $response;
    }

    private function injectSchedules(array $cards): array
    {
        $schedules = $this->scheduleManager->findByCards(array_map(function (array $card) {
            return $card['id'];
        }, $cards));

        $schedulesByCardId = [];

        foreach ($schedules as $schedule) {
            if (!isset($schedulesByCardId[$schedule['card_id']])) {
                $schedulesByCardId[$schedule['card_id']] = [];
            }

            $schedulesByCardId[$schedule['card_id']][] = $schedule;
        }

        return array_map(function (array $card) use ($schedulesByCardId) {
            $cardSchedules = $schedulesByCardId[$card['id']] ?? [];

            $card['schedules'] = array_map(function (array $schedule) {
                return $this->normalizeSchedule($schedule);
            }, $cardSchedules);

            return $card;
        }, $cards);
    }

    private function normalizeSchedule(array $schedule): array
    {
        return [
            'id' => $schedule['id'],
            'name' => $schedule['name'],
        ];
    }
}
