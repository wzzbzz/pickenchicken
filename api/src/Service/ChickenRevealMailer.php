<?php

namespace App\Service;

use App\Entity\Game;
use App\Repository\UserPickRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class ChickenRevealMailer
{
    public function __construct(
        private readonly UserPickRepository $pickRepo,
        private readonly MailerInterface $mailer,
    ) {}

    /**
     * Emails every player who has a pick on this game, revealing the Chicken's
     * certified pick and which bot in its crew it was sourced from.
     */
    public function revealFor(Game $game): int
    {
        if ($game->getChickenPick() === null) {
            return 0;
        }

        $picks = $this->pickRepo->findBy(['game' => $game]);
        $sent = 0;

        foreach ($picks as $pick) {
            $email = $pick->getUser()->getEmail();
            if (!$email) {
                continue;
            }

            $this->mailer->send($this->buildEmail($game, $email, $pick->getPick()));
            $sent++;
        }

        return $sent;
    }

    private function buildEmail(Game $game, string $toEmail, string $userPick): Email
    {
        $chickenTeam = $this->teamFor($game, $game->getChickenPick());
        $userTeam = $this->teamFor($game, $userPick);
        $justification = $this->justification($game);

        $html = sprintf(
            '<p><strong>The Chicken has picked %s vs %s.</strong></p>'
            . '<p>The Chicken is on <strong>%s</strong>.</p>'
            . '<p>%s</p>'
            . '<p>Your pick: <strong>%s</strong>.</p>',
            htmlspecialchars($game->getAwayTeam()),
            htmlspecialchars($game->getHomeTeam()),
            htmlspecialchars($chickenTeam),
            htmlspecialchars($justification),
            htmlspecialchars($userTeam),
        );

        return (new Email())
            ->from('noreply@pickenchicken.com')
            ->to($toEmail)
            ->subject(sprintf('🐔 The Chicken picks %s', $chickenTeam))
            ->html($html);
    }

    private function justification(Game $game): string
    {
        $botName = $this->friendlyBotName($game->getChickenBotId());
        $strength = $game->getChickenSignalStrength();

        if ($strength === null) {
            return sprintf('No source bot data was available — this pick fell back to a coin flip.');
        }

        return sprintf(
            'The most significant bot in the Chicken\'s crew for this game was "%s", currently hitting %s%% against the spread — that signal was strong enough for the Chicken to ride with it.',
            $botName,
            number_format((float) $strength, 1),
        );
    }

    private function friendlyBotName(?string $botId): string
    {
        if (!$botId) {
            return 'an unknown bot';
        }

        return ucwords(str_replace('_', ' ', $botId));
    }

    private function teamFor(Game $game, ?string $side): string
    {
        return $side === 'home' ? $game->getHomeTeam() : $game->getAwayTeam();
    }
}
