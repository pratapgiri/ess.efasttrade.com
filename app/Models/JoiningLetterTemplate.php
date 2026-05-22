<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class JoiningLetterTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'language',
        'content',
        'variables',
        'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function getTemplate($language, $createdBy = null)
    {
        return self::where('language', $language)
            ->where('created_by', $createdBy)
            ->first();
    }

    public static function createTemplatesForCompany($companyId)
    {
        $templates = [
            'ar' => '<h2>خطاب الانضمام</h2><p>التاريخ: {date}</p><p>عزيزي/عزيزتي <strong>{employee_name}</strong>،</p><p>يسعدنا أن نرحب بك في {company_name} بصفتك {designation}.</p><p>تاريخ بدء العمل: <strong>{joining_date}</strong><br>الراتب: <strong>{salary}</strong><br>القسم: <strong>{department}</strong></p><p>نتطلع إلى مساهمتك القيمة في نجاح شركتنا.</p><p>مع أطيب التحيات،<br><strong>قسم الموارد البشرية</strong><br>{company_name}</p>',
            'da' => '<h2>Tiltrædelsesbreve</h2><p>Dato: {date}</p><p>Kære <strong>{employee_name}</strong>,</p><p>Vi er glade for at byde dig velkommen til {company_name} som {designation}.</p><p>Startdato: <strong>{joining_date}</strong><br>Løn: <strong>{salary}</strong><br>Afdeling: <strong>{department}</strong></p><p>Vi ser frem til dit værdifulde bidrag til vores virksomheds succes.</p><p>Med venlig hilsen,<br><strong>HR-afdelingen</strong><br>{company_name}</p>',
            'de' => '<h2>Beitrittsschreiben</h2><p>Datum: {date}</p><p>Liebe/r <strong>{employee_name}</strong>,</p><p>Wir freuen uns, Sie bei {company_name} als {designation} willkommen zu heißen.</p><p>Startdatum: <strong>{joining_date}</strong><br>Gehalt: <strong>{salary}</strong><br>Abteilung: <strong>{department}</strong></p><p>Wir freuen uns auf Ihren wertvollen Beitrag zum Erfolg unseres Unternehmens.</p><p>Mit freundlichen Grüßen,<br><strong>Personalabteilung</strong><br>{company_name}</p>',
            'en' => '<h2>Joining Letter</h2><p>Date: {date}</p><p>Dear <strong>{employee_name}</strong>,</p><p>We are pleased to welcome you to {company_name} as {designation}.</p><p>Joining Date: <strong>{joining_date}</strong><br>Salary: <strong>{salary}</strong><br>Department: <strong>{department}</strong></p><p>We look forward to your valuable contribution to our company\'s success.</p><p>Best regards,<br><strong>HR Department</strong><br>{company_name}</p>',
            'es' => '<h2>Carta de Incorporación</h2><p>Fecha: {date}</p><p>Estimado/a <strong>{employee_name}</strong>,</p><p>Nos complace darle la bienvenida a {company_name} como {designation}.</p><p>Fecha de Incorporación: <strong>{joining_date}</strong><br>Salario: <strong>{salary}</strong><br>Departamento: <strong>{department}</strong></p><p>Esperamos con interés su valiosa contribución al éxito de nuestra empresa.</p><p>Saludos cordiales,<br><strong>Departamento de RRHH</strong><br>{company_name}</p>',
            'fr' => '<h2>Lettre d\'Adhésion</h2><p>Date: {date}</p><p>Cher/Chère <strong>{employee_name}</strong>,</p><p>Nous sommes heureux de vous accueillir chez {company_name} en tant que {designation}.</p><p>Date d\'Entrée: <strong>{joining_date}</strong><br>Salaire: <strong>{salary}</strong><br>Département: <strong>{department}</strong></p><p>Nous attendons avec impatience votre précieuse contribution au succès de notre entreprise.</p><p>Cordialement,<br><strong>Département RH</strong><br>{company_name}</p>',
            'he' => '<h2>מכתב הצטרפות</h2><p>תאריך: {date}</p><p><strong>{employee_name}</strong> יקר/ה,</p><p>אנו שמחים לקבל אותך ל-{company_name} בתפקיד {designation}.</p><p>תאריך התחלה: <strong>{joining_date}</strong><br>משכורת: <strong>{salary}</strong><br>מחלקה: <strong>{department}</strong></p><p>אנו מצפים לתרומתך החשובה להצלחת החברה שלנו.</p><p>בברכה,<br><strong>מחלקת משאבי אנוש</strong><br>{company_name}</p>',
            'it' => '<h2>Lettera di Adesione</h2><p>Data: {date}</p><p>Caro/a <strong>{employee_name}</strong>,</p><p>Siamo lieti di darti il benvenuto in {company_name} come {designation}.</p><p>Data di Inizio: <strong>{joining_date}</strong><br>Stipendio: <strong>{salary}</strong><br>Dipartimento: <strong>{department}</strong></p><p>Non vediamo l\'ora del tuo prezioso contributo al successo della nostra azienda.</p><p>Cordiali saluti,<br><strong>Dipartimento HR</strong><br>{company_name}</p>',
            'ja' => '<h2>入社通知書</h2><p>日付: {date}</p><p><strong>{employee_name}</strong>様</p><p>{company_name}に{designation}としてご入社いただき、心より歓迎いたします。</p><p>入社日: <strong>{joining_date}</strong><br>給与: <strong>{salary}</strong><br>部署: <strong>{department}</strong></p><p>弊社の成功への貴重な貢献を楽しみにしております。</p><p>敬具<br><strong>人事部</strong><br>{company_name}</p>',
            'nl' => '<h2>Toetredingsbrief</h2><p>Datum: {date}</p><p>Beste <strong>{employee_name}</strong>,</p><p>We zijn verheugd u te verwelkomen bij {company_name} als {designation}.</p><p>Startdatum: <strong>{joining_date}</strong><br>Salaris: <strong>{salary}</strong><br>Afdeling: <strong>{department}</strong></p><p>We kijken uit naar uw waardevolle bijdrage aan het succes van ons bedrijf.</p><p>Met vriendelijke groet,<br><strong>HR Afdeling</strong><br>{company_name}</p>',
            'pl' => '<h2>List Dołączenia</h2><p>Data: {date}</p><p>Drogi/a <strong>{employee_name}</strong>,</p><p>Mamy przyjemność powitać Cię w {company_name} na stanowisku {designation}.</p><p>Data Rozpoczęcia: <strong>{joining_date}</strong><br>Wynagrodzenie: <strong>{salary}</strong><br>Dział: <strong>{department}</strong></p><p>Czekamy na Twój cenny wkład w sukces naszej firmy.</p><p>Z poważaniem,<br><strong>Dział HR</strong><br>{company_name}</p>',
            'pt' => '<h2>Carta de Adesão</h2><p>Data: {date}</p><p>Caro/a <strong>{employee_name}</strong>,</p><p>Temos o prazer de dar-lhe as boas-vindas à {company_name} como {designation}.</p><p>Data de Início: <strong>{joining_date}</strong><br>Salário: <strong>{salary}</strong><br>Departamento: <strong>{department}</strong></p><p>Esperamos ansiosamente sua valiosa contribuição para o sucesso da nossa empresa.</p><p>Atenciosamente,<br><strong>Departamento de RH</strong><br>{company_name}</p>',
            'pt-BR' => '<h2>Carta de Adesão</h2><p>Data: {date}</p><p>Caro/a <strong>{employee_name}</strong>,</p><p>Temos o prazer de dar-lhe as boas-vindas à {company_name} como {designation}.</p><p>Data de Início: <strong>{joining_date}</strong><br>Salário: <strong>{salary}</strong><br>Departamento: <strong>{department}</strong></p><p>Esperamos ansiosamente sua valiosa contribuição para o sucesso da nossa empresa.</p><p>Atenciosamente,<br><strong>Departamento de RH</strong><br>{company_name}</p>',
            'ru' => '<h2>Письмо о Присоединении</h2><p>Дата: {date}</p><p>Уважаемый/ая <strong>{employee_name}</strong>,</p><p>Мы рады приветствовать вас в {company_name} на должности {designation}.</p><p>Дата Начала Работы: <strong>{joining_date}</strong><br>Зарплата: <strong>{salary}</strong><br>Отдел: <strong>{department}</strong></p><p>Мы с нетерпением ждем вашего ценного вклада в успех нашей компании.</p><p>С уважением,<br><strong>Отдел кадров</strong><br>{company_name}</p>',
            'tr' => '<h2>Katılım Mektubu</h2><p>Tarih: {date}</p><p>Sayın <strong>{employee_name}</strong>,</p><p>Sizi {company_name} şirketinde {designation} pozisyonunda karşılamaktan memnuniyet duyuyoruz.</p><p>İşe Başlama Tarihi: <strong>{joining_date}</strong><br>Maaş: <strong>{salary}</strong><br>Departman: <strong>{department}</strong></p><p>Şirketimizin başarısına değerli katkınızı dört gözle bekliyoruz.</p><p>Saygılarımızla,<br><strong>İnsan Kaynakları Departmanı</strong><br>{company_name}</p>',
            'zh' => '<h2>入职信</h2><p>日期：{date}</p><p>亲爱的<strong>{employee_name}</strong>，</p><p>我们很高兴欢迎您加入{company_name}，担任{designation}职位。</p><p>入职日期：<strong>{joining_date}</strong><br>薪资：<strong>{salary}</strong><br>部门：<strong>{department}</strong></p><p>我们期待您为公司成功做出宝贵贡献。</p><p>此致<br><strong>人力资源部</strong><br>{company_name}</p>'
        ];

        $variables = json_encode(['date', 'company_name', 'employee_name', 'designation', 'joining_date', 'salary', 'department']);

        try {
            foreach ($templates as $language => $content) {
                self::updateOrCreate(
                    [
                        'language' => $language,
                        'created_by' => $companyId
                    ],
                    [
                        'content' => $content,
                        'variables' => $variables
                    ]
                );
            }
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create Joining Letter templates for company ID: ' . $companyId . '. Error: ' . $e->getMessage());
            return false;
        }
    }
}