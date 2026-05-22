<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TimeEntryController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-time-entries')) {
            $query = TimeEntry::with(['employee', 'approver', 'creator'])->where(function ($q) {
                if (Auth::user()->can('manage-any-time-entries')) {
                    $q->whereIn('created_by', getCompanyAndUsersId());
                } elseif (Auth::user()->can('manage-own-time-entries')) {
                    $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id())->orWhere('approved_by', Auth::id());
                } else {
                    $q->whereRaw('1 = 0');
                }
            });

            // Handle search
            if ($request->has('search') && ! empty($request->search)) {
                $query->where(function ($q) use ($request) {
                    $q->where('description', 'like', '%'.$request->search.'%')
                        ->orWhere('project', 'like', '%'.$request->search.'%')
                        ->orWhereHas('employee', function ($subQ) use ($request) {
                            $subQ->where('name', 'like', '%'.$request->search.'%');
                        });
                });
            }

            // Handle employee filter
            if ($request->has('employee_id') && ! empty($request->employee_id) && $request->employee_id !== 'all') {
                $query->where('employee_id', $request->employee_id);
            }

            // Handle status filter
            if ($request->has('status') && ! empty($request->status) && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Handle project filter
            if ($request->has('project') && ! empty($request->project) && $request->project !== 'all') {
                $query->where('project', $request->project);
            }

            // Handle date range filter
            if ($request->has('date_from') && ! empty($request->date_from)) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->has('date_to') && ! empty($request->date_to)) {
                $query->where('date', '<=', $request->date_to);
            }

            // Handle sorting
            if ($request->has('sort_field') && ! empty($request->sort_field)) {
                $sortField = $request->sort_field;
                $sortDirection = $request->sort_direction ?? 'asc';

                if ($sortField === 'created_at') {
                    $query->orderBy('created_at', $sortDirection);
                } else {
                    $query->orderBy('date', 'desc');
                }
            } else {
                $query->orderBy('id', 'desc');
            }

            $timeEntries = $query->paginate($request->per_page ?? 10);

            // Get employees for filter dropdown
            $employees = User::where('type', 'employee')
                ->whereIn('created_by', getCompanyAndUsersId())
                ->get(['id', 'name']);

            // Get unique projects for filter dropdown
            $projects = TimeEntry::whereIn('created_by', getCompanyAndUsersId())
                ->whereNotNull('project')
                ->distinct()
                ->pluck('project');

            return Inertia::render('hr/time-entries/index', [
                'timeEntries' => $timeEntries,
                'employees' => $this->getFilteredEmployees(),
                'projects' => $projects,
                'hasSampleFile' => file_exists(storage_path('uploads/sample/sample-time-entry.xlsx')),
                'filters' => $request->all(['search', 'employee_id', 'status', 'project', 'date_from', 'date_to', 'sort_field', 'sort_direction', 'per_page']),
            ]);
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    private function getFilteredEmployees()
    {
        // Get employees for filter dropdown (compatible with getFilteredEmployees logic)
        $employeeQuery = Employee::whereIn('created_by', getCompanyAndUsersId());

        if (Auth::user()->can('manage-own-time-entries') && ! Auth::user()->can('manage-any-time-entries')) {
            $employeeQuery->where(function ($q) {
                $q->where('created_by', Auth::id())->orWhere('user_id', Auth::id());
            });
        }

        $employees = User::emp()
            ->with('employee')
            ->whereIn('created_by', getCompanyAndUsersId())
            ->where('status', 'active')
            ->whereIn('id', $employeeQuery->pluck('user_id'))
            ->select('id', 'name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee->employee_id ?? '',
                ];
            });

        return $employees;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'hours' => 'required|numeric|min:0.5|max:24',
            'description' => 'required|string',
            'project' => 'nullable|string|max:255',
        ]);

        $validated['created_by'] = creatorId();

        TimeEntry::create($validated);

        return redirect()->back()->with('success', __('Time entry created successfully.'));
    }

    public function update(Request $request, $timeEntryId)
    {
        $timeEntry = TimeEntry::where('id', $timeEntryId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($timeEntry) {
            try {
                $validated = $request->validate([
                    'employee_id' => 'required|exists:users,id',
                    'date' => 'required|date',
                    'hours' => 'required|numeric|min:0.5|max:24',
                    'description' => 'required|string',
                    'project' => 'nullable|string|max:255',
                ]);

                // Only allow updates if status is pending
                if ($timeEntry->status !== 'pending') {
                    return redirect()->back()->with('error', __('Cannot update processed time entry.'));
                }

                $timeEntry->update($validated);

                return redirect()->back()->with('success', __('Time entry updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update time entry'));
            }
        } else {
            return redirect()->back()->with('error', __('Time entry Not Found.'));
        }
    }

    public function destroy($timeEntryId)
    {
        $timeEntry = TimeEntry::where('id', $timeEntryId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($timeEntry) {
            try {
                // Only allow deletion if status is pending
                if ($timeEntry->status !== 'pending') {
                    return redirect()->back()->with('error', __('Cannot delete processed time entry.'));
                }

                $timeEntry->delete();

                return redirect()->back()->with('success', __('Time entry deleted successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to delete time entry'));
            }
        } else {
            return redirect()->back()->with('error', __('Time entry Not Found.'));
        }
    }

    public function updateStatus(Request $request, $timeEntryId)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'manager_comments' => 'nullable|string',
        ]);

        $timeEntry = TimeEntry::where('id', $timeEntryId)
            ->whereIn('created_by', getCompanyAndUsersId())
            ->first();

        if ($timeEntry) {
            try {
                $timeEntry->update([
                    'status' => $validated['status'],
                    'manager_comments' => $validated['manager_comments'],
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                return redirect()->back()->with('success', __('Time entry status updated successfully'));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage() ?: __('Failed to update time entry status'));
            }
        } else {
            return redirect()->back()->with('error', __('Time entry Not Found.'));
        }
    }

    public function export()
    {
        if (Auth::user()->can('export-time-entry')) {
            try {
                $timeEntries = TimeEntry::with(['employee', 'approver'])
                    ->where(function ($q) {
                        if (Auth::user()->can('manage-any-time-entries')) {
                            $q->whereIn('created_by', getCompanyAndUsersId());
                        } elseif (Auth::user()->can('manage-own-time-entries')) {
                            $q->where('created_by', Auth::id())->orWhere('employee_id', Auth::id())->orWhere('approved_by', Auth::id());
                        } else {
                            $q->whereRaw('1 = 0');
                        }
                    })->orderBy('date', 'desc')->get();

                $fileName = 'time_entries_'.date('Y-m-d_His').'.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                ];

                $callback = function () use ($timeEntries) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, [
                        'Employee',
                        'Date',
                        'Hours',
                        'Project',
                        'Description',
                        'Status',
                        'Approved By',
                        'Approved At',
                        'Submitted On',
                    ]);

                    foreach ($timeEntries as $entry) {
                        fputcsv($file, [
                            $entry->employee->name ?? '',
                            $entry->date ? date('Y-m-d', strtotime($entry->date)) : '',
                            $entry->hours ?? '',
                            $entry->project ?? '',
                            $entry->description ?? '',
                            $entry->status ?? '',
                            $entry->approver->name ?? '',
                            $entry->approved_at ?? '',
                            $entry->created_at ?? '',
                        ]);
                    }
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            } catch (\Exception $e) {
                return response()->json(['message' => __('Failed to export time entries: :message', ['message' => $e->getMessage()])], 500);
            }
        } else {
            return response()->json(['message' => __('Permission Denied.')], 403);
        }
    }

    public function downloadTemplate()
    {
        $filePath = storage_path('uploads/sample/sample-time-entry.xlsx');
        if (! file_exists($filePath)) {
            return response()->json(['error' => __('Template file not available')], 404);
        }

        return response()->download($filePath, 'sample-time-entry.xlsx');
    }

    public function parseFile(Request $request)
    {
        if (Auth::user()->can('import-time-entry')) {
            $rules = ['file' => 'required|mimes:csv,txt,xlsx,xls'];
            $validator = Validator::make($request->all(), $rules);

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
                return response()->json(['message' => __('Failed to parse file: :error', ['error' => $e->getMessage()])]);
            }
        } else {
            return response()->json(['message' => __('Permission denied.')], 403);
        }
    }

    public function fileImport(Request $request)
    {
        if (Auth::user()->can('import-time-entry')) {
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
                        if (empty($row['employee']) || empty($row['date']) || empty($row['hours'])) {
                            $skipped++;

                            continue;
                        }

                        $employee = User::where('name', $row['employee'])
                            ->whereIn('created_by', getCompanyAndUsersId())
                            ->where('type', 'employee')
                            ->first();

                        if (! $employee) {
                            $skipped++;

                            continue;
                        }

                        // Check if time entry already exists for this employee and date
                        $exists = TimeEntry::where('employee_id', $employee->id)
                            ->whereDate('date', $row['date'])
                            ->exists();

                        if ($exists) {
                            $skipped++;
                            continue;
                        }

                        TimeEntry::create([
                            'employee_id' => $employee->id,
                            'date' => $row['date'],
                            'hours' => $row['hours'],
                            'project' => $row['project'] ?? null,
                            'description' => $row['description'] ?? '',
                            'status' => 'pending',
                            'created_by' => creatorId(),
                        ]);

                        $imported++;
                    } catch (\Exception $e) {
                        $skipped++;
                    }
                }

                return redirect()->back()->with('success', __('Import completed: :added time entries added, :skipped time entries skipped', ['added' => $imported, 'skipped' => $skipped]));
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Failed to import: :error', ['error' => $e->getMessage()]));
            }
        } else {
            return redirect()->back()->with('error', __('Permission denied.'));
        }
    }
}
