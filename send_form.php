<?php
// エラーレポートの設定
error_reporting(E_ALL);
ini_set('display_errors', 0); // 本番環境では0に設定

// 文字コードをUTF-8に設定
mb_language("japanese"); 
mb_internal_encoding("UTF-8");

// CSRFチェック（簡易的な対策）
if (!isset($_POST['form_submitted']) || $_POST['form_submitted'] !== '1') {
    header("Location: index.html?error=invalid_submission");
    exit;
}

// フォームからのデータ取得と無害化
$name = isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8') : '未入力';
$email = isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '未入力';
$area = isset($_POST['area']) ? htmlspecialchars($_POST['area'], ENT_QUOTES, 'UTF-8') : '未選択';
$topics = isset($_POST['topics']) ? htmlspecialchars($_POST['topics'], ENT_QUOTES, 'UTF-8') : '未選択';
$opinion = isset($_POST['opinion']) ? htmlspecialchars($_POST['opinion'], ENT_QUOTES, 'UTF-8') : '';
$request = isset($_POST['request']) ? htmlspecialchars($_POST['request'], ENT_QUOTES, 'UTF-8') : '未入力';

// フォームが送信された場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {
      // 送信先メールアドレス
    $to = "info@yachiyo-shisei.sub.jp";
    
    // メールの件名
    $subject = "【八千代市政をぶち壊す会】ウェブサイトからのご意見";
    
    // メール本文
    $message = "八千代市政をぶち壊す会ウェブサイトからのご意見です。\n\n";
    $message .= "■ 送信日時: " . date("Y/m/d H:i:s") . "\n";
    $message .= "■ お名前: " . $name . "\n";
    $message .= "■ メールアドレス: " . $email . "\n";
    $message .= "■ お住まいの地域: " . $area . "\n";
    $message .= "■ 関心のある市政テーマ: " . $topics . "\n\n";
    $message .= "■ ご意見・ご提案:\n" . $opinion . "\n\n";
    $message .= "■ 市政に望むこと:\n" . $request . "\n\n";
    $message .= "---\nこのメールは八千代市政をぶち壊す会ウェブサイトのお問い合わせフォームから自動送信されています。";
    
    // 送信者のアドレスを指定
    $from_email = "no-reply@yachiyo-shisei.sub.jp";
    $from_name = "八千代市政をぶち壊す会";
    
    // ヘッダー情報
    $headers = "From: " . mb_encode_mimeheader($from_name, "UTF-8", "B") . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . ($email != '未入力' ? $email : $from_email) . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // メールを送信（mb_send_mailとmail両方試行）
    $success = false;
    
    // まずmb_send_mailで試行
    if (function_exists('mb_send_mail')) {
        $success = mb_send_mail($to, $subject, $message, $headers);
    }
    
    // 失敗した場合は通常のmailを試行
    if (!$success) {
        $success = mail($to, $subject, $message, $headers);
    }
      if ($success) {
        // 送信成功時、ログ記録（オプション）
        $log_message = date("Y-m-d H:i:s") . " - メール送信成功 - " . $email . "\n";
        @file_put_contents('form_log.txt', $log_message, FILE_APPEND);
        
        // サンクスページにリダイレクト
        header("Location: thanks.html");
        exit;
    } else {
        // エラー時のログ記録（オプション）
        $error_message = date("Y-m-d H:i:s") . " - メール送信失敗 - " . $email . "\n";
        @file_put_contents('form_error_log.txt', $error_message, FILE_APPEND);
        
        // フォールバック：form_processingページに移動してJavaScriptでサンクスページに遷移させる
        header("Location: form_processing.html?error=1");
        exit;
    }
} else {
    // POSTでないアクセスは元のフォームにリダイレクト
    header("Location: index.html");
    exit;
}
?>
