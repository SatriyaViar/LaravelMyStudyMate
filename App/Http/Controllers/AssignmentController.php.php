<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id(); // Ganti dengan token auth jika belum pakai Sanctum
        $query = DB::table('assignments')->where('user_id', $userId);

        if ($request->filled('search')) {
            $query->where('title', 'ilike', "%{$request->search}%");
        }

        $assignments = $query->orderBy('deadline', 'asc')->get();

        return response()->json($assignments);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'required|date',
        ]);

        $id = DB::table('assignments')->insertGetId([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'deadline' => $request->deadline,
            'is_done' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');

        return response()->json(['id' => $id, 'message' => 'Assignment created'], 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'deadline' => 'sometimes|date',
            'is_done' => 'sometimes|boolean',
        ]);

        DB::table('assignments')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->update($request->only(['title', 'description', 'deadline', 'is_done']) + ['updated_at' => now()]);

        return response()->json(['message' => 'Assignment updated']);
    }

    public function destroy($id)
    {
        DB::table('assignments')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->delete();

        return response()->json(['message' => 'Assignment deleted']);
    }

    public function markAsDone($id)
    {
        DB::table('assignments')
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->update(['is_done' => true, 'updated_at' => now()]);

        return response()->json(['message' => 'Marked as done']);
    }

    public function weeklyProgress()
    {
        $userId = Auth::id();
        $startOfWeek = Carbon::now()->startOfWeek();
        $endOfWeek = Carbon::now()->endOfWeek();

        $total = DB::table('assignments')
            ->where('user_id', $userId)
            ->whereBetween('deadline', [$startOfWeek, $endOfWeek])
            ->count();

        $done = DB::table('assignments')
            ->where('user_id', $userId)
            ->where('is_done', true)
            ->whereBetween('deadline', [$startOfWeek, $endOfWeek])
            ->count();

        $progress = $total > 0 ? round(($done / $total) * 100) : 0;

        return response()->json([
            'progress' => $progress,
            'done' => $done,
            'total' => $total
        ]);
    }
}