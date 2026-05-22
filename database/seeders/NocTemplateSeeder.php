<?php

namespace Database\Seeders;

use App\Models\NocTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class NocTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Get all companies
        $companies = User::where('type', 'company')->get();

        if ($companies->isEmpty()) {
            $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
            return;
        }

        $languages = [
            'ar' => 'شهادة عدم ممانعة',
            'da' => 'Ingen indsigelse certifikat',
            'de' => 'Unbedenklichkeitsbescheinigung',
            'en' => 'No Objection Certificate',
            'es' => 'Certificado de No Objeción',
            'fr' => 'Certificat de Non-Objection',
            'he' => 'תעודת אי התנגדות',
            'it' => 'Certificato di Non Obiezione',
            'ja' => '異議なし証明書',
            'nl' => 'Geen Bezwaar Certificaat',
            'pl' => 'Certyfikat Braku Sprzeciwu',
            'pt' => 'Certificado de Não Objeção',
            'pt-BR' => 'Certificado de Não Objeção',
            'ru' => 'Справка об отсутствии возражений',
            'tr' => 'İtiraz Yok Belgesi',
            'zh' => '无异议证明'
        ];

        $templates = [
            'ar' => '<h2>شهادة عدم ممانعة</h2><p>التاريخ: {date}</p><p>إلى من يهمه الأمر،</p><p>نشهد بأن <strong>{employee_name}</strong> يعمل حالياً لدى {company_name} بمنصب {designation}.</p><p>ليس لدينا أي اعتراض على الموظف المذكور أعلاه لأي أغراض رسمية.</p><p>مع خالص التقدير،<br><strong>قسم الموارد البشرية</strong><br>{company_name}</p>',
            'da' => '<h2>Ingen indsigelse certifikat</h2><p>Dato: {date}</p><p>Til hvem det måtte vedkomme,</p><p>Dette er for at bekræfte, at <strong>{employee_name}</strong> i øjeblikket er ansat hos {company_name} som {designation}.</p><p>Vi har ingen indvendinger mod ovennævnte medarbejder til officielle formål.</p><p>Med venlig hilsen,<br><strong>HR-afdelingen</strong><br>{company_name}</p>',
            'de' => '<h2>Unbedenklichkeitsbescheinigung</h2><p>Datum: {date}</p><p>An wen es betrifft,</p><p>Hiermit wird bestätigt, dass <strong>{employee_name}</strong> derzeit bei {company_name} als {designation} beschäftigt ist.</p><p>Wir haben keine Einwände gegen den oben genannten Mitarbeiter für offizielle Zwecke.</p><p>Mit freundlichen Grüßen,<br><strong>Personalabteilung</strong><br>{company_name}</p>',
            'en' => '<h2>No Objection Certificate</h2><p>Date: {date}</p><p>To Whom It May Concern,</p><p>This is to certify that <strong>{employee_name}</strong> is currently employed with {company_name} as {designation}.</p><p>We have no objection to the above mentioned employee for any official purposes.</p><p>Sincerely,<br><strong>HR Department</strong><br>{company_name}</p>',
            'es' => '<h2>Certificado de No Objeción</h2><p>Fecha: {date}</p><p>A quien corresponda,</p><p>Por la presente certificamos que <strong>{employee_name}</strong> está actualmente empleado en {company_name} como {designation}.</p><p>No tenemos objeción alguna al empleado mencionado anteriormente para cualquier propósito oficial.</p><p>Atentamente,<br><strong>Departamento de RRHH</strong><br>{company_name}</p>',
            'fr' => '<h2>Certificat de Non-Objection</h2><p>Date: {date}</p><p>À qui de droit,</p><p>Ceci certifie que <strong>{employee_name}</strong> est actuellement employé chez {company_name} en tant que {designation}.</p><p>Nous n\'avons aucune objection concernant l\'employé mentionné ci-dessus à des fins officielles.</p><p>Cordialement,<br><strong>Département RH</strong><br>{company_name}</p>',
            'he' => '<h2>תעודת אי התנגדות</h2><p>תאריך: {date}</p><p>למי שזה נוגע,</p><p>זאת להעיד כי <strong>{employee_name}</strong> מועסק כעת ב-{company_name} בתפקיד {designation}.</p><p>אין לנו התנגדות לעובד הנ"ל לכל מטרה רשמית.</p><p>בכבוד רב,<br><strong>מחלקת משאבי אנוש</strong><br>{company_name}</p>',
            'it' => '<h2>Certificato di Non Obiezione</h2><p>Data: {date}</p><p>A chi di competenza,</p><p>Si certifica che <strong>{employee_name}</strong> è attualmente impiegato presso {company_name} come {designation}.</p><p>Non abbiamo obiezioni riguardo al suddetto dipendente per scopi ufficiali.</p><p>Cordiali saluti,<br><strong>Dipartimento HR</strong><br>{company_name}</p>',
            'ja' => '<h2>異議なし証明書</h2><p>日付: {date}</p><p>関係者各位</p><p><strong>{employee_name}</strong>が現在{company_name}で{designation}として雇用されていることを証明いたします。</p><p>上記従業員に関して、公的な目的での異議はございません。</p><p>敬具<br><strong>人事部</strong><br>{company_name}</p>',
            'nl' => '<h2>Geen Bezwaar Certificaat</h2><p>Datum: {date}</p><p>Aan wie het betreft,</p><p>Hierbij wordt bevestigd dat <strong>{employee_name}</strong> momenteel werkzaam is bij {company_name} als {designation}.</p><p>Wij hebben geen bezwaar tegen bovengenoemde werknemer voor officiële doeleinden.</p><p>Met vriendelijke groet,<br><strong>HR Afdeling</strong><br>{company_name}</p>',
            'pl' => '<h2>Certyfikat Braku Sprzeciwu</h2><p>Data: {date}</p><p>Do kogo to dotyczy,</p><p>Niniejszym poświadczamy, że <strong>{employee_name}</strong> jest obecnie zatrudniony w {company_name} na stanowisku {designation}.</p><p>Nie mamy sprzeciwu wobec wyżej wymienionego pracownika w celach urzędowych.</p><p>Z poważaniem,<br><strong>Dział HR</strong><br>{company_name}</p>',
            'pt' => '<h2>Certificado de Não Objeção</h2><p>Data: {date}</p><p>A quem possa interessar,</p><p>Certificamos que <strong>{employee_name}</strong> está atualmente empregado na {company_name} como {designation}.</p><p>Não temos objeção ao funcionário mencionado acima para fins oficiais.</p><p>Atenciosamente,<br><strong>Departamento de RH</strong><br>{company_name}</p>',
            'pt-BR' => '<h2>Certificado de Não Objeção</h2><p>Data: {date}</p><p>A quem possa interessar,</p><p>Certificamos que <strong>{employee_name}</strong> está atualmente empregado na {company_name} como {designation}.</p><p>Não temos objeção ao funcionário mencionado acima para fins oficiais.</p><p>Atenciosamente,<br><strong>Departamento de RH</strong><br>{company_name}</p>',
            'ru' => '<h2>Справка об отсутствии возражений</h2><p>Дата: {date}</p><p>Кого это касается,</p><p>Настоящим подтверждаем, что <strong>{employee_name}</strong> в настоящее время работает в {company_name} в должности {designation}.</p><p>У нас нет возражений против вышеупомянутого сотрудника для официальных целей.</p><p>С уважением,<br><strong>Отдел кадров</strong><br>{company_name}</p>',
            'tr' => '<h2>İtiraz Yok Belgesi</h2><p>Tarih: {date}</p><p>İlgili Makama,</p><p><strong>{employee_name}</strong> adlı kişinin {company_name} şirketinde {designation} pozisyonunda çalıştığını onaylarız.</p><p>Yukarıda belirtilen çalışanımız için resmi amaçlar doğrultusunda herhangi bir itirazımız bulunmamaktadır.</p><p>Saygılarımızla,<br><strong>İnsan Kaynakları Departmanı</strong><br>{company_name}</p>',
            'zh' => '<h2>无异议证明</h2><p>日期：{date}</p><p>致相关人员：</p><p>兹证明<strong>{employee_name}</strong>目前在{company_name}担任{designation}职位。</p><p>我们对上述员工用于官方目的无任何异议。</p><p>此致<br><strong>人力资源部</strong><br>{company_name}</p>'
        ];

        $variables = json_encode(['date', 'company_name', 'employee_name', 'designation']);

        foreach ($companies as $company) {
            foreach ($languages as $code => $title) {
                try {
                    NocTemplate::updateOrCreate(
                        [
                            'language' => $code,
                            'created_by' => $company->id
                        ],
                        [
                            'content' => $templates[$code] ?? $templates['en'],
                            'variables' => $variables
                        ]
                    );
                } catch (\Exception $e) {
                    $this->command->error('Failed to create NOC template for language: ' . $code . ' and company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('NocTemplate seeder completed successfully!');
    }
}