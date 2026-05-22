<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateLang;
use App\Models\UserEmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $getExtraEmailTemplates = EmailTemplate::whereIn('name',['Appointment Created','User Created'])->get();
        if($getExtraEmailTemplates->isNotEmpty()){
            foreach($getExtraEmailTemplates as $template){
                $template->delete();
            }
        }
        
        $languages = json_decode(file_get_contents(resource_path('lang/language.json')), true);
        $langCodes = collect($languages)->pluck('code')->toArray();
        $fromName = isSaas() ? 'HRM SaaS' : 'HRM';
        

        $templates = [
            [
                'name' => 'User Created',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Welcome to {app_name} - Account Created',
                        'content' => '<p>Hello <strong>{user_name}</strong>,</p><p>Your account has been successfully created on {app_name}.</p><p><strong>Login Details:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>Email: {user_email}</li><li>Password: {user_password}</li><li>Account Type: {user_type}</li></ul><p>Please keep this information secure and change your password after first login.</p><p>Best regards,<br>{app_name} Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Bienvenido a {app_name} - Cuenta Creada',
                        'content' => '<p>Hola <strong>{user_name}</strong>,</p><p>Su cuenta ha sido creada exitosamente en {app_name}.</p><p><strong>Detalles de acceso:</strong></p><ul><li>Sitio web: <a href="{app_url}">{app_url}</a></li><li>Email: {user_email}</li><li>Contraseña: {user_password}</li><li>Tipo de cuenta: {user_type}</li></ul><p>Por favor mantenga esta información segura y cambie su contraseña después del primer inicio de sesión.</p><p>Saludos cordiales,<br>Equipo de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'مرحباً بك في {app_name} - تم إنشاء الحساب',
                        'content' => '<p>مرحباً <strong>{user_name}</strong>،</p><p>تم إنشاء حسابك بنجاح على {app_name}.</p><p><strong>تفاصيل تسجيل الدخول:</strong></p><ul><li>الموقع: <a href="{app_url}">{app_url}</a></li><li>البريد الإلكتروني: {user_email}</li><li>كلمة المرور: {user_password}</li><li>نوع الحساب: {user_type}</li></ul><p>يرجى الحفاظ على هذه المعلومات آمنة وتغيير كلمة المرور بعد أول تسجيل دخول.</p><p>مع أطيب التحيات،<br>فريق {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Velkommen til {app_name} - Konto Oprettet',
                        'content' => '<p>Hej <strong>{user_name}</strong>,</p><p>Din konto er blevet oprettet på {app_name}.</p><p><strong>Login detaljer:</strong></p><ul><li>Hjemmeside: <a href="{app_url}">{app_url}</a></li><li>Email: {user_email}</li><li>Adgangskode: {user_password}</li><li>Kontotype: {user_type}</li></ul><p>Hold venligst disse oplysninger sikre og skift din adgangskode efter første login.</p><p>Med venlig hilsen,<br>{app_name} Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Willkommen bei {app_name} - Konto Erstellt',
                        'content' => '<p>Hallo <strong>{user_name}</strong>,</p><p>Ihr Konto wurde erfolgreich auf {app_name} erstellt.</p><p><strong>Anmeldedaten:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>E-Mail: {user_email}</li><li>Passwort: {user_password}</li><li>Kontotyp: {user_type}</li></ul><p>Bitte bewahren Sie diese Informationen sicher auf und ändern Sie Ihr Passwort nach der ersten Anmeldung.</p><p>Mit freundlichen Grüßen,<br>{app_name} Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Bienvenue sur {app_name} - Compte Créé',
                        'content' => '<p>Bonjour <strong>{user_name}</strong>,</p><p>Votre compte a été créé avec succès sur {app_name}.</p><p><strong>Détails de connexion:</strong></p><ul><li>Site web: <a href="{app_url}">{app_url}</a></li><li>Email: {user_email}</li><li>Mot de passe: {user_password}</li><li>Type de compte: {user_type}</li></ul><p>Veuillez garder ces informations en sécurité et changer votre mot de passe après la première connexion.</p><p>Cordialement,<br>Équipe {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'ברוכים הבאים ל-{app_name} - חשבון נוצר',
                        'content' => '<p>שלום <strong>{user_name}</strong>,</p><p>החשבון שלך נוצר בהצלחה ב-{app_name}.</p><p><strong>פרטי התחברות:</strong></p><ul><li>אתר: <a href="{app_url}">{app_url}</a></li><li>אימייל: {user_email}</li><li>סיסמה: {user_password}</li><li>סוג חשבון: {user_type}</li></ul><p>אנא שמור על מידע זה מאובטח ושנה את הסיסמה שלך לאחר הכניסה הראשונה.</p><p>בברכה,<br>צוות {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Benvenuto su {app_name} - Account Creato',
                        'content' => '<p>Ciao <strong>{user_name}</strong>,</p><p>Il tuo account è stato creato con successo su {app_name}.</p><p><strong>Dettagli di accesso:</strong></p><ul><li>Sito web: <a href="{app_url}">{app_url}</a></li><li>Email: {user_email}</li><li>Password: {user_password}</li><li>Tipo di account: {user_type}</li></ul><p>Si prega di mantenere queste informazioni al sicuro e cambiare la password dopo il primo accesso.</p><p>Cordiali saluti,<br>Team {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '{app_name}へようこそ - アカウント作成完了',
                        'content' => '<p>こんにちは <strong>{user_name}</strong>様、</p><p>{app_name}でアカウントが正常に作成されました。</p><p><strong>ログイン情報:</strong></p><ul><li>ウェブサイト: <a href="{app_url}">{app_url}</a></li><li>メール: {user_email}</li><li>パスワード: {user_password}</li><li>アカウントタイプ: {user_type}</li></ul><p>この情報を安全に保管し、初回ログイン後にパスワードを変更してください。</p><p>よろしくお願いいたします、<br>{app_name}チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Welkom bij {app_name} - Account Aangemaakt',
                        'content' => '<p>Hallo <strong>{user_name}</strong>,</p><p>Uw account is succesvol aangemaakt op {app_name}.</p><p><strong>Inloggegevens:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>Email: {user_email}</li><li>Wachtwoord: {user_password}</li><li>Accounttype: {user_type}</li></ul><p>Bewaar deze informatie veilig en wijzig uw wachtwoord na de eerste login.</p><p>Met vriendelijke groet,<br>{app_name} Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Witamy w {app_name} - Konto Utworzone',
                        'content' => '<p>Witaj <strong>{user_name}</strong>,</p><p>Twoje konto zostało pomyślnie utworzone w {app_name}.</p><p><strong>Dane logowania:</strong></p><ul><li>Strona: <a href="{app_url}">{app_url}</a></li><li>Email: {user_email}</li><li>Hasło: {user_password}</li><li>Typ konta: {user_type}</li></ul><p>Prosimy o bezpieczne przechowywanie tych informacji i zmianę hasła po pierwszym logowaniu.</p><p>Z poważaniem,<br>Zespół {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Bem-vindo ao {app_name} - Conta Criada',
                        'content' => '<p>Olá <strong>{user_name}</strong>,</p><p>Sua conta foi criada com sucesso no {app_name}.</p><p><strong>Detalhes de login:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>Email: {user_email}</li><li>Senha: {user_password}</li><li>Tipo de conta: {user_type}</li></ul><p>Por favor, mantenha essas informações seguras e altere sua senha após o primeiro login.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Bem-vindo ao {app_name} - Conta Criada',
                        'content' => '<p>Olá <strong>{user_name}</strong>,</p><p>Sua conta foi criada com sucesso no {app_name}.</p><p><strong>Detalhes de login:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>Email: {user_email}</li><li>Senha: {user_password}</li><li>Tipo de conta: {user_type}</li></ul><p>Por favor, mantenha essas informações seguras e altere sua senha após o primeiro login.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Добро пожаловать в {app_name} - Аккаунт Создан',
                        'content' => '<p>Здравствуйте <strong>{user_name}</strong>,</p><p>Ваш аккаунт успешно создан в {app_name}.</p><p><strong>Данные для входа:</strong></p><ul><li>Сайт: <a href="{app_url}">{app_url}</a></li><li>Email: {user_email}</li><li>Пароль: {user_password}</li><li>Тип аккаунта: {user_type}</li></ul><p>Пожалуйста, храните эту информацию в безопасности и измените пароль после первого входа.</p><p>С уважением,<br>Команда {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => '{app_name} Hoş Geldiniz - Hesap Oluşturuldu',
                        'content' => '<p>Merhaba <strong>{user_name}</strong>,</p><p>Hesabınız {app_name} üzerinde başarıyla oluşturuldu.</p><p><strong>Giriş Bilgileri:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>Email: {user_email}</li><li>Şifre: {user_password}</li><li>Hesap Türü: {user_type}</li></ul><p>Lütfen bu bilgileri güvenli tutun ve ilk girişten sonra şifrenizi değiştirin.</p><p>Saygılarımızla,<br>{app_name} Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '欢迎来到 {app_name} - 账户已创建',
                        'content' => '<p>您好 <strong>{user_name}</strong>，</p><p>您的账户已在 {app_name} 成功创建。</p><p><strong>登录详情：</strong></p><ul><li>网站：<a href="{app_url}">{app_url}</a></li><li>邮箱：{user_email}</li><li>密码：{user_password}</li><li>账户类型：{user_type}</li></ul><p>请妥善保管这些信息，并在首次登录后更改密码。</p><p>此致，<br>{app_name} 团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'Employee Created',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Welcome to {app_name} - Employee Account Created',
                        'content' => '<p>Hello <strong>{employee_name}</strong>,</p><p>Your employee account has been successfully created on {app_name}.</p><p><strong>Login Details:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>Email: {employee_email}</li><li>Password: {employee_password}</li><li>Employee ID: {employee_id}</li><li>Department: {department_name}</li><li>Designation: {designation_name}</li><li>Joining Date: {joining_date}</li></ul><p>Please keep this information secure and change your password after first login.</p><p>Best regards,<br>{app_name} Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Bienvenido a {app_name} - Cuenta de Empleado Creada',
                        'content' => '<p>Hola <strong>{employee_name}</strong>,</p><p>Su cuenta de empleado ha sido creada exitosamente en {app_name}.</p><p><strong>Detalles de acceso:</strong></p><ul><li>Sitio web: <a href="{app_url}">{app_url}</a></li><li>Email: {employee_email}</li><li>Contraseña: {employee_password}</li><li>ID de Empleado: {employee_id}</li><li>Departamento: {department_name}</li><li>Designación: {designation_name}</li><li>Fecha de Ingreso: {joining_date}</li></ul><p>Por favor mantenga esta información segura y cambie su contraseña después del primer inicio de sesión.</p><p>Saludos cordiales,<br>Equipo de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'مرحباً بك في {app_name} - تم إنشاء حساب الموظف',
                        'content' => '<p>مرحباً <strong>{employee_name}</strong>،</p><p>تم إنشاء حساب الموظف الخاص بك بنجاح على {app_name}.</p><p><strong>تفاصيل تسجيل الدخول:</strong></p><ul><li>الموقع: <a href="{app_url}">{app_url}</a></li><li>البريد الإلكتروني: {employee_email}</li><li>كلمة المرور: {employee_password}</li><li>رقم الموظف: {employee_id}</li><li>القسم: {department_name}</li><li>المسمى الوظيفي: {designation_name}</li><li>تاريخ الانضمام: {joining_date}</li></ul><p>يرجى الحفاظ على هذه المعلومات آمنة وتغيير كلمة المرور بعد أول تسجيل دخول.</p><p>مع أطيب التحيات،<br>فريق {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Velkommen til {app_name} - Medarbejderkonto Oprettet',
                        'content' => '<p>Hej <strong>{employee_name}</strong>,</p><p>Din medarbejderkonto er blevet oprettet på {app_name}.</p><p><strong>Login detaljer:</strong></p><ul><li>Hjemmeside: <a href="{app_url}">{app_url}</a></li><li>Email: {employee_email}</li><li>Adgangskode: {employee_password}</li><li>Medarbejder ID: {employee_id}</li><li>Afdeling: {department_name}</li><li>Betegnelse: {designation_name}</li><li>Tiltrædelsesdato: {joining_date}</li></ul><p>Hold venligst disse oplysninger sikre og skift din adgangskode efter første login.</p><p>Med venlig hilsen,<br>{app_name} Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Willkommen bei {app_name} - Mitarbeiterkonto Erstellt',
                        'content' => '<p>Hallo <strong>{employee_name}</strong>,</p><p>Ihr Mitarbeiterkonto wurde erfolgreich auf {app_name} erstellt.</p><p><strong>Anmeldedaten:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>E-Mail: {employee_email}</li><li>Passwort: {employee_password}</li><li>Mitarbeiter-ID: {employee_id}</li><li>Abteilung: {department_name}</li><li>Bezeichnung: {designation_name}</li><li>Eintrittsdatum: {joining_date}</li></ul><p>Bitte bewahren Sie diese Informationen sicher auf und ändern Sie Ihr Passwort nach der ersten Anmeldung.</p><p>Mit freundlichen Grüßen,<br>{app_name} Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Bienvenue sur {app_name} - Compte Employé Créé',
                        'content' => '<p>Bonjour <strong>{employee_name}</strong>,</p><p>Votre compte employé a été créé avec succès sur {app_name}.</p><p><strong>Détails de connexion:</strong></p><ul><li>Site web: <a href="{app_url}">{app_url}</a></li><li>Email: {employee_email}</li><li>Mot de passe: {employee_password}</li><li>ID Employé: {employee_id}</li><li>Département: {department_name}</li><li>Désignation: {designation_name}</li><li>Date d\'adhésion: {joining_date}</li></ul><p>Veuillez garder ces informations en sécurité et changer votre mot de passe après la première connexion.</p><p>Cordialement,<br>Équipe {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'ברוכים הבאים ל-{app_name} - חשבון עובד נוצר',
                        'content' => '<p>שלום <strong>{employee_name}</strong>,</p><p>חשבון העובד שלך נוצר בהצלחה ב-{app_name}.</p><p><strong>פרטי התחברות:</strong></p><ul><li>אתר: <a href="{app_url}">{app_url}</a></li><li>אימייל: {employee_email}</li><li>סיסמה: {employee_password}</li><li>מזהה עובד: {employee_id}</li><li>מחלקה: {department_name}</li><li>תפקיד: {designation_name}</li><li>תאריך הצטרפות: {joining_date}</li></ul><p>אנא שמור על מידע זה מאובטח ושנה את הסיסמה שלך לאחר הכניסה הראשונה.</p><p>בברכה,<br>צוות {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Benvenuto su {app_name} - Account Dipendente Creato',
                        'content' => '<p>Ciao <strong>{employee_name}</strong>,</p><p>Il tuo account dipendente è stato creato con successo su {app_name}.</p><p><strong>Dettagli di accesso:</strong></p><ul><li>Sito web: <a href="{app_url}">{app_url}</a></li><li>Email: {employee_email}</li><li>Password: {employee_password}</li><li>ID Dipendente: {employee_id}</li><li>Dipartimento: {department_name}</li><li>Designazione: {designation_name}</li><li>Data di Assunzione: {joining_date}</li></ul><p>Si prega di mantenere queste informazioni al sicuro e cambiare la password dopo il primo accesso.</p><p>Cordiali saluti,<br>Team {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '{app_name}へようこそ - 従業員アカウント作成完了',
                        'content' => '<p>こんにちは <strong>{employee_name}</strong>様、</p><p>{app_name}で従業員アカウントが正常に作成されました。</p><p><strong>ログイン情報:</strong></p><ul><li>ウェブサイト: <a href="{app_url}">{app_url}</a></li><li>メール: {employee_email}</li><li>パスワード: {employee_password}</li><li>従業員ID: {employee_id}</li><li>部門: {department_name}</li><li>役職: {designation_name}</li><li>入社日: {joining_date}</li></ul><p>この情報を安全に保管し、初回ログイン後にパスワードを変更してください。</p><p>よろしくお願いいたします、<br>{app_name}チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Welkom bij {app_name} - Werknemersaccount Aangemaakt',
                        'content' => '<p>Hallo <strong>{employee_name}</strong>,</p><p>Uw werknemersaccount is succesvol aangemaakt op {app_name}.</p><p><strong>Inloggegevens:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>Email: {employee_email}</li><li>Wachtwoord: {employee_password}</li><li>Werknemer ID: {employee_id}</li><li>Afdeling: {department_name}</li><li>Aanduiding: {designation_name}</li><li>Indiensttreding: {joining_date}</li></ul><p>Bewaar deze informatie veilig en wijzig uw wachtwoord na de eerste login.</p><p>Met vriendelijke groet,<br>{app_name} Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Witamy w {app_name} - Konto Pracownika Utworzone',
                        'content' => '<p>Witaj <strong>{employee_name}</strong>,</p><p>Twoje konto pracownika zostało pomyślnie utworzone w {app_name}.</p><p><strong>Dane logowania:</strong></p><ul><li>Strona: <a href="{app_url}">{app_url}</a></li><li>Email: {employee_email}</li><li>Hasło: {employee_password}</li><li>ID Pracownika: {employee_id}</li><li>Dział: {department_name}</li><li>Stanowisko: {designation_name}</li><li>Data Zatrudnienia: {joining_date}</li></ul><p>Prosimy o bezpieczne przechowywanie tych informacji i zmianę hasła po pierwszym logowaniu.</p><p>Z poważaniem,<br>Zespół {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Bem-vindo ao {app_name} - Conta de Funcionário Criada',
                        'content' => '<p>Olá <strong>{employee_name}</strong>,</p><p>Sua conta de funcionário foi criada com sucesso no {app_name}.</p><p><strong>Detalhes de login:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>Email: {employee_email}</li><li>Senha: {employee_password}</li><li>ID do Funcionário: {employee_id}</li><li>Departamento: {department_name}</li><li>Designação: {designation_name}</li><li>Data de Admissão: {joining_date}</li></ul><p>Por favor, mantenha essas informações seguras e altere sua senha após o primeiro login.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Bem-vindo ao {app_name} - Conta de Funcionário Criada',
                        'content' => '<p>Olá <strong>{employee_name}</strong>,</p><p>Sua conta de funcionário foi criada com sucesso no {app_name}.</p><p><strong>Detalhes de login:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>Email: {employee_email}</li><li>Senha: {employee_password}</li><li>ID do Funcionário: {employee_id}</li><li>Departamento: {department_name}</li><li>Designação: {designation_name}</li><li>Data de Admissão: {joining_date}</li></ul><p>Por favor, mantenha essas informações seguras e altere sua senha após o primeiro login.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Добро пожаловать в {app_name} - Аккаунт Сотрудника Создан',
                        'content' => '<p>Здравствуйте <strong>{employee_name}</strong>,</p><p>Ваш аккаунт сотрудника успешно создан в {app_name}.</p><p><strong>Данные для входа:</strong></p><ul><li>Сайт: <a href="{app_url}">{app_url}</a></li><li>Email: {employee_email}</li><li>Пароль: {employee_password}</li><li>ID Сотрудника: {employee_id}</li><li>Отдел: {department_name}</li><li>Должность: {designation_name}</li><li>Дата Приема: {joining_date}</li></ul><p>Пожалуйста, храните эту информацию в безопасности и измените пароль после первого входа.</p><p>С уважением,<br>Команда {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => '{app_name} Hoş Geldiniz - Çalışan Hesabı Oluşturuldu',
                        'content' => '<p>Merhaba <strong>{employee_name}</strong>,</p><p>Çalışan hesabınız {app_name} üzerinde başarıyla oluşturuldu.</p><p><strong>Giriş Bilgileri:</strong></p><ul><li>Website: <a href="{app_url}">{app_url}</a></li><li>Email: {employee_email}</li><li>Şifre: {employee_password}</li><li>Çalışan ID: {employee_id}</li><li>Departman: {department_name}</li><li>Unvan: {designation_name}</li><li>İşe Başlama Tarihi: {joining_date}</li></ul><p>Lütfen bu bilgileri güvenli tutun ve ilk girişten sonra şifrenizi değiştirin.</p><p>Saygılarımızla,<br>{app_name} Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '欢迎来到 {app_name} - 员工账户已创建',
                        'content' => '<p>您好 <strong>{employee_name}</strong>，</p><p>您的员工账户已在 {app_name} 成功创建。</p><p><strong>登录详情：</strong></p><ul><li>网站：<a href="{app_url}">{app_url}</a></li><li>邮箱：{employee_email}</li><li>密码：{employee_password}</li><li>员工ID：{employee_id}</li><li>部门：{department_name}</li><li>职位：{designation_name}</li><li>入职日期：{joining_date}</li></ul><p>请妥善保管这些信息，并在首次登录后更改密码。</p><p>此致，<br>{app_name} 团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'New Award',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Congratulations! You Have Received an Award - {award_type}',
                        'content' => '<p>Dear <strong>{employee_name}</strong>,</p><p>Congratulations! We are pleased to inform you that you have been awarded the <strong>{award_type}</strong>.</p><p><strong>Award Details:</strong></p><ul><li>Award Type: {award_type}</li><li>Award Date: {award_date}</li><li>Description: {description}</li></ul><p>Your dedication and hard work have been recognized and appreciated. Keep up the excellent work!</p><p>Best regards,<br>{app_name} Team</p>',
                    ],
                    'es' => [
                        'subject' => '¡Felicitaciones! Has Recibido un Premio - {award_type}',
                        'content' => '<p>Estimado/a <strong>{employee_name}</strong>,</p><p>¡Felicitaciones! Nos complace informarle que ha sido galardonado con <strong>{award_type}</strong>.</p><p><strong>Detalles del Premio:</strong></p><ul><li>Tipo de Premio: {award_type}</li><li>Fecha del Premio: {award_date}</li><li>Descripción: {description}</li></ul><p>Su dedicación y arduo trabajo han sido reconocidos y apreciados. ¡Siga con el excelente trabajo!</p><p>Saludos cordiales,<br>Equipo de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'تهانينا! لقد حصلت على جائزة - {award_type}',
                        'content' => '<p>عزيزي/عزيزتي <strong>{employee_name}</strong>،</p><p>تهانينا! يسعدنا إبلاغك بأنك حصلت على <strong>{award_type}</strong>.</p><p><strong>تفاصيل الجائزة:</strong></p><ul><li>نوع الجائزة: {award_type}</li><li>تاريخ الجائزة: {award_date}</li><li>الوصف: {description}</li></ul><p>لقد تم الاعتراف بتفانيك وعملك الجاد وتقديره. استمر في العمل الممتاز!</p><p>مع أطيب التحيات،<br>فريق {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Tillykke! Du Har Modtaget en Pris - {award_type}',
                        'content' => '<p>Kære <strong>{employee_name}</strong>,</p><p>Tillykke! Vi er glade for at informere dig om, at du er blevet tildelt <strong>{award_type}</strong>.</p><p><strong>Prisdetaljer:</strong></p><ul><li>Pristype: {award_type}</li><li>Prisdato: {award_date}</li><li>Beskrivelse: {description}</li></ul><p>Din dedikation og hårde arbejde er blevet anerkendt og værdsat. Fortsæt det fremragende arbejde!</p><p>Med venlig hilsen,<br>{app_name} Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Herzlichen Glückwunsch! Sie Haben eine Auszeichnung Erhalten - {award_type}',
                        'content' => '<p>Liebe/r <strong>{employee_name}</strong>,</p><p>Herzlichen Glückwunsch! Wir freuen uns, Ihnen mitteilen zu können, dass Sie mit <strong>{award_type}</strong> ausgezeichnet wurden.</p><p><strong>Auszeichnungsdetails:</strong></p><ul><li>Auszeichnungstyp: {award_type}</li><li>Auszeichnungsdatum: {award_date}</li><li>Beschreibung: {description}</li></ul><p>Ihr Engagement und Ihre harte Arbeit wurden anerkannt und geschätzt. Machen Sie weiter so!</p><p>Mit freundlichen Grüßen,<br>{app_name} Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Félicitations! Vous Avez Reçu un Prix - {award_type}',
                        'content' => '<p>Cher/Chère <strong>{employee_name}</strong>,</p><p>Félicitations! Nous sommes heureux de vous informer que vous avez reçu le prix <strong>{award_type}</strong>.</p><p><strong>Détails du Prix:</strong></p><ul><li>Type de Prix: {award_type}</li><li>Date du Prix: {award_date}</li><li>Description: {description}</li></ul><p>Votre dévouement et votre travail acharné ont été reconnus et appréciés. Continuez votre excellent travail!</p><p>Cordialement,<br>Équipe {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'מזל טוב! קיבלת פרס - {award_type}',
                        'content' => '<p>יקר/ה <strong>{employee_name}</strong>,</p><p>מזל טוב! אנו שמחים להודיע לך שקיבלת את <strong>{award_type}</strong>.</p><p><strong>פרטי הפרס:</strong></p><ul><li>סוג הפרס: {award_type}</li><li>תאריך הפרס: {award_date}</li><li>תיאור: {description}</li></ul><p>המסירות והעבודה הקשה שלך הוכרו והוערכו. המשך בעבודה המצוינת!</p><p>בברכה,<br>צוות {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Congratulazioni! Hai Ricevuto un Premio - {award_type}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Congratulazioni! Siamo lieti di informarti che hai ricevuto il premio <strong>{award_type}</strong>.</p><p><strong>Dettagli del Premio:</strong></p><ul><li>Tipo di Premio: {award_type}</li><li>Data del Premio: {award_date}</li><li>Descrizione: {description}</li></ul><p>La tua dedizione e il tuo duro lavoro sono stati riconosciuti e apprezzati. Continua così!</p><p>Cordiali saluti,<br>Team {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => 'おめでとうございます！賞を受賞されました - {award_type}',
                        'content' => '<p><strong>{employee_name}</strong>様、</p><p>おめでとうございます！<strong>{award_type}</strong>を受賞されたことをお知らせいたします。</p><p><strong>賞の詳細：</strong></p><ul><li>賞のタイプ: {award_type}</li><li>授賞日: {award_date}</li><li>説明: {description}</li></ul><p>あなたの献身と努力が認められ、評価されました。素晴らしい仕事を続けてください！</p><p>よろしくお願いいたします、<br>{app_name}チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Gefeliciteerd! Je Hebt een Prijs Ontvangen - {award_type}',
                        'content' => '<p>Beste <strong>{employee_name}</strong>,</p><p>Gefeliciteerd! We zijn verheugd je te informeren dat je de <strong>{award_type}</strong> hebt ontvangen.</p><p><strong>Prijsdetails:</strong></p><ul><li>Prijstype: {award_type}</li><li>Prijsdatum: {award_date}</li><li>Beschrijving: {description}</li></ul><p>Je toewijding en harde werk zijn erkend en gewaardeerd. Ga zo door!</p><p>Met vriendelijke groet,<br>{app_name} Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Gratulacje! Otrzymałeś Nagrodę - {award_type}',
                        'content' => '<p>Drogi/a <strong>{employee_name}</strong>,</p><p>Gratulacje! Z przyjemnością informujemy, że otrzymałeś nagrodę <strong>{award_type}</strong>.</p><p><strong>Szczegóły Nagrody:</strong></p><ul><li>Typ Nagrody: {award_type}</li><li>Data Nagrody: {award_date}</li><li>Opis: {description}</li></ul><p>Twoje zaangażowanie i ciężka praca zostały docenione. Tak trzymaj!</p><p>Z poważaniem,<br>Zespół {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Parabéns! Você Recebeu um Prêmio - {award_type}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Parabéns! Temos o prazer de informar que você recebeu o prêmio <strong>{award_type}</strong>.</p><p><strong>Detalhes do Prêmio:</strong></p><ul><li>Tipo de Prêmio: {award_type}</li><li>Data do Prêmio: {award_date}</li><li>Descrição: {description}</li></ul><p>Sua dedicação e trabalho árduo foram reconhecidos e apreciados. Continue com o excelente trabalho!</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Parabéns! Você Recebeu um Prêmio - {award_type}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Parabéns! Temos o prazer de informar que você recebeu o prêmio <strong>{award_type}</strong>.</p><p><strong>Detalhes do Prêmio:</strong></p><ul><li>Tipo de Prêmio: {award_type}</li><li>Data do Prêmio: {award_date}</li><li>Descrição: {description}</li></ul><p>Sua dedicação e trabalho árduo foram reconhecidos e apreciados. Continue com o excelente trabalho!</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Поздравляем! Вы Получили Награду - {award_type}',
                        'content' => '<p>Уважаемый/ая <strong>{employee_name}</strong>,</p><p>Поздравляем! Мы рады сообщить вам, что вы получили награду <strong>{award_type}</strong>.</p><p><strong>Детали Награды:</strong></p><ul><li>Тип Награды: {award_type}</li><li>Дата Награды: {award_date}</li><li>Описание: {description}</li></ul><p>Ваша преданность и упорный труд были признаны и оценены. Продолжайте в том же духе!</p><p>С уважением,<br>Команда {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'Tebrikler! Bir Ödül Aldınız - {award_type}',
                        'content' => '<p>Sayın <strong>{employee_name}</strong>,</p><p>Tebrikler! <strong>{award_type}</strong> ödülünü aldığınızı bildirmekten mutluluk duyuyoruz.</p><p><strong>Ödül Detayları:</strong></p><ul><li>Ödül Türü: {award_type}</li><li>Ödül Tarihi: {award_date}</li><li>Açıklama: {description}</li></ul><p>Özveriniz ve sıkı çalışmanız takdir edildi. Mükemmel çalışmalarınıza devam edin!</p><p>Saygılarımızla,<br>{app_name} Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '恭喜！您获得了奖项 - {award_type}',
                        'content' => '<p>尊敬的 <strong>{employee_name}</strong>，</p><p>恭喜！我们很高兴地通知您，您已获得 <strong>{award_type}</strong> 奖项。</p><p><strong>奖项详情：</strong></p><ul><li>奖项类型：{award_type}</li><li>颁奖日期：{award_date}</li><li>描述：{description}</li></ul><p>您的奉献和辛勤工作得到了认可和赞赏。继续保持出色的工作！</p><p>此致，<br>{app_name} 团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'Employee Promotion',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Congratulations on Your Promotion - {designation_name}',
                        'content' => '<p>Dear <strong>{employee_name}</strong>,</p><p>We are delighted to inform you that you have been promoted to the position of <strong>{designation_name}</strong>.</p><p><strong>Promotion Details:</strong></p><ul><li>Previous Designation: {previous_designation}</li><li>New Designation: {designation_name}</li><li>Promotion Date: {promotion_date}</li><li>Effective Date: {effective_date}</li><li>Reason: {reason}</li></ul><p>This promotion is a recognition of your hard work, dedication, and outstanding contributions to the organization. We are confident that you will excel in your new role.</p><p>Congratulations once again!</p><p>Best regards,<br>{app_name} Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Felicitaciones por su Promoción - {designation_name}',
                        'content' => '<p>Estimado/a <strong>{employee_name}</strong>,</p><p>Nos complace informarle que ha sido promovido/a al puesto de <strong>{designation_name}</strong>.</p><p><strong>Detalles de la Promoción:</strong></p><ul><li>Designación Anterior: {previous_designation}</li><li>Nueva Designación: {designation_name}</li><li>Fecha de Promoción: {promotion_date}</li><li>Fecha Efectiva: {effective_date}</li><li>Motivo: {reason}</li></ul><p>Esta promoción es un reconocimiento a su arduo trabajo, dedicación y contribuciones sobresalientes a la organización. Estamos seguros de que sobresaldrá en su nuevo rol.</p><p>¡Felicitaciones una vez más!</p><p>Saludos cordiales,<br>Equipo de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'تهانينا على ترقيتك - {designation_name}',
                        'content' => '<p>عزيزي/عزيزتي <strong>{employee_name}</strong>،</p><p>يسعدنا إبلاغك بأنك تمت ترقيتك إلى منصب <strong>{designation_name}</strong>.</p><p><strong>تفاصيل الترقية:</strong></p><ul><li>المسمى الوظيفي السابق: {previous_designation}</li><li>المسمى الوظيفي الجديد: {designation_name}</li><li>تاريخ الترقية: {promotion_date}</li><li>تاريخ السريان: {effective_date}</li><li>السبب: {reason}</li></ul><p>هذه الترقية هي اعتراف بعملك الجاد وتفانيك ومساهماتك المتميزة في المنظمة. نحن واثقون من أنك ستتفوق في دورك الجديد.</p><p>تهانينا مرة أخرى!</p><p>مع أطيب التحيات،<br>فريق {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Tillykke med Din Forfremmelse - {designation_name}',
                        'content' => '<p>Kære <strong>{employee_name}</strong>,</p><p>Vi er glade for at informere dig om, at du er blevet forfremmet til stillingen som <strong>{designation_name}</strong>.</p><p><strong>Forfremmelsesdetaljer:</strong></p><ul><li>Tidligere Betegnelse: {previous_designation}</li><li>Ny Betegnelse: {designation_name}</li><li>Forfremmelsesdato: {promotion_date}</li><li>Ikrafttrædelsesdato: {effective_date}</li><li>Årsag: {reason}</li></ul><p>Denne forfremmelse er en anerkendelse af dit hårde arbejde, dedikation og fremragende bidrag til organisationen. Vi er sikre på, at du vil udmærke dig i din nye rolle.</p><p>Tillykke endnu en gang!</p><p>Med venlig hilsen,<br>{app_name} Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Herzlichen Glückwunsch zu Ihrer Beförderung - {designation_name}',
                        'content' => '<p>Liebe/r <strong>{employee_name}</strong>,</p><p>Wir freuen uns, Ihnen mitteilen zu können, dass Sie zur Position <strong>{designation_name}</strong> befördert wurden.</p><p><strong>Beförderungsdetails:</strong></p><ul><li>Vorherige Bezeichnung: {previous_designation}</li><li>Neue Bezeichnung: {designation_name}</li><li>Beförderungsdatum: {promotion_date}</li><li>Gültigkeitsdatum: {effective_date}</li><li>Grund: {reason}</li></ul><p>Diese Beförderung ist eine Anerkennung Ihrer harten Arbeit, Ihres Engagements und Ihrer herausragenden Beiträge zur Organisation. Wir sind zuversichtlich, dass Sie in Ihrer neuen Rolle hervorragende Leistungen erbringen werden.</p><p>Nochmals herzlichen Glückwunsch!</p><p>Mit freundlichen Grüßen,<br>{app_name} Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Félicitations pour Votre Promotion - {designation_name}',
                        'content' => '<p>Cher/Chère <strong>{employee_name}</strong>,</p><p>Nous sommes ravis de vous informer que vous avez été promu(e) au poste de <strong>{designation_name}</strong>.</p><p><strong>Détails de la Promotion:</strong></p><ul><li>Désignation Précédente: {previous_designation}</li><li>Nouvelle Désignation: {designation_name}</li><li>Date de Promotion: {promotion_date}</li><li>Date d\'Effet: {effective_date}</li><li>Raison: {reason}</li></ul><p>Cette promotion est une reconnaissance de votre travail acharné, de votre dévouement et de vos contributions exceptionnelles à l\'organisation. Nous sommes convaincus que vous excellerez dans votre nouveau rôle.</p><p>Félicitations encore une fois!</p><p>Cordialement,<br>Équipe {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'מזל טוב על הקידום שלך - {designation_name}',
                        'content' => '<p>יקר/ה <strong>{employee_name}</strong>,</p><p>אנו שמחים להודיע לך שקודמת לתפקיד <strong>{designation_name}</strong>.</p><p><strong>פרטי הקידום:</strong></p><ul><li>תפקיד קודם: {previous_designation}</li><li>תפקיד חדש: {designation_name}</li><li>תאריך קידום: {promotion_date}</li><li>תאריך תוקף: {effective_date}</li><li>סיבה: {reason}</li></ul><p>קידום זה הוא הכרה בעבודה הקשה שלך, במסירות ובתרומות המצוינות שלך לארגון. אנו בטוחים שתצטיין בתפקידך החדש.</p><p>מזל טוב שוב!</p><p>בברכה,<br>צוות {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Congratulazioni per la Tua Promozione - {designation_name}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Siamo lieti di informarti che sei stato/a promosso/a alla posizione di <strong>{designation_name}</strong>.</p><p><strong>Dettagli della Promozione:</strong></p><ul><li>Designazione Precedente: {previous_designation}</li><li>Nuova Designazione: {designation_name}</li><li>Data di Promozione: {promotion_date}</li><li>Data Effettiva: {effective_date}</li><li>Motivo: {reason}</li></ul><p>Questa promozione è un riconoscimento del tuo duro lavoro, dedizione e contributi eccezionali all\'organizzazione. Siamo sicuri che eccellerai nel tuo nuovo ruolo.</p><p>Congratulazioni ancora!</p><p>Cordiali saluti,<br>Team {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '昇進おめでとうございます - {designation_name}',
                        'content' => '<p><strong>{employee_name}</strong>様、</p><p><strong>{designation_name}</strong>の役職に昇進されたことをお知らせいたします。</p><p><strong>昇進の詳細：</strong></p><ul><li>以前の役職: {previous_designation}</li><li>新しい役職: {designation_name}</li><li>昇進日: {promotion_date}</li><li>発効日: {effective_date}</li><li>理由: {reason}</li></ul><p>この昇進は、あなたの勤勉さ、献身、そして組織への優れた貢献の認識です。新しい役割で優れた成果を上げられることを確信しています。</p><p>改めておめでとうございます！</p><p>よろしくお願いいたします、<br>{app_name}チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Gefeliciteerd met Je Promotie - {designation_name}',
                        'content' => '<p>Beste <strong>{employee_name}</strong>,</p><p>We zijn verheugd je te informeren dat je bent gepromoveerd tot de functie van <strong>{designation_name}</strong>.</p><p><strong>Promotiedetails:</strong></p><ul><li>Vorige Aanduiding: {previous_designation}</li><li>Nieuwe Aanduiding: {designation_name}</li><li>Promotiedatum: {promotion_date}</li><li>Ingangsdatum: {effective_date}</li><li>Reden: {reason}</li></ul><p>Deze promotie is een erkenning van je harde werk, toewijding en uitstekende bijdragen aan de organisatie. We zijn ervan overtuigd dat je zult uitblinken in je nieuwe rol.</p><p>Nogmaals gefeliciteerd!</p><p>Met vriendelijke groet,<br>{app_name} Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Gratulacje z Okazji Awansu - {designation_name}',
                        'content' => '<p>Drogi/a <strong>{employee_name}</strong>,</p><p>Z przyjemnością informujemy, że zostałeś/aś awansowany/a na stanowisko <strong>{designation_name}</strong>.</p><p><strong>Szczegóły Awansu:</strong></p><ul><li>Poprzednie Stanowisko: {previous_designation}</li><li>Nowe Stanowisko: {designation_name}</li><li>Data Awansu: {promotion_date}</li><li>Data Obowiązywania: {effective_date}</li><li>Powód: {reason}</li></ul><p>Ten awans jest uznaniem Twojej ciężkiej pracy, zaangażowania i wybitnych wkładów w organizację. Jesteśmy przekonani, że będziesz się wyróżniać w swojej nowej roli.</p><p>Gratulacje jeszcze raz!</p><p>Z poważaniem,<br>Zespół {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Parabéns pela Sua Promoção - {designation_name}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Temos o prazer de informar que você foi promovido/a para o cargo de <strong>{designation_name}</strong>.</p><p><strong>Detalhes da Promoção:</strong></p><ul><li>Designação Anterior: {previous_designation}</li><li>Nova Designação: {designation_name}</li><li>Data da Promoção: {promotion_date}</li><li>Data Efetiva: {effective_date}</li><li>Motivo: {reason}</li></ul><p>Esta promoção é um reconhecimento do seu trabalho árduo, dedicação e contribuições excepcionais para a organização. Estamos confiantes de que você se destacará em seu novo papel.</p><p>Parabéns mais uma vez!</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Parabéns pela Sua Promoção - {designation_name}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Temos o prazer de informar que você foi promovido/a para o cargo de <strong>{designation_name}</strong>.</p><p><strong>Detalhes da Promoção:</strong></p><ul><li>Designação Anterior: {previous_designation}</li><li>Nova Designação: {designation_name}</li><li>Data da Promoção: {promotion_date}</li><li>Data Efetiva: {effective_date}</li><li>Motivo: {reason}</li></ul><p>Esta promoção é um reconhecimento do seu trabalho árduo, dedicação e contribuições excepcionais para a organização. Estamos confiantes de que você se destacará em seu novo papel.</p><p>Parabéns mais uma vez!</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Поздравляем с Повышением - {designation_name}',
                        'content' => '<p>Уважаемый/ая <strong>{employee_name}</strong>,</p><p>Мы рады сообщить вам, что вы были повышены до должности <strong>{designation_name}</strong>.</p><p><strong>Детали Повышения:</strong></p><ul><li>Предыдущая Должность: {previous_designation}</li><li>Новая Должность: {designation_name}</li><li>Дата Повышения: {promotion_date}</li><li>Дата Вступления в Силу: {effective_date}</li><li>Причина: {reason}</li></ul><p>Это повышение является признанием вашего упорного труда, преданности и выдающегося вклада в организацию. Мы уверены, что вы преуспеете в своей новой роли.</p><p>Поздравляем еще раз!</p><p>С уважением,<br>Команда {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'Terfi Ettiğiniz İçin Tebrikler - {designation_name}',
                        'content' => '<p>Sayın <strong>{employee_name}</strong>,</p><p><strong>{designation_name}</strong> pozisyonuna terfi ettiğinizi bildirmekten mutluluk duyuyoruz.</p><p><strong>Terfi Detayları:</strong></p><ul><li>Önceki Unvan: {previous_designation}</li><li>Yeni Unvan: {designation_name}</li><li>Terfi Tarihi: {promotion_date}</li><li>Geçerlilik Tarihi: {effective_date}</li><li>Sebep: {reason}</li></ul><p>Bu terfi, sıkı çalışmanızın, özverinizin ve organizasyona olağanüstü katkılarınızın bir takdiridir. Yeni rolünüzde başarılı olacağınızdan eminiz.</p><p>Bir kez daha tebrikler!</p><p>Saygılarımızla,<br>{app_name} Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '恭喜您获得晋升 - {designation_name}',
                        'content' => '<p>尊敬的 <strong>{employee_name}</strong>，</p><p>我们很高兴地通知您，您已被晋升为 <strong>{designation_name}</strong> 职位。</p><p><strong>晋升详情：</strong></p><ul><li>以前职位：{previous_designation}</li><li>新职位：{designation_name}</li><li>晋升日期：{promotion_date}</li><li>生效日期：{effective_date}</li><li>原因：{reason}</li></ul><p>此次晋升是对您辛勤工作、奉献精神和对组织杰出贡献的认可。我们相信您将在新角色中表现出色。</p><p>再次祝贺！</p><p>此致，<br>{app_name} 团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'Employee Resignation',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Resignation Letter - {employee_name}',
                        'content' => '<p>Dear Sir/Madam,</p><p>I am writing to formally notify you of my resignation from my position at {app_name}, effective <strong>{resignation_date}</strong>.</p><p><strong>Resignation Details:</strong></p><ul><li>Resignation Date: {resignation_date}</li><li>Reason: {reason}</li></ul><p>I would like to thank you for the opportunities and experiences I have gained during my time with the organization. I am committed to ensuring a smooth transition of my responsibilities.</p><p>Thank you for your understanding.</p><p>Sincerely,<br><strong>{employee_name}</strong></p>',
                    ],
                    'es' => [
                        'subject' => 'Carta de Renuncia - {employee_name}',
                        'content' => '<p>Estimado Señor/Señora,</p><p>Le escribo para notificarle formalmente mi renuncia a mi puesto en {app_name}, efectiva el <strong>{resignation_date}</strong>.</p><p><strong>Detalles de la Renuncia:</strong></p><ul><li>Fecha de Renuncia: {resignation_date}</li><li>Motivo: {reason}</li></ul><p>Me gustaría agradecerle por las oportunidades y experiencias que he obtenido durante mi tiempo en la organización. Estoy comprometido a garantizar una transición fluida de mis responsabilidades.</p><p>Gracias por su comprensión.</p><p>Atentamente,<br><strong>{employee_name}</strong></p>',
                    ],
                    'ar' => [
                        'subject' => 'خطاب استقالة - {employee_name}',
                        'content' => '<p>عزيزي السيد/السيدة،</p><p>أكتب إليكم لإبلاغكم رسميًا باستقالتي من منصبي في {app_name}، اعتبارًا من <strong>{resignation_date}</strong>.</p><p><strong>تفاصيل الاستقالة:</strong></p><ul><li>تاريخ الاستقالة: {resignation_date}</li><li>السبب: {reason}</li></ul><p>أود أن أشكركم على الفرص والخبرات التي اكتسبتها خلال فترة عملي في المنظمة. أنا ملتزم بضمان انتقال سلس لمسؤولياتي.</p><p>شكرًا لتفهمكم.</p><p>مع التقدير،<br><strong>{employee_name}</strong></p>',
                    ],
                    'da' => [
                        'subject' => 'Opsigelse - {employee_name}',
                        'content' => '<p>Kære Hr./Fru,</p><p>Jeg skriver for formelt at meddele dig om min opsigelse fra min stilling hos {app_name}, gældende fra <strong>{resignation_date}</strong>.</p><p><strong>Opsigelsesdetaljer:</strong></p><ul><li>Opsigelsesdato: {resignation_date}</li><li>Årsag: {reason}</li></ul><p>Jeg vil gerne takke dig for de muligheder og erfaringer, jeg har fået i min tid i organisationen. Jeg er forpligtet til at sikre en problemfri overgang af mine ansvarsområder.</p><p>Tak for din forståelse.</p><p>Med venlig hilsen,<br><strong>{employee_name}</strong></p>',
                    ],
                    'de' => [
                        'subject' => 'Kündigungsschreiben - {employee_name}',
                        'content' => '<p>Sehr geehrte Damen und Herren,</p><p>Ich schreibe Ihnen, um Sie formell über meine Kündigung meiner Position bei {app_name} zu informieren, wirksam ab <strong>{resignation_date}</strong>.</p><p><strong>Kündigungsdetails:</strong></p><ul><li>Kündigungsdatum: {resignation_date}</li><li>Grund: {reason}</li></ul><p>Ich möchte mich für die Möglichkeiten und Erfahrungen bedanken, die ich während meiner Zeit in der Organisation gesammelt habe. Ich bin bestrebt, einen reibungslosen Übergang meiner Verantwortlichkeiten zu gewährleisten.</p><p>Vielen Dank für Ihr Verständnis.</p><p>Mit freundlichen Grüßen,<br><strong>{employee_name}</strong></p>',
                    ],
                    'fr' => [
                        'subject' => 'Lettre de Démission - {employee_name}',
                        'content' => '<p>Madame, Monsieur,</p><p>Je vous écris pour vous informer formellement de ma démission de mon poste chez {app_name}, effective le <strong>{resignation_date}</strong>.</p><p><strong>Détails de la Démission:</strong></p><ul><li>Date de Démission: {resignation_date}</li><li>Raison: {reason}</li></ul><p>Je tiens à vous remercier pour les opportunités et les expériences que j\'ai acquises pendant mon temps au sein de l\'organisation. Je m\'engage à assurer une transition en douceur de mes responsabilités.</p><p>Merci de votre compréhension.</p><p>Cordialement,<br><strong>{employee_name}</strong></p>',
                    ],
                    'he' => [
                        'subject' => 'מכתב התפטרות - {employee_name}',
                        'content' => '<p>אדון/גברת נכבדים,</p><p>אני כותב כדי להודיע לכם רשמית על התפטרותי מתפקידי ב-{app_name}, בתוקף מ-<strong>{resignation_date}</strong>.</p><p><strong>פרטי ההתפטרות:</strong></p><ul><li>תאריך התפטרות: {resignation_date}</li><li>סיבה: {reason}</li></ul><p>אני רוצה להודות לכם על ההזדמנויות והחוויות שצברתי במהלך תקופתי בארגון. אני מחויב להבטיח מעבר חלק של האחריות שלי.</p><p>תודה על ההבנה.</p><p>בכבוד רב,<br><strong>{employee_name}</strong></p>',
                    ],
                    'it' => [
                        'subject' => 'Lettera di Dimissioni - {employee_name}',
                        'content' => '<p>Gentile Signore/Signora,</p><p>Le scrivo per notificarLe formalmente le mie dimissioni dalla mia posizione presso {app_name}, con effetto dal <strong>{resignation_date}</strong>.</p><p><strong>Dettagli delle Dimissioni:</strong></p><ul><li>Data di Dimissioni: {resignation_date}</li><li>Motivo: {reason}</li></ul><p>Vorrei ringraziarLa per le opportunità e le esperienze che ho acquisito durante il mio tempo nell\'organizzazione. Mi impegno a garantire una transizione fluida delle mie responsabilità.</p><p>Grazie per la comprensione.</p><p>Cordiali saluti,<br><strong>{employee_name}</strong></p>',
                    ],
                    'ja' => [
                        'subject' => '退職届 - {employee_name}',
                        'content' => '<p>拝啓</p><p>{app_name}での私の職を<strong>{resignation_date}</strong>付けで辞職することを正式にお知らせいたします。</p><p><strong>退職の詳細：</strong></p><ul><li>退職日: {resignation_date}</li><li>理由: {reason}</li></ul><p>組織での期間中に得た機会と経験に感謝いたします。私の責任のスムーズな引き継ぎを確実にすることをお約束します。</p><p>ご理解いただきありがとうございます。</p><p>敬具<br><strong>{employee_name}</strong></p>',
                    ],
                    'nl' => [
                        'subject' => 'Ontslagbrief - {employee_name}',
                        'content' => '<p>Geachte heer/mevrouw,</p><p>Ik schrijf u om u formeel op de hoogte te stellen van mijn ontslag uit mijn functie bij {app_name}, met ingang van <strong>{resignation_date}</strong>.</p><p><strong>Ontslagdetails:</strong></p><ul><li>Ontslagdatum: {resignation_date}</li><li>Reden: {reason}</li></ul><p>Ik wil u bedanken voor de kansen en ervaringen die ik heb opgedaan tijdens mijn tijd bij de organisatie. Ik ben toegewijd om een soepele overgang van mijn verantwoordelijkheden te waarborgen.</p><p>Dank u voor uw begrip.</p><p>Met vriendelijke groet,<br><strong>{employee_name}</strong></p>',
                    ],
                    'pl' => [
                        'subject' => 'List Rezygnacyjny - {employee_name}',
                        'content' => '<p>Szanowni Państwo,</p><p>Piszę, aby formalnie powiadomić Państwa o mojej rezygnacji z mojego stanowiska w {app_name}, ze skutkiem od <strong>{resignation_date}</strong>.</p><p><strong>Szczegóły Rezygnacji:</strong></p><ul><li>Data Rezygnacji: {resignation_date}</li><li>Powód: {reason}</li></ul><p>Chciałbym podziękować za możliwości i doświadczenia, które zdobyłem podczas mojego czasu w organizacji. Jestem zaangażowany w zapewnienie płynnego przejścia moich obowiązków.</p><p>Dziękuję za zrozumienie.</p><p>Z poważaniem,<br><strong>{employee_name}</strong></p>',
                    ],
                    'pt' => [
                        'subject' => 'Carta de Demissão - {employee_name}',
                        'content' => '<p>Prezado(a) Senhor(a),</p><p>Escrevo para notificá-lo formalmente da minha demissão do meu cargo na {app_name}, com efeito a partir de <strong>{resignation_date}</strong>.</p><p><strong>Detalhes da Demissão:</strong></p><ul><li>Data de Demissão: {resignation_date}</li><li>Motivo: {reason}</li></ul><p>Gostaria de agradecer pelas oportunidades e experiências que obtive durante meu tempo na organização. Estou comprometido em garantir uma transição suave das minhas responsabilidades.</p><p>Obrigado pela compreensão.</p><p>Atenciosamente,<br><strong>{employee_name}</strong></p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Carta de Demissão - {employee_name}',
                        'content' => '<p>Prezado(a) Senhor(a),</p><p>Escrevo para notificá-lo formalmente da minha demissão do meu cargo na {app_name}, com efeito a partir de <strong>{resignation_date}</strong>.</p><p><strong>Detalhes da Demissão:</strong></p><ul><li>Data de Demissão: {resignation_date}</li><li>Motivo: {reason}</li></ul><p>Gostaria de agradecer pelas oportunidades e experiências que obtive durante meu tempo na organização. Estou comprometido em garantir uma transição suave das minhas responsabilidades.</p><p>Obrigado pela compreensão.</p><p>Atenciosamente,<br><strong>{employee_name}</strong></p>',
                    ],
                    'ru' => [
                        'subject' => 'Заявление об Увольнении - {employee_name}',
                        'content' => '<p>Уважаемый господин/госпожа,</p><p>Пишу, чтобы официально уведомить вас о моем увольнении с должности в {app_name}, вступающем в силу с <strong>{resignation_date}</strong>.</p><p><strong>Детали Увольнения:</strong></p><ul><li>Дата Увольнения: {resignation_date}</li><li>Причина: {reason}</li></ul><p>Я хотел бы поблагодарить вас за возможности и опыт, которые я получил за время работы в организации. Я обязуюсь обеспечить плавную передачу моих обязанностей.</p><p>Спасибо за понимание.</p><p>С уважением,<br><strong>{employee_name}</strong></p>',
                    ],
                    'tr' => [
                        'subject' => 'İstifa Mektubu - {employee_name}',
                        'content' => '<p>Sayın Yetkili,</p><p>{app_name} bünyesindeki pozisyonumdan <strong>{resignation_date}</strong> tarihinden itibaren geçerli olmak üzere istifa ettiğimi resmi olarak bildirmek için yazıyorum.</p><p><strong>İstifa Detayları:</strong></p><ul><li>İstifa Tarihi: {resignation_date}</li><li>Sebep: {reason}</li></ul><p>Organizasyonda geçirdiğim süre boyunca elde ettiğim fırsatlar ve deneyimler için teşekkür etmek isterim. Sorumluluklarımın sorunsuz bir şekilde devredilmesini sağlamaya kararlıyım.</p><p>Anlayışınız için teşekkür ederim.</p><p>Saygılarımla,<br><strong>{employee_name}</strong></p>',
                    ],
                    'zh' => [
                        'subject' => '辞职信 - {employee_name}',
                        'content' => '<p>尊敬的先生/女士，</p><p>我写信正式通知您，我将从<strong>{resignation_date}</strong>起辞去在{app_name}的职位。</p><p><strong>辞职详情：</strong></p><ul><li>辞职日期：{resignation_date}</li><li>原因：{reason}</li></ul><p>我要感谢在组织工作期间获得的机会和经验。我承诺确保我的职责顺利交接。</p><p>感谢您的理解。</p><p>此致敬礼，<br><strong>{employee_name}</strong></p>',
                    ],
                ],
            ],
            [
                'name' => 'Employee Termination',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Employment Termination Notice - {employee_name}',
                        'content' => '<p>Dear <strong>{employee_name}</strong>,</p><p>We regret to inform you that your employment with {app_name} will be terminated effective <strong>{termination_date}</strong>.</p><p><strong>Termination Details:</strong></p><ul><li>Termination Type: {termination_type}</li><li>Termination Date: {termination_date}</li><li>Notice Date: {notice_date}</li><li>Reason: {reason}</li></ul><p>Please contact the HR department for further information regarding final settlement and exit procedures.</p><p>Best regards,<br>{app_name} Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Aviso de Terminación de Empleo - {employee_name}',
                        'content' => '<p>Estimado/a <strong>{employee_name}</strong>,</p><p>Lamentamos informarle que su empleo con {app_name} será terminado con efecto <strong>{termination_date}</strong>.</p><p><strong>Detalles de la Terminación:</strong></p><ul><li>Tipo de Terminación: {termination_type}</li><li>Fecha de Terminación: {termination_date}</li><li>Fecha de Aviso: {notice_date}</li><li>Motivo: {reason}</li></ul><p>Por favor, contacte al departamento de RRHH para obtener más información sobre la liquidación final y los procedimientos de salida.</p><p>Saludos cordiales,<br>Equipo de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'إشعار إنهاء الخدمة - {employee_name}',
                        'content' => '<p>عزيزي/عزيزتي <strong>{employee_name}</strong>،</p><p>يؤسفنا إبلاغك بأن خدمتك مع {app_name} سيتم إنهاؤها اعتبارًا من <strong>{termination_date}</strong>.</p><p><strong>تفاصيل الإنهاء:</strong></p><ul><li>نوع الإنهاء: {termination_type}</li><li>تاريخ الإنهاء: {termination_date}</li><li>تاريخ الإشعار: {notice_date}</li><li>السبب: {reason}</li></ul><p>يرجى الاتصال بقسم الموارد البشرية للحصول على مزيد من المعلومات بخصوص التسوية النهائية وإجراءات الخروج.</p><p>مع أطيب التحيات،<br>فريق {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Meddelelse om Ophør af Ansættelse - {employee_name}',
                        'content' => '<p>Kære <strong>{employee_name}</strong>,</p><p>Vi beklager at informere dig om, at dit ansættelsesforhold med {app_name} vil blive ophørt med virkning fra <strong>{termination_date}</strong>.</p><p><strong>Ophørsdetaljer:</strong></p><ul><li>Ophørstype: {termination_type}</li><li>Ophørsdato: {termination_date}</li><li>Meddelelsesdato: {notice_date}</li><li>Årsag: {reason}</li></ul><p>Kontakt venligst HR-afdelingen for yderligere information om endelig afregning og udtrædelsesprocedurer.</p><p>Med venlig hilsen,<br>{app_name} Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Mitteilung über Beendigung des Arbeitsverhältnisses - {employee_name}',
                        'content' => '<p>Liebe/r <strong>{employee_name}</strong>,</p><p>Wir bedauern, Ihnen mitteilen zu müssen, dass Ihr Arbeitsverhältnis mit {app_name} mit Wirkung zum <strong>{termination_date}</strong> beendet wird.</p><p><strong>Beendigungsdetails:</strong></p><ul><li>Beendigungsart: {termination_type}</li><li>Beendigungsdatum: {termination_date}</li><li>Mitteilungsdatum: {notice_date}</li><li>Grund: {reason}</li></ul><p>Bitte wenden Sie sich an die Personalabteilung für weitere Informationen zur Endabrechnung und zu den Austrittsverfahren.</p><p>Mit freundlichen Grüßen,<br>{app_name} Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Avis de Fin de Contrat - {employee_name}',
                        'content' => '<p>Cher/Chère <strong>{employee_name}</strong>,</p><p>Nous regrettons de vous informer que votre emploi avec {app_name} prendra fin à compter du <strong>{termination_date}</strong>.</p><p><strong>Détails de la Fin de Contrat:</strong></p><ul><li>Type de Fin de Contrat: {termination_type}</li><li>Date de Fin de Contrat: {termination_date}</li><li>Date de Notification: {notice_date}</li><li>Raison: {reason}</li></ul><p>Veuillez contacter le département RH pour plus d\'informations concernant le règlement final et les procédures de sortie.</p><p>Cordialement,<br>Équipe {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'הודעת סיום עבודה - {employee_name}',
                        'content' => '<p>יקר/ה <strong>{employee_name}</strong>,</p><p>אנו מצטערים להודיע לך שהעסקתך עם {app_name} תסתיים בתוקף מ-<strong>{termination_date}</strong>.</p><p><strong>פרטי סיום העבודה:</strong></p><ul><li>סוג סיום: {termination_type}</li><li>תאריך סיום: {termination_date}</li><li>תאריך הודעה: {notice_date}</li><li>סיבה: {reason}</li></ul><p>אנא צור קשר עם מחלקת המשאבי אנוש למידע נוסף לגבי הסדר סופי ונהלי יציאה.</p><p>בברכה,<br>צוות {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Avviso di Cessazione del Rapporto di Lavoro - {employee_name}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Siamo spiacenti di informarti che il tuo rapporto di lavoro con {app_name} sarà cessato con effetto dal <strong>{termination_date}</strong>.</p><p><strong>Dettagli della Cessazione:</strong></p><ul><li>Tipo di Cessazione: {termination_type}</li><li>Data di Cessazione: {termination_date}</li><li>Data di Notifica: {notice_date}</li><li>Motivo: {reason}</li></ul><p>Si prega di contattare il dipartimento HR per ulteriori informazioni riguardo alla liquidazione finale e alle procedure di uscita.</p><p>Cordiali saluti,<br>Team {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '雇用終了通知 - {employee_name}',
                        'content' => '<p><strong>{employee_name}</strong>様、</p><p>討しくも、<strong>{termination_date}</strong>付けで{app_name}との雇用関係が終了することをお知らせいたします。</p><p><strong>終了の詳細：</strong></p><ul><li>終了タイプ: {termination_type}</li><li>終了日: {termination_date}</li><li>通知日: {notice_date}</li><li>理由: {reason}</li></ul><p>最終精算および退職手続きに関する詳細については、HR部門にお問い合わせください。</p><p>よろしくお願いいたします、<br>{app_name}チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Kennisgeving van Beëindiging Dienstverband - {employee_name}',
                        'content' => '<p>Beste <strong>{employee_name}</strong>,</p><p>Het spijt ons je te moeten informeren dat je dienstverband met {app_name} met ingang van <strong>{termination_date}</strong> wordt beëindigd.</p><p><strong>Beëindigingsdetails:</strong></p><ul><li>Type Beëindiging: {termination_type}</li><li>Beëindigingsdatum: {termination_date}</li><li>Kennisgevingsdatum: {notice_date}</li><li>Reden: {reason}</li></ul><p>Neem contact op met de HR-afdeling voor meer informatie over de eindafrekening en vertrekprocedures.</p><p>Met vriendelijke groet,<br>{app_name} Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Zawiadomienie o Rozwiazaniu Umowy o Pracę - {employee_name}',
                        'content' => '<p>Drogi/a <strong>{employee_name}</strong>,</p><p>Z przykrością informujemy, że Twoje zatrudnienie w {app_name} zostanie rozwiązane z dniem <strong>{termination_date}</strong>.</p><p><strong>Szczegóły Rozwiązania:</strong></p><ul><li>Typ Rozwiązania: {termination_type}</li><li>Data Rozwiązania: {termination_date}</li><li>Data Powiadomienia: {notice_date}</li><li>Powód: {reason}</li></ul><p>Prosimy o kontakt z działem HR w celu uzyskania dalszych informacji dotyczących ostatecznego rozliczenia i procedur wyjścia.</p><p>Z poważaniem,<br>Zespół {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Aviso de Rescisão de Contrato - {employee_name}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Lamentamos informá-lo de que seu emprego com {app_name} será rescindido com efeito a partir de <strong>{termination_date}</strong>.</p><p><strong>Detalhes da Rescisão:</strong></p><ul><li>Tipo de Rescisão: {termination_type}</li><li>Data de Rescisão: {termination_date}</li><li>Data de Aviso: {notice_date}</li><li>Motivo: {reason}</li></ul><p>Por favor, entre em contato com o departamento de RH para mais informações sobre acerto final e procedimentos de saída.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Aviso de Rescisão de Contrato - {employee_name}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Lamentamos informá-lo de que seu emprego com {app_name} será rescindido com efeito a partir de <strong>{termination_date}</strong>.</p><p><strong>Detalhes da Rescisão:</strong></p><ul><li>Tipo de Rescisão: {termination_type}</li><li>Data de Rescisão: {termination_date}</li><li>Data de Aviso: {notice_date}</li><li>Motivo: {reason}</li></ul><p>Por favor, entre em contato com o departamento de RH para mais informações sobre acerto final e procedimentos de saída.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Уведомление о Расторжении Трудового Договора - {employee_name}',
                        'content' => '<p>Уважаемый/ая <strong>{employee_name}</strong>,</p><p>С сожалением сообщаем вам, что ваше трудоустройство в {app_name} будет прекращено с <strong>{termination_date}</strong>.</p><p><strong>Детали Расторжения:</strong></p><ul><li>Тип Расторжения: {termination_type}</li><li>Дата Расторжения: {termination_date}</li><li>Дата Уведомления: {notice_date}</li><li>Причина: {reason}</li></ul><p>Пожалуйста, свяжитесь с отделом кадров для получения дополнительной информации о окончательном расчете и процедурах увольнения.</p><p>С уважением,<br>Команда {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'İş Sonu Bildirimi - {employee_name}',
                        'content' => '<p>Sayın <strong>{employee_name}</strong>,</p><p>Üzgünlükle bildiririz ki, {app_name} ile olan iş sözleşmeniz <strong>{termination_date}</strong> tarihinden itibaren sona erecektir.</p><p><strong>İş Sonu Detayları:</strong></p><ul><li>İş Sonu Türü: {termination_type}</li><li>İş Sonu Tarihi: {termination_date}</li><li>Bildirim Tarihi: {notice_date}</li><li>Sebep: {reason}</li></ul><p>Lütfen nihai ödeme ve çıkış prosedürleri hakkında daha fazla bilgi için İK departmanı ile iletişime geçin.</p><p>Saygılarımızla,<br>{app_name} Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '雇用终止通知 - {employee_name}',
                        'content' => '<p>尊敬的 <strong>{employee_name}</strong>，</p><p>我们遗憾地通知您，您与{app_name}的雇用关系将从<strong>{termination_date}</strong>起终止。</p><p><strong>终止详情：</strong></p><ul><li>终止类型：{termination_type}</li><li>终止日期：{termination_date}</li><li>通知日期：{notice_date}</li><li>原因：{reason}</li></ul><p>请联系HR部门了解有关最终结算和离职程序的进一步信息。</p><p>此致，<br>{app_name} 团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'Employee Warning',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Official Warning Notice - {subject}',
                        'content' => '<p>Dear <strong>{employee_name}</strong>,</p><p>This letter serves as an official <strong>{severity}</strong> warning regarding <strong>{subject}</strong>.</p><p><strong>Warning Details:</strong></p><ul><li>Warning Type: {warning_type}</li><li>Severity: {severity}</li><li>Warning Date: {warning_date}</li><li>Subject: {subject}</li><li>Description: {description}</li></ul><p>This warning is being issued due to concerns about your conduct/performance. We expect immediate improvement in this area.</p><p>Please acknowledge receipt of this warning and contact HR if you have any questions.</p><p>Best regards,<br>{app_name} Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Aviso de Advertencia Oficial - {subject}',
                        'content' => '<p>Estimado/a <strong>{employee_name}</strong>,</p><p>Esta carta sirve como una advertencia oficial de <strong>{severity}</strong> con respecto a <strong>{subject}</strong>.</p><p><strong>Detalles de la Advertencia:</strong></p><ul><li>Tipo de Advertencia: {warning_type}</li><li>Severidad: {severity}</li><li>Fecha de Advertencia: {warning_date}</li><li>Asunto: {subject}</li><li>Descripción: {description}</li></ul><p>Esta advertencia se emite debido a preocupaciones sobre su conducta/desempeño. Esperamos una mejora inmediata en esta área.</p><p>Por favor, confirme la recepción de esta advertencia y contacte a RRHH si tiene alguna pregunta.</p><p>Saludos cordiales,<br>Equipo de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'إشعار تحذير رسمي - {subject}',
                        'content' => '<p>عزيزي/عزيزتي <strong>{employee_name}</strong>،</p><p>تعتبر هذه الرسالة تحذيرًا رسميًا من مستوى <strong>{severity}</strong> بخصوص <strong>{subject}</strong>.</p><p><strong>تفاصيل التحذير:</strong></p><ul><li>نوع التحذير: {warning_type}</li><li>الخطورة: {severity}</li><li>تاريخ التحذير: {warning_date}</li><li>الموضوع: {subject}</li><li>الوصف: {description}</li></ul><p>يتم إصدار هذا التحذير بسبب مخاوف بشأن سلوكك/أدائك. نتوقع تحسنًا فوريًا في هذا المجال.</p><p>يرجى تأكيد استلام هذا التحذير والاتصال بالموارد البشرية إذا كان لديك أي أسئلة.</p><p>مع أطيب التحيات،<br>فريق {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Officiel Advarsel - {subject}',
                        'content' => '<p>Kære <strong>{employee_name}</strong>,</p><p>Dette brev tjener som en officiel <strong>{severity}</strong> advarsel vedrørende <strong>{subject}</strong>.</p><p><strong>Advarselsdetaljer:</strong></p><ul><li>Advarselstype: {warning_type}</li><li>Alvorlighed: {severity}</li><li>Advarselsdato: {warning_date}</li><li>Emne: {subject}</li><li>Beskrivelse: {description}</li></ul><p>Denne advarsel udstedes på grund af bekymringer om din adfærd/præstation. Vi forventer øjeblikkelig forbedring på dette område.</p><p>Bekræft venligst modtagelsen af denne advarsel og kontakt HR, hvis du har spørgsmål.</p><p>Med venlig hilsen,<br>{app_name} Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Offizielle Verwarnung - {subject}',
                        'content' => '<p>Liebe/r <strong>{employee_name}</strong>,</p><p>Dieses Schreiben dient als offizielle <strong>{severity}</strong> Verwarnung bezüglich <strong>{subject}</strong>.</p><p><strong>Verwarnungsdetails:</strong></p><ul><li>Verwarnungstyp: {warning_type}</li><li>Schweregrad: {severity}</li><li>Verwarnungsdatum: {warning_date}</li><li>Betreff: {subject}</li><li>Beschreibung: {description}</li></ul><p>Diese Verwarnung wird aufgrund von Bedenken bezüglich Ihres Verhaltens/Ihrer Leistung ausgesprochen. Wir erwarten eine sofortige Verbesserung in diesem Bereich.</p><p>Bitte bestätigen Sie den Erhalt dieser Verwarnung und wenden Sie sich bei Fragen an die Personalabteilung.</p><p>Mit freundlichen Grüßen,<br>{app_name} Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Avertissement Officiel - {subject}',
                        'content' => '<p>Cher/Chère <strong>{employee_name}</strong>,</p><p>Cette lettre constitue un avertissement officiel de niveau <strong>{severity}</strong> concernant <strong>{subject}</strong>.</p><p><strong>Détails de l\'Avertissement:</strong></p><ul><li>Type d\'Avertissement: {warning_type}</li><li>Gravité: {severity}</li><li>Date d\'Avertissement: {warning_date}</li><li>Sujet: {subject}</li><li>Description: {description}</li></ul><p>Cet avertissement est émis en raison de préoccupations concernant votre conduite/performance. Nous attendons une amélioration immédiate dans ce domaine.</p><p>Veuillez accuser réception de cet avertissement et contacter les RH si vous avez des questions.</p><p>Cordialement,<br>Équipe {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'הודעת אזהרה רשמית - {subject}',
                        'content' => '<p>יקר/ה <strong>{employee_name}</strong>,</p><p>מכתב זה משמש כאזהרה רשמית ברמת <strong>{severity}</strong> בנוגע ל-<strong>{subject}</strong>.</p><p><strong>פרטי האזהרה:</strong></p><ul><li>סוג אזהרה: {warning_type}</li><li>חומרה: {severity}</li><li>תאריך אזהרה: {warning_date}</li><li>נושא: {subject}</li><li>תיאור: {description}</li></ul><p>אזהרה זו מונפקת עקב חששות לגבי התנהגותך/ביצועיך. אנו מצפים לשיפור מיידי בתחום זה.</p><p>אנא אשר קבלת אזהרה זו וצור קשר עם משאבי אנוש אם יש לך שאלות.</p><p>בברכה,<br>צוות {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Avviso di Ammonimento Ufficiale - {subject}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Questa lettera costituisce un ammonimento ufficiale di livello <strong>{severity}</strong> riguardante <strong>{subject}</strong>.</p><p><strong>Dettagli dell\'Ammonimento:</strong></p><ul><li>Tipo di Ammonimento: {warning_type}</li><li>Gravità: {severity}</li><li>Data Ammonimento: {warning_date}</li><li>Oggetto: {subject}</li><li>Descrizione: {description}</li></ul><p>Questo ammonimento viene emesso a causa di preoccupazioni riguardo alla tua condotta/prestazione. Ci aspettiamo un miglioramento immediato in quest\'area.</p><p>Si prega di confermare la ricezione di questo ammonimento e contattare l\'HR per eventuali domande.</p><p>Cordiali saluti,<br>Team {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '正式警告通知 - {subject}',
                        'content' => '<p><strong>{employee_name}</strong>様、</p><p>この書簡は、<strong>{subject}</strong>に関する<strong>{severity}</strong>レベルの正式な警告として機能します。</p><p><strong>警告の詳細：</strong></p><ul><li>警告タイプ: {warning_type}</li><li>重大度: {severity}</li><li>警告日: {warning_date}</li><li>件名: {subject}</li><li>説明: {description}</li></ul><p>この警告は、あなたの行動/パフォーマンスに関する懸念により発行されています。この分野での即座の改善を期待しています。</p><p>この警告の受領を確認し、質問がある場合はHRにお問い合わせください。</p><p>よろしくお願いいたします、<br>{app_name}チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Officiële Waarschuwing - {subject}',
                        'content' => '<p>Beste <strong>{employee_name}</strong>,</p><p>Deze brief dient als een officiële <strong>{severity}</strong> waarschuwing met betrekking tot <strong>{subject}</strong>.</p><p><strong>Waarschuwingsdetails:</strong></p><ul><li>Waarschuwingstype: {warning_type}</li><li>Ernst: {severity}</li><li>Waarschuwingsdatum: {warning_date}</li><li>Onderwerp: {subject}</li><li>Beschrijving: {description}</li></ul><p>Deze waarschuwing wordt afgegeven vanwege zorgen over je gedrag/prestaties. We verwachten onmiddellijke verbetering op dit gebied.</p><p>Bevestig de ontvangst van deze waarschuwing en neem contact op met HR als je vragen hebt.</p><p>Met vriendelijke groet,<br>{app_name} Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Oficjalne Ostrzeżenie - {subject}',
                        'content' => '<p>Drogi/a <strong>{employee_name}</strong>,</p><p>Ten list stanowi oficjalne ostrzeżenie na poziomie <strong>{severity}</strong> dotyczące <strong>{subject}</strong>.</p><p><strong>Szczegóły Ostrzeżenia:</strong></p><ul><li>Typ Ostrzeżenia: {warning_type}</li><li>Powaga: {severity}</li><li>Data Ostrzeżenia: {warning_date}</li><li>Temat: {subject}</li><li>Opis: {description}</li></ul><p>To ostrzeżenie jest wydawane z powodu obaw dotyczących Twojego zachowania/wydajności. Oczekujemy natychmiastowej poprawy w tym obszarze.</p><p>Prosimy o potwierdzenie odbioru tego ostrzeżenia i skontaktowanie się z HR w przypadku pytań.</p><p>Z poważaniem,<br>Zespół {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Aviso de Advertência Oficial - {subject}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Esta carta serve como uma advertência oficial de nível <strong>{severity}</strong> referente a <strong>{subject}</strong>.</p><p><strong>Detalhes da Advertência:</strong></p><ul><li>Tipo de Advertência: {warning_type}</li><li>Severidade: {severity}</li><li>Data da Advertência: {warning_date}</li><li>Assunto: {subject}</li><li>Descrição: {description}</li></ul><p>Esta advertência está sendo emitida devido a preocupações sobre sua conduta/desempenho. Esperamos melhoria imediata nesta área.</p><p>Por favor, confirme o recebimento desta advertência e entre em contato com o RH se tiver alguma dúvida.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Aviso de Advertência Oficial - {subject}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Esta carta serve como uma advertência oficial de nível <strong>{severity}</strong> referente a <strong>{subject}</strong>.</p><p><strong>Detalhes da Advertência:</strong></p><ul><li>Tipo de Advertência: {warning_type}</li><li>Severidade: {severity}</li><li>Data da Advertência: {warning_date}</li><li>Assunto: {subject}</li><li>Descrição: {description}</li></ul><p>Esta advertência está sendo emitida devido a preocupações sobre sua conduta/desempenho. Esperamos melhoria imediata nesta área.</p><p>Por favor, confirme o recebimento desta advertência e entre em contato com o RH se tiver alguma dúvida.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Официальное Предупреждение - {subject}',
                        'content' => '<p>Уважаемый/ая <strong>{employee_name}</strong>,</p><p>Это письмо служит официальным предупреждением уровня <strong>{severity}</strong> относительно <strong>{subject}</strong>.</p><p><strong>Детали Предупреждения:</strong></p><ul><li>Тип Предупреждения: {warning_type}</li><li>Серьезность: {severity}</li><li>Дата Предупреждения: {warning_date}</li><li>Тема: {subject}</li><li>Описание: {description}</li></ul><p>Это предупреждение выдается в связи с озабоченностью по поводу вашего поведения/производительности. Мы ожидаем немедленного улучшения в этой области.</p><p>Пожалуйста, подтвердите получение этого предупреждения и свяжитесь с отделом кадров, если у вас есть вопросы.</p><p>С уважением,<br>Команда {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'Resmi Uyarı Bildirimi - {subject}',
                        'content' => '<p>Sayın <strong>{employee_name}</strong>,</p><p>Bu mektup, <strong>{subject}</strong> ile ilgili <strong>{severity}</strong> seviyesinde resmi bir uyarı olarak hizmet etmektedir.</p><p><strong>Uyarı Detayları:</strong></p><ul><li>Uyarı Türü: {warning_type}</li><li>Ciddiyet: {severity}</li><li>Uyarı Tarihi: {warning_date}</li><li>Konu: {subject}</li><li>Açıklama: {description}</li></ul><p>Bu uyarı, davranışınız/performansınız hakkındaki endişeler nedeniyle verilmektedir. Bu alanda acil iyileşme bekliyoruz.</p><p>Lütfen bu uyarının alındığını onaylayın ve sorularınız varsa İK ile iletişime geçin.</p><p>Saygılarımızla,<br>{app_name} Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '正式警告通知 - {subject}',
                        'content' => '<p>尊敬的 <strong>{employee_name}</strong>，</p><p>此信函作为关于<strong>{subject}</strong>的<strong>{severity}</strong>级别正式警告。</p><p><strong>警告详情：</strong></p><ul><li>警告类型：{warning_type}</li><li>严重程度：{severity}</li><li>警告日期：{warning_date}</li><li>主题：{subject}</li><li>描述：{description}</li></ul><p>由于对您的行为/表现存在担忧，特此发出此警告。我们期望在这方面立即改进。</p><p>请确认收到此警告，如有任何问题，请联系HR部门。</p><p>此致，<br>{app_name} 团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'Employee Trip',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Business Trip Assignment - {destination}',
                        'content' => '<p>Dear <strong>{employee_name}</strong>,</p><p>You have been assigned a business trip to <strong>{destination}</strong>.</p><p><strong>Trip Details:</strong></p><ul><li>Purpose: {purpose}</li><li>Destination: {destination}</li><li>Start Date: {start_date}</li><li>End Date: {end_date}</li><li>Description: {description}</li></ul><p>Please make necessary arrangements and contact HR for travel logistics and advance payment if required.</p><p>Best regards,<br>{app_name} Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Asignación de Viaje de Negocios - {destination}',
                        'content' => '<p>Estimado/a <strong>{employee_name}</strong>,</p><p>Se le ha asignado un viaje de negocios a <strong>{destination}</strong>.</p><p><strong>Detalles del Viaje:</strong></p><ul><li>Propósito: {purpose}</li><li>Destino: {destination}</li><li>Fecha de Inicio: {start_date}</li><li>Fecha de Fin: {end_date}</li><li>Descripción: {description}</li></ul><p>Por favor, realice los arreglos necesarios y contacte a RRHH para la logística de viaje y pago anticipado si es necesario.</p><p>Saludos cordiales,<br>Equipo de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'تكليف برحلة عمل - {destination}',
                        'content' => '<p>عزيزي/عزيزتي <strong>{employee_name}</strong>،</p><p>تم تكليفك برحلة عمل إلى <strong>{destination}</strong>.</p><p><strong>تفاصيل الرحلة:</strong></p><ul><li>الغرض: {purpose}</li><li>الوجهة: {destination}</li><li>تاريخ البدء: {start_date}</li><li>تاريخ الانتهاء: {end_date}</li><li>الوصف: {description}</li></ul><p>يرجى إجراء الترتيبات اللازمة والاتصال بالموارد البشرية للحصول على لوجستيات السفر والدفع المسبق إذا لزم الأمر.</p><p>مع أطيب التحيات،<br>فريق {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Forretningsrejse Tildeling - {destination}',
                        'content' => '<p>Kære <strong>{employee_name}</strong>,</p><p>Du er blevet tildelt en forretningsrejse til <strong>{destination}</strong>.</p><p><strong>Rejsedetaljer:</strong></p><ul><li>Formål: {purpose}</li><li>Destination: {destination}</li><li>Startdato: {start_date}</li><li>Slutdato: {end_date}</li><li>Beskrivelse: {description}</li></ul><p>Foretag venligst de nødvendige arrangementer og kontakt HR for rejselogistik og forskudsbetaling, hvis det er nødvendigt.</p><p>Med venlig hilsen,<br>{app_name} Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Geschäftsreise Zuweisung - {destination}',
                        'content' => '<p>Liebe/r <strong>{employee_name}</strong>,</p><p>Ihnen wurde eine Geschäftsreise nach <strong>{destination}</strong> zugewiesen.</p><p><strong>Reisedetails:</strong></p><ul><li>Zweck: {purpose}</li><li>Ziel: {destination}</li><li>Startdatum: {start_date}</li><li>Enddatum: {end_date}</li><li>Beschreibung: {description}</li></ul><p>Bitte treffen Sie die notwendigen Vorkehrungen und wenden Sie sich an die Personalabteilung für Reiselogistik und Vorauszahlung, falls erforderlich.</p><p>Mit freundlichen Grüßen,<br>{app_name} Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Affectation de Voyage d\'Affaires - {destination}',
                        'content' => '<p>Cher/Chère <strong>{employee_name}</strong>,</p><p>Vous avez été affecté(e) à un voyage d\'affaires à <strong>{destination}</strong>.</p><p><strong>Détails du Voyage:</strong></p><ul><li>Objectif: {purpose}</li><li>Destination: {destination}</li><li>Date de Début: {start_date}</li><li>Date de Fin: {end_date}</li><li>Description: {description}</li></ul><p>Veuillez prendre les dispositions nécessaires et contacter les RH pour la logistique de voyage et le paiement anticipé si nécessaire.</p><p>Cordialement,<br>Équipe {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'הקצאת נסיעת עסקים - {destination}',
                        'content' => '<p>יקר/ה <strong>{employee_name}</strong>,</p><p>הוקצתה לך נסיעת עסקים ל-<strong>{destination}</strong>.</p><p><strong>פרטי הנסיעה:</strong></p><ul><li>מטרה: {purpose}</li><li>יעד: {destination}</li><li>תאריך התחלה: {start_date}</li><li>תאריך סיום: {end_date}</li><li>תיאור: {description}</li></ul><p>אנא בצע את ההסדרים הדרושים וצור קשר עם משאבי אנוש ללוגיסטיקת נסיעות ותשלום מקדמה במידת הצורך.</p><p>בברכה,<br>צוות {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Assegnazione Viaggio di Lavoro - {destination}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Ti è stato assegnato un viaggio di lavoro a <strong>{destination}</strong>.</p><p><strong>Dettagli del Viaggio:</strong></p><ul><li>Scopo: {purpose}</li><li>Destinazione: {destination}</li><li>Data di Inizio: {start_date}</li><li>Data di Fine: {end_date}</li><li>Descrizione: {description}</li></ul><p>Si prega di effettuare le disposizioni necessarie e contattare l\'HR per la logistica di viaggio e il pagamento anticipato se necessario.</p><p>Cordiali saluti,<br>Team {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '出張割り当て - {destination}',
                        'content' => '<p><strong>{employee_name}</strong>様、</p><p><strong>{destination}</strong>への出張が割り当てられました。</p><p><strong>出張の詳細：</strong></p><ul><li>目的: {purpose}</li><li>目的地: {destination}</li><li>開始日: {start_date}</li><li>終了日: {end_date}</li><li>説明: {description}</li></ul><p>必要な手配を行い、旅行の手配や前払いが必要な場合はHRにお問い合わせください。</p><p>よろしくお願いいたします、<br>{app_name}チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Zakelijke Reis Toewijzing - {destination}',
                        'content' => '<p>Beste <strong>{employee_name}</strong>,</p><p>Je bent toegewezen aan een zakelijke reis naar <strong>{destination}</strong>.</p><p><strong>Reisdetails:</strong></p><ul><li>Doel: {purpose}</li><li>Bestemming: {destination}</li><li>Startdatum: {start_date}</li><li>Einddatum: {end_date}</li><li>Beschrijving: {description}</li></ul><p>Maak de nodige regelingen en neem contact op met HR voor reislogistiek en vooruitbetaling indien nodig.</p><p>Met vriendelijke groet,<br>{app_name} Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Przydzielenie Podróży Służbowej - {destination}',
                        'content' => '<p>Drogi/a <strong>{employee_name}</strong>,</p><p>Zostałeś/aś przydzielony/a do podróży służbowej do <strong>{destination}</strong>.</p><p><strong>Szczegóły Podróży:</strong></p><ul><li>Cel: {purpose}</li><li>Miejsce Docelowe: {destination}</li><li>Data Rozpoczęcia: {start_date}</li><li>Data Zakończenia: {end_date}</li><li>Opis: {description}</li></ul><p>Prosimy o dokonanie niezbędnych ustaleń i skontaktowanie się z HR w sprawie logistyki podróży i zaliczki, jeśli jest to wymagane.</p><p>Z poważaniem,<br>Zespół {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Atribuição de Viagem de Negócios - {destination}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Você foi designado para uma viagem de negócios para <strong>{destination}</strong>.</p><p><strong>Detalhes da Viagem:</strong></p><ul><li>Propósito: {purpose}</li><li>Destino: {destination}</li><li>Data de Início: {start_date}</li><li>Data de Término: {end_date}</li><li>Descrição: {description}</li></ul><p>Por favor, faça os arranjos necessários e entre em contato com o RH para logística de viagem e pagamento antecipado, se necessário.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Atribuição de Viagem de Negócios - {destination}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Você foi designado para uma viagem de negócios para <strong>{destination}</strong>.</p><p><strong>Detalhes da Viagem:</strong></p><ul><li>Propósito: {purpose}</li><li>Destino: {destination}</li><li>Data de Início: {start_date}</li><li>Data de Término: {end_date}</li><li>Descrição: {description}</li></ul><p>Por favor, faça os arranjos necessários e entre em contato com o RH para logística de viagem e pagamento antecipado, se necessário.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Назначение Командировки - {destination}',
                        'content' => '<p>Уважаемый/ая <strong>{employee_name}</strong>,</p><p>Вам назначена командировка в <strong>{destination}</strong>.</p><p><strong>Детали Поездки:</strong></p><ul><li>Цель: {purpose}</li><li>Место Назначения: {destination}</li><li>Дата Начала: {start_date}</li><li>Дата Окончания: {end_date}</li><li>Описание: {description}</li></ul><p>Пожалуйста, сделайте необходимые приготовления и свяжитесь с отделом кадров для организации поездки и авансового платежа, если требуется.</p><p>С уважением,<br>Команда {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'İş Seyahati Ataması - {destination}',
                        'content' => '<p>Sayın <strong>{employee_name}</strong>,</p><p><strong>{destination}</strong> için bir iş seyahati atandınız.</p><p><strong>Seyahat Detayları:</strong></p><ul><li>Amaç: {purpose}</li><li>Hedef: {destination}</li><li>Başlangıç Tarihi: {start_date}</li><li>Bitiş Tarihi: {end_date}</li><li>Açıklama: {description}</li></ul><p>Lütfen gerekli düzenlemeleri yapın ve gerekirse seyahat lojistiği ve avans ödemesi için İK ile iletişime geçin.</p><p>Saygılarımızla,<br>{app_name} Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '商务出差分配 - {destination}',
                        'content' => '<p>尊敬的 <strong>{employee_name}</strong>，</p><p>您已被分配到<strong>{destination}</strong>的商务出差。</p><p><strong>出差详情：</strong></p><ul><li>目的：{purpose}</li><li>目的地：{destination}</li><li>开始日期：{start_date}</li><li>结束日期：{end_date}</li><li>描述：{description}</li></ul><p>请做好必要的安排，如需旅行后勤和预付款，请联系HR部门。</p><p>此致，<br>{app_name} 团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'Employee Complaint',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Employee Complaint Submission - {subject}',
                        'content' => '<p>Dear HR Team,</p><p>This is to formally submit a complaint regarding <strong>{subject}</strong>.</p><p><strong>Complaint Details:</strong></p><ul><li>Employee Name: {employee_name}</li><li>Complaint Type: {complaint_type}</li><li>Complaint Date: {complaint_date}</li><li>Subject: {subject}</li><li>Description: {description}</li></ul><p>I request that this matter be investigated and addressed promptly. I am available to provide any additional information if needed.</p><p>Thank you for your attention to this matter.</p><p>Sincerely,<br><strong>{employee_name}</strong></p>',
                    ],
                    'es' => [
                        'subject' => 'Presentación de Queja de Empleado - {subject}',
                        'content' => '<p>Estimado Equipo de RRHH,</p><p>Por la presente presento formalmente una queja con respecto a <strong>{subject}</strong>.</p><p><strong>Detalles de la Queja:</strong></p><ul><li>Nombre del Empleado: {employee_name}</li><li>Tipo de Queja: {complaint_type}</li><li>Fecha de Queja: {complaint_date}</li><li>Asunto: {subject}</li><li>Descripción: {description}</li></ul><p>Solicito que este asunto sea investigado y abordado con prontitud. Estoy disponible para proporcionar cualquier información adicional si es necesario.</p><p>Gracias por su atención a este asunto.</p><p>Atentamente,<br><strong>{employee_name}</strong></p>',
                    ],
                    'ar' => [
                        'subject' => 'تقديم شكوى موظف - {subject}',
                        'content' => '<p>عزيزي فريق الموارد البشرية،</p><p>أتقدم بهذا رسميًا بشكوى بخصوص <strong>{subject}</strong>.</p><p><strong>تفاصيل الشكوى:</strong></p><ul><li>اسم الموظف: {employee_name}</li><li>نوع الشكوى: {complaint_type}</li><li>تاريخ الشكوى: {complaint_date}</li><li>الموضوع: {subject}</li><li>الوصف: {description}</li></ul><p>أطلب التحقيق في هذا الأمر ومعالجته على الفور. أنا متاح لتقديم أي معلومات إضافية إذا لزم الأمر.</p><p>شكرًا لاهتمامكم بهذا الأمر.</p><p>مع خالص التقدير،<br><strong>{employee_name}</strong></p>',
                    ],
                    'da' => [
                        'subject' => 'Medarbejderklage Indsendelse - {subject}',
                        'content' => '<p>Kære HR-team,</p><p>Dette er for formelt at indsende en klage vedrørende <strong>{subject}</strong>.</p><p><strong>Klagedetaljer:</strong></p><ul><li>Medarbejdernavn: {employee_name}</li><li>Klagetype: {complaint_type}</li><li>Klagedato: {complaint_date}</li><li>Emne: {subject}</li><li>Beskrivelse: {description}</li></ul><p>Jeg anmoder om, at denne sag undersøges og behandles hurtigt. Jeg er tilgængelig for at give yderligere oplysninger, hvis det er nødvendigt.</p><p>Tak for din opmærksomhed på denne sag.</p><p>Med venlig hilsen,<br><strong>{employee_name}</strong></p>',
                    ],
                    'de' => [
                        'subject' => 'Mitarbeiterbeschwerde Einreichung - {subject}',
                        'content' => '<p>Liebes HR-Team,</p><p>Hiermit reiche ich formell eine Beschwerde bezüglich <strong>{subject}</strong> ein.</p><p><strong>Beschwerdedetails:</strong></p><ul><li>Mitarbeitername: {employee_name}</li><li>Beschwerdeart: {complaint_type}</li><li>Beschwerdedatum: {complaint_date}</li><li>Betreff: {subject}</li><li>Beschreibung: {description}</li></ul><p>Ich bitte darum, dass diese Angelegenheit umgehend untersucht und bearbeitet wird. Ich stehe zur Verfügung, um bei Bedarf zusätzliche Informationen bereitzustellen.</p><p>Vielen Dank für Ihre Aufmerksamkeit in dieser Angelegenheit.</p><p>Mit freundlichen Grüßen,<br><strong>{employee_name}</strong></p>',
                    ],
                    'fr' => [
                        'subject' => 'Soumission de Plainte d\'Employé - {subject}',
                        'content' => '<p>Cher Équipe RH,</p><p>Je soumets formellement une plainte concernant <strong>{subject}</strong>.</p><p><strong>Détails de la Plainte:</strong></p><ul><li>Nom de l\'Employé: {employee_name}</li><li>Type de Plainte: {complaint_type}</li><li>Date de Plainte: {complaint_date}</li><li>Sujet: {subject}</li><li>Description: {description}</li></ul><p>Je demande que cette affaire soit enquêtée et traitée rapidement. Je suis disponible pour fournir toute information supplémentaire si nécessaire.</p><p>Merci pour votre attention à cette affaire.</p><p>Cordialement,<br><strong>{employee_name}</strong></p>',
                    ],
                    'he' => [
                        'subject' => 'הגשת תלונת עובד - {subject}',
                        'content' => '<p>צוות משאבי אנוש יקר,</p><p>זאת להגיש רשמית תלונה בנוגע ל-<strong>{subject}</strong>.</p><p><strong>פרטי התלונה:</strong></p><ul><li>שם העובד: {employee_name}</li><li>סוג תלונה: {complaint_type}</li><li>תאריך תלונה: {complaint_date}</li><li>נושא: {subject}</li><li>תיאור: {description}</li></ul><p>אני מבקש שעניין זה ייחקר ויטופל במהירות. אני זמין לספק כל מידע נוסף במידת הצורך.</p><p>תודה על תשומת הלב לעניין זה.</p><p>בכבוד רב,<br><strong>{employee_name}</strong></p>',
                    ],
                    'it' => [
                        'subject' => 'Presentazione Reclamo Dipendente - {subject}',
                        'content' => '<p>Caro Team HR,</p><p>Con la presente presento formalmente un reclamo riguardante <strong>{subject}</strong>.</p><p><strong>Dettagli del Reclamo:</strong></p><ul><li>Nome Dipendente: {employee_name}</li><li>Tipo di Reclamo: {complaint_type}</li><li>Data Reclamo: {complaint_date}</li><li>Oggetto: {subject}</li><li>Descrizione: {description}</li></ul><p>Richiedo che questa questione venga indagata e affrontata prontamente. Sono disponibile a fornire ulteriori informazioni se necessario.</p><p>Grazie per l\'attenzione a questa questione.</p><p>Cordiali saluti,<br><strong>{employee_name}</strong></p>',
                    ],
                    'ja' => [
                        'subject' => '従業員苦情提出 - {subject}',
                        'content' => '<p>人事部御中、</p><p><strong>{subject}</strong>に関する苦情を正式に提出いたします。</p><p><strong>苦情の詳細：</strong></p><ul><li>従業員名: {employee_name}</li><li>苦情タイプ: {complaint_type}</li><li>苦情日: {complaint_date}</li><li>件名: {subject}</li><li>説明: {description}</li></ul><p>この件について速やかに調査し、対処していただくようお願いいたします。必要に応じて追加情報を提供する用意があります。</p><p>この件へのご配慮に感謝いたします。</p><p>敬具<br><strong>{employee_name}</strong></p>',
                    ],
                    'nl' => [
                        'subject' => 'Indiening Werknemersklacht - {subject}',
                        'content' => '<p>Beste HR-team,</p><p>Dit is om formeel een klacht in te dienen met betrekking tot <strong>{subject}</strong>.</p><p><strong>Klachtdetails:</strong></p><ul><li>Werknemersnaam: {employee_name}</li><li>Klachttype: {complaint_type}</li><li>Klachtdatum: {complaint_date}</li><li>Onderwerp: {subject}</li><li>Beschrijving: {description}</li></ul><p>Ik verzoek dat deze kwestie snel wordt onderzocht en aangepakt. Ik ben beschikbaar om indien nodig aanvullende informatie te verstrekken.</p><p>Dank u voor uw aandacht voor deze kwestie.</p><p>Met vriendelijke groet,<br><strong>{employee_name}</strong></p>',
                    ],
                    'pl' => [
                        'subject' => 'Złożenie Skargi Pracownika - {subject}',
                        'content' => '<p>Szanowny Zespół HR,</p><p>Niniejszym formalnie składam skargę dotyczącą <strong>{subject}</strong>.</p><p><strong>Szczegóły Skargi:</strong></p><ul><li>Imię i Nazwisko Pracownika: {employee_name}</li><li>Typ Skargi: {complaint_type}</li><li>Data Skargi: {complaint_date}</li><li>Temat: {subject}</li><li>Opis: {description}</li></ul><p>Proszę o zbadanie i niezwłoczne zajęcie się tą sprawą. Jestem dostępny, aby w razie potrzeby dostarczyć dodatkowe informacje.</p><p>Dziękuję za uwagę poświęconą tej sprawie.</p><p>Z poważaniem,<br><strong>{employee_name}</strong></p>',
                    ],
                    'pt' => [
                        'subject' => 'Submissão de Reclamação de Funcionário - {subject}',
                        'content' => '<p>Prezada Equipe de RH,</p><p>Venho formalmente apresentar uma reclamação referente a <strong>{subject}</strong>.</p><p><strong>Detalhes da Reclamação:</strong></p><ul><li>Nome do Funcionário: {employee_name}</li><li>Tipo de Reclamação: {complaint_type}</li><li>Data da Reclamação: {complaint_date}</li><li>Assunto: {subject}</li><li>Descrição: {description}</li></ul><p>Solicito que este assunto seja investigado e tratado prontamente. Estou disponível para fornecer qualquer informação adicional, se necessário.</p><p>Obrigado pela atenção a este assunto.</p><p>Atenciosamente,<br><strong>{employee_name}</strong></p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Submissão de Reclamação de Funcionário - {subject}',
                        'content' => '<p>Prezada Equipe de RH,</p><p>Venho formalmente apresentar uma reclamação referente a <strong>{subject}</strong>.</p><p><strong>Detalhes da Reclamação:</strong></p><ul><li>Nome do Funcionário: {employee_name}</li><li>Tipo de Reclamação: {complaint_type}</li><li>Data da Reclamação: {complaint_date}</li><li>Assunto: {subject}</li><li>Descrição: {description}</li></ul><p>Solicito que este assunto seja investigado e tratado prontamente. Estou disponível para fornecer qualquer informação adicional, se necessário.</p><p>Obrigado pela atenção a este assunto.</p><p>Atenciosamente,<br><strong>{employee_name}</strong></p>',
                    ],
                    'ru' => [
                        'subject' => 'Подача Жалобы Сотрудника - {subject}',
                        'content' => '<p>Уважаемая команда HR,</p><p>Настоящим официально подаю жалобу относительно <strong>{subject}</strong>.</p><p><strong>Детали Жалобы:</strong></p><ul><li>Имя Сотрудника: {employee_name}</li><li>Тип Жалобы: {complaint_type}</li><li>Дата Жалобы: {complaint_date}</li><li>Тема: {subject}</li><li>Описание: {description}</li></ul><p>Прошу расследовать и оперативно рассмотреть этот вопрос. Я готов предоставить любую дополнительную информацию при необходимости.</p><p>Благодарю за внимание к этому вопросу.</p><p>С уважением,<br><strong>{employee_name}</strong></p>',
                    ],
                    'tr' => [
                        'subject' => 'Çalışan Şikayeti Gönderimi - {subject}',
                        'content' => '<p>Sayın İK Ekibi,</p><p>Bu, <strong>{subject}</strong> ile ilgili resmi olarak bir şikayet göndermek içindir.</p><p><strong>Şikayet Detayları:</strong></p><ul><li>Çalışan Adı: {employee_name}</li><li>Şikayet Türü: {complaint_type}</li><li>Şikayet Tarihi: {complaint_date}</li><li>Konu: {subject}</li><li>Açıklama: {description}</li></ul><p>Bu konunun araştırılmasını ve derhal ele alınmasını talep ediyorum. Gerekirse ek bilgi sağlamak için hazırım.</p><p>Bu konuya gösterdiğiniz ilgi için teşekkür ederim.</p><p>Saygılarımla,<br><strong>{employee_name}</strong></p>',
                    ],
                    'zh' => [
                        'subject' => '员工投诉提交 - {subject}',
                        'content' => '<p>尊敬的HR团队，</p><p>特此正式提交关于<strong>{subject}</strong>的投诉。</p><p><strong>投诉详情：</strong></p><ul><li>员工姓名：{employee_name}</li><li>投诉类型：{complaint_type}</li><li>投诉日期：{complaint_date}</li><li>主题：{subject}</li><li>描述：{description}</li></ul><p>我请求对此事进行调查并及时处理。如有需要，我可以提供任何额外信息。</p><p>感谢您对此事的关注。</p><p>此致敬礼，<br><strong>{employee_name}</strong></p>',
                    ],
                ],
            ],
            [
                'name' => 'Employee Transfer',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Employee Transfer Notification - {employee_name}',
                        'content' => '<p>Dear <strong>{employee_name}</strong>,</p><p>We are writing to inform you that you have been transferred effective <strong>{effective_date}</strong>.</p><p><strong>Transfer Details:</strong></p><ul><li>Transfer Date: {transfer_date}</li><li>Effective Date: {effective_date}</li><li>Department: {from_department_name} → {to_department_name}</li><li>Designation: {from_designation_name} → {to_designation_name}</li><li>Reason: {reason}</li></ul><p>Please contact HR for further details regarding your transfer and any necessary arrangements.</p><p>Best regards,<br>{app_name} Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Notificación de Transferencia de Empleado - {employee_name}',
                        'content' => '<p>Estimado/a <strong>{employee_name}</strong>,</p><p>Le escribimos para informarle que ha sido transferido/a con efecto <strong>{effective_date}</strong>.</p><p><strong>Detalles de la Transferencia:</strong></p><ul><li>Fecha de Transferencia: {transfer_date}</li><li>Fecha Efectiva: {effective_date}</li><li>Departamento: {from_department_name} → {to_department_name}</li><li>Designación: {from_designation_name} → {to_designation_name}</li><li>Motivo: {reason}</li></ul><p>Por favor, contacte a RRHH para más detalles sobre su transferencia y cualquier arreglo necesario.</p><p>Saludos cordiales,<br>Equipo de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'إشعار نقل موظف - {employee_name}',
                        'content' => '<p>عزيزي/عزيزتي <strong>{employee_name}</strong>،</p><p>نكتب إليك لإبلاغك بأنه تم نقلك اعتبارًا من <strong>{effective_date}</strong>.</p><p><strong>تفاصيل النقل:</strong></p><ul><li>تاريخ النقل: {transfer_date}</li><li>تاريخ السريان: {effective_date}</li><li>القسم: {from_department_name} → {to_department_name}</li><li>المسمى الوظيفي: {from_designation_name} → {to_designation_name}</li><li>السبب: {reason}</li></ul><p>يرجى الاتصال بالموارد البشرية لمزيد من التفاصيل حول نقلك وأي ترتيبات ضرورية.</p><p>مع أطيب التحيات،<br>فريق {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Medarbejderoverførsel Meddelelse - {employee_name}',
                        'content' => '<p>Kære <strong>{employee_name}</strong>,</p><p>Vi skriver for at informere dig om, at du er blevet overført med virkning fra <strong>{effective_date}</strong>.</p><p><strong>Overførselsdetaljer:</strong></p><ul><li>Overførselsdato: {transfer_date}</li><li>Ikrafttrædelsesdato: {effective_date}</li><li>Afdeling: {from_department_name} → {to_department_name}</li><li>Betegnelse: {from_designation_name} → {to_designation_name}</li><li>Årsag: {reason}</li></ul><p>Kontakt venligst HR for yderligere detaljer om din overførsel og eventuelle nødvendige arrangementer.</p><p>Med venlig hilsen,<br>{app_name} Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Mitarbeiterversetzung Benachrichtigung - {employee_name}',
                        'content' => '<p>Liebe/r <strong>{employee_name}</strong>,</p><p>Wir schreiben Ihnen, um Sie darüber zu informieren, dass Sie mit Wirkung zum <strong>{effective_date}</strong> versetzt wurden.</p><p><strong>Versetzungsdetails:</strong></p><ul><li>Versetzungsdatum: {transfer_date}</li><li>Gültigkeitsdatum: {effective_date}</li><li>Abteilung: {from_department_name} → {to_department_name}</li><li>Bezeichnung: {from_designation_name} → {to_designation_name}</li><li>Grund: {reason}</li></ul><p>Bitte wenden Sie sich an die Personalabteilung für weitere Details zu Ihrer Versetzung und notwendigen Vorkehrungen.</p><p>Mit freundlichen Grüßen,<br>{app_name} Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Notification de Transfert d\'Employé - {employee_name}',
                        'content' => '<p>Cher/Chère <strong>{employee_name}</strong>,</p><p>Nous vous écrivons pour vous informer que vous avez été transféré(e) à compter du <strong>{effective_date}</strong>.</p><p><strong>Détails du Transfert:</strong></p><ul><li>Date de Transfert: {transfer_date}</li><li>Date d\'Effet: {effective_date}</li><li>Département: {from_department_name} → {to_department_name}</li><li>Désignation: {from_designation_name} → {to_designation_name}</li><li>Raison: {reason}</li></ul><p>Veuillez contacter les RH pour plus de détails concernant votre transfert et les arrangements nécessaires.</p><p>Cordialement,<br>Équipe {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'הודעת העברת עובד - {employee_name}',
                        'content' => '<p>יקר/ה <strong>{employee_name}</strong>,</p><p>אנו כותבים להודיע לך שהועברת בתוקף מ-<strong>{effective_date}</strong>.</p><p><strong>פרטי ההעברה:</strong></p><ul><li>תאריך העברה: {transfer_date}</li><li>תאריך תוקף: {effective_date}</li><li>מחלקה: {from_department_name} → {to_department_name}</li><li>תפקיד: {from_designation_name} → {to_designation_name}</li><li>סיבה: {reason}</li></ul><p>אנא צור קשר עם משאבי אנוש לפרטים נוספים לגבי ההעברה שלך וכל הסדר נדרש.</p><p>בברכה,<br>צוות {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Notifica di Trasferimento Dipendente - {employee_name}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Ti scriviamo per informarti che sei stato/a trasferito/a con effetto dal <strong>{effective_date}</strong>.</p><p><strong>Dettagli del Trasferimento:</strong></p><ul><li>Data di Trasferimento: {transfer_date}</li><li>Data Effettiva: {effective_date}</li><li>Dipartimento: {from_department_name} → {to_department_name}</li><li>Designazione: {from_designation_name} → {to_designation_name}</li><li>Motivo: {reason}</li></ul><p>Si prega di contattare l\'HR per ulteriori dettagli riguardo al tuo trasferimento e qualsiasi disposizione necessaria.</p><p>Cordiali saluti,<br>Team {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '従業員異動通知 - {employee_name}',
                        'content' => '<p><strong>{employee_name}</strong>様、</p><p><strong>{effective_date}</strong>付けで異動となりましたことをお知らせいたします。</p><p><strong>異動の詳細：</strong></p><ul><li>異動日: {transfer_date}</li><li>発効日: {effective_date}</li><li>部門: {from_department_name} → {to_department_name}</li><li>役職: {from_designation_name} → {to_designation_name}</li><li>理由: {reason}</li></ul><p>異動に関する詳細および必要な手配については、人事部にお問い合わせください。</p><p>よろしくお願いいたします、<br>{app_name}チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Werknemersoverplaatsing Kennisgeving - {employee_name}',
                        'content' => '<p>Beste <strong>{employee_name}</strong>,</p><p>We schrijven je om te informeren dat je met ingang van <strong>{effective_date}</strong> bent overgeplaatst.</p><p><strong>Overplaatsingsdetails:</strong></p><ul><li>Overplaatsingsdatum: {transfer_date}</li><li>Ingangsdatum: {effective_date}</li><li>Afdeling: {from_department_name} → {to_department_name}</li><li>Aanduiding: {from_designation_name} → {to_designation_name}</li><li>Reden: {reason}</li></ul><p>Neem contact op met HR voor meer details over je overplaatsing en eventuele noodzakelijke regelingen.</p><p>Met vriendelijke groet,<br>{app_name} Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Powiadomienie o Przeniesieniu Pracownika - {employee_name}',
                        'content' => '<p>Drogi/a <strong>{employee_name}</strong>,</p><p>Piszemy, aby poinformować Cię, że zostałeś/aś przeniesiony/a z dniem <strong>{effective_date}</strong>.</p><p><strong>Szczegóły Przeniesienia:</strong></p><ul><li>Data Przeniesienia: {transfer_date}</li><li>Data Obowiązywania: {effective_date}</li><li>Dział: {from_department_name} → {to_department_name}</li><li>Stanowisko: {from_designation_name} → {to_designation_name}</li><li>Powód: {reason}</li></ul><p>Prosimy o kontakt z HR w celu uzyskania dalszych szczegółów dotyczących Twojego przeniesienia i wszelkich niezbędnych ustaleń.</p><p>Z poważaniem,<br>Zespół {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Notificação de Transferência de Funcionário - {employee_name}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Escrevemos para informá-lo de que você foi transferido com efeito a partir de <strong>{effective_date}</strong>.</p><p><strong>Detalhes da Transferência:</strong></p><ul><li>Data de Transferência: {transfer_date}</li><li>Data Efetiva: {effective_date}</li><li>Departamento: {from_department_name} → {to_department_name}</li><li>Designação: {from_designation_name} → {to_designation_name}</li><li>Motivo: {reason}</li></ul><p>Por favor, entre em contato com o RH para mais detalhes sobre sua transferência e quaisquer arranjos necessários.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Notificação de Transferência de Funcionário - {employee_name}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Escrevemos para informá-lo de que você foi transferido com efeito a partir de <strong>{effective_date}</strong>.</p><p><strong>Detalhes da Transferência:</strong></p><ul><li>Data de Transferência: {transfer_date}</li><li>Data Efetiva: {effective_date}</li><li>Departamento: {from_department_name} → {to_department_name}</li><li>Designação: {from_designation_name} → {to_designation_name}</li><li>Motivo: {reason}</li></ul><p>Por favor, entre em contato com o RH para mais detalhes sobre sua transferência e quaisquer arranjos necessários.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Уведомление о Переводе Сотрудника - {employee_name}',
                        'content' => '<p>Уважаемый/ая <strong>{employee_name}</strong>,</p><p>Мы пишем, чтобы сообщить вам, что вы были переведены с <strong>{effective_date}</strong>.</p><p><strong>Детали Перевода:</strong></p><ul><li>Дата Перевода: {transfer_date}</li><li>Дата Вступления в Силу: {effective_date}</li><li>Отдел: {from_department_name} → {to_department_name}</li><li>Должность: {from_designation_name} → {to_designation_name}</li><li>Причина: {reason}</li></ul><p>Пожалуйста, свяжитесь с отделом кадров для получения дополнительной информации о вашем переводе и необходимых мероприятиях.</p><p>С уважением,<br>Команда {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'Çalışan Transferi Bildirimi - {employee_name}',
                        'content' => '<p>Sayın <strong>{employee_name}</strong>,</p><p><strong>{effective_date}</strong> tarihinden itibaren geçerli olmak üzere transfer edildiğinizi bildirmek için yazıyoruz.</p><p><strong>Transfer Detayları:</strong></p><ul><li>Transfer Tarihi: {transfer_date}</li><li>Geçerlilik Tarihi: {effective_date}</li><li>Departman: {from_department_name} → {to_department_name}</li><li>Unvan: {from_designation_name} → {to_designation_name}</li><li>Sebep: {reason}</li></ul><p>Lütfen transferiniz ve gerekli düzenlemeler hakkında daha fazla bilgi için İK ile iletişime geçin.</p><p>Saygılarımızla,<br>{app_name} Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '员工调动通知 - {employee_name}',
                        'content' => '<p>尊敬的 <strong>{employee_name}</strong>，</p><p>我们写信通知您，您已被调动，生效日期为<strong>{effective_date}</strong>。</p><p><strong>调动详情：</strong></p><ul><li>调动日期：{transfer_date}</li><li>生效日期：{effective_date}</li><li>部门：{from_department_name} → {to_department_name}</li><li>职位：{from_designation_name} → {to_designation_name}</li><li>原因：{reason}</li></ul><p>请联系HR部门了解有关您调动的更多详情和任何必要的安排。</p><p>此致，<br>{app_name} 团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'Employee Contract',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Employment Contract - {contract_number}',
                        'content' => '<p>Dear <strong>{employee_name}</strong>,</p><p>We are pleased to provide you with your employment contract details.</p><p><strong>Contract Details:</strong></p><ul><li>Contract Number: {contract_number}</li><li>Contract Type: {contract_type}</li><li>Start Date: {start_date}</li><li>End Date: {end_date}</li><li>Basic Salary: {basic_salary}</li></ul><p>Please review the contract carefully. If you have any questions or concerns, please contact HR.</p><p>Best regards,<br>{app_name} Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Contrato de Empleo - {contract_number}',
                        'content' => '<p>Estimado/a <strong>{employee_name}</strong>,</p><p>Nos complace proporcionarle los detalles de su contrato de empleo.</p><p><strong>Detalles del Contrato:</strong></p><ul><li>Número de Contrato: {contract_number}</li><li>Tipo de Contrato: {contract_type}</li><li>Fecha de Inicio: {start_date}</li><li>Fecha de Fin: {end_date}</li><li>Salario Básico: {basic_salary}</li></ul><p>Por favor, revise el contrato cuidadosamente. Si tiene alguna pregunta o inquietud, contacte a RRHH.</p><p>Saludos cordiales,<br>Equipo de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'عقد العمل - {contract_number}',
                        'content' => '<p>عزيزي/عزيزتي <strong>{employee_name}</strong>،</p><p>يسعدنا تزويدك بتفاصيل عقد العمل الخاص بك.</p><p><strong>تفاصيل العقد:</strong></p><ul><li>رقم العقد: {contract_number}</li><li>نوع العقد: {contract_type}</li><li>تاريخ البدء: {start_date}</li><li>تاريخ الانتهاء: {end_date}</li><li>الراتب الأساسي: {basic_salary}</li></ul><p>يرجى مراجعة العقد بعناية. إذا كان لديك أي أسئلة أو مخاوف، يرجى الاتصال بالموارد البشرية.</p><p>مع أطيب التحيات،<br>فريق {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Ansættelseskontrakt - {contract_number}',
                        'content' => '<p>Kære <strong>{employee_name}</strong>,</p><p>Vi er glade for at give dig dine ansættelseskontraktdetaljer.</p><p><strong>Kontraktdetaljer:</strong></p><ul><li>Kontraktnummer: {contract_number}</li><li>Kontrakttype: {contract_type}</li><li>Startdato: {start_date}</li><li>Slutdato: {end_date}</li><li>Grundløn: {basic_salary}</li></ul><p>Gennemgå venligst kontrakten omhyggeligt. Hvis du har spørgsmål eller bekymringer, kontakt HR.</p><p>Med venlig hilsen,<br>{app_name} Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Arbeitsvertrag - {contract_number}',
                        'content' => '<p>Liebe/r <strong>{employee_name}</strong>,</p><p>Wir freuen uns, Ihnen Ihre Arbeitsvertragsdetails zur Verfügung zu stellen.</p><p><strong>Vertragsdetails:</strong></p><ul><li>Vertragsnummer: {contract_number}</li><li>Vertragsart: {contract_type}</li><li>Startdatum: {start_date}</li><li>Enddatum: {end_date}</li><li>Grundgehalt: {basic_salary}</li></ul><p>Bitte prüfen Sie den Vertrag sorgfältig. Bei Fragen oder Bedenken wenden Sie sich bitte an die Personalabteilung.</p><p>Mit freundlichen Grüßen,<br>{app_name} Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Contrat de Travail - {contract_number}',
                        'content' => '<p>Cher/Chère <strong>{employee_name}</strong>,</p><p>Nous sommes heureux de vous fournir les détails de votre contrat de travail.</p><p><strong>Détails du Contrat:</strong></p><ul><li>Numéro de Contrat: {contract_number}</li><li>Type de Contrat: {contract_type}</li><li>Date de Début: {start_date}</li><li>Date de Fin: {end_date}</li><li>Salaire de Base: {basic_salary}</li></ul><p>Veuillez examiner attentivement le contrat. Si vous avez des questions ou des préoccupations, veuillez contacter les RH.</p><p>Cordialement,<br>Équipe {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'חוזה עבודה - {contract_number}',
                        'content' => '<p>יקר/ה <strong>{employee_name}</strong>,</p><p>אנו שמחים לספק לך את פרטי חוזה העבודה שלך.</p><p><strong>פרטי החוזה:</strong></p><ul><li>מספר חוזה: {contract_number}</li><li>סוג חוזה: {contract_type}</li><li>תאריך התחלה: {start_date}</li><li>תאריך סיום: {end_date}</li><li>משכורת בסיס: {basic_salary}</li></ul><p>אנא עיין בחוזה בקפידה. אם יש לך שאלות או חששות, אנא צור קשר עם משאבי אנוש.</p><p>בברכה,<br>צוות {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Contratto di Lavoro - {contract_number}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Siamo lieti di fornirti i dettagli del tuo contratto di lavoro.</p><p><strong>Dettagli del Contratto:</strong></p><ul><li>Numero Contratto: {contract_number}</li><li>Tipo di Contratto: {contract_type}</li><li>Data di Inizio: {start_date}</li><li>Data di Fine: {end_date}</li><li>Stipendio Base: {basic_salary}</li></ul><p>Si prega di esaminare attentamente il contratto. Per domande o dubbi, contattare l\'HR.</p><p>Cordiali saluti,<br>Team {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '雇用契約 - {contract_number}',
                        'content' => '<p><strong>{employee_name}</strong>様、</p><p>雇用契約の詳細をお知らせいたします。</p><p><strong>契約の詳細：</strong></p><ul><li>契約番号: {contract_number}</li><li>契約タイプ: {contract_type}</li><li>開始日: {start_date}</li><li>終了日: {end_date}</li><li>基本給: {basic_salary}</li></ul><p>契約内容を注意深くご確認ください。ご質問やご不明な点がございましたら、人事部にお問い合わせください。</p><p>よろしくお願いいたします、<br>{app_name}チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Arbeidscontract - {contract_number}',
                        'content' => '<p>Beste <strong>{employee_name}</strong>,</p><p>We zijn verheugd je de details van je arbeidscontract te verstrekken.</p><p><strong>Contractdetails:</strong></p><ul><li>Contractnummer: {contract_number}</li><li>Contracttype: {contract_type}</li><li>Startdatum: {start_date}</li><li>Einddatum: {end_date}</li><li>Basissalaris: {basic_salary}</li></ul><p>Bekijk het contract zorgvuldig. Als je vragen of zorgen hebt, neem dan contact op met HR.</p><p>Met vriendelijke groet,<br>{app_name} Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Umowa o Pracę - {contract_number}',
                        'content' => '<p>Drogi/a <strong>{employee_name}</strong>,</p><p>Z przyjemnością przekazujemy szczegóły Twojej umowy o pracę.</p><p><strong>Szczegóły Umowy:</strong></p><ul><li>Numer Umowy: {contract_number}</li><li>Typ Umowy: {contract_type}</li><li>Data Rozpoczęcia: {start_date}</li><li>Data Zakończenia: {end_date}</li><li>Wynagrodzenie Podstawowe: {basic_salary}</li></ul><p>Prosimy o dokładne zapoznanie się z umową. W przypadku pytań lub wątpliwości prosimy o kontakt z HR.</p><p>Z poważaniem,<br>Zespół {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Contrato de Trabalho - {contract_number}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Temos o prazer de fornecer os detalhes do seu contrato de trabalho.</p><p><strong>Detalhes do Contrato:</strong></p><ul><li>Número do Contrato: {contract_number}</li><li>Tipo de Contrato: {contract_type}</li><li>Data de Início: {start_date}</li><li>Data de Término: {end_date}</li><li>Salário Básico: {basic_salary}</li></ul><p>Por favor, revise o contrato cuidadosamente. Se tiver alguma dúvida ou preocupação, entre em contato com o RH.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Contrato de Trabalho - {contract_number}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Temos o prazer de fornecer os detalhes do seu contrato de trabalho.</p><p><strong>Detalhes do Contrato:</strong></p><ul><li>Número do Contrato: {contract_number}</li><li>Tipo de Contrato: {contract_type}</li><li>Data de Início: {start_date}</li><li>Data de Término: {end_date}</li><li>Salário Básico: {basic_salary}</li></ul><p>Por favor, revise o contrato cuidadosamente. Se tiver alguma dúvida ou preocupação, entre em contato com o RH.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Трудовой Договор - {contract_number}',
                        'content' => '<p>Уважаемый/ая <strong>{employee_name}</strong>,</p><p>Мы рады предоставить вам детали вашего трудового договора.</p><p><strong>Детали Договора:</strong></p><ul><li>Номер Договора: {contract_number}</li><li>Тип Договора: {contract_type}</li><li>Дата Начала: {start_date}</li><li>Дата Окончания: {end_date}</li><li>Базовая Зарплата: {basic_salary}</li></ul><p>Пожалуйста, внимательно ознакомьтесь с договором. Если у вас есть вопросы или сомнения, свяжитесь с отделом кадров.</p><p>С уважением,<br>Команда {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'İş Sözleşmesi - {contract_number}',
                        'content' => '<p>Sayın <strong>{employee_name}</strong>,</p><p>İş sözleşmenizin detaylarını size sunmaktan mutluluk duyuyoruz.</p><p><strong>Sözleşme Detayları:</strong></p><ul><li>Sözleşme Numarası: {contract_number}</li><li>Sözleşme Türü: {contract_type}</li><li>Başlangıç Tarihi: {start_date}</li><li>Bitiş Tarihi: {end_date}</li><li>Temel Maaş: {basic_salary}</li></ul><p>Lütfen sözleşmeyi dikkatlice inceleyin. Herhangi bir sorunuz veya endişeniz varsa, İK ile iletişime geçin.</p><p>Saygılarımızla,<br>{app_name} Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '雇佣合同 - {contract_number}',
                        'content' => '<p>尊敬的 <strong>{employee_name}</strong>，</p><p>我们很高兴为您提供雇佣合同详情。</p><p><strong>合同详情：</strong></p><ul><li>合同编号：{contract_number}</li><li>合同类型：{contract_type}</li><li>开始日期：{start_date}</li><li>结束日期：{end_date}</li><li>基本工资：{basic_salary}</li></ul><p>请仔细审阅合同。如有任何疑问或顾虑，请联系HR部门。</p><p>此致，<br>{app_name} 团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'New Leave Request',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Leave Request Submitted - {employee_name}',
                        'content' => '<p>Dear HR Team,</p><p>A new leave request has been submitted by <strong>{employee_name}</strong>.</p><p><strong>Leave Request Details:</strong></p><ul><li>Employee Name: {employee_name}</li><li>Leave Type: {leave_type}</li><li>Start Date: {start_date}</li><li>End Date: {end_date}</li><li>Total Days: {total_days}</li><li>Reason: {reason}</li></ul><p>Please review and take appropriate action on this leave request.</p><p>Best regards,<br>{app_name} System</p>',
                    ],
                    'es' => [
                        'subject' => 'Solicitud de Permiso Enviada - {employee_name}',
                        'content' => '<p>Estimado Equipo de RRHH,</p><p>Se ha enviado una nueva solicitud de permiso por <strong>{employee_name}</strong>.</p><p><strong>Detalles de la Solicitud de Permiso:</strong></p><ul><li>Nombre del Empleado: {employee_name}</li><li>Tipo de Permiso: {leave_type}</li><li>Fecha de Inicio: {start_date}</li><li>Fecha de Fin: {end_date}</li><li>Total de Días: {total_days}</li><li>Motivo: {reason}</li></ul><p>Por favor, revise y tome las medidas apropiadas sobre esta solicitud de permiso.</p><p>Saludos cordiales,<br>Sistema {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'طلب إجازة مقدم - {employee_name}',
                        'content' => '<p>عزيزي فريق الموارد البشرية،</p><p>تم تقديم طلب إجازة جديد من قبل <strong>{employee_name}</strong>.</p><p><strong>تفاصيل طلب الإجازة:</strong></p><ul><li>اسم الموظف: {employee_name}</li><li>نوع الإجازة: {leave_type}</li><li>تاريخ البدء: {start_date}</li><li>تاريخ الانتهاء: {end_date}</li><li>إجمالي الأيام: {total_days}</li><li>السبب: {reason}</li></ul><p>يرجى المراجعة واتخاذ الإجراء المناسب بشأن طلب الإجازة هذا.</p><p>مع أطيب التحيات،<br>نظام {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Orlovsanmodning Indsendt - {employee_name}',
                        'content' => '<p>Kære HR-team,</p><p>En ny orlovsanmodning er blevet indsendt af <strong>{employee_name}</strong>.</p><p><strong>Orlovsanmodningsdetaljer:</strong></p><ul><li>Medarbejdernavn: {employee_name}</li><li>Orlovstype: {leave_type}</li><li>Startdato: {start_date}</li><li>Slutdato: {end_date}</li><li>Antal Dage: {total_days}</li><li>Årsag: {reason}</li></ul><p>Gennemgå venligst og tag passende handling på denne orlovsanmodning.</p><p>Med venlig hilsen,<br>{app_name} System</p>',
                    ],
                    'de' => [
                        'subject' => 'Urlaubsantrag Eingereicht - {employee_name}',
                        'content' => '<p>Liebes HR-Team,</p><p>Ein neuer Urlaubsantrag wurde von <strong>{employee_name}</strong> eingereicht.</p><p><strong>Urlaubsantragsdetails:</strong></p><ul><li>Mitarbeitername: {employee_name}</li><li>Urlaubsart: {leave_type}</li><li>Startdatum: {start_date}</li><li>Enddatum: {end_date}</li><li>Gesamttage: {total_days}</li><li>Grund: {reason}</li></ul><p>Bitte prüfen Sie und ergreifen Sie geeignete Maßnahmen zu diesem Urlaubsantrag.</p><p>Mit freundlichen Grüßen,<br>{app_name} System</p>',
                    ],
                    'fr' => [
                        'subject' => 'Demande de Congé Soumise - {employee_name}',
                        'content' => '<p>Cher Équipe RH,</p><p>Une nouvelle demande de congé a été soumise par <strong>{employee_name}</strong>.</p><p><strong>Détails de la Demande de Congé:</strong></p><ul><li>Nom de l\'Employé: {employee_name}</li><li>Type de Congé: {leave_type}</li><li>Date de Début: {start_date}</li><li>Date de Fin: {end_date}</li><li>Total de Jours: {total_days}</li><li>Raison: {reason}</li></ul><p>Veuillez examiner et prendre les mesures appropriées concernant cette demande de congé.</p><p>Cordialement,<br>Système {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'בקשת חופשה הוגשה - {employee_name}',
                        'content' => '<p>צוות משאבי אנוש יקר,</p><p>בקשת חופשה חדשה הוגשה על ידי <strong>{employee_name}</strong>.</p><p><strong>פרטי בקשת החופשה:</strong></p><ul><li>שם העובד: {employee_name}</li><li>סוג חופשה: {leave_type}</li><li>תאריך התחלה: {start_date}</li><li>תאריך סיום: {end_date}</li><li>סך הכל ימים: {total_days}</li><li>סיבה: {reason}</li></ul><p>אנא בדוק ונקוט בפעולה המתאימה לגבי בקשת חופשה זו.</p><p>בברכה,<br>מערכת {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Richiesta di Permesso Inviata - {employee_name}',
                        'content' => '<p>Caro Team HR,</p><p>Una nuova richiesta di permesso è stata inviata da <strong>{employee_name}</strong>.</p><p><strong>Dettagli della Richiesta di Permesso:</strong></p><ul><li>Nome Dipendente: {employee_name}</li><li>Tipo di Permesso: {leave_type}</li><li>Data di Inizio: {start_date}</li><li>Data di Fine: {end_date}</li><li>Totale Giorni: {total_days}</li><li>Motivo: {reason}</li></ul><p>Si prega di esaminare e prendere le misure appropriate su questa richiesta di permesso.</p><p>Cordiali saluti,<br>Sistema {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '休暇申請が提出されました - {employee_name}',
                        'content' => '<p>人事部御中、</p><p><strong>{employee_name}</strong>から新しい休暇申請が提出されました。</p><p><strong>休暇申請の詳細：</strong></p><ul><li>従業員名: {employee_name}</li><li>休暇タイプ: {leave_type}</li><li>開始日: {start_date}</li><li>終了日: {end_date}</li><li>合計日数: {total_days}</li><li>理由: {reason}</li></ul><p>この休暇申請を確認し、適切な対応をお願いいたします。</p><p>よろしくお願いいたします、<br>{app_name}システム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Verlofaanvraag Ingediend - {employee_name}',
                        'content' => '<p>Beste HR-team,</p><p>Een nieuwe verlofaanvraag is ingediend door <strong>{employee_name}</strong>.</p><p><strong>Verlofaanvraagdetails:</strong></p><ul><li>Werknemersnaam: {employee_name}</li><li>Verloftype: {leave_type}</li><li>Startdatum: {start_date}</li><li>Einddatum: {end_date}</li><li>Totaal Dagen: {total_days}</li><li>Reden: {reason}</li></ul><p>Bekijk en neem passende actie op deze verlofaanvraag.</p><p>Met vriendelijke groet,<br>{app_name} Systeem</p>',
                    ],
                    'pl' => [
                        'subject' => 'Wniosek Urlopowy Złożony - {employee_name}',
                        'content' => '<p>Szanowny Zespół HR,</p><p>Nowy wniosek urlopowy został złożony przez <strong>{employee_name}</strong>.</p><p><strong>Szczegóły Wniosku Urlopowego:</strong></p><ul><li>Imię i Nazwisko Pracownika: {employee_name}</li><li>Typ Urlopu: {leave_type}</li><li>Data Rozpoczęcia: {start_date}</li><li>Data Zakończenia: {end_date}</li><li>Łączna Liczba Dni: {total_days}</li><li>Powód: {reason}</li></ul><p>Prosimy o przegląd i podjęcie odpowiednich działań w sprawie tego wniosku urlopowego.</p><p>Z poważaniem,<br>System {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Solicitação de Licença Enviada - {employee_name}',
                        'content' => '<p>Prezada Equipe de RH,</p><p>Uma nova solicitação de licença foi enviada por <strong>{employee_name}</strong>.</p><p><strong>Detalhes da Solicitação de Licença:</strong></p><ul><li>Nome do Funcionário: {employee_name}</li><li>Tipo de Licença: {leave_type}</li><li>Data de Início: {start_date}</li><li>Data de Término: {end_date}</li><li>Total de Dias: {total_days}</li><li>Motivo: {reason}</li></ul><p>Por favor, revise e tome as medidas apropriadas sobre esta solicitação de licença.</p><p>Atenciosamente,<br>Sistema {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Solicitação de Licença Enviada - {employee_name}',
                        'content' => '<p>Prezada Equipe de RH,</p><p>Uma nova solicitação de licença foi enviada por <strong>{employee_name}</strong>.</p><p><strong>Detalhes da Solicitação de Licença:</strong></p><ul><li>Nome do Funcionário: {employee_name}</li><li>Tipo de Licença: {leave_type}</li><li>Data de Início: {start_date}</li><li>Data de Término: {end_date}</li><li>Total de Dias: {total_days}</li><li>Motivo: {reason}</li></ul><p>Por favor, revise e tome as medidas apropriadas sobre esta solicitação de licença.</p><p>Atenciosamente,<br>Sistema {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Заявка на Отпуск Подана - {employee_name}',
                        'content' => '<p>Уважаемая команда HR,</p><p>Новая заявка на отпуск была подана <strong>{employee_name}</strong>.</p><p><strong>Детали Заявки на Отпуск:</strong></p><ul><li>Имя Сотрудника: {employee_name}</li><li>Тип Отпуска: {leave_type}</li><li>Дата Начала: {start_date}</li><li>Дата Окончания: {end_date}</li><li>Всего Дней: {total_days}</li><li>Причина: {reason}</li></ul><p>Пожалуйста, рассмотрите и примите соответствующие меры по этой заявке на отпуск.</p><p>С уважением,<br>Система {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'İzin Talebi Gönderildi - {employee_name}',
                        'content' => '<p>Sayın İK Ekibi,</p><p><strong>{employee_name}</strong> tarafından yeni bir izin talebi gönderildi.</p><p><strong>İzin Talebi Detayları:</strong></p><ul><li>Çalışan Adı: {employee_name}</li><li>İzin Türü: {leave_type}</li><li>Başlangıç Tarihi: {start_date}</li><li>Bitiş Tarihi: {end_date}</li><li>Toplam Gün: {total_days}</li><li>Sebep: {reason}</li></ul><p>Lütfen bu izin talebini inceleyin ve uygun işlemi yapın.</p><p>Saygılarımızla,<br>{app_name} Sistemi</p>',
                    ],
                    'zh' => [
                        'subject' => '请假申请已提交 - {employee_name}',
                        'content' => '<p>尊敬的HR团队，</p><p><strong>{employee_name}</strong>提交了新的请假申请。</p><p><strong>请假申请详情：</strong></p><ul><li>员工姓名：{employee_name}</li><li>请假类型：{leave_type}</li><li>开始日期：{start_date}</li><li>结束日期：{end_date}</li><li>总天数：{total_days}</li><li>原因：{reason}</li></ul><p>请审核并对此请假申请采取适当措施。</p><p>此致，<br>{app_name} 系统</p>',
                    ],
                ],
            ],
            [
                'name' => 'Leave Request Status Change',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Leave Request {status} - {leave_type}',
                        'content' => '<p>Dear <strong>{employee_name}</strong>,</p><p>Your leave request has been <strong>{status}</strong> by the HR/Management.</p><p><strong>Leave Request Details:</strong></p><ul><li>Leave Type: {leave_type}</li><li>Start Date: {start_date}</li><li>End Date: {end_date}</li><li>Total Days: {total_days}</li><li>Status: {status}</li><li>Reviewed By: {approved_by}</li><li>Reviewed Date: {approved_at}</li><li>Comments: {manager_comments}</li></ul><p>If you have any questions regarding this decision, please contact the HR department.</p><p>Best regards,<br>{app_name} Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Solicitud de Permiso {status} - {leave_type}',
                        'content' => '<p>Estimado/a <strong>{employee_name}</strong>,</p><p>Su solicitud de permiso ha sido <strong>{status}</strong> por RRHH/Gerencia.</p><p><strong>Detalles de la Solicitud:</strong></p><ul><li>Tipo de Permiso: {leave_type}</li><li>Fecha de Inicio: {start_date}</li><li>Fecha de Fin: {end_date}</li><li>Total de Días: {total_days}</li><li>Estado: {status}</li><li>Revisado Por: {approved_by}</li><li>Fecha de Revisión: {approved_at}</li><li>Comentarios: {manager_comments}</li></ul><p>Si tiene alguna pregunta sobre esta decisión, contacte al departamento de RRHH.</p><p>Saludos cordiales,<br>Equipo de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'طلب الإجازة {status} - {leave_type}',
                        'content' => '<p>عزيزي/عزيزتي <strong>{employee_name}</strong>،</p><p>تم <strong>{status}</strong> طلب الإجازة الخاص بك من قبل الموارد البشرية/الإدارة.</p><p><strong>تفاصيل طلب الإجازة:</strong></p><ul><li>نوع الإجازة: {leave_type}</li><li>تاريخ البدء: {start_date}</li><li>تاريخ الانتهاء: {end_date}</li><li>إجمالي الأيام: {total_days}</li><li>الحالة: {status}</li><li>تمت المراجعة بواسطة: {approved_by}</li><li>تاريخ المراجعة: {approved_at}</li><li>التعليقات: {manager_comments}</li></ul><p>إذا كان لديك أي أسئلة بخصوص هذا القرار، يرجى الاتصال بقسم الموارد البشرية.</p><p>مع أطيب التحيات،<br>فريق {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Orlovsanmodning {status} - {leave_type}',
                        'content' => '<p>Kære <strong>{employee_name}</strong>,</p><p>Din orlovsanmodning er blevet <strong>{status}</strong> af HR/Ledelsen.</p><p><strong>Orlovsanmodningsdetaljer:</strong></p><ul><li>Orlovstype: {leave_type}</li><li>Startdato: {start_date}</li><li>Slutdato: {end_date}</li><li>Antal Dage: {total_days}</li><li>Status: {status}</li><li>Gennemgået Af: {approved_by}</li><li>Gennemgangsdato: {approved_at}</li><li>Kommentarer: {manager_comments}</li></ul><p>Hvis du har spørgsmål til denne beslutning, kontakt HR-afdelingen.</p><p>Med venlig hilsen,<br>{app_name} Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Urlaubsantrag {status} - {leave_type}',
                        'content' => '<p>Liebe/r <strong>{employee_name}</strong>,</p><p>Ihr Urlaubsantrag wurde von der Personalabteilung/Geschäftsführung <strong>{status}</strong>.</p><p><strong>Urlaubsantragsdetails:</strong></p><ul><li>Urlaubsart: {leave_type}</li><li>Startdatum: {start_date}</li><li>Enddatum: {end_date}</li><li>Gesamttage: {total_days}</li><li>Status: {status}</li><li>Überprüft Von: {approved_by}</li><li>Überprüfungsdatum: {approved_at}</li><li>Kommentare: {manager_comments}</li></ul><p>Bei Fragen zu dieser Entscheidung wenden Sie sich bitte an die Personalabteilung.</p><p>Mit freundlichen Grüßen,<br>{app_name} Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Demande de Congé {status} - {leave_type}',
                        'content' => '<p>Cher/Chère <strong>{employee_name}</strong>,</p><p>Votre demande de congé a été <strong>{status}</strong> par les RH/Direction.</p><p><strong>Détails de la Demande:</strong></p><ul><li>Type de Congé: {leave_type}</li><li>Date de Début: {start_date}</li><li>Date de Fin: {end_date}</li><li>Total de Jours: {total_days}</li><li>Statut: {status}</li><li>Examiné Par: {approved_by}</li><li>Date d\'Examen: {approved_at}</li><li>Commentaires: {manager_comments}</li></ul><p>Si vous avez des questions concernant cette décision, veuillez contacter le département RH.</p><p>Cordialement,<br>Équipe {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'בקשת חופשה {status} - {leave_type}',
                        'content' => '<p>יקר/ה <strong>{employee_name}</strong>,</p><p>בקשת החופשה שלך <strong>{status}</strong> על ידי משאבי אנוש/הנהלה.</p><p><strong>פרטי בקשת החופשה:</strong></p><ul><li>סוג חופשה: {leave_type}</li><li>תאריך התחלה: {start_date}</li><li>תאריך סיום: {end_date}</li><li>סך הכל ימים: {total_days}</li><li>סטטוס: {status}</li><li>נבדק על ידי: {approved_by}</li><li>תאריך בדיקה: {approved_at}</li><li>הערות: {manager_comments}</li></ul><p>אם יש לך שאלות לגבי החלטה זו, אנא צור קשר עם מחלקת משאבי אנוש.</p><p>בברכה,<br>צוות {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Richiesta di Permesso {status} - {leave_type}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>La tua richiesta di permesso è stata <strong>{status}</strong> dall\'HR/Direzione.</p><p><strong>Dettagli della Richiesta:</strong></p><ul><li>Tipo di Permesso: {leave_type}</li><li>Data di Inizio: {start_date}</li><li>Data di Fine: {end_date}</li><li>Totale Giorni: {total_days}</li><li>Stato: {status}</li><li>Esaminato Da: {approved_by}</li><li>Data di Esame: {approved_at}</li><li>Commenti: {manager_comments}</li></ul><p>Per domande su questa decisione, contattare il dipartimento HR.</p><p>Cordiali saluti,<br>Team {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '休暇申請{status} - {leave_type}',
                        'content' => '<p><strong>{employee_name}</strong>様、</p><p>あなたの休暇申請はHR/管理部門により<strong>{status}</strong>されました。</p><p><strong>休暇申請の詳細：</strong></p><ul><li>休暇タイプ: {leave_type}</li><li>開始日: {start_date}</li><li>終了日: {end_date}</li><li>合計日数: {total_days}</li><li>ステータス: {status}</li><li>承認者: {approved_by}</li><li>承認日: {approved_at}</li><li>コメント: {manager_comments}</li></ul><p>この決定について質問がある場合は、人事部にお問い合わせください。</p><p>よろしくお願いいたします、<br>{app_name}チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Verlofaanvraag {status} - {leave_type}',
                        'content' => '<p>Beste <strong>{employee_name}</strong>,</p><p>Je verlofaanvraag is <strong>{status}</strong> door HR/Management.</p><p><strong>Verlofaanvraagdetails:</strong></p><ul><li>Verloftype: {leave_type}</li><li>Startdatum: {start_date}</li><li>Einddatum: {end_date}</li><li>Totaal Dagen: {total_days}</li><li>Status: {status}</li><li>Beoordeeld Door: {approved_by}</li><li>Beoordelingsdatum: {approved_at}</li><li>Opmerkingen: {manager_comments}</li></ul><p>Voor vragen over deze beslissing, neem contact op met de HR-afdeling.</p><p>Met vriendelijke groet,<br>{app_name} Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Wniosek Urlopowy {status} - {leave_type}',
                        'content' => '<p>Drogi/a <strong>{employee_name}</strong>,</p><p>Twój wniosek urlopowy został <strong>{status}</strong> przez HR/Zarząd.</p><p><strong>Szczegóły Wniosku:</strong></p><ul><li>Typ Urlopu: {leave_type}</li><li>Data Rozpoczęcia: {start_date}</li><li>Data Zakończenia: {end_date}</li><li>Łączna Liczba Dni: {total_days}</li><li>Status: {status}</li><li>Sprawdzone Przez: {approved_by}</li><li>Data Sprawdzenia: {approved_at}</li><li>Komentarze: {manager_comments}</li></ul><p>W przypadku pytań dotyczących tej decyzji, skontaktuj się z działem HR.</p><p>Z poważaniem,<br>Zespół {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Solicitação de Licença {status} - {leave_type}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Sua solicitação de licença foi <strong>{status}</strong> pelo RH/Gerência.</p><p><strong>Detalhes da Solicitação:</strong></p><ul><li>Tipo de Licença: {leave_type}</li><li>Data de Início: {start_date}</li><li>Data de Término: {end_date}</li><li>Total de Dias: {total_days}</li><li>Status: {status}</li><li>Revisado Por: {approved_by}</li><li>Data de Revisão: {approved_at}</li><li>Comentários: {manager_comments}</li></ul><p>Se tiver dúvidas sobre esta decisão, entre em contato com o departamento de RH.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Solicitação de Licença {status} - {leave_type}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Sua solicitação de licença foi <strong>{status}</strong> pelo RH/Gerência.</p><p><strong>Detalhes da Solicitação:</strong></p><ul><li>Tipo de Licença: {leave_type}</li><li>Data de Início: {start_date}</li><li>Data de Término: {end_date}</li><li>Total de Dias: {total_days}</li><li>Status: {status}</li><li>Revisado Por: {approved_by}</li><li>Data de Revisão: {approved_at}</li><li>Comentários: {manager_comments}</li></ul><p>Se tiver dúvidas sobre esta decisão, entre em contato com o departamento de RH.</p><p>Atenciosamente,<br>Equipe {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Заявка на Отпуск {status} - {leave_type}',
                        'content' => '<p>Уважаемый/ая <strong>{employee_name}</strong>,</p><p>Ваша заявка на отпуск была <strong>{status}</strong> отделом кадров/руководством.</p><p><strong>Детали Заявки:</strong></p><ul><li>Тип Отпуска: {leave_type}</li><li>Дата Начала: {start_date}</li><li>Дата Окончания: {end_date}</li><li>Всего Дней: {total_days}</li><li>Статус: {status}</li><li>Рассмотрено: {approved_by}</li><li>Дата Рассмотрения: {approved_at}</li><li>Комментарии: {manager_comments}</li></ul><p>Если у вас есть вопросы по этому решению, свяжитесь с отделом кадров.</p><p>С уважением,<br>Команда {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'İzin Talebi {status} - {leave_type}',
                        'content' => '<p>Sayın <strong>{employee_name}</strong>,</p><p>İzin talebiniz İK/Yönetim tarafından <strong>{status}</strong>.</p><p><strong>İzin Talebi Detayları:</strong></p><ul><li>İzin Türü: {leave_type}</li><li>Başlangıç Tarihi: {start_date}</li><li>Bitiş Tarihi: {end_date}</li><li>Toplam Gün: {total_days}</li><li>Durum: {status}</li><li>İnceleyen: {approved_by}</li><li>İnceleme Tarihi: {approved_at}</li><li>Yorumlar: {manager_comments}</li></ul><p>Bu karar hakkında sorularınız varsa, İK departmanı ile iletişime geçin.</p><p>Saygılarımızla,<br>{app_name} Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '请假申请{status} - {leave_type}',
                        'content' => '<p>尊敬的 <strong>{employee_name}</strong>，</p><p>您的请假申请已被HR/管理层<strong>{status}</strong>。</p><p><strong>请假申请详情：</strong></p><ul><li>请假类型：{leave_type}</li><li>开始日期：{start_date}</li><li>结束日期：{end_date}</li><li>总天数：{total_days}</li><li>状态：{status}</li><li>审核人：{approved_by}</li><li>审核日期：{approved_at}</li><li>备注：{manager_comments}</li></ul><p>如对此决定有任何疑问，请联系HR部门。</p><p>此致，<br>{app_name} 团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'Offer Create',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Job Offer - {position} at {app_name}',
                        'content' => '<p>Dear <strong>{candidate_name}</strong>,</p><p>We are pleased to extend an offer of employment for the position of <strong>{position}</strong> at {app_name}.</p><p><strong>Offer Details:</strong></p><ul><li>Position: {position}</li><li>Department: {department_name}</li><li>Salary: {salary}</li><li>Start Date: {start_date}</li><li>Offer Date: {offer_date}</li><li>Expiration Date: {expiration_date}</li><li>Status: {status}</li></ul><p>Please review this offer carefully and respond by <strong>{expiration_date}</strong>.</p><p>We look forward to welcoming you to our team!</p><p>Best regards,<br>{app_name} HR Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Oferta de Trabajo - {position} en {app_name}',
                        'content' => '<p>Estimado/a <strong>{candidate_name}</strong>,</p><p>Nos complace extender una oferta de empleo para el puesto de <strong>{position}</strong> en {app_name}.</p><p><strong>Detalles de la Oferta:</strong></p><ul><li>Puesto: {position}</li><li>Departamento: {department_name}</li><li>Salario: {salary}</li><li>Fecha de Inicio: {start_date}</li><li>Fecha de Oferta: {offer_date}</li><li>Fecha de Vencimiento: {expiration_date}</li><li>Estado: {status}</li></ul><p>Por favor, revise esta oferta cuidadosamente y responda antes del <strong>{expiration_date}</strong>.</p><p>¡Esperamos darle la bienvenida a nuestro equipo!</p><p>Saludos cordiales,<br>Equipo de RRHH de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'عرض عمل - {position} في {app_name}',
                        'content' => '<p>عزيزي/عزيزتي <strong>{candidate_name}</strong>،</p><p>يسعدنا تقديم عرض عمل لمنصب <strong>{position}</strong> في {app_name}.</p><p><strong>تفاصيل العرض:</strong></p><ul><li>المنصب: {position}</li><li>القسم: {department_name}</li><li>الراتب: {salary}</li><li>تاريخ البدء: {start_date}</li><li>تاريخ العرض: {offer_date}</li><li>تاريخ الانتهاء: {expiration_date}</li><li>الحالة: {status}</li></ul><p>يرجى مراجعة هذا العرض بعناية والرد قبل <strong>{expiration_date}</strong>.</p><p>نتطلع للترحيب بك في فريقنا!</p><p>مع أطيب التحيات،<br>فريق الموارد البشرية في {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Jobtilbud - {position} hos {app_name}',
                        'content' => '<p>Kære <strong>{candidate_name}</strong>,</p><p>Vi er glade for at tilbyde dig ansættelse som <strong>{position}</strong> hos {app_name}.</p><p><strong>Tilbudsdetaljer:</strong></p><ul><li>Stilling: {position}</li><li>Afdeling: {department_name}</li><li>Løn: {salary}</li><li>Startdato: {start_date}</li><li>Tilbudsdato: {offer_date}</li><li>Udløbsdato: {expiration_date}</li><li>Status: {status}</li></ul><p>Gennemgå venligst dette tilbud omhyggeligt og svar senest <strong>{expiration_date}</strong>.</p><p>Vi ser frem til at byde dig velkommen i vores team!</p><p>Med venlig hilsen,<br>{app_name} HR Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Stellenangebot - {position} bei {app_name}',
                        'content' => '<p>Liebe/r <strong>{candidate_name}</strong>,</p><p>Wir freuen uns, Ihnen ein Stellenangebot für die Position <strong>{position}</strong> bei {app_name} zu unterbreiten.</p><p><strong>Angebotsdetails:</strong></p><ul><li>Position: {position}</li><li>Abteilung: {department_name}</li><li>Gehalt: {salary}</li><li>Startdatum: {start_date}</li><li>Angebotsdatum: {offer_date}</li><li>Ablaufdatum: {expiration_date}</li><li>Status: {status}</li></ul><p>Bitte prüfen Sie dieses Angebot sorgfältig und antworten Sie bis zum <strong>{expiration_date}</strong>.</p><p>Wir freuen uns darauf, Sie in unserem Team willkommen zu heißen!</p><p>Mit freundlichen Grüßen,<br>{app_name} HR Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Offre d\'Emploi - {position} chez {app_name}',
                        'content' => '<p>Cher/Chère <strong>{candidate_name}</strong>,</p><p>Nous sommes heureux de vous proposer un emploi pour le poste de <strong>{position}</strong> chez {app_name}.</p><p><strong>Détails de l\'Offre:</strong></p><ul><li>Poste: {position}</li><li>Département: {department_name}</li><li>Salaire: {salary}</li><li>Date de Début: {start_date}</li><li>Date d\'Offre: {offer_date}</li><li>Date d\'Expiration: {expiration_date}</li><li>Statut: {status}</li></ul><p>Veuillez examiner attentivement cette offre et répondre avant le <strong>{expiration_date}</strong>.</p><p>Nous sommes impatients de vous accueillir dans notre équipe!</p><p>Cordialement,<br>Équipe RH de {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'הצעת עבודה - {position} ב-{app_name}',
                        'content' => '<p>יקר/ה <strong>{candidate_name}</strong>,</p><p>אנו שמחים להציע לך משרה בתפקיד <strong>{position}</strong> ב-{app_name}.</p><p><strong>פרטי ההצעה:</strong></p><ul><li>תפקיד: {position}</li><li>מחלקה: {department_name}</li><li>משכורת: {salary}</li><li>תאריך התחלה: {start_date}</li><li>תאריך הצעה: {offer_date}</li><li>תאריך תפוגה: {expiration_date}</li><li>סטטוס: {status}</li></ul><p>אנא עיין בהצעה זו בקפידה והשב עד <strong>{expiration_date}</strong>.</p><p>אנו מצפים לקבל אותך לצוות שלנו!</p><p>בברכה,<br>צוות משאבי אנוש {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Offerta di Lavoro - {position} presso {app_name}',
                        'content' => '<p>Caro/a <strong>{candidate_name}</strong>,</p><p>Siamo lieti di offrirti un impiego per la posizione di <strong>{position}</strong> presso {app_name}.</p><p><strong>Dettagli dell\'Offerta:</strong></p><ul><li>Posizione: {position}</li><li>Dipartimento: {department_name}</li><li>Stipendio: {salary}</li><li>Data di Inizio: {start_date}</li><li>Data Offerta: {offer_date}</li><li>Data di Scadenza: {expiration_date}</li><li>Stato: {status}</li></ul><p>Si prega di esaminare attentamente questa offerta e rispondere entro il <strong>{expiration_date}</strong>.</p><p>Non vediamo l\'ora di darti il benvenuto nel nostro team!</p><p>Cordiali saluti,<br>Team HR di {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '採用オファー - {app_name}の{position}',
                        'content' => '<p><strong>{candidate_name}</strong>様、</p><p>{app_name}の<strong>{position}</strong>職への採用オファーをお送りいたします。</p><p><strong>オファー詳細：</strong></p><ul><li>役職: {position}</li><li>部門: {department_name}</li><li>給与: {salary}</li><li>入社日: {start_date}</li><li>オファー日: {offer_date}</li><li>有効期限: {expiration_date}</li><li>ステータス: {status}</li></ul><p>このオファーを注意深くご確認の上、<strong>{expiration_date}</strong>までにご返答をお願いいたします。</p><p>あなたをチームに迎えることを楽しみにしております！</p><p>よろしくお願いいたします、<br>{app_name} 人事チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Werkaanbod - {position} bij {app_name}',
                        'content' => '<p>Beste <strong>{candidate_name}</strong>,</p><p>We zijn verheugd je een werkaanbod te doen voor de functie van <strong>{position}</strong> bij {app_name}.</p><p><strong>Aanboddetails:</strong></p><ul><li>Functie: {position}</li><li>Afdeling: {department_name}</li><li>Salaris: {salary}</li><li>Startdatum: {start_date}</li><li>Aanboddatum: {offer_date}</li><li>Vervaldatum: {expiration_date}</li><li>Status: {status}</li></ul><p>Bekijk dit aanbod zorgvuldig en reageer uiterlijk <strong>{expiration_date}</strong>.</p><p>We kijken ernaar uit je te verwelkomen in ons team!</p><p>Met vriendelijke groet,<br>{app_name} HR Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Oferta Pracy - {position} w {app_name}',
                        'content' => '<p>Drogi/a <strong>{candidate_name}</strong>,</p><p>Z przyjemnością składamy ofertę zatrudnienia na stanowisko <strong>{position}</strong> w {app_name}.</p><p><strong>Szczegóły Oferty:</strong></p><ul><li>Stanowisko: {position}</li><li>Dział: {department_name}</li><li>Wynagrodzenie: {salary}</li><li>Data Rozpoczęcia: {start_date}</li><li>Data Oferty: {offer_date}</li><li>Data Wygaśnięcia: {expiration_date}</li><li>Status: {status}</li></ul><p>Prosimy o dokładne zapoznanie się z tą ofertą i odpowiedź do <strong>{expiration_date}</strong>.</p><p>Cieszymy się na powitanie Cię w naszym zespole!</p><p>Z poważaniem,<br>Zespół HR {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Oferta de Emprego - {position} na {app_name}',
                        'content' => '<p>Caro/a <strong>{candidate_name}</strong>,</p><p>Temos o prazer de estender uma oferta de emprego para a posição de <strong>{position}</strong> na {app_name}.</p><p><strong>Detalhes da Oferta:</strong></p><ul><li>Posição: {position}</li><li>Departamento: {department_name}</li><li>Salário: {salary}</li><li>Data de Início: {start_date}</li><li>Data da Oferta: {offer_date}</li><li>Data de Expiração: {expiration_date}</li><li>Status: {status}</li></ul><p>Por favor, revise esta oferta cuidadosamente e responda até <strong>{expiration_date}</strong>.</p><p>Estamos ansiosos para recebê-lo em nossa equipe!</p><p>Atenciosamente,<br>Equipe de RH da {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Oferta de Emprego - {position} na {app_name}',
                        'content' => '<p>Caro/a <strong>{candidate_name}</strong>,</p><p>Temos o prazer de estender uma oferta de emprego para a posição de <strong>{position}</strong> na {app_name}.</p><p><strong>Detalhes da Oferta:</strong></p><ul><li>Posição: {position}</li><li>Departamento: {department_name}</li><li>Salário: {salary}</li><li>Data de Início: {start_date}</li><li>Data da Oferta: {offer_date}</li><li>Data de Expiração: {expiration_date}</li><li>Status: {status}</li></ul><p>Por favor, revise esta oferta cuidadosamente e responda até <strong>{expiration_date}</strong>.</p><p>Estamos ansiosos para recebê-lo em nossa equipe!</p><p>Atenciosamente,<br>Equipe de RH da {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Предложение о Работе - {position} в {app_name}',
                        'content' => '<p>Уважаемый/ая <strong>{candidate_name}</strong>,</p><p>Мы рады предложить вам работу на должность <strong>{position}</strong> в {app_name}.</p><p><strong>Детали Предложения:</strong></p><ul><li>Должность: {position}</li><li>Отдел: {department_name}</li><li>Зарплата: {salary}</li><li>Дата Начала: {start_date}</li><li>Дата Предложения: {offer_date}</li><li>Срок Действия: {expiration_date}</li><li>Статус: {status}</li></ul><p>Пожалуйста, внимательно ознакомьтесь с этим предложением и ответьте до <strong>{expiration_date}</strong>.</p><p>Мы с нетерпением ждем возможности приветствовать вас в нашей команде!</p><p>С уважением,<br>Команда HR {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'İş Teklifi - {app_name}\'de {position}',
                        'content' => '<p>Sayın <strong>{candidate_name}</strong>,</p><p>{app_name}\'de <strong>{position}</strong> pozisyonu için size bir iş teklifi sunmaktan mutluluk duyuyoruz.</p><p><strong>Teklif Detayları:</strong></p><ul><li>Pozisyon: {position}</li><li>Departman: {department_name}</li><li>Maaş: {salary}</li><li>Başlangıç Tarihi: {start_date}</li><li>Teklif Tarihi: {offer_date}</li><li>Son Geçerlilik Tarihi: {expiration_date}</li><li>Durum: {status}</li></ul><p>Lütfen bu teklifi dikkatlice inceleyin ve <strong>{expiration_date}</strong> tarihine kadar yanıt verin.</p><p>Sizi ekibimizde görmek için sabırsızlanıyoruz!</p><p>Saygılarımızla,<br>{app_name} İK Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '工作邀请 - {app_name}的{position}',
                        'content' => '<p>尊敬的 <strong>{candidate_name}</strong>，</p><p>我们很高兴向您提供{app_name}的<strong>{position}</strong>职位的工作邀请。</p><p><strong>邀请详情：</strong></p><ul><li>职位：{position}</li><li>部门：{department_name}</li><li>薪资：{salary}</li><li>入职日期：{start_date}</li><li>邀请日期：{offer_date}</li><li>截止日期：{expiration_date}</li><li>状态：{status}</li></ul><p>请仔细查看此邀请并在<strong>{expiration_date}</strong>之前回复。</p><p>我们期待欢迎您加入我们的团队！</p><p>此致，<br>{app_name} 人力资源团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'Offer Status Update',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Offer Status Update - {position}',
                        'content' => '<p>Dear <strong>{candidate_name}</strong>,</p><p>We would like to inform you that the status of your job offer for the position of <strong>{position}</strong> has been updated by our HR team.</p><p><strong>Offer Status Details:</strong></p><ul><li>Position: {position}</li><li>Department: {department_name}</li><li>Current Status: {status}</li><li>Offer Date: {offer_date}</li><li>Expiration Date: {expiration_date}</li></ul><p>If you have any questions regarding this update, please contact our HR department.</p><p>Best regards,<br>{app_name} HR Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Actualización del Estado de la Oferta - {position}',
                        'content' => '<p>Estimado/a <strong>{candidate_name}</strong>,</p><p>Nos gustaría informarle que el estado de su oferta de trabajo para el puesto de <strong>{position}</strong> ha sido actualizado por nuestro equipo de RRHH.</p><p><strong>Detalles del Estado de la Oferta:</strong></p><ul><li>Puesto: {position}</li><li>Departamento: {department_name}</li><li>Estado Actual: {status}</li><li>Fecha de Oferta: {offer_date}</li><li>Fecha de Vencimiento: {expiration_date}</li></ul><p>Si tiene alguna pregunta sobre esta actualización, contacte a nuestro departamento de RRHH.</p><p>Saludos cordiales,<br>Equipo de RRHH de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'تحديث حالة العرض - {position}',
                        'content' => '<p>عزيزي/عزيزتي <strong>{candidate_name}</strong>،</p><p>نود إبلاغك بأن حالة عرض العمل الخاص بك لمنصب <strong>{position}</strong> قد تم تحديثها من قبل فريق الموارد البشرية لدينا.</p><p><strong>تفاصيل حالة العرض:</strong></p><ul><li>المنصب: {position}</li><li>القسم: {department_name}</li><li>الحالة الحالية: {status}</li><li>تاريخ العرض: {offer_date}</li><li>تاريخ الانتهاء: {expiration_date}</li></ul><p>إذا كان لديك أي أسئلة بخصوص هذا التحديث، يرجى الاتصال بقسم الموارد البشرية.</p><p>مع أطيب التحيات،<br>فريق الموارد البشرية في {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Opdatering af Tilbudsstatus - {position}',
                        'content' => '<p>Kære <strong>{candidate_name}</strong>,</p><p>Vi vil gerne informere dig om, at status på dit jobtilbud for stillingen som <strong>{position}</strong> er blevet opdateret af vores HR-team.</p><p><strong>Tilbudsstatusdetaljer:</strong></p><ul><li>Stilling: {position}</li><li>Afdeling: {department_name}</li><li>Nuværende Status: {status}</li><li>Tilbudsdato: {offer_date}</li><li>Udløbsdato: {expiration_date}</li></ul><p>Hvis du har spørgsmål til denne opdatering, kontakt vores HR-afdeling.</p><p>Med venlig hilsen,<br>{app_name} HR Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Aktualisierung des Angebotsstatus - {position}',
                        'content' => '<p>Liebe/r <strong>{candidate_name}</strong>,</p><p>Wir möchten Sie darüber informieren, dass der Status Ihres Stellenangebots für die Position <strong>{position}</strong> von unserem HR-Team aktualisiert wurde.</p><p><strong>Angebotsstatusdetails:</strong></p><ul><li>Position: {position}</li><li>Abteilung: {department_name}</li><li>Aktueller Status: {status}</li><li>Angebotsdatum: {offer_date}</li><li>Ablaufdatum: {expiration_date}</li></ul><p>Bei Fragen zu dieser Aktualisierung wenden Sie sich bitte an unsere Personalabteilung.</p><p>Mit freundlichen Grüßen,<br>{app_name} HR Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Mise à Jour du Statut de l\'Offre - {position}',
                        'content' => '<p>Cher/Chère <strong>{candidate_name}</strong>,</p><p>Nous souhaitons vous informer que le statut de votre offre d\'emploi pour le poste de <strong>{position}</strong> a été mis à jour par notre équipe RH.</p><p><strong>Détails du Statut de l\'Offre:</strong></p><ul><li>Poste: {position}</li><li>Département: {department_name}</li><li>Statut Actuel: {status}</li><li>Date d\'Offre: {offer_date}</li><li>Date d\'Expiration: {expiration_date}</li></ul><p>Si vous avez des questions concernant cette mise à jour, veuillez contacter notre département RH.</p><p>Cordialement,<br>Équipe RH de {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'עדכון סטטוס הצעה - {position}',
                        'content' => '<p>יקר/ה <strong>{candidate_name}</strong>,</p><p>ברצוננו להודיע לך שהסטטוס של הצעת העבודה שלך לתפקיד <strong>{position}</strong> עודכן על ידי צוות משאבי האנוש שלנו.</p><p><strong>פרטי סטטוס ההצעה:</strong></p><ul><li>תפקיד: {position}</li><li>מחלקה: {department_name}</li><li>סטטוס נוכחי: {status}</li><li>תאריך הצעה: {offer_date}</li><li>תאריך תפוגה: {expiration_date}</li></ul><p>אם יש לך שאלות לגבי עדכון זה, אנא צור קשר עם מחלקת משאבי אנוש.</p><p>בברכה,<br>צוות משאבי אנוש {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Aggiornamento Stato Offerta - {position}',
                        'content' => '<p>Caro/a <strong>{candidate_name}</strong>,</p><p>Desideriamo informarti che lo stato della tua offerta di lavoro per la posizione di <strong>{position}</strong> è stato aggiornato dal nostro team HR.</p><p><strong>Dettagli dello Stato dell\'Offerta:</strong></p><ul><li>Posizione: {position}</li><li>Dipartimento: {department_name}</li><li>Stato Attuale: {status}</li><li>Data Offerta: {offer_date}</li><li>Data di Scadenza: {expiration_date}</li></ul><p>Per domande su questo aggiornamento, contattare il nostro dipartimento HR.</p><p>Cordiali saluti,<br>Team HR di {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => 'オファーステータス更新 - {position}',
                        'content' => '<p><strong>{candidate_name}</strong>様、</p><p><strong>{position}</strong>職の採用オファーのステータスが人事チームにより更新されましたのでお知らせいたします。</p><p><strong>オファーステータス詳細：</strong></p><ul><li>役職: {position}</li><li>部門: {department_name}</li><li>現在のステータス: {status}</li><li>オファー日: {offer_date}</li><li>有効期限: {expiration_date}</li></ul><p>この更新に関してご質問がある場合は、人事部にお問い合わせください。</p><p>よろしくお願いいたします、<br>{app_name} 人事チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Update Aanbodstatus - {position}',
                        'content' => '<p>Beste <strong>{candidate_name}</strong>,</p><p>We willen je informeren dat de status van je werkaanbod voor de functie van <strong>{position}</strong> is bijgewerkt door ons HR-team.</p><p><strong>Aanbodstatusdetails:</strong></p><ul><li>Functie: {position}</li><li>Afdeling: {department_name}</li><li>Huidige Status: {status}</li><li>Aanboddatum: {offer_date}</li><li>Vervaldatum: {expiration_date}</li></ul><p>Voor vragen over deze update, neem contact op met onze HR-afdeling.</p><p>Met vriendelijke groet,<br>{app_name} HR Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Aktualizacja Statusu Oferty - {position}',
                        'content' => '<p>Drogi/a <strong>{candidate_name}</strong>,</p><p>Chcielibyśmy poinformować, że status Twojej oferty pracy na stanowisko <strong>{position}</strong> został zaktualizowany przez nasz zespół HR.</p><p><strong>Szczegóły Statusu Oferty:</strong></p><ul><li>Stanowisko: {position}</li><li>Dział: {department_name}</li><li>Aktualny Status: {status}</li><li>Data Oferty: {offer_date}</li><li>Data Wygaśnięcia: {expiration_date}</li></ul><p>W przypadku pytań dotyczących tej aktualizacji, skontaktuj się z naszym działem HR.</p><p>Z poważaniem,<br>Zespół HR {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Atualização do Status da Oferta - {position}',
                        'content' => '<p>Caro/a <strong>{candidate_name}</strong>,</p><p>Gostaríamos de informá-lo de que o status de sua oferta de emprego para a posição de <strong>{position}</strong> foi atualizado por nossa equipe de RH.</p><p><strong>Detalhes do Status da Oferta:</strong></p><ul><li>Posição: {position}</li><li>Departamento: {department_name}</li><li>Status Atual: {status}</li><li>Data da Oferta: {offer_date}</li><li>Data de Expiração: {expiration_date}</li></ul><p>Se tiver dúvidas sobre esta atualização, entre em contato com nosso departamento de RH.</p><p>Atenciosamente,<br>Equipe de RH da {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Atualização do Status da Oferta - {position}',
                        'content' => '<p>Caro/a <strong>{candidate_name}</strong>,</p><p>Gostaríamos de informá-lo de que o status de sua oferta de emprego para a posição de <strong>{position}</strong> foi atualizado por nossa equipe de RH.</p><p><strong>Detalhes do Status da Oferta:</strong></p><ul><li>Posição: {position}</li><li>Departamento: {department_name}</li><li>Status Atual: {status}</li><li>Data da Oferta: {offer_date}</li><li>Data de Expiração: {expiration_date}</li></ul><p>Se tiver dúvidas sobre esta atualização, entre em contato com nosso departamento de RH.</p><p>Atenciosamente,<br>Equipe de RH da {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Обновление Статуса Предложения - {position}',
                        'content' => '<p>Уважаемый/ая <strong>{candidate_name}</strong>,</p><p>Мы хотим сообщить вам, что статус вашего предложения о работе на должность <strong>{position}</strong> был обновлен нашим отделом кадров.</p><p><strong>Детали Статуса Предложения:</strong></p><ul><li>Должность: {position}</li><li>Отдел: {department_name}</li><li>Текущий Статус: {status}</li><li>Дата Предложения: {offer_date}</li><li>Срок Действия: {expiration_date}</li></ul><p>Если у вас есть вопросы по этому обновлению, свяжитесь с нашим отделом кадров.</p><p>С уважением,<br>Команда HR {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'Teklif Durumu Güncelleme - {position}',
                        'content' => '<p>Sayın <strong>{candidate_name}</strong>,</p><p><strong>{position}</strong> pozisyonu için iş teklifinizin durumunun İK ekibimiz tarafından güncellendiğini bildirmek isteriz.</p><p><strong>Teklif Durumu Detayları:</strong></p><ul><li>Pozisyon: {position}</li><li>Departman: {department_name}</li><li>Mevcut Durum: {status}</li><li>Teklif Tarihi: {offer_date}</li><li>Son Geçerlilik Tarihi: {expiration_date}</li></ul><p>Bu güncelleme hakkında sorularınız varsa, İK departmanımızla iletişime geçin.</p><p>Saygılarımızla,<br>{app_name} İK Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '邀请状态更新 - {position}',
                        'content' => '<p>尊敬的 <strong>{candidate_name}</strong>，</p><p>我们想通知您，您的<strong>{position}</strong>职位工作邀请的状态已由我们的HR团队更新。</p><p><strong>邀请状态详情：</strong></p><ul><li>职位：{position}</li><li>部门：{department_name}</li><li>当前状态：{status}</li><li>邀请日期：{offer_date}</li><li>截止日期：{expiration_date}</li></ul><p>如对此更新有任何疑问，请联系我们的HR部门。</p><p>此致，<br>{app_name} 人力资源团队</p>',
                    ],
                ],
            ],
            [
                'name' => 'Payslip Generate',
                'from' => $fromName,
                'translations' => [
                    'en' => [
                        'subject' => 'Payslip Generated - {pay_period_start} to {pay_period_end}',
                        'content' => '<p>Dear <strong>{employee_name}</strong>,</p><p>Your payslip for the period <strong>{pay_period_start}</strong> to <strong>{pay_period_end}</strong> has been generated and is now available.</p><p><strong>Payslip Details:</strong></p><ul><li>Payslip Number: {payslip_number}</li><li>Pay Period: {pay_period_start} to {pay_period_end}</li><li>Pay Date: {pay_date}</li><li>Gross Pay: {gross_pay}</li><li>Net Pay: {net_pay}</li></ul><p>You can download your payslip from the employee portal or contact HR for assistance.</p><p>Best regards,<br>{app_name} HR Team</p>',
                    ],
                    'es' => [
                        'subject' => 'Nómina Generada - {pay_period_start} a {pay_period_end}',
                        'content' => '<p>Estimado/a <strong>{employee_name}</strong>,</p><p>Su nómina para el período <strong>{pay_period_start}</strong> a <strong>{pay_period_end}</strong> ha sido generada y ya está disponible.</p><p><strong>Detalles de la Nómina:</strong></p><ul><li>Número de Nómina: {payslip_number}</li><li>Período de Pago: {pay_period_start} a {pay_period_end}</li><li>Fecha de Pago: {pay_date}</li><li>Salario Bruto: {gross_pay}</li><li>Salario Neto: {net_pay}</li></ul><p>Puede descargar su nómina desde el portal de empleados o contactar a RRHH para asistencia.</p><p>Saludos cordiales,<br>Equipo de RRHH de {app_name}</p>',
                    ],
                    'ar' => [
                        'subject' => 'تم إنشاء قسيمة الراتب - {pay_period_start} إلى {pay_period_end}',
                        'content' => '<p>عزيزي/عزيزتي <strong>{employee_name}</strong>،</p><p>تم إنشاء قسيمة الراتب الخاصة بك للفترة من <strong>{pay_period_start}</strong> إلى <strong>{pay_period_end}</strong> وهي متاحة الآن.</p><p><strong>تفاصيل قسيمة الراتب:</strong></p><ul><li>رقم القسيمة: {payslip_number}</li><li>فترة الدفع: {pay_period_start} إلى {pay_period_end}</li><li>تاريخ الدفع: {pay_date}</li><li>الراتب الإجمالي: {gross_pay}</li><li>صافي الراتب: {net_pay}</li></ul><p>يمكنك تنزيل قسيمة الراتب من بوابة الموظفين أو الاتصال بالموارد البشرية للمساعدة.</p><p>مع أطيب التحيات،<br>فريق الموارد البشرية في {app_name}</p>',
                    ],
                    'da' => [
                        'subject' => 'Lønseddel Genereret - {pay_period_start} til {pay_period_end}',
                        'content' => '<p>Kære <strong>{employee_name}</strong>,</p><p>Din lønseddel for perioden <strong>{pay_period_start}</strong> til <strong>{pay_period_end}</strong> er blevet genereret og er nu tilgængelig.</p><p><strong>Lønseddeldetaljer:</strong></p><ul><li>Lønseddelnummer: {payslip_number}</li><li>Lønperiode: {pay_period_start} til {pay_period_end}</li><li>Lønningsdato: {pay_date}</li><li>Bruttoløn: {gross_pay}</li><li>Nettoløn: {net_pay}</li></ul><p>Du kan downloade din lønseddel fra medarbejderportalen eller kontakte HR for hjælp.</p><p>Med venlig hilsen,<br>{app_name} HR Team</p>',
                    ],
                    'de' => [
                        'subject' => 'Gehaltsabrechnung Erstellt - {pay_period_start} bis {pay_period_end}',
                        'content' => '<p>Liebe/r <strong>{employee_name}</strong>,</p><p>Ihre Gehaltsabrechnung für den Zeitraum <strong>{pay_period_start}</strong> bis <strong>{pay_period_end}</strong> wurde erstellt und ist jetzt verfügbar.</p><p><strong>Gehaltsabrechnungsdetails:</strong></p><ul><li>Abrechnungsnummer: {payslip_number}</li><li>Abrechnungszeitraum: {pay_period_start} bis {pay_period_end}</li><li>Zahlungsdatum: {pay_date}</li><li>Bruttogehalt: {gross_pay}</li><li>Nettogehalt: {net_pay}</li></ul><p>Sie können Ihre Gehaltsabrechnung über das Mitarbeiterportal herunterladen oder sich an die Personalabteilung wenden.</p><p>Mit freundlichen Grüßen,<br>{app_name} HR Team</p>',
                    ],
                    'fr' => [
                        'subject' => 'Fiche de Paie Générée - {pay_period_start} à {pay_period_end}',
                        'content' => '<p>Cher/Chère <strong>{employee_name}</strong>,</p><p>Votre fiche de paie pour la période du <strong>{pay_period_start}</strong> au <strong>{pay_period_end}</strong> a été générée et est maintenant disponible.</p><p><strong>Détails de la Fiche de Paie:</strong></p><ul><li>Numéro de Fiche: {payslip_number}</li><li>Période de Paie: {pay_period_start} à {pay_period_end}</li><li>Date de Paiement: {pay_date}</li><li>Salaire Brut: {gross_pay}</li><li>Salaire Net: {net_pay}</li></ul><p>Vous pouvez télécharger votre fiche de paie depuis le portail employé ou contacter les RH pour assistance.</p><p>Cordialement,<br>Équipe RH de {app_name}</p>',
                    ],
                    'he' => [
                        'subject' => 'תלוש שכר נוצר - {pay_period_start} עד {pay_period_end}',
                        'content' => '<p>יקר/ה <strong>{employee_name}</strong>,</p><p>תלוש השכר שלך לתקופה <strong>{pay_period_start}</strong> עד <strong>{pay_period_end}</strong> נוצר וזמין כעת.</p><p><strong>פרטי תלוש השכר:</strong></p><ul><li>מספר תלוש: {payslip_number}</li><li>תקופת תשלום: {pay_period_start} עד {pay_period_end}</li><li>תאריך תשלום: {pay_date}</li><li>שכר ברוטו: {gross_pay}</li><li>שכר נטו: {net_pay}</li></ul><p>ניתן להוריד את תלוש השכר מפורטל העובדים או ליצור קשר עם משאבי אנוש לסיוע.</p><p>בברכה,<br>צוות משאבי אנוש {app_name}</p>',
                    ],
                    'it' => [
                        'subject' => 'Busta Paga Generata - {pay_period_start} a {pay_period_end}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>La tua busta paga per il periodo dal <strong>{pay_period_start}</strong> al <strong>{pay_period_end}</strong> è stata generata ed è ora disponibile.</p><p><strong>Dettagli della Busta Paga:</strong></p><ul><li>Numero Busta Paga: {payslip_number}</li><li>Periodo di Pagamento: {pay_period_start} a {pay_period_end}</li><li>Data di Pagamento: {pay_date}</li><li>Retribuzione Lorda: {gross_pay}</li><li>Retribuzione Netta: {net_pay}</li></ul><p>Puoi scaricare la tua busta paga dal portale dipendenti o contattare l\'HR per assistenza.</p><p>Cordiali saluti,<br>Team HR di {app_name}</p>',
                    ],
                    'ja' => [
                        'subject' => '給与明細書作成 - {pay_period_start}から{pay_period_end}',
                        'content' => '<p><strong>{employee_name}</strong>様、</p><p><strong>{pay_period_start}</strong>から<strong>{pay_period_end}</strong>までの給与明細書が作成され、現在利用可能です。</p><p><strong>給与明細書の詳細：</strong></p><ul><li>明細書番号: {payslip_number}</li><li>支給期間: {pay_period_start}から{pay_period_end}</li><li>支給日: {pay_date}</li><li>総支給額: {gross_pay}</li><li>差引支給額: {net_pay}</li></ul><p>従業員ポータルから給与明細書をダウンロードするか、人事部にお問い合わせください。</p><p>よろしくお願いいたします、<br>{app_name} 人事チーム</p>',
                    ],
                    'nl' => [
                        'subject' => 'Loonstrook Gegenereerd - {pay_period_start} tot {pay_period_end}',
                        'content' => '<p>Beste <strong>{employee_name}</strong>,</p><p>Je loonstrook voor de periode <strong>{pay_period_start}</strong> tot <strong>{pay_period_end}</strong> is gegenereerd en is nu beschikbaar.</p><p><strong>Loonstrookdetails:</strong></p><ul><li>Loonstrooknummer: {payslip_number}</li><li>Loonperiode: {pay_period_start} tot {pay_period_end}</li><li>Betaaldatum: {pay_date}</li><li>Brutoloon: {gross_pay}</li><li>Nettoloon: {net_pay}</li></ul><p>Je kunt je loonstrook downloaden via het werknemersportaal of contact opnemen met HR voor hulp.</p><p>Met vriendelijke groet,<br>{app_name} HR Team</p>',
                    ],
                    'pl' => [
                        'subject' => 'Pasek Wypłaty Wygenerowany - {pay_period_start} do {pay_period_end}',
                        'content' => '<p>Drogi/a <strong>{employee_name}</strong>,</p><p>Twój pasek wypłaty za okres od <strong>{pay_period_start}</strong> do <strong>{pay_period_end}</strong> został wygenerowany i jest teraz dostępny.</p><p><strong>Szczegóły Paska Wypłaty:</strong></p><ul><li>Numer Paska: {payslip_number}</li><li>Okres Wypłaty: {pay_period_start} do {pay_period_end}</li><li>Data Wypłaty: {pay_date}</li><li>Wynagrodzenie Brutto: {gross_pay}</li><li>Wynagrodzenie Netto: {net_pay}</li></ul><p>Możesz pobrać swój pasek wypłaty z portalu pracowniczego lub skontaktować się z HR w celu uzyskania pomocy.</p><p>Z poważaniem,<br>Zespół HR {app_name}</p>',
                    ],
                    'pt' => [
                        'subject' => 'Holerite Gerado - {pay_period_start} a {pay_period_end}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Seu holerite para o período de <strong>{pay_period_start}</strong> a <strong>{pay_period_end}</strong> foi gerado e está disponível agora.</p><p><strong>Detalhes do Holerite:</strong></p><ul><li>Número do Holerite: {payslip_number}</li><li>Período de Pagamento: {pay_period_start} a {pay_period_end}</li><li>Data de Pagamento: {pay_date}</li><li>Salário Bruto: {gross_pay}</li><li>Salário Líquido: {net_pay}</li></ul><p>Você pode baixar seu holerite no portal do funcionário ou entrar em contato com o RH para assistência.</p><p>Atenciosamente,<br>Equipe de RH da {app_name}</p>',
                    ],
                    'pt-BR' => [
                        'subject' => 'Holerite Gerado - {pay_period_start} a {pay_period_end}',
                        'content' => '<p>Caro/a <strong>{employee_name}</strong>,</p><p>Seu holerite para o período de <strong>{pay_period_start}</strong> a <strong>{pay_period_end}</strong> foi gerado e está disponível agora.</p><p><strong>Detalhes do Holerite:</strong></p><ul><li>Número do Holerite: {payslip_number}</li><li>Período de Pagamento: {pay_period_start} a {pay_period_end}</li><li>Data de Pagamento: {pay_date}</li><li>Salário Bruto: {gross_pay}</li><li>Salário Líquido: {net_pay}</li></ul><p>Você pode baixar seu holerite no portal do funcionário ou entrar em contato com o RH para assistência.</p><p>Atenciosamente,<br>Equipe de RH da {app_name}</p>',
                    ],
                    'ru' => [
                        'subject' => 'Расчетный Лист Создан - {pay_period_start} до {pay_period_end}',
                        'content' => '<p>Уважаемый/ая <strong>{employee_name}</strong>,</p><p>Ваш расчетный лист за период с <strong>{pay_period_start}</strong> по <strong>{pay_period_end}</strong> был создан и теперь доступен.</p><p><strong>Детали Расчетного Листа:</strong></p><ul><li>Номер Листа: {payslip_number}</li><li>Период Оплаты: {pay_period_start} до {pay_period_end}</li><li>Дата Выплаты: {pay_date}</li><li>Валовая Зарплата: {gross_pay}</li><li>Чистая Зарплата: {net_pay}</li></ul><p>Вы можете скачать свой расчетный лист с портала сотрудников или связаться с отделом кадров для помощи.</p><p>С уважением,<br>Команда HR {app_name}</p>',
                    ],
                    'tr' => [
                        'subject' => 'Maaş Bordrosu Oluşturuldu - {pay_period_start} - {pay_period_end}',
                        'content' => '<p>Sayın <strong>{employee_name}</strong>,</p><p><strong>{pay_period_start}</strong> - <strong>{pay_period_end}</strong> dönemi için maaş bordronuz oluşturuldu ve artık kullanılabilir.</p><p><strong>Bordro Detayları:</strong></p><ul><li>Bordro Numarası: {payslip_number}</li><li>Ödeme Dönemi: {pay_period_start} - {pay_period_end}</li><li>Ödeme Tarihi: {pay_date}</li><li>Brüt Maaş: {gross_pay}</li><li>Net Maaş: {net_pay}</li></ul><p>Maaş bordronuzu çalışan portalından indirebilir veya yardım için İK ile iletişime geçebilirsiniz.</p><p>Saygılarımızla,<br>{app_name} İK Ekibi</p>',
                    ],
                    'zh' => [
                        'subject' => '工资单已生成 - {pay_period_start}至{pay_period_end}',
                        'content' => '<p>尊敬的 <strong>{employee_name}</strong>，</p><p>您<strong>{pay_period_start}</strong>至<strong>{pay_period_end}</strong>期间的工资单已生成，现在可以查看。</p><p><strong>工资单详情：</strong></p><ul><li>工资单编号：{payslip_number}</li><li>工资期间：{pay_period_start}至{pay_period_end}</li><li>发薪日期：{pay_date}</li><li>应发工资：{gross_pay}</li><li>实发工资：{net_pay}</li></ul><p>您可以从员工门户下载工资单或联系HR部门寻求帮助。</p><p>此致，<br>{app_name} 人力资源团队</p>',
                    ],
                ],
            ],
        ];

        foreach ($templates as $templateData) {
            $template = EmailTemplate::create([
                'name' => $templateData['name'],
                'from' => $templateData['from'],
                'user_id' => 1,
            ]);

            foreach ($langCodes as $langCode) {
                $translation = $templateData['translations'][$langCode] ?? $templateData['translations']['en'];

                EmailTemplateLang::create([
                    'parent_id' => $template->id,
                    'lang' => $langCode,
                    'subject' => $translation['subject'],
                    'content' => $translation['content'],
                ]);
            }

            UserEmailTemplate::create([
                'template_id' => $template->id,
                'user_id' => 1,
                'is_active' => true,
            ]);
        }
    }
}
