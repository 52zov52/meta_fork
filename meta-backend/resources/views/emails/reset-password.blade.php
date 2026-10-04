<!DOCTYPE html>
<html>
<head>
    <title>Обновление Пароля к Метавселенной</title>
    <style>
        body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px 30px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); border-top: 5px solid #0056b3; }
        h1 { color: #0056b3; font-size: 24px; margin-bottom: 20px; text-align: center; }
        p { margin-bottom: 1em; font-size: 16px; }
        strong { color: #0056b3; }
        .password-box { background-color: #e9ecef; border: 1px solid #ced4da; padding: 15px; margin: 20px 0; border-radius: 5px; text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 1.5px; word-break: break-all; }
        .warning { background-color: #fff3cd; border-left: 5px solid #ffeeba; padding: 10px 15px; margin-top: 20px; border-radius: 5px; font-size: 14px; color: #664d03; }
        .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; font-size: 14px; color: #777; }
        a { color: #0056b3; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Meta IUBIP | Доступ Обновлен!</h1>

        <p>Здравствуйте, <strong>{{ $userName }}</strong>!</p>

        <p>Надеемся, у вас все хорошо. Мы обновили ваш пароль для доступа к сайту метавселенной. Это необходимо для обеспечения безопасности и актуальности ваших учетных данных.</p>

        <p>Ваш <strong>новый временный пароль</strong>:</p>
        <div class="password-box">
            {{ $newPassword }}
        </div>

        <div class="warning">
            <p><strong>Важно:</strong> Для вашей безопасности мы настоятельно рекомендуем изменить этот временный пароль на более сложный и запоминающийся сразу после первого входа в систему.</p>
            <p>Пожалуйста, не сообщайте ваш пароль никому и не храните его в легкодоступном месте.</p>
        </div>

        <p>Вы можете войти в систему, используя ваш email ({{ $user->email }}) и этот новый пароль.</p>

        <p>Если у вас возникнут вопросы или проблемы со входом, пожалуйста, напишите письмо на эту почту с которой вам поступило это письмо.</p>

        <p>С уважением,<br>Администрация Сайта</p>

        <div class="footer">
            <p>Это автоматическое уведомление. Пожалуйста, не отвечайте на это письмо. Если вы получили это письмо по ошибке, пожалуйста, проигнорируйте его.</p>
            <p>&copy; {{ date('Y') }} Meta IUBIP. Все права защищены.</p>
        </div>
    </div>
</body>
</html>
