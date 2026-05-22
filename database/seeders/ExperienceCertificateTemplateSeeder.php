<?php

namespace Database\Seeders;

use App\Models\ExperienceCertificateTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExperienceCertificateTemplateSeeder extends Seeder
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
            'ar' => 'شهادة خبرة',
            'da' => 'Erfaringsbevis',
            'de' => 'Arbeitsbescheinigung',
            'en' => 'Experience Certificate',
            'es' => 'Certificado de Experiencia',
            'fr' => 'Certificat d\'Expérience',
            'he' => 'תעודת ניסיון',
            'it' => 'Certificato di Esperienza',
            'ja' => '経験証明書',
            'nl' => 'Ervaring Certificaat',
            'pl' => 'Świadectwo Doświadczenia',
            'pt' => 'Certificado de Experiência',
            'pt-BR' => 'Certificado de Experiência',
            'ru' => 'Справка о трудовом стаже',
            'tr' => 'Deneyim Belgesi',
            'zh' => '工作经验证明'
        ];

        $templates = [
            'ar' => '<h2>شهادة خبرة</h2><p>التاريخ: {date}</p><p>إلى من يهمه الأمر،</p><p>نشهد بأن <strong>{employee_name}</strong> قد عمل لدى {company_name} بمنصب {designation} من تاريخ {joining_date} إلى {leaving_date}.</p><p>خلال فترة عمله، أظهر الموظف المذكور أداءً ممتازاً ومهارات مهنية عالية. لقد كان موظفاً مخلصاً ومسؤولاً.</p><p>نتمنى له التوفيق في مساعيه المستقبلية.</p><p>مع خالص التقدير،<br><strong>قسم الموارد البشرية</strong><br>{company_name}</p>',
            'da' => '<h2>Erfaringsbevis</h2><p>Dato: {date}</p><p>Til hvem det måtte vedkomme,</p><p>Dette er for at bekræfte, at <strong>{employee_name}</strong> var ansat hos {company_name} som {designation} fra {joining_date} til {leaving_date}.</p><p>I løbet af ansættelsesperioden viste den nævnte medarbejder fremragende præstation og høje professionelle færdigheder. Han/hun var en dedikeret og ansvarlig medarbejder.</p><p>Vi ønsker ham/hende held og lykke med fremtidige bestræbelser.</p><p>Med venlig hilsen,<br><strong>HR-afdelingen</strong><br>{company_name}</p>',
            'de' => '<h2>Arbeitsbescheinigung</h2><p>Datum: {date}</p><p>An wen es betrifft,</p><p>Hiermit wird bestätigt, dass <strong>{employee_name}</strong> bei {company_name} als {designation} vom {joining_date} bis {leaving_date} beschäftigt war.</p><p>Während der Beschäftigungszeit zeigte der genannte Mitarbeiter hervorragende Leistungen und hohe berufliche Fähigkeiten. Er/Sie war ein engagierter und verantwortungsvoller Mitarbeiter.</p><p>Wir wünschen ihm/ihr alles Gute für zukünftige Unternehmungen.</p><p>Mit freundlichen Grüßen,<br><strong>Personalabteilung</strong><br>{company_name}</p>',
            'en' => '<h2>Experience Certificate</h2><p>Date: {date}</p><p>To Whom It May Concern,</p><p>This is to certify that <strong>{employee_name}</strong> was employed with {company_name} as {designation} from {joining_date} to {leaving_date}.</p><p>During the period of employment, the above-mentioned employee demonstrated excellent performance and high professional skills. He/She was a dedicated and responsible employee.</p><p>We wish him/her all the best for future endeavors.</p><p>Sincerely,<br><strong>HR Department</strong><br>{company_name}</p>',
            'es' => '<h2>Certificado de Experiencia</h2><p>Fecha: {date}</p><p>A quien corresponda,</p><p>Por la presente certificamos que <strong>{employee_name}</strong> estuvo empleado en {company_name} como {designation} desde {joining_date} hasta {leaving_date}.</p><p>Durante el período de empleo, el empleado mencionado demostró un excelente desempeño y altas habilidades profesionales. Fue un empleado dedicado y responsable.</p><p>Le deseamos todo lo mejor para sus futuros proyectos.</p><p>Atentamente,<br><strong>Departamento de RRHH</strong><br>{company_name}</p>',
            'fr' => '<h2>Certificat d\'Expérience</h2><p>Date: {date}</p><p>À qui de droit,</p><p>Ceci certifie que <strong>{employee_name}</strong> était employé chez {company_name} en tant que {designation} du {joining_date} au {leaving_date}.</p><p>Pendant la période d\'emploi, l\'employé susmentionné a démontré d\'excellentes performances et de hautes compétences professionnelles. Il/Elle était un employé dévoué et responsable.</p><p>Nous lui souhaitons tout le meilleur pour ses projets futurs.</p><p>Cordialement,<br><strong>Département RH</strong><br>{company_name}</p>',
            'he' => '<h2>תעודת ניסיון</h2><p>תאריך: {date}</p><p>למי שזה נוגע,</p><p>זאת להעיד כי <strong>{employee_name}</strong> הועסק ב-{company_name} בתפקיד {designation} מ-{joining_date} עד {leaving_date}.</p><p>במהלך תקופת העבודה, העובד הנ"ל הפגין ביצועים מעולים וכישורים מקצועיים גבוהים. הוא/היא היה/הייתה עובד/ת מסור/ה ואחראי/ת.</p><p>אנו מאחלים לו/לה הצלחה בכל המאמצים העתידיים.</p><p>בכבוד רב,<br><strong>מחלקת משאבי אנוש</strong><br>{company_name}</p>',
            'it' => '<h2>Certificato di Esperienza</h2><p>Data: {date}</p><p>A chi di competenza,</p><p>Si certifica che <strong>{employee_name}</strong> è stato impiegato presso {company_name} come {designation} dal {joining_date} al {leaving_date}.</p><p>Durante il periodo di impiego, il suddetto dipendente ha dimostrato prestazioni eccellenti e alte competenze professionali. È stato un dipendente dedicato e responsabile.</p><p>Gli auguriamo tutto il meglio per i futuri progetti.</p><p>Cordiali saluti,<br><strong>Dipartimento HR</strong><br>{company_name}</p>',
            'ja' => '<h2>経験証明書</h2><p>日付: {date}</p><p>関係者各位</p><p><strong>{employee_name}</strong>が{joining_date}から{leaving_date}まで{company_name}で{designation}として雇用されていたことを証明いたします。</p><p>雇用期間中、上記従業員は優秀な成績と高い専門技能を示しました。献身的で責任感のある従業員でした。</p><p>今後の活動における成功をお祈りいたします。</p><p>敬具<br><strong>人事部</strong><br>{company_name}</p>',
            'nl' => '<h2>Ervaring Certificaat</h2><p>Datum: {date}</p><p>Aan wie het betreft,</p><p>Hierbij wordt bevestigd dat <strong>{employee_name}</strong> werkzaam was bij {company_name} als {designation} van {joining_date} tot {leaving_date}.</p><p>Tijdens de dienstperiode toonde bovengenoemde werknemer uitstekende prestaties en hoge professionele vaardigheden. Hij/Zij was een toegewijde en verantwoordelijke werknemer.</p><p>Wij wensen hem/haar het beste toe voor toekomstige ondernemingen.</p><p>Met vriendelijke groet,<br><strong>HR Afdeling</strong><br>{company_name}</p>',
            'pl' => '<h2>Świadectwo Doświadczenia</h2><p>Data: {date}</p><p>Do kogo to dotyczy,</p><p>Niniejszym poświadczamy, że <strong>{employee_name}</strong> był zatrudniony w {company_name} na stanowisku {designation} od {joining_date} do {leaving_date}.</p><p>W okresie zatrudnienia wyżej wymieniony pracownik wykazał się doskonałymi wynikami i wysokimi umiejętnościami zawodowymi. Był oddanym i odpowiedzialnym pracownikiem.</p><p>Życzymy mu/jej powodzenia w przyszłych przedsięwzięciach.</p><p>Z poważaniem,<br><strong>Dział HR</strong><br>{company_name}</p>',
            'pt' => '<h2>Certificado de Experiência</h2><p>Data: {date}</p><p>A quem possa interessar,</p><p>Certificamos que <strong>{employee_name}</strong> esteve empregado na {company_name} como {designation} de {joining_date} a {leaving_date}.</p><p>Durante o período de emprego, o funcionário mencionado demonstrou excelente desempenho e altas habilidades profissionais. Foi um funcionário dedicado e responsável.</p><p>Desejamos-lhe tudo de bom para empreendimentos futuros.</p><p>Atenciosamente,<br><strong>Departamento de RH</strong><br>{company_name}</p>',
            'pt-BR' => '<h2>Certificado de Experiência</h2><p>Data: {date}</p><p>A quem possa interessar,</p><p>Certificamos que <strong>{employee_name}</strong> esteve empregado na {company_name} como {designation} de {joining_date} a {leaving_date}.</p><p>Durante o período de emprego, o funcionário mencionado demonstrou excelente desempenho e altas habilidades profissionais. Foi um funcionário dedicado e responsável.</p><p>Desejamos-lhe tudo de bom para empreendimentos futuros.</p><p>Atenciosamente,<br><strong>Departamento de RH</strong><br>{company_name}</p>',
            'ru' => '<h2>Справка о трудовом стаже</h2><p>Дата: {date}</p><p>Кого это касается,</p><p>Настоящим подтверждаем, что <strong>{employee_name}</strong> работал в {company_name} в должности {designation} с {joining_date} по {leaving_date}.</p><p>В период трудоустройства вышеупомянутый сотрудник продемонстрировал отличные результаты и высокие профессиональные навыки. Он/Она был/была преданным и ответственным сотрудником.</p><p>Желаем ему/ей всего наилучшего в будущих начинаниях.</p><p>С уважением,<br><strong>Отдел кадров</strong><br>{company_name}</p>',
            'tr' => '<h2>Deneyim Belgesi</h2><p>Tarih: {date}</p><p>İlgili Makama,</p><p><strong>{employee_name}</strong> adlı kişinin {joining_date} tarihinden {leaving_date} tarihine kadar {company_name} şirketinde {designation} pozisyonunda çalıştığını onaylarız.</p><p>İstihdam süresi boyunca yukarıda belirtilen çalışan mükemmel performans ve yüksek mesleki beceriler sergilemiştir. Kendisi özverili ve sorumlu bir çalışandı.</p><p>Gelecekteki çalışmalarında kendisine başarılar dileriz.</p><p>Saygılarımızla,<br><strong>İnsan Kaynakları Departmanı</strong><br>{company_name}</p>',
            'zh' => '<h2>工作经验证明</h2><p>日期：{date}</p><p>致相关人员：</p><p>兹证明<strong>{employee_name}</strong>于{joining_date}至{leaving_date}期间在{company_name}担任{designation}职位。</p><p>在任职期间，上述员工表现出色，具备高水平的专业技能。他/她是一位敬业负责的员工。</p><p>祝愿他/她在未来的工作中一切顺利。</p><p>此致<br><strong>人力资源部</strong><br>{company_name}</p>'
        ];

        $variables = json_encode(['date', 'company_name', 'employee_name', 'designation', 'joining_date', 'leaving_date']);

        foreach ($companies as $company) {
            foreach ($languages as $code => $title) {
                try {
                    ExperienceCertificateTemplate::updateOrCreate(
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
                    $this->command->error('Failed to create Experience Certificate template for language: ' . $code . ' and company: ' . $company->name);
                    continue;
                }
            }
        }

        $this->command->info('ExperienceCertificateTemplate seeder completed successfully!');
    }
}