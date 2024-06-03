<?php

/**
 * REQUIRED .env variables:
 *
 * 1) DSN
 * 2) DB_USER
 * 3) DB_PASSWORD
 *
 * Available flags:
 *
 * -s email sender
 * -t email text
 * -e subscription expires in
 * -l start from users.id (not including)
 * -s batch size
 *
 * Example:
 *
 * php send_expiration_reminder.php -s sender_email@email.com -t "your subscription is expiring" -e 1 -l 0 -s 200
 */

declare(strict_types=1);

exit(main());

function main(): int
{
    $options = getopt('f:t:e:l::s::');

    $email_from = (string) $options['f'];
    $email_text = (string) $options['t'];
    $expires_in = (int) $options['e'];
    $last_id = (int) ($options['l'] ?? 0);
    $chunk_size = (int) ($options['s'] ?? 200);

    $expires_from = date('Y-m-d 00:00:00', strtotime(sprintf('+%d days', $expires_in)));
    $expires_to = date('Y-m-d 23:59:59', strtotime(sprintf('+%d days', $expires_in)));


    $users = get_users_with_expiring_subscription($expires_from, $expires_to, $last_id, $chunk_size);
    $users_with_valid_emails = $users_with_non_valid_emails = [];

    while (($user = $users->current()) !== null) {
        $users->next();

        $user_email = $user['email'];

        if (!has_validated_email($user) && !check_email($user_email)) {
            $users_with_non_valid_emails [] = $user_email;

            continue;
        }

        if (send_email($email_from, $user_email, $email_text)) {
            $users_with_valid_emails [] = $user_email;

            continue;
        }

        $users_with_non_valid_emails [] = $user_email;

        if (!$users->valid()) {
            update_users_with_valid_emails($users_with_valid_emails, $expires_from, $expires_to);
            update_users_with_non_valid_emails($users_with_non_valid_emails, $expires_from, $expires_to);

            $users_with_valid_emails = $users_with_non_valid_emails = [];
        }
    }

    return 0;
}

function get_users_with_expiring_subscription(
    string $active_from,
    string $active_to,
    int $last_id = 0,
    int $chunk_size = 0
): Generator {
    $sql = <<<SQL
            SELECT email, email_validated_at
            FROM users
            WHERE subscription_active_until BETWEEN :active_from AND :active_to
            AND email_confirmed_at IS NOT NULL
            AND id > :last_id
            ORDER BY id ASC
            LIMIT :chunk_size
            SQL;
    $stmt = get_pdo_statement($sql);

    do {

        $stmt->bindParam(':active_from', $active_from);
        $stmt->bindParam(':active_to', $active_to);
        $stmt->bindParam(':last_id', $last_id, PDO::PARAM_INT);
        $stmt->bindParam(':chunk_size', $chunk_size, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        yield $rows;

        if (!empty($rows)) {
            $last_id = (int) end($rows)['id'];
        }
    } while (!empty($rows));
}

function get_pdo_statement(string $sql): PDOStatement
{
    static $pdo = new PDO($_ENV['DSN'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD']);

    return $pdo->prepare($sql);
}

function has_validated_email(array $user): bool
{
    return $user['email_validated_at'] !== null;
}

function check_email(string $email): bool
{
    sleep(random_int(1, 60));

    return (bool) random_int(0, 1);
}

function send_email(string $email_from, string $email_to, string $text): bool
{
    sleep(random_int(1, 10));

    return (bool) random_int(0, 1);
}

function update_users_with_valid_emails(
    array $users_with_valid_emails,
    string $active_from,
    string $active_to,
): void {
    $sql_template = <<<SQL
                    UPDATE users
                    SET email_validated_at = :email_validated_at
                    WHERE subscription_active_until BETWEEN :active_from AND :active_to AND email_confirmed_at IS NOT NULL AND
                    email IN (%s)
                    SQL;
    $sql = sprintf($sql_template, trim(str_repeat('?,', count($users_with_valid_emails) - 1), ','));

    $params = array_merge([date('Y-m-d H:i:s'), $active_from, $active_to], $users_with_valid_emails);

    get_pdo_statement($sql)->execute($params);
}

function update_users_with_non_valid_emails(
    array $users_with_non_valid_emails,
    string $active_from,
    string $active_to,
): void {
    $sql_template = <<<SQL
                    UPDATE users
                    SET email_validated_at = NULL
                    WHERE subscription_active_until BETWEEN :active_from AND :active_to AND email_confirmed_at IS NOT NULL AND
                    email IN (%s)
                    SQL;
    $sql = sprintf($sql_template, trim(str_repeat('?,', count($users_with_non_valid_emails) - 1), ','));

    $params = array_merge([$active_from, $active_to], $users_with_non_valid_emails);

    get_pdo_statement($sql)->execute($params);
}
