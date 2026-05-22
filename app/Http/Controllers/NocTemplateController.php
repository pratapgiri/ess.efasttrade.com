<?php

namespace App\Http\Controllers;

use App\Models\NocTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NocTemplateController extends Controller
{
    public function update(Request $request)
    {
        if (Auth::user()->can('update-noc')) {
            $request->validate([
                'content' => 'required|string'
            ]);

            if ($request->templateId) {
                // Update existing template
                $template = NocTemplate::where('id', $request->templateId)
                    ->where('created_by', auth::id())
                    ->firstOrFail();
                $template->update(['content' => $request->content]);
            } else {
                // Create or update by language
                $template = NocTemplate::updateOrCreate(
                    [
                        'language' => $request->language,
                        'created_by' => auth::id()
                    ],
                    [
                        'content' => $request->content
                    ]
                );
            }

            return redirect()->back()->with('success', __('NOC template updated successfully.'));

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}