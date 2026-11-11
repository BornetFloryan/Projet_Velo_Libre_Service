<?php

declare(strict_types=1);

date_default_timezone_set('Europe/Paris');
session_name('velo_libre_service_session');
session_start();

const APP_NAME = 'Vélo Libre-Service';

$config = [
    'db_host' => getenv('VLS_DB_HOST') ?: '127.0.0.1',
    'db_port' => (int) (getenv('VLS_DB_PORT') ?: 3306),
    'db_name' => getenv('VLS_DB_NAME') ?: 'velo_libre_service',
    'db_user' => getenv('VLS_DB_USER') ?: 'root',
    'db_password' => getenv('VLS_DB_PASSWORD') ?: '',
];

$localConfigPath = dirname(__DIR__) . '/config.local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (is_array($localConfig)) {
        $config = array_replace($config, $localConfig);
    }
}

$pdo = null;
$databaseError = null;

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        $config['db_port'],
        $config['db_name']
    );

    $pdo = new PDO($dsn, $config['db_user'], $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $exception) {
    $databaseError = $exception;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function appUrl(?string $page = null, array $params = []): string
{
    $query = $params;
    if ($page !== null && $page !== '') {
        $query = ['page' => $page] + $query;
    }

    return 'index.php' . ($query === [] ? '' : '?' . http_build_query($query));
}

function redirectTo(string $page, array $params = []): never
{
    header('Location: ' . appUrl($page, $params));
    exit;
}

function currentActor(): ?array
{
    return $_SESSION['actor'] ?? null;
}

function isAuthenticated(): bool
{
    return currentActor() !== null;
}

function isUsager(): bool
{
    return (currentActor()['type'] ?? null) === 'usager';
}

function isOperator(): bool
{
    return (currentActor()['type'] ?? null) === 'operateur';
}

function requireActor(): array
{
    $actor = currentActor();
    if ($actor === null) {
        throw new RuntimeException('Veuillez vous connecter pour accéder à cette action.');
    }

    return $actor;
}

function requireUsager(): array
{
    $actor = requireActor();
    if ($actor['type'] !== 'usager') {
        throw new RuntimeException('Cette action est réservée aux usagers.');
    }

    return $actor;
}

function requireOperator(array $roles = []): array
{
    $actor = requireActor();
    if ($actor['type'] !== 'operateur') {
        throw new RuntimeException('Cette action est réservée aux opérateurs.');
    }

    if ($roles !== [] && !in_array($actor['role'], $roles, true)) {
        throw new RuntimeException('Votre rôle ne permet pas cette action.');
    }

    return $actor;
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!is_string($submittedToken) || !hash_equals(csrfToken(), $submittedToken)) {
        throw new RuntimeException('Le formulaire a expiré. Rechargez la page puis réessayez.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function consumeFlashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    return $flashes;
}

function fetchOne(PDO $pdo, string $sql, array $params = []): ?array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $result = $statement->fetch();

    return $result === false ? null : $result;
}

function fetchAllRows(PDO $pdo, string $sql, array $params = []): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function fetchValue(PDO $pdo, string $sql, array $params = []): mixed
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchColumn();
}

function optionalInt(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }

    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    if ($filtered === false) {
        throw new RuntimeException('La valeur numérique transmise est invalide.');
    }

    return (int) $filtered;
}

function requiredString(string $key, int $maxLength = 255): string
{
    $value = trim((string) ($_POST[$key] ?? ''));
    if ($value === '') {
        throw new RuntimeException('Tous les champs obligatoires doivent être renseignés.');
    }

    if (mb_strlen($value) > $maxLength) {
        throw new RuntimeException('Un champ dépasse la longueur autorisée.');
    }

    return $value;
}

function businessMessage(Throwable $exception): string
{
    $message = $exception->getMessage();

    if ($exception instanceof PDOException) {
        if (preg_match('/(?:MESSAGE_TEXT|45000).*?:\s*(.+)$/s', $message, $matches)) {
            return trim($matches[1]);
        }

        if (str_contains($message, 'Duplicate entry')) {
            return 'Cette information existe déjà dans la base.';
        }

        if (str_contains($message, 'foreign key constraint fails')) {
            return 'L’opération est impossible car une donnée associée est manquante ou encore utilisée.';
        }
    }

    return $message;
}

function frenchDate(?string $dateTime, bool $withTime = true): string
{
    if ($dateTime === null || $dateTime === '') {
        return '—';
    }

    $date = new DateTimeImmutable($dateTime);
    return $date->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
}

function money(mixed $amount): string
{
    return number_format((float) $amount, 2, ',', ' ') . ' €';
}
