<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\BotSubscriptionRepository;
use App\Repository\UserSettingsRepository;
use App\Security\RequiresPermission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/settings')]
class UserSettingsController extends AbstractController
{
    private const VALID_ACCOUNT_TYPES = ['human', 'bot'];

    public function __construct(
        private readonly UserSettingsRepository $settingsRepo,
        private readonly BotSubscriptionRepository $subscriptionRepo,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', methods: ['GET'])]
    #[RequiresPermission]
    public function show(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return new JsonResponse($this->serialize($user));
    }

    #[Route('', methods: ['PATCH'])]
    #[RequiresPermission]
    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $settings = $this->settingsRepo->provisionForUser($user);

        $data = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('account_type', $data)) {
            if (!in_array($data['account_type'], self::VALID_ACCOUNT_TYPES, true)) {
                return new JsonResponse(['message' => 'account_type must be human or bot.'], 400);
            }
            $settings->setAccountType($data['account_type']);
        }

        if (array_key_exists('avatar_url', $data)) {
            $settings->setAvatarUrl($data['avatar_url'] !== '' ? $data['avatar_url'] : null);
        }

        if (array_key_exists('personal_statement', $data)) {
            $settings->setPersonalStatement($data['personal_statement'] !== '' ? $data['personal_statement'] : null);
        }

        if (array_key_exists('pick_style', $data)) {
            $settings->setPickStyle($data['pick_style'] !== '' ? $data['pick_style'] : null);
        }

        $botId = trim($data['bot_id'] ?? '');

        if ($settings->isBot() && $botId === '' && !$this->subscriptionRepo->findActiveForUser($user)) {
            return new JsonResponse(['message' => "Bot accounts must subscribe to a bot's signal."], 400);
        }

        if ($botId !== '') {
            $this->subscriptionRepo->subscribe($user, $botId);
        }

        $this->em->flush();

        return new JsonResponse($this->serialize($user));
    }

    private function serialize(User $user): array
    {
        $settings = $this->settingsRepo->provisionForUser($user);

        return [
            ...$settings->toArray(),
            'bot_subscriptions' => array_map(
                fn ($s) => $s->toArray(),
                $this->subscriptionRepo->findAllForUser($user),
            ),
        ];
    }
}
