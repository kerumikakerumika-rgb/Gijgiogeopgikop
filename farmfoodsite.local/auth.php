<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function reply(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(405, ['success' => false, 'message' => 'Разрешён только POST-запрос.']);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw ?: '', true);
if (!is_array($data)) {
    reply(400, ['success' => false, 'message' => 'Некорректные данные формы.']);
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    reply(403, ['success' => false, 'message' => 'Сессия устарела. Обновите страницу и попробуйте снова.']);
}

$action = $data['action'] ?? '';

if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    reply(200, ['success' => true, 'message' => 'Вы вышли из аккаунта.']);
}

try {
    require __DIR__ . '/config.php';
    $pdo = db();

    if ($action === 'register') {
        $name = trim((string)($data['name'] ?? ''));
        $email = mb_strtolower(trim((string)($data['email'] ?? '')));
        $phone = trim((string)($data['phone'] ?? ''));
        $password = (string)($data['password'] ?? '');

        if ($name === '' || mb_strlen($name) > 100) {
            reply(422, ['success' => false, 'message' => 'Введите имя (не более 100 символов).']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            reply(422, ['success' => false, 'message' => 'Введите корректную электронную почту.']);
        }
        if ($phone === '' || mb_strlen($phone) > 30) {
            reply(422, ['success' => false, 'message' => 'Введите номер телефона.']);
        }
        if (strlen($password) < 8) {
            reply(422, ['success' => false, 'message' => 'Пароль должен содержать минимум 8 символов.']);
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            reply(409, ['success' => false, 'message' => 'Пользователь с такой почтой уже зарегистрирован.']);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash) VALUES (?, ?, ?, ?)');
        $insert->execute([$name, $email, $phone, $hash]);

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$pdo->lastInsertId();
        $_SESSION['user_name'] = $name;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        reply(201, ['success' => true, 'message' => 'Регистрация прошла успешно!']);
    }

    if ($action === 'login') {
        $email = mb_strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            reply(422, ['success' => false, 'message' => 'Проверьте почту и пароль.']);
        }

        $query = $pdo->prepare('SELECT id, name, password_hash FROM users WHERE email = ? LIMIT 1');
        $query->execute([$email]);
        $user = $query->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            reply(401, ['success' => false, 'message' => 'Неверная почта или пароль.']);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        reply(200, ['success' => true, 'message' => 'Вы успешно вошли в аккаунт!']);
    }

    reply(400, ['success' => false, 'message' => 'Неизвестное действие.']);
} catch (PDOException $e) {
    error_log('Farm Food database error: ' . $e->getMessage());
    reply(500, ['success' => false, 'message' => 'Ошибка базы данных. Проверьте настройки config.php и импортируйте database.sql.']);
} catch (Throwable $e) {
    error_log('Farm Food error: ' . $e->getMessage());
    reply(500, ['success' => false, 'message' => 'Внутренняя ошибка сервера.']);
}
