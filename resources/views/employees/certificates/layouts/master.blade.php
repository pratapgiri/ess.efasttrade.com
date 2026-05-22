<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Employee Certificate')</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        .certificate-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
            min-height: 100vh;
        }

        .certificate-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
        }

        .certificate-title {
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }

        .certificate-content {
            margin: 40px 0;
            text-align: justify;
            font-size: 16px;
            line-height: 1.8;
        }

        .employee-details {
            background: #f8f9fa;
            padding: 20px;
            border-left: 4px solid #3498db;
            margin: 20px 0;
        }

        @media print {
            body {
                margin: 0;
            }

            .certificate-container {
                padding: 20px;
            }
        }
    </style>

    @yield('additional-styles')
</head>

<body>
    <div class="certificate-container" id="@yield('element-id', 'certificate')">
        <div class="certificate-header">
            <div class="company-name">{{ $companyName ?? 'Company Name' }}</div>
            <div class="certificate-title">@yield('certificate-type', 'Certificate')</div>
        </div>

        <div class="certificate-content">
            @yield('content')
        </div>

        @hasSection('employee-details')
            <div class="employee-details">
                @yield('employee-details')
            </div>
        @endif
    </div>

    @yield('scripts')
</body>

</html>
