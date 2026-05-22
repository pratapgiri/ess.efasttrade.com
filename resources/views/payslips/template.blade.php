<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $payrollEntry->employee->name }}</title>
    <style>
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #333;
            padding: 0;
        }

        .header {
            text-align: center;
            padding: 15px;
            border-bottom: 1px solid #333;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .payslip-title {
            font-size: 14px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            font-weight: bold;
            background-color: #fafafa;
        }

        .section-header {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: center;
        }

        .amount {
            text-align: right;
        }

        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .net-salary-row {
            font-weight: bold;
            font-size: 13px;
            background-color: #f0f0f0;
        }

        .footer {
            text-align: center;
            padding: 10px;
            font-size: 10px;
            border-top: 1px solid #ccc;
        }
    </style>
</head>

<body>
    <div class="container" id="payslip-content">
        <div class="header">
            <div class="company-name">
                {{ isset($companySettings['titleText']) ? $companySettings['titleText'] : config('app.name', 'HRMGo SaaS') }}
            </div>
            @if (isset($companySettings['companyAddress']))
                <div style="font-size: 10px; margin-top: 5px;">{{ $companySettings['companyAddress'] }}</div>
            @endif
            <div style="font-size: 10px; margin-top: 3px;">
                @if (isset($companySettings['companyEmail']))
                    Email: {{ $companySettings['companyEmail'] }}
                @endif
                @if (isset($companySettings['companyMobile']))
                    | Phone: {{ $companySettings['companyMobile'] }}
                @endif
            </div>
            <div class="payslip-title" style="margin-top: 10px;">Salary Slip</div>
            <div>{{ $payrollEntry->payrollRun->pay_period_start->format('F Y') }}</div>
        </div>

        <table>
            <tr>
                <th colspan="4" class="section-header">Employee Information</th>
            </tr>
            <tr>
                <td width="20%"><strong>Employee Name</strong></td>
                <td width="30%">{{ $payrollEntry->employee->name }}</td>
                <td width="20%"><strong>Employee ID</strong></td>
                <td width="30%">{{ $employeeData->employee_id ?? $payrollEntry->employee->id }}</td>
            </tr>
            <tr>
                <td><strong>Email</strong></td>
                <td>{{ $payrollEntry->employee->email }}</td>
                <td><strong>Pay Period</strong></td>
                <td>{{ $payrollEntry->payrollRun->pay_period_start->format('d M Y') }} -
                    {{ $payrollEntry->payrollRun->pay_period_end->format('d M Y') }}</td>
            </tr>
            <tr>
                <td><strong>Basic Salary</strong></td>
                <td>{{ formatCurrency($payrollEntry->basic_salary) }}</td>
                <td><strong>Generated On</strong></td>
                <td>{{ now()->format('d M Y') }}</td>
            </tr>
            @if (isset($employeeData->bank_name) || isset($employeeData->bank_account_number))
                <tr>
                    <td><strong>Bank Name</strong></td>
                    <td>{{ $employeeData->bank_name ?? 'N/A' }}</td>
                    <td><strong>Account Number</strong></td>
                    <td>{{ $employeeData->account_number ?? 'N/A' }}</td>
                </tr>
            @endif
        </table>

        @if ($payrollEntry->working_days > 0)
            <table>
                <tr>
                    <th colspan="6" class="section-header">Attendance Summary</th>
                </tr>
                <tr>
                    <td><strong>Working Days</strong><br>{{ $payrollEntry->working_days }}</td>
                    <td><strong>Present</strong><br>{{ $payrollEntry->present_days }}</td>
                    <td><strong>Paid Leave</strong><br>{{ $payrollEntry->paid_leave_days }}</td>
                    <td><strong>Unpaid Leave</strong><br>{{ $payrollEntry->unpaid_leave_days }}</td>
                    <td><strong>Half Days</strong><br>{{ $payrollEntry->half_days }}</td>
                    <td><strong>Absent</strong><br>{{ $payrollEntry->absent_days }}</td>
                </tr>
                <tr>
                    <td colspan="6"><strong>Overtime Hours:</strong>
                        {{ number_format($payrollEntry->overtime_hours, 1) }}h</td>
                </tr>
            </table>
        @endif

        @php
            $unpaidLeaveDeduction = $payrollEntry->unpaid_leave_deduction ?? 0;
        @endphp

        @if ($unpaidLeaveDeduction > 0)
            <table>
                <tr>
                    <th colspan="2" class="section-header">Deduction Calculation</th>
                </tr>
                <tr>
                    <td width="70%"><strong>Per Day Salary</strong>
                        ({{ formatCurrency($payrollEntry->basic_salary) }} / {{ $payrollEntry->working_days }} days)
                    </td>
                    <td class="amount">{{ formatCurrency($payrollEntry->per_day_salary ?? 0) }}</td>
                </tr>
                <tr>
                    <td><strong>Unpaid Leave Deduction</strong> (Absent + Half Days + Unpaid Leave)</td>
                    <td class="amount">{{ formatCurrency($unpaidLeaveDeduction) }}</td>
                </tr>
            </table>
        @endif

        <table>
            <tr>
                <th colspan="4" class="section-header">Salary Details</th>
            </tr>
            <tr>
                <th width="35%">Earnings</th>
                <th width="15%" class="amount">Amount</th>
                <th width="35%">Deductions</th>
                <th width="15%" class="amount">Amount</th>
            </tr>
            @php
                $earnings = $payrollEntry->earnings_breakdown ?? [];
                $deductions = $payrollEntry->deductions_breakdown ?? [];

                if ($payrollEntry->overtime_amount > 0) {
                    $earnings['Overtime Amount'] = $payrollEntry->overtime_amount;
                }

                if ($payrollEntry->unpaid_leave_deduction > 0) {
                    $deductions['Unpaid Leave Deduction'] = $payrollEntry->unpaid_leave_deduction;
                }

                $maxRows = max(count($earnings), count($deductions), 1);
                $earningsKeys = array_keys($earnings);
                $deductionsKeys = array_keys($deductions);

                $totalEarnings = $payrollEntry->total_earnings + $payrollEntry->overtime_amount;
                $totalDeductions = $payrollEntry->total_deductions + $payrollEntry->unpaid_leave_deduction;
            @endphp

            @for ($i = 0; $i < $maxRows; $i++)
                <tr>
                    <td>{{ $earningsKeys[$i] ?? '' }}</td>
                    <td class="amount">
                        {{ isset($earningsKeys[$i]) ? formatCurrency($earnings[$earningsKeys[$i]]) : '' }}</td>
                    <td>{{ $deductionsKeys[$i] ?? '' }}</td>
                    <td class="amount">
                        {{ isset($deductionsKeys[$i]) ? formatCurrency($deductions[$deductionsKeys[$i]]) : '' }}</td>
                </tr>
            @endfor

            <tr class="total-row">
                <td><strong>Total Earnings</strong></td>
                <td class="amount"><strong>{{ formatCurrency($totalEarnings) }}</strong></td>
                <td><strong>Total Deductions</strong></td>
                <td class="amount"><strong>{{ formatCurrency($totalDeductions) }}</strong></td>
            </tr>

            <tr class="net-salary-row">
                <td colspan="3"><strong>Net Salary (Take Home)</strong></td>
                <td class="amount"><strong>{{ formatCurrency($payrollEntry->net_pay) }}</strong></td>
            </tr>
        </table>

        <div class="footer">
            <p><strong>This is a computer-generated payslip and does not require a signature.</strong></p>
            <p>For any queries, please contact the HR department.</p>
        </div>
    </div>
</body>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js">
</script>
<script>
    window.addEventListener('load', function() {
        var element = document.querySelector('.container');
        var filename = 'payslip-{{ $payrollEntry->employee->name }}-{{ $payrollEntry->payrollRun->pay_period_start->format("M-Y") }}.pdf';
        var opt = {
            margin: 0.3,
            filename: filename,
            image: { type: 'jpeg', quality: 1 },
            html2canvas: { scale: 2, dpi: 192, letterRendering: true },
            jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save().then(function() {
            setTimeout(function() {
                window.location.href = '{{ route('hr.payslips.index') }}';
            }, 1000);
        });
    });
</script>

</html>
