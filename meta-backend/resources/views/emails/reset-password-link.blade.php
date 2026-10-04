<!DOCTYPE html>
<html>

<head>
    <title>Восстановление пароля к Метавселенной</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px 30px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-top: 5px solid #0056b3;
        }

        h1 {
            color: #0056b3;
            font-size: 24px;
            margin-bottom: 20px;
            text-align: center;
        }

        p {
            margin-bottom: 1em;
            font-size: 16px;
        }

        strong {
            color: #0056b3;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0056b3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }

        .button:hover {
            background-color: #003d7a;
        }

        .warning {
            background-color: #fff3cd;
            border-left: 5px solid #ffeeba;
            padding: 10px 15px;
            margin-top: 20px;
            border-radius: 5px;
            font-size: 14px;
            color: #664d03;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 14px;
            color: #777;
        }

        a {
            color: #0056b3;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .link-box {
            word-break: break-all;
            margin: 20px 0;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Meta IUBIP | Восстановление пароля</h1>

        <p>Здравствуйте, <strong>{{ $email }}</strong>!</p>

        <p>Мы получили запрос на восстановление пароля для вашей учетной записи в метавселенной.</p>

        <p>Для установки нового пароля, пожалуйста, нажмите на кнопку ниже:</p>

        <div style="text-align: center;">
            <a href="{{ $resetUrl }}" class="button">Сбросить пароль</a>
        </div>

        <p>Или скопируйте и вставьте следующую ссылку в адресную строку браузера:</p>

        <div class="link-box">
            {{ $resetUrl }}
        </div>

        <div class="warning">
            <p><strong>Важно:</strong> Эта ссылка действительна в течение {{ $expire }} минут. Если вы не запрашивали восстановление пароля, пожалуйста, проигнорируйте это письмо.</p>
            <p>Никогда не передавайте эту ссылку третьим лицам.</p>
        </div>

        <p>Если у вас возникнут проблемы со ссылкой, пожалуйста, напишите на нашу службу поддержки.</p>

        <p>С уважением,<br>Администрация Сайта</p>

        <div class="footer">
            <p>Это автоматическое уведомление. Пожалуйста, не отвечайте на это письмо.</p>
            <p>&copy; {{ date('Y') }} Meta IUBIP. Все права защищены.</p>
        </div>
    </div>
</body>

</html>