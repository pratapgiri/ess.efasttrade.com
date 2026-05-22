<?php

namespace App\Http\Controllers;

use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\Payslip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class PayrollRunController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-payroll-runs')) {
            $query = PayrollRun::with(['creator'])->where(function ($q) {
                if (Auth::user()->can('manage-any-payroll-runs')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-payroll-runs')) {
                    $q->where('created_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && ! empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('title', 'like', '%'.$request->search.'%')
                        ->orWhere('notes', 'like', '%'.$request->search.'%');
                });
            }

            // Handle status filter
            if ($request->has('status') && ! empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle date range filter
            if ($request->has('date_from') && ! empty($request->date_from)) {
                $query->where('pay_period_start', '>=', $request->date_from);
            }
            if ($request->has('date_to') && ! empty($request->date_to)) {
                $query->where('pay_period_end', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && ! empty($request->sort_field)) {
                $sortField = $request->sort_field;
                $sortDirection = $request->sort_direction ?? 'asc';

                if ($sortField === 'pay_date') {
                    $query->orderBy('pay_date', $sortDirection);
                } else {
                    $query->orderBy('pay_period_start', 'desc');
                }
            } else {
                $query->orderBy('id', 'desc');
            }

            $payrollRuns = $query->paginate($request->per_page ?? 10);

            return Inertia::render('hr/payroll-runs/index', [
                'payrollRuns' => $payrollRuns,
                'hasSampleFile' => file_exists(storage_path('uploads/sample/sample-payroll-run.xlsx')),
                'filters' => $request->all(['search', 'status', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function show($payrollRunId)
    {
        if (Auth::user()->can('view-payroll-runs')) {
            $payrollRun = PayrollRun::where('id', $payrollRunId)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->with(['payrollEntries.employee'])
                ->first();

            if (! $payrollRun) {
                return redirect()->back()->with('error', __('Payroll run not found.'));
            }

            return Inertia::render('hr/payroll-runs/show', [
                'payrollRun' => $payrollRun,
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function store(Request $request)
    {
        if (Auth::user()->can('create-payroll-runs')) {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'payroll_frequency' => 'required|in:weekly,biweekly,monthly',
                'pay_period_start' => 'required|date',
                'pay_period_end' => 'required|date|after:pay_period_start',
                'pay_date' => 'required|date|after_or_equal:pay_period_end',
                'notes' => 'nullable|string',
            ]);

            $validated['created_by'] = creatorId();
            $validated['status'] = 'draft';

            // Check if payroll run already exists for this period
            $exists = PayrollRun::where('pay_period_start', $validated['pay_period_start'])
                ->where('pay_period_end', $validated['pay_period_end'])
                ->whereIn('created_by', getCompanyAndUsersId())
                ->exists();

            if ($exists) {
                return redirect()->back()->with('error', __('Payroll run already exists for this period.'));
            }

            PayrollRun::create($validated);

            return redirect()->back()->with('success', __('Payroll run created successfully.'));
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function update(Request $request, $payrollRunId)
    {
        if (Auth::user()->can('edit-payroll-runs')) {
            $payrollRun = PayrollRun::where('id', $payrollRunId)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();

            if ($payrollRun) {
                try {
                    $validated = $request->validate([
                        'title' => 'required|string|max:255',
                        'payroll_frequency' => 'required|in:weekly,biweekly,monthly',
                        'pay_period_start' => 'required|date',
                        'pay_period_end' => 'required|date|after:pay_period_start',
                        'pay_date' => 'required|date|after_or_equal:pay_period_end',
                        'notes' => 'nullable|string',
                    ]);

                    // Only allow updates if status is draft
                    if ($payrollRun->status !== 'draft') {
                        return redirect()->back()->with('error', __('Cannot update processed payroll run.'));
                    }

                    $payrollRun->update($validated);

                    return redirect()->back()->with('success', __('Payroll run updated successfully'));
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update payroll run'));
                }
            } else {
                return redirect()->back()->with('error', __('Payroll run Not Found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function destroy($payrollRunId)
    {
        if (Auth::user()->can('delete-payroll-runs')) {
            $payrollRun = PayrollRun::where('id', $payrollRunId)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();

            if ($payrollRun) {
                try {
                    // Only allow deletion if status is draft
                    if ($payrollRun->status !== 'draft') {
                        return redirect()->back()->with('error', __('Cannot delete processed payroll run.'));
                    }

                    $payrollRun->delete();

                    return redirect()->back()->with('success', __('Payroll run deleted successfully'));
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete payroll run'));
                }
            } else {
                return redirect()->back()->with('error', __('Payroll run Not Found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function process($payrollRunId)
    {
        if (Auth::user()->can('process-payroll-runs')) {
            $payrollRun = PayrollRun::where('id', $payrollRunId)
                ->whereIn('created_by', getCompanyAndUsersId())
                ->first();

            if ($payrollRun) {
                try {
                    if ($payrollRun->status !== 'draft') {
                        return redirect()->back()->with('error', __('Payroll run is not in draft status.'));
                    }

                    $success = $payrollRun->processPayroll();

                    if ($success) {
                        return redirect()->back()->with('success', __('Payroll run processed successfully'));
                    } else {
                        return redirect()->back()->with('error', __('Failed to process payroll run'));
                    }
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to process payroll run'));
                }
            } else {
                return redirect()->back()->with('error', __('Payroll run Not Found.'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function destroyEntry($payrollEntryId)
    {
        $payrollEntry = PayrollEntry::where('id', $payrollEntryId)
            ->whereHas('payrollRun', function ($q) {
                $q->whereIn('created_by', getCompanyAndUsersId());
            })
            ->with('payrollRun')
            ->first();

        if (! $payrollEntry) {
            return redirect()->back()->with('error', __('Payroll entry not found.'));
        }

        try {
            $payrollRun = $payrollEntry->payrollRun;

            // Delete associated payslip if exists
            Payslip::where('payroll_entry_id', $payrollEntry->id)->delete();

            $payrollEntry->delete();

            if ($payrollRun) {
                $payrollRun->calculateTotals();
                $payrollRun->update(['status' => 'draft']);
            }

            return redirect()->back()->with('success', __('Payroll entry deleted successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to delete payroll entry'));
        }
    }

    public function export()
    {
        if (Auth::user()->can('export-payroll-runs')) {
            try {
                $payrollRuns = PayrollRun::whereIn('created_by', getCompanyAndUsersId())->get();

                $fileName = 'payroll_runs_'.date('Y-m-d_His').'.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                ];

                $callback = function () use ($payrollRuns) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, ['Title', 'Payroll Frequency', 'Pay Period Start', 'Pay Period End', 'Pay Date', 'Status', 'Notes']);

                    foreach ($payrollRuns as $run) {
                        fputcsv($file, [
                            $run->title,
                            $run->payroll_frequency,
                            \Carbon\Carbon::parse($run->pay_period_start)->format('Y-m-d'),
                            \Carbon\Carbon::parse($run->pay_period_end)->format('Y-m-d'),
                            \Carbon\Carbon::parse($run->pay_date)->format('Y-m-d'),
                            $run->status,
                            $run->notes ?? '',
                        ]);
                    }
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            } catch (\Exception $e) {
                return response()->json(['message' => __('Failed to export payroll runs')], 500);
            }
        } else {
            return response()->json(['message' => __('Permission Denied.')], 403);
        }
    }

    public function downloadTemplate()
    {
        $filePath = storage_path('uploads/sample/sample-payroll-run.xlsx');
        if (! file_exists($filePath)) {
            return response()->json(['error' => __('Template file not available')], 404);
        }

        return response()->download($filePath, 'sample-payroll-run.xlsx');
    }

    public function parseFile(Request $request)
    {
        if (Auth::user()->can('import-payroll-runs')) {
            $rules = ['file' => 'required|mimes:csv,txt,xlsx,xls'];
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(['message' => $validator->getMessageBag()->first()]);
            }

            try {
                $file = $request->file('file');
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
                $worksheet = $spreadsheet->getActiveSheet();
                $highestColumn = $worksheet->getHighestColumn();
                $highestRow = $worksheet->getHighestRow();
                $headers = [];

                for ($col = 'A'; $col <= $highestColumn; $col++) {
                    $value = $worksheet->getCell($col.'1')->getValue();
                    if ($value) {
                        $headers[] = (string) $value;
                    }
                }

                $previewData = [];
                for ($row = 2; $row <= $highestRow; $row++) {
                    $rowData = [];
                    $colIndex = 0;
                    for ($col = 'A'; $col <= $highestColumn; $col++) {
                        if ($colIndex < count($headers)) {
                            $rowData[$headers[$colIndex]] = (string) $worksheet->getCell($col.$row)->getValue();
                        }
                        $colIndex++;
                    }
                    $previewData[] = $rowData;
                }

                return response()->json(['excelColumns' => $headers, 'previewData' => $previewData]);
            } catch (\Exception $e) {
                return response()->json(['message' => __('Failed to parse file')]);
            }
        } else {
            return response()->json(['message' => __('Permission denied.')], 403);
        }
    }

    public function fileImport(Request $request)
    {
        if (Auth::user()->can('import-payroll-runs')) {
            $rules = ['data' => 'required|array'];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return redirect()->back()->with('error', $validator->getMessageBag()->first());
            }

            try {
                $data = $request->data;
                $imported = 0;
                $skipped = 0;

                foreach ($data as $row) {
                    try {
                        if (empty($row['title']) || empty($row['pay_period_start']) || empty($row['pay_period_end']) || empty($row['pay_date'])) {
                            $skipped++;
                            continue;
                        }

                        // Validate dates
                        if ($row['pay_period_end'] <= $row['pay_period_start']) {
                            $skipped++;
                            continue;
                        }

                        if ($row['pay_date'] < $row['pay_period_end']) {
                            $skipped++;
                            continue;
                        }

                        // Check if payroll run already exists for this period
                        $exists = PayrollRun::where('pay_period_start', $row['pay_period_start'])
                            ->where('pay_period_end', $row['pay_period_end'])
                            ->whereIn('created_by', getCompanyAndUsersId())
                            ->exists();

                        if ($exists) {
                            $skipped++;
                            continue;
                        }

                        PayrollRun::create([
                            'title' => $row['title'],
                            'payroll_frequency' => $row['payroll_frequency'] ?? 'monthly',
                            'pay_period_start' => $row['pay_period_start'],
                            'pay_period_end' => $row['pay_period_end'],
                            'pay_date' => $row['pay_date'],
                            'status' => $row['status'] ?? 'draft',
                            'notes' => $row['notes'] ?? null,
                            'created_by' => creatorId(),
                        ]);

                        $imported++;
                    } catch (\Exception $e) {
                        $skipped++;
                    }
                }

                return redirect()->back()->with('success',
                    __('Import completed: :added payroll runs added, :skipped payroll runs skipped', [
                        'added' => $imported,
                        'skipped' => $skipped,
                    ])
                );
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to import'));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
