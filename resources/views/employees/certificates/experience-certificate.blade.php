@extends('employees.certificates.layouts.master')

@section('title', ucfirst(str_replace('_', ' ', $type)))
@section('certificate-type', ucfirst(str_replace('_', ' ', $type)))
@section('element-id', 'boxes')

@section('content')
    {!! $content !!}
@endsection

@section('scripts')
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js">
    </script>
    <script>
        function closeScript() {
            setTimeout(function() {
                window.open(window.location, '_self').close();
            }, 1000);
        }

        window.addEventListener('load', function() {
            var element = document.getElementById('boxes');
            var format = '{{ $format ?? 'pdf' }}';

            if (format === 'doc') {
                // Generate Word document
                var content = element.innerHTML;
                var blob = new Blob(['\ufeff', content], {
                    type: 'application/msword'
                });
                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = '{{ $user->name }}.doc';
                link.click();
                closeScript();
            } else {
                // Generate PDF
                var opt = {
                    margin: 0.3,
                    filename: '{{ $user->name }}',
                    image: {
                        type: 'jpeg',
                        quality: 1
                    },
                    html2canvas: {
                        scale: 4,
                        dpi: 72,
                        letterRendering: true
                    },
                    jsPDF: {
                        unit: 'in',
                        format: 'A4'
                    }
                };
                html2pdf().set(opt).from(element).save().then(closeScript);
            }
        });
    </script>
@endsection
