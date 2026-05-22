<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        if (Auth::user()->can('manage-contacts')) {
            $query = Contact::query();

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            }

            // Sorting
            $sortField = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            
            // Validate sort field
            $allowedSortFields = ['name', 'email', 'subject', 'created_at'];
            if (!in_array($sortField, $allowedSortFields)) {
                $sortField = 'created_at';
            }
            
            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = $request->get('per_page', 10);
            $contacts = $query->paginate($perPage)->withQueryString();

            return Inertia::render('contacts/index', [
                'contacts' => $contacts,
                'filters' => $request->only(['search', 'sort_field', 'sort_direction', 'per_page'])
            ]);

        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function sendReply(Request $request, Contact $contact)
    {
        if (Auth::user()->can('send-reply-contacts')) {
            $request->validate([
                'subject' => 'required|string|max:255',
                'message' => 'required|string'
            ]);

            try {
                // Send email directly without template
                $config = setEmailConfigurations();                
                $fromEmail = getSetting('email_from_address') ?: config('mail.from.address');
                $fromName = getSetting('email_from_name') ?: config('mail.from.name');

                Mail::send([], [], function ($message) use ($contact, $request, $fromEmail, $fromName) {
                    $message->to($contact->email, $contact->name)
                        ->subject($request->subject)
                        ->html(nl2br(e($request->message)))
                        ->from($fromEmail, $fromName);
                });

                // Update contact status to 'Contacted'
                $contact->update(['status' => 'Contacted']);

                return redirect()->back()->with('success', 'Reply sent successfully.');
            } catch (\Exception $e) {
                Log::error('Failed to send reply: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Failed to send reply. Please check email settings.');
            }
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function updateStatus(Request $request, Contact $contact)
    {
        if (Auth::user()->can('update-contact-status')) {
            $request->validate([
                'status' => 'required|in:New,Contacted,Qualified,Converted,Closed'
            ]);

            $contact->update([
                'status' => $request->status
            ]);

            return redirect()->back()->with('success', 'Contact status updated successfully.');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }

    public function destroy(Contact $contact)
    {
        if (Auth::user()->can('delete-contacts')) {
            $contact->delete();

            return redirect()->back()->with('success', 'Contact deleted successfully.');
        } else {
            return redirect()->back()->with('error', __('Permission Denied.'));
        }
    }
}