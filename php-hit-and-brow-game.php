<?php
session_start();

// 1. 新しいゲームの初期化関数（難易度を引数で受け取る）
function initGame($difficulty = 4) {
    $numbers = range(0, 9);
    shuffle($numbers);
    
    // 指定された桁数分だけ切り出す
    $_SESSION['digits'] = $difficulty; 
    $_SESSION['answer'] = array_slice($numbers, 0, $difficulty);
    $_SESSION['history'] = [];
    $_SESSION['turn'] = 1;
    $_SESSION['cleared'] = false;
}

// 初回アクセス時はデフォルトで「4桁」で初期化
if (!isset($_SESSION['answer'])) {
    initGame(4);
}

// 難易度変更ボタン、または最初からやり直すボタンが押された場合
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_difficulty'])) {
    $level = (int)$_POST['difficulty'];
    initGame($level);
}

$error = '';
$hit = 0;
$blow = 0;
$digits = $_SESSION['digits']; // 現在の桁数（3, 4, 5）

// 2. フォームから予想が送信されたときの処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guess']) && !$_SESSION['cleared']) {
    $input = trim($_POST['guess']);

    // 動的に正規表現を生成（例: 4桁なら /^[0-9]{4}$/）
    if (!preg_match('/^[0-9]{' . $digits . '}$/', $input)) {
        $error = "エラー: {$digits}桁の「数字」を入力してください。";
    } else {
        $guess = str_split($input);
        
        if (count(array_unique($guess)) !== $digits) {
            $error = 'エラー: 重複のない数字を入力してください。';
        } else {
            // Hit & Blow の判定
            $answer = $_SESSION['answer'];
            foreach ($guess as $index => $digit) {
                $digit = (int)$digit;
                if ($digit === $answer[$index]) {
                    $hit++;
                } elseif (in_array($digit, $answer, true)) {
                    $blow++;
                }
            }

            // 履歴に保存
            $_SESSION['history'][] = [
                'turn'  => $_SESSION['turn'],
                'input' => $input,
                'hit'   => $hit,
                'blow'  => $blow
            ];

            // クリア判定（桁数と同じ数だけHitしたらクリア）
            if ($hit === $digits) {
                $_SESSION['cleared'] = true;
            } else {
                $_SESSION['turn']++;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>PHP Hit & Blow (難易度選択版)</title>
    <style>
        body { font-family: sans-serif; background: #f5f5f5; padding: 20px; display: flex; flex-direction: column; align-items: center; }
        .game-container { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 400px; }
        h1 { font-size: 24px; text-align: center; color: #333; margin-top: 0; }
        .error { color: #e74c3c; font-weight: bold; margin-bottom: 15px; font-size: 14px; text-align: center; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-weight: bold; }
        .input-group { margin-bottom: 20px; }
        input[type="text"] { width: 100%; padding: 10px; font-size: 20px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; text-align: center; letter-spacing: 5px; }
        button { width: 100%; padding: 10px; font-size: 16px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 10px; font-weight: bold; }
        .btn-primary { background: #3498db; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        
        /* 難易度選択のスタイル */
        .difficulty-area { background: #eef2f3; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; }
        .difficulty-radio { margin: 0 10px; font-weight: bold; cursor: pointer; }

        .history-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .history-table th, .history-table td { border-bottom: 1px solid #eee; padding: 10px; text-align: center; }
        .history-table th { background: #f8f9fa; color: #666; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; color: white; }
        .badge-hit { background: #e74c3c; }
        .badge-blow { background: #f39c12; }
    </style>
</head>
<body>

<div class="game-container">
    <h1>Hit & Blow</h1>

    <!-- 難易度設定フォーム -->
    <div class="difficulty-area">
        <form method="POST" action="">
            <span style="font-size: 14px; color: #555; display: block; margin-bottom: 8px;">▼ 難易度（桁数）を選択 ▼</span>
            <label class="difficulty-radio"><input type="radio" name="difficulty" value="3" <?= $digits === 3 ? 'checked' : '' ?>> 3桁 (Easy)</label>
            <label class="difficulty-radio"><input type="radio" name="difficulty" value="4" <?= $digits === 4 ? 'checked' : '' ?>> 4桁 (Normal)</label>
            <label class="difficulty-radio"><input type="radio" name="difficulty" value="5" <?= $digits === 5 ? 'checked' : '' ?>> 5桁 (Hard)</label>
            <button type="submit" name="change_difficulty" class="btn-secondary" style="margin-top: 10px; font-size: 13px; padding: 5px;">難易度を変更してリセット</button>
        </form>
    </div>

    <!-- エラーメッセージ -->
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- クリア画面 -->
    <?php if ($_SESSION['cleared']): ?>
        <div class="success">
            🎉 おめでとうございます！<br>
            <?= $_SESSION['digits'] ?>桁の数字を<br>
            <?= $_SESSION['turn'] ?> ターン目で当てました！
        </div>
    <?php endif; ?>

    <!-- 入力フォーム -->
    <form method="POST" action="">
        <?php if (!$_SESSION['cleared']): ?>
            <div class="input-group">
                <label style="display:block; margin-bottom: 5px; color:#555; font-weight: bold;">
                    ターン <?= $_SESSION['turn'] ?> （<?= $digits ?>桁の予想）:
                </label>
                <!-- HTML側でもmaxlengthを動的に変更 -->
                <input type="text" name="guess" maxlength="<?= $digits ?>" placeholder="<?= str_repeat('?', $digits) ?>" autocomplete="off" required autofocus>
            </div>
            <button type="submit" class="btn-primary">予想を送信</button>
        <?php endif; ?>
    </form>

    <!-- 履歴の表示 -->
    <?php if (!empty($_SESSION['history'])): ?>
        <table class="history-table">
            <thead>
                <tr>
                    <th>ターン</th>
                    <th>予想</th>
                    <th>結果</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($_SESSION['history']) as $log): ?>
                    <tr>
                        <td><?= $log['turn'] ?></td>
                        <td style="font-weight: bold; letter-spacing: 2px;"><?= htmlspecialchars($log['input'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge badge-hit"><?= $log['hit'] ?> H</span>
                            <span class="badge badge-blow"><?= $log['blow'] ?> B</span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
