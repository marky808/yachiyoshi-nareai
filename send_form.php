<?php
// エラーレポートの設定
error_reporting(E_ALL);
ini_set('display_errors', 0); // 本番環境では0に設定

// 文字コードをUTF-8に設定
mb_language("Japanese"); 
mb_internal_encoding("UTF-8");
mb_http_output("UTF-8");
mb_regex_encoding("UTF-8");

// CSRFチェック（簡易的な対策）
if (!isset($_POST['form_submitted']) || $_POST['form_submitted'] !== '1') {
    header("Location: index.html?error=invalid_submission");
    exit;
}

// フォームからのデータ取得と文字コード処理
function convert_encoding($str) {
    // 文字コードを自動検出して変換
    $detect_order = array('UTF-8', 'SJIS', 'EUC-JP', 'JIS', 'ASCII');
    $detected = mb_detect_encoding($str, $detect_order, true);
    if ($detected) {
        return mb_convert_encoding($str, 'UTF-8', $detected);
    }
    return mb_convert_encoding($str, 'UTF-8', 'auto');
}

// データの取得と文字コード変換、HTMLエスケープ処理
$name = isset($_POST['name']) ? htmlspecialchars(convert_encoding($_POST['name']), ENT_QUOTES, 'UTF-8') : '未入力';
$email = isset($_POST['email']) ? htmlspecialchars(convert_encoding($_POST['email']), ENT_QUOTES, 'UTF-8') : '未入力';
$area = isset($_POST['area']) ? htmlspecialchars(convert_encoding($_POST['area']), ENT_QUOTES, 'UTF-8') : '未選択';
$topics = isset($_POST['topics']) ? htmlspecialchars(convert_encoding($_POST['topics']), ENT_QUOTES, 'UTF-8') : '未選択';
$opinion = isset($_POST['opinion']) ? htmlspecialchars(convert_encoding($_POST['opinion']), ENT_QUOTES, 'UTF-8') : '';
$request = isset($_POST['request']) ? htmlspecialchars(convert_encoding($_POST['request']), ENT_QUOTES, 'UTF-8') : '未入力';

// フォームが送信された場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {
      // 送信先メールアドレス
    $to = "info@yachiyo-shisei.sub.jp";
    
    // メールの件名
    $subject = "【八千代市政をぶち壊す会】ウェブサイトからのご意見";
      // メール本文
    $message = '';
    $message .= "八千代市政をぶち壊す会ウェブサイトからのご意見です。\n\n";
    $message .= "■ 送信日時: " . date("Y/m/d H:i:s") . "\n";
    $message .= "■ お名前: " . $name . "\n";
    $message .= "■ メールアドレス: " . $email . "\n";
    $message .= "■ お住まいの地域: " . $area . "\n";
    $message .= "■ 関心のある市政テーマ: " . $topics . "\n\n";
    $message .= "■ ご意見・ご提案:\n" . $opinion . "\n\n";
    $message .= "■ 市政に望むこと:\n" . $request . "\n\n";
    $message .= "---\nこのメールは八千代市政をぶち壊す会ウェブサイトのお問い合わせフォームから自動送信されています。";
    
    // 確実にUTF-8で処理するための対策
    $message = mb_convert_encoding($message, "UTF-8", "auto");
      // 送信者のアドレスを指定
    $from_email = "no-reply@yachiyo-shisei.sub.jp";
    $from_name = "八千代市政をぶち壊す会";
    
    // ヘッダー情報
    $headers = "From: " . mb_encode_mimeheader($from_name, "UTF-8", "B") . " <" . $from_email . ">\r\n";
    $headers .= "Reply-To: " . ($email != '未入力' ? $email : $from_email) . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
      // メールの内容をエンコード
    $subject = mb_encode_mimeheader($subject, "UTF-8", "B");
    
    // メールを送信（mb_send_mailとmail両方試行）
    $success = false;
      // まずmb_send_mailで試行（パラメータ5つ目にエンコードを指定）
    if (function_exists('mb_send_mail')) {
        $additional_parameter = "-f " . $from_email;
        $success = mb_send_mail($to, $subject, $message, $headers, $additional_parameter);
    }
    
    // 失敗した場合は通常のmailを試行
    if (!$success) {
        $additional_parameter = "-f " . $from_email;
        $success = mail($to, $subject, $message, $headers, $additional_parameter);
    }
    
    // デバッグ用にコピーを別メールアドレスに送信（テスト用）
    $debug_email = "808works.jp@gmail.com"; // テスト用メールアドレス
    if ($debug_email) {
        $debug_subject = "【デバッグ】フォーム送信データ";
        $debug_message = "以下は送信されたフォームデータのコピーです。\n\n";
        $debug_message .= "■ 送信日時: " . date("Y/m/d H:i:s") . "\n";
        $debug_message .= "■ お名前: " . $name . "\n";
        $debug_message .= "■ メールアドレス: " . $email . "\n";
        $debug_message .= "■ お住まいの地域: " . $area . "\n";
        $debug_message .= "■ 関心のある市政テーマ: " . $topics . "\n\n";
        $debug_message .= "■ ご意見・ご提案:\n" . $opinion . "\n\n";
        $debug_message .= "■ 市政に望むこと:\n" . $request . "\n\n";
        $debug_message .= "---\nこれはデバッグ用のメールです。";
        
        $debug_headers = "From: 八千代市政をぶち壊す会 <debug@yachiyo-shisei.sub.jp>\r\n";
        $debug_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $debug_headers .= "Content-Transfer-Encoding: 8bit\r\n";
        
        mb_send_mail($debug_email, $debug_subject, $debug_message, $debug_headers);
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
