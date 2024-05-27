<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class SubscriptionCoordinator
{
    public function __construct(private int $butchSize, private string $emailFrom, private string $emailText)
    {
    }

    public function sendExpirationReminderEmails(int $expiresIn): void
    {
        $activeTo = Carbon::now()->startOfDay()->addDays($expiresIn);
        $activeFrom = $activeTo->subDay();

        DB::table('users')
            ->select(['email', 'email_validated_at'])
            ->whereBetween('subscription_active_until', [$activeTo, $activeFrom])
            ->whereNotNull('email_confirmed_at')
            ->chunkById($this->butchSize, function (Collection $users) use ($activeTo, $activeFrom) {
                [$usersWithValidEmails, $usersWithNonValidEmails] = $this->sendExpirationReminderTo($users);

                $this->updateUsersWithValidEmails($usersWithValidEmails, $activeTo, $activeFrom);
                $this->updateUsersWithNonValidEmails($usersWithNonValidEmails, $activeTo, $activeFrom);
            });
    }

    private function sendExpirationReminderTo(Collection $users): array
    {
        /**
         * @var array{email: string, email_validated_at: ?int} $user
         */
        $usersWithValidEmails = $usersWithNonValidEmails = [];
        foreach ($users as $user) {
            $userEmail = $user['email'];

            if (!$this->hasValidatedEmail($user) && !$this->validateEmailDummy($userEmail)) {
                $usersWithNonValidEmails [] = $userEmail;

                continue;
            }

            if ($this->sendEmailDummy($this->emailFrom, $userEmail, $this->emailText)) {
                $usersWithValidEmails [] = $userEmail;

                continue;
            }

            $usersWithNonValidEmails [] = $userEmail;
        }

        return [$usersWithValidEmails, $usersWithNonValidEmails];
    }

    private function hasValidatedEmail(array $user): bool
    {
        return $user['email_validated_at'] !== null;
    }

    private function validateEmailDummy(string $emailText): int
    {
        return random_int(0, 1);
    }

    private function sendEmailDummy(string $emailForm, string $emailTO, string $emailText): int
    {
        return random_int(0, 1);
    }

    private function updateUsersWithValidEmails(
        array $usersWithValidEmails,
        Carbon $activeTo,
        Carbon $activeFrom
    ): void {
        DB::table('users')
            ->whereBetween('subscription_active_until', [$activeTo, $activeFrom])
            ->whereNotNull('email_confirmed_at')
            ->whereIn('email', $usersWithValidEmails)
            ->update(['email_validated_at' => Carbon::now()]);
    }

    private function updateUsersWithNonValidEmails(
        array $usersWithNonValidEmails,
        Carbon $activeTo,
        Carbon $activeFrom
    ): void {
        DB::table('users')
            ->whereBetween('subscription_active_until', [$activeTo, $activeFrom])
            ->whereNotNull('email_confirmed_at')
            ->whereIn('email', $usersWithNonValidEmails)
            ->update(['email_validated_at' => null]);
    }
}
