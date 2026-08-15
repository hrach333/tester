<?php
$encoded = '';
$decodedImage = '';
$error = '';
$maxFileSize = 10 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'encode') {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $error = 'Не удалось загрузить изображение.';
        } elseif ($_FILES['image']['size'] > $maxFileSize) {
            $error = 'Файл слишком большой. Максимальный размер — 10 МБ.';
        } else {
            $tmpName = $_FILES['image']['tmp_name'];
            $mime = mime_content_type($tmpName);

            if ($mime === false || !str_starts_with($mime, 'image/')) {
                $error = 'Можно загружать только изображения.';
            } else {
                $fileContent = file_get_contents($tmpName);

                if ($fileContent === false) {
                    $error = 'Не удалось прочитать загруженный файл.';
                } else {
                    $encoded = base64_encode($fileContent);
                }
            }
        }
    }

    if ($action === 'decode') {
        $base64 = trim($_POST['base64'] ?? '');

        if ($base64 === '') {
            $error = 'Вставь Base64-код изображения.';
        } else {
            if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $base64)) {
                $base64 = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', $base64);
            }

            $base64 = preg_replace('/\s+/', '', $base64);
            $decoded = base64_decode($base64, true);

            if ($decoded === false) {
                $error = 'Base64-код повреждён или имеет неверный формат.';
            } elseif (strlen($decoded) > $maxFileSize) {
                $error = 'Декодированное изображение слишком большое. Максимум — 10 МБ.';
            } else {
                $imageInfo = @getimagesizefromstring($decoded);

                if ($imageInfo === false || empty($imageInfo['mime']) || !str_starts_with($imageInfo['mime'], 'image/')) {
                    $error = 'Base64 содержит данные, но это не изображение.';
                } else {
                    $decodedImage = 'data:' . $imageInfo['mime'] . ';base64,' . base64_encode($decoded);
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Image ↔ Base64</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: Arial, sans-serif; background: #f4f6fb; color: #202431; }
        .container { width: min(1100px, calc(100% - 32px)); margin: 40px auto; }
        h1 { margin-bottom: 8px; }
        .subtitle { margin-top: 0; color: #667085; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; margin-top: 28px; }
        .card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 10px 30px rgba(31, 41, 55, 0.08); }
        label { display: block; font-weight: 700; margin-bottom: 10px; }
        input[type="file"], textarea { width: 100%; }
        input[type="file"] { padding: 14px; border: 1px solid #d0d5dd; border-radius: 10px; background: #fff; }
        textarea { min-height: 240px; resize: vertical; padding: 14px; border: 1px solid #d0d5dd; border-radius: 10px; font-family: monospace; line-height: 1.45; }
        button { margin-top: 14px; border: 0; border-radius: 10px; padding: 12px 18px; cursor: pointer; font-weight: 700; background: #5b5bd6; color: #fff; }
        .secondary { background: #344054; }
        .error { margin-top: 20px; padding: 14px 16px; border-radius: 10px; background: #fee4e2; color: #b42318; }
        .result { margin-top: 22px; }
        .preview { max-width: 100%; max-height: 520px; display: block; margin-top: 14px; border-radius: 12px; border: 1px solid #e4e7ec; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; }
        @media (max-width: 800px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <h1>Изображение ↔ Base64</h1>
    <p class="subtitle">Кодирование картинки в Base64 и обратное преобразование Base64 в изображение.</p>

    <?php if ($error !== ''): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="grid">
        <section class="card">
            <h2>Картинка → Base64</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="encode">
                <label for="image">Выбери изображение</label>
                <input id="image" type="file" name="image" accept="image/*" required>
                <button type="submit">Закодировать</button>
            </form>

            <?php if ($encoded !== ''): ?>
                <div class="result">
                    <label for="encodedResult">Base64</label>
                    <textarea id="encodedResult" readonly><?= htmlspecialchars($encoded, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <div class="actions">
                        <button type="button" onclick="copyEncoded()">Скопировать</button>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Base64 → картинка</h2>
            <form method="post">
                <input type="hidden" name="action" value="decode">
                <label for="base64">Вставь Base64</label>
                <textarea id="base64" name="base64" placeholder="iVBORw0KGgoAAA... или data:image/png;base64,..." required><?= htmlspecialchars(($_POST['action'] ?? '') === 'decode' ? ($_POST['base64'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <button type="submit" class="secondary">Показать изображение</button>
            </form>

            <?php if ($decodedImage !== ''): ?>
                <div class="result">
                    <img class="preview" src="<?= htmlspecialchars($decodedImage, ENT_QUOTES, 'UTF-8') ?>" alt="Декодированное изображение">
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<script>
    async function copyEncoded() {
        const textarea = document.getElementById('encodedResult');
        if (!textarea) return;
        await navigator.clipboard.writeText(textarea.value);
    }
</script>
</body>
</html>
