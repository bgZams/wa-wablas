<?php

namespace App\Http\Controllers;

use App\Models\AutoReply;
use App\Models\Setting;
use Illuminate\Http\Request;

class AutoReplyController extends Controller
{
    /**
     * Display a listing of auto-replies with filters and summary statistics.
     */
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $matchType = $request->input('match_type', '');
        $status = $request->input('status', '');

        $query = AutoReply::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('keyword', 'LIKE', "%{$search}%")
                  ->orWhere('reply', 'LIKE', "%{$search}%");
            });
        }

        if ($matchType !== '' && in_array($matchType, ['exact', 'contains', 'starts_with'])) {
            $query->where('match_type', $matchType);
        }

        if ($status !== '' && in_array($status, ['1', '0'])) {
            $query->where('active', (bool)$status);
        }

        $rules = $query->orderByDesc('id')->paginate(15)->withQueryString();

        // Calculate summary statistics
        $allRules = AutoReply::all();
        $stats = [
            'total' => $allRules->count(),
            'active' => $allRules->where('active', true)->count(),
            'inactive' => $allRules->where('active', false)->count(),
            'exact' => $allRules->where('match_type', 'exact')->count(),
            'contains' => $allRules->where('match_type', 'contains')->count(),
            'starts_with' => $allRules->where('match_type', 'starts_with')->count(),
        ];

        $autoReplyEnabled = Setting::get('auto_reply_enabled', '1') === '1';

        return view('autoreply.index', compact(
            'rules',
            'stats',
            'search',
            'matchType',
            'status',
            'autoReplyEnabled'
        ));
    }

    /**
     * Store a newly created auto-reply in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'keyword' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,starts_with',
            'reply' => 'required|string',
            'active' => 'nullable',
        ]);

        $rule = AutoReply::create([
            'keyword' => trim($data['keyword']),
            'match_type' => $data['match_type'],
            'reply' => trim($data['reply']),
            'active' => $request->has('active') ? (bool)$request->input('active') : true,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Aturan auto-reply berhasil ditambahkan.',
                'rule' => $rule,
            ]);
        }

        return redirect()->route('autoreply.manage')
            ->with('success', 'Aturan auto-reply baru berhasil disimpan.');
    }

    /**
     * Update the specified auto-reply in storage.
     */
    public function update(Request $request, $id)
    {
        $rule = AutoReply::findOrFail($id);

        $data = $request->validate([
            'keyword' => 'required|string|max:255',
            'match_type' => 'required|in:exact,contains,starts_with',
            'reply' => 'required|string',
            'active' => 'nullable',
        ]);

        $rule->update([
            'keyword' => trim($data['keyword']),
            'match_type' => $data['match_type'],
            'reply' => trim($data['reply']),
            'active' => $request->has('active') ? (bool)$request->input('active') : $rule->active,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Aturan auto-reply berhasil diperbarui.',
                'rule' => $rule,
            ]);
        }

        return redirect()->route('autoreply.manage')
            ->with('success', 'Aturan auto-reply berhasil diperbarui.');
    }

    /**
     * Remove the specified auto-reply from storage.
     */
    public function destroy(Request $request, $id)
    {
        $rule = AutoReply::findOrFail($id);
        $keyword = $rule->keyword;
        $rule->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Aturan untuk kata kunci '{$keyword}' berhasil dihapus.",
            ]);
        }

        return redirect()->route('autoreply.manage')
            ->with('success', "Aturan untuk kata kunci '{$keyword}' berhasil dihapus.");
    }

    /**
     * Toggle the active status of an auto-reply.
     */
    public function toggle(Request $request, $id)
    {
        $rule = AutoReply::findOrFail($id);
        $rule->active = !$rule->active;
        $rule->save();

        return response()->json([
            'success' => true,
            'active' => $rule->active,
            'message' => 'Status aktif aturan berhasil diubah menjadi ' . ($rule->active ? 'Aktif' : 'Nonaktif') . '.',
        ]);
    }

    /**
     * Toggle the global auto-reply master switch.
     */
    public function toggleGlobal(Request $request)
    {
        $enabled = $request->boolean('enabled');
        Setting::put('auto_reply_enabled', $enabled ? '1' : '0');

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'message' => 'Fitur Auto-Reply Bot berhasil ' . ($enabled ? 'diaktifkan' : 'dinonaktifkan') . '.',
        ]);
    }

    /**
     * Test a message string against all active auto-reply rules.
     */
    public function test(Request $request)
    {
        $message = trim((string)$request->input('message', ''));

        if ($message === '') {
            return response()->json([
                'success' => false,
                'message' => 'Harap masukkan teks pesan untuk diuji.',
            ], 422);
        }

        $activeRules = AutoReply::where('active', true)->get();
        $matchedRule = $activeRules->first(fn ($r) => $r->matches($message));

        if ($matchedRule) {
            return response()->json([
                'success' => true,
                'matched' => true,
                'rule' => [
                    'id' => $matchedRule->id,
                    'keyword' => $matchedRule->keyword,
                    'match_type' => $matchedRule->match_type,
                    'reply' => $matchedRule->reply,
                ],
                'reply' => $matchedRule->reply,
            ]);
        }

        return response()->json([
            'success' => true,
            'matched' => false,
            'message' => "Tidak ada aturan aktif yang cocok dengan pesan '{$message}'.",
        ]);
    }
}
